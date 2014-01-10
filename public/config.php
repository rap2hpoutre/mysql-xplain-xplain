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
		// Login permanent : à faire plus propre et plus secure
		if (isset($_POST['permanent_login']) && $_POST['permanent_login'] == '1') {
			$conf_dir = '../conf';
			if (!file_exists($conf_dir)) mkdir ($conf_dir);
			file_put_contents(
				$conf_dir . '/db.php',
				'<?php $_SESSION[\'mysql\'] = array(
					\'host\' => \'' . $_POST['host'] . '\',
					\'user\' => \'' . $_POST['user'] . '\',
					\'password\' => \'' . $_POST['password'] . '\',
					\'base\' => \'' . $_POST['base'] . '\'
				);'
			);
		} else {
			if (file_exists($conf_dir)) unlink($conf_dir . '/db.php');
		}
		// Redirection
		$_SESSION['flash_message'] = 'MySQL connection successful :)';
		header('Location: index.php');
		exit;
	} catch (\Exception $e) {
		$template->error = utf8_encode($e->getMessage());
	}
	
}

// Affichage
$template->page = 'Config';
echo $template->render('config');