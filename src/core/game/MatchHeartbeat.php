<?php

/**
 * ConflictCore – MatchHeartbeat.php
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

namespace core\game;

use pocketmine\scheduler\PluginTask;

class MatchHeartbeat extends PluginTask {

	/** @var MatchManager */
	private $manager;

	public function __construct(MatchManager $manager) {
		$this->manager = $manager;
		parent::__construct($manager->getPlugin());
	}

	/**
	 * @return MatchManager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * Ticks all the match manager
	 *
	 * @param $currentTick
	 */
	public function onRun($currentTick) {
		$this->manager->tick($currentTick);
	}

}