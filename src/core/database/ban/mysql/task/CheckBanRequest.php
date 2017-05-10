<?php

/**
 * ConflictCore – CheckBanRequest.php
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
 * Created on 23/03/2017 at 3:15 PM
 *
 */

namespace core\database\ban\mysql\task;

use core\CorePlayer;
use core\database\ban\mysql\MySQLBanDatabase;
use core\database\ban\mysql\MySQLBanRequest;
use core\Main;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class CheckBanRequest extends MySQLBanRequest {

	/** @var string */
	private $username;

	/** @var string */
	private $ip;

	/** @var string */
	private $cid;

	/** @var bool */
	private $doCallback;

	public function __construct(MySQLBanDatabase $database, string $username, string $ip, string $cid, bool $doCallback = false) {
		parent::__construct($database->getCredentials());
		$this->username = strtolower($username);
		$this->ip = $ip;
		$this->cid = $cid;
		$this->doCallback = $doCallback;
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$stmt = $mysqli->stmt_init();
		$stmt->prepare("SELECT * FROM bans WHERE username = ? OR ip = ? OR uid = ?");
		$stmt->bind_param("sss", $mysqli->escape_string($this->username), $mysqli->escape_string($this->ip), $mysqli->escape_string($this->cid));
		$stmt->execute();
		$result = $stmt->get_result();
		if($this->checkError($stmt)) return;
		$rows = [];
		while($row = $result->fetch_assoc()) $rows[] = $row;
		$result->free();
		if($this->checkResult($rows)) return;
		$this->setResult([self::SUCCESS, $rows]);
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
						$server->getLogger()->debug("Failed to complete CheckBanRequest for bans database due to a connection error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					case self::SUCCESS:
						$time = time();
						foreach($result[1] as $ban) {
							if($ban["valid"]) {
								if($ban["expires"] >= $time or $ban["expires"] == 0) {
									$player->setNetworkBanned(true);
									$player->setNetworkBanData($ban);
									break;
								} else {
									$player->setHasPreviousNetworkBan(true);
									$player->setPreviousNetworkBanData($ban);
								}
							}
						}
						if($this->doCallback) {
							$player->checkNetworkBan();
						}
						$server->getLogger()->debug("Successfully completed CheckBanRequest for bans database! User: {$this->username}");
						return;
					case self::NO_DATA:
						$server->getLogger()->debug("Successfully completed CheckBanRequest for bans database! User: {$this->username}");
						return;
					case self::NO_CHANGE:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete CheckBanRequest for bans database due the username not being registered! User: {$this->username}");
						return;
					case self::MYSQLI_ERROR:
						$player->sendTranslatedMessage("REGISTER_ERROR", [], true);
						$server->getLogger()->debug("Failed to complete CheckBanRequest for bans database due to a mysqli error. Error: {$result[1]}");
						throw new \RuntimeException($result[1]);
					default:
						$plugin->getLogger()->debug("Unhandled result type for CheckBanRequest! Result:" . $result[0]);
						var_dump($result);
						return;
				}
			} else {
				$server->getLogger()->debug("Failed to complete CheckBanRequest for bans database due to user not being online! User: {$this->username}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete CheckBanRequest for bans database while Components plugin isn't enabled! User: {$this->username}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}