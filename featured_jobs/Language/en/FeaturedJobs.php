<?php

declare(strict_types=1);

return [
    'nav_learning'      => 'Modules demo',
    'page_title'        => 'Featured Jobs Module',
    'page_intro'        => 'This page is served by a module directory that lives beside app/. It demonstrates CodeIgniter 4 modules through PSR-4 autoloading, route discovery, namespaced MVC classes, module views, and a reusable view cell.',
    'cell_heading'      => 'Featured jobs',
    'cell_intro'        => 'Rendered by a module-owned view cell. The host page asks for this fragment, but the query and markup live in featured_jobs/.',
    'learn_link'        => 'Open the module learning page',
    'company_fallback'  => 'Company',
    'empty'             => 'No published jobs are available yet.',
    'map_heading'       => 'What this module owns',
    'map_routes'        => 'is auto-discovered and registers this learning route.',
    'map_controller'    => 'loads module data and returns a namespaced module view.',
    'map_model'         => 'queries the existing job portal tables without moving app models.',
    'map_cell'          => 'renders a reusable fragment that the main jobs page can include.',
    'map_view'          => 'is loaded with the module namespace.',
    'page_data_heading' => 'Same module data on a full page',
];
