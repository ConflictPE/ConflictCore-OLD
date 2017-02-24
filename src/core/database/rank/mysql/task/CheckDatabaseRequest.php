<?php

/**
 * ConflictCore – CheckDatabaseRequest.php
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

namespace core\database\rank\mysql\task;

use core\database\mysql\MySQLRequest;
use core\database\rank\mysql\MySQLRankDatabase;
use core\Main;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

/**
 * Check to make sure the Auth database is online and working
 */
class CheckDatabaseRequest extends MySQLRequest {

	/** Error states */
	const CONNECTION_FAILURE ="mysqlrequest.connection.failure";
	const MYSQLI_ERROR ="mysqlrequest.table.creation.error";

	public function __construct(MySQLRankDatabase $database) {
		parent::__construct($database->getCredentials());
	}

	/**
	 * Attempt to connect to the database
	 */
	public function onRun() {
		$mysqli = $this->getMysqli();
		if($mysqli->connect_error) {
			$this->setResult([self::CONNECTION_FAILURE, $mysqli->connect_error]);
			return;
		}
		$mysqli->query("CREATE TABLE IF NOT EXISTS ranks (
			username VARCHAR(64) PRIMARY KEY,
			ranks VARCHAR(512) DEFAULT '{}'
			)");
		if(isset($mysqli->error) and $mysqli->error) {
			$mysqli->close();
			$this->setResult([self::MYSQLI_ERROR, $mysqli->error]);
			return;
		}
		$mysqli->close();
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			switch($result[0]) {
				default:
					$server->getLogger()->debug("Successfully completed CheckDatabaseRequest for ranks database!");
					return;
				case self::CONNECTION_FAILURE:
					$server->getLogger()->debug("Failed to complete CheckDatabaseRequest for ranks database due to a connection error");
					throw new \RuntimeException($result[1]);
				case self::MYSQLI_ERROR:
					$server->getLogger()->debug("Failed to complete CheckDatabaseRequest for ranks database due to a mysqli error");
					throw new \RuntimeException($result[1]);
			}
		} else {
			$server->getLogger()->debug("Attempted to complete CheckDatabaseRequest for ranks database while Components plugin ins't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}