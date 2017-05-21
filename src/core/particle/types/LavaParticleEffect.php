<?php

/**
 * ConflictCore – LavaParticleEffect.php
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
 * Created on 14/5/17 at 1:23 AM
 *
 */

namespace core\particle\types;

use core\CorePlayer;
use core\particle\BaseParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\LavaParticle;

class LavaParticleEffect extends BaseParticle {

	public function drawParticle(CorePlayer $player, int $currentTick, array $displayTo) {
		$pos = $player->getLocation();
		$pos->getLevel()->addParticle(new LavaParticle($pos->add(0, 1 + lcg_value(), 0)), $displayTo);
		if($currentTick - $player->getLastMoveTick() >= 5) {
			$distance = -0.5 + lcg_value();
			$yaw = $pos->yaw * M_PI / 180;
			$x = $distance * cos($yaw);
			$z = $distance * sin($yaw);
			$pos->getLevel()->addParticle(new LavaDripParticle($pos->add($x, 0.2, $z)), $displayTo);
		}
	}

}