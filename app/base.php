<?php

require '../vendor/autoload.php';

require '../app/Explainer.class.php';
require '../app/Row.class.php';
require '../app/Cell.class.php';
require '../app/Key.class.php';
require '../app/constants.php';

session_start();

// Connexion permanente
if (file_exists('../conf/db.php')) {
	require '../conf/db.php';
}

header('Content-Type: text/html; charset=utf-8');

$engine = new \League\Plates\Engine('../app/templates');
$template = new \League\Plates\Template($engine);

$template->title = "MySQL Explain Explain";

if (isset($_SESSION['flash_message'])) {
	$template->flash_message  = $_SESSION['flash_message'];
	unset($_SESSION['flash_message']);
}