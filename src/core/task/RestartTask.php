<?php

/**
 * ConflictCore – RestartTask.php
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

namespace core\task;

use core\CorePlayer;
use core\language\LanguageManager;
use core\Main;
use pocketmine\scheduler\PluginTask;

class RestartTask extends PluginTask {

	/** @var int */
	private $time = 3600;

	/**
	 * RestartTask constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->time = (int) $plugin->getSettings()->getNested("settings.restart-time");
		$this->setHandler($plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, 20));
	}

	public function onRun($tick) {
		if($this->time <= 10 and $this->time >= 1) {
			$this->getOwner()->getServer()->broadcastTip(LanguageManager::getInstance()->translate("SECONDS_UNTIL_RESTART", "en", [$this->time]));
		} elseif($this->time === 60) {
			/** @var CorePlayer $p */
			foreach($this->getOwner()->getServer()->getOnlinePlayers() as $p) {
				$p->sendTranslatedMessage("ONE_MINUTE_UNTIL_RESTART", [], true);
			}
		}  elseif($this->time <= 0) {
			/** @var CorePlayer $p */
			foreach($this->getOwner()->getServer()->getOnlinePlayers() as $p) {
				$p->kick(LanguageManager::getInstance()->translateForPlayer($p, "SERVER_RESTART"));
			}
			$this->getOwner()->getServer()->forceShutdown();
		}
		$this->time--;
	}

}