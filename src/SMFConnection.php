<?php

namespace SMFClient\SMFConnection;

/**
 * Class to handle connection to Simple machines forum. 
 *
 */
class SMFConnection implements ConnectionInterface {
	// Forum address.
	private $address;
	// Curl connection resource.
	private $connection;

	/**
	 * Initialize connection and log in to the forum.
	 *
	 * @param $forumAddress Full address to the forum.
	 * @param $user User's login name.
	 * @param $password User's password.
	 */
	public function __construct($forumAddress, $user, $password) {
		$this->address = $forumAddress;
		$this->connection = curl_init();
		curl_setopt_array($this->connection, array(
			CURLOPT_POST => TRUE,
			CURLOPT_HEADER => TRUE,
			CURLOPT_VERBOSE => 1,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => -10,
			CURLOPT_SSL_VERIFYPEER =>  FALSE,
			CURLOPT_SSL_VERIFYHOST =>  FALSE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_COOKIEJAR => $_SERVER['DOCUMENT_ROOT'] . "/cookie.txt",
			CURLOPT_URL => $this->address . "/index.php",
			CURLOPT_POSTFIELDS => "&action=login2&user=$user&passwrd=$password&cookielength=-1&submit=Login",
			CURLOPT_RETURNTRANSFER => TRUE,
		));

		$result = curl_exec($this->connection);
	}

