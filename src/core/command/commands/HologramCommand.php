<?php

/**
 * ConflictCore – Main.php
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
 * Created on 17/03/2017 at 7:45 PM
 *
 */

namespace core\command\commands;

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\entity\text\FloatingText;
use core\Main;
use pocketmine\utils\TextFormat;

class HologramCommand extends CoreUserCommand {

	/** Sub command information */
	const SUB_COMMANDS = [
		"help" => "Get help with all the hologram commands",
		"variables" => "Get a list of all the available variables",
		"create <text>" => "Create a new hologram at your position",
		"id" => "Get the ID of a hologram",
		"edit <id> <text" => "Edit the text of a hologram",
		"delete <id>" => "Delete a hologram"
	];

	/** Text variable information */
	const VARIABLES = [
		"{players}" => "Count of all players online",
		"{max_players}" => "Max player count",
		"{level_players}" => "Count of all players in the same level as the text",
		"{level_name}" => "Name of the level the text is in",
		"{break}" => "Makes a new line (all text after the break will be on a new line)"
	];

	public function __construct(Main $plugin){
		parent::__construct($plugin, "hologram", "Main hologram command", "/hologram <help|new|id|edit|delete>", ["floatingtext", "ft", "holo"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if($player->hasPermission("hologram.command")) {
			switch(strtolower(array_shift($args))) {
				case "help":
				case "h":
					$player->sendMessage(TextFormat::GOLD . "-==-" . TextFormat::GREEN . " EasyHolograms Help " . TextFormat::GOLD . "-==-" . TextFormat::RESET);
					foreach(self::SUB_COMMANDS as $command => $description) {
						$player->sendMessage(TextFormat::GOLD . "/hologram " . $command . ": " . TextFormat::WHITE . $description . TextFormat::RESET);
					}
					break;
				case "variables":
				case "vars":
				case "codes":
					$player->sendMessage(TextFormat::YELLOW . "=+=+=" . TextFormat::GREEN . " EasyHolograms Variables " . TextFormat::YELLOW . "=+=+=" . TextFormat::RESET);
					foreach(self::VARIABLES as $variable => $description) {
						$player->sendMessage(TextFormat::GREEN . "{$variable} - " . TextFormat::WHITE . $description . TextFormat::RESET);
					}
					break;
				case "create":
				case "spawn":
				case "new":
					if(isset($args[0])) {
						$text = new FloatingText($player->getPosition(), implode(" ", $args));
						$this->getPlugin()->getFloatingTextManager()->addText($text);
						$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GREEN . "Hologram created!" . TextFormat::RESET);
						$this->getPlugin()->getFloatingTextManager()->saveText();
					} else {
						$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Usage: " . TextFormat::GOLD . "/create <text>" . TextFormat::RESET);
					}
					break;
				case "id":
				case "name":
					$player->setHologramIdSession();
					$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GREEN . "Tap a hologram to get it's ID!" . TextFormat::RESET);
					break;
				case "edit":
				case "change":
					if(isset($args[1])) {
						$id = (int) array_shift($args);
						$text = $this->getPlugin()->getFloatingTextManager()->getFloatingText();
						if(isset($text[$player->getLevel()->getName()][$id])) {
							$hologram = $text[$player->getLevel()->getName()][$id];
							if($hologram instanceof FloatingText) {
								$hologram->setText(implode(" ", $args));
								$hologram->doTextUpdate();
								$hologram->update();
								$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GREEN . "Edited hologram successfully!" . TextFormat::RESET);
								$this->getPlugin()->getFloatingTextManager()->saveText();
							} else {
								$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GOLD . "There was an issue editing the hologram, please restart the server and try again!" . TextFormat::RESET);
							}
						} else {
							$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Hologram doesn't exist!" . TextFormat::RESET);
						}
					} else {
						$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Usage: " . TextFormat::GOLD . "/edit <id> <text>" . TextFormat::RESET);
					}
					break;
				case "delete":
				case "remove":
					if(isset($args[0])) {
						$id = (int) array_shift($args);
						$text = $this->getPlugin()->getFloatingTextManager()->getFloatingText();
						if(isset($text[$player->getLevel()->getName()][$id])) {
							$hologram = $text[$player->getLevel()->getName()][$id];
							if($hologram instanceof FloatingText) {
								$hologram->despawnFromAll();
								$this->getPlugin()->getFloatingTextManager()->removeText($player->getLevel(), $id);
								$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GREEN . "Hologram has been deleted!" . TextFormat::RESET);
								$this->getPlugin()->getFloatingTextManager()->saveText();
							} else {
								$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::GOLD . "There was an issue deleting the hologram, please restart the server and try again!" . TextFormat::RESET);
							}
						} else {
							$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Hologram doesn't exist!" . TextFormat::RESET);
						}
					} else {
						$player->sendMessage(TextFormat::GOLD . "- " . TextFormat::RED . "Usage: " . TextFormat::GOLD . "/delete <id>" . TextFormat::RESET);
					}
					break;
			}
		} else {
			$player->sendMessage($this->getPermissionMessage());
		}
	}

}