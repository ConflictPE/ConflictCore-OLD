<?php

/**
 * ConflictCore – NetworkServer.php
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
 * Created on 15/04/2017 at 12:41 AM
 *
 */

namespace core\network;

/**
 * A class that represents the current status of another server on the network
 */
class NetworkServer {

	/** @var int */
	private $id = 0;

	/** @var string */
	private $name = "Hub-1";

	/** @var string */
	private $node;

	/** @var string */
	private $host = "";

	/** @var int */
	private $port = 19132;

	/** @var int */
	private $onlinePlayers = 0;

	/** @var int */
	private $maxPlayers = 100;

	/** @var bool */
	private $lastOnline = 0;

	/** @var bool */
	private $online = false;

	/** @var bool */
	private $closed = false;

	public function __construct(int $id, string $name, string $node, string $host, int $port, int $maxPlayers, int $onlinePlayers, array $playerList, int $lastSync, bool $online) {
		$this->id = $id;
		$this->name = $name;
		$this->node = $node;
		$this->host = $host;
		$this->port = $port;
		$this->setPlayerStatus($onlinePlayers, $maxPlayers);
		$this->lastOnline = $lastSync;
		$this->online = $online;
	}

	/**
	 * Update the online and max max player count of the server
	 *
	 * @param int $online
	 * @param int $max
	 */
	public function setPlayerStatus(int $online, int $max) {
		$this->onlinePlayers = $online;
		$this->maxPlayers = $max;
	}

	/**
	 * Get the node ID of the server
	 *
	 * @return int
	 */
	public function getId() : int {
		return $this->id;
	}

	/**
	 * Get the MOTD of the server
	 *
	 * @return string
	 */
	public function getName() : string {
		return $this->name;
	}

	/**
	 * Get the node string of the server
	 *
	 * @return string
	 */
	public function getNode() : string {
		return $this->node;
	}

	/**
	 * Get the IP of the server
	 *
	 * @return string
	 */
	public function getHost() : string {
		return $this->host;
	}

	/**
	 * Get the port of the server
	 *
	 * @return int
	 */
	public function getPort() : int {
		return $this->port;
	}

	/**
	 * Check if the server is available to join
	 *
	 * @return bool
	 */
	public function isAvailable()  : bool {
		return $this->online and $this->onlinePlayers < $this->maxPlayers and time() - $this->lastOnline <= 15;
	}

	/**
	 * Get the online player count of the server
	 *
	 * @return int
	 */
	public function getOnlinePlayers() : int {
		return $this->onlinePlayers;
	}

	/**
	 * Get the max player count of the server
	 *
	 * @return int
	 */
	public function getMaxPlayers() : int {
		return $this->maxPlayers;
	}

	/**
	 * Get the timestamp of when the server was last synced
	 *
	 * @return mixed
	 */
	public function getLastSyncTime() : int {
		return $this->lastOnline;
	}

	/**
	 * Get the online status of the server
	 *
	 * @return bool
	 */
	public function isOnline() {
		return $this->online;
	}

	/**
	 * Set the online status of the server
	 *
	 * @param bool $value
	 */
	public function setOnline(bool $value = true) {
		$this->online = $value;
	}

	/**
	 * Dump all data safely to prevent memory leaks and shutdown hold ups
	 */
	public function close() {
		if(!$this->closed) {
			$this->closed = true;
			unset($this->id, $this->name, $this->node, $this->host, $this->port, $this->onlinePlayers, $this->maxPlayers, $this->lastOnline);
		}
	}

}