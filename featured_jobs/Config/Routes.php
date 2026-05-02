<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$featuredJobsRoutes = static function (RouteCollection $routes): void {
    $routes->get('featured-jobs', 'FeaturedJobs::index');
};

$routes->group('learning/modules', ['namespace' => 'FeaturedJobs\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $featuredJobsRoutes);
$routes->group('en/learning/modules', ['namespace' => 'FeaturedJobs\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $featuredJobsRoutes);
$routes->group('fr/learning/modules', ['namespace' => 'FeaturedJobs\Controllers', 'filter' => \App\Filters\LocaleFilter::class], $featuredJobsRoutes);
