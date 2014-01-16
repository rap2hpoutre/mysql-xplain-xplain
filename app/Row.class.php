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
	 * @param $row
	 */
	public function __construct($row) {
		foreach($row as $k => $v) {
			$this->cells[$k] = new Cell($v);
		}
		$this->performSelectTypeAnalysis();
		$this->performExtraAnalysis();
		$this->performKeyAnalysis();
		$this->performTypeAnalysis();
		$this->performRefAnalysis();

		$this->buildTableSchema();
		$this->cells['id']->info = 'SELECT identifier #' . $this->cells['id']->v;
		$this->cells['rows']->info = "MySQL believes it must examine {$this->cells['rows']->v} rows to execute the query";
	}

	/**
	 * Analyse de la colonne type
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
	 */
	public function performExtraAnalysis() {
		// La colonne extra contient des infos multiples alors on utilise un tableau d'information
		// Par contre l'état (danger ou success) reste global à la cellule et est à gérer au cas par cas
		$infos = array();
		// Contient Using temporary; Using filesort
		if (preg_match('/Using temporary;\\s*Using filesort/', $this->cells['Extra']->v)) {
			$this->cells['Extra']->setDanger();
			$infos[] = 	'<p>You should avoid <code>Using temporary</code> and <code>Using filesort</code> on big queries.
						It means a temporary table is created, and a sort is performed on that temporary table</p>
						<ul>
							<li>Using temporary: To resolve the query, MySQL needs to create a temporary table to hold the result</li>
							<li>Using filesort: MySQL must do an extra pass to find out how to retrieve the rows in sorted order.
							The sort is done by going through all rows according to the join type and storing the sort key and 
							pointer to the row for all rows that match the WHERE clause.</li>
						</ul>';
		}
		// Contient Impossible WHERE noticed after reading const tables
		if (preg_match('/Impossible WHERE noticed after reading const tables/', $this->cells['Extra']->v)) {
			$infos[] = 	'MySQL has read all <code>const</code> (and <code>system</code>) tables and 
						notice that the WHERE clause is always false';
		}
		// Contient Using where
		if(preg_match('/Using where/', $this->cells['Extra']->v)) {
			$infos[] = "A WHERE clause is used to restrict which rows to match against the next table or send to the client.
						Unless you specifically intend to fetch or examine all rows from the table, you may have something
						wrong in your query if the <code>Extra</code> value is not <code>Using where</code> and the table join
						type is <code>ALL</code> or <code>index</code>.";
		}
		// Contient Using join buffer
		if(preg_match('/Using join buffer \\((.*?)\\)/', $this->cells['Extra']->v, $matches)) {
			$infos[] = "Tables from earlier joins are read in portions into the join buffer, and then their rows
						are used from the buffer to perform the join with the current table
						<code>{$this->cells['table']->v}</code> using <code>{$matches[1]}</code> algorithm";
		}
		// Contient Using index
		if(preg_match('/Using index/', $this->cells['Extra']->v)) {
			$tmp = "The column information is retrieved from the table using only information in the index tree
					without having to do an additional seek to read the actual row.
					This strategy can be used when the query uses only columns that are part of a single index.";
			if(preg_match('/Using where/', $this->cells['Extra']->v)) {
				$tmp .= "The index is being used to perform lookups of key values";
			} else {
				$tmp .= "The optimizer may be reading the index to avoid reading data rows but not using it for lookups.
						For example, if the index is a covering index for the query, the optimizer may scan it without using it for lookups.";
			}
			$infos[] = $tmp;
		}
		// Contient const row not found
		if(preg_match('/const row not found/', $this->cells['Extra']->v)) {
			$infos[] = "The table was empty";
		}

		if (!count($infos)) {
			$infos[] = 'Not Implemented Now :(';
		}
		
		$this->cells['Extra']->info = implode('<br /><br />', $infos);
	}

	/**
	 *
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
			try {
				$indexes = DB::conn()->fetchAll("SHOW INDEX FROM {$this->cells['table']->v}");
			} catch (DB_Exception $e) {
				$indexes = null;
			}
			// S'il y avait des index dans la table, on propose d'utiliser ceux-là
			if (count($indexes)) {
				$this->cells['possible_keys']->info = "You have the following indexes in table <code>{$this->cells['table']->v}</code> : ";
				$indexes_text = array();
				foreach($indexes as $index) {
					$indexes_text[] = $index['Key_name'];
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
	 *
	 */
	public function performTypeAnalysis() {
		if (!$this->cells['type']->v) return;
		if ($this->cells['type']->v == 'ALL') {
			$this->cells['type']->setWarning();
		}

		$infos = array(
			'system' =>         'The table has only one row (= system table). This is a special case of the const join type.',
			'const' =>          "<p>The table has at most one matching row, which is read at the start of the query.
								In the following queries, {$this->cells['table']->v} can be used as a const table:</p>" .
								\SqlFormatter::highlight("SELECT * FROM {$this->cells['table']->v} WHERE primary_key=1;"),
			'eq_ref' =>         '<p>One row is read from this table for each combination of rows from the previous tables. Example:</p>' .
								\SqlFormatter::highlight("SELECT * FROM ref_table,{$this->cells['table']->v} WHERE ref_table.key_column={$this->cells['table']->v}.column;"),
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
	 *
	 */
	public function buildTableSchema() {
		$this->cells['table']->info = 'No table schema informations';
		try {
			$table_schema = DB::conn()->fetchPairs("SHOW CREATE TABLE {$this->cells['table']->v}");
			$this->cells['table']->info = '<p>Table Schema</p>';
			$this->cells['table']->info .= \SqlFormatter::format($table_schema[$this->cells['table']->v]);
		} catch (DB_Exception $e) { }
	}

	/**
	 *
	 */
	public function performRefAnalysis() {
		if (!$this->cells['ref']->v) return;
		// s'il s'agit d'une référence à une colonne d'une table
		if (preg_match('/^.+?\\..+?\\..+$/', $this->cells['ref']->v)) {
			$ref_infos = explode('.', $this->cells['ref']->v);
			$this->cells['ref']->info = "The <code>{$ref_infos[2]}</code> column of table <code>{$ref_infos[1]}</code> is compared to 
										<code>{$this->cells['key']->v}</code> key of table <code>{$this->cells['table']->v}</code>";
		}
		
	}

}
