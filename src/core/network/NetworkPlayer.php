<?php

/**
 * ConflictCore – NetworkPlayer.php
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
 * Created on 7/5/2017 at 1:56 PM
 *
 */

namespace core\network;

/**
 * Class to represent a player that is online on the network
 */
class NetworkPlayer {

	/** @var string */
	private $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName() : string {
		return $this->name;
	}

}