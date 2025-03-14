<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_pinnacle extends Abstract_game_api_tot_seamless_gateway {

    public $origin;

    public function getPlatformCode() {
        return T1_PINNACLE_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode() {
        return PINNACLE_SEAMLESS_GAME_API;
    }

    public function __construct() {
        parent::__construct();
        $this->origin = $this->getSystemInfo('origin');

        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    public function processResultForQueryForwardGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = array();
        $result['url'] = '';

       	if ($success && !empty($resultArr['detail']['launcher']['url'])) {
            $result['url'] =  $resultArr['detail']['launcher']['url'];

            if (!empty($this->origin)) {
                $result['origin'] = $this->origin;
            }
       	}

        return array($success, $result);
    }
}
