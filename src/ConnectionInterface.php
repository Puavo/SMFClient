<?php

namespace SMFClient;

interface ConnectionInterface {
	public function __construct($forumAddress, $user, $password);
	public function createTopic($boardId, $subject, $message);
	public function readTopic($topicId);
	public function sendMessage($topicId, $subject, $message);
	public function editMessage($topic, $messageId, $subject, $message, $add = FALSE);
	public function sendPrivateMessage($userId, $subject, $message);
}
