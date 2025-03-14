<?php
require_once dirname(__FILE__).'/game_api_common_fg.php';

class Game_api_fg extends Game_api_common_fg 
{

	const ORIGINAL_TABLE_NAME = "fg_game_logs";

	public function __construct()
	{
		parent::__construct();
	}

	public function getPlatformCode() {
		return FG_API;
	}
}

/*end of file*/