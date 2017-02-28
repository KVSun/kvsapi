<?php

namespace KVSun\KVSAPI\Traits;

trait URL
{
	/**
	 * [$_url description]
	 * @var array
	 */
	protected $_url = array();

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
}
