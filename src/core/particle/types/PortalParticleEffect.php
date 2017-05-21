<?php

/**
 * ConflictCore – PortalParticleEffect.php
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
 * Created on 14/5/17 at 1:42 AM
 *
 */

namespace core\particle\types;

use core\CorePlayer;
use core\particle\BaseParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\PortalParticle;

class PortalParticleEffect extends BaseParticle {

	public function drawParticle(CorePlayer $player, int $currentTick, array $displayTo) {
		$pos = $player->getLocation();
		$pos->getLevel()->addParticle(new DustParticle($pos->add(-0.5 + lcg_value(), 1.5 + lcg_value() / 2, -0.5 + lcg_value()), 255, 0, 255), $displayTo);
		$pos->getLevel()->addParticle(new DustParticle($pos->add(-0.5 + lcg_value(), 1.5 + lcg_value() / 2, -0.5 + lcg_value()), 255, 0, 255), $displayTo);
		$pos->getLevel()->addParticle(new PortalParticle($pos->add(-0.5 + lcg_value(), 0.5 + lcg_value(), -0.5 + lcg_value())), $displayTo);
		$pos->getLevel()->addParticle(new PortalParticle($pos->add(-0.5 + lcg_value(), 0.5 + lcg_value(), -0.5 + lcg_value())), $displayTo);
	}

}