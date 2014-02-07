<?php
require '../app/base.php';

use \Jasny\MySQL\DB as DB;
use \Jasny\MySQL\DB_Exception as DB_Exception;

use \Rap2hpoutre\MySQLExplainExplain\Explainer as Explainer;
use \Rap2hpoutre\MySQLExplainExplain\Row as Row;

$query = '';
$explainer = null;

if (isset($_SESSION['mysql'])) {
	new DB(
		$_SESSION['mysql']['host'],
		$_SESSION['mysql']['user'],
		$_SESSION['mysql']['password'],
		$_SESSION['mysql']['base']
	);
	$mysql_version = mb_substr(DB::conn()->server_info,0,3);
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$query = $_POST['query'];
		try {
			// Contextual queries (on les exécute sans rien en faire)
			if (isset($_POST['context_queries'])) {
				DB::conn()->multi_query($_POST['context_queries']);
				// Code pour jeter les résultats à la poubelle
				do { if ($res = DB::conn()->store_result()) $res->free(); } while (DB::conn()->more_results() && DB::conn()->next_result());
			}
			// Recuperation des résultats de la requete
			$explain_results = @DB::conn()->fetchAll(
				(strpos(strtolower($query), 'explain') === false ? 'EXPLAIN ' : '') . $_POST['query']
			);
			// Création de l'Explainer
			$explainer = new Explainer($_POST['query'], $mysql_version);
			if (is_array($explain_results)) {
				foreach ($explain_results as $result) {
					// Création de la ligne et attachement à l'explainer
					$explainer->addRow(
						new Row(
							$result,
							$explainer->getRowsCount() > 0 ? $explainer->rows[$explainer->getRowsCount() - 1] : null ,
							$explainer
						)
					);
				}
			}
		} catch (DB_Exception $e) {
			$template->error = utf8_encode($e->getError());
		}
	}
} else {
	header('Location: config.php');
	exit;
}
// Affichage
$template->page = 'Home';
$template->explainer = $explainer;
$template->query = $query;
$template->mysql_base_doc_url = MYSQL_DOC_URL . $mysql_version . '/en/explain-output.html';
echo $template->render('home');
