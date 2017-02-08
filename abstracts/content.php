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

	/**
	 * Create instance of content using database & URL
	 * @param PDO    $pdo Instance of database connection
	 * @param String $url The URL to get content for
	 */
	public function __construct(\PDO $pdo, String $url = null)
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

	/**
	 * Checks if a property is set
	 * @param  String  $prop Property to check
	 * @return boolean       If it is set
	 */
	final public function __isset(String $prop): Bool
	{
		return isset($this->{self::MAGIC_PROPERTY}[$prop]);
	}

	/**
	 * Gets a property from set data
	 * @param  String $prop The property
	 * @return Mixed        Its value, if it exists
	 */
	final public function __get(String $prop)
	{
		if ($this->__isset($prop)) {
			return $this->{self::MAGIC_PROPERTY}[$prop];
		}
	}

	/**
	 * Returns an array to convert to JSON when calling `json_encode` on class
	 * @return Array An array of data
	 */
	final public function jsonSerialize(): Array
	{
		return [
			'type' => $this::TYPE,
			'url' => $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->{self::MAGIC_PROPERTY}
		];
	}

	/**
	 * Returns an array of data to use when calling `var_dump` on class
	 * @return Array Array of data
	 */
	final public function __debugInfo(): Array
	{
		return [
			'type' => $this::TYPE,
			'url' => $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->{self::MAGIC_PROPERTY}
		];
	}

	/**
	 * Retrieve data from `head` table
	 * @param  String $prop Optional property from head to retrieve instead
	 * @return Mixed        Data from `head` table
	 */
	final public function getHead(String $prop = null)
	{
		return is_null($prop) ? clone($this->_head) : $this->_head->{$prop};
	}

	/**
	 * Returns URL parsed as an array
	 * @return Array ['path' => '...', 'scheme' => '...', 'host' => '...', ...]
	 */
	final public function getURL(): Array
	{
		return $this->_url;
	}

	final public function getPath(): Array
	{
		return $this->_parsePath();
	}

	/**
	 * Parses a path of a URL.
	 * "/path/to/file" becomes ['path', 'to', 'file']
	 * @return Array Parsed URL path
	 */
	final protected function _parsePath(): Array
	{
		$path = explode('/', trim($this->_url['path'], '/'));
		$path = array_filter($path);
		return $path;
	}

	/**
	 * Parses a URL and sets `$this->_url`
	 * @param  String $url The optional URL to parse
	 * @return void
	 */
	final private function _parseURL(String $url = null)
	{
		$url = parse_url($url) ?? [];

		$this->_url = array_merge([
			'scheme' => $_SERVER['REQUEST_SCHEME'],
			'host'   => $_SERVER['HTTP_HOST'],
			'path'   => $_SERVER['SCRIPT_NAME'],
		], $url);
	}

	/**
	 * Protected method for setting data
	 * @param  String $prop  Name of property to set
	 * @param  Mixed  $value Value to set it to
	 * @return self          Return self to make chainable
	 */
	final protected function _set(String $prop, $value): self
	{
		$this->{self::MAGIC_PROPERTY}[$prop] = $value;
		return $this;
	}

	/**
	 * Converts {name, value} into {name: value}
	 * @param  stdClass  $head Stores data retrieved from `head` table
	 * @param  stdClass  $item The current item
	 * @return stdClass        Updated $head
	 */
	final private function _reduceHead(\stdClass $head, \stdClass $item): \stdClass
	{
		$head->{$item->name} = $item->value;
		return $head;
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
	abstract protected function _setData(\PDOStatement $stm);
}
