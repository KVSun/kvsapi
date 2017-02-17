<?php

namespace KVSun\KVSAPI;

final class Comment extends \ArrayObject implements \JsonSerializable
{
	const REQUIRED = [
		'created',
		'text',
		'commentID',
		'name',
		'username',
		'email',
	];

	/**
	 * Create a comment instance from comment data
	 * @param stdClass $comment_data Data from comments table
	 * @param boolean  $hash_email   Whether or not to convert email to MD5 hash
	 */
	public function __construct(\stdClass $comment_data, Bool $hash_email = true)
	{
		if (!static::isValid($comment_data)) {
			throw new \InvalidArgumentException('Comment does not contain all required properties.');
		} elseif ($hash_email) {
			$comment_data->email = md5(strtolower($comment_data->email));
		}
		parent::__construct(get_object_vars($comment_data), self::ARRAY_AS_PROPS);
	}

	/**
	 * Method used when class instance is used as a string
	 * @return string Return comment text
	 */
	public function __toString(): String
	{
		return $this->text;
	}

	/**
	 * Method used when class instance is passed to `json_encode`
	 * @return Array Comment data as array
	 */
	public function jsonSerialize(): Array
	{
		return $this->getArrayCopy();
	}

	/**
	 * Method used when class instance passed to debugging function, such as `var_dump`
	 * @return Array Comment data as array
	 */
	public function __debugInfo(): Array
	{
		return $this->getArrayCopy();
	}

	/**
	 * Checks if $omment contains all requried fields
	 * @param  stdClass $comment Comment data to check
	 * @return Bool              Whether or not all fields exist
	 */
	public static function isValid(\stdClass $comment): Bool
	{
		$valid = true;
		foreach (self::REQUIRED as $prop) {
			if (!isset($comment->{$prop})) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}
}
