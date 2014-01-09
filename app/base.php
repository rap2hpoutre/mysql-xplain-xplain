<?php
require '../vendor/autoload.php';

require '../app/Explainer.class.php';

session_start();

header('Content-Type: text/html; charset=utf-8');

$engine = new \League\Plates\Engine('../app/templates');
$template = new \League\Plates\Template($engine);

$template->title = "MySQL Explain Explain";