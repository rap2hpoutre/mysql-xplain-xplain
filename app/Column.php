<?php

namespace Rap2hpoutre\MySQLExplainExplain;

class Column {

	public $field, $type, $null, $key, $default, $extra;

	public function __construct($sql_col) {
		$this->field = $sql_col['Field'];
		$this->type = $sql_col['Type'];
		$this->null = $sql_col['Null'];
		$this->key = $sql_col['Key'];
		$this->default = $sql_col['Default'];
		$this->extra = $sql_col['Extra'];
	}

	public function containsId() {
		return preg_match('/id/', $this->field);
	}

	public function isNull() {
		return trim($this->null) == 'YES';
	}
}
