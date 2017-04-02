<?php

/**
 * ConflictCore – ChestGUI.php
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
 * Created on 02/04/2017 at 1:46 PM
 *
 */

namespace core\gui;

use core\CorePlayer;
use core\gui\item\GUIItem;
use core\Utils;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;

abstract class ChestGUI extends ChestInventory {

	/** @var null|Chest */
	private $fakeChest = null;

	/** @var Position */
	private $lastOpenPos = null;

	/** @var array */
	private $replacedBlockData = [];

	public function __construct(CorePlayer $owner) {
		$this->fakeChest = new Chest($owner->getLevel(), new CompoundTag("", [
			new ListTag("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $owner->x),
			new IntTag("y", $owner->y),
			new IntTag("z", $owner->z)
		]));
		parent::__construct($this->fakeChest);
	}

	public function onOpen(Player $who) {
		$this->lastOpenPos = $who->getPosition()->subtract(0.5, 4, 0.5);
		$this->fakeChest->setComponents($this->lastOpenPos->x, $this->lastOpenPos->y, $this->lastOpenPos->z);
		$this->fakeChest->spawnTo($who);
		$block = $who->getLevel()->getBlock($this->lastOpenPos);
		$this->replacedBlockData = [$block->getId(), $block->getDamage()];
		Utils::sendBlock($who, $this->lastOpenPos, Item::CHEST, 0);
		parent::onOpen($who);
	}

	public function onClose(Player $who) {
		Utils::sendBlock($who, $this->lastOpenPos, $this->replacedBlockData[0], $this->replacedBlockData[1]);
		parent::onClose($who);
	}

	public function onSelect($slot, GUIItem $item, CorePlayer $player) {
		return false;
	}

}