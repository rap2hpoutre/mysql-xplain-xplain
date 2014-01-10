<?php
namespace Rap2hpoutre\MySQLExplainExplain;

class Row {
	public $cells = array();

	public function __construct($row) {
		foreach($row as $k => $v) {
			$this->cells[$k] = new Cell($v);
		}
		$this->performSelectType();
		$this->performExtraAnalysis();
		$this->performKeyAnalysis();
		$this->performTypeAnalysis();
	}

	public function performSelectType() {
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
