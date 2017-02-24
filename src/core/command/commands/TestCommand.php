<?php

/**
 * ConflictCore – TestCommand.php
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

use core\command\CoreCommand;
use core\Main;
use core\task\ReportErrorTask;
use pocketmine\command\CommandSender;

class TestCommand extends CoreCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "test", "Command for testing stuff", "/test");
	}

	public function run(CommandSender $sender, array $args) {
		if(Main::$debug) {
			$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new ReportErrorTask("Test error log: \nCalled from {$this->getPlugin()->getServer()->getIp()}:{$this->getPlugin()->getServer()->getPort()}!"));
		} else {
			$sender->sendMessage($this->getPlugin()->getLanguageManager()->translate("TESTING_NOT_ENABLED"));
		}
	}

}