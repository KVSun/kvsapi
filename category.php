<?php

namespace KVSun\KVSAPI;

final class Category extends Abstracts\Content
{
	const TYPE = 'category';
	const DEFAULTS = [
		'articles' => [],
	];

	private $_start = 0;
	private $_end   = 7;

	public function __construct(\PDO $pdo, $url = null, $start = 0, $end = 7)
	{
		$this->_start = $start;
		$this->_end = $end;
		parent::__construct($pdo, $url);
	}

	protected function _getSQL()
	{
		$sql = 'SELECT
			`posts`.`title`,
			`posts`.`author`,
			`posts`.`img`,
			`posts`.`url`,
			`posts`.`posted`,
			`categories`.`url-name` AS `catURL`,
			`categories`.`parent`
		FROM `posts`
		JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
		WHERE `categories`.`url-name` = :name
		ORDER BY `posts`.`posted` DESC
		LIMIT %d, %d;';

		return sprintf($sql, $this->_start, $this->_end);
	}

	protected function _setData(\PDOStatement $stm)
	{
		$path = $this->_parsePath();
		$cat = $path[0];
		$stm->bindParam(':name', $cat);
		$stm->execute();
		$results = $stm->fetchAll(\PDO::FETCH_CLASS);

		$this->_set('category', $cat);
		$this->_set('articles', $results);
		$this->_set('title', ucwords($cat));
	}
}
