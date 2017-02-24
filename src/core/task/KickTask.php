<?php

/**
 * ConflictCore – KickTask.php
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
use core\Main;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class KickTask extends PluginTask {

	/** @var CorePlayer */
	private $player;

	/** @var string */
	private $message;

	public function __construct(Main $plugin, Player $player, string $message){
		parent::__construct($plugin);
		$this->player = $player;
		$this->message = $message;
		$this->setHandler($player->getServer()->getScheduler()->scheduleDelayedTask($this, 40));
	}

	public function onRun($currentTick){
		$this->player->kick($this->message, false);
	}

}