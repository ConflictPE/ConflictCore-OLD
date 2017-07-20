<?php

/**
 * ConflictCore – UpdateRequestScheduler.php
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

namespace core\database\auth\mysql\task;

use core\CorePlayer;
use core\Main;
use pocketmine\scheduler\PluginTask;

class UpdateRequestScheduler extends PluginTask {

	/**
	 * UpdateRequestScheduler constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->setHandler($plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, 20 * 120));
	}

	/**
	 * @param $tick
	 */
	public function onRun($tick) {
		/** @var Main $plugin */
		$plugin = $this->getOwner();
		/** @var CorePlayer $p */
		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $p) {
			if($p->isAuthenticated()) {
				$plugin->getDatabaseManager()->getAuthDatabase()->update($p->getName(), $p->getAuthData());
			}
		}
	}

}