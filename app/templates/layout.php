<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="" />
	<meta name="author" content="" />

	<title><?=$this->title?> - <?=$this->page?></title>

	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" />
	<link rel="stylesheet" href="./css/default.css" />
	<link rel="icon" type="image/x-icon" href="./favicon.ico" />

	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->
</head>
<body>

	<!-- Barre de navigation -->
	<div class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="index.php">Mysql Explain Explain</a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li <?php if ($this->page == 'Home') echo 'class="active"'; ?>>
						<a href="./">Home</a>
					</li>
					<li <?php if ($this->page == 'Config') echo 'class="active"'; ?>>
						<a href="config.php">Configuration</a>
					</li>
					<li <?php if ($this->page == 'About') echo 'class="active"'; ?>>
						<a href="about.php">About</a>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="container">
		<?php if (isset($this->error)): ?>
			<div class="alert alert-danger"><?=$this->error?></div>
		<?php endif; ?>
		<?php if (isset($this->flash_message)): ?>
			<div class="alert alert-info"><?=$this->flash_message?></div>
		<?php endif; ?>
		<?=$this->child()?>
	</div>

	<script src="https://code.jquery.com/jquery.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
	<script src="./js/mysql-xplain-xplain.js"></script>
</body>
</html>
