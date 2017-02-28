<?php

namespace KVSun\KVSAPI\Traits;

use \shgysk8zer0\Core\{PDO};
use \stdClass;

trait Head
{
	/**
	 * [$_head description]
	 * @var \stdClass
	 */
	private $_head;

	/**
	 * Retrieve data from `head` table
	 * @param  String $prop Optional property from head to retrieve instead
	 * @return Mixed        Data from `head` table
	 */
	final public function getHead(): stdClass
	{
		if (is_null($this->_head)) {
			$this->_getHead();
		}
		return clone($this->_head);
	}

	final private function _getHead()
	{
		$query = $this->_pdo->query('SELECT `name`, `value` FROM `head`;');
		$query->execute();
		$this->_head = array_reduce(
			$query->fetchAll(PDO::FETCH_CLASS),
			[$this, '_reduceHead'],
			new stdClass()
		);
	}

	/**
	 * Converts {name, value} into {name: value}
	 * @param  stdClass  $head Stores data retrieved from `head` table
	 * @param  stdClass  $item The current item
	 * @return stdClass        Updated $head
	 */
	final private function _reduceHead(stdClass $head, stdClass $item): stdClass
	{
		$head->{$item->name} = $item->value;
		return $head;
	}
}
