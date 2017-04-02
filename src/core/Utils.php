<?php

/**
 * ConflictCore – Utils.php
 *
 * Copyright (C) 2017 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 29/01/2017 at 4:46 PM
 *
 */

namespace core;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\WeakPosition;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\TextFormat;

class Utils {

	const PREFIX = TextFormat::BOLD . TextFormat::GOLD . "C" . TextFormat::GRAY . "PE" . TextFormat::RESET . TextFormat::YELLOW . "> " . TextFormat::RESET;

	/**
	 * Get a vector instance from a string
	 *
	 * @param string $string
	 * @return Vector3
	 */
	public static function parseVector(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		return new Vector3($data[0], $data[1], $data[2]);
	}

	/**
	 * Get a position instance from a string
	 *
	 * @param string $string
	 * @return Position|Vector3
	 */
	public static function parsePosition(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		$level = Server::getInstance()->getLevelByName($data[3]);
		if(!($level instanceof Level)) {
			$level = Server::getInstance()->getDefaultLevel();
		}
		return new WeakPosition($data[0], $data[1], $data[2], $level);
	}

	/**
	 * Apply minecraft color codes to a string from our custom ones
	 *
	 * @param string $string
	 * @param string $symbol
	 *
	 * @return mixed
	 */
	public static function translateColors($string, $symbol = "&") {
		$string = str_replace($symbol . "0", TF::BLACK, $string);
		$string = str_replace($symbol . "1", TF::DARK_BLUE, $string);
		$string = str_replace($symbol . "2", TF::DARK_GREEN, $string);
		$string = str_replace($symbol . "3", TF::DARK_AQUA, $string);
		$string = str_replace($symbol . "4", TF::DARK_RED, $string);
		$string = str_replace($symbol . "5", TF::DARK_PURPLE, $string);
		$string = str_replace($symbol . "6", TF::GOLD, $string);
		$string = str_replace($symbol . "7", TF::GRAY, $string);
		$string = str_replace($symbol . "8", TF::DARK_GRAY, $string);
		$string = str_replace($symbol . "9", TF::BLUE, $string);
		$string = str_replace($symbol . "a", TF::GREEN, $string);
		$string = str_replace($symbol . "b", TF::AQUA, $string);
		$string = str_replace($symbol . "c", TF::RED, $string);
		$string = str_replace($symbol . "d", TF::LIGHT_PURPLE, $string);
		$string = str_replace($symbol . "e", TF::YELLOW, $string);
		$string = str_replace($symbol . "f", TF::WHITE, $string);

		$string = str_replace($symbol . "k", TF::OBFUSCATED, $string);
		$string = str_replace($symbol . "l", TF::BOLD, $string);
		$string = str_replace($symbol . "m", TF::STRIKETHROUGH, $string);
		$string = str_replace($symbol . "n", TF::UNDERLINE, $string);
		$string = str_replace($symbol . "o", TF::ITALIC, $string);
		$string = str_replace($symbol . "r", TF::RESET, $string);

		return $string;
	}

	/**
	 * Removes all coloring and color codes from a string
	 *
	 * @param $string
	 * @return mixed
	 */
	public static function cleanString($string) {
		$string = self::translateColors($string);
		$string = TF::clean($string);
		return $string;
	}

	/**
	 * Replaces all in a string spaces with -
	 *
	 * @param $string
	 * @return mixed
	 */
	public static function stripSpaces($string) {
		return str_replace(" ", "_", $string);
	}

	/**
	 * Strip all white space in a string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function stripWhiteSpace(string $string) {
		$string = preg_replace('/\s+/', "", $string);
		$string = preg_replace('/=+/', '=', $string);
		return $string;
	}

	/**
	 * Center a line of text based around the length of another line
	 * 
	 * @param $toCentre
	 * @param $checkAgainst
	 *
	 * @return string
	 */
	public static function centerText($toCentre, $checkAgainst) {
		if(strlen($toCentre) >= strlen($checkAgainst)) {
			return $toCentre;
		}

		$times = floor((strlen($checkAgainst) - strlen($toCentre)) / 2);
		return str_repeat(" ", ($times > 0 ? $times : 0)) . $toCentre;
	}

	/**
	 * @param $time
	 *
	 * @return string
	 */
	public static function getTimeString($time) {
		if($time <= 0) {
			return "0 seconds";
		}
		$sec = floor($time / 20); // Convert to seconds
		$min = floor($sec / 60);
		if(($sec % 60) == 0) { // If it is exactly a multiple of 60
			$timeStr = $min . " minute" . ($min == 1 ? "" : "s");
		} else {
			// If more than 1 min but not exactly a minute
			$secLeft = $sec - ($min * 60);
			$timeStr = "";
			if($min > 0) {
				$timeStr = $min . " minute" . ($min == 1 ? "" : "s") . " ";
			}
			$timeStr .= $secLeft . " second" . ($secLeft == 1 ? "" : "s");
		}
		trim($timeStr);
		return $timeStr;

	}

	/**
	 * @param $uuid
	 *
	 * @return null|\pocketmine\Player
	 */
	public static function getPlayerByUUID($uuid) {
		$uuid = str_replace("-", "", strtolower($uuid));
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if(str_replace("-", "", strtolower($player->getUniqueId()->toString())) == $uuid) {
				return $player;
			}
		}
		return null;
	}

	/**
	 * Send a 'ghost' block to a player
	 *
	 * @param CorePlayer $player
	 * @param Vector3 $pos
	 * @param $id
	 * @param $damage
	 */
	public static function sendBlock(CorePlayer $player, Vector3 $pos, $id, $damage) {
		$pk = new UpdateBlockPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->blockId = $id;
		$pk->blockData = $damage;
		$pk->flags = UpdateBlockPacket::FLAG_PRIORITY;
		$player->dataPacket($pk);
	}

	/**
	 * Return the stack trace
	 *
	 * @param int $start
	 * @param null $trace
	 *
	 * @return array
	 */
	public static function getTrace($start = 1, $trace = null) {
		if($trace === null) {
			if(function_exists("xdebug_get_function_stack")) {
				$trace = array_reverse(xdebug_get_function_stack());
			} else {
				$e = new \Exception();
				$trace = $e->getTrace();
			}
		}
		$messages = [];
		$j = 0;
		for($i = (int)$start; isset($trace[$i]); ++$i, ++$j) {
			$params = "";
			if(isset($trace[$i]["args"]) or isset($trace[$i]["params"])) {
				if(isset($trace[$i]["args"])) {
					$args = $trace[$i]["args"];
				} else {
					$args = $trace[$i]["params"];
				}
				foreach($args as $name => $value) {
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? ($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}
		return $messages;
	}

	/**
	 * Uses SHA-512 [http://en.wikipedia.org/wiki/SHA-2] and Whirlpool [http://en.wikipedia.org/wiki/Whirlpool_(cryptography)]
	 *
	 * Both of them have an output of 512 bits. Even if one of them is broken in the future, you have to break both
	 * of them at the same time due to being hashed separately and then XORed to mix their results equally.
	 *
	 * @param string $salt
	 * @param string $password
	 *
	 * @return string[128] hex 512-bit hash
	 */
	public static function hash($salt, $password) {
		$salt = strtolower($salt); // temp fix for password in chat check :p
		return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
	}

}