<?php

/**
 * ConflictCore – WordList.php
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

class WordList {

	/** @var LanguageManager */
	private $manager;

	/** @var bool */
	protected $useLeet;

	public $foundMatch;

	public $wordListLeet = [];

	public $wordList = [];


	function __construct(LanguageManager $manager, array $files, $useLeet = true) {
		$this->manager = $manager;
		$this->useLeet = $useLeet;

		$path = $manager->getPath() . LanguageManager::FILTER_PATH;
		if(!is_dir($path)) @mkdir($path);
		foreach($files as $file) $this->addToList($file);

	}

	/**
	 * Add to the word list, given a csv file.
	 *
	 * @param $filename
	 */
	public function addToList($filename) {
		$wordsToAdd = $this->csvToArray($filename);

		$this->wordList = array_merge($this->wordList, $wordsToAdd);
		if($this->useLeet) {
			$wordsToAddLeet = $this->makeListLeet($wordsToAdd);
			$this->wordListLeet = array_merge($this->wordListLeet, $wordsToAddLeet);
		}
	}

	/**
	 * Given a word list, replace all the characters in it with regular expressions that search for leat.
	 * Do this in preparation for later leet searches.
	 * This utility may be used by other objects as well.
	 *
	 * @param $wordListIn
	 *
	 * @return array
	 */
	public function makeListLeet(array $wordListIn) {
		$returnWordList = [];

		$sepChars = '(\'|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\_|\+|\-|\=';   // Top row of a Qwerty keyboard, in order.
		$sepChars .= '|\{|\}|\||\[|\]|\\\\|\:|\"|\;|\'|\<|\>|\?|\,|\.|\/|\"';   // Right side of keyboard, working our way down
		$sepChars .= '|\~|\`|\´|\d'; // remaining two in the upper left, some special German apostrophy, and number 1. Any digit
		$sepChars .= ')*'; // Closing of regex group, and quantifier (zero to unlimited times)

		$leet_replace['a'] = '(a+|4|@|q)';
		$leet_replace['b'] = '(b+|8|ß|Β|β)';
		$leet_replace['c'] = '(c+)';
		$leet_replace['d'] = '(d+)';
		$leet_replace['e'] = '(e+|3)';
		$leet_replace['f'] = '(f+|ph)';
		$leet_replace['g'] = '(g+|6|9)';
		$leet_replace['h'] = '(h+)';
		$leet_replace['i'] = '(i+|1|\!)';
		$leet_replace['j'] = '(j+)';
		$leet_replace['k'] = '(k+)';
		$leet_replace['l'] = '(l+|1)';
		$leet_replace['m'] = '(m+|nn)';
		$leet_replace['n'] = '(n+)';
		$leet_replace['o'] = '(o+|0)';
		$leet_replace['p'] = '(p+)';
		$leet_replace['q'] = '(q+)';
		$leet_replace['r'] = '(r+|®)';
		$leet_replace['s'] = '(s+|5|z|\$)';
		$leet_replace['t'] = '(t+|7)';
		$leet_replace['u'] = '(u+|v)';
		$leet_replace['v'] = '(v+|u)';
		$leet_replace['w'] = '(w+)';
		$leet_replace['x'] = '(x+|\&|\>\<|\)\()';
		$leet_replace['y'] = '(y+)';
		$leet_replace['z'] = '(z+|s)';

		for($wordIndex = 0; $wordIndex < count($wordListIn); $wordIndex++) {
			$word = $wordListIn[$wordIndex];
			$wordReplacer = '';
			for($letterIndex = 0; $letterIndex < strlen($word); $letterIndex++) {
				$char = substr($word, $letterIndex, 1);
				if(array_key_exists($char, $leet_replace)) {
					$charReplacer = $leet_replace[$char];
					$wordReplacer .= $charReplacer . $sepChars;
				} else {
					$wordReplacer .= $char . $sepChars;
				}
			}
			$returnWordList[] = '/' . $wordReplacer . '/';
		}

		return $returnWordList;
	}

	/**
	 * See if the $inString contains leet version of any of the words on the object's list (which are bad words.)
	 * Will set this->rejectReason to a meaningful reason if it is.  Also sets $this->isProfane
	 *
	 * @param string $inString text to be tested.
	 *
	 * @return bool
	 */
	public function checkLeet($inString) {
		if(!$this->useLeet) return false;
		$matches = [];
		try {
			for($index = 0; $index < count($this->wordListLeet); $index++) {
				if(preg_match($this->wordListLeet[$index], $inString, $matches)) {
					return true;
				}
			}
		} catch(\Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Check to see if the input string contains one of the words.  Plain old match, not looking for leet.
	 *
	 * @param $inString
	 *
	 * @return bool
	 */
	function checkPlain($inString) {
		for($index = 0; $index < count($this->wordList); $index++) {
			$found = strstr($inString, $this->wordList[$index]);
			if($found != "") return true;
		}
		return false;
	}

	/**
	 * Given an input string, if any words (or phrases) on the wordList appear in the input string,
	 * replace them with a placeholder character: ‡
	 * This can be used for detecting harmless words.
	 * It will also check for the word plus and 's' on the end, because that is also almost certainly harmless.
	 * It only considers them harmless with a space on the left, and a space, period, question mark, or
	 * exclaimation point on the right.
	 * The placeholder prevents the filter from falsely catching this: "po happy op"
	 * Because of the place holder, this becomes: "po ‡ op" or after spaces removed, po‡op.  Not caught.
	 * Without the placeholder it would become "poop', which would be a false positive.
	 * Yes, that means people could use this character to trick us as a separator.  So keep it quiet!
	 *
	 * @param $inString
	 *
	 * @return mixed|string
	 */
	public function replaceFromList($inString) {
		$outString = " " . $inString . " ";
		$NSwapped = 0;
		for($index = 0; $index < count($this->wordList); $index++) {
			$count = 0;
			$matchPattern = '/ ' . $this->wordList[$index] . '[ .?!]/';
			$outString = preg_replace($matchPattern, " ‡ ", $outString, -1, $count);
			$matchPattern = '/ ' . $this->wordList[$index] . 's[ .?!]/';
			$outString = preg_replace($matchPattern, " ‡ ", $outString, -1, $count);
			$NSwapped += $count;
		}
		$outString = preg_replace('/( ‡(\s*‡\s*)+)/', ' ‡ ', $outString);

		$outString = trim($outString);

		return $outString;
	}

	public function dump() {
		echo("Entry #: Word => Leet Pattern" . PHP_EOL . PHP_EOL);
		for($index = 0; $index < count($this->wordList); $index++) {
			echo(PHP_EOL . "#" . $index . ": " . $this->wordList[$index] . " => " . $this->wordListLeet[$index] . PHP_EOL);
		}
	}

	/**
	 * Given a csv file, return an array of strings.
	 * Anything after a semicolon is a comment.
	 * Multiple entries may be made on a line, separated by comments.
	 * If a single entry has spaces between words they are preserved.
	 *
	 * @param $filename
	 *
	 * @return array
	 */
	public function csvToArray($filename) {
		$outputArray = [];

		$this->manager->getCore()->saveResource(LanguageManager::BASE_LANGUAGE_DIRECTORY.  LanguageManager::FILTER_PATH . $filename);

		$file = $this->manager->getPath() . LanguageManager::FILTER_PATH . $filename;

		if(!file_exists($file)) return $outputArray;

		$rows = file($file);
		foreach($rows as $row) {
			if($row[0] != ';') {
				$row = strtok($row, ';');
				$row = trim($row);
				$rowArray = explode(',', $row);
				$rowArray = array_filter($rowArray);
				$outputArray = array_merge($outputArray, $rowArray);
			}
		}
		$outputArray = array_map('trim', $outputArray);
		$outputArray = array_filter($outputArray);
		return $outputArray;

	}

}