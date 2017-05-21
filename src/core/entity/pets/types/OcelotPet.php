<?php

/**
 * ConflictCore – OcelotPet.php
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
 * Created on 11/5/17 at 1:19 PM
 *
 */

namespace core\entity\pets\types;

use core\entity\pets\BasePet;

class OcelotPet extends BasePet {

	const NETWORK_ID = 22;

	public $width = 0.72;
	public $height = 0.9;

	public function getName() {
		return "OcelotPet";
	}

	public function getPetName() : string {
		return "Ocelot";
	}

	public function getSpeed() : int {
		return 2.4;
	}

}
