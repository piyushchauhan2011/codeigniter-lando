<?php

declare(strict_types=1);

/**
 * Poll until the appserver can open a MySQL connection to the Lando `database` service.
 * Avoids a race on CI right after `lando start` (healthcheck can pass before mysqld
 * accepts TCP from other containers).
 *
 * Run via: lando php scripts/ci-wait-mysql.php
 */
$host = 'myfirstlampapp_database_1';
$user = 'lamp';
$pass = 'lamp';
$db = 'lamp';
$port = 3306;
$attempts = 45;
$sleepSeconds = 3;

for ($i = 1; $i <= $attempts; $i++) {
    $mysqli = @new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_errno === 0) {
        $mysqli->close();
        fwrite(STDERR, "MySQL reachable from appserver (attempt {$i}/{$attempts}).\n");
        exit(0);
    }

    fwrite(STDERR, "Waiting for MySQL from appserver ({$i}/{$attempts})...\n");
    sleep($sleepSeconds);
}

fwrite(STDERR, "MySQL not reachable from appserver after {$attempts} attempts.\n");
exit(1);
