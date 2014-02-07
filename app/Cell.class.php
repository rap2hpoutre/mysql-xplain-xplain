<?php
namespace Rap2hpoutre\MySQLExplainExplain;

class Cell {
	public $v;
	public $score = null;
	public $info;
	public $id;

	public function __construct($v) {
		$this->v = $v;
		$this->id = uniqid('cell');
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
