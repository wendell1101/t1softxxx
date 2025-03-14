<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_phoenix_chess_card_poker.php';

/**
 *
 *
 * @category Game API
 * @copyright 2013-2022 tot
 *
 */
class Game_api_phoenix_chess_card_poker extends Abstract_game_api_common_phoenix_chess_card_poker {

    public function getOriginalTable(){
        return 'phoenix_chess_card_game_logs';
    }

    public function getPlatformCode(){
        return PHOENIX_CHESS_CARD_POKER_API;
    }

}
