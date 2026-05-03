<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class ElkLab extends BaseController
{
    public function index(): string
    {
        return view('portal/elk_lab', [
            'title' => 'ELK Learning Lab',
        ]);
    }

    public function logDemo(): \CodeIgniter\HTTP\RedirectResponse
    {
        log_message('info', json_encode([
            'message' => 'ELK lab demo info log',
            'event'   => ['dataset' => 'codeigniter.elk_lab'],
            'labels'  => ['demo_kind' => 'info'],
        ], JSON_THROW_ON_ERROR));

        log_message('warning', json_encode([
            'message' => 'ELK lab demo warning log',
            'event'   => ['dataset' => 'codeigniter.elk_lab'],
            'labels'  => ['demo_kind' => 'warning'],
        ], JSON_THROW_ON_ERROR));

        return redirect()->to(site_url('learning/elk'))->with('message', 'Wrote demo info and warning logs.');
    }

    public function handledError(): \CodeIgniter\HTTP\RedirectResponse
    {
        try {
            throw new \RuntimeException('Handled ELK lab exception for APM/log grouping practice.');
        } catch (\RuntimeException $exception) {
            log_message('error', json_encode([
                'message'   => $exception->getMessage(),
                'event'     => ['dataset' => 'codeigniter.elk_lab'],
                'error'     => [
                    'type'        => $exception::class,
                    'message'     => $exception->getMessage(),
                    'stack_trace' => $exception->getTraceAsString(),
                ],
                'log.origin' => [
                    'file.name' => $exception->getFile(),
                    'file.line' => $exception->getLine(),
                ],
                'labels' => ['demo_kind' => 'handled_exception'],
            ], JSON_THROW_ON_ERROR));
        }

        return redirect()->to(site_url('learning/elk'))->with('message', 'Logged a handled exception.');
    }

    public function unhandledError(): never
    {
        throw new \RuntimeException('Unhandled ELK lab exception. Kibana APM should group repeated hits.');
    }

    public function slowRequest(): \CodeIgniter\HTTP\RedirectResponse
    {
        usleep(650_000);
        log_message('notice', json_encode([
            'message' => 'ELK lab slow request completed',
            'event'   => ['dataset' => 'codeigniter.elk_lab'],
            'labels'  => ['demo_kind' => 'slow_request', 'simulated_delay_ms' => 650],
        ], JSON_THROW_ON_ERROR));

        return redirect()->to(site_url('learning/elk'))->with('message', 'Completed a simulated slow request.');
    }

    public function notFound(): never
    {
        throw PageNotFoundException::forPageNotFound('ELK lab generated 404.');
    }
}
