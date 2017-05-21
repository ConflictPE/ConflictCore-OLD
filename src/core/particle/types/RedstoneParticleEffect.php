<?php

/**
 * ConflictCore – RedstoneParticleEffect.php
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
 * Created on 14/5/17 at 1:44 AM
 *
 */

namespace core\particle\types;

use core\CorePlayer;
use core\particle\BaseParticle;
use pocketmine\level\particle\RedstoneParticle;

class RedstoneParticleEffect extends BaseParticle {

	/** @var array */
	private $extraData = [];

	public function subscribe(CorePlayer $player) {
		parent::subscribe($player);
		$this->extraData[$player->getName()] = 0;
	}

	public  function drawParticle(CorePlayer $player, int $currentTick, array $displayTo) {
		$pos = $player->getLocation();
		if($currentTick - $player->getLastMoveTick() >= 5) { // idle
			$n = $this->extraData[$player->getName()]++;
			$v = 2 * M_PI / 120 * ($n % 120);
			$i = 2 * M_PI / 70 * ($n % 70);
			$x = cos($i);
			$y = cos($v);
			$z = sin($i);

			$pos->getLevel()->addParticle(new RedstoneParticle($pos->add($x, 1 - $y, -$z)), $displayTo);
			$pos->getLevel()->addParticle(new RedstoneParticle($pos->add(-$x, 1 - $y, $z)), $displayTo);
		} else {
			if($this->extraData[$player->getName()] !== 0) {
				$this->extraData[$player->getName()] = 0;
			}
			$distance = -0.5 + lcg_value();
			$yaw = $pos->yaw * M_PI / 180;
			$x = $distance * cos($yaw);
			$z = $distance * sin($yaw);
			$y = lcg_value() * 0.4;
			$pos->getLevel()->addParticle(new RedstoneParticle($pos->add($x, $y, $z)), $displayTo);
		}
	}

}