<?php

namespace KVSun\KVSAPI\Abstracts;

use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\Traits\{URL, Magic, Head};
use \PDOStatement;
use \stdClass;

abstract class Content implements \JsonSerializable
{
	use URL;
	use Magic;
	use Head;

	/**
	 * [$_pdo description]
	 * @var \PDO
	 */
	protected $_pdo;

	/**
	 * Create instance of content using database & URL
	 * @param PDO    $pdo Instance of database connection
	 * @param String $url The URL to get content for
	 */
	protected function _init(PDO $pdo, String $url = null)
	{
		$this->_pdo = $pdo;
		$this->_setDefaults($this::DEFAULTS);
		$this->_parseURL($url);
		$this->_setData($this->_pdo->prepare($this->_getSQL()));
	}

	/**
	 * Required method to get query to execute
	 * @return String SQL query
	 */
	abstract protected function _getSQL(): String;

	/**
	 * Required method for setting data
	 * @param PDOStatement $stm A prepared statment using `\PDO::prepare`
	 */
	abstract protected function _setData(PDOStatement $stm);
}
