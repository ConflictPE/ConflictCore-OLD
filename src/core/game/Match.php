<?php

/**
 * ConflictCore – Match.php
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

namespace core\game;

use core\CorePlayer;

class Match {

	/** @var MatchManager */
	private $manager;

	/** @var bool */
	private $active = true;

	/** @var int */
	private $lastTick = 0;

	/** @var CorePlayer[] */
	private $players = [];

	/** @var CorePlayer[] */
	private $spectators = [];

	public function __construct(MatchManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @return MatchManager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * @return bool
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * @return int
	 */
	public function getLastTick() {
		return $this->lastTick;
	}

	/**
	 * @param CorePlayer $player
	 */
	public function addPlayer(CorePlayer $player) {
		$this->players[$player->getName()] = $player;
	}

	/**
	 * @param CorePlayer $player
	 */
	public function addSpectator(CorePlayer $player) {
		$this->spectators[$player->getName()] = $player;
	}

	/**
	 * @param string|CorePlayer $player
	 */
	public function removePlayer($player) {
		if($player instanceof CorePlayer) $player = $player->getName();
		$player->kill();
		unset($this->players[$player]);
	}

	/**
	 * @param string|CorePlayer $player
	 */
	public function removeSpectator($player) {
		if($player instanceof CorePlayer) $player = $player->getName();
		$player->kill();
		unset($this->spectators[$player]);
	}

	/**
	 * @param int $currentTick
	 */
	public function tick($currentTick) {
		$this->checkPlayers();
		$this->lastTick = $currentTick;
	}

	public function checkPlayers() {
		foreach($this->players as $player) {
			if($player instanceof CorePlayer) {

			} else {

			}
		}
	}

	/**
	 * Safely close the match instance
	 */
	public function close() {
		if($this->active) {
			foreach($this->players as $player) $this->removePlayer($player);
			foreach($this->spectators as $spectator) $this->removeSpectator($spectator);
		}
	}

	public function __destruct() {
		$this->close();
	}

}