<?php
require_once dirname(__FILE__) . '/game_api_ezugi_seamless_api.php';

/**
 * EZUGI Integration
 * OGP-23012
 *
 * @author  Sony
 */

class Game_api_ezugi_netent_seamless_api extends Game_api_ezugi_seamless_api {

    public function __construct(){     
        parent::__construct();
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');

        if($this->use_ezugi_seamless_wallet_transactions){
            $this->original_transaction_table = 'ezugi_netent_seamless_wallet_transactions';
            $this->ymt_init();
        }else{
            $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
        }
    }

    public function getPlatformCode(){
        return EZUGI_NETENT_SEAMLESS_API;
    }

}