<?php
namespace KVSun\KVSAPI\Traits;
use \shgysk8zer0\Core\{PDO};
use \shgysk8zer0\Login\{User};
trait Images
{
	private static $_img_stm;
	private static $_source_stm;
	private static $_add_img_stm;
	private static $_add_source_stm;
	private static $_img_id_stm;

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
	 * Extract image data using microdata
	 * @param  DOMElement $figure `<figure><picture>...</picture>...</figure>`
	 * @return Array              Array of extracted data
	 */
	final public function parseFigure(\DOMElement $figure): Array
	{
		$data = [];
		if (
			$figure->tagName === 'figure'
			and $figure->hasAttribute('itemtype')
			and $figure->getAttribute('itemtype') === 'http://schema.org/ImageObject'
		) {
			$xpath = new \DOMXpath($figure->ownerDocument);
			if ($url = $xpath->query('.//*[@itemprop="url"]', $figure) and $url->length) {
				$url = $url->item(0);
				if ($url->hasAttribute('src')) {
					$data['path'] = $url->getAttribute('src');
				} elseif ($url->hasAttribute('content')) {
					$data['path'] = $url->getAttribute('content');
				} else {
					$data['path'] = $url->textContent;
				}
				unset($url);
			}
			if ($cap = $xpath->query('.//*[@itemprop="caption"]', $figure) and $cap->length) {
				$cap = $cap->item(0);
				if ($cap->hasAttribute('content')) {
					$data['caption'] = $cap->getAttribute('content');
				} else {
					$data['caption'] = $cap->textContent;
				}
				unset($cap);
			}
			if ($creator = $xpath->query('.//*[@itemprop="name"]', $figure) and $creator->length) {
				$creator = $creator->item(0);
				if ($creator->hasAttribute('content')) {
					$data['creator'] = $creator->getAttribute('content');
				} else {
					$data['creator'] = $creator->textContent;
				}
				unset($creator);
			}
			if ($size = $xpath->query('.//*[@itemprop="contentSize"]', $figure)and $size->length) {
				$size = $size->item(0);
				if ($size->hasAttribute('content')) {
					$data['size'] = intval($size->getAttribute('content')) * 1024;
				} else {
					$data['size'] = intval($size->textContent) * 1024;
				}
				unset($size);
			}
			if ($width = $xpath->query('.//*[@itemprop="width"]', $figure) and $width->length) {
				$width = $width->item(0);
				if ($width->hasAttribute('content')) {
					$data['width'] = intval($width->getAttribute('content'));
				} else {
					$data['width'] = intval($width->textContent);
				}
				unset($width);
			}
			if ($height = $xpath->query('.//*[@itemprop="height"]', $figure) and $height->length) {
				$height = $height->item(0);
				if ($height->hasAttribute('content')) {
					$data['height'] = intval($height->getAttribute('content'));
				} else {
					$data['height'] = intval($height->textContent);
				}
				unset($size);
			}
			if ($date = $xpath->query('.//*[@itemprop="uploadDate"]', $figure) and $date->length) {
				$date = $date->item(0);
				if ($date->hasAttribute('content')) {
					$data['uploadDate'] = $date->getAttribute('content');
				} else {
					$data['uploadDate'] = $date->textContent;
				}
				unset($date);
			}
			if ($format = $xpath->query('.//*[@itemprop="fileFormat"]', $figure) and $format->length) {
				$format = $format->item(0);
				if ($format->hasAttribute('content')) {
					$data['type'] = $format->getAttribute('content');
				} else {
					$data['type'] = $format->textContent;
				}
				unset($format);
			}
		}
		return $data;

	}

