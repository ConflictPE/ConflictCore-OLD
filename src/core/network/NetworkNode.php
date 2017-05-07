<?php

/**
 * ConflictCore – NetworkNode.php
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

namespace core\network;

/**
 * Class that represents a group (node) of servers, usually each mini-game has it's own node
 */
class NetworkNode {

	/** @var string */
	private $name;

	/** @var string */
	private $display;

	/** @var NetworkServer[] */
	private $servers = [];

	/** @var int */
	private $onlinePlayerCount = 0;

	/** @var int */
	private $maxPlayerCount = 100;

	/** @var bool */
	private $closed = false;

	public function __construct(string $name, string $display, array $servers = []) {
		$this->name = $name;
		$this->display = $display;
		$this->servers = $servers;
	}

	/**
	 * Get the nodes internal name
	 *
	 * @return string
	 */
	public function getName() : string {
		return $this->name;
	}

	/**
	 * Get the nodes display name
	 *
	 * @return string
	 */
	public function getDisplay() : string {
		return $this->display;
	}

	/**
	 * Get the total number of players online on this node
	 *
	 * @return int
	 */
	public function getOnlinePlayers() : int {
		return $this->onlinePlayerCount;
	}

	/**
	 * Get the total number of slots for this node
	 *
	 * @return int
	 */
	public function getMaxPlayers() : int {
		return $this->maxPlayerCount;
	}

	/**
	 * Recalculate the number of players online and total number of slots for this node
	 */
	public function recalculateSlotCounts() {
		$online = 0;
		$max = 0;
		$time = time();
		foreach($this->servers as $server) {
			if($time - $server->getLastSyncTime() <= 15) {
				$online += $server->getOnlinePlayers();
				$max += $server->getMaxPlayers();
			}
		}
		$this->onlinePlayerCount = $online;
		$this->maxPlayerCount = $max;
	}

	/**
	 * Find a server that is suitable to join
	 *
	 * @return NetworkServer|mixed|null
	 */
	public function getSuitableServer() {
		if(!empty($this->servers)) {
			foreach($this->servers as $server) {
				if($server->isAvailable()) {
					return $server;
				}
			}
		}
		return null;
	}

	/**
	 * @return NetworkServer[]
	 */
	public function getServers() {
		return $this->servers;
	}

	/**
	 * Add a server to the node
	 *
	 * @param NetworkServer $server
	 */
	public function addServer(NetworkServer $server) {
		if(!isset($this->servers[$server->getId()])) {
			$this->servers[$server->getId()] = $server;
		}
	}

	/**
	 * Replace an existing network server with an updated version
	 *
	 * @param NetworkServer $server
	 */
	public function updateServer(NetworkServer $server) {
		$this->servers[$server->getId()] = $server;
	}

	/**
	 * Remove a server from the node
	 *
	 * @param NetworkServer $server
	 */
	public function removeServer(NetworkServer $server) {
		unset($this->servers[$server->getId()]);
	}

	/**
	 * Dump all data safely to prevent memory leaks and shutdown hold ups
	 */
	public function close() {
		if(!$this->closed) {
			$this->closed = true;
			foreach($this->servers as $server) {
				unset($this->servers[$server->getId()]);
				$server->close();
			}
			unset($this->name, $this->display, $this->servers, $this->onlinePlayerCount, $this->maxPlayerCount);
		}
	}

}