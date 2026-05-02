<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$performanceLabRoutes = static function (RouteCollection $routes): void {
    $routes->get('performance-lab', 'PerformanceLab::index');
    $routes->post('performance-lab/cache/clear', 'PerformanceLab::clearCache');
    $routes->get('performance-lab/page-cache/cached', 'PerformanceLab::cachedPage', ['filter' => 'pagecache']);
    $routes->get('performance-lab/page-cache/uncached', 'PerformanceLab::uncachedPage');
};

$routes->group('learning/modules', ['namespace' => 'PerformanceLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $performanceLabRoutes);
$routes->group('en/learning/modules', ['namespace' => 'PerformanceLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $performanceLabRoutes);
$routes->group('fr/learning/modules', ['namespace' => 'PerformanceLab\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $performanceLabRoutes);
