<?php

/**
 * ConflictCore – DumpSkinCommand.php
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

class DumpSkinCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "dumpskin", "Dumps your current skin to a plugin readable format", "/dumpskin <name>", ["saveskin"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(Main::$debug) {
			$dir = $this->getPlugin()->getDataFolder() . "skin_dumps" . DIRECTORY_SEPARATOR;
			if(!is_dir($dir))
				mkdir($dir);
			$file = fopen($dir . strtolower(isset($args[0]) ? $args[0] : $player->getName() . date("d_m_Y_H:i:sA")) . ".skin", "w");
			fwrite($file, $player->getSkinData());
			fclose($file);
			$player->sendTranslatedMessage("SKIN_DUMPED", [], true);
		} else {
			$player->sendTranslatedMessage("TESTING_NOT_ENABLED", [], true);
		}
	}

}