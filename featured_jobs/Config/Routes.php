<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('learning/modules', ['namespace' => 'FeaturedJobs\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('featured-jobs', 'FeaturedJobs::index');
});
