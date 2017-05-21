<?php

/**
 * ConflictCore – BaseParticle.php
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
 * Created on 14/5/17 at 1:18 AM
 *
 */

namespace core\particle;

use core\CorePlayer;
use core\Utils;

abstract class BaseParticle {

	/** @var CorePlayer[] */
	private $subscribed = [];

	/**
	 * Subscribe a user to the particle
	 *
	 * @param CorePlayer $player
	 */
	public function subscribe(CorePlayer $player) {
		$this->subscribed[$player->getName()] = $player->getUniqueId()->toString();
	}

	/**
	 * Un-subscribe a user from the particle
	 *
	 * @param string $player
	 */
	public function unSubscribe($player) {
		if(isset($this->subscribed[$player])) {
			unset($this->subscribed[$player]);
		}
	}

	/**
	 * Tick the particle
	 *
	 * @param int $currentTick
	 */
	public function tick(int $currentTick) {
		foreach($this->subscribed as $name => $uuid) {
			$player = Utils::getPlayerByUUID($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$this->drawParticle($player, $currentTick, array_merge($player->getViewers(), [$player]));
			} else {
				unset($this->subscribed[$name]);
			}
		}
	}

	public abstract function drawParticle(CorePlayer $player, int $currentTick, array $displayTo);

}