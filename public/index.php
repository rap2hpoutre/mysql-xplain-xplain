<?php
require '../app/base.php';
use \Jasny\MySQL\DB as DB;
use \Rap2hpoutre\MySQLExplainExplain\Explainer as Explainer;

$query = '';
$explainer = null;

if (isset($_SESSION['mysql'])) {
	new DB(
		$_SESSION['mysql']['host'], 
		$_SESSION['mysql']['user'], 
		$_SESSION['mysql']['password'], 
		$_SESSION['mysql']['base']
	);
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$query = $_POST['query'];
		$explain_results = DB::conn()->fetchAll((strpos($query, 'EXPLAIN') === false ? 'EXPLAIN ' : '') .$_POST['query']);
		$explainer = new Explainer($explain_results);
	}
}



// Affichage
$template->page = 'Home';
$template->explainer = $explainer;
$template->query = $query;
echo $template->render('home');