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

use pocketmine\scheduler\PluginTask;

class FloatingTextUpdateTask extends PluginTask {

	/** @var FloatingTextManager */
	private $manager;

	public function __construct(FloatingTextManager $manager) {
		parent::__construct($manager->getPlugin());
		$this->manager = $manager;
		$this->setHandler($this->getOwner()->getServer()->getScheduler()->scheduleRepeatingTask($this, 20 * 30));
	}

	/**
	 * @return FloatingTextManager
	 */
	public function getManager() {
		return $this->manager;
	}

	public function onRun($currentTick) {
		foreach($this->manager->getFloatingText() as $levels) {
			/** @var FloatingText $hologram */
			foreach($levels as $hologram) {
				$hologram->doTextUpdate();
				$hologram->update();
			}
		}
	}

}