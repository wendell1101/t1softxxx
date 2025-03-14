<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_lucky_game.php';

class Game_api_lucky_game extends Abstract_game_api_common_lucky_game {

    public function getPlatformCode(){
        return LUCKY_GAME_CHESS_POKER_API;
    }

    public function __construct(){
        parent::__construct();
    }

    public function getOriginalTable(){
        return 'lucky_game_game_logs';
    }
}
/*end of file*/