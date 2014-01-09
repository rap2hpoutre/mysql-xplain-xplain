<?php
require '../app/base.php';
use \Jasny\MySQL\DB as DB;

$template->page = 'About';
echo $template->render('about');