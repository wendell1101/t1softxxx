<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_queen_maker.php';

class Game_api_king_midas extends Abstract_game_api_common_queen_maker {

    const ORIGINAL_TABLE = 'king_midas_game_logs';
    
    public function getPlatformCode(){
        return KING_MIDAS_GAME_API;
    }


    public function __construct(){
        parent::__construct();
        $this->original_game_logs_table = self::ORIGINAL_TABLE;
        $this->currency = $this->getSystemInfo('currency', 'IDR');
    }

    public function getCurrency() {
        return $this->currency;
    }
}