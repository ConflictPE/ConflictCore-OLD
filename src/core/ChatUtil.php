<?php

namespace core;

use pocketmine\command\CommandSender;
use pocketmine\event\TimingsHandler;
use pocketmine\Player;

class ChatUtil {

	const MSG_LEN = 30;
	const TITLE_CHAR = '*';
	const BORDER_CHAR = '*';
	const LIST_PER_PAGE = 5;
	const CHAR_LENGTH = 6;
	const SPACE_CHAR = " ";

	/**
	 * @var TimingsHandler
	 */
	public static $centerTimings = null;

	/**
	 * @var TimingsHandler
	 */
	public static $wrapTimings = null;

	/**
	 * @var TimingsHandler
	 */
	public static $lenTimings = null;

	private static $charLengths = [
		" " => 4,
		"!" => 2,
		"\"" => 5,
		"'" => 3,
		"(" => 5,
		")" => 5,
		"*" => 5,
		"," => 2,
		"." => 2,
		":" => 2,
		";" => 2,
		"<" => 5,
		">" => 5,
		"@" => 7,
		"I" => 4,
		"[" => 4,
		"]" => 4,
		"f" => 5,
		"i" => 2,
		"k" => 5,
		"l" => 3,
		"t" => 4,
		"{" => 5,
		"|" => 2,
		"}" => 5,
		"~" => 7,
		"█" => "9",
		"░" => "8",
		"▒" => "9",
		"▓" => "9",
		"▌" => "5",
		"─" => "9",
		//        "ï"  => 4,
		//        "ì" => 3,
		//        "×" => 4,
		//        "í" => 3,
		//        "®" => 7,
		//        "¡" => 2,
		//		"-" => 4
	];

	public static function init() {
		if(self::$centerTimings === null) {
			self::$centerTimings = new TimingsHandler("Plugin: Components Event: " . self::class . "::centerPrecise()");
		}
		if(self::$wrapTimings === null) {
			self::$wrapTimings = new TimingsHandler("Plugin: Components Event: " . self::class . "::wrapPrecise()");
		}
		if(self::$lenTimings === null) {
			self::$lenTimings = new TimingsHandler("Plugin: Components Event: " . self::class . "::getPixelLength()");
		}
	}

