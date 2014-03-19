<?php
namespace Rap2hpoutre\MySQLExplainExplain;

use \Jasny\MySQL\DB as DB;
use \Jasny\MySQL\DB_Exception as DB_Exception;

/**
 * Class Row
 * @package Rap2hpoutre\MySQLExplainExplain
 */
class Row {
	/**
	 * @var array
	 */
	public $cells = array();

	/**
	 * @var array
	 */
	private $_keys = array();

	/**
	 * On peut accéder à la ligne précédente (utile pour analyser les jointures)
	 * @var Row
	 */
	private $_previous_row = null;

	private $_explainer = null;

	public $uses_table = false;

	/**
	 * Row::__construct()
	 *
	 * @param mixed $row
	 * @param Row $prev
	 * @param Explainer $explainer
	 * @return
	 */
	public function __construct($row, Row $prev = null, Explainer $explainer = null) {
		foreach ($row as $k => $v) {
			$this->cells[$k] = new Cell($v);
		}
		if ($prev !== null) {
			$this->_previous_row = $prev;
		}
		if ($explainer !== null) {
			$this->_explainer = $explainer;
		}

		$this->buildTableSchema();
		$this->initKeys($this->cells['table']->v);
		$this->initColumns($this->cells['table']->v);

		$this->performSelectTypeAnalysis();
		$this->performExtraAnalysis();
		$this->performKeyAnalysis();
		$this->performTypeAnalysis();
		$this->performRefAnalysis();

		$this->cells['id']->info = 'SELECT identifier #' . $this->cells['id']->v;
		$this->cells['rows']->info = "MySQL believes it must examine {$this->cells['rows']->v} rows to execute the query";
	}

	/**
	 * Analyse de la colonne type
	 *
	 * @return
	 */
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

