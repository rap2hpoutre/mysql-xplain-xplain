<?php
require '../app/base.php';
use \Jasny\MySQL\DB as DB;

// Enregistrement de Configuration MySQl
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$c = @new DB(
			$_POST['host'], 
			$_POST['user'], 
			$_POST['password'], 
			$_POST['base']
		);
		if ($c->connect_errno) {
			throw new Exception ('Failed to connect to MySQL: ' . $c->connect_error);
		}
		$_SESSION['mysql'] = array(
			'host' 		=> 	$_POST['host'],
			'user' 		=> 	$_POST['user'],
			'password' 	=> 	$_POST['password'],
			'base' 		=> 	$_POST['base'],
		);
	} catch (\Exception $e) {
		$template->error = utf8_encode($e->getMessage());
	}
	
}

// Affichage
$template->page = 'Config';
echo $template->render('config');