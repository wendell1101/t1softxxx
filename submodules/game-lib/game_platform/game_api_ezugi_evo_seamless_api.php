<?php
require_once dirname(__FILE__) . '/game_api_ezugi_seamless_api.php';

/**
 * EZUGI Integration
 * OGP-23012
 *
 * @author  Sony
 */

class Game_api_ezugi_evo_seamless_api extends Game_api_ezugi_seamless_api {

    public function __construct(){     
        parent::__construct();

        $this->use_ezugi_evo_seamless_wallet_transactions = $this->getSystemInfo('use_ezugi_evo_seamless_wallet_transactions', false);
        if($this->use_ezugi_seamless_wallet_transactions && $this->use_ezugi_evo_seamless_wallet_transactions){
            $this->original_transaction_table = 'ezugi_evo_seamless_wallet_transactions';
            $this->ymt_init();
        }
    }

    public function getPlatformCode(){
        return EZUGI_EVO_SEAMLESS_API;
    }

}