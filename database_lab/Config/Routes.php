<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$databaseLabRoutes = static function (RouteCollection $routes): void {
    $routes->get('database-lab', 'DatabaseLab::index');
};

$routes->group('learning/modules', ['namespace' => 'DatabaseLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $databaseLabRoutes);
$routes->group('en/learning/modules', ['namespace' => 'DatabaseLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $databaseLabRoutes);
$routes->group('fr/learning/modules', ['namespace' => 'DatabaseLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $databaseLabRoutes);
