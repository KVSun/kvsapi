<?php

namespace KVSun\KVSAPI;

use \PDO;
use \PDOStatement;

final class BusinessDirectory extends Abstracts\Content
{
	const TYPE = 'businessdirectory';
	const DEFAULTS = [
		'categories' => [],
		'title'      => 'Business Directory',
		'keywords'   => [
			'businesses',
			'directory',
			'kvsun',
			'lake isabella',
			'kern valley',
			'kern river valley',
		],
	];

	public function __construct(PDO $pdo, String $url)
	{
		$this->_init($pdo, $url);
	}

	/**
	 * Required method to get query to execute
	 * @return String SQL query
	 */
	protected function _getSQL(): String
	{
		return 'SELECT
			`name`,
			`category`,
			`description` AS `text`,
			`start`,
			`end`,
			`img` AS `image`
		FROM `businessDirectory`
		WHERE `start` <= CURRENT_DATE
		AND (
			`end` IS NULL
			OR `end` >= CURRENT_DATE
		);';
	}

	/**
	 * Required method for setting data
	 * @param PDOStatement $stm A prepared statment using `\PDO::prepare`
	 */
	protected function _setData(PDOStatement $stm)
	{
		$stm->execute();
		$cats = [];
		$results = $stm->fetchAll(PDO::FETCH_CLASS);

		foreach ($results as $result) {
			if (! array_key_exists($result->category, $cats)) {
				$cats[$result->category] = [];
			}
			$cats[$result->category][] = $result;
			unset($cats[$result->category]->category);
		}
		$this->_set('categories', $cats);
	}
}
