<?php
require_once dirname(__FILE__) . '/game_api_redgenn_seamless_api.php';

class Game_api_redgenn_playson_seamless_streamer extends Game_api_redgenn_seamless_api {
    public $original_transactions_table, $game_platform_id, $launcher_mode;
    public function getPlatformCode(){
        return REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = 'redgenn_playson_seamless_streamer_wallet_transactions';
        $this->game_platform_id = $this->getPlatformCode();
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');
    }
  
}

/*end of file*/

        
