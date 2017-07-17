<?php

/**
 * ConflictCore – HumanNPC.php
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

namespace core\entity\npc;

use core\Main;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\Player;
use pocketmine\utils\PluginException;

abstract class HumanNPC extends Human implements BaseNPC {

	/** @var Main */
	private $core;

	/** @var string */
	protected $name;

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}

	/**
	 * Spawn the NPC to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player !== $this and !isset($this->hasSpawned[$player->getId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;

			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = [$this->getUniqueId(), $this->getId(), "", ($this->skinId !== "" ? $this->skinId : $player->skinId), ($this->skin !== "" ? $this->skin : $player->skin)];
			$player->dataPacket($pk);

			$pk = new AddPlayerPacket();
			$pk->uuid = $this->getUniqueId();
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->item = $this->getInventory()->getItemInHand();
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->sendLinkedData();
			$this->inventory->sendArmorContents($player);
		}
	}

	/**
	 * Ensure the NPC doesn't take damage
	 *
	 * @param float $damage
	 * @param EntityDamageEvent $source
	 */
	public function attack($damage, EntityDamageEvent $source) {
		$source->setCancelled(true);
	}

	/**
	 * Make sure the npc doesn't get saved
	 */
	public function saveNBT() {
		return false;
	}

	/**
	 * Same save characteristics as a player
	 */
	public function getSaveId() {
		return "Human";
	}

	/**
	 * Set the NPC's real name to the one given when the entity is spawned
	 */
	public function initEntity() {
		parent::initEntity();
		$plugin = $this->server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main and $plugin->isEnabled()){
			$this->core = $plugin;
		} else {
			throw new PluginException("Core plugin isn't loaded!");
		}
		$this->name = $this->getNameTag();
		$this->getLevel()->getChunk($this->x >> 4, $this->z >> 4)->allowUnload = false;
		$this->setImmobile(true);
		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
	}

	/**
	 * @param $string
	 */
	public function setName($string) {
		$this->name = $string;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Make sure nothing drops in case the NPC dies
	 *
	 * @return array
	 */
	public function getDrops() {
		return [];
	}

	/**
	 * Function to easily spawn an NPC
	 *
	 * @param string $shortName
	 * @param Location $pos
	 * @param string $name
	 * @param string $skin
	 * @param string $skinName
	 * @param CompoundTag $nbt
	 *
	 * @return HumanNPC|null
	 */
	public static function spawn($shortName, Location $pos, $name, $skin, $skinName, Compound $nbt) {
		$entity = Entity::createEntity($shortName, $pos->getLevel()->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
		if($entity instanceof HumanNPC) {
			$entity->setSkin($skin, $skinName);
			$entity->setName($name);
			$entity->setNameTag($entity->getName());
			$entity->setSkin($skin, $skinName);
			$entity->setPositionAndRotation($pos, $pos->yaw, $pos->pitch);
			return $entity;
		} else {
			$entity->kill();
		}
		return null;
	}

}