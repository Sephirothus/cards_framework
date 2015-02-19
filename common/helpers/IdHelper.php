<?php
namespace common\helpers;

class IdHelper {

	public static function toId($id) {
		return new \MongoId((string)$id);
	}

	public static function fromId($id) {
		return (string)$id;
	}
}