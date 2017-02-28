<?php
namespace KVSun\KVSAPI;
use \shgysk8zer0\Core\{PDO};
use \PDOStatement;
use \stdClass;

final class Article extends Abstracts\Content
{
	use Traits\Images;
	public function __construct(PDO $pdo, String $url = null)
	{
		$this->_init($pdo, $url);
	}

	/**
	 * Type of content
	 * @var string
	 */
	const TYPE = 'article';

	/**
	 * Default content
	 * @var Array
	 */
	const DEFAULTS = [
		'id'          => null,
		'category'    => null,
		'title'       => null,
		'author'      => null,
		'content'     => null,
		'posted'      => null,
		'updated'     => null,
		'draft'       => true,
		'url'         => [],
		'img'         => null,
		'posted_by'   => null,
		'keywords'    => [],
		'description' => null,
		'is_free'     => true,
		'comments'    => [],
	];

	/**
	 * Required method for setting data
	 * @param PDOStatement $stm A prepared statment using `\PDO::prepare`
	 */
	protected function _setData(PDOStatement $stm)
	{
		$path = $this->_parsePath();

		if (count($path) === 2) {
			list($category, $url) = $path;
			$stm->bindParam(':url', $url);
			$stm->bindParam(':category', $category);
			$stm->execute();
			$results = $stm->fetchObject();
			if (isset(
				$results->id,
				$results->category,
				$results->title,
				$results->author,
				$results->content,
				$results->posted
			)) {
				if (isset($results->keywords)) {
					$keywords = explode(',', $results->keywords);
					$keywords = array_filter($keywords);
					$keywords = array_map('trim', $keywords);
				} else {
					$keywords = [];
				}

				$cat = new stdClass();
				$cat->name = $results->category;
				$cat->url = $results->catURL;
				$cat->id = intval($results->catID);

				$this->_set('id', intval($results->id));
				$this->_set('category', $cat);
				$this->_set('title', $results->title);
				$this->_set('author', $results->author);
				$this->_set('content', $this->_articleBuilder($results->content));
				$this->_set('posted', $results->posted);
				$this->_set('updated', $results->updated);
				$this->_set('draft', $results->draft === '1');
				$this->_set('url', $results->url);
				$this->_set('img', $results->img);
				$this->_set('image', $this->_getImage(intval($results->img)));
				$this->_set('posted_by', $results->posted_by);
				$this->_set('keywords', $keywords);
				$this->_set('description', $results->description);
				$this->_set('is_free', $results->isFree === '1' ?? true);
				$this->_set('comments', Comments::getComments($this->_pdo, $this->id, $cat->id));
			}
		}
	}

	/**
	 * Updates article HTML, creating responsive images
	 * @param  String $content Article HTML
	 * @return String          Updated HTML as string
	 */
	protected function _articleBuilder(String $content): String
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		libxml_use_internal_errors(true);
		$dom->loadHTML("<div>$content</div>");
		libxml_clear_errors();

		foreach ($dom->documentElement->getElementsByTagName('figure') as $figure) {
			if ($figure->hasAttribute('data-image-id')) {
				try {
					$new_fig = $this->_getFigure(
						$figure->getAttribute('data-image-id'),
						$figure->parentNode
					);
					$figure->parentNode->replaceChild($new_fig, $figure);
				} catch (\Throwable $e) {
					trigger_error($e->getMessage());
				}
			}
		}
		foreach ($dom->documentElement->getElementsByTagName('img') as $img) {
			if ($img->hasAttribute('data-image-id')) {
				$figure = $this->_getFigure(
					$img->getAttribute('data-image-id'),
					$img->parentNode
				);
				$img->parentNode->replaceChild($figure, $img);
			}
		}
		return $dom->saveHTML($dom->documentElement->firstChild);
	}

	/**
	 * Required method to get query to execute
	 * @return String SQL query
	 */
	protected function _getSQL(): String
	{
		return 'SELECT `posts`.`id`,
			`categories`.`name` AS `category`,
			`categories`.`url-name` AS `catURL`,
			`categories`.`id` AS `catID`,
			`posts`.`title`,
			`posts`.`author`,
			`posts`.`content`,
			`posts`.`posted`,
			`posts`.`updated`,
			`posts`.`draft`,
			`posts`.`isFree`,
			`posts`.`url`,
			`posts`.`img`,
			`user_data`.`name` AS `posted_by`,
			`posts`.`keywords`,
			`posts`.`description`
		FROM `posts`
		JOIN `user_data` ON `posts`.`posted_by` = `user_data`.`id`
		JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
		WHERE `posts`.`url` = :url AND `categories`.`url-name` = :category
		LIMIT 1;';
	}
}
