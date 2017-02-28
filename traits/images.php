<?php
namespace KVSun\KVSAPI\Traits;
use \shgysk8zer0\Core\{PDO};
trait Images
{
	private static $_img_stm;
	private static $_source_stm;

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
}
