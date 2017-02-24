<?php

/**
 * ConflictCore – CoreCommandMap.php
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

use core\command\commands\ChangePasswordCommand;
use core\command\commands\DumpSkinCommand;
use core\command\commands\LoginCommand;
use core\command\commands\RegisterCommand;
use core\command\commands\StaffChatCommand;
use core\command\commands\TestCommand;
use core\command\commands\TogglePlayersCommand;
use core\Main;

/**
 * Manages all commands
 */
class CoreCommandMap {

	/** @var CoreCommand[] */
	protected $commands = [];

	/** @var $plugin */
	private $plugin;

	/**
	 * CommandMap constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->setDefaultCommands();
	}

	/**
	 * Set the default commands
	 */
	public function setDefaultCommands() {
		$this->registerAll([
			new ChangePasswordCommand($this->plugin),
			new DumpSkinCommand($this->plugin),
			new LoginCommand($this->plugin),
			new RegisterCommand($this->plugin),
			new StaffChatCommand($this->plugin),
			new TestCommand($this->plugin),
			new TogglePlayersCommand($this->plugin),
		]);
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	/**
	 * Register an array of commands
	 *
	 * @param array $commands
	 */
	public function registerAll(array $commands) {
		foreach($commands as $command) {
			$this->register($command);
		}
	}

	/**
	 * Register a command
	 *
	 * @param CoreCommand $command
	 * @param string $fallbackPrefix
	 *
	 * @return bool
	 */
	public function register(CoreCommand $command, $fallbackPrefix = "cc") {
		if($command instanceof CoreCommand) {
			$this->plugin->getServer()->getCommandMap()->register($fallbackPrefix, $command);
			$this->commands[strtolower($command->getName())] = $command;
		}
		return false;
	}

	/**
	 * Unregisters all commands
	 */
	public function clearCommands() {
		foreach($this->commands as $command) {
			$this->unregister($command);
		}
		$this->commands = [];
		$this->setDefaultCommands();
	}

	/**
	 * Unregister a command
	 *
	 * @param CoreCommand $command
	 */
	public function unregister(CoreCommand $command) {
		unset($this->commands[strtolower($command->getName())]);
	}

	/**
	 * Get a command
	 *
	 * @param $name
	 *
	 * @return CoreCommand|null
	 */
	public function getCommand($name) {
		if(isset($this->commands[$name])) {
			return $this->commands[$name];
		}
		return null;
	}

	/**
	 * @return CoreCommand[]
	 */
	public function getCommands() {
		return $this->commands;
	}

	public function __destruct() {
		$this->close();
	}

	public function close() {
		foreach($this->commands as $command) {
			$this->unregister($command);
		}
		unset($this->commands, $this->plugin);
	}

}