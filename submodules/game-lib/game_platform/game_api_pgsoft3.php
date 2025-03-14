<?php
require_once dirname(__FILE__) . '/game_api_pgsoft.php';

class Game_api_pgsoft3 extends game_api_pgsoft {
    
    public function getPlatformCode(){
        return PGSOFT3_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = 'pgsoft3_game_logs';
    }
}

/*end of file*/

        
