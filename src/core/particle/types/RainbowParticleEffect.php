<?php

/**
 * ConflictCore – RainbowParticleEffect.php
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
use core\Utils;
use core\particle\BaseParticle;
use pocketmine\level\particle\DustParticle;

class RainbowParticleEffect extends BaseParticle {

	/** @var array */
	private $extraData = [];

	public function subscribe(CorePlayer $player) {
		parent::subscribe($player);
		$this->extraData[$player->getName()] = 0;
	}

	public  function drawParticle(CorePlayer $player, int $currentTick, array $displayTo) {
		$pos = $player->getLocation();
		$n = $this->extraData[$player->getName()]++;
		Utils::hsv2rgb($n * 2, 100, 100, $r, $g, $b);

		if($currentTick - $player->getLastMoveTick() >= 5) { // idle
			$v = 2 * M_PI / 120 * ($n % 120);
			$i = 2 * M_PI / 60 * ($n % 60);
			$x = cos($i);
			$y = cos($v) * 0.5;
			$z = sin($i);

			$pos->getLevel()->addParticle(new DustParticle($pos->add($x, 2 - $y, -$z), $r, $g, $b), $displayTo);
			$pos->getLevel()->addParticle(new DustParticle($pos->add(-$x, 2 - $y, $z), $r, $g, $b), $displayTo);
		} else {
			for($i = 0; $i < 2; $i++) {
				$distance = -0.5 + lcg_value();
				$yaw = $pos->yaw * M_PI / 180 + (-0.5 + lcg_value()) * 90;
				$x = $distance * cos($yaw);
				$z = $distance * sin($yaw);
				$y = lcg_value() * 0.4 + 0.5;
				$pos->getLevel()->addParticle(new DustParticle($pos->add($x, $y, $z), $r, $g, $b), $displayTo);
			}
		}
	}

}