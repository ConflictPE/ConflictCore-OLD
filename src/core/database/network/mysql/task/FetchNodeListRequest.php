<?php

/**
 * ConflictCore – FetchNodeListRequest.php
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
 * Created on 7/5/2017 at 1:18 PM
 *
 */

namespace core\database\network\mysql\task;

use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\mysql\MySQLNetworkRequest;
use core\Main;
use core\network\NetworkNode;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

class FetchNodeListRequest extends MySQLNetworkRequest {

	public function __construct(MySQLNetworkDatabase $database) {
		parent::__construct($database->getCredentials());
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		$result = $mysqli->query("SELECT * FROM network_nodes WHERE max_servers > 0");
		if($result instanceof \mysqli_result) {
			$nodes = [];
			while($row = $result->fetch_assoc()) {
				$nodes[] = serialize(new NetworkNode($row["node_name"], $row["node_display"]));
			}
			$result->free();
			$this->setResult([self::SUCCESS, $nodes]);
			return;
		}
		$this->setResult([self::MYSQLI_ERROR, []]);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			switch((is_array($result) ? $result[0] : $result)) {
				case self::SUCCESS:
					$plugin->getNetworkManager()->setNodes(array_map("unserialize", $result[1]));
					$server->getLogger()->debug("Successfully completed FetchNodeListRequest!");
					return;
				case self::MYSQLI_ERROR:
					return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete FetchNodeListRequest while Components plugin isn't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}


}