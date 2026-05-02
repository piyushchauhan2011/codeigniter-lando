<?php

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Shield\Config\Services as ShieldServices;

/** @var RouteCollection $routes */
$routes->match(['get', 'post', 'options', 'head'], '__apm-proxy/intake/(:segment)/rum/events', 'ApmProxy::forward/$1');

$routes->get('/', 'Home::index');
$routes->get('/hello', 'Tutorial::hello');

$routes->get('/posts', 'Tutorial::posts');
$routes->get('/posts/new', 'Tutorial::newPost');
$routes->post('/posts', 'Tutorial::createPost');

$routes->get('/learning/elk', 'ElkLab::index');
$routes->get('/learning/elk/log-demo', 'ElkLab::logDemo');
$routes->get('/learning/elk/handled-error', 'ElkLab::handledError');
$routes->get('/learning/elk/unhandled-error', 'ElkLab::unhandledError');
$routes->get('/learning/elk/slow-request', 'ElkLab::slowRequest');
$routes->get('/learning/elk/not-found', 'ElkLab::notFound');

$portalRoutes = static function (RouteCollection $routes): void {
    $routes->get('jobs', 'Jobs::index');
    $routes->get('jobs/(:num)', 'Jobs::show/$1');

    $routes->get('contact', 'Contact::index');
    $routes->post('contact', 'Contact::submit');

    $routes->group('', ['filter' => 'guest'], static function (RouteCollection $routes): void {
        $routes->get('login', 'Auth::login');
        $routes->post('login', 'Auth::attemptLogin');
        $routes->get('register', 'Auth::register');
        $routes->post('register', 'Auth::attemptRegister');
    });

    $routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
        $routes->post('logout', 'Auth::logout');
        $routes->get('dashboard', 'Dashboard::index');
    });

    $routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin::dashboard');
    });

    $routes->group('employer', ['filter' => ['auth', 'employer']], static function (RouteCollection $routes): void {
        $routes->get('/', 'Employer::dashboard');
        $routes->get('profile', 'Employer::profile');
        $routes->post('profile', 'Employer::updateProfile');
        $routes->get('jobs/new', 'Employer::newJob');
        $routes->post('jobs', 'Employer::createJob');
        $routes->get('jobs/(:num)/edit', 'Employer::editJob/$1');
        $routes->post('jobs/(:num)', 'Employer::updateJob/$1');
        $routes->post('jobs/(:num)/delete', 'Employer::deleteJob/$1');
        $routes->get('jobs/(:num)/assets', 'EmployerAssets::index/$1');
        $routes->post('jobs/(:num)/assets', 'EmployerAssets::create/$1');
        $routes->post('jobs/(:num)/feature-payment', 'EmployerPayments::create/$1');
        $routes->get('jobs/(:num)/applications', 'Employer::applications/$1');
        $routes->get('payments/(:num)', 'EmployerPayments::show/$1');
        $routes->post('applications/(:num)/status', 'Employer::updateApplicationStatus/$1');
        $routes->get('applications/(:num)/resume', 'Employer::downloadResume/$1');
        $routes->get('assets/(:num)/signed-url', 'EmployerAssets::signedUrl/$1');
        $routes->post('assets/(:num)/delete', 'EmployerAssets::delete/$1');
    });

    $routes->group('seeker', ['filter' => ['auth', 'seeker']], static function (RouteCollection $routes): void {
        $routes->get('/', 'Seeker::dashboard');
        $routes->get('profile', 'Seeker::profile');
        $routes->post('profile', 'Seeker::updateProfile');
        $routes->get('applications', 'Seeker::applications');
        $routes->post('jobs/(:num)/apply', 'Seeker::apply/$1');
        $routes->post('jobs/(:num)/save', 'Seeker::toggleSave/$1');
    });
};

$portalRoutes($routes);
$routes->group('fr', $portalRoutes);
$routes->group('en', $portalRoutes);

ShieldServices::auth()->routes($routes, ['except' => ['register', 'login', 'logout', 'magic-link']]);

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes): void {
    $routes->resource('jobs', ['controller' => 'JobsApi', 'only' => ['index', 'show']]);
});
