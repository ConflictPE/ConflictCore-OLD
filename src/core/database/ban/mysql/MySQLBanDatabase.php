<?php

/**
 * ConflictCore – MySQLBanDatabase.php
 *
 * Copyright (C) 2017 Jack Noordhuis
 *
 * This is private software, you cannot redistribute it and/or modify any way
 * unless otherwise given permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 14/07/2016 at 4:15 PM
 *
 */

namespace core\database\ban\mysql;

use core\database\ban\BanDatabase;
use core\database\ban\mysql\task\AddBanRequest;
use core\database\ban\mysql\task\CheckBanRequest;
use core\database\ban\mysql\task\CheckDatabaseRequest;
use core\database\ban\mysql\task\UpdateBanRequest;
use core\database\mysql\MySQLDatabase;

class MySQLBanDatabase extends MySQLDatabase implements BanDatabase {

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
	}

	public function check($name, $ip, $cid, $doCallback) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckBanRequest($this, $name, $ip, $cid, $doCallback));
	}

	public function add($name, $ip, $cid, $expiry, $reason, $issuer) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new AddBanRequest($this, $name, $ip, $cid, $expiry, $reason, $issuer));
	}

	public function update($name, $ip, $cid) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new UpdateBanRequest($this, $name, $ip, $cid));
	}

	public function remove($name, $ip, $id) {

	}

}