<?php

declare(strict_types=1);

namespace App\Log\Handlers;

use CodeIgniter\Log\Handlers\BaseHandler;
use DateTimeImmutable;

class JsonFileHandler extends BaseHandler
{
    private string $path;

    private int $filePermissions;

    /**
     * @param array{handles?: list<string>, path?: string, filePermissions?: int} $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->path            = rtrim($config['path'] ?? WRITEPATH . 'logs/', '/') . '/';
        $this->filePermissions = $config['filePermissions'] ?? 0644;
    }

    public function handle($level, $message): bool
    {
        $filepath = $this->path . 'log-' . date('Y-m-d') . '.json';
        $newFile  = ! is_file($filepath);

        if (! is_dir($this->path)) {
            mkdir($this->path, 0775, true);
        }

        $payload = [
            '@timestamp' => (new DateTimeImmutable())->format(DATE_ATOM),
            'log.level'  => strtolower($level),
            'message'    => $message,
            'service'    => [
                'name'        => config(\Config\Elastic::class)->serviceName,
                'environment' => ENVIRONMENT,
            ],
        ];

        $decoded = json_decode($message, true);
        if (is_array($decoded)) {
            $payload = array_replace_recursive($payload, $decoded);
            if (isset($decoded['message']) && is_string($decoded['message'])) {
                $payload['message'] = $decoded['message'];
            }
        }

        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";

        if (! $fp = @fopen($filepath, 'ab')) {
            return false;
        }

        flock($fp, LOCK_EX);
        $result = fwrite($fp, $line);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($newFile) {
            chmod($filepath, $this->filePermissions);
        }

        return $result !== false;
    }
}
