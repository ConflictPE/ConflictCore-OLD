<?php

/**
 * ConflictCore – FloatingText.php
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

namespace core\entity\text;

use core\Main;
use core\Utils;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

class FloatingText {

	/** @var Position */
	protected $pos;

	/** @var string */
	protected $rawText = "";

	/** @var int */
	protected $eid;

	/** @var array */
	protected $metadata = [];

	/** @var array */
	protected $hasSpawned = [];

	public static function fromSaveData(array $data) {
		return new FloatingText(Utils::parsePosition($data["pos"] . ", "), $data["text"]);
	}

	public function __construct(Position $pos, $text) {
		$this->pos = $pos;
		$this->rawText = $text;
		$this->eid = Entity::$entityCount++;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$flags |= 1 << Entity::DATA_FLAG_LEASHED;
		$this->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, (string)$text],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
		];
		$this->doTextUpdate();
		$this->spawnToAll();
	}

	public function getSaveData() : array {
		return [
			"pos" => "{$this->pos->x}, {$this->pos->y}, {$this->pos->z}, {$this->pos->getLevel()->getName()}",
			"text" => $this->getText()
		];
	}

	public function getId() {
		return $this->eid;
	}

	public function getText() : string {
		return $this->rawText;
	}

	public function setText(string $text) {
		$this->rawText = $text;
		$this->doTextUpdate();
	}

	public function getPosition() : Position {
		return $this->pos;
	}

	/**
	 * Spawn the floating text to an array of players
	 *
	 * @param array $players
	 */
	public function spawnToAll(array $players = []) {
		if(empty($players)) {
			$players = $this->pos->getLevel()->getPlayers();
		}
		foreach($players as $p) {
			$this->spawnTo($p);
		}
	}

	/**
	 * Despawn the floating text from an array of players
	 *
	 * @param array $players
	 */
	public function despawnFromAll(array $players = []) {
		if(empty($players)) {
			$players = $this->hasSpawned;
		}
		foreach($players as $p) {
			$this->despawnFrom($p);
		}
	}

	/**
	 * Spawn the floating text to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player !== $this and !isset($this->hasSpawned[$player->getId()])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddPlayerPacket();
			$pk->eid = $this->eid;
			$pk->username = "";
			$pk->uuid = UUID::fromRandom();
			$pk->x = $this->pos->x;
			$pk->y = $this->pos->y - 1.62;
			$pk->z = $this->pos->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->item = Item::get(0);
			$player->dataPacket($pk);
			$this->update([$player]);
		}
	}

	/**
	 * Despawn the floating text from a player
	 *
	 * @param Player $player
	 */
	public function despawnFrom(Player $player) {
		if(isset($this->hasSpawned[$player->getId()])) {
			unset($this->hasSpawned[$player->getId()]);
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->eid;
			$player->dataPacket($pk);
		}
	}

	/**
	 * @param Player[] $players
	 */
	public function update(array $players = []) {
		if(empty($players)) {
			$players = $this->hasSpawned;
		}
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->eid;
		$pk->metadata = $this->metadata;
		Server::getInstance()->broadcastPacket($players, $pk);
	}

	public function doTextUpdate() {
		$this->metadata[Entity::DATA_NAMETAG] = [Entity::DATA_TYPE_STRING, str_replace([
			"{players}",
			"{max_players}",
			"{level_players}",
			"{level_name}",
			"{break}"
		], [
			count(Server::getInstance()->getOnlinePlayers()),
			Server::getInstance()->getMaxPlayers(),
			count($this->pos->getLevel()->getPlayers()),
			$this->pos->getLevel()->getName(),
			"\n",

		], $this->rawText)];
	}

}