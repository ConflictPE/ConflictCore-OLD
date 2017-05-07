<?php

/**
 * ConflictCore – NetworkScheduler.php
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
 * Created on 16/04/2017 at 12:09 AM
 *
 */

namespace core\database\network;

use core\Main;
use core\network\NetworkServer;
use pocketmine\scheduler\PluginTask;

class NetworkScheduler extends PluginTask {

	/**
	 * NetworkScheduler constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->setHandler($plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, $plugin->getSettings()->getNested("settings.network.sync-interval", 20)));
	}

	/**
	 * @param $tick
	 */
	public function onRun($tick) {
		/** @var Main $plugin */
		$plugin = $this->getOwner();
		$plugin->getDatabaseManager()->getNetworkDatabase()->sync();
	}

	/**
	 * Cancel the task
	 */
	public function cancel() {
		$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
	}

}