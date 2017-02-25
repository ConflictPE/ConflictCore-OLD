<?php

/**
 * ConflictCore – PlayCommand.php
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
use pocketmine\network\protocol\TransferPacket;
use pocketmine\utils\TextFormat;

class PlayCommand extends CoreUserCommand {

	/** @var string */
	private $gamemode = "";

	/** @var string[] */
	private $servers = [];

	public function __construct(Main $plugin) {
		$this->gamemode = strtolower($plugin->getSettings()->getNested("settings.gamemode", "prison"));
		$this->servers = $plugin->getSettings()->getNested("settings.servers", ["factions" => "fac.conflictpe.net", "prison" => "psn.conflictpe.net"]);
		parent::__construct($plugin, "play", "Join another Conflict server!", "/play <gamemode>", ["transfer"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			$server = strtolower($args[0]);
			if(isset($this->servers[$server])) {
				if($server !== $this->gamemode) {
					$pk = new TransferPacket();
					$pk->address = $this->servers[$server];
					$player->dataPacket($pk);
				} else {
					$player->sendMessage(TextFormat::RED . "- " . TextFormat::GOLD . "You're already on the " . $this->gamemode . " server!");
				}
			} else {
				$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Sorry, we didn't recognise that server! Do /play to list all available servers.");
			}
		} else {
			$message = TextFormat::YELLOW . "=---= " . TextFormat::GOLD . TextFormat::BOLD . "Conflict Servers" . TextFormat::RESET . TextFormat::YELLOW . " =---=" . TextFormat::RESET;
			foreach($this->servers as $gamemode => $ip) {
				$message .= "\n"  . TextFormat::GREEN . "{$gamemode}: " . TextFormat::WHITE . $ip . TextFormat::RESET;
			}
			$player->sendMessage($message);
		}
	}

}