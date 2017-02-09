<?php
namespace KVSun\KVSAPI;

final class Article extends Abstracts\Content
{
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
	protected function _setData(\PDOStatement $stm)
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

				$cat = new \stdClass();
				$cat->name = $results->category;
				$cat->url = $results->catURL;
				$cat->id = intval($results->catID);

				$this->_set('id', intval($results->id));
				$this->_set('category', $cat);
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
				$this->_set('is_free', $results->is_free ?? true);
				$this->_set('comments', $this->_getComments($this->id, $cat->id));
			}
		}
	}

	/**
	 * Gets an array of comments for post by post ID and category ID
	 * @param  Int   $post_id ID of post (`posts`.`id`)
	 * @param  Int   $cat_id  ID of category (`posts`.`cat-id`)
	 * @return Array          An array of comments for post
	 */
	private function _getComments(Int $post_id, Int $cat_id): Array
	{
		$stm = $this->_pdo->prepare(
			'SELECT
				`post_comments`.`created`,
				`post_comments`.`text`,
				`post_comments`.`id` AS `commentID`,
				`user_data`.`name`,
				`users`.`username`,
				`users`.`email`
			FROM `post_comments`
			JOIN `posts` ON `posts`.`id` = `post_comments`.`postID`
				AND `posts`.`cat-id` = `post_comments`.`catID`
			JOIN `users` ON `users`.`id` = `post_comments`.`userID`
			JOIN `user_data` ON `user_data`.`id` = `post_comments`.`userID`
			WHERE `posts`.`id` = :post
			AND `posts`.`cat-id` = :cat
			AND `post_comments`.`approved` = 1
			ORDER BY `post_comments`.`created` DESC;'
		);
		$stm->bindParam(':post', $post_id);
		$stm->bindParam(':cat', $cat_id);
		try {
			$stm->execute();
			if (intval($stm->errorCode()) !== 0) {
				throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
			}
			$comments = $stm->getResults();
			// Convert user email to an MD5, useful for Gravatar
			array_walk($comments, function(\stdClass &$comment)
			{
				$comment->email = md5(strtolower($comment->email));
			});
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		} finally {
			return $comments ?? [];
		}
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
