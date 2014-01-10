<?php
namespace Rap2hpoutre\MySQLExplainExplain;

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
