<?php

/**
 * ConflictCore – BabyCowPet.php
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
 * Created on 13/5/17 at 8:04 PM
 *
 */

namespace core\entity\pets\types;

use core\entity\pets\BasePet;
use pocketmine\entity\Entity;

class BabyCowPet extends BasePet {

	const NETWORK_ID = 11;

	public $width = 0.9;
	public $height = 1.3;

	public function getName() {
		return "BabyCowPet";
	}

	public function getPetName() : string {
		return "Cow";
	}

	public function getSpeed() : int {
		return 1.6;
	}

	public function initEntity() {
		parent::initEntity();
		//$this->setDataFlag(Entity::DATA_FLAG_BABY, Entity::DATA_TYPE_BYTE, 1);
		$this->setScale(0.7);
	}

}