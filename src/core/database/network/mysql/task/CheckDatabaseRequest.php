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
 * Created on 01/05/2017 at 8:50 PM
 *
 */

namespace core\database\network\mysql\task;

use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\mysql\MySQLNetworkRequest;
use core\Main;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

class CheckDatabaseRequest extends MySQLNetworkRequest {

	public function __construct(MySQLNetworkDatabase $database) {
		parent::__construct($database->getCredentials());
	}

	/**
	 * Attempt to connect to the database
	 */
	public function onRun() {
		$mysqli = $this->getMysqli();
		if($this->checkConnection($mysqli)) return;
		$mysqli->query("
			CREATE TABLE IF NOT EXISTS network_nodes (
				id INT AUTO_INCREMENT PRIMARY KEY,
				node_name VARCHAR(64) NOT NULL,
				node_display VARCHAR(64) NOT NULL,
				max_servers INT DEFAULT 12
			);
			CREATE TABLE IF NOT EXISTS network_servers (
				id INT AUTO_INCREMENT PRIMARY KEY,
				server_motd VARCHAR(32) DEFAULT 'ConflictPE: Server',
				node_id INT NOT NULL,
				node VARCHAR(6) NOT NULL,
				address VARCHAR(45) DEFAULT '0.0.0.0',
				server_port INT DEFAULT 19132,
				online_players INT DEFAULT 0,
				max_players INT DEFAULT 100,
				player_list VARCHAR(50000) DEFAULT '[]',
				online BIT DEFAULT 0,
				last_sync INT DEFAULT 0
			);");
		$this->setResult(self::SUCCESS);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			switch((is_array($result) ? $result[0] : $result)) {
				case self::CONNECTION_ERROR:
					$server->getLogger()->debug("Failed to complete CheckDatabaseRequest for network database due to a connection error. Error: {$result[1]}");
					throw new \RuntimeException($result[1]);
				case self::SUCCESS:
					$server->getLogger()->debug("Successfully completed CheckDatabaseRequest for network database!");
					return;
				case self::MYSQLI_ERROR:
					throw new \RuntimeException($result[1]);
			}
		} else {
			$server->getLogger()->debug("Attempted to complete CheckDatabaseRequest for network database while Components plugin isn't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}