<?php

namespace KVSun\KVSAPI;

final class Home extends Abstracts\Content
{
	/**
	 * Type of content
	 * @var string
	 */
	const TYPE = 'home';

	/**
	 * Default content
	 * @var Array
	 */
	const DEFAULTS = [
		'categories' => null,
	];

	/**
	 * Creates an instance of Home containing categories and articles
	 * @param PDO     $pdo        Instance of database connection
	 * @param String  $url        URL to retrieve data for (this should always be '/')
	 * @param Array   $categories Array of categories to get data for
	 * @param integer $count      Max number of results
	 */
	public function __construct(
		\PDO    $pdo,
		String  $url        = null,
		Array   $categories,
		Int     $count      = 12
	)
	{
		$this->_categories = $categories;
		$this->_count = $count;
		parent::__construct($pdo, $url);
	}

	/**
	 * Required method to get query to execute
	 * @return String SQL query
	 */
	protected function _getSQL(): String
	{
		return "SELECT
			`posts`.`title`,
			`posts`.`author`,
			`posts`.`img`,
			`posts`.`url`,
			`posts`.`posted`,
			`posts`.`isFree`,
			`posts`.`keywords`,
			`posts`.`description`,
			`posts`.`draft`,
			`categories`.`url-name` AS `catURL`,
			`categories`.`icon`,
			`categories`.`parent`,
			`categories`.`name` as `category`
		FROM `posts`
		JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
		WHERE `categories`.`url-name` = :cat
		ORDER BY `posts`.`posted` DESC
		LIMIT {$this->_count};";
	}

	/**
	 * Required method for setting data
	 * @param PDOStatement $stm A prepared statment using `\PDO::prepare`
	 */
	protected function _setData(\PDOStatement $stm)
	{
		$cats = new \stdClass();

		foreach ($this->_categories as $cat) {
			$stm->bindParam(':cat', $cat);
			$stm->execute();
			$results = $stm->fetchAll(\PDO::FETCH_CLASS) ?? [];
			array_reduce($results, [$this, '_reduceSections'], $cats);
		}

		$this->_set('categories', $cats);
	}

	/**
	 * Maps data from table to more complex objects
	 * @param  stdClass  $sections Class containing all sections
	 * @param  StdClass  $post     Individual post containing full data from table
	 * @return stdClass            $sections with $post data appended, possibly creating a new section
	 */
	private function _reduceSections(\stdClass $sections, \StdClass $post): \stdClass
	{
		$sec_name = $post->category;

		// If section is not an object in $sections, create the object
		// and give it properties that belong on sections/categories
		if (! isset($sections->{$sec_name})) {
			$sections->{$sec_name} = new \stdClass();
			$sections->{$sec_name}->posts = [];
			$sections->{$sec_name}->icon = $post->icon;
			$sections->{$sec_name}->catURL = $post->catURL;
			$sections->{$sec_name}->parent = $post->parent;
		}

		// Section will exist in $sections now, so add individual post data
		// to the array, of posts for the section
		$sections->{$sec_name}->posts[] = (object)[
			'title'         => $post->title,
			'author'        => $post->author,
			'description'   => $post->description,
			'keywords'      => $this->_getKeywords($post->keywords ?? ''),
			'img'           => $post->img,
			'url'           => "{$post->catURL}/{$post->url}",
			'posted'        => $post->posted,
			'isFree'        => $post->isFree === '1',
			'isDraft'       => $post->draft === '1',
		];

		return $sections;
	}

	/**
	 * Converts keywords from string to array (trimmed)
	 * @param  String $keywords "keywords 1, keyword2, ..."
	 * @return Array            ["keywords 1", "keyword2", ...]
	 */
	protected function _getKeywords(String $keywords): Array
	{
		return empty($keywords)
			? []
			: array_map('trim', array_filter(explode(',', $keywords)));
	}
}
