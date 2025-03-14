<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_om_lotto.php';

class Game_api_hp_2d3d extends Abstract_game_api_common_om_lotto {

    const ORIGINAL_TABLE = 'hp_2d3d_game_logs';
    
    public function getPlatformCode(){
        return HP_2D3D_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_TABLE;
        $this->currency = $this->getSystemInfo('currency', '');
    }

    public function getCurrency() {
        return $this->currency;
    }
}