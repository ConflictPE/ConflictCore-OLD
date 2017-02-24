<?php

/**
 * ConflictCore – MySQLAuthDatabase.php
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

namespace core\database\auth\mysql;

use core\database\auth\AuthDatabase;
use core\database\auth\mysql\task\CheckDatabaseRequest;
use core\database\auth\mysql\task\LoginRequest;
use core\database\auth\mysql\task\RegisterRequest;
use core\database\auth\mysql\task\UpdatePasswordRequest;
use core\database\auth\mysql\task\UpdateRequest;
use core\database\auth\mysql\task\UpdateRequestScheduler;
use core\database\mysql\MySQLDatabase;

/**
 * MySQL implementation of the Auth database
 */
class MySQLAuthDatabase extends MySQLDatabase implements AuthDatabase {
	
	/** @var UpdateRequestScheduler */
	protected $updateScheduler;

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckDatabaseRequest($this));
		$this->updateScheduler = new UpdateRequestScheduler($this->getPlugin());
	}

	public function register($name, $hash, $email) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new RegisterRequest($this, $name, $hash, $email));
	}

	public function login($name) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new LoginRequest($this, $name));
	}

	public function update($name, array $args) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new UpdateRequest($this, $name, $args));
	}

	public function changePassword($name, $hash) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new UpdatePasswordRequest($this, $name, $hash));
	}

	public function unregister($name) {

	}

	public function close() {
	}

}