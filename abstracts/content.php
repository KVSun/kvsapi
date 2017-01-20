<?php

namespace KVSun\KVSAPI\Abstracts;

use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\Core as Core;

abstract class Content implements \JsonSerializable
{
	const MAGIC_PROPERTY = '_data';

	private $_data = array();
	protected $_url;
	protected $_pdo;

	public function __construct(\PDO $pdo, $url = null)
	{
		$this->{self::MAGIC_PROPERTY} = $this::DEFAULTS;
		$this->_pdo = $pdo;
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
			'url' => $this->_url,
			'data' => $this->{self::MAGIC_PROPERTY}
		];
	}

	final public function __debugInfo()
	{
		return $this->{self::MAGIC_PROPERTY};
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

	abstract protected function _getSQL();

	abstract protected function _setData(\PDOStatement $stm);
}
