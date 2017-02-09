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

	public function __construct(\stdClass $comment_data)
	{
		if (!static::isValid($comment_data)) {
			throw new \InvalidArgumentException('Comment does not contain all required properties.');
		}
		parent::__construct(get_object_vars($comment_data), self::ARRAY_AS_PROPS);
	}

	public function jsonSerialize(): Array
	{
		return $this->getArrayCopy();
	}

	public function __debugInfo(): Array
	{
		return $this->getArrayCopy();
	}

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
