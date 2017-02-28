<?php
namespace KVSun\KVSAPI\Traits;
use \shgysk8zer0\Core\{PDO};
trait Images
{
	private static $_img_stm;
	private static $_source_stm;
	private static $_srcset_props = [
		'path',
		'width',
		'height',
		'format',
		'fileSize',
	];

	private static $_img_props = [
		'path',
		'fileFormat',
		'contentSize',
		'uploadDate',
		'height',
		'width',
	];

	/**
	 * Get an image for post by its ID
	 * @param  Int       $id Image ID, such as from `posts`.`id`
	 * @return stdClass      The image as an object
	 */
	final protected function _getImage(Int $id): \stdClass
	{
		if (is_null(static::$_img_stm)) {
			static::$_img_stm = $this->_pdo->prepare(
				'SELECT *
				FROM `images`
				WHERE `id` = :id
				LIMIT 1;'
			);
		}
		static::$_img_stm->bindParam(':id', $id);
		static::$_img_stm->execute();
		if ($img = static::$_img_stm->fetchObject()) {
			$img->sources = $this->_getSources($id);
			unset($img->id, $img->uploadedBy);
			return $img;
		} else {
			return new \StdClass();
		}
	}

	/**
	 * Get an array of sources for an image by parent ID
	 * @param  Int   $id Parent image ID
	 * @return Array     Array of sources
	 */
	final protected function _getSources(Int $id): Array
	{
		if (is_null(static::$_source_stm)) {
			static::$_source_stm = $this->_pdo->prepare(
				'SELECT * FROM `srcset` WHERE `parentID` = :id;'
			);
		}
		static::$_source_stm->bindParam(':id', $id);
		static::$_source_stm->execute();
		if ($sources = static::$_source_stm->fetchAll(PDO::FETCH_CLASS)) {
			foreach ($sources as &$source) {
				unset($source->parentId, $source->id);
			}
			return $sources;
		} else {
			return [];
		}
	}

	final protected static function _validImage(\stdClass $image): Bool
	{
		$valid = true;
		foreach (static::$_img_props as $prop) {
			if (! isset($image->{$prop})) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}

	final protected static function _validSrcset(\stdClass $srcset): Bool
	{
		$valid = true;
		foreach (static::$_srcset_props as $prop) {
			if (! isset($srcset->{$prop})) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}

	/**
	 * Appends a `<picture>` (responsive image) with microdata to an element
	 * @param DOMElement $parent The element to append `<picture>` to
	 * @param Int        $img_id `images`.`id`
	 */
	final protected function _addPicture(\DOMElement $parent, Int $img_id): \DOMElement
	{
		if ($parent->tagName !== 'figure') {
			throw new \InvalidArgumentException("Expected a <figure> but got a <{$parent->tagName}>");
		}
		$dom = $parent->ownerDocument;
		$picture = $parent->appendChild($dom->createElement('picture'));
		$sources = $this->_getSources($img_id);
		$image = $this->_getImage($img_id);
		if (!static::_validImage($image)) {
			throw new \RuntimeException('Image did not have all required properties');
		}
		$this->_addSources($picture, $sources);
		$img = $picture->appendChild($dom->createElement('img'));
		$img->setAttribute('src', $image->path);
		$img->setAttribute('width', $image->width);
		$img->setAttribute('height', $image->height);
		$img->setAttribute('alt', $image->alt ?? '');
		$img->setAttribute('itemprop', 'url');
		$meta = $parent->appendChild($dom->createElement('meta'));
		$meta->setAttribute('itemprop', 'width');
		$meta->setAttribute('content', $image->width);
		$meta = $parent->appendChild($dom->createElement('meta'));
		$meta->setAttribute('itemprop', 'height');
		$meta->setAttribute('content', $image->height);
		$meta = $parent->appendChild($dom->createElement('meta'));
		$meta->setAttribute('itemprop', 'fileFormat');
		$meta->setAttribute('content', $image->fileFormat);
		$meta = $parent->appendChild($dom->createElement('meta'));
		$meta->setAttribute('itemprop', 'contentSize');
		$meta->setAttribute('content', round($image->contentSize / 1024, 1) . ' kB');
		$meta = $parent->appendChild($dom->createElement('meta'));
		$meta->setAttribute('itemprop', 'uploadDate');
		$meta->setAttribute('content', $image->uploadDate);

		if (isset($image->caption) or isset($image->creator)) {
			$caption = $parent->appendChild($dom->createElement('figcaption'));
			if (isset($image->creator)) {
				$cite = $caption->appendChild($dom->createElement('cite', "Photo by&nbsp;"));
				$cite->setAttribute('itemprop', 'creator');
				$cite->setAttribute('itemtype', 'http://schema.org/Person');
				$cite->setAttribute('itemscope', null);
				$credit = $cite->appendChild($dom->createElement('span', $image->creator));
				$credit->setAttribute('itemprop', 'name');
				$caption->appendChild($dom->createElement('br'));
			}
			if (isset($image->caption)) {
				$cap = $caption->appendChild($dom->createElement('blockquote', $image->caption));
				$cap->setAttribute('itemprop', 'caption');
			}
		}
		return $parent;
	}

	final protected function _sortSources(\stdClass $src1, \stdClass $src2): Int
	{
		return $src1->width <=> $src2->width;
	}

	final protected function _createFigure(Int $img_id, \DOMElement $parent): \DOMElement
	{
		if (is_null($parent)) {
			$dom = new \DOMDocument('1.0', 'UTF-8');
			$dom->appendChild($dom->createElement('html'));
			$parent = $dom->documentElement->appendChild($dom->createElement('body'));
		}
		$figure = $parent->appendChild($parent->ownerDocument->createElement('figure'));
		$figure->setAttribute('data-image-id', $img_id);
		$figure->setAttribute('itemprop', 'image');
		$figure->setAttribute('itemtype', 'http://schema.org/ImageObject');
		$figure->setAttribute('itemscope', null);
		return $this->_addPicture($figure, $img_id);
	}

	/**
	 * Adds `<source>`s to a `<picture>`
	 * @param DOMElement $parent  `<picture>` to append `<source>`s to
	 * @param Array      $sources An array, such as from `srcset` table
	 */
	final protected function _addSources(\DOMElement $parent, Array $sources)
	{
		if ($parent->tagName !== 'picture') {
			throw new \InvalidArgumentException("Expected a <picture> but got a <{$parent->tagName}>");
		}
		usort($sources, [$this, '_sortSources']);
		$srcset = [];
		$srcs = [];
		foreach ($sources as $src) {
			if (! array_key_exists($src->format, $srcset)) {
				$srcset[$src->format] = $parent->appendChild($parent->ownerDocument->createElement('source'));
				$srcs[$src->format] = [];
				$srcset[$src->format]->setAttribute('type', $src->format);
			}
			$srcs[$src->format][] = "{$src->path} {$src->width}w";
		}
		foreach($srcs as $mime => $src) {
			$srcset[$mime]->setAttribute('srcset', join(',', $src));
		}
	}
}
