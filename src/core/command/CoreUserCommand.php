<?php

/**
 * ConflictCore – CoreUserCommand.php
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

namespace core\command;

use core\CorePlayer;
use core\language\LanguageManager;
use pocketmine\command\CommandSender;

abstract class CoreUserCommand extends CoreCommand {

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function run(CommandSender $sender, array $args) {
		if($sender instanceof CorePlayer) {
			if($sender->isAuthenticated()) {
				return $this->onRun($sender, $args);
			} else {
				$sender->sendTranslatedMessage("MUST_AUTHENTICATE_FIRST");
			}
		} else {
			$sender->sendMessage(LanguageManager::getInstance()->translate("MUST_BE_PLAYER_FOR_COMMAND"));
		}
		return true;
	}

	/**
	 * Override this function to make the command do stuff
	 *
	 * @param CorePlayer $player
	 * @param array $args
	 *
	 * @return mixed
	 */
	public abstract function onRun(CorePlayer $player, array $args);

}