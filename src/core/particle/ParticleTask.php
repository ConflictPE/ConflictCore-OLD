<?php

/**
 * ConflictCore – ParticleTask.php
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
 * Created on 14/5/17 at 1:20 AM
 *
 */

namespace core\particle;

use core\Main;
use pocketmine\scheduler\PluginTask;

/**
 * Handle repeatable particle effects for players
 */
class ParticleTask extends PluginTask {

	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, 3);
	}

	/**
	 * Task start handling
	 *
	 * @param int $currentTick
	 */
	public function onRun($currentTick) {
		/** @var Main $plugin */
		$plugin = $this->getOwner();
		foreach($plugin->getParticleManager()->getAllParticleEffects() as $type => $effect) {
			$effect->tick($currentTick);
		}
	}

}
