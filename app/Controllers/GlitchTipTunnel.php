<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\GlitchTip as GlitchTipConfig;

/**
 * Sentry-compatible envelope tunnel for GlitchTip.
 *
 * @see https://docs.sentry.io/platforms/javascript/troubleshooting/#using-the-tunnel-option
 */
class GlitchTipTunnel extends BaseController
{
    /**
     * Forward POST body (application/x-sentry-envelope) to GlitchTip inside Docker/Lando.
     *
     * {@see IncomingRequest::getMethod()} returns uppercase verbs (e.g. POST).
     */
    public function forward(): ResponseInterface
    {
        if ($this->request->is('options')) {
            return $this->response->setStatusCode(204)->setHeader('Allow', 'POST, OPTIONS');
        }

        if ($this->request->is('get')) {
            return $this->response->setStatusCode(405)
                ->setHeader('Allow', 'POST, OPTIONS')
                ->setBody('Use POST with a Sentry envelope (same-origin tunnel from @sentry/browser).');
        }

        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setHeader('Allow', 'POST, OPTIONS')->setBody('Method Not Allowed');
        }

        /** @var GlitchTipConfig $config */
        $config = config(GlitchTipConfig::class);

        if ($config->internalIngestBase === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'error' => 'GlitchTip ingest proxy not configured (glitchtip.internalIngestBase).',
            ]);
        }

        $body = $this->request->getBody();

        if ($body === '') {
            return $this->response->setStatusCode(400)->setBody('Empty body');
        }

        $newline = strpos($body, "\n");
        $headerLine = $newline === false ? $body : substr($body, 0, $newline);
        $header = json_decode($headerLine, true);

        if (! is_array($header) || ! isset($header['dsn']) || ! is_string($header['dsn'])) {
            return $this->response->setStatusCode(400)->setBody('Invalid envelope header');
        }

        $parts = parse_url($header['dsn']);

        if ($parts === false || ! isset($parts['host'])) {
            return $this->response->setStatusCode(400)->setBody('Invalid DSN URL');
        }

        $projectId = isset($parts['path']) ? trim($parts['path'], '/') : '';

        if ($projectId === '' || ! preg_match('/^[0-9]+$/', $projectId)) {
            return $this->response->setStatusCode(400)->setBody('Invalid project id in DSN');
        }

        if ($config->allowedProjectIds !== [] && ! in_array($projectId, $config->allowedProjectIds, true)) {
            return $this->response->setStatusCode(403)->setBody('Project not allowed');
        }

        $host = strtolower($parts['host']);

        if ($config->allowedDsnHosts !== []) {
            $ok = false;

            foreach ($config->allowedDsnHosts as $allowed) {
                if (strtolower($allowed) === $host) {
                    $ok = true;

                    break;
                }
            }

            if (! $ok) {
                return $this->response->setStatusCode(403)->setBody('DSN host not allowed');
            }
        }

        $target = $config->internalIngestBase . '/api/' . rawurlencode($projectId) . '/envelope/';
        $contentType = $this->request->getHeaderLine('Content-Type');

        if ($contentType === '') {
            $contentType = 'application/x-sentry-envelope';
        }

        // Connect via Docker hostname (internalIngestBase) but send the public Host GlitchTip expects
        // (ALLOWED_HOSTS / SSL settings); otherwise Django often responds 403 for Host mismatch.
        $forwardHost = $host;

        if (isset($parts['port'])) {
            $forwardHost .= ':' . $parts['port'];
        }

        try {
            $client = service('curlrequest', ['timeout' => 15]);
            $upstream = $client->request('POST', $target, [
                'body'    => $body,
                'headers' => [
                    'Content-Type' => $contentType,
                    'Host'         => $forwardHost,
                ],
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'GlitchTip tunnel upstream error: ' . $e->getMessage());

            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'Upstream unavailable',
            ]);
        }

        $status       = $upstream->getStatusCode();
        $upstreamBody = (string) $upstream->getBody();

        if ($status >= 400) {
            log_message(
                'warning',
                'GlitchTip tunnel upstream HTTP ' . $status . ' → ' . $target . ' body=' . substr($upstreamBody, 0, 500),
            );
        }

        $response = $this->response->setStatusCode($status);

        $upstreamType = $upstream->getHeaderLine('Content-Type');

        if ($upstreamType !== '') {
            $response->setContentType($upstreamType);
        }

        return $response->setBody($upstreamBody);
    }
}
