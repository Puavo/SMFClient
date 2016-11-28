<?php

namespace SMFClient\TopicParser;

class TopicParser {

	private static function removeQuotes($topic) {
		$count = 1;
		while ($count) {
			$topic = preg_replace('/<div class="quoteheader">(?!.*<div class="quoteheader">).*?<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>/', '', $topic, 1, $count);
		}
		return $topic;
	}

	private static function removeSpecialCharacters($topic) {
		// Change non-breaking spaces to regular one
		$topic = str_replace("\00A0", " ", $topic);
		$topic = str_replace("&nbsp;", " ", $topic);
		return $topic;
	}

	private static function preProcess($topic) {
		$topic = self::removeSpecialCharacters($topic);

		return $topic;
	}

	public static function parseMessages($topic, $options) {
		$topic = self::preProcess($topic);
		$messages = [];

		$rows = explode("\n", $topic);
		$index = 0;
		$length = count($rows);
		while ($index < $length) {
			$row = $rows[$index];
			$row = trim($row);
			if ($row == '<dl id="posts">') {
				$index++;
				break;
			}
			$index++;
		}

		$postNumber = 0;
		while ($index < $length) {
			$message = [];
			$postNumber++;
			$row_postheader_start = trim($rows[$index]);
			// Check that we are dealing with actual post
			if ($row_postheader_start != '<dt class="postheader">') {
				break;
			}

			//$row_title = $rows[$index+1];
			$row_author = trim($rows[$index+2]);
			//$row_postheader_end = $rows[$index+3];
			//$row_postbody_start = $rows[$index+4];
			$row_postbody = trim($rows[$index+5]);
			// Remove double whitespaces
			$row_postbody = preg_replace('/\s+/', ' ', $row_postbody);
			$row_postbody = str_replace('<strong>', '', $row_postbody);
			//$row_postbody_end = $rows[$index+6];

			// Get name of the poster
			$row_author = strip_tags($row_author);
			$author_data = explode(" ", $row_author);

			$firstName = $author_data[1];
			$lastName = $author_data[2];
			// Get posting time
			$pos = strrpos($row_author, '-');
			$timeStr = substr($row_author, $pos + 2);
			$timeStr = str_replace(
				array("Tammikuu","Helmikuu","Maaliskuu","Huhtikuu","Toukokuu","Kesäkuu","Heinäkuu","Elokuu","Syyskuu","Lokakuu","Marraskuu","Joulukuu"),
				array("January","February","March","April","May","June","July","August","September","October","November","December"),
				$timeStr
				);
			$time = strtotime($timeStr);

			$index = $index + 7;

			$message['body'] = $row_postbody;
			$message['firstName'] = $firstName;
			$message['lastName'] = $lastName;
			$message['name'] = $firstName . ' ' . $lastName;
			$message['bodyWithoutQuotes'] = self::removeQuotes($row_postbody);
			$message['time'] = $time;
			$message['index'] = $postNumber;
			$messages[] = $message;
		}

		return $messages;
	}
}
