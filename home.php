<?php

namespace KVSun\KVSAPI;

final class Home extends Abstracts\Content
{
	const TYPE = 'home';
	const DEFAULTS = [
		'sections' => [],
	];

	public function __construct(\PDO $pdo, $url = null, Array $categories, $count = 12)
	{
		$this->_categories = $categories;
		$this->_count = $count;
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
			`categories`.`parent`,
			`categories`.`name` as `category`
		FROM `posts`
		JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
		WHERE `categories`.`url-name` = :name
		ORDER BY `posts`.`posted` DESC
		LIMIT %d;';

		return sprintf($sql, $this->_count);
	}

	protected function _setData(\PDOStatement $stm)
	{
		$categories = new \stdClass();

		foreach ($this->_categories as $cat) {
			$stm->bindParam(':name', $cat);
			$stm->execute();
			$categories->{$cat} = $stm->fetchAll(\PDO::FETCH_CLASS);
		}

		$this->_set('sections', $categories);
	}
}
