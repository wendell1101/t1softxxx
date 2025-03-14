<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ae_slots.php';

class Game_api_ae_slots extends Abstract_game_api_common_ae_slots {

    const CURRENCY_TYPE = "CNY";

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency', self::CURRENCY_TYPE);
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return AE_SLOTS_GAMING_API;
    }

    /**
     * 
     * @return string original game logs table in database
     */
    public function getOriginalTable(){
        return 'ae_slots_game_logs';
    }
}
/** end of fle */