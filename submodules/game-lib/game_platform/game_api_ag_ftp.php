<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting the constant variable of the AG_FTP
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @deprecated 2.0
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10 
 * @copyright 2013-2022 tot
 */
class Game_api_ag_ftp extends Abstract_game_api {

	function getPlatformCode() {
		return AG_FTP;
	}

}

/*end of file*/