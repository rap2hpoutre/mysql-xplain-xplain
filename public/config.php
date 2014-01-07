<?php
// TODO: Nettoyer ce code infect
session_start();

// Enregistrement de Configuration MySQl
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$mysqli = @new mysqli(
			$_POST['host'], 
			$_POST['user'], 
			$_POST['password'], 
			$_POST['base']
		);
		if ($mysqli->connect_errno) {
			throw new Exception ('Failed to connect to MySQL: ' . $mysqli->connect_error);
		}
		$_SESSION['mysql'] = array(
			'host' 		=> 	$_POST['host'],
			'user' 		=> 	$_POST['user'],
			'password' 	=> 	$_POST['password'],
			'base' 		=> 	$_POST['base'],
		);
	} catch (\Exception $e) {
		$error = utf8_encode($e->getMessage());
	}
	
}

$current_page = 'config';

// Affichage
include '../app/templates/header.php';
include '../app/templates/config.php';
include '../app/templates/footer.php';