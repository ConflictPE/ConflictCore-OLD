<?php

/**
 * ConflictCore – RegisterRequest.php
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

class RegisterRequest extends MySQLAuthRequest {

	/** @var string */
	private $name;

	/** @var string */
	private $hash;

	/** @var string */
	private $email;

	/** States */
	const CONNECTION_FAILURE = "database.connection.failure";
	const REGISTER_SUCCESS = "registration.success";
	const REGISTER_FAILURE = "registration.failure";

	/**
	 * RegisterRequest constructor
	 *
	 * @param MySQLAuthDatabase $database
	 * @param string $name
	 * @param string $hash
	 * @param string $email
	 */
	public function __construct(MySQLAuthDatabase $database, string $name, string $hash, string $email) {
		parent::__construct($database->getCredentials());
		$this->name = strtolower($name);
		$this->hash = $hash;
		$this->email = strtolower($email);
	}

	/**
	 * Executes the registration request
	 */
	public function onRun() {
		$time = time();
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$stmt = $mysqli->stmt_init();
		$stmt->prepare("INSERT INTO auth (username, hash, email, registerdate, lastlogin) VALUES
			(?, ?, ?, ?, ?)");
		$stmt->bind_param("sssii", $mysqli->escape_string($this->name), $this->hash, $mysqli->escape_string($this->email), $time, $time);
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
						$server->getLogger()->debug("Failed to complete RegisterRequest for auth database due to a connection error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					case self::SUCCESS:
						$player->setRegistered(true);
						$player->setAuthenticated(true);
						$player->setLoginTime();
						/** @var CorePlayer $p */
						foreach($server->getOnlinePlayers() as $p) {
							$p->showPlayer($player);
						}
						$player->spawnKillAuraDetectors();
						$player->sendTranslatedMessage("REGISTER_SUCCESS", [], true);
						$server->getLogger()->debug("Successfully completed RegisterRequest for auth database! User: {$this->name}");
						return;
					case self::NO_CHANGE:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete RegisterRequest for auth database due the username not being registered! User: {$this->name}");
						return;
					case self::MYSQLI_ERROR:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete RegisterRequest for auth database due to a mysqli error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
				}
			} else {
				$server->getLogger()->debug("Failed to complete RegisterRequest for auth database due to user not being online! User: {$this->name}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete RegisterRequest for auth database while Components plugin isn't enabled! User: {$this->name}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}