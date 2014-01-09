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

class Row {
	public $cells = array();

	public function __construct($row) {
		foreach($row as $k => $v) {
			$this->cells[$k] = new Cell($v);
		}
		$this->performExtraAnalysis();
		$this->performKeyAnalysis();
		$this->performTypeAnalysis();
	}

	public function performExtraAnalysis() {
		if (preg_match('/Using temporary; Using filesort/', $this->cells['Extra']->v)) {
			$this->cells['Extra']->setDanger();
		}
	}

	public function performKeyAnalysis() {
		if ($this->cells['key']->v == 'PRIMARY' && $this->cells['possible_keys']->v == 'PRIMARY') {
			$this->cells['key']->setSuccess();
		}
	}

	public function performTypeAnalysis() {
		if ($this->cells['type']->v == 'ALL') {
			$this->cells['type']->setWarning();
		}
	}

}

class Cell {
	public $v;
	public $score = null;

	public function __construct($v) {
		$this->v = $v;
	}

	public function setSuccess() {
		$this->score = 2;
	}
	public function setWarning() {
		$this->score = 1;
	}
	public function setDanger() {
		$this->score = 0;
	}

	public function isSuccess() {
		return $this->score === 2;
	}
	public function isWarning() {
		return $this->score === 1;
	}
	public function isDanger() {
		return $this->score === 0;
	}


}