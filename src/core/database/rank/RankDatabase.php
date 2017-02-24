<?php

/**
 * ConflictCore – RankDatabase.php
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

namespace core\database\rank;

/**
 * All classes that implement a rank database MUST implement this class
 */
interface RankDatabase {

	public function load($player);

	public function add($player, $rank);

	public function remove($player, $rank);

}