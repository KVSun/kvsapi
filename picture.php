<?php

namespace KVSun\KVSAPI;
use \shgysk8zer0\Core\{PDO};

final class Picture
{
	use Traits\Images;

	private $_pdo;

	/**
	 * Create a new instance and set PDO instance var
	 * @param PDO $pdo Database connection
	 */
	public function __construct(PDO $pdo)
	{
		$this->_pdo = $pdo;
	}

	public function __invoke(...$args): \DOMElement
	{
		return $this->getFigure(...$args);
	}

	/**
	 * Get an image for post by its ID
	 * @param  Int       $img_id Image ID, such as from `posts`.`id`
	 * @return stdClass          The image as an object
	 */
	public function getImageData(Int $img_id): \stdClass
	{
		return $this->_getImage($img_id);
	}

	/**
	 * Creates a `<figure>` and `<picture>` element with `<figcaption>` if
	 * creator or caption are set
	 * @param  Int         $id     Image ID from `posts.img`
	 * @param  DOMElement  $parent Element to appdend it to
	 * @param  Array       $sizes  Media queries to hint at image size to use
	 * @return DOMElement          Newly created `<figure>`
	 */
	public function getFigure(
		Int         $id,
		\DOMElement $parent = null,
		Array       $sizes = array()
	): \DOMElement
	{
		return $this->_getFigure($id, $parent, $sizes);
	}

	/**
	 * Creates a `<figure>` and `<picture>` element with `<figcaption>` if
	 * creator or caption are set and returns HTML
	 * @param  Int         $id     Image ID from `posts.img`
	 * @param  DOMElement  $parent Element to appdend it to
	 * @param  Array       $sizes  Media queries to hint at image size to use
	 * @return String              Newly created `<figure>` as HTML string
	 */
	public function getFigureHTML(Int $id, Array $sizes = array()): String
	{
		$figure = $this->getFigure($id);
		return $figure->ownerDocument->saveHTML($figure, null, $sizes);
	}

	/**
	 * Appends a `<picture>` (responsive image) with microdata to an element
	 * @param Int         $img_id        `images`.`id`
	 * @param DOMElement  $parent        The element to append `<picture>` to
	 * @param Array       $sizes         Media queries to hint at image size to use
	 * @param Bool        $use_microdata Whether or not to set microdata for image
	 * @return DOMElement The `<picture>`
	 */
	public function getPicture(
		Int         $img_id,
		\DOMElement $parent        = null,
		Array       $sizes         = array(),
		Bool        $use_microdata = true
	): \DOMElement
	{
		$img = $this->_getImage($img_id);
		$img->id = $img_id;
		return $this->_getPicture($parent, $img, $sizes, $use_microdata);
	}

	/**
	 * Appends a `<picture>` (responsive image) with microdata to an element and returns HTML
	 * @param Int         $img_id        `images`.`id`
	 * @param Array       $sizes         Media queries to hint at image size to use
	 * @param Bool        $use_microdata Whether or not to set microdata for image
	 * @return String                    The `<picture>` as an HTML string
	 */
	public function getPictureHTML(
		Int   $id,
		Array $sizes         = array(),
		Bool  $use_microdata = true
	): String
	{
		$picture = $this->getPicture($id, null, $sizes, $use_microdata);
		return $picture->ownerDocument->saveHTML($picture);
	}
}
