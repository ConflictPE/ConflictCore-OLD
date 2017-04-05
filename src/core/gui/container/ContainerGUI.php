<?php

/**
 * ConflictCore – ContainerGUI.php
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
 * Created on 05/04/2017 at 2:26 PM
 *
 */

namespace core\gui\container;

use core\CorePlayer;
use core\gui\item\GUIItem;
use pocketmine\inventory\Inventory;

interface ContainerGUI extends Inventory {

	/**
	 * Called when a player selects an item from within the GUI
	 *
	 * @param $slot
	 * @param GUIItem $item
	 * @param CorePlayer $player
	 *
	 * @return mixed
	 */
	public function onSelect($slot, GUIItem $item, CorePlayer $player);

}