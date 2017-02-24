<?php

/**
 * ConflictCore – StaffChatCommand.php
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

class StaffChatCommand extends CoreUserCommand {

	public function __construct(Main $plugin){
		parent::__construct($plugin, "staffchat", "Allows you to join/leave the staff chat", "/staffchat <join|leave>", ["sc"]);
	}

	public function onRun(CorePlayer $player, array $args){
		if($player->hasPermission("staffchat.toggle")){
			$join = !$player->isSubscribedToStaffChat();
			if(isset($args[0])){
				$join = in_array(strtolower($args[0]), ["join", "yes", "true", "listen"]);
			}
			$player->subscribeToStaffChat($join);
			$player->sendTranslatedMessage("TOGGLE_STAFF_CHAT", [($join ? "joined" : "left")], true);
			if($join) $player->sendTranslatedMessage("TOGGLE_STAFF_CHAT_WARNING", [], true);
		} else {
			$player->sendMessage($this->getPermissionMessage());
		}
		return true;
	}

}