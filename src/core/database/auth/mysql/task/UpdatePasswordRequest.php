<?php

/**
 * ConflictCore – UpdatePasswordRequest.php
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

namespace core\database\auth\mysql\task;

use core\CorePlayer;
use core\database\auth\mysql\MySQLAuthDatabase;
use core\database\auth\mysql\MySQLAuthRequest;
use core\Main;
use pocketmine\utils\PluginException;
use pocketmine\Server;

class UpdatePasswordRequest extends MySQLAuthRequest  {

	/** @var string */
	protected $name;

	/** @var string */
	protected $hash;

	public function __construct(MySQLAuthDatabase $database, string $name, string $hash) {
		parent::__construct($database->getCredentials());
		$this->name = strtolower($name);
		$this->hash = $hash;
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$stmt = $mysqli->stmt_init();
		$stmt->prepare("UPDATE auth SET hash = ? WHERE username = ?");
		$stmt->bind_param("ss", $this->hash, $this->name);
		$stmt->execute();
		if($this->checkError($stmt)) return;
		if($this->checkAffectedRows($stmt)) return;
		$this->setResult(self::SUCCESS);
	}

	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$player = $server->getPlayerExact($this->name);
			if($player instanceof CorePlayer) {
				$result = $this->getResult();
				switch((is_array($result) ? $result[0] : $result)) {
					case self::CONNECTION_ERROR:
						$player->sendTranslatedMessage("DATABASE_CONNECTION_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdatePasswordRequest for auth database due to a connection error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					case self::SUCCESS:
						$player->setHash($this->hash);
						$player->sendTranslatedMessage("PASSWORD_CHANGE_SUCCESS", [], true);
						$server->getLogger()->debug("Successfully completed UpdatePasswordRequest for auth database! User: {$this->name}");
						return;
					case self::NO_CHANGE:
						$player->sendTranslatedMessage("PASSWORD_CHANGE_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdatePasswordRequest for auth database due the username not being registered! User: {$this->name}");
						return;
					case self::MYSQLI_ERROR:
						$player->sendTranslatedMessage("PASSWORD_CHANGE_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UpdatePasswordRequest for auth database due to a mysqli error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
				}
			} else {
				$server->getLogger()->debug("Failed to complete UpdatePasswordRequest for auth database due to user not being online! User: {$this->name}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete UpdatePasswordRequest for auth database while Components plugin isn't enabled! User: {$this->name}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}