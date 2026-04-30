<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

defined('ROOTPATH') || define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('FCPATH') || define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
defined('SYSTEMPATH') || define('SYSTEMPATH', ROOTPATH . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
defined('WRITEPATH') || define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);
defined('TESTPATH') || define('TESTPATH', ROOTPATH . 'tests' . DIRECTORY_SEPARATOR);
defined('CI_DEBUG') || define('CI_DEBUG', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

require APPPATH . 'Config/Constants.php';
require SYSTEMPATH . 'Common.php';
