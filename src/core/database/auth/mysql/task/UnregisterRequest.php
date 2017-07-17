<?php

/**
 * ConflictCore – UnregisterRequest.php
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

class UnregisterRequest extends MySQLAuthRequest {

	/** @var string */
	protected $name;

	/**
	 * UnregisterRequest constructor
	 *
	 * @param MySQLAuthDatabase $database
	 * @param string $name
	 */
	public function __construct(MySQLAuthDatabase $database, string $name) {
		parent::__construct($database->getCredentials());
		$this->name = strtolower($name);
	}

	/**
	 * Executes the unregister request
	 */
	public function onRun() {
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$stmt = $mysqli->stmt_init();
		$stmt->prepare("DELETE FROM auth WHERE username = ?");
		$stmt->bind_param("s", $mysqli->escape_string($this->name));
		$stmt->execute();
		if($this->checkError($stmt)) return;
		if($this->checkAffectedRows($stmt)) return;
		$this->setResult(self::SUCCESS);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$player = $server->getPlayerExact($this->name);
			if($player instanceof CorePlayer) {
				$result = $this->getResult();
				switch((is_array($result) ? $result[0] : $result)) {
					case self::CONNECTION_ERROR:
						$player->sendTranslatedMessage("DATABASE_CONNECTION_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UnregisterRequest for auth database due to a connection error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					case self::SUCCESS:
						$player->sendTranslatedMessage("UNREGISTER_SUCCESS", [], true);
						$server->getLogger()->debug("Successfully completed UnregisterRequest for auth database! User: {$this->name}");
						return;
					case self::NO_CHANGE:
						$player->sendTranslatedMessage("UNREGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UnregisterRequest for auth database due the username not being registered! User: {$this->name}");
						return;
					case self::MYSQLI_ERROR:
						$player->sendTranslatedMessage("UNREGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete UnregisterRequest for auth database due to a mysqli error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
				}
			} else {
				$server->getLogger()->debug("Failed to complete UnregisterRequest for auth database due to user not being online! User: {$this->name}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete UnregisterRequest for auth database while Components plugin isn't enabled! User: {$this->name}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}