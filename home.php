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
		'sections' => [],
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
		$categories = new \stdClass();

		foreach ($this->_categories as $cat) {
			$stm->bindParam(':cat', $cat);
			$stm->execute();
			$categories->{$cat} = $stm->fetchAll(\PDO::FETCH_CLASS) ?? [];
		}

		$this->_set('sections', $categories);
	}
}
