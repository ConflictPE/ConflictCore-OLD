<?php

/**
 * ConflictCore – BasePet.php
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
 * Created on 11/5/17 at 1:19 PM
 *
 */

namespace core\entity\pets;

use core\CorePlayer;
use core\Utils;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

abstract class BasePet extends Creature {

	/** @var CorePlayer */
	private $owner = null;

	/** @var int */
	protected $distanceToOwner = 0;

	/** @var bool */
	protected $onSlab = false;

	public function __construct(Level $level, CompoundTag $nbt, CorePlayer $owner = null) {
		$this->owner = $owner;
		parent::__construct($level, $nbt);
	}

	/**
	 * @return CorePlayer
	 */
	public function getOwner() {
		return $this->owner;
	}

	public function saveNBT() {
		return null;
	}

	public function setOwner(CorePlayer $player) {
		$this->owner = $player;
	}

	public abstract function getPetName() :string;

	public function initEntity() {
		parent::initEntity();
		$this->setNameTagVisible();
		$this->setNameTag(Utils::translateColors("&e{$this->owner->getName()}('s) pet {$this->getPetName()}"));
	}

	public function spawnTo(Player $player) {
		if(!$this->closed and $player->spawned and $player->isAlive()) {
			if(!isset($this->hasSpawned[$player->getId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
				$pk = new AddEntityPacket();
				$pk->eid = $this->getID();
				$pk->type = static::NETWORK_ID;
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->speedX = 0;
				$pk->speedY = 0;
				$pk->speedZ = 0;
				$pk->yaw = $this->yaw;
				$pk->pitch = $this->pitch;
				$pk->metadata = $this->dataProperties;
				$player->dataPacket($pk);
				$this->hasSpawned[$player->getId()] = $player;
			}
		}
	}

	public function updateMovement() {
		if($this->lastX !== $this->x or $this->lastY !== $this->y or $this->lastZ !== $this->z or $this->lastYaw !== $this->yaw or $this->lastPitch !== $this->pitch) {
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;
		}
		$this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
	}

	public function attack($damage, EntityDamageEvent $source) {
		$source->setCancelled(true);
	}

	public function move($dx, $dy, $dz) {
		$this->boundingBox->offset($dx, 0, 0);
		$this->boundingBox->offset(0, 0, $dz);
		$this->boundingBox->offset(0, $dy, 0);
		$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
		return true;
	}

	/**
	 * Get the entities base speed
	 *
	 * @return int
	 */
	public function getSpeed() : int {
		return 1;
	}

	public function checkMovement() {
		$x = $this->owner->x - $this->x;
		$z = $this->owner->z - $this->z;
		if($x ** 2 + $z ** 2 < 4) {
			$this->motionX = 0;
			$this->motionZ = 0;
			$this->motionY = 0;
			return false;
		} else {
			$diff = abs($x) + abs($z);
			$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
			$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
		}
		$this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
		$y = $this->owner->y - $this->y;
		$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		$dx = $this->motionX;
		$dz = $this->motionZ;
		$newX = Math::floorFloat($this->x + $dx);
		$newZ = Math::floorFloat($this->z + $dz);
		$currentBlock = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if(!($currentBlock instanceof Air) and !($currentBlock instanceof Liquid)) {
				$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y + 1), $newZ));
				if(!($block instanceof Air) and !($block instanceof Liquid)) {
					$this->motionY = 0;
					$this->returnToOwner();
					$this->onSlab = false;
				} else {
					if(!$block->canBeFlowedInto()) {
						$this->motionY = 1.1;
						$this->onSlab = false;
					} elseif(($currentBlock->getId() === Block::STONE_SLAB and $currentBlock->getDamage() <= 7) or ($currentBlock->getId() === Block::WOOD_SLAB and $currentBlock->getDamage() <= 5)) {
						if(!$this->onSlab) {
							$this->motionY = 0.6;
							$this->onSlab = true;
						}
					} else {
						$this->motionY = 0;
						$this->onSlab = false;
					}
				}
		} else {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y - 1), $newZ));
			if(!($block instanceof Air) and !($block instanceof Liquid)) {
				$blockY = Math::floorFloat($this->y);
				if($this->y - $this->gravity * 4 > $blockY) {
					$this->motionY = -$this->gravity * 4;
					$this->onSlab = false;
				} else {
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : 0;
				}
			} else {
				$this->motionY -= $this->gravity * 4;
				$this->onSlab = false;
			}
		}
		$dy = $this->motionY;
		$this->move($dx, $dy, $dz);
		$this->updateMovement();
		return true;
	}

	public function onUpdate($currentTick) {
		if($this->closed){
			return false;
		}
		if(!($this->owner instanceof CorePlayer) or $this->owner->closed) {
			$this->close();
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		$this->lastUpdate = $currentTick;
		if($this->distance($this->owner) > 40) {
			$this->returnToOwner();
		}
		$this->entityBaseTick($tickDiff);
		$this->checkMovement();
		$this->checkChunks();
		return true;
	}

	/**
	 * Return the pet to the vicinity of it's owner
	 */
	public function returnToOwner() {
		$len = rand(2, 6);
		$x = (-sin(deg2rad($this->owner->yaw))) * $len + $this->owner->getX();
		$z = cos(deg2rad($this->owner->yaw)) * $len + $this->owner->getZ();
		$this->x = $x;
		$this->y = $this->owner->getY() + 1;
		$this->z = $z;
	}

}