	/**
	 * Create a new topic to the given board.
	 *
	 * @param $boardId The internal id of the forum board.
	 * @param $subject Subject of the topic.
	 * @param $message Message for the first post.
	 *
	 * @return string $topicId
	 *   Id of the created topic.
	 */
	public function createTopic($boardId, $subject, $message) {
		// Turn text data to ISO-8859-1.
		$subject = utf8_decode($subject);
		$message = utf8_decode($message);
		// Get post form
		curl_setopt($this->connection, CURLOPT_POST, FALSE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post&board=" . $boardId);
		$result = curl_exec($this->connection);
		// Parse necessary hidden fields from form.
		preg_match_all("/<input type=\"hidden\" name=\"(.*?)\" value=\"(.*?)\" \/>/", $result, $matches);
		$seqnum = $matches[2][7];
		$name = $matches[1][6];
		$value = $matches[2][6];
		// Send message
		curl_setopt($this->connection, CURLOPT_POST, TRUE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post2;start=0;board=" . $boardId);
		curl_setopt($this->connection, CURLOPT_POSTFIELDS, "message=".urlencode($message)."&$name=$value&goback=1&seqnum=$seqnum&subject=$subject&post=Post&notify=0&action=post2&board=$boardId");
		$result = curl_exec($this->connection);
		sleep(5);
		$returnAddress = curl_getinfo($this->connection, CURLINFO_EFFECTIVE_URL);
		// Parse topic id from address.
		preg_match("/\?topic=([0-9]+)/", $returnAddress, $matches);
		$topicId = $matches[1];
		return $topicId;

	}

	/**
	 * Read all information in a topic.
	 *
	 * @param $topicId The internal id of the forum topic.
	 *
	 * @return String $result
	 *   Contents of the topic.
	 */
	public function readTopic($topicId) {
		curl_setopt($this->connection, CURLOPT_POST, FALSE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=printpage;topic=" . $topicId . ".0");
		$result = curl_exec($this->connection);
		return utf8_encode($result);
	}

	/**
	 * Send message to the given topic.
	 *
	 * @param $topicId The internal id of the forum topic.
	 * @param $subject Subject of the topic.
	 * @param $message Message for the post.
	 *
	 * @return String $messageId
	 *   Id of the created post.
	 */
	public function sendMessage($topicId, $subject, $message) {
		// Turn text data to ISO-8859-1.
		$subject = utf8_decode($subject);
		$message = utf8_decode($message);
		// Get post form.
		curl_setopt($this->connection, CURLOPT_POST, FALSE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post&topic=" . $topicId);
		$result = curl_exec($this->connection);
		// Parse necessary hidden fields from form.
		preg_match_all("/<input type=\"hidden\" name=\"(.*?)\" value=\"(.*?)\" \/>/", $result, $matches);
		$seqnum = $matches[2][8];
		$name = $matches[1][7];
		$value = $matches[2][7];
		// Send message.
		curl_setopt($this->connection, CURLOPT_POST, TRUE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post2;start=0");
		curl_setopt($this->connection, CURLOPT_POSTFIELDS, "message=".urlencode($message)."&$name=$value&goback=1&seqnum=$seqnum&subject=$subject&post=Post&notify=0&topic=$topicId&action=post2");
		$result = curl_exec($this->connection);
		sleep(5);
		// Parse message ids from the result.
		preg_match_all("/topic=$topicId\.msg([0-9]+)#msg/", $result, $matches);
		// Take last match. That's the post just sent.
		$messageId = end($matches[1]);
		return $messageId;

	}

	/**
	 * Edit message.
	 *
	 * @param $topicId The internal id of the forum topic.
	 * @param $message The internal id of the post.
	 * @param $subject Subject of the topic.
	 * @param $message Message for the post.
	 * @param $add Replace or extend current message.
	 */
	public function editMessage($topicId, $messageId, $subject, $message, $add = FALSE) {
		// Turn text data to ISO-8859-1.
		$subject = utf8_decode($subject);
		$message = utf8_decode($message);
		// Get post form
		curl_setopt($this->connection, CURLOPT_POST, FALSE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post&msg=" . $messageId . "&topic=" . $topicId);
		$result = curl_exec($this->connection);
		// Parse necessary hidden fields from form
		preg_match_all("/<input type=\"hidden\" name=\"(.*?)\" value=\"(.*?)\" \/>/", $result, $matches);
		$seqnum = $matches[2][8];
		$name = $matches[1][7];
		$value = $matches[2][7];
		// Send message
		curl_setopt($this->connection, CURLOPT_POST, TRUE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=post2;msg=$messageId;topic=$topicId");
		curl_setopt($this->connection, CURLOPT_POSTFIELDS, "message=".urlencode($message)."&$name=$value&seqnum=$seqnum&msg=$messageId&subject=$subject&post=Post&notify=0&topic=$topicId&action=post2");
		$result = curl_exec($this->connection);
	}

	/**
	 * Search for private messages.
	 *
	 * @param $searchWord Search for messages containing this word.
	 *
	 * @return String $result
	 *   Results of the search. 
	 */
	public function readPrivateMessages($searchWord) {
		// Access private message search
		curl_setopt($this->connection, CURLOPT_POST, TRUE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=pm;sa=search2");
		curl_setopt($this->connection, CURLOPT_POSTFIELDS, "advanced=1&search=$searchWord&searchtype=1&userspec=*&sort=id_pm%7Cdesc&minage=0&maxage=9999&submit=Hae");
		$result = curl_exec($this->connection);
		// Turn results to UTF-8 and return them.
		return utf8_encode($result);

	}

	/**
	 * Send private message to a user or users.
	 *
	 * @param $userId The internal id of the user or array of ids.
	 * @param $subject Subject of the message.
	 * @param $message Actual message.
	 */
	public function sendPrivateMessage($userId, $subject, $message) {
		// Turn text data to ISO-8859-1.
		$subject = utf8_decode($subject);
		$message = utf8_decode($message);
		// Get post form
		curl_setopt($this->connection, CURLOPT_POST, FALSE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=pm;sa=send2");
		$result = curl_exec($this->connection);
		// Parse necessary hidden fields from form
		preg_match_all("/<input type=\"hidden\" name=\"(.*?)\" value=\"(.*?)\" \/>/", $result, $matches);
		$seqnum = $matches[2][3];
		$name = $matches[1][2];
		$value = $matches[2][2];
		// Send message
		curl_setopt($this->connection, CURLOPT_POST, TRUE);
		curl_setopt($this->connection, CURLOPT_URL, $this->address . "/index.php?action=pm;sa=send2");
		// Set recipients
		if (is_array($userId)) {
			$recipient = "";
			foreach ($userId as $id) {
				$recipient .= "&recipient_to%5B%5D=$id";
			}
		}
		else {
			$recipient = "&recipient_to%5B%5D=$userId";
		}
		curl_setopt($this->connection, CURLOPT_POSTFIELDS, "message=".urlencode($message)."&$name=$value&seqnum=$seqnum&subject=$subject&post=Post&outbox=1$recipient");
		$result = curl_exec($this->connection);
	}
}
