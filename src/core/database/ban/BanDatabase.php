<?php

/**
 * ConflictCore – BanDatabase.php
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
 * Created on 14/07/2016 at 4:12 PM
 *
 */

namespace core\database\ban;

/**
 * All classes that implement a ban database MUST implement this class
 */
interface BanDatabase {

	public function check($name, $ip, $cid, $doCallback);

	public function add($name, $ip, $cid, $expiry, $reason, $issuer);

	public function update($name, $ip, $cid);

	public function remove($name, $ip, $cid);

}