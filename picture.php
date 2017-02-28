<?php

namespace KVSun\KVSAPI;
use \shgysk8zer0\Core\{PDO, Console};
final class Picture
{
	use Traits\Images;

	private $_pdo;

	public function __construct(PDO $pdo)
	{
		$this->_pdo = $pdo;
	}

	public function getFigure(Int $id, \DOMElement $parent = null): \DOMElement
	{
		return $this->_createFigure($id, $parent);
	}

	public function getFigureHTML(Int $id): String
	{
		$figure = $this->getFigure($id);
		return $figure->ownerDocument->saveHTML($figure);
	}

	public function getPicture(Int $id, \DOMElement $parent = null): \DOMElement
	{
		//
	}

	public function __invoke(Int $id, \DOMElement $parent = null): \DOMElement
	{
		return $this->getFigure($id, $parent);
	}
}
