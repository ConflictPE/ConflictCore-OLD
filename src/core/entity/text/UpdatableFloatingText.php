<?php

/**
 * ConflictCore – UpdatingFloatingText.php
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

use pocketmine\entity\Entity;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\Server;

class UpdatableFloatingText extends FloatingText {

	/**
	 * @param $text
	 * @param Player[] $players
	 */
	public function update($text, array $players = []) {
		if(empty($players)) {
			$players = Server::getInstance()->getOnlinePlayers();
		}
		$this->text = $text;
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->eid;
		$pk->metadata = [
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text]
		];
		Server::getInstance()->broadcastPacket($players, $pk);
	}

}