	/**
	 * Analyse de la colonne extra
	 *
	 * @return
	 */
	public function performExtraAnalysis() {
		// La colonne extra contient des infos multiples alors on utilise un tableau d'information
		// Par contre l'état (danger ou success) reste global à la cellule et est à gérer au cas par cas
		$infos = array();
		// Contient Using temporary; Using filesort
		if (preg_match('/Using temporary;\\s*Using filesort/', $this->cells['Extra']->v)) {
			$this->cells['Extra']->setDanger();
			$this->_explainer->hints[] = 'You should avoid <code>Using temporary</code> and <code>Using filesort</code> on big queries';
			$infos[] = 	'<p>You should avoid <code>Using temporary</code> and <code>Using filesort</code> on big queries.
						It means a temporary table is created, and a sort is performed on that temporary table</p>
						<ul>
							<li>Using temporary: To resolve the query, MySQL needs to create a temporary table to hold the result</li>
							<li>Using filesort: MySQL must do an extra pass to find out how to retrieve the rows in sorted order.
							The sort is done by going through all rows according to the join type and storing the sort key and
							pointer to the row for all rows that match the WHERE clause.</li>
						</ul>';
		} elseif (preg_match('/Using temporary(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = 'To resolve the query, MySQL needs to create a temporary table to hold the result';
		} elseif (preg_match('/Using filesort(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = 'MySQL must do an extra pass to find out how to retrieve the rows in sorted order.
						The sort is done by going through all rows according to the join type and storing the sort key and
						pointer to the row for all rows that match the WHERE clause';
		}
		// Contient Impossible WHERE noticed after reading const tables
		if (preg_match('/Impossible WHERE noticed after reading const tables/', $this->cells['Extra']->v)) {
			$infos[] = 	'MySQL has read all <code>const</code> (and <code>system</code>) tables and
						notice that the WHERE clause is always false';
		}
		// Contient Using where
		if (preg_match('/Using where(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "A WHERE clause is used to restrict which rows to match against the next table or send to the client.
						Unless you specifically intend to fetch or examine all rows from the table, you may have something
						wrong in your query if the <code>Extra</code> value is not <code>Using where</code> and the table join
						type is <code>ALL</code> or <code>index</code>.";
		}
		// Contient Using join buffer
		if (preg_match('/Using join buffer \\((.*?)\\)/', $this->cells['Extra']->v, $matches)) {
			$infos[] = "Tables from earlier joins are read in portions into the join buffer, and then their rows
						are used from the buffer to perform the join with the current table
						<code>{$this->cells['table']->v}</code> using <code>{$matches[1]}</code> algorithm";
		} elseif (preg_match('/Using join buffer(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "Tables from earlier joins are read in portions into the join buffer, and then their rows
						are used from the buffer to perform the join with the current table";
		}
		// Contient Using index
		if (preg_match('/Using index(;|$)/', $this->cells['Extra']->v)) {
			$tmp = "The column information is retrieved from the table using only information in the index tree
					without having to do an additional seek to read the actual row.
					This strategy can be used when the query uses only columns that are part of a single index.";
			if (preg_match('/Using where/', $this->cells['Extra']->v)) {
				$tmp .= "The index is being used to perform lookups of key values";
			} else {
				$tmp .= "The optimizer may be reading the index to avoid reading data rows but not using it for lookups.
						For example, if the index is a covering index for the query, the optimizer may scan it without using it for lookups.";
			}
			$infos[] = $tmp;
		}
		// Contient const row not found
		if (preg_match('/const row not found/', $this->cells['Extra']->v)) {
			$infos[] = "The table was empty";
		}

		// Traités dans l'ordre de l'apparition dans la doc

		// Distinct
		if (preg_match('/Distinct(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "MySQL is looking for distinct values, so it stops searching for more rows
						for the current row combination after it has found the first matching row";
		}
		// Full scan on NULL key
		if (preg_match('/Full scan on NULL key(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "This occurs for subquery optimization as a fallback strategy
						when the optimizer cannot use an index-lookup access method.";
		}
		// Impossible HAVING
		if (preg_match('/Impossible HAVING(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "The HAVING clause is always false and cannot select any rows.";
		}
		// Impossible WHERE
		if (preg_match('/Impossible WHERE(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "The WHERE clause is always false and cannot select any rows.";
		}
		// LooseScan
		if (preg_match('/LooseScan(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "The semi-join LooseScan strategy is used.";
		}
		// No matching min/max row
		if (preg_match('/No matching min\\/max row(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "No row satisfies the condition for a query such as <code>SELECT MIN(col) FROM table WHERE condition</code>";
		}
		// no matching row in const table
		if (preg_match('/no matching row in const table(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "For a query with a join, there was an empty table or a table with no rows satisfying a unique index condition";
		}
		// no matching row in const table
		if (preg_match('/No matching rows after partition pruning(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "For <code>DELETE</code> or <code>UPDATE</code>, the optimizer found nothing to delete or update after partition pruning. It is similar in meaning to <code>Impossible WHERE</code> for <code>SELECT</code> statements.";
		}
		// No tables used
		if (preg_match('/No tables used(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "The query has no <code>FROM</code> clause, or has a <code>FROM DUAL</code> clause.";
		}
		// Not exists
		if (preg_match('/Not exists(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "MySQL was able to do a <code>LEFT JOIN</code> optimization on the query and does not examine more rows in this table for the previous row combination after it finds one row that matches the <code>LEFT JOIN</code> criteria";
		}
		// Plan isn't ready yet
		if (preg_match('/Plan isn\'t ready yet(;|$)/', $this->cells['Extra']->v)) {
			$infos[] = "This value occurs with EXPLAIN FOR CONNECTION when the optimizer has not finished creating the execution plan for the statement executing in the named connection. If execution plan output comprises multiple lines, any or all of them could have this Extra value, depending on the progress of the optimizer in determining the full execution plan.";
		}

		if (!count($infos)) {
			$infos[] = 'Not Implemented Now :(';
		}

		$this->cells['Extra']->info = implode('<br /><br />', $infos);
	}

	/**
	 * Row::performKeyAnalysis()
	 *
	 * @return
	 */
	public function performKeyAnalysis() {
		$this->cells['key']->v = str_replace(',', ', ', $this->cells['key']->v);
		$this->cells['possible_keys']->v = str_replace(',', ', ', $this->cells['possible_keys']->v);

		if ($this->cells['key']->v) {
			$this->cells['key']->info = "MySQL decided to use <code>{$this->cells['key']->v}</code> key. Using key is faster.";
		}

		// Si la clé utilisée est la même qu'une des clés possibles c'est bien
		if ($this->cells['key']->v && $this->cells['possible_keys']->v && strpos($this->cells['possible_keys']->v,  $this->cells['key']->v) !== false) {
			$this->cells['key']->setSuccess();
			$this->cells['possible_keys']->info = "MySQL actually decided to use <code>{$this->cells['key']->v}</code> key";
		// S'il y a des clés possible
		} elseif ($this->cells['possible_keys']->v) {
			$this->cells['possible_keys']->info = "MySQL can choose one of the following key : <code>{$this->cells['possible_keys']->v}</code>";
			if (!$this->cells['key']->v) {
				$this->cells['possible_keys']->info .= "... but did not choose any one :(";
				$this->cells['possible_keys']->setWarning();
			}
		}

		// DANGER: Pas de possible_keys alors qu'il y a un WHERE
		if (!$this->cells['possible_keys']->v && preg_match('/Using where/', $this->cells['Extra']->v)) {
			$this->cells['possible_keys']->v = 'NULL';
			$this->cells['possible_keys']->setDanger();
			$indexes = $this->_keys;
			// S'il y avait des index dans la table, on propose d'utiliser ceux-là
			if (count($indexes)) {
				$this->cells['possible_keys']->info = "You have the following indexes in table <code>{$this->cells['table']->v}</code> : ";
				$indexes_text = array();
				foreach ($indexes as $index) {
					$indexes_text[] = $index->key_name;
				}
				$this->cells['possible_keys']->info .= '<code>' . implode(', ', $indexes_text) . '</code><br />';
				$this->cells['possible_keys']->info .= 'You should use one of them or add new ones !';
			// Sinon on conseille d'en ajouter au moins un
			} else {
				$this->cells['possible_keys']->info = "You have no indexes in table <code>{$this->cells['table']->v}</code> ! You should add some !";
			}
		}

		// La longeur de la clé
		if ($this->cells['key']->v && $this->cells['key_len']->v) {
			$this->cells['key_len']->info = "The length of the key that MySQL decided to use (<code>{$this->cells['key']->v}</code>) is {$this->cells['key_len']->v}";
		}
	}

	/**
	 * Row::performTypeAnalysis()
	 *
	 * @return
	 */
	public function performTypeAnalysis() {
		if (!$this->cells['type']->v) return;
		if ($this->cells['type']->v == 'ALL') {
			$this->cells['type']->setWarning();
		}

		$infos = array(
			'system' =>         'The table has only one row (= system table). This is a special case of the const join type.',
			'const' =>          "<p>The table has at most one matching row, which is read at the start of the query.
								In the following queries, <code>{$this->cells['table']->v}</code> can be used as a const table:</p>" .
								\SqlFormatter::highlight("SELECT * FROM {$this->cells['table']->v} WHERE {$this->getPrimaryKey()->col_name}=1;"),
			'eq_ref' =>         '<p>One row is read from this table for each combination of rows from the previous tables. Example:</p>' .
								\SqlFormatter::highlight(
									"SELECT * FROM ref_table,{$this->cells['table']->v} WHERE ref_table.key_column={$this->cells['table']->v}.column;"
								),
			'ref' =>            '<p>All rows with matching index values are read from this table for each combination of rows from the previous tables. Example:</p>' .
								\SqlFormatter::highlight("SELECT * FROM {$this->cells['table']->v} WHERE {$this->cells['key']->v}=expr;"),
			'fulltext' =>       'The join is performed using a FULLTEXT index',
			'ref_or_null' =>    'This join type is like ref, but with the addition that MySQL does an extra search for rows that contain NULL values',
			'index_merge' =>    'This join type indicates that the Index Merge optimization is used.
								In this case, the key column in the output row contains a list of indexes used, and key_len contains a list of the
								longest key parts for the indexes used. For more information, see Section 8.2.1.4, “Index Merge Optimization”',
			'unique_subquery'=> 'This type replaces ref for some IN subqueries of the following form:' .
								\SqlFormatter::highlight("value IN (SELECT primary_key FROM single_table WHERE some_expr)"),
			'index_subquery' => 'This join type is similar to unique_subquery. It replaces IN subqueries, but it works for nonunique indexes.',
			'range' =>          "<p>Only rows that are in a given range are retrieved, using an index (in this query <code>{$this->cells['key']->v}</code>)
								to select the rows.</p>
								<ul><li>The <code>key</code> column in the output row indicates which index is used.</li>
								<li>The <code>key_len</code> contains the longest key part that was used</li></ul>",
			'index' =>          'The index join type is the same as ALL, except that the index tree is scanned',
			'ALL' =>            'A full table scan is done for each combination of rows from the previous tables.
								This is normally <b>not good</b> if the table is the first table not marked const, and usually <b>very bad</b> in all other cases.
								Normally, you can avoid ALL by adding indexes that enable row retrieval from the table based on constant
								values or column values from earlier tables.'
		);
		$this->cells['type']->info = $infos[$this->cells['type']->v];
	}

	/**
	 * Row::buildTableSchema()
	 *
	 * @return
	 */
	public function buildTableSchema() {
		$this->cells['table']->info = 'No table schema informations';
		try {
			$table_schema = DB::conn()->fetchPairs("SHOW CREATE TABLE `{$this->cells['table']->v}`");
			$this->cells['table']->info = '<p>Table Schema</p>';
			$this->cells['table']->info .= \SqlFormatter::format($table_schema[$this->cells['table']->v]);
			$this->uses_table = true;
		} catch (DB_Exception $e) { }
	}

	/**
	 * Row::performRefAnalysis()
	 *
	 * @return
	 */
	public function performRefAnalysis() {
		$this->cells['ref']->v = str_replace(',', ', ', $this->cells['ref']->v);
		
		if (!$this->cells['ref']->v) return;
		// s'il s'agit d'une référence à une colonne d'une table : base.table.column
		if (preg_match('/^.+?\\..+?\\..+$/', $this->cells['ref']->v)) {
			$ref_infos = explode('.', $this->cells['ref']->v);
			$this->cells['ref']->info = "The <code>{$ref_infos[2]}</code> column of table <code>{$ref_infos[1]}</code> is compared to
										<code>{$this->cells['key']->v}</code> key of table <code>{$this->cells['table']->v}</code>";
		}
		if (preg_match('/func/', $this->cells['ref']->v)) {
			$this->cells['ref']->info = "The value used as input to <code>{$this->cells['type']->v}</code> is the output of some function";
		}
		if (preg_match('/const/', $this->cells['ref']->v)) {
			$this->cells['ref']->info = "A constant value is compared to <code>{$this->cells['key']->v}</code>";
		}

	}

	/**
	 * Row::initKeys()
	 *
	 * @param mixed $table
	 * @return
	 */
	public function initKeys($table) {
		try {
			$sql_keys = DB::conn()->fetchAll("SHOW INDEX FROM `$table`");
			if (is_array($sql_keys) && count($sql_keys)) {
				foreach ($sql_keys as $sql_key) {
					$this->_keys[] = new Key($sql_key);
				}
			} elseif ($this->uses_table) {
				$this->_explainer->hints[] = "There are no keys on table <code>{$this->cells['table']->v}</code>, you should add some.";
			}
		} catch (DB_Exception $e) {	}
	}

	/**
	 * Row::initColumns()
	 *
	 * @param mixed $table
	 * @return void
	 */
	public function initColumns($table) {
		try {
			$sql_cols = DB::conn()->fetchAll("SHOW COLUMNS FROM `$table`");
			if (is_array($sql_cols) && count($sql_cols)) {
				$has_id_col = false;
				$has_null_col = false;
				foreach ($sql_cols as $sql_col) {
					$tmp_col = new Column($sql_col);
					if ($tmp_col->containsId()) $has_id_col = true;
					if ($tmp_col->isNull()) $has_null_col = true;
					$this->_cols[] = $tmp_col;
				}
				if (!$has_id_col) {
					$this->_explainer->hints[] = "It seems no column is named <code>id</code> in <code>{$this->cells['table']->v}</code>, it's ok but not usual.";
				}
				/* if ($has_null_col) {
					$this->_explainer->hints[] = "You have some nullable columns in <code>{$this->cells['table']->v}</code>, use <code>NOT NULL</code> if you can";
				} */
			}
		} catch (DB_Exception $e) { }
	}

	/**
	 * Row::getPrimaryKey()
	 *
	 * @return
	 */
	public function getPrimaryKey() {
		if (is_array($this->_keys)) {
			foreach ($this->_keys as $key) {
				if ($key->isPrimary()) return $key;
			}
		}
	}
}
