<?php

/**
 * ConflictCore – Main.php
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

use core\command\CoreCommandMap;
use core\database\CoreDatabaseManager;
use core\database\DatabaseManager;
use core\entity\antihack\KillAuraDetector;
use core\entity\pets\PetsManager;
use core\entity\text\FloatingText;
use core\entity\text\FloatingTextManager;
use core\language\LanguageManager;
use core\network\NetworkManager;
use core\task\ReportErrorTask;
use core\task\RestartTask;
use core\particle\ParticleManager;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

	/** @var int */
	protected $loadTime;

	/** @var Main */
	private static $instance;

	/** @var Config */
	protected $errorLog;

	/** @var Config */
	private $settings;

	/** @var CoreCommandMap */
	private $commandMap;

	/** @var CoreDatabaseManager */
	private $databaseManager;

	/** @var CoreListener */
	private $listener;

	/** @var LanguageManager */
	private $languageManager;

	/** @var FloatingTextManager */
	private $floatingTextManager;

	/** @var NetworkManager */
	private $networkManager;

	/** @var PetsManager|null */
	private $petsManager;

	/** @var ParticleManager|null */
	private $particleManager;

	/** @var RestartTask */
	private $restartTask;

	/** @var bool */
	public static $testing = false;

	/** @var bool */
	public static $debug = false;

	/** Resource files & paths */
	const SETTINGS_FILE = "Settings.yml";
	const ERROR_REPORT_LOG = "error_log.json";
	const DATA_FOLDER = "data/";
	const FLOATING_TEXT_DATA_FILE = "data/holograms.json";

	public function onLoad() {
		$this->loadTime = microtime(true);
		self::$instance = $this;
		$this->debug("Loading configs...");
		$this->loadConfigs();
	}

	public function onEnable() {
		Main::$testing = $this->settings->getNested("settings.testing-mode", false);
		Main::$debug = $this->settings->getNested("settings.enable-debug", false);
		if(Main::$testing) $this->enableTesting();
		$this->debug("Enabling command map...");
		$this->setCommandMap();
		$this->debug("Enabling network manager...");
		$this->setNetworkManager();
		$this->debug("Initializing database manager...");
		$this->setDatabaseManager();
		$this->debug("Setting event listener...");
		$this->setListener();
		$this->debug("Enabling language manager...");
		$this->setLanguageManager();
		$this->debug("Enabling floating text manager...");
		$this->setFloatingTextManager();
		$this->debug("Applying finishing touches...");
		$this->getServer()->getNetwork()->setName($this->languageManager->translate("SERVER_NAME", "en"));
		$this->restartTask = new RestartTask($this);
		$server = $this->getServer();
		foreach($server->getLevels() as $level) {
			$level->setTime(6000);
			$level->stopTime();
			$this->floatingTextManager->onLevelLoad($level);
		}
		$this->getLogger()->info("ConflictCore enabled on {$server->getIp()}:{$server->getPort()} with {$server->getMaxPlayers()} slots! (" . round(microtime(true) - $this->loadTime, 3) . "s)!");
	}

	/**
	 * @return Main
	 */
	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * Safely shutdown the plugin
	 */
	public function onDisable() {
		$server = $this->getServer();
		$server->setAutoSave(true);
		$server->doAutoSave();
		/** @var CorePlayer $p */
		foreach($this->getServer()->getOnlinePlayers() as $p) $p->kick($this->getLanguageManager()->translateForPlayer($p, "SERVER_RESTART"), false);
		$this->errorLog->save(false);
		$this->networkManager->getServer()->setOnline(false);
		$this->databaseManager->getNetworkDatabase()->sync();
		$this->databaseManager->close();
		$this->networkManager->close();
	}

	/**
	 * Enables all the testing features
	 */
	public function enableTesting() {
		$this->debug("Enabling testing features...");
		Entity::registerEntity(KillAuraDetector::class, true);
		set_error_handler([$this, "errorHandler"], E_ALL);
	}

	/**
	 * Save all the configs and get them ready for use
	 */
	public function loadConfigs() {
		$this->saveResource(self::SETTINGS_FILE);
		$this->settings = new Config($this->getDataFolder() . self::SETTINGS_FILE, Config::YAML);
		$this->saveResource(self::ERROR_REPORT_LOG);
		$this->errorLog = new Config($this->getDataFolder() . self::ERROR_REPORT_LOG, Config::JSON);
		if(!is_dir($this->getDataFolder() . self::DATA_FOLDER)) @mkdir($this->getDataFolder() . self::DATA_FOLDER);
		$this->saveResource($this->getDataFolder() . self::FLOATING_TEXT_DATA_FILE);
	}

	/**
	 * Print a message through the debug channel
	 *
	 * @param string $message
	 */
	public function debug(string $message) {
		if(Main::$debug) $this->getLogger()->debug($message);
	}

	/**
	 * @return Config
	 */
	public function getSettings() : Config {
		return $this->settings;
	}

	/**
	 * @return CoreCommandMap
	 */
	public function getCommandMap() : CoreCommandMap {
		return $this->commandMap;
	}

	/**
	 * @return CoreDatabaseManager
	 */
	public function getDatabaseManager() : CoreDatabaseManager {
		return $this->databaseManager;
	}

	/**
	 * @return CoreListener
	 */
	public function getListener() : CoreListener {
		return $this->listener;
	}

	/**
	 * @return LanguageManager
	 */
	public function getLanguageManager() : LanguageManager {
		return $this->languageManager;
	}

	/**
	 * @return FloatingTextManager
	 */
	public function getFloatingTextManager() : FloatingTextManager {
		return $this->floatingTextManager;
	}

	/**
	 * @return NetworkManager
	 */
	public function getNetworkManager() : NetworkManager {
		return $this->networkManager;
	}

	/**
	 * @return PetsManager|null
	 */
	public function getPetsManager() {
		return $this->petsManager;
	}

	/**
	 * @return ParticleManager
	 */
	public function getParticleManager() {
		return $this->particleManager;
	}

	/**
	 * Set the command map
	 */
	public function setCommandMap() {
		$this->commandMap = new CoreCommandMap($this);
	}

	/**
	 * Set the event listener
	 */
	public function setListener() {
		$this->listener = new CoreListener($this);
	}

	/**
	 * Set the database manager
	 */
	public function setDatabaseManager() {
		$this->databaseManager = new CoreDatabaseManager($this);
	}

	/**
	 * Set the language manager
	 */
	public function setLanguageManager() {
		$this->languageManager = new LanguageManager($this);
	}

	/**
	 * Set the floating text manager
	 */
	public function setFloatingTextManager() {
		$this->floatingTextManager = new FloatingTextManager($this);
	}

	/**
	 * Set the network manager
	 */
	public function setNetworkManager() {
		$this->networkManager = new NetworkManager($this);
	}

	/**
	 * Set the pet manager
	 */
	public function setPetsManager() {
		$this->petsManager = new PetsManager($this);
	}

	/**
	 * Set the particle manager
	 */
	public function setParticleManager() {
		$this->particleManager = new ParticleManager($this);
	}

	/**
	 * Stop loaded chunks from being unloaded
	 */
	public function freezeLoadedChunks() {
		$chunks = $this->getServer()->getDefaultLevel()->getProvider()->getLoadedChunks();
		foreach($chunks as $chunk) {
			/** @noinspection PhpUndefinedFieldInspection */
			$chunk->allowUnload = false;
		}
	}

	/**
	 * Our custom error handler
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $context
	 * @param null $trace
	 *
	 * @return bool
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null) {
		$errorConversion = [E_ERROR => "E_ERROR", E_WARNING => "E_WARNING", E_PARSE => "E_PARSE", E_NOTICE => "E_NOTICE", E_CORE_ERROR => "E_CORE_ERROR", E_CORE_WARNING => "E_CORE_WARNING", E_COMPILE_ERROR => "E_COMPILE_ERROR", E_COMPILE_WARNING => "E_COMPILE_WARNING", E_USER_ERROR => "E_USER_ERROR", E_USER_WARNING => "E_USER_WARNING", E_USER_NOTICE => "E_USER_NOTICE", E_STRICT => "E_STRICT", E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR", E_DEPRECATED => "E_DEPRECATED", E_USER_DEPRECATED => "E_USER_DEPRECATED",];
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if(($pos = strpos($errstr, "\n")) !== false) {
			$errstr = substr($errstr, 0, $pos);
		}

		$error = "An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline" . PHP_EOL;

		if(!$this->errorLog->exists(strtolower($error))) {
			$this->getLogger()->debug("Logging error to error reporting channel, Error: {$errno} \"{$errstr}\"");
			$this->errorLog->set(strtolower($error), true);
			$this->errorLog->save(true);
			$error .= "```";
			foreach(($trace = \core\Utils::getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
				$error .= $line . PHP_EOL;
			}
			$error .= "```";
			$this->getServer()->getScheduler()->scheduleAsyncTask(new ReportErrorTask($error . PHP_EOL .  "Server: {$this->getServer()->getIp()}:{$this->getServer()->getPort()}"));
		}

		return true;
	}

	/**
	 * Uses SHA-512 [http://en.wikipedia.org/wiki/SHA-2] and Whirlpool
	 * [http://en.wikipedia.org/wiki/Whirlpool_(cryptography)]
	 *
	 * Both of them have an output of 512 bits. Even if one of them is broken in the future, you have to break both
	 * of them at the same time due to being hashed separately and then XORed to mix their results equally.
	 *
	 * @param string $salt
	 * @param string $password
	 *
	 * @return string[128] hex 512-bit hash
	 */
	public static function hash($salt, $password) {
		$salt = strtolower($salt); // temp fix for password in chat check :p
		return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
	}

}