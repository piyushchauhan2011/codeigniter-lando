<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\JobModel;
use App\Models\PaymentAttemptModel;
use App\Models\PaymentDeadLetterModel;
use App\Models\PaymentIntentModel;
use App\Payments\FakePaymentGateway;
use App\Payments\PermanentPaymentException;
use App\Payments\TransientPaymentException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Queue\BaseJob;

class ProcessFeaturedJobPayment extends BaseJob
{
    protected int $retryAfter = 30;

    protected int $tries = 3;

    public function process(): void
    {
        $intentId = (int) ($this->data['payment_intent_id'] ?? 0);
        if ($intentId < 1) {
            log_message('warning', 'ProcessFeaturedJobPayment: invalid payload {data}', ['data' => $this->data]);

            return;
        }

        $intentModel = model(PaymentIntentModel::class, false);
        $intent      = $intentModel->find($intentId);
        if ($intent === null) {
            log_message('warning', 'ProcessFeaturedJobPayment: payment intent not found id={id}', ['id' => $intentId]);

            return;
        }

        if (in_array($intent['status'], ['paid', 'dead_lettered'], true)) {
            log_message('info', 'ProcessFeaturedJobPayment: idempotent no-op for intent {id} with status {status}', [
                'id'     => $intentId,
                'status' => $intent['status'],
            ]);

            return;
        }

        $attemptNumber = (int) $intent['attempts_count'] + 1;
        $attemptModel  = model(PaymentAttemptModel::class, false);
        $attemptId     = $attemptModel->insert([
            'payment_intent_id' => $intentId,
            'attempt_number'    => $attemptNumber,
            'status'            => 'processing',
            'scenario'          => $intent['scenario'],
        ], true);

        $intentModel->update($intentId, [
            'status'         => 'processing',
            'attempts_count' => $attemptNumber,
            'last_error'     => null,
        ]);

        try {
            $result = (new FakePaymentGateway())->charge($intent, $attemptNumber);
        } catch (PermanentPaymentException $e) {
            $this->markDeadLettered($intent, $attemptId, 'permanent_failure', $e->getMessage());

            return;
        } catch (TransientPaymentException $e) {
            if ($attemptNumber >= $this->tries) {
                $this->markDeadLettered($intent, $attemptId, 'retries_exhausted', $e->getMessage());
            } else {
                $attemptModel->update($attemptId, [
                    'status'        => 'retrying',
                    'error_message' => $e->getMessage(),
                ]);
                $intentModel->update($intentId, [
                    'status'     => 'retrying',
                    'last_error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }

        $now = Time::now()->toDateTimeString();
        $attemptModel->update($attemptId, [
            'status'             => 'paid',
            'provider_reference' => $result->providerReference,
            'error_message'      => null,
        ]);
        $intentModel->update($intentId, [
            'status'             => 'paid',
            'provider_reference' => $result->providerReference,
            'last_error'         => null,
            'paid_at'            => $now,
        ]);
        model(JobModel::class, false)->update((int) $intent['job_id'], [
            'is_featured'    => 1,
            'featured_until' => Time::now()->addDays(30)->toDateTimeString(),
        ]);

        log_message('info', 'ProcessFeaturedJobPayment: paid intent {id}, job {job} is featured until {until}', [
            'id'    => $intentId,
            'job'   => (int) $intent['job_id'],
            'until' => Time::now()->addDays(30)->toDateTimeString(),
        ]);
    }

    /**
     * @param array<string, mixed> $intent
     */
    private function markDeadLettered(array $intent, int|string $attemptId, string $reason, string $message): void
    {
        $intentId = (int) $intent['id'];
        $now      = Time::now()->toDateTimeString();

        model(PaymentAttemptModel::class, false)->update($attemptId, [
            'status'        => 'dead_lettered',
            'error_message' => $message,
        ]);

        model(PaymentIntentModel::class, false)->update($intentId, [
            'status'           => 'dead_lettered',
            'last_error'       => $message,
            'dead_lettered_at' => $now,
        ]);

        $deadLetterModel = model(PaymentDeadLetterModel::class, false);
        if ($deadLetterModel->findForIntent($intentId) === null) {
            $payload = json_encode([
                'payment_intent_id' => $intentId,
                'job_id'            => (int) $intent['job_id'],
                'scenario'          => (string) $intent['scenario'],
                'idempotency_key'   => (string) $intent['idempotency_key'],
            ]);

            $deadLetterModel->insert([
                'payment_intent_id' => $intentId,
                'job_id'            => (int) $intent['job_id'],
                'employer_user_id'  => (int) $intent['employer_user_id'],
                'reason'            => $reason,
                'error_message'     => $message,
                'payload'           => $payload === false ? null : $payload,
            ]);
        }

        log_message('error', 'ProcessFeaturedJobPayment: dead-lettered intent {id} reason={reason} message={message}', [
            'id'      => $intentId,
            'reason'  => $reason,
            'message' => $message,
        ]);
    }
}
