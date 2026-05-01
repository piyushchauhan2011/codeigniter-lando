#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Parse PHPUnit JUnit XML and print shell variable assignments for bash `eval`.
 * Classifies rows by test file path: tests/feature/* vs tests/unit/* (else unit).
 */

$path = $argv[1] ?? 'build/phpunit-junit.xml';
if (! is_readable($path)) {
    fwrite(STDERR, "ci-phpunit-junit-split: unreadable {$path}\n");
    exit(2);
}

$xml = new SimpleXMLElement((string) file_get_contents($path));

$unitTests = $unitAssertions = 0;
$featureTests = $featureAssertions = 0;

foreach ($xml->xpath('//testcase') as $tc) {
    $file = str_replace('\\', '/', (string) $tc['file']);
    $isFeature = str_contains($file, '/tests/feature/');
    $assertions = (int) ($tc['assertions'] ?? '0');
    if ($isFeature) {
        $featureTests++;
        $featureAssertions += $assertions;
    } else {
        $unitTests++;
        $unitAssertions += $assertions;
    }
}

echo 'PHPUNIT_UNIT_TESTS=' . $unitTests . PHP_EOL;
echo 'PHPUNIT_UNIT_ASSERTIONS=' . $unitAssertions . PHP_EOL;
echo 'PHPUNIT_FEATURE_TESTS=' . $featureTests . PHP_EOL;
echo 'PHPUNIT_FEATURE_ASSERTIONS=' . $featureAssertions . PHP_EOL;
