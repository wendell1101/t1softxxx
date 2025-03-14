<?php

require_once dirname(__FILE__).'/game_api_common_fg.php';

class Game_api_fg_entaplay extends Game_api_common_fg 
{

    const ORIGINAL_TABLE_NAME = "fg_entaplay_game_logs";

    public function getPlatformCode()
    {
        return FG_ENTAPLAY_API;
    }

    public function __construct()
    {
        parent::__construct();
    }

}

/*end of file*/
