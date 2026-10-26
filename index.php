<?php

/*
 * Front controller: every page goes through here
 *   index.php?url=items/mine
 */

session_start();

define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('UPLOAD_PATH', __DIR__ . '/uploads');

require ROOT_PATH . '/config/config.php';
require APP_PATH . '/core/helpers.php';
require APP_PATH . '/core/Database.php';
require APP_PATH . '/core/Model.php';
require APP_PATH . '/core/Controller.php';
require APP_PATH . '/core/Router.php';

// Relative paths like "uploads/..." resolve from the project root
chdir(ROOT_PATH);

$router = new Router(require ROOT_PATH . '/config/routes.php');
$router->dispatch($_GET['url'] ?? '');
