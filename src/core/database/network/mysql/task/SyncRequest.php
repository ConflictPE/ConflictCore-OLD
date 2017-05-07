<?php

/**
 * ConflictCore – SyncRequest.php
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
 * Created on 16/04/2017 at 12:09 AM
 *
 */

namespace core\database\network\mysql\task;

use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\mysql\MySQLNetworkRequest;
use core\network\NetworkServer;
use core\Main;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

class SyncRequest extends MySQLNetworkRequest {

	/** @var NetworkServer */
	private $server;

	public function __construct(MySQLNetworkDatabase $database, NetworkServer $server) {
		parent::__construct($database->getCredentials());
		$this->server = serialize($server);
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		$server = unserialize($this->server);
		if(!$this->updateServer($mysqli, $server)) {
			$this->setResult(self::CONNECTION_ERROR);
			return;
		}

		$this->setResult([self::SUCCESS, $this->fetchServers($mysqli, $server)]);
	}

	public function updateServer(\mysqli $mysqli, NetworkServer $server) {
		try {
			$result = $mysqli->query("SELECT * FROM network_servers WHERE node = '{$server->getNode()}' AND node_id = {$server->getId()}");
			if($result instanceof \mysqli_result) {
				$data = $result->fetch_assoc();
				$result->free();
				if(isset($data["node"]) and isset($data["node_id"]) and (string) $data["node"] === $server->getNode() and (int) $data["node_id"] === $server->getId()) {
					$exists = true;
				} else {
					$exists = false;
				}
			} else {
				$exists = false;
			}
			if($exists === true) {
				$mysqli->query("UPDATE network_servers SET server_motd = '{$server->getName()}', address = '{$server->getHost()}', server_port = {$server->getPort()}, online_players = {$server->getOnlinePlayers()}, max_players = {$server->getMaxPlayers()}, last_sync = " . time() . ", online = " . ($server->isOnline() ? 1 : 0) . " WHERE node = '{$server->getNode()}' AND node_id = {$server->getId()}");
			} elseif($exists === false) {
				$mysqli->query("INSERT INTO network_servers (server_motd, node, node_id, address, server_port, online_players, max_players, player_list, last_sync, online) VALUES
				('{$server->getName()}', '{$server->getNode()}', {$server->getId()}, '{$server->getHost()}', {$server->getPort()}, {$server->getOnlinePlayers()}, {$server->getMaxPlayers()}, '[]', " . time() . ", " . ($server->isOnline() ? 1 : 0) . ")");
			} else {
				return false;
			}
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}

	public function fetchServers(\mysqli $mysqli, NetworkServer $server) {
		$result = $mysqli->query("SELECT * FROM network_servers WHERE (node = '{$server->getNode()}' AND NOT node_id = {$server->getId()}) OR NOT node = '{$server->getNode()}'");
		if($result instanceof \mysqli_result) {
			$servers = [];
			while($row = $result->fetch_assoc()) {
				$servers[] = serialize(new NetworkServer($row["node_id"], $row["server_motd"], $row["node"], $row["address"], $row["server_port"], $row["max_players"], $row["online_players"], json_decode($row["player_list"], true), $row["last_sync"], (bool) $row["online"]));
			}
			$result->free();
			return $servers;
		}
		return [];
	}

	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			switch($result[0]) {
				case self::SUCCESS:
					$plugin->getNetworkManager()->networkSyncCallback(array_map("unserialize", $result[1]));
					$server->getLogger()->debug("Successfully completed SyncRequest!");
					return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete SyncRequest while Components plugin isn't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}