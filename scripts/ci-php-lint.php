<?php

declare(strict_types=1);

$dir = $argv[1] ?? 'app';

if (!is_dir($dir)) {
    fwrite(STDERR, "Directory not found: {$dir}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    passthru('php -l ' . escapeshellarg($path), $code);

    if ($code !== 0) {
        exit($code);
    }
}
