<?php

namespace Rap2hpoutre\MySQLExplainExplain;

class Key {
	public $key_name, $col_name;

	public function __construct($sql_key_row) {
		$this->key_name = $sql_key_row['Key_name'];
		$this->col_name = $sql_key_row['Column_name'];
	}

	public function isPrimary() {
		return $this->key_name == 'PRIMARY';
	}
}
