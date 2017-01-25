<?php

namespace KVSun\KVSAPI\Abstracts;

use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\Core as Core;

abstract class Content implements \JsonSerializable
{
	const MAGIC_PROPERTY = '_data';

	/**
	 * [$_data description]
	 * @var array
	 */
	private $_data = array();

	/**
	 * [$_url description]
	 * @var array
	 */
	protected $_url = array();

	/**
	 * [$_pdo description]
	 * @var \PDO
	 */
	protected $_pdo;

	/**
	 * [$_head description]
	 * @var \stdClass
	 */
	private $_head;

	public function __construct(\PDO $pdo, $url = null)
	{
		$this->{self::MAGIC_PROPERTY} = $this::DEFAULTS;
		$this->_pdo = $pdo;
		$query = $this->_pdo->query('SELECT `name`, `value` FROM `head`;');
		$query->execute();
		$this->_head = array_reduce(
			$query->fetchAll(\PDO::FETCH_CLASS),
			[$this, '_reduceHead'],
			new \stdClass()
		);
		$this->_parseURL($url);
		$stm = $pdo->prepare($this->_getSQL());
		$this->_setData($stm);
	}

	final public function __isset($prop)
	{
		return isset($this->{self::MAGIC_PROPERTY}[$prop]);
	}

	final public function __get($prop)
	{
		if ($this->__isset($prop)) {
			return $this->{self::MAGIC_PROPERTY}[$prop];
		}
	}

	final public function jsonSerialize()
	{
		return [
			'type' => $this::TYPE,
			'url' => $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->{self::MAGIC_PROPERTY}
		];
	}

	final public function __debugInfo()
	{
		return [
			'type' => $this::TYPE,
			'url' => $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->{self::MAGIC_PROPERTY}
		];
	}

	final public function getHead($prop = null)
	{
		return is_null($prop) ? clone($this->_head) : $this->_head->{$prop};
	}

	final public function getURL()
	{
		return $this->_url;
	}

	final public function getPath()
	{
		return $this->_parsePath();
	}

	final protected function _parsePath()
	{
		$path = explode('/', trim($this->_url['path'], '/'));
		$path = array_filter($path);
		return $path;
	}

	final private function _parseURL($url = null)
	{
		if (is_string($url)) {
			$url = parse_url($url);
		} else {
			$url = [];
		}

		$this->_url = array_merge([
			'scheme' => $_SERVER['REQUEST_SCHEME'],
			'host' => $_SERVER['HTTP_HOST'],
			'path' => $_SERVER['SCRIPT_NAME'],
		], $url);
	}

	final protected function _set($prop, $value)
	{
		$this->{self::MAGIC_PROPERTY}[$prop] = $value;
		return $this;
	}

	final private function _reduceHead(\stdClass $head, \stdClass $item)
	{
		$head->{$item->name} = $item->value;
		return $head;
	}

	abstract protected function _getSQL();

	abstract protected function _setData(\PDOStatement $stm);
}
