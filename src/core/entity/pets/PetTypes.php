<?php

/**
 * ConflictCore – PetTypes.php
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
 * Created on 11/5/17 at 4:44 PM
 *
 */

namespace core\entity\pets;

/**
 * List of all available pet types
 */
interface PetTypes {

	const PET_TYPE_BABY_COW = "pet.type.baby.cow";
	const PET_TYPE_BABY_PIG = "pet.type.baby.pig";
	const PET_TYPE_CHICKEN = "pet.type.chicken";
	const PET_TYPE_OCELOT = "pet.type.ocelot";
	const PET_TYPE_PIG = "pet.type.pig";
	const PET_TYPE_RABBIT = "pet.type.rabbit";
	const PET_TYPE_WOLF = "pet.type.wolf";

}