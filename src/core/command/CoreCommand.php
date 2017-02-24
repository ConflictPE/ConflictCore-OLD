<?php

/**
 * ConflictCore – CoreCommand.php
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

namespace core\command;

use core\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class CoreCommand extends Command implements PluginIdentifiableCommand {

	/** @var Main */
	private $plugin;

	/**
	 * DefaultCommand constructor.
	 *
	 * @param Main $plugin
	 * @param string $name
	 * @param null|string $description
	 * @param \string $usage
	 * @param string[] $aliases
	 */
	public function __construct(Main $plugin, $name, $description, $usage, array $aliases = []) {
		parent::__construct($name, $description, $usage, $aliases);
		$this->plugin = $plugin;
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	/**
	 * Initial command call
	 *
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $commandLabel, array $args) {
		if($this->testPermission($sender)) {
			return $this->run($sender, $args);
		} else {
			$sender->sendMessage($this->getPermissionMessage());
		}
		return false;
	}

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return mixed
	 */
	protected abstract function run(CommandSender $sender, array $args);

}