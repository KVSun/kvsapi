<?php

namespace KVSun\KVSAPI;

final class Comments extends \SplObjectStorage implements \JsonSerializable
{
	/**
	 * Create a Comments instance from a list of comments
	 * @param stdClass $comments $comment1, $comment2, ...
	 */
	public function __construct(\stdClass ...$comments)
	{
		foreach ($comments as $comment) {
			$this->attach(new Comment($comment));
		}
	}

	/**
	 * Method used when class instance passed to `json_encode`
	 * @return Array Array of Comments
	 */
	public function jsonSerialize(): Array
	{
		return $this->_asArray();
	}

	/**
	 * Method used when class instance passed to debugging function, such as `var_dump`
	 * @return Array Array of Comments
	 */
	public function __debugInfo(): Array
	{
		return $this->_asArray();
	}

	/**
	 * Private method for converting comment storage to an array
	 * @return Array Array of Comments
	 */
	private function _asArray(): Array
	{
		$items = [];
		foreach ($this as $item) {
			array_push($items, $item);
		}
		return $items;
	}

	/**
	 * Static method to get comments for an article / post
	 * @param  PDO     $pdo             Instance of database connection
	 * @param  Int     $post_id         Post ID
	 * @param  Int     $cat_id          Category ID
	 * @param  boolean $filter_approved Filter out comments that are not approved
	 * @param  Int     $start           Starting index
	 * @param  Int     $end             Ending index
	 * @param  Bool    $hash_email      Convert email to MD5 hash, I.E. for Gravatar
	 * @return self                     A new instance of Comments filled with comments on post
	 */
	public static function getComments(
		\PDO $pdo,
		Int  $post_id,
		Int  $cat_id,
		Bool $filter_approved = true,
		Int  $start           = 0,
		Int  $end             = PHP_INT_MAX,
		Bool $hash_email      = true
	): self
	{
		try {
			$stm = $pdo->prepare(
				"SELECT
					`post_comments`.`created`,
					`post_comments`.`text`,
					`post_comments`.`id` AS `commentID`,
					`post_comments`.`approved`,
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
				ORDER BY `post_comments`.`created` DESC
				LIMIT $start, $end;"
			);
			$stm->bindParam(':post', $post_id);
			$stm->bindParam(':cat', $cat_id);
			$stm->execute();
			if (intval($stm->errorCode()) !== 0) {
				throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
			}
			$comments = $stm->fetchAll(\PDO::FETCH_CLASS);
			if ($filter_approved) {
				$comments = array_filter($comments, __CLASS__ . '::_isApproved');
			}
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		} finally {
			return new self(...$comments ?? null);
		}
	}

	/**
	 * Private method for filtering comments by `approved` column in table
	 * @param  StdClass $comment Comment data
	 * @return Bool              Whether or not the comment has been approved
	 */
	private static function _isApproved(\StdClass $comment): Bool
	{
		return isset($comment->approved) and $comment->approved == 1;
	}
}
