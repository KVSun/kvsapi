<?php

namespace KVSun\KVSAPI;

final class Category extends Abstracts\Content
{
	/**
	 * Type of content
	 * @var string
	 */
	const TYPE = 'category';

	/**
	 * Default content
	 * @var Array
	 */
	const DEFAULTS = [
		'articles' => [],
	];

	/**
	 * First int param for SQL LIMIT
	 * @var integer
	 */
	private $_start = 0;

	/**
	 * Second int param for SQL LIMIT
	 * @var integer
	 */
	private $_end   = 7;

	/**
	 * Create instance of content using database & URL
	 * @param PDO     $pdo   Instance of database connection
	 * @param String  $url   The URL to get content for
	 * @param Integer $start Optional starting point for category content
	 * @param Integer $end   Optional endinging point for category content
	 */
	public function __construct(
		\PDO   $pdo,
		String $url   = null,
		Int    $start = 0,
		Int    $end   = 7
	)
	{
		$this->_start = $start;
		$this->_end = $end;
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
			`categories`.`name`,
			`categories`.`parent`
		FROM `posts`
		JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
		WHERE `categories`.`url-name` = :name
		ORDER BY `posts`.`posted` DESC
		LIMIT $this->_start, $this->_end;";
	}

	/**
	 * Required method for setting data
	 * @param PDOStatement $stm A prepared statment using `\PDO::prepare`
	 */
	protected function _setData(\PDOStatement $stm)
	{
		$path = $this->_parsePath();
		$cat_url = $path[0];
		$stm->bindParam(':name', $cat_url);
		$stm->execute();
		$results = $stm->fetchAll(\PDO::FETCH_CLASS);

		$this->_set('category', $cat_url ?? null);
		$this->_set('articles', $results ?? []);
		$this->_set('title', $results[0]->name ?? 'Category not found');
	}
}
