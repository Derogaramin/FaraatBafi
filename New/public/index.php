<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/jdf.php';

use Core\Application;

function url($path = '') { return \Core\Helper::url($path); }
function asset($path = '') { return \Core\Helper::asset($path); }
function redirect($path = '') { \Core\Helper::redirect($path); }

$app = new Core\Application();
$app->run();
$app = new Application();
$app->run();