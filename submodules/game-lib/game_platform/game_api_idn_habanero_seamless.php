<?php
require_once dirname(__FILE__) . '/game_api_habanero_seamless.php';

class Game_api_idn_habanero_seamless extends Game_api_habanero_seamless {
    public function __construct() {
        parent::__construct();
        $this->original_transactions_table = 'idn_habanero_transactions';
        $this->createTableLike($this->original_transactions_table, self::ORIGINAL_TRANSACTIONS);

        // initiate year month table
        $this->ymt_init();
    }

    public function getPlatformCode() {
        return IDN_HABANERO_SEAMLESS_GAMING_API;
    }

    public function getTransactionsTable() {
        return $this->original_transactions_table;
    }
}
/*end of file*/