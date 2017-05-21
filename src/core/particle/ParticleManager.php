<?php

/**
 * ConflictCore – ParticleManager.php
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
 * Created on 14/5/17 at 1:20 AM
 *
 */

namespace core\particle;

use core\CorePlayer;
use core\Main;
use core\particle\types\LavaParticleEffect;
use core\particle\types\PortalParticleEffect;
use core\particle\types\RainbowParticleEffect;
use core\particle\types\RedstoneParticleEffect;

class ParticleManager {

	/** @var Main */
	private $plugin;

	/** @var ParticleTask */
	private $particleTask;

	/** @var BaseParticle[] */
	private $registeredParticles = [];

	/**
	 * Class constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->registerDefaultParticles();
		$this->task = new ParticleTask($plugin);
	}

	public function registerDefaultParticles() {
		$this->registerParticle(LavaParticleEffect::class, ParticleTypes::PARTICLE_TYPE_LAVA);
		$this->registerParticle(PortalParticleEffect::class, ParticleTypes::PARTICLE_TYPE_PORTAL);
		$this->registerParticle(RainbowParticleEffect::class, ParticleTypes::PARTICLE_TYPE_RAINBOW);
		$this->registerParticle(RedstoneParticleEffect::class, ParticleTypes::PARTICLE_TYPE_REDSTONE);
	}

	/**
	 * Register a particle class
	 *
	 * @param string $class
	 * @param string $type
	 */
	public function registerParticle(string $class, string $type) {
		$reflection = new \ReflectionClass($class);
		if($reflection->isSubclassOf(BaseParticle::class) and !$reflection->isAbstract()) {
			$this->registeredParticles[$type] = new $class();
		}
	}

	/**
	 * @return BaseParticle[]
	 */
	public function getAllParticleEffects() {
		return $this->registeredParticles;
	}

	/**
	 * @param string $type
	 *
	 * @return null|string
	 */
	public function getParticleEffect(string $type) {
		return $this->registeredParticles[$type] ?? null;
	}

	/**
	 * Subscribe a player to a particle effect
	 *
	 * @param CorePlayer $player
	 * @param string $type
	 */
	public function subscribePlayerToEffect(CorePlayer $player, string $type) {
		$particle = $this->getParticleEffect($type);
		if($particle instanceof BaseParticle) {
			$player->setHasParticle(true);
			$particle->subscribe($player);
		}
	}

	/**
	 * Un-Subscribe a player from a particle effect
	 *
	 * @param $player
	 * @param string $type
	 */
	public function unSubscribePlayerFromEffect($player, string $type) {
		if($player instanceof CorePlayer) {
			$player = $player->getName();
		}
		$particle = $this->getParticleEffect($type);
		if($particle instanceof BaseParticle) {
			$particle->unSubscribe($player);
		}
	}

}
