<?php
// index.php — front controller

require_once __DIR__ . '/app/bootstrap.php';

$router = new Router();
require_once CONFIG_PATH . '/routes.php';
$router->dispatch();
