<?php

/**
 * ConflictCore – PetCommand.php
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

namespace core\command\commands;

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\entity\pets\PetTypes;
use core\Main;

class PetCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "pet", "Manage your pet!", "/pet <enable|disable|change>", ["pets"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			switch(strtolower(array_shift($args))) {
				case "enable":
				case "on":
				case "activate":
					if(!$player->hasPet()) {
						$player->activatePet();
						$player->setHasPet(true);
						$player->sendMessage("Enabled pet!");
					} else {
						$player->sendMessage("Pet is already enabled!");
					}
					break;
				case "disable":
				case "off":
				case "deactivate":
					if($player->hasPet()) {
						$player->deactivatePet();
						$player->setHasPet(false);
						$player->sendMessage("Deactivated pet!");
					} else {
						$player->sendMessage("Pet isn't enabled!");
					}
					break;
				case "change":
				case "switch":
				case "type":
					if(isset($args[0])) {
						switch(str_replace(" ", "_", strtolower($args[0]))) {
							case "cow":
							case "baby_cow":
							case "calf":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_BABY_COW);
								$player->sendMessage("Activating Baby Cow pet!");
								break;
							case "pig":
							case "baby_pig":
							case "piglet":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_BABY_PIG);
								$player->sendMessage("Activating Baby Pig pet!");
								break;
							case "chicken":
							case "chook":
							case "chick":
							case "rooster":
							case "hen":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_CHICKEN);
								$player->sendMessage("Activating chicken pet!");
								break;
							case "ocelot":
							case "cat":
							case "kitten":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_OCELOT);
								$player->sendMessage("Activating Ocelot pet!");
								break;
							case "rabbit":
							case "bunny":
							case "bunny_rabbit":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_RABBIT);
								$player->sendMessage("Activating Rabbit pet!");
								break;
							case "wolf":
							case "dog":
							case "puppy":
							case "puppy_dog":
								$player->setLastUsedPetType(PetTypes::PET_TYPE_WOLF);
								$player->sendMessage("Activating Wolf pet!");
							default:
								$player->sendMessage("Unknown pet type!");
								return;
						}

						if($player->hasPet()) {
							$player->changePet();
						} else {
							$player->activatePet();
							$player->setHasPet(true);
						}
					} else {
						$player->sendMessage("Usage: /pet change <name>");
					}
					break;
			}
		} else {
			$player->sendMessage("Usage: " . $this->getUsage());
		}
	}

}