<?php

/**
 * ConflictCore – CoreListener.php
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

use core\entity\text\FloatingText;
use core\language\LanguageManager;
use core\task\KickTask;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\TextFormat;

class CoreListener implements Listener {

	/** @var Main */
	private $plugin;

	/* Array of commands that a player can execute at any time */
	public static $whitelistedCommands = [
		"login",
		"authenticate",
		"l",
		"register",
		"claim",
		"r",
		"help",
		"h"
	];

	/* Array of banned commands */
	public static $bannedCommands = [
		"me",
		"op",
		"deop",
		"reload",
		"spawnpoint",
		"setworldspawn",
		"stop",
	];

	/**
	 * CoreListener constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @return mixed
	 */
	public function getPlugin() {
		return $this->getPlugin();
	}

	/**
	 * Make sure all worlds don't save and the time is locked
	 *
	 * @param LevelLoadEvent $event
	 */
	public function onLevelLoad(LevelLoadEvent $event) {
		$level = $event->getLevel();
		$level->setAutoSave(false);
		$level->setTime(6000);
		$level->stopTime();
	}

	/**
	 * Sets all players to a core player on creation
	 *
	 * @param PlayerCreationEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerCreation(PlayerCreationEvent $event) {
		$event->setPlayerClass(CorePlayer::class);
	}

	public function onPreLogin(PlayerPreLoginEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$ips = 0;
		$player->setDataProperty(Entity::DATA_FLAG_INVISIBLE, Entity::DATA_TYPE_BYTE, 1);
		/** @var CorePlayer $p */
		foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
			$p->hidePlayer($player);
			if(!$p->isAuthenticated())
				$player->hidePlayer($p);
			if(strtolower($p->getName()) === strtolower($player->getName())) {
				if($p->getAddress() === $player->getAddress()) {
					$event->setKickMessage(LanguageManager::getInstance()->translate("LOGIN_FROM_ANOTHER_LOCATION", "en"));
					$event->setCancelled(true);
				} else {
					$event->setKickMessage(LanguageManager::getInstance()->translate("ALREADY_ONLINE", "en"));
					$event->setCancelled(true);
				}
				return;
			}
			if($p->getAddress() === $player->getAddress()) $ips++;
		}
		if($ips >= 5) {
			$event->setKickMessage(LanguageManager::getInstance()->translate("MAX_CONNECTIONS", "en"));
			$event->setCancelled(true);
			return;
		}
		$this->plugin->getDatabaseManager()->getAuthDatabase()->login($player->getName());
		$player->setChatMuted(true);
	}

	public function onJoin(PlayerJoinEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		if(count($players = $this->plugin->getServer()->getOnlinePlayers()) > $this->plugin->getServer()->getMaxPlayers()) {
			if($player->hasPermission("vipslots.bypass")) {
				/** @var CorePlayer $p */
				foreach($players as $p) {
					if($p->getName() === $player->getName())
						continue;
					if(!$p->hasPermission("vipslots.bypass")) {
						$p->kick("§7           You have been kicked to make room for a VIP!§r\n§6Purchase a rank from §estore.conflictpe.net §6to reserve your slot!§r", false);
						break;
					}
				}
			} else {
				new KickTask($this->plugin, $player, "§7                             The server is full!§r\n§6Purchase a rank from §estore.conflictpe.net §6to reserve your slot!§r");
				return;
			}
		}
		foreach($this->plugin->floatingText as $text) {
			if($text instanceof FloatingText) $text->spawnTo($player);
		}
	}

	/**
	 * Handle player chatting
	 *
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onChat($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handles unauthenticated command execution
	 *
	 * @param PlayerCommandPreprocessEvent $event
	 */
	public function onCommandPreProcess(PlayerCommandPreprocessEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if(substr($message, 0, 1) === "/") {
			$command = substr($message, 1);
			$args = explode(" ", $command);

			if(!$player->isAuthenticated()) {
				if(in_array($args[0], self::$whitelistedCommands)) {
					// let the command do it's thing ;p
					return;
				} else {
					$event->setCancelled(true);
					$player->sendTranslatedMessage("MUST_AUTHENTICATE_FIRST", [], true);
					return;
				}
			}
			if(in_array(strtolower($args[0]), self::$bannedCommands)) {
				$event->setCancelled(true);
				$player->sendTranslatedMessage("COMMAND_BANNED");
				return;
			}
		}
	}

	/**
	 * Handle players breaking blocks
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onBreak($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle players placing blocks
	 *
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onPlace($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Despawn arrows when they land
	 *
	 * @param ProjectileHitEvent $event
	 */
	public function onArrowHit(ProjectileHitEvent $event) {
		$event->getEntity()->kill();
	}

	/**
	 * Handle player movement
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onMove($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle player item dropping
	 *
	 * @param PlayerDropItemEvent $event
	 */
	public function onItemDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onDrop($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle player interaction
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onInteract($event);
		} else {
			$event->setCancelled();
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		///** @var CorePlayer $player */
		//$player = $event->getPlayer();
		$event->setQuitMessage("");
		//$this->plugin->getDatabaseManager()->getAuthDatabase()->update($player->getName(), $player->getAuthData());
	}

	public function onKick(PlayerKickEvent $event) {
		///** @var CorePlayer $player */
		//$player = $event->getPlayer();
		$event->setQuitMessage("");
		if($event->getReason() === "disconnectionScreen.serverFull") {
			$event->setCancelled(true);
		}
		//$this->plugin->getDatabaseManager()->getAuthDatabase()->update($player->getName(), $player->getAuthData());
	}

}