<?php

/**
 * ConflictCore – PetsManager.php
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

use core\entity\pets\types\BabyCowPet;
use core\entity\pets\types\BabyPigPet;
use core\entity\pets\types\PigPet;
use core\CorePlayer;
use core\entity\pets\types\ChickenPet;
use core\entity\pets\types\OcelotPet;
use core\entity\pets\types\RabbitPet;
use core\entity\pets\types\WolfPet;
use core\Main;
use pocketmine\entity\Entity;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

class PetsManager {

	/** @var Main */
	private $plugin;

	/** @var string[] */
	private $registeredPets = [];

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->registerDefaultPets();
	}

	public function registerDefaultPets() {
		$this->registerPet(BabyCowPet::class, PetTypes::PET_TYPE_BABY_COW);
		$this->registerPet(BabyPigPet::class, PetTypes::PET_TYPE_BABY_PIG);
		$this->registerPet(ChickenPet::class, PetTypes::PET_TYPE_CHICKEN);
		$this->registerPet(OcelotPet::class, PetTypes::PET_TYPE_OCELOT);
		//$this->registerPet(PigPet::class, PetTypes::PET_TYPE_PIG);
		$this->registerPet(RabbitPet::class, PetTypes::PET_TYPE_RABBIT);
		$this->registerPet(WolfPet::class, PetTypes::PET_TYPE_WOLF);
	}

	/**
	 * Register a pet class
	 *
	 * @param string $class
	 * @param string $type
	 */
	public function registerPet(string $class, string $type) {
		$reflection = new \ReflectionClass($class);
		if($reflection->isSubclassOf(BasePet::class) and !$reflection->isAbstract()) {
			Entity::registerEntity($class, true);
			$this->registeredPets[$type] = $reflection->getShortName();
		}
	}

	/**
	 * @param string $type
	 *
	 * @return null|string
	 */
	public function getRegisteredPetClass(string $type) {
		return $this->registeredPets[$type] ?? null;
	}

	/**
	 * Spawn a pet entity
	 *
	 * @param CorePlayer $owner
	 * @param Position $source
	 * @param string $type
	 *
	 * @return null|Entity|\pocketmine\entity\Projectile
	 */
	public function create(CorePlayer $owner, Position $source, string $type = PetTypes::PET_TYPE_CHICKEN) {
		$class = $this->getRegisteredPetClass($type);
		if($class !== null) {
			$nbt = new CompoundTag("", [
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $source->x),
					new DoubleTag("", $source->y),
					new DoubleTag("", $source->z),
				]),
				"Motion" => new ListTag("Motion", [
					new DoubleTag("", 0),
					new DoubleTag("", 0),
					new DoubleTag("", 0),
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", $source instanceof Location ? $source->yaw : 0),
					new FloatTag("", $source instanceof Location ? $source->pitch : 0),
				]),
			]);
			return Entity::createEntity($class, $source->getLevel(), $nbt, $owner);
		}
		return null;
	}

	/**
	 * Create a pet for a player based off their last used pet
	 *
	 * @param CorePlayer $player
	 */
	public function createPetFor(CorePlayer $player) {
		if(!$player->hasPet()) {
			$len = rand(8, 12);
			$x = (-sin(deg2rad($player->yaw))) * $len + $player->getX();
			$z = cos(deg2rad($player->yaw)) * $len + $player->getZ();
			$y = $player->getLevel()->getHighestBlockAt($x, $z);
			$source = new Position($x, $y + 2, $z, $player->getLevel());
			$type = $player->getLastUsedPetType();
			$pet = $this->create($player, $source, $type);
			if($pet !== null) {
				$player->setHasPet(true);
				$player->setPetEntityId($pet->getId());
				$pet->spawnToAll();
			}
		}
	}

}
