<?php
if( ! defined('BASEPATH')){
    exit('No direct Script Access Allowed');
}

require_once dirname(__FILE__) . '/abstract_game_api_common_seamless_lucky_streak.php';

class Game_api_lucky_streak_seamless_thb1_api extends Abstract_game_api_common_seamless_lucky_streak
{
    /**
     * The Game currency
     * 
     * @var const CURRENCY_TYPE
     */
    const CURRENCY_TYPE = 'THB';

    const ORIGINAL_TABLE = 'lucky_streak_seamless_thb1_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    public function __construct()
    {
        parent::__construct();
        
        $this->currencyType = self::CURRENCY_TYPE;
        $this->original_table = self::ORIGINAL_TABLE;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }

    /** 
     * Abstract in Parent Class
     * Constant in apis.php, Game API unique ID
     * 
     * @return array
    */
    public function getPlatformCode()
    {
        return LUCKY_STREAK_SEAMLESS_THB1_API;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }


}