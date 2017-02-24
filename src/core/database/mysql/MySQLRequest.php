<?php

/**
 * ConflictCore – MySQLRequest.php
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

namespace core\database\mysql;

use core\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

abstract class MySQLRequest extends AsyncTask {

	/** @var MySQLCredentials */
	private $credentials;

	/** States */
	const CONNECTION_ERROR = "state.connection.error";
	const MYSQLI_ERROR = "state.mysqli.error";
	const NO_DATA = "state.no.data";
	const WRONG_FORMAT = "state.wrong.format";
	const NO_CHANGE = "state.no.change";
	const SUCCESS = "state.success";

	/**
	 * MySQLQuery constructor.
	 *
	 * @param MySQLCredentials $credentials
	 */
	public function __construct(MySQLCredentials $credentials) {
		$this->credentials = $credentials;
	}

	/**
	 * @return \mysqli
	 */
	public function getMysqli() {
		return $this->credentials->getMysqli();
	}

	/**
	 * @param Server $server
	 *
	 * @return null|Main
	 */
	public function getCore(Server $server) {
		return $server->getPluginManager()->getPlugin("Components");
	}

	/**
	 * @param \mysqli $mysqli
	 *
	 * @return bool
	 */
	public function checkConnection(\mysqli $mysqli) {
		if($mysqli->connect_error) {
			$this->setResult([self::CONNECTION_ERROR, $mysqli->connect_error]);
			return true;
		}
		return false;
	}

	/**
	 * @param \mysqli_stmt $stmt
	 *
	 * @return bool
	 */
	public function checkError(\mysqli_stmt $stmt) {
		if($stmt->error) {
			$this->setResult([self::MYSQLI_ERROR, $stmt->error]);
			return true;
		}
		return false;
	}

	/**
	 * @param \mysqli_stmt $stmt
	 *
	 * @return bool
	 */
	public function checkAffectedRows(\mysqli_stmt $stmt) {
		if($stmt->affected_rows < 0) {
			$this->setResult([self::NO_CHANGE]);
			return true;
		}
		return false;
	}

	/**
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public function checkResult($data) {
		if(!is_array($data)) {
			$this->setResult([self::NO_DATA]);
			return true;
		}
		return false;
	}

}