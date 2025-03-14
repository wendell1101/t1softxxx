<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_red_rake.php';

class Game_api_redrakegaming extends Abstract_game_api_common_red_rake {

    const CURRENCY_TYPE = "CNY";

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return REDRAKE_GAMING_API;
    }

    /**
     * 
     * @return string original game logs table in database
     */
    public function getOriginalTable(){
        return 'red_rake_game_logs';
    }
}
/** end of fle */