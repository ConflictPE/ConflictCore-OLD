<?php

/**
 * ConflictCore – Main.php
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
 * Created on 17/03/2017 at 7:45 PM
 *
 */

namespace core\entity\text;

use core\CorePlayer;
use core\Main;
use pocketmine\level\Level;
use pocketmine\scheduler\FileWriteTask;

class FloatingTextManager {

	/** @var Main */
	private $plugin;

	/** @var bool */
	public $hasLoadedText = false;

	/** @var array */
	private $floatingText = [];

	/** @var string */
	private $floatingTextDataPath = "";

	/** @var null */
	private $updateTask = null;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		if(!is_dir($this->plugin->getDataFolder() . "data")) @mkdir($this->plugin->getDataFolder() . "data");
		$this->floatingTextDataPath = $plugin->getDataFolder() . Main::FLOATING_TEXT_DATA_FILE;
		$this->plugin->saveResource(Main::FLOATING_TEXT_DATA_FILE);
		$this->updateTask = new FloatingTextUpdateTask($this);
	}

	public function getPlugin() : Main {
		return $this->plugin;
	}

	public function getFloatingText() : array {
		return $this->floatingText;
	}

	public function addText(FloatingText $text) {
		$this->floatingText[$text->getPosition()->getLevel()->getName()][$text->getId()] = $text;
	}

	public function getText(Level $level, $id) {
		return $this->floatingText[$level->getName()][$id];
	}

	public function removeText(Level $level, $id) {
		unset($this->floatingText[$level->getName()][$id]);
	}

	public function loadText() {
		if(!$this->hasLoadedText) {
			$this->hasLoadedText = true;
			foreach(json_decode(file_get_contents($this->floatingTextDataPath), true) as $data) {
				$text = FloatingText::fromSaveData($data);
				$levelName = $players = $text->getPosition()->getLevel()->getName();
				if(!isset($this->floatingText[$levelName]))
					$this->floatingText[$levelName] = [];
				$this->floatingText[$levelName][$text->getId()] = $text;
			}
		}
	}

	public function saveText($async = true) {
		$data = [];
		foreach($this->floatingText as $levels) {
			/** @var FloatingText $text */
			foreach($levels as $text) {
				$data[] = $text->getSaveData();
			}
		}
		if(!empty($data)) {
			$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			if($async) {
				$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new FileWriteTask($this->floatingTextDataPath, $data));
			} else {
				file_put_contents($this->floatingTextDataPath, $data);
			}
		}
	}

	/**
	 * Spawn all text in the players level to them
	 *
	 * @param CorePlayer $player
	 */
	public function onJoin(CorePlayer $player) {
		/** @var FloatingText $text */
		foreach($this->floatingText[$player->getLevel()->getName()] as $text) {
			$text->spawnTo($player);
		}
	}

	/**
	 * Despawn all text from a player
	 *
	 * @param CorePlayer $player
	 */
	public function onQuit(CorePlayer $player) {
		/** @var FloatingText $text */
		foreach($this->floatingText[$player->getLevel()->getName()] as $text) {
			$text->despawnFrom($player);
		}
	}

	/**
	 * Despawn all text from the players old level and spawn the text from the new level
	 *
	 * @param CorePlayer $player
	 * @param Level $oldLevel
	 * @param Level $targetLevel
	 */
	public function onLevelChange(CorePlayer $player, Level $oldLevel, Level $targetLevel) {
		/** @var FloatingText $oldText */
		foreach($this->floatingText[$oldLevel->getName()] as $oldText) {
			$oldText->despawnFrom($player);
		}
		/** @var FloatingText $newText */
		foreach($this->floatingText[$targetLevel->getName()] as $newText) {
			$newText->spawnTo($player);
		}
	}

	/**
	 * Add all loaded levels names into the store to prevent errors
	 *
	 * @param Level $level
	 */
	public function onLevelLoad(Level $level) {
		$this->loadText();
		if(!isset($this->floatingText[$level->getName()])) {
			$this->floatingText[$level->getName()] = [];
		}
	}

}