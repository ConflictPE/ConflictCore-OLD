<?php

/**
 * ConflictCore – GUIItem.php
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

namespace core\gui\item;

use core\ChatUtil;
use core\CorePlayer;
use core\gui\ChestGUI;
use core\language\LanguageManager;
use core\Utils;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;

abstract class GUIItem extends Item {

	/** Time in which a user has to double click the item */
	const DOUBLE_CLICK_TIME = 20;

	private static $cooldownTick = [];
	protected $clickCount = 0;
	protected $lastClick = 0;

	/** @var ChestGUI */
	private $parent;

	/**
	 * GUIItem constructor
	 *
	 * @param Item $item
	 * @param ChestGUI $parent
	 */
	public function __construct(Item $item, ChestGUI $parent = null) {
		parent::__construct($item->getId(), $item->getDamage(), $item->getCount(), $item->getName());
		$this->parent = $parent;
	}

	/**
	 *
	 *
	 * @param CorePlayer $player
	 * @param bool $force
	 */
	final public function handleClick(CorePlayer $player, bool $force = false) {
		$this->tickCooldowns();
		$ticks = $player->getServer()->getTick();
		$lang = LanguageManager::getInstance();
		if($ticks - $this->getCooldownTick($player) > $this->getCooldown()) {
			if($this->clickCount == 0 and !$force) {
				$player->sendPopup(ChatUtil::centerPrecise($lang->translateForPlayer($player, "GUI_ITEM_PREVIEW", [$this->getPreviewName($player)]) . $lang->translateForPlayer($player, "GUI_ITEM_TAP_GROUND"), null));
				$this->clickCount++;
			} else {
				$this->clickCount = 0;
				$this->lastClick = 0;
				self::$cooldownTick[$player->getUniqueId()->toString()] = $ticks;
				$this->onClick($player);
			}
		} else {
			$player->sendPopup($lang->translateForPlayer($player, "GUI_ITEM_COOLDOWN", [Utils::getTimeString($this->getCooldown() - ($ticks - $this->getCooldownTick($player)))]));
		}
		$this->lastClick = $ticks;
	}

	public function onClick(CorePlayer $player) {
		return true;
	}

	public abstract function getCooldown() : int;

	public function getPreviewName(CorePlayer $player) {
		return $this->getName();
	}

	final private function getCooldownTick(CorePlayer $player) {
		if(isset(self::$cooldownTick[$player->getUniqueId()->toString()])) {
			return self::$cooldownTick[$player->getUniqueId()->toString()];
		}
		return 0;
	}

	final private function tickCooldowns() {
		foreach(self::$cooldownTick as $plId => $cooldown) {
			if($cooldown == 0 or Utils::getPlayerByUUID($plId) == null) {
				unset(self::$cooldownTick[$plId]);
			}
		}
	}

	public function giveEnchantmentEffect() {
		$tag = $this->getNamedTag();
		$tag->ench = new ListTag("ench", [
			0 => new CompoundTag("", [
				"id" => new ShortTag("id", -1),
				"lvl" => new ShortTag("lvl", 1)
			])
		]);
		$tag->ench->setTagType(NBT::TAG_Compound);
		$this->setNamedTag($tag);
	}

	public function removeEnchantmentEffect() {
		$tag = $this->getNamedTag();
		unset($tag->ench);
		$this->setNamedTag($tag);
	}

}