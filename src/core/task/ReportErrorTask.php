<?php

/**
 * ConflictCore – ReportErrorTask.php
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

namespace core\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Utils;

class ReportErrorTask extends AsyncTask {

	/** @var string */
	private $error;

	public function __construct($error) {
		$this->error = $error;
	}

	/**
	 * Attempt to report the error to slack
	 */
	public function onRun() {
		$this->reportErrorSlack($this->error);
	}

	/**
	 * Report an error to the slack channel
	 *
	 * @param string $errorMessage
	 */
	public function reportErrorSlack($errorMessage) {
		Utils::postURL("https://slack.com/api/chat.postMessage", [
			"token" => "xoxb-76326966178-gDlv1M29RWgtHBFK46Z75Jzd",
			"channel" => "#errors",
			"text" => "{$errorMessage}",
			"as_user" => true
		]);
	}

}