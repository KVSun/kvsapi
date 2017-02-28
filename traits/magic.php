<?php

namespace KVSun\KVSAPI\Traits;

trait Magic
{
	/**
	 * [$_data description]
	 * @var array
	 */
	private $_data = array();

	/**
	 * Checks if a property is set
	 * @param  String  $prop Property to check
	 * @return boolean       If it is set
	 */
	final public function __isset(String $prop): Bool
	{
		return isset($this->_data[$prop]);
	}

	/**
	 * Gets a property from set data
	 * @param  String $prop The property
	 * @return Mixed        Its value, if it exists
	 */
	final public function __get(String $prop)
	{
		if ($this->__isset($prop)) {
			return $this->_data[$prop];
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
			'url' =>  $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->_data
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
			'url'  => $this->getURL(),
			'head' => $this->getHead(),
			'data' => $this->_data
		];
	}

	final protected function _setDefaults(Array $defaults)
	{
		$this->_data = $defaults;
	}

	/**
	 * Protected method for setting data
	 * @param  String $prop  Name of property to set
	 * @param  Mixed  $value Value to set it to
	 * @return self          Return self to make chainable
	 */
	final protected function _set(String $prop, $value): self
	{
		$this->_data[$prop] = $value;
		return $this;
	}

	abstract function getHead(): \stdClass;

	abstract function getURL(): Array;
}
