<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__). "/abstract_game_api_common_evolution_gaming.php";

/**
 * Class Game_api_evolution_gaming
 *
 * Credentials BO
 *   url : https://okada.uat1.evo-test.com/admin
 *
 * Launch Game
 *   format : /iframe_module/goto_evolution_game/<game_type>/<game_code>
 *   sample : /iframe_module/goto_evolution_game/bacarrat/zixzea8nrf1675oh
 */

class Game_api_evolution_gaming extends Abstract_game_api_common_evolution_gaming
{

    const CURRENCY_TYPE = "CNY";

    public function __construct()
    {
        parent::__construct();

        $this->currency_code = self::CURRENCY_TYPE;
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return EVOLUTION_GAMING_API;
    }

    /**
     * 
     * @return string original game logs table in database
     * 
     * @return string the original game logs table
     */
    public function getOriginalTable()
    {
        return 'evolution_2_game_logs';
    }
}