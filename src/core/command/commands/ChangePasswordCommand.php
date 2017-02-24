<?php

/**
 * ConflictCore – ChangePasswordCommand.php
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
use core\Utils;

class ChangePasswordCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "changepassword", "Change your accounts password", "/changepassword <password>", ["chgpassword", "chgpword", "chgpass", "changepword", "changepass"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			$pass = Utils::hash($player->getName(), implode(" ", $args));
			$this->getPlugin()->getDatabaseManager()->getAuthDatabase()->changePassword($player->getName(), $pass);
		} else {
			$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
		}
	}

}