<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_vivogaming_seamless.php';

class Game_api_vivogaming_seamless extends Abstract_game_api_common_vivogaming_seamless {
    public $game_type_lobby_supported;
    
    public function getPlatformCode(){
        return VIVOGAMING_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getSystemInfo('original_gamelogs_table', 'vivogaming_seamless_game_logs');
        $this->original_transactions_table = $this->getSystemInfo('original_transactions_table', 'vivogaming_transactions');
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', ['live_dealer']);

        if ($this->use_monthly_transactions_table) {
            $this->ymt_initialize($this->original_transactions_table, true);
            $this->original_transactions_table = $this->ymt_get_current_year_month_table();
        }
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}

/*end of file*/

        
