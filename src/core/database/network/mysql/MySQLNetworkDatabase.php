<?php

/**
 * ConflictCore – MySQLNetworkDatabase.php
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

namespace core\database\network\mysql;

use core\database\mysql\MySQLDatabase;
use core\database\network\mysql\task\CheckDatabaseRequest;
use core\database\network\mysql\task\FetchNodeListRequest;
use core\database\network\mysql\task\SyncRequest;
use core\database\network\NetworkDatabase;
use core\database\network\NetworkScheduler;

/**
 * MySQL implementation of the network database
 */
class MySQLNetworkDatabase extends MySQLDatabase implements NetworkDatabase {

	/** @var NetworkScheduler */
	protected $updateScheduler;

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckDatabaseRequest($this));
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new FetchNodeListRequest($this));
		$this->updateScheduler = new NetworkScheduler($this->getPlugin());
	}

	public function sync() {
		$server = $this->getPlugin()->getNetworkManager()->getServer();
		$server->setPlayerStatus(count($this->getPlugin()->getServer()->getOnlinePlayers()), $this->getPlugin()->getServer()->getMaxPlayers());
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new SyncRequest($this, $server));
	}

	public function close() {
		if(parent::close()) {
			$this->updateScheduler->cancel();
			unset($this->updateScheduler);
		}
	}

}