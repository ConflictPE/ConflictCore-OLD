<?php

/**
 * ConflictCore – RabbitPet.php
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
 * Created on 13/5/17 at 7:44 PM
 *
 */

namespace core\entity\pets\types;

use core\entity\pets\BasePet;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;

class RabbitPet extends BasePet {

	const NETWORK_ID = 18;

	public $width = 0.67;
	public $height = 0.67;

	public function getName() {
		return "RabbitPet";
	}

	public function getPetName() : string {
		return "Rabbit";
	}

	public function getSpeed() : int {
		return 3;
	}

	public function initEntity() {
		parent::initEntity();
		$this->namedtag->RabbitType = new IntTag("RabbitType", mt_rand(0, 5));
	}

	/**
	 * Override the BasePet's checkMovement method so the rabbit 'hops' around instead of walking
	 *
	 * @return bool
	 */
	public function checkMovement() {
		$x = $this->getOwner()->x - $this->x;
		$z = $this->getOwner()->z - $this->z;
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
		$y = $this->getOwner()->y - $this->y;
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
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : $this->ticksLived % 20 === 0 ? 0.7 : 0;
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

}