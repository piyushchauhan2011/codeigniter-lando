<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobModel;
use App\Models\PaymentAttemptModel;
use App\Models\PaymentDeadLetterModel;
use App\Models\PaymentIntentModel;
use CodeIgniter\Queue\Config\Services as QueueServices;
use Config\Services;
use Throwable;

class EmployerPayments extends BaseController
{
    private const AMOUNT_CENTS = 4999;

    /** @var list<string> */
    private const SCENARIOS = ['success', 'transient_fail', 'permanent_fail', 'exhaust_retries'];

    public function create(int $jobId)
    {
        $auth = Services::portalAuth();
        $job  = model(JobModel::class, false)->find($jobId);

        if ($job === null || (int) $job['employer_user_id'] !== $auth->id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $scenario = (string) $this->request->getPost('scenario');
        if (! in_array($scenario, self::SCENARIOS, true)) {
            return redirect()->back()->with('error', 'Choose a valid fake payment scenario.');
        }

        $idempotencyKey = trim((string) $this->request->getPost('idempotency_key'));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            return redirect()->back()->with('error', 'Missing idempotency key.');
        }

        $intentModel = model(PaymentIntentModel::class, false);
        $intent      = $intentModel->findByIdempotencyKey($idempotencyKey);
        $intentId    = (int) ($intent['id'] ?? 0);
        if ($intent === null) {
            $intentId = $intentModel->insert([
                'job_id'           => $jobId,
                'employer_user_id' => $auth->id(),
                'idempotency_key'  => $idempotencyKey,
                'amount_cents'     => self::AMOUNT_CENTS,
                'currency'         => 'USD',
                'scenario'         => $scenario,
                'status'           => 'pending',
            ], true);

            $intentId = (int) $intentId;
            if ($intentId < 1) {
                return redirect()->back()->with('error', 'Could not create payment intent.');
            }

            try {
                $push = QueueServices::queue()->push('payments', 'process-featured-job-payment', [
                    'payment_intent_id' => $intentId,
                ]);
            } catch (Throwable $e) {
                $intentModel->update($intentId, [
                    'status'     => 'failed',
                    'last_error' => 'Queue push failed: ' . $e->getMessage(),
                ]);

                return redirect()
                    ->to(site_url(Services::portalLocale()->localizePath('employer/payments/' . $intentId)))
                    ->with('error', 'Payment intent was created, but RabbitMQ was not reachable.');
            }

            if (! $push->getStatus()) {
                $intentModel->update($intentId, [
                    'status'     => 'failed',
                    'last_error' => 'Queue push failed: ' . ($push->getError() ?? 'unknown error'),
                ]);

                return redirect()
                    ->to(site_url(Services::portalLocale()->localizePath('employer/payments/' . $intentId)))
                    ->with('error', 'Payment intent was created, but the queue rejected the job.');
            }
        }

        if ($intentId < 1) {
            return redirect()->back()->with('error', 'Could not load the existing payment intent.');
        }

        return redirect()
            ->to(site_url(Services::portalLocale()->localizePath('employer/payments/' . $intentId)))
            ->with('message', 'Payment intent queued. Start the payment worker to process it.');
    }

    public function show(int $paymentIntentId): string
    {
        $auth        = Services::portalAuth();
        $intentModel = model(PaymentIntentModel::class, false);
        $intent      = $intentModel->findWithJobForEmployer($paymentIntentId, (int) $auth->id());

        if ($intent === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('portal/employer/payment_show', [
            'title'      => 'Featured job payment',
            'intent'     => $intent,
            'attempts'   => model(PaymentAttemptModel::class)->findForIntent($paymentIntentId),
            'deadLetter' => model(PaymentDeadLetterModel::class)->findForIntent($paymentIntentId),
        ]);
    }
}
