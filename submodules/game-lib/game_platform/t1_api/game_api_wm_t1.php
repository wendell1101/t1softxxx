<?php
require_once dirname(__FILE__) . '/game_api_t1_common.php';
/*
*   This class will integrate game_gateway api for T1 WM
*
*   For Testing, you must invoke "create_simple_merchant" in gamegateway_module.php,
*   then change the extra_info based on generated keys
*
*   extra_info
*  {
*   "api_merchant_code": "testmerchant",
*   "api_secured_key": "123456"
*  }
*/

class Game_api_wm_t1 extends Game_api_t1_common {

    public function getPlatformCode(){
        return T1WM_API;
    }

    public function getOriginalPlatformCode(){
        return WM_API;
    }

    public function __construct(){
        parent::__construct();
    }

}

/*end of file*/
