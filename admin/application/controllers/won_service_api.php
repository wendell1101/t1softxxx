<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/yeebet_service_api.php';
/**
 * Won casino Single Wallet API Controller
 * OGP-31571
 *
 * @author  Jerbey Capoquian
    Seamless Wallet API (Endpoint)
    For seamless wallet integration only. Please refer to API 2.8
    APP ID : <provided by provider>
    Secret key : <provided by provider>
    Callback endpoint : <domain>/won_service_api
    Balance interface : /balance
    Deposit interface : /deposit
    Withdraw interface : /withdraw
    Rollback interface : /rollback
 * 
 * Related File
     - game_api_won_casino_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Won_service_api extends Yeebet_service_api {

    public function getExternalGameId(){
        return WON_CASINO_SEAMLESS_GAME_API;
    }
}

