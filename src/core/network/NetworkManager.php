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
 * Created on 6/5/2017 at 3:18 PM
 *
 */

namespace core\network;

use core\Main;

class NetworkManager {

	/** @var Main */
	private $plugin;

	/** @var NetworkServer */
	private $server;

	/** @var NetworkNode[] */
	private $nodes = [];

	/** @var int */
	private $onlinePlayerCount = 0;

	/** @var int */
	private $maxPlayerCount = 100;

	/** @var bool */
	private $closed = false;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$settings = $plugin->getSettings();
		$server = $plugin->getServer();
		$this->server = new NetworkServer($settings->getNested("settings.network.id"), $server->getNetwork()->getName(), $settings->getNested("settings.network.node"), $server->getIp(), $server->getPort(), count($server->getOnlinePlayers()), $server->getMaxPlayers(), [], time(), true);
	}

	/**
	 * @return Main
	 */
	public function getPlugin() : Main {
		return $this->plugin;
	}

	/**
	 * @return NetworkServer
	 */
	public function getServer() {
		return $this->server;
	}

	/**
	 * @return NetworkNode[]
	 */
	public function getNodes() {
		return $this->nodes;
	}

	/**
	 * Get the total number of players on the network
	 *
	 * @return int
	 */
	public function getOnlinePlayers() : int {
		return $this->onlinePlayerCount;
	}

	/**
	 * Get the total number of slots for the network
	 *
	 * @return int
	 */
	public function getMaxPlayers() : int {
		return $this->maxPlayerCount;
	}

	/**
	 * Set the available nodes
	 *
	 * ** NOTE: This will remove all servers from the node lists until the next network sync **
	 *
	 * @param NetworkNode[] $nodes
	 */
	public function setNodes(array $nodes) {
		foreach($nodes as $node) {
			$this->nodes[$node->getName()] = $node;
		}
	}

	/**
	 * Callback for network sync
	 *
	 * @param NetworkServer[] $servers
	 */
	public function networkSyncCallback(array $servers) {
		foreach($servers as $server) {
			if(isset($this->nodes[$server->getNode()])) {
				$this->nodes[$server->getNode()]->updateServer($server);
			}
		}
		$this->recalculateSlots();
	}

	/**
	 * Recalculate the global slot counts for the network
	 */
	public function recalculateSlots() {
		$online = 0;
		$max = 0;
		foreach($this->nodes as $node) {
			$node->recalculateSlotCounts();
			$online += $node->getOnlinePlayers();
			$max += $node->getMaxPlayers();
		}
		$this->onlinePlayerCount = $online;
		$this->maxPlayerCount = $max;
	}

	/**
	 * Dump all data safely to prevent memory leaks and shutdown hold ups
	 */
	public function close() {
		if(!$this->closed) {
			$this->closed = true;
			foreach($this->nodes as $node) {
				unset($this->nodes[$node->getName()]);
				$node->close();
			}
			$this->server->close();
			unset($this->plugin, $this->server, $this->nodes, $this->onlinePlayerCount, $this->maxPlayerCount);
		}
	}

}