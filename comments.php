<?php

namespace KVSun\KVSAPI;

final class Comments extends \SplObjectStorage implements \JsonSerializable
{
	public function __construct(\stdClass ...$comments)
	{
		foreach ($comments as $comment) {
			$this->attach(new Comment($comment));
		}
	}

	public function jsonSerialize(): Array
	{
		return $this->_asArray();
	}

	public function __debugInfo(): Array
	{
		return $this->_asArray();
	}

	private function _asArray(): Array
	{
		$items = [];
		foreach ($this as $item) {
			array_push($items, $item);
		}
		return $items;
	}

	public static function getComments(\PDO $pdo, Int $post_id, Int $cat_id): self
	{
		try {
			$stm = $pdo->prepare(
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
			return new self(...$comments ?? []);
		}
	}
}
