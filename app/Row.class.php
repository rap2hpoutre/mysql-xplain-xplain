<?php
namespace Rap2hpoutre\MySQLExplainExplain;

use \Jasny\MySQL\DB as DB;
use \Jasny\MySQL\DB_Exception as DB_Exception;

class Row {
	public $cells = array();

	public function __construct($row) {
		foreach($row as $k => $v) {
			$this->cells[$k] = new Cell($v);
		}
		$this->performSelectTypeAnalysis();
		$this->performExtraAnalysis();
		$this->performKeyAnalysis();
		$this->performTypeAnalysis();

		$this->buildTableSchema();
		$this->cells['id']->info = 'SELECT identifier #' . $this->cells['id']->v;
		$this->cells['rows']->info = "MySQL believes it must examine {$this->cells['rows']->v} rows to execute the query";
	}

	public function performSelectTypeAnalysis() {
		$infos = array(
			'SIMPLE' => 'Simple SELECT (not using UNION or subqueries)',
			'PRIMARY' => 'Outermost SELECT',
			'UNION' => 'Second or later SELECT statement in a UNION',
			'DEPENDENT' => 'UNION	Second or later SELECT statement in a UNION, dependent on outer query',
			'UNION RESULT' => 'Result of a UNION.',
			'SUBQUERY' => 'First SELECT in subquery',
			'DEPENDENT SUBQUERY' => 'First SELECT in subquery, dependent on outer query',
			'DERIVED' => 'Derived table SELECT (subquery in FROM clause)',
			'MATERIALIZED' => 'Materialized subquery',
			'UNCACHEABLE SUBQUERY' => 'A subquery for which the result cannot be cached and must be re-evaluated for each row of the outer query',
			'UNCACHEABLE UNION' => 'The second or later select in a UNION that belongs to an uncacheable subquery (see UNCACHEABLE SUBQUERY)'
		);
		$this->cells['select_type']->info = $infos[$this->cells['select_type']->v];
	}

	public function performExtraAnalysis() {
		if (preg_match('/Using temporary;\\s*Using filesort/', $this->cells['Extra']->v)) {
			$this->cells['Extra']->setDanger();
		}
	}

	public function performKeyAnalysis() {
		// Si la clé utilisée est la même qu'une des clés possibles c'est bien
		if ($this->cells['possible_keys']->v && strpos($this->cells['possible_keys']->v,  $this->cells['key']->v) !== false) {
			$this->cells['key']->setSuccess();
		}

		$this->cells['key']->v = str_replace(',', ', ', $this->cells['key']->v);
		$this->cells['possible_keys']->v = str_replace(',', ', ', $this->cells['possible_keys']->v);

		// DANGER: Pas de possible_keys alors qu'il y a un WHERE
		if (!$this->cells['possible_keys']->v && preg_match('/Using where/', $this->cells['Extra']->v)) {
			$this->cells['possible_keys']->v = 'NULL';
			$this->cells['possible_keys']->setDanger();
			try {
				$indexes = DB::conn()->fetchAll("SHOW INDEX FROM {$this->cells['table']->v}");
			} catch (DB_Exception $e) {
				$indexes = null;
			}
			// S'il y avait des index dans la table, on propose d'utiliser ceux-là
			if (count($indexes)) {
				$this->cells['possible_keys']->info = "You have the following indexes in table <b>{$this->cells['table']->v}</b> : ";
				$indexes_text = array();
				foreach($indexes as $index) {
					$indexes_text[] = $index['Key_name'];
				}
				$this->cells['possible_keys']->info .= '<i>' . implode(', ', $indexes_text) . '</i><br />';
				$this->cells['possible_keys']->info .= 'You should use one of them or add new ones !';
			// Sinon on conseille d'en ajouter au moins un
			} else {
				$this->cells['possible_keys']->info = "You have no indexes in table <b>{$this->cells['table']->v}</b> ! You should add some !";
			}
		}

		// La longeur de la clé
		if ($this->cells['key']->v && $this->cells['key_len']->v) {
			$this->cells['key_len']->info = "The length of the key that MySQL decided to use is {$this->cells['key_len']->v}";
		}
	}

	public function performTypeAnalysis() {
		if ($this->cells['type']->v == 'ALL') {
			$this->cells['type']->setWarning();
		}

		$infos = array(
			'system' =>         'The table has only one row (= system table). This is a special case of the const join type.',
			'const' =>          'The table has at most one matching row, which is read at the start of the query.
								In the following queries, tbl_name can be used as a const table:' .
								\SqlFormatter::highlight("SELECT * FROM tbl_name WHERE primary_key=1;"),
			'eq_ref' =>         'One row is read from this table for each combination of rows from the previous tables. Example:' .
								\SqlFormatter::highlight("SELECT * FROM ref_table,other_table WHERE ref_table.key_column=other_table.column;"),
			'ref' =>            'All rows with matching index values are read from this table for each combination of rows from the previous tables. Example:' .
								\SqlFormatter::highlight("SELECT * FROM ref_table WHERE key_column=expr;"),
			'fulltext' =>       'The join is performed using a FULLTEXT index',
			'ref_or_null' =>    'This join type is like ref, but with the addition that MySQL does an extra search for rows that contain NULL values',
			'index_merge' =>    'This join type indicates that the Index Merge optimization is used.
								In this case, the key column in the output row contains a list of indexes used, and key_len contains a list of the
								longest key parts for the indexes used. For more information, see Section 8.2.1.4, “Index Merge Optimization”',
			'unique_subquery'=> 'This type replaces ref for some IN subqueries of the following form:' .
								\SqlFormatter::highlight("value IN (SELECT primary_key FROM single_table WHERE some_expr)"),
			'index_subquery' => 'This join type is similar to unique_subquery. It replaces IN subqueries, but it works for nonunique indexes.',
			'range' =>          'Only rows that are in a given range are retrieved, using an index to select the rows.
								The key column in the output row indicates which index is used. The key_len contains the longest key part that was used',
			'index' =>          'The index join type is the same as ALL, except that the index tree is scanned',
			'ALL' =>            'A full table scan is done for each combination of rows from the previous tables.
								This is normally <b>not good</b> if the table is the first table not marked const, and usually <b>very bad</b> in all other cases.
								Normally, you can avoid ALL by adding indexes that enable row retrieval from the table based on constant
								values or column values from earlier tables.'
		);
		$this->cells['type']->info = $infos[$this->cells['type']->v];
	}

	public function buildTableSchema() {
		$this->cells['table']->info = 'No table schema informations';
		try {
			$table_schema = DB::conn()->fetchPairs("SHOW CREATE TABLE {$this->cells['table']->v}");
			$this->cells['table']->info = \SqlFormatter::format($table_schema[$this->cells['table']->v]);
		} catch (DB_Exception $e) { }
	}

}
