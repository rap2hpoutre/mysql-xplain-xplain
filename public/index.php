<?php
// TODO: Nettoyer ce code infect
session_start();

if (isset($_SESSION['mysql'])) {
	$mysqli = new mysqli(
		$_SESSION['mysql']['host'], 
		$_SESSION['mysql']['user'], 
		$_SESSION['mysql']['password'], 
		$_SESSION['mysql']['base']
	);
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (strpos($_POST['query'], 'EXPLAIN') === false) {
			$_POST['query'] = 'EXPLAIN ' . $_POST['query'];
		}
		$explain_results = array();
		if ($result = $mysqli->query($_POST['query'])) {
			while ($row = $result->fetch_assoc()) {
				$explain_results[] = $row;
			}
			$result->free();
		}
		$mysqli->close();
	}
}


$current_page = 'index';

// Affichage
include '../app/templates/header.php';
include '../app/templates/default.php';
include '../app/templates/footer.php';