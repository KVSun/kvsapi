<?php
namespace KVSun\KVSAPI;

final class Article extends Abstracts\Content
{
	const TYPE = 'article';
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
	];

	protected function _setData(\PDOStatement $stm)
	{
		$path = $this->_parsePath();

		if (count($path) === 2) {
			list($category, $url) = $path;
			\shgysk8zer0\Core\Console::info(['category' => $category, 'url' => $url]);
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

				$this->_set('id', intval($results->id));
				$this->_set('category', $results->category);
				$this->_set('title', $results->title);
				$this->_set('author', $results->author);
				$this->_set('content', $results->content);
				$this->_set('posted', $results->posted);
				$this->_set('updated', $results->updated);
				$this->_set('draft', $results->draft === '1');
				$this->_set('url', $results->url);
				$this->_set('img', $results->img);
				$this->_set('posted_by', $results->posted_by);
				$this->_set('keywords', $keywords);
				$this->_set('description', $results->description);
				$this->_set('is_free', !isset($results->is_free) or $results->is_free === '1');
			}
		}
	}

	protected function _getSQL()
	{
		return 'SELECT `posts`.`id`,
			`categories`.`name` AS `category`,
			`posts`.`title`,
			`posts`.`author`,
			`posts`.`content`,
			`posts`.`posted`,
			`posts`.`updated`,
			`posts`.`draft`,
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
