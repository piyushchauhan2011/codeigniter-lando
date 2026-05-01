<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('/hello', 'Tutorial::hello');

$routes->get('/posts', 'Tutorial::posts');
$routes->get('/posts/new', 'Tutorial::newPost');
$routes->post('/posts', 'Tutorial::createPost');

// Job portal — public
$routes->get('jobs', 'Jobs::index');
$routes->get('jobs/(:num)', 'Jobs::show/$1');

$routes->get('contact', 'Contact::index');
$routes->post('contact', 'Contact::submit');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->resource('jobs', ['controller' => 'JobsApi', 'only' => ['index', 'show']]);
});

$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attemptLogin');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::attemptRegister');
});

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->post('logout', 'Auth::logout');
    $routes->get('dashboard', 'Dashboard::index');
});

$routes->group('employer', ['filter' => ['auth', 'employer']], static function ($routes) {
    $routes->get('/', 'Employer::dashboard');
    $routes->get('profile', 'Employer::profile');
    $routes->post('profile', 'Employer::updateProfile');
    $routes->get('jobs/new', 'Employer::newJob');
    $routes->post('jobs', 'Employer::createJob');
    $routes->get('jobs/(:num)/edit', 'Employer::editJob/$1');
    $routes->post('jobs/(:num)', 'Employer::updateJob/$1');
    $routes->post('jobs/(:num)/delete', 'Employer::deleteJob/$1');
    $routes->get('jobs/(:num)/applications', 'Employer::applications/$1');
    $routes->post('applications/(:num)/status', 'Employer::updateApplicationStatus/$1');
    $routes->get('applications/(:num)/resume', 'Employer::downloadResume/$1');
});

$routes->group('seeker', ['filter' => ['auth', 'seeker']], static function ($routes) {
    $routes->get('/', 'Seeker::dashboard');
    $routes->get('profile', 'Seeker::profile');
    $routes->post('profile', 'Seeker::updateProfile');
    $routes->get('applications', 'Seeker::applications');
    $routes->post('jobs/(:num)/apply', 'Seeker::apply/$1');
    $routes->post('jobs/(:num)/save', 'Seeker::toggleSave/$1');
});
