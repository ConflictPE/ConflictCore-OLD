<?php

/**
 * ConflictCore – TogglePlayersCommand.php
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
 * Created on 29/01/2017 at 4:46 PM
 *
 */

namespace core\command\commands;

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\Main;

class TogglePlayersCommand extends CoreUserCommand {

	public function __construct(Main $plugin){
		parent::__construct($plugin, "toggleplayers", "Allows you to toggle player visibility", "/toggleplayers <on|off>", ["togglep", "hideplayers"]);
	}

	public function onRun(CorePlayer $player, array $args){
		$visible = !$player->hasPlayersVisible();
		if(isset($args[0])){
			$visible = in_array(strtolower($args[0]), ["yes", "true", "visible". "on", "1"]);
		}
		$player->setPlayersVisible($visible);
		$player->sendTranslatedMessage("TOGGLED_PLAYERS", [], true);
		if(!$visible) $player->sendTranslatedMessage("TOGGLE_PLAYERS_WARNING", [], true);
		return true;
	}

}