	/**
	 * Add images to `srcset` table
	 * @param  Array $sources   Array of image sources to add
	 * @param  Int   $parent_id Parent ID (PDO::lastInsertId())
	 * @return Bool             Whether or not all image sources were added
	 */
	public function addSources(Array $sources, Int $parent_id): Bool
	{
		// LAST_INSERT_ID returns 0 on update, so we can skip this
		if ($parent_id === 0) {
			return true;
		}
		if (is_null(static::$_add_source_stm)) {
			static::$_add_source_stm = $this->_pdo->prepare(
				'INSERT INTO `srcset` (
					`parentID`,
					`path`,
					`width`,
					`height`,
					`format`,
					`filesize`
				) VALUES (
					:parent,
					:path,
					:width,
					:height,
					:format,
					:size
				) ON DUPLICATE KEY UPDATE
					`filesize` = COALESCE(:size, `filesize`);'
			);
		}
		try {
			foreach ($sources as $source) {
				static::$_add_source_stm->execute([
					'parent' => $parent_id,
					'path'   => $source['path'],
					'width'  => $source['width'],
					'height' => $source['height'],
					'format' => $source['type'],
					'size'   => $source['size'],
				]);
				if (intval(static::$_add_source_stm->errorCode()) !== 0) {
					throw new \RuntimeException(
						'SQL Error: '. join(PHP_EOL, static::$_add_source_stm->errorInfo())
					);
				}
			}
			return true;
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
			return false;
		}
	}

	/**
	 * Adds an image to `images` table and returns its ID
	 * @param  Array $img  Array of image data
	 * @param  User  $user User object for user uploading image
	 * @return Int         ID of newly inserted image
	 */
	final public function addImage(Array $img, User $user): Int
	{
		if (is_null(static::$_add_img_stm)) {
			static::$_add_img_stm = $this->_pdo->prepare(
				'INSERT INTO `images` (
					`path`,
					`fileFormat`,
					`contentSize`,
					`height`,
					`width`,
					`creator`,
					`uploadDate`,
					`caption`,
					`alt`,
					`uploadedBy`
				) VALUES (
					:path,
					:format,
					:size,
					:height,
					:width,
					:creator,
					:date,
					:caption,
					:alt,
					:userId
				) ON DUPLICATE KEY UPDATE
					`creator`    = COALESCE(:creator, `creator`),
					`caption`    = COALESCE(:caption, `caption`),
					`alt`        = COALESCE(:alt,     `alt`),
					`uploadDate` = COALESCE(:date,    `uploadDate`),
					`uploadedBy` = :userId;'
			);
		}
		static::$_add_img_stm->execute([
			'path'    => '/'. ltrim($img['path'], '/'),
			'format'  => $img['type'],
			'size'    => $img['size'],
			'height'  => $img['height'],
			'width'   => $img['width'],
			'creator' => $img['creator'] ?? null,
			'date'    => $img['uploadDate'] ?? null,
			'caption' => $img['caption'] ?? null,
			'alt'     => $img['alt']     ?? null,
			'userId'  => $user->id,
		]);
		if (intval(static::$_add_img_stm->errorCode()) !== 0) {
			throw new \RuntimeException(
				'SQL Error: '. join(PHP_EOL, static::$_add_img_stm->errorInfo())
			);
		} else {
			return $insert_id = intval($this->_pdo->lastInsertId());
			if ($insert_id <= 0) {
				$insert_id = $this->getImageId($img['path']);
			}
			return $insert_id;
		}
	}

	/**
	 * Get an image's ID from its path
	 * @param  String $path "/path/to/image"
	 * @return Int          Image ID or 0 if not found
	 */
	final public function getImageId(String $path): Int
	{
		if (is_null(static::$_img_id_stm)) {
			static::$_img_id_stm = $this->_pdo->prepare(
				'SELECT `id` FROM `images` WHERE `path` = :path LIMIT 1;'
			);
		}
		static::$_img_id_stm->execute(['path' => '/' . ltrim($path, '/')]);
		$img = static::$_img_id_stm->fetchObject() ?? new \stdClass();

		return $img->id ?? 0;
	}

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
	 * Get the largest image of a set Mime-type from an array, such as from `$_FILES`
	 * @param  Array  $imgs Array of images
	 * @param  string $mime Mime-type of image to find the largest of
	 * @return Array       Image data for largest image
	 */
	final public function largestImage(Array $imgs, String $mime = 'image/jpeg'): Array
	{
		return array_reduce($imgs, function(Array $largest, Array $img) use($mime): Array
		{
			if (
				array_key_exists('type', $img)
				and array_key_exists('width', $img)
				and $img['type'] === $mime
				and $img['width'] > $largest['width']
			) {
				$largest = $img;
			}
			return $largest;
		}, ['width' => 0]);
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

	/**
	 * Validates that an image object has all required properties
	 * @param  stdClass $image Image object, such as from `images` table
	 * @return Bool            Whether or not all required attributes are set
	 */
	final protected static function _validImage(\stdClass $image): Bool
	{
		$valid = true;
		foreach (static::$_img_props as $prop) {
			if (! isset($image->{$prop})) {
				trigger_error("Missing {$prop} attribute");
				$valid = false;
				break;
			}
		}
		return $valid;
	}

	/**
	 * Validate that a srcset object has all required properties
	 * @param  stdClass $srcset srcset object, such as from `srcset` table
	 * @return Bool             Whether or not all required attributes are set
	 */
	final protected static function _validSrcset(\stdClass $srcset): Bool
	{
		$valid = true;
		foreach (static::$_srcset_props as $prop) {
			if (! isset($srcset->{$prop})) {
				trigger_error("Missing {$prop} attribute");
				$valid = false;
				break;
			}
		}
		return $valid;
	}

	/**
	 * Appends a `<picture>` (responsive image) with microdata to an element
	 * @param DOMElement  $parent The element to append `<picture>` to
	 * @param Int         $img_id `images`.`id`
	 * @return DOMElement The `<picture>`
	 */
	final protected function _getPicture(\DOMElement $parent, \stdClass $image): \DOMElement
	{
		if (! (static::_validImage($image) and isset($image->id))) {
			throw new \InvalidArgumentException('Cannot create an image without required attributes');
		}
		$dom = $parent->ownerDocument;
		$picture = $parent->appendChild($dom->createElement('picture'));
		$this->_addSources($picture,  $this->_getSources($image->id));
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

		return $picture;
	}

	/**
	 * Sort an array of `srcsets` (from `srcset` table) from smallest to largest width
	 * @param  stdClass $src1 A `srcset` object
	 * @param  stdClass $src2 Another `srcset` object
	 * @return Int            -1 if smaller, 0 if equal, 1 if greater
	 */
	final protected function _sortSources(\stdClass $src1, \stdClass $src2): Int
	{
		return $src1->width <=> $src2->width;
	}

	/**
	 * Creates a `<figure>` and `<picture>` element with `<figcaption>` if
	 * creator or caption are set
	 * @param  Int         $img_id Image ID from `posts.img`
	 * @param  DOMElement  $parent Element to appdend it to
	 * @return DOMElement         Newly created `<figure>`
	 */
	final protected function _getFigure(Int $img_id, \DOMElement $parent = null): \DOMElement
	{
		if (is_null($parent)) {
			$dom = new \DOMDocument('1.0', 'UTF-8');
			$dom->appendChild($dom->createElement('html'));
			$parent = $dom->documentElement->appendChild($dom->createElement('body'));
		} else {
			$dom = $parent->ownerDocument;
		}
		$figure = $parent->appendChild($parent->ownerDocument->createElement('figure'));
		$figure->setAttribute('data-image-id', $img_id);
		$figure->setAttribute('itemprop', 'image');
		$figure->setAttribute('itemtype', 'http://schema.org/ImageObject');
		$figure->setAttribute('itemscope', null);
		$image = $this->_getImage($img_id);
		$image->id = $img_id;
		$this->_getPicture($figure, $image);
		if (isset($image->caption) or isset($image->creator)) {
			$caption = $figure->appendChild($dom->createElement('figcaption'));
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
		return $figure;
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
