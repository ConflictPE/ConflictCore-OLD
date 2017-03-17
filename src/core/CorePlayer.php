<?php

/**
 * ConflictCore – CorePlayer.php
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

namespace core;

use core\entity\antihack\KillAuraDetector;
use core\language\LanguageManager;
use core\task\CheckMessageTask;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\plugin\PluginException;

class CorePlayer extends Player {

	/** @var Main */
	private $core;

	/** @var bool */
	private $registered = false;

	/** @var bool */
	private $authenticated = false;

	/** @var bool */
	private $locked = false;

	/** @var string */
	private $lockReason = "";

	/** @var string */
	private $hash = "";

	/** @var string */
	private $email = "";

	/** @var int */
	private $loginTime = 0;

	/** @var int */
	private $timePlayed = 0;

	/** @var int */
	private $state = self::STATE_LOBBY;

	/** @var string */
	private $registrationStatus = self::AUTH_PASSWORD;

	/** @var bool */
	private $chatMuted = false;

	/** @var string */
	private $lang = "en";

	/** @var int */
	private $coins = 0;

	/** @var string */
	private $lastMessage = "";

	/** @var int */
	private $lastMessageTime = 0;

	/** @var int */
	private $lastDamagedTime = 0;

	/** @var int */
	private $loginAttempts = 0;

	/** @var int */
	private $killAuraTriggers = 0;

	/** @var int */
	private $flyChances = 0;

	/** @var bool */
	private $showPlayers = true;

	/** @var bool */
	private $isSubscribedToStaffChat = false;

	/** @var bool */
	private $hasHologramIdSession = false;

	/** Game statuses */
	const STATE_LOBBY = "state.lobby";
	const STATE_PLAYING = "state.playing";
	const STATE_SPECTATING = "state.spectating";

	/** Authentication statuses */
	const AUTH_PASSWORD = "auth.password";
	const AUTH_CONFIRM = "auth.confirm";
	const AUTH_EMAIL = "auth.email";

	/**
	 * Make sure the core plugin is enabled before an instance is constructed
	 *
	 * @param SourceInterface $interface
	 * @param null $clientID
	 * @param string $ip
	 * @param int $port
	 */
	public function __construct(SourceInterface $interface, $clientID, $ip, $port) {
		parent::__construct($interface, $clientID, $ip, $port);
		if(($plugin = $this->getServer()->getPluginManager()->getPlugin("Components")) instanceof Main and $plugin->isEnabled()){
			$this->core = $plugin;
		} else {
			$this->kick("Error");
			throw new PluginException("Core plugin isn't loaded!");
		}
	}

	/**
	 * @return bool
	 */
	public function isRegistered() {
		return $this->registered;
	}

	/**
	 * @return bool
	 */
	public function isAuthenticated() {
		return $this->authenticated;
	}

	/**
	 * @return mixed
	 */
	public function isLocked() {
		return $this->isLocked();
	}

	/**
	 * @return string
	 */
	public function getLockReason() {
		return $this->lockReason;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return int
	 */
	public function getLoginTime() {
		return $this->loginTime;
	}

	/**
	 * @return int
	 */
	public function getTimePlayed() {
		return $this->timePlayed;
	}

	/**
	 * @return int
	 */
	public function getTotalTimePlayed() {
		return (time() - $this->loginTime) + $this->timePlayed;
	}

	/**
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @return string
	 */
	public function getRegistrationState() {
		return $this->registrationStatus;
	}

	/**
	 * @return bool
	 */
	public function hasChatMuted() {
		return $this->chatMuted;
	}

	/**
	 * @return string
	 */
	public function getLanguageAbbreviation() {
		return $this->lang;
	}

	/**
	 * @return int
	 */
	public function getCoins() {
		return $this->coins;
	}

	/**
	 * @return string
	 */
	public function getLastMessage() {
		return $this->lastMessage;
	}

	/**
	 * @return int
	 */
	public function getLastMessageTime() {
		return $this->lastMessageTime;
	}

	/**
	 * @return int
	 */
	public function getLoginAttempts() {
		return $this->loginAttempts;
	}

	/**
	 * @return int
	 */
	public function getKillAuraTriggers() {
		return $this->killAuraTriggers;
	}

	/**
	 * @return bool
	 */
	public function hasPlayersVisible() {
		return $this->showPlayers;
	}

	/**
	 * @return bool
	 */
	public function isSubscribedToStaffChat() {
		return $this->isSubscribedToStaffChat;
	}

	/**
	 * @return bool
	 */
	public function hasHologramIdSession() {
		return $this->hasHologramIdSession;
	}

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}

	/**
	 * @param $value
	 */
	public function setRegistered($value = true) {
		$this->registered = $value;
	}

	/**
	 * @param bool $authenticated
	 */
	public function setAuthenticated($authenticated = true) {
		$this->authenticated = $authenticated;
		$this->inventory->sendContents($this);
		$this->inventory->sendArmorContents($this);
		if($this->isSurvival() or $this->isAdventure()) return;
		$pk = new ContainerSetContentPacket();
		$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
		if($this->gamemode === Player::CREATIVE) {
			$pk->slots = array_merge(Item::getCreativeItems(), $this->personalCreativeItems);
		}
		$this->dataPacket($pk);
	}

	/**
	 * @param bool $value
	 */
	public function setLocked($value = true) {
		$this->locked = $value;
	}

	/**
	 * @param string $reason
	 */
	public function setLockReason($reason = "") {
		$this->lockReason = $reason;
	}

	/**
	 * @param string $hash
	 */
	public function setHash($hash) {
		$this->hash = $hash;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Set the last time a players login date was updated
	 */
	public function setLoginTime() {
		$this->loginTime = time();
	}

	/**
	 * @param $time
	 */
	public function setTimePlayed($time) {
		$this->timePlayed = $time;
	}

	/**
	 * @param int $state
	 */
	public function setStatus($state) {
		$this->state = $state;
	}

	/** @param string $status */
	public function setRegistrationStatus($status) {
		$this->registrationStatus = $status;
	}

	/**
	 * @param $value
	 */
	public function setChatMuted($value) {
		$this->chatMuted = $value;
	}

	/**
	 * @param string $abbreviation
	 */
	public function setLanguageAbbreviation($abbreviation) {
		$this->lang = $abbreviation;
	}

	/**
	 * @param $value
	 */
	public function addCoins($value) {
		$this->coins += $value;
	}

	/**
	 * @param $message
	 */
	public function setLastMessage($message) {
		$this->lastMessage = $message;
		$this->lastMessageTime = floor(microtime(true));
	}

	/**
	 * Add a failed login attempt for the player
	 */
	public function addLoginAttempt() {
		$this->loginAttempts++;
	}

	/**
	 * @param bool $value
	 */
	public function setPlayersVisible($value = true) {
		$this->showPlayers = $value;
		foreach($this->server->getOnlinePlayers() as $p) {
			if($value) {
				if($p->distance($this) <= 40) $p->spawnTo($this);
			} else {
				$p->despawnFrom($this);
			}
		}
	}

	/**
	 * @param bool $value
	 */
	public function subscribeToStaffChat($value = true) {
		$this->isSubscribedToStaffChat = $value;
	}

	/**
	 * @param bool $value
	 */
	public function setHologramIdSession($value = true) {
		$this->hasHologramIdSession = $value;
	}

	/**
	 * Increases the amount of times a player has been detected for having kill aura
	 */
	public function addKillAuraTrigger() {
		$this->killAuraTriggers++;
		$this->checkKillAuraTriggers();
	}

	/**
	 * Checks the amount of times a player has triggered a kill aura detector and handles the result accordingly
	 */
	public function checkKillAuraTriggers() {
		if($this->killAuraTriggers >= 8) $this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD", ["Kill Aura"]));
	}

	/**
	 * Spawn the kill aura detection entities
	 */
	public function spawnKillAuraDetectors() {
		if(Main::$testing) {
			$nbt = new CompoundTag("", [
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $this->x),
					new DoubleTag("", $this->y),
					new DoubleTag("", $this->z)
				]),
				"Motion" => new ListTag("Motion", [
					new DoubleTag("", 0),
					new DoubleTag("", 0),
					new DoubleTag("", 0)
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", 180),
					new FloatTag("", 0)
				]),
			]);
			$entity = Entity::createEntity("KillAuraDetector", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), clone $nbt);
			if($entity instanceof KillAuraDetector) {
				$entity->setTarget($this);
				$entity->setOffset(new Vector3(-1, 2.5, -1));
			} else {
				$entity->kill();
			}
			$entity = Entity::createEntity("KillAuraDetector", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), clone $nbt);
			if($entity instanceof KillAuraDetector) {
				$entity->setTarget($this);
				$entity->setOffset(new Vector3(1, -2.5, 1));
			} else {
				$entity->kill();
			}
		}
	}

	public function handleAuth(string $message) {
		if($this->isRegistered()) {
			if(hash_equals($this->getHash(), Utils::hash(strtolower($this->getName()), $message))) {
				$this->chatMuted = false;
				$this->setAuthenticated();
				$this->setLoginTime();
				/** @var CorePlayer $p */
				foreach($this->getServer()->getOnlinePlayers() as $p) {
					$p->showPlayer($this);
				}
				$this->spawnKillAuraDetectors();
				$this->sendTranslatedMessage("LOGIN_SUCCESS", [], true);
			} else {
				$this->addLoginAttempt();
				if($this->loginAttempts >= 3) {
					$this->kick($this->core->getLanguageManager()->translateForPlayer($this, "TOO_MANY_LOGIN_ATTEMPTS"));
				}
				$this->sendTranslatedMessage("INCORRECT_PASSWORD", [], true);
			}
		} else {
			switch($this->registrationStatus) {
				// password
				default:
					$this->hash = Utils::hash(strtolower($this->getName()), $message);
					$this->registrationStatus = self::AUTH_CONFIRM;
					$this->sendTranslatedMessage("CONFIRM_PASSWORD_PROMPT", [], true);
					break;
				// password confirmation
				case self::AUTH_CONFIRM:
					if(hash_equals($this->getHash(), Utils::hash(strtolower($this->getName()), $message))) {
						$this->registrationStatus = self::AUTH_EMAIL;
						$this->sendTranslatedMessage("EMAIL_PROMPT", [], true);
						break;
					}
					$this->sendTranslatedMessage("PASSWORDS_NO_MATCH", [], true);
					$this->registrationStatus = self::AUTH_PASSWORD;
					$this->sendTranslatedMessage("REGISTER_PROMPT", [], true);
					break;
				// email
				case self::AUTH_EMAIL:
					if(filter_var($message, FILTER_VALIDATE_EMAIL)) {
						$this->email = strtolower($message);
						$this->chatMuted = false;
						$this->core->getDatabaseManager()->getAuthDatabase()->register($this->getName(), $this->getHash(), $this->getEmail());
						break;
					}
					$this->sendTranslatedMessage("INVALID_EMAIL", [], true);
					break;
			}
		}
	}

	/**
	 * Returns an array of data to be saved to the database
	 *
	 * @return array
	 */
	public function getAuthData() {
		return [
			"ip" => $this->getAddress(),
			"lang" => $this->lang,
			"coins" => $this->coins,
			"timePlayed" => time() - $this->loginTime,
			"lastLogin" => $this->loginTime
		];
	}

	/**
	 * @param $key
	 * @param array $args
	 * @param bool $isImportant
	 */
	public function sendTranslatedMessage($key, array $args = [], $isImportant = false) {
		$this->sendMessage($this->core->getLanguageManager()->translateForPlayer($this, $key, $args), $isImportant);
	}

	/**
	 * @param \pocketmine\event\TextContainer|string $message
	 * @param bool $isImportant
	 *
	 * @return bool
	 */
	public function sendMessage($message, $isImportant = false) {
		if(!$isImportant and $this->chatMuted) {
			return false;
		}
		parent::sendMessage($message);
		return true;
	}

	/**
	 * @param float $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool
	 */
	public function attack($damage, EntityDamageEvent $source) {
		$result = true;
		if($this->authenticated) {
			$result = parent::attack($damage, $source);
			if(!$source->isCancelled()) $this->lastDamagedTime = microtime(true);
			return $result;
		} else {
			$source->setCancelled(true);
		}
		return false;
	}

	///**
	// * Ensures players don't actually die
	// *
	// * @param bool $forReal
	// * @return bool
	// */
	//public function kill($forReal = false) {
	//
	//}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player instanceof CorePlayer and $player->showPlayers) parent::spawnTo($player);
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$message = $event->getMessage();
		$event->setCancelled();
		if($this->authenticated) {
			$start = microtime(true);
			if(($key = $this->getCore()->getLanguageManager()->check($message)) !== false) {
				$this->sendTranslatedMessage(($key === "" ? "BLOCKED_MESSAGE" : $key), [], true);
			} else {
				//$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckMessageTask($this->getName(), $this->hash, $this->lastMessage, $this->lastMessageTime, $message, $this->chatMuted));
				if(!$this->hasChatMuted()) {
					if(!LanguageManager::containsPassword($this->getName(), $message, $this->hash)) {
						if(floor(microtime(true) - $this->lastMessageTime) >= 3) {
							similar_text(strtolower($this->lastMessage), strtolower($message), $percent);
							if(round($percent) < 80) {
								if($this->isSubscribedToStaffChat) {
									/** @var CorePlayer $p */
									foreach($this->getCore()->getServer()->getOnlinePlayers() as $p) {
										if($p->isSubscribedToStaffChat()) $p->sendMessage("§7[§6SC§7] §r§l{$this->getName()}§r§7:§r {$message}§r");
									}
								} else {
									$event->setCancelled(false);
								}
								$this->setLastMessage($message);
							} else {
								$this->sendTranslatedMessage("MESSAGES_TOO_SIMILAR", [], true);
							}
						} else {
							$this->sendTranslatedMessage("CHAT_COOLDOWN", [], true);
						}
					} else {
						$this->sendTranslatedMessage("PASSWORD_IN_CHAT", [], true);
					}
				} else {
					$this->sendTranslatedMessage("CANNOT_CHAT_WHILE_MUTED", [], true);
				}
			}
			$end = microtime(true);
			$this->getCore()->debug("MESSAGE CHECK TIME: " . round($end - $start, 3) . "s ");
		} else {
			$this->handleAuth($message);
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) {
		if(!$this->isAuthenticated()){
			$from = $event->getFrom();
			$to = clone $event->getTo();
			if($to->x != $from->x and $to->z != $from->z){
				$to->x = $from->x;
				$to->z = $from->z;
				$event->setTo($to);
			}
		} else {
			$y = $event->getTo()->getY();
			if($y <= 0 or $y >= 112) {
				$this->kill();
			} else {
				$block = $this->getLevel()->getBlock(new Vector3($this->getFloorX(), $this->getFloorY() - 1, $this->getFloorZ()));
				if(($this->isSurvival() or $this->isAdventure()) and round($event->getTo()->getY() - $event->getFrom()->getY(), 3) >= 0.375 && $block->getId() === Block::AIR and floor(microtime(true) - $this->lastDamagedTime) >= 5/* and !$block->getId() === Block::LAVA and !$block->getId() === Block::STILL_LAVA and !$block->getId() === Block::WATER and !$block->getId() === Block::STILL_WATER and !$block->getId() === Block::RED_SANDSTONE_SLAB and !$block->getId() === Block::ACTIVATOR_RAIL and !$block->getId() === Block::SLAB*/) {
					$this->flyChances++;
				} else {
					$this->flyChances = 0;
				}
				if($this->flyChances >= 6) {
					$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD", ["Fly"]), false);
				}
			}
		}
	}

	/**
	 * @param PlayerDropItemEvent $event
	 */
	public function onDrop(PlayerDropItemEvent $event) {
		if(!$this->authenticated) $event->setCancelled(true);
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) {
		if(!$this->authenticated) $event->setCancelled(true);
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) {
		if(!$this->authenticated) $event->setCancelled(true);
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) {
		if(!$this->authenticated) $event->setCancelled(true);
	}

	/**
	 * Mask the players inventory until they authenticate
	 *
	 * @param DataPacket $packet
	 * @param bool $needACK
	 *
	 * @return bool|int
	 */
	public function dataPacket(DataPacket $packet, $needACK = false){
		if(!$this->authenticated and ($packet instanceof ContainerSetContentPacket or $packet instanceof ContainerSetSlotPacket)) return true;
		return parent::dataPacket($packet, $needACK);
	}

}