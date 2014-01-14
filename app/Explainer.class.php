<?php
namespace Rap2hpoutre\MySQLExplainExplain;

class Explainer {
	public $header_row;

	public $mysql_version;

	public $rows = array();

	public function __construct($mysql_version) {
		$this->mysql_version = $mysql_version;
		$this->initExplainCols();
	}

	public function initExplainCols() {
		$this->header_row = array(
			'id' => 'The SELECT identifier. This is the sequential number of the SELECT within the query. The value can be NULL if the row refers to the union result of other rows. In this case, the table column shows a value like <unionM,N> to indicate that the row refers to the union of the rows with id values of M and N.',
			'select_type' => 'The type of SELECT',
			'table' => 'The name of the table to which the row of output refers.',
			'type' => 'The join type. For descriptions of the different types, see EXPLAIN Join Types.',
			'possible_keys' => 'The possible_keys column indicates which indexes MySQL can choose from use to find the rows in this table. Note that this column is totally independent of the order of the tables as displayed in the output from EXPLAIN. That means that some of the keys in possible_keys might not be usable in practice with the generated table order.',
			'key' => 'The key column indicates the key (index) that MySQL actually decided to use. If MySQL decides to use one of the possible_keys indexes to look up rows, that index is listed as the key value.',
			'key_len' => 'The key_len column indicates the length of the key that MySQL decided to use. The length is NULL if the key column says NULL. Note that the value of key_len enables you to determine how many parts of a multiple-part key MySQL actually uses.',
			'ref' => 'The ref column shows which columns or constants are compared to the index named in the key column to select rows from the table.',
			'rows' => 'The rows column indicates the number of rows MySQL believes it must examine to execute the query. For InnoDB tables, this number is an estimate, and may not always be exact.',
		);
		if((float)$this->mysql_version >= 5.7) {
			$this->header_row['filtered'] = 'The filtered column indicates an estimated percentage of table rows that will be filtered by the table condition. That is, rows shows the estimated number of rows examined and rows × filtered / 100 shows the number of rows that will be joined with previous tables.';
		}

		$this->header_row['Extra'] = 'This column contains additional information about how MySQL resolves the query. For descriptions of the different values, see EXPLAIN Extra Information.';
	}

	public function setResults($results) {
		foreach($results as $result) {
			$this->rows[] = new Row($result);
		}
	}
}