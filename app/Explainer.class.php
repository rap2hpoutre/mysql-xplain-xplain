<?php
namespace Rap2hpoutre\MySQLExplainExplain;

class Explainer {

	public $rows = array();

	public function __construct($results) {
		foreach($results as $result) {
			$this->rows[] = new Row($result);
		}
	}

}