	/**
	 * Wrap the message in a nice full-screen format, center text etc.
	 *
	 * @param $message
	 * @param bool $center
	 * @param bool $border
	 * @param bool $prefix
	 *
	 * @return array
	 */
	public static function wrapText($message, $center = false, $border = false, $prefix = false) {
		// Split into multiple lines
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN);
		$lines = explode("\n", $wrapped);
		$return = [];
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, self::MSG_LEN);
		}
		foreach($lines as $line) {
			$return[] = ($center) ? self::center($line) : ($prefix !== false ? $prefix : "") . $line;
		}
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, self::MSG_LEN);
		}
		return $return;
	}

	/**
	 * @param CommandSender $sender
	 * @param $message
	 * @param bool $center
	 * @param bool $border
	 */
	public static function sendWrappedText($sender, $message, $center = false, $border = false, $prefix = false) {
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = ($sender->getName() == "CONSOLE") ? self::wrapText($message, $center, $border, $prefix) : self::wrapPrecise($message, $center, $border, $prefix);
		$x = 0;
		foreach($wrapped as $line) {
			$sender->sendMessage($line);
			$x++;
		}
		unset($x);
	}

	/**
	 *
	 * @param CommandSender|Player $sender
	 * @param string $title
	 * @param array $list
	 */
	public static function sendList($sender, $title, $list, $page = 1) {
		$pages = ceil(count($list) / self::LIST_PER_PAGE);
		$newList = array_slice($list, ($page - 1) * self::LIST_PER_PAGE, self::LIST_PER_PAGE);
		$header = " [ " . $title . " | " . $page . "/" . $pages . " ] ";
		$num = self::MSG_LEN - strlen($header);
		$spacer = str_repeat(self::TITLE_CHAR, floor($num / 2));
		$sender->sendMessage($spacer . $header . $spacer . (str_repeat(self::TITLE_CHAR, ($num % 2))));
		if(count($newList) == 0) {
			$sender->sendMessage(self::center("No Results to display."));
		}
		foreach($newList as $value) {
			$sender->sendMessage("/> " . $value);
		}
		$sender->sendMessage(str_repeat(self::TITLE_CHAR, self::MSG_LEN));
	}

	public static function center($message, $len = self::MSG_LEN, $fillChar = self::SPACE_CHAR) {
		if(is_array($message)) {
			// Get longest line msg
			if($len == null) {
				$len = 0;
				foreach($message as $line) {
					$l = strlen(self::clean($line));
					$len = ($l > $len) ? $l : $len;
				}
			}
			$lines = [];
			foreach($message as $line) {
				$lines[] = ChatUtil::center($line, $len, $fillChar);
			}
			return $lines;
		}
		$message = trim($message);
		$stripped = self::clean($message);
		$padd = ($len - strlen($stripped)) / 2;
		$leftPadding = max(ceil($padd), 1);
		$rightPadding = max(floor($padd), 1);
		return str_repeat($fillChar, $leftPadding) . $message . str_repeat($fillChar, $rightPadding);
	}

	public static function wrapPrecise($message, $center = false, $border = false, $prefix = false) {
		if(self::$wrapTimings === null) {
			self::init();
		}
		self::$wrapTimings->startTiming();
		// Split into multiple lines
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN);
		$lines = explode("\n", $wrapped);
		$return = [];
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, ceil((self::MSG_LEN * self::CHAR_LENGTH) / self::getCharLength(self::BORDER_CHAR)));
		}
		foreach($lines as $line) {
			$return[] = ($center ? self::centerPrecise($line) : ($prefix !== false ? $prefix : "") . $line);
		}
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, ceil((self::MSG_LEN * self::CHAR_LENGTH) / self::getCharLength(self::BORDER_CHAR)));
		}
		self::$wrapTimings->stopTiming();
		return $return;
	}

	public static function centerPrecise($message, $len = self::MSG_LEN) {
		if(self::$centerTimings === null) {
			self::init();
		}
		self::$centerTimings->startTiming();
		if(is_array($message)) {
			// Get longest line msg
			if($len == null) {
				$len = 0;
				foreach($message as $line) {
					$l = (self::calculatePixelLength($line)) / self::CHAR_LENGTH;
					$len = ($l > $len) ? $l : $len;
				}
			}
			$lines = [];
			foreach($message as $line) {
				$lines[] = ChatUtil::centerPrecise($line, $len);
			}
			return $lines;
		}
		// \n
		if(strpos($message, "\n") > -1) {
			$arr = explode("\n", $message);
			return implode("\n", self::centerPrecise($arr, $len));
		}
		$message = trim($message);
		$messageLength = self::calculatePixelLength($message);
		$totalLength = $len * self::CHAR_LENGTH;
		$half = ($totalLength - $messageLength) / (2 * self::getCharLength(self::SPACE_CHAR));
		$prePadding = max(floor($half), 0);
		$postPadding = max(floor($half), 0);
		$newLine = ($prePadding > 0 ? str_repeat(self::SPACE_CHAR, $prePadding) : "") . $message;// . ($postPadding > 0 ? str_repeat(self::SPACE_CHAR, $postPadding) : "");
		self::$centerTimings->stopTiming();
		return $newLine;
	}

	public static function wrap($message, $prefix = "") {
		$len = ($prefix !== "") ? self::MSG_LEN - ceil(self::calculatePixelLength($prefix) / self::CHAR_LENGTH) - self::CHAR_LENGTH : self::MSG_LEN;
		$wrapped = $prefix . " " . wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN * 1.5, ($prefix !== "" ? "\n" . $prefix . " " : "\n"));
		return $wrapped;
	}

	public static function colour($string) {
		return preg_replace("/&([0123456789abcdefklmnor])/i", "§$1", $string);
	}

	public static function clean($string) {
		return preg_replace("/(?:&|§)([0123456789abcdefklmnor])/i", "", $string);
	}

	public static function rainbow($string) {
		$str = "";
		$col = ["4", "c", "6", "e", "a", "2", "b", "3", "1", "5", "d"];
		$string = str_replace("§", "^", $string);
		$chars = str_split($string);
		$i = 0;
		$skip = false;
		foreach($chars as $char) {
			if(ctype_alnum($char) && $char != "^" && $skip == false) {
				$str .= "§" . $col[$i];
				$i = ($i < (count($col) - 1) ? $i + 1 : 0);
			}
			$skip = false;
			if($char == "^") {
				$skip = true;
			}
			$str .= $char;
		}
		return str_replace("^", "§", $str);
	}

	public static function str_split_unicode($str, $l = 0) {
		if($l > 0) {
			$ret = [];
			$len = mb_strlen($str, "UTF-8");
			for($i = 0; $i < $len; $i += $l) {
				$ret[] = mb_substr($str, $i, $l, "UTF-8");
			}
			return $ret;
		}
		return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	private static function calculatePixelLength($string) {
		if(self::$lenTimings === null) {
			self::init();
		}
		self::$lenTimings->startTiming();
		$clean = self::clean($string);
		$parts = self::str_split_unicode($clean);
		$length = 0;
		foreach($parts as $part) {
			$length += self::getCharLength($part);
		}
		// +1 for each bold character
		// if has bold text
		preg_match_all("/(?:&|§)l(.+?)(?:[&|§]r|$)/", $string, $matches);
		if(isset($matches[1])) {
			foreach($matches[1] as $match) {
				$cl = trim(str_replace(" ", "", self::clean($match)));
				$cl = preg_replace("/[^\x20-\x7E]+/", "", $cl);
				$length += strlen($cl);
			}
		}
		self::$lenTimings->stopTiming();
		return $length;
	}

	private static function getCharLength($char) {
		return (isset(self::$charLengths[$char])) ? self::$charLengths[$char] : self::CHAR_LENGTH;
	}

}
