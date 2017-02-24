<?php

/**
 * ConflictCore – CheckMessageTask.php
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

namespace core\task;

use core\CorePlayer;
use core\language\LanguageManager;
use core\Main;
use pocketmine\plugin\PluginException;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

/**
 * Check messages for blocked phrases and passwords in chat in an
 * async task so the main thread does less work ^-^
 */
class CheckMessageTask extends AsyncTask {

	/** @var string */
	protected $name;

	/** @var string */
	protected $hash;

	/** @var string */
	protected $lastMessage;

	/** @var int */
	protected $lastMessageTime;

	/** @var string */
	protected $message;

	/** @var bool */
	protected $isMuted;

	/* Results */
	const SUCCESS = "result.success";
	const MESSAGES_SIMILAR = "result.similar";
	const CHAT_COOLDOWN = "result.cooldown";
	const CONTAINS_PASSWORD = "result.contains.password";
	const CHAT_MUTED = "result.muted";

	public function __construct(string $name, string $hash, string $lastMessage, int $lastMessageTime, string $message, bool $isMuted) {
		$this->name = $name;
		$this->hash = $hash;
		$this->lastMessage = $lastMessage;
		$this->lastMessageTime = $lastMessageTime;
		$this->message = $message;
		$this->isMuted = $isMuted;
	}

	/**
	 * Check the message for all the things!
	 */
	public function onRun() {
		// chat filter, formatting, check for password in chat etc
		if(!$this->isMuted) {
			if(!LanguageManager::containsPassword($this->name, $this->message, $this->hash)) {
				if(floor(microtime(true) - $this->lastMessageTime) >= 3) {
					similar_text(strtolower($this->lastMessage), strtolower($this->message), $percent);
					if(round($percent) < 80) {
						$this->setResult(self::SUCCESS);
					} else {
						$this->setResult(self::MESSAGES_SIMILAR);
					}
				} else {
					$this->setResult(self::CHAT_COOLDOWN);
				}
			} else {
				$this->setResult(self::CONTAINS_PASSWORD);
			}
		} else {
			$this->setResult(self::CHAT_MUTED);
		}
	}

	public function onCompletion(Server $server) {
		$plugin = $server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main) {
			$player = $server->getPlayerExact($this->name);
			if($player instanceof CorePlayer) {
				$result = $this->getResult();
				switch($result) {
					case self::SUCCESS:
						$message = TextFormat::LIGHT_PURPLE . $this->name . TextFormat::GOLD . ": " . TextFormat::GRAY . TextFormat::clean($this->message);
						if($player->isSubscribedToStaffChat()) {
							/** @var CorePlayer $p */
							foreach($plugin->getServer()->getOnlinePlayers() as $p) {
								if($p->isSubscribedToStaffChat()) $p->sendMessage("§7[§6SC§7] §r§l{$player->getName()}§r§7:§r {$message}§r");
							}
						} else {
							foreach($server->getOnlinePlayers() as $p) {
								$p->sendMessage($message);
							}
						}
						$player->setLastMessage($this->message);
						return;
					case self::MESSAGES_SIMILAR:
						$player->sendTranslatedMessage("MESSAGES_TOO_SIMILAR", [], true);
						return;
					case self::CHAT_COOLDOWN:
						$player->sendTranslatedMessage("CHAT_COOLDOWN", [], true);
						return;
					case self::CONTAINS_PASSWORD:
						$player->sendTranslatedMessage("PASSWORD_IN_CHAT", [], true);
						return;
					case self::CHAT_MUTED:
						$player->sendTranslatedMessage("CANNOT_CHAT_WHILE_MUTED", [], true);
						return;
				}
			} else {
				$server->getLogger()->debug("Failed to complete CheckMessageTask due to user not being online! User: {$this->name}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete CheckMessageTask while Components plugin isn't enabled! User: {$this->name}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}