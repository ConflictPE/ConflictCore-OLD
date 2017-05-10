<?php

/**
 * ConflictCore – UpdateBanRequest.php
 *
 * Copyright (C) 2017 Jack Noordhuis
 *
 * This is private software, you cannot redistribute it and/or modify any way
 * unless otherwise given permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 24/04/2017 at 1:40 PM
 *
 */

namespace core\database\ban\mysql\task;

use core\CorePlayer;
use core\database\ban\mysql\MySQLBanDatabase;
use core\database\ban\mysql\MySQLBanRequest;
use core\Main;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class UpdateBanRequest extends MySQLBanRequest {

	private $username;

	private $ip;

	private $cid;

	public function __construct(MySQLBanDatabase $database, string $username, string $ip, string $cid) {
		parent::__construct($database->getCredentials());
		$this->username = strtolower($username);
		$this->ip = $ip;
		$this->cid = $cid;
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$stmt = $mysqli->stmt_init();
		$stmt->prepare("UPDATE bans SET ip = ?, uid = ? WHERE username = ?");
		$stmt->bind_param("sss", $mysqli->escape_string($this->ip), $mysqli->escape_string($this->cid), $mysqli->escape_string($this->username));
		$stmt->execute();
		if($this->checkError($stmt)) return;
		if($this->checkAffectedRows($stmt)) return;
		$this->setResult(self::SUCCESS);
	}

	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$player = $server->getPlayerExact($this->username);
			if($player instanceof CorePlayer) {
				$result = $this->getResult();
				switch((is_array($result) ? $result[0] : $result)) {
					case self::CONNECTION_ERROR:
						$player->sendTranslatedMessage("DATABASE_CONNECTION_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdateBanRequest for bans table due to a connection error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					case self::SUCCESS:
						$plugin->getDatabaseManager()->getBanDatabase()->check($this->username, $this->ip, $this->cid, true);
						$server->getLogger()->debug("Successfully completed UpdateBanRequest for bans table! User: {$this->username}");
						return;
					case self::NO_CHANGE:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdateBanRequest for bans table due the username not being registered! User: {$this->username}");
						return;
					case self::MYSQLI_ERROR:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdateBanRequest for bans table due to a mysqli error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
				}
			} else {
				$server->getLogger()->debug("Failed to complete UpdateBanRequest for bans table due to user not being online! User: {$this->username}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete UpdateBanRequest for bans table while Components plugin isn't enabled! User: {$this->username}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}