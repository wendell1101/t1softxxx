<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_qt_seamless.php';

class Game_api_qt_nolimitcity_seamless extends Abstract_game_api_common_qt_seamless {



    public function getPlatformCode(){
        return QT_NOLIMITCITY_SEAMLESS_API;
    }


    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token)
    {
        return $this->returnUnimplemented();
    }

    public function getPlatformPrefix(){
        return "NLC";
    }
}
/*end of file*/