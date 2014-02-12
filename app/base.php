<?php
// Composer install verification : https://github.com/rap2hpoutre/mysql-xplain-xplain/issues/4
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) exit('Install dependencies : "composer install"');

/**
 * Autoloader PSR-4 (bit.ly/1fg1P08)
 */
spl_autoload_register(function ($class) {

	// project-specific namespace prefix
	$prefix = 'Rap2hpoutre\\MySQLExplainExplain\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . '/';

	// does the class use the namespace prefix?
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr($class, $len);

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

	// if the file exists, require it
	if (file_exists($file)) {
	    require $file;
	}
});

// Composer autoloader
require __DIR__ . '/../vendor/autoload.php';
// Some consts
require __DIR__ . '/constants.php';

// Session
session_start();

// Permanent login
if (file_exists(__DIR__ . '/../conf/db.php')) {
	require __DIR__ . '/../conf/db.php';
}

// UTF-8
header('Content-Type: text/html; charset=utf-8');

// Template engine
$engine = new \League\Plates\Engine(__DIR__ . '/templates');

// Template
$template = new \League\Plates\Template($engine);

// Template title
$template->title = "MySQL Explain Explain";

// Flash message
if (isset($_SESSION['flash_message'])) {
	$template->flash_message  = $_SESSION['flash_message'];
	unset($_SESSION['flash_message']);
}
