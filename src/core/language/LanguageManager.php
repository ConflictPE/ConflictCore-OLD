<?php

/**
 * ConflictCore – LanguageManager.php
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

namespace core\language;

use core\CorePlayer;
use core\Main;
use core\Utils;
use pocketmine\utils\Config;

class LanguageManager {

	/** @var LanguageManager */
	private static $instance;

	/** @var Main */
	private $plugin;

	/** @var string */
	private $path = "";

	/** @var array */
	public $messages = [];

	/** @var WordList */
	protected $profane;

	/** @var WordList */
	protected $dating;

	/** @var WordList */
	protected $advertising;

	/** @var WordList */
	protected $whitelist;

	/** @var array */
	protected $rawSepList = [];

	/** @var array */
	protected $sepList = [];

	/** @var array */
	public static $messageLangs = [
		"en" => "english.json",
		"es" => "spanish.json",
	];

	/** @var array */
	public static $profaneFiles = [
		"bad_english.csv",
		"bad_english.csv",
		"bad_english.csv"
	];

	/** @var array */
	public static $datingFiles = [
		"dating.csv"
	];

	/** @var array */
	public static $advertisingFiles = [
		"advertising.csv"
	];

	/** @var array */
	public static $whitelistedFiles = [
		"harmless.csv"
	];

	/** Language file directory */
	const BASE_LANGUAGE_DIRECTORY = "lang" . DIRECTORY_SEPARATOR;
	const MESSAGES_PATH = "messages" . DIRECTORY_SEPARATOR;
	const FILTER_PATH = "filter" . DIRECTORY_SEPARATOR;

	/** Blocked message types */
	const TYPE_GENERAL = "BLOCKED_MESSAGE";
	const TYPE_PROFANE = "BLOCKED_MESSAGE_PROFANE";
	const TYPE_DATING = "BLOCKED_MESSAGE_DATING";

	public function __construct(Main $plugin) {
		self::$instance = $this;
		$this->plugin = $plugin;

		$this->path = $plugin->getDataFolder() . self::BASE_LANGUAGE_DIRECTORY;
		if(!is_dir($this->path)) @mkdir($this->path);

		$this->loadMessages();

		$this->generateSepChars();

		$this->profane = new WordList($this, self::$profaneFiles);

		$this->dating = new WordList($this, self::$datingFiles);

		$this->advertising = new WordList($this, self::$advertisingFiles);

		$this->whitelist = new WordList($this, self::$whitelistedFiles, false);
	}

	/**
	 * @return LanguageManager
	 */
	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->plugin;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Load all languages and their messages into an array
	 */
	private function loadMessages() {
		$path = $this->path . self::MESSAGES_PATH;
		if(!is_dir($path)) @mkdir($path);
		foreach(self::$messageLangs as $lang => $filename) {
			$this->plugin->saveResource(self::BASE_LANGUAGE_DIRECTORY . self::MESSAGES_PATH . $filename);
			$file = $path . $filename;
			$this->registerLanguage($lang, (new Config($file, Config::JSON))->getAll());
		}
	}

	/**
	 * Load a language into the existing ones
	 * 
	 * @param $lang
	 * @param $data
	 */
	public function registerLanguage($lang, $data) {
		foreach($data as $key => $message) {
			if(is_array(($message))) {
				$string = "";
				foreach($message as $parts) {
					$string .= "{$parts}";
				}
				$this->registerMessage($lang, $key, Utils::translateColors($string));
			} else {
				$this->registerMessage($lang, $key, Utils::translateColors($message));
			}
		}
	}

	/**
	 * Register a message into an existing language
	 * 
	 * @param $lang
	 * @param $key
	 * @param $message
	 */
	public function registerMessage($lang, $key, $message) {
		$this->messages[$lang][$key] = Utils::translateColors($message);
	}

	/**
	 * @param CorePlayer $player
	 * @param $key
	 * @param array $args
	 *
	 * @return mixed|null
	 */
	public function translateForPlayer(CorePlayer $player, $key, $args = []) {
		return $this->translate($key, $player->getLanguageAbbreviation(), $args);
	}

	/**
	 * @param $key
	 * @param string $lang
	 * @param array $args
	 *
	 * @return mixed|null
	 */
	public function translate($key, $lang = "en", $args = []) {
		if(!$this->isLanguage($lang)) {
			$lang = "en";
		}
		if(isset($this->messages[$lang][$key])) {
			return self::argumentsToString($this->messages[$lang][$key], $args);
		}
		$this->plugin->getLogger()->debug("Couldn't find message key '{$key}' for language '{$lang}'");
		return "";
	}

	/**
	 * @param $string
	 *
	 * @return bool
	 */
	public function isLanguage($string) {
		return isset(self::$messageLangs[$string]);
	}

	/**
	 * Check a string against the chat filter
	 *
	 * @param $message
	 *
	 * @return bool
	 */
	public function check($message) {
		$message = " " . $message . " ";

		if(($type = $this->checkRaw($message)) !== "") return $type;

		$message = strtolower($message);

		$message = $this->whitelist->replaceFromList($message);

		if(($type = $this->detectSpecialWords($message)) !== "") return $type;

		$message = Utils::stripWhiteSpace($this->cleanMessage($message));

		if($this->profane->checkLeet($message)) return self::TYPE_PROFANE;

		if($this->dating->checkLeet($message)) return self::TYPE_DATING;

		return false;
	}

	protected function checkRaw($string) {
		$list = [
			"fu" => [
				"pattern" => '/' . $this->rawSepList . 'f' . $this->sepList . 'u/i',
				"type" => self::TYPE_PROFANE
			],
			"ef you" => [
				"pattern" => '/' . $this->rawSepList . 'ef+' . $this->sepList . 'you/i',
				"type" => self::TYPE_PROFANE
			],
			"bch" => [
				"pattern" => '/ bch /i',
				"type" => self::TYPE_PROFANE
			],
			"s=x" => [
				"pattern" => '/s=x/i',
				"type" => self::TYPE_PROFANE
			],
			"se*" => [
				"pattern" => '/se\*/i',
				"type" => self::TYPE_PROFANE
			],
			"s/x" => [
				"pattern" => '/s[[:punct:]]x/i',
				"type" => self::TYPE_PROFANE
			],
			"b*" => [
				"pattern" => '/ b(\*|=) /',
				"type" => self::TYPE_DATING
			],
			"gir/" => [
				"pattern" => '/ gir(\*|\\\|\/) /',
				"type" => self::TYPE_DATING
			],
			"g*" => [
				"pattern" => '/ g(\*|=) /',
				"type" => self::TYPE_DATING
			],
			"ag.friend" => [
				"pattern" => '/ a(b|g)[[:punct:]]friend/',
				"type" => self::TYPE_DATING
			],
			"ag.f" => [
				"pattern" => '/ a(b|g)[[:punct:]]f /',
				"type" => self::TYPE_DATING
			],
			"8==D" => [
				"pattern" => '/8=+(D|>)/i',
				"type" => self::TYPE_PROFANE
			],
			".|." => [
				"pattern" => '/\.\|\./i',
				"type" => self::TYPE_PROFANE
			],
			"(.)(.)" => [
				"pattern" => '/\(\.\)\s*\(\.\)/',
				"type" => self::TYPE_PROFANE
			]
		];

		foreach($list as $key => $pattern) {
			if(preg_match($pattern["pattern"], $string, $matches)) {
				if(Main::$debug) {
					echo "<----------- RAW MESSAGE CHECK ----------->" . PHP_EOL;
					echo "       TYPE: {$pattern["type"]}" . PHP_EOL;
				}
				return $pattern["type"];
			}
		}

		return "";
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function detectSpecialWords(string $string) {
		$string = " " . $string . " ";

		$list = [
			"eff u" => [
				"pattern" => '/ ef+ u/',
				"type" => self::TYPE_PROFANE
			],
			"tit" => [
				"pattern" => '/ tit+ /',
				"type" => self::TYPE_PROFANE
			],
			"tits" => [
				"pattern" => '/ tit+s/',
				"type" => self::TYPE_PROFANE
			],
			"t i t" => [
				"pattern" => '/ t i t /',
				"type" => self::TYPE_PROFANE
			],
			"t it" => [
				"pattern" => '/ t it /',
				"type" => self::TYPE_PROFANE
			],
			"ass" => [
				"pattern" => '/ ass /',
				"type" => self::TYPE_PROFANE
			],
			" sx" => [
				"pattern" => '/ sx/',
				"type" => self::TYPE_PROFANE
			],
			"a s s" => [
				"pattern" => '/ a s s/',
				"type" => self::TYPE_PROFANE
			],
			" g|b f " => [
				"pattern" => '/' . $this->rawSepList . '(g+|b+)' . $this->sepList . 'f/',
				"type" => self::TYPE_DATING
			],
			" s*x" => [
				"pattern" => '/' . $this->rawSepList . '(s+)' . $this->rawSepList . 'x+/',
				"type" => self::TYPE_DATING
			],
			" u r hot" => [
				"pattern" => '/' . $this->rawSepList . '(u+)' . $this->rawSepList . 'r+' . $this->rawSepList . 'hot/',
				"type" => self::TYPE_DATING
			],
			" u r so hot" => [
				"pattern" => '/' . $this->rawSepList . '(u+)' . $this->rawSepList . 'r+' . $this->rawSepList . 'so' . $this->rawSepList . 'hot/',
				"type" => self::TYPE_DATING
			],
			"dotnet" => [
				"pattern" => '/\.net/',
				"type" => self::TYPE_GENERAL
			],
			"dotcom" => [
				"pattern" => '/\.com/',
				"type" => self::TYPE_GENERAL
			],
			"dotme" => [
				"pattern" => '/\.me/',
				"type" => self::TYPE_GENERAL
			],
		];

		foreach($list as $key => $pattern) {
			if(preg_match($pattern["pattern"], $string, $matches)) {
				if(Main::$debug) {
					echo "<----------- Special MESSAGE CHECK ----------->" . PHP_EOL;
					echo "        TYPE: {$pattern["type"]}" . PHP_EOL;
				}
				return $pattern["type"];
			}
		}

		return "";
	}

	/**
	 * Change special upper and lower case characters to their normal letters.
	 * Only characters that are 99% likely to be used in place of their normal character.
	 *
	 * @param string $message
	 *
	 * @return mixed|string
	 */
	public function cleanMessage(string $message) {
		$output = $message;
		$output = preg_replace("/A|Á|á|À|Â|à|Â|â|Ä|ä|Ã|ã|Å|å|α|Δ|Λ|λ/", "a", $output);
		$output = preg_replace("/Β/", "b", $output);
		$output = preg_replace("/C|Ç|ç|¢|©/", "c", $output);
		$output = preg_replace("/D|Þ|þ|Ð|ð/", "d", $output);
		$output = preg_replace("/E|€|È|è|É|é|Ê|ê|∑|£|€/", "e", $output);
		$output = preg_replace("/F|ƒ/", "f", $output);
		$output = preg_replace("/G/", "g", $output);
		$output = preg_replace("/H/", "h", $output);
		$output = preg_replace("/I|Ì|Í|Î|Ï|ì|í|î|ï/", "i", $output);
		$output = preg_replace("/J/", "j", $output);
		$output = preg_replace("/Κ|κ/", "k", $output);
		$output = preg_replace("/L|£/", "l", $output);
		$output = preg_replace("/M/", "m", $output);
		$output = preg_replace("/N|η|ñ|Ν|Π/", "n", $output);
		$output = preg_replace("/O|Ο|○|ο|Φ|¤|°|ø|ö|ó/", "o", $output);
		$output = preg_replace("/P|ρ|Ρ|¶|þ/", "p", $output);
		$output = preg_replace("/Q/", "q", $output);
		$output = preg_replace("/R|®/", "r", $output);
		$output = preg_replace("/S/", "s", $output);
		$output = preg_replace("/Τ|τ/", "t", $output);
		$output = preg_replace("/U|υ|µ/", "u", $output);
		$output = preg_replace("/V|ν/", "v", $output);
		$output = preg_replace("/W|ω|ψ|Ψ/", "w", $output);
		$output = preg_replace("/Χ|χ|×/", "x", $output);
		$output = preg_replace("/Y|¥|γ|ÿ|ý|Ÿ|Ý/", "y", $output);
		$output = preg_replace("/Z/", "z", $output);
		return $output;
	}

	public function generateSepChars() {
		$this->rawSepList = '(\'|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\_|\+|\-|\=| '; // Top row of a Qwerty keyboard, in order, AND SPACE
		$this->rawSepList .= '|\{|\}|\||\[|\]|\\\\|\:|\"|\;|\'|\<|\>|\?|\,|\.|\/|\"'; // Right side of keyboard, working our way down
		$this->rawSepList .= '|\~|\`|\´|\d'; // Remaining two in the upper left, and the number one.
		$this->rawSepList .= ')+'; // Closing of regex group, and quantifier (zero to unlimited times)
		$this->sepList = rtrim($this->rawSepList, "+")."*";
	}

	/**
	 * Check a message for a players hash
	 *
	 * @param string $name
	 * @param string $message
	 * @param string $hash
	 * @return bool
	 */
	public static function containsPassword(string $name, string $message, string $hash) {
		$parts = explode(" ", $message);
		foreach($parts as $part) {
			if(hash_equals($hash, Utils::hash($name, $part))) return true;
		}
		return false;
	}

	/**
	 * @param string $string
	 * @param array $args
	 *
	 * @return string
	 */
	public static function argumentsToString($string, $args = []) {
		foreach($args as $key => $data) {
			$string = str_replace("{args" . (string)((int)$key + 1) . "}", $data, $string);
		}
		return $string;
	}

}