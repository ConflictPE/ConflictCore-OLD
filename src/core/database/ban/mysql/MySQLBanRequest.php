<?php

/**
 * ConflictCore – MySQLBanRequest.php
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
 * Created on 24/03/2017 at 9:45 AM
 *
 */

namespace core\database\ban\mysql;

use core\database\mysql\MySQLRequest;

abstract class MySQLBanRequest extends MySQLRequest {

	/* The key used to store a mysqli instance onto the thread */
	const BANS_KEY = "mysqli.bans";

	/**
	 * @return mixed|\mysqli
	 */
	public function getMysqli() {
		$mysqli = $this->getFromThreadStore(self::BANS_KEY);
		if($mysqli !== null){
			return $mysqli;
		}
		$mysqli = parent::getMysqli();
		$this->saveToThreadStore(self::BANS_KEY, $mysqli);
		return $mysqli;
	}

}