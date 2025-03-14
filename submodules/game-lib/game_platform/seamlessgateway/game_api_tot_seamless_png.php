<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_png extends Abstract_game_api_tot_seamless_gateway{
    public $api_domain;
    public $origin;

    public function getPlatformCode(){
        return T1_PNG_SEAMLESS_API;
    }

    public function getOriginalPlatformCode(){
        return PNG_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->api_domain = $this->getSystemInfo('api_domain', '');
    }

    public function processResultForQueryForwardGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = [
            'url' => '',
            'api_domain' => '',
            'origin' => '',
        ];

       	if ($success && !empty($resultArr['detail']['launcher']['url'])) {
            $result['url'] =  $resultArr['detail']['launcher']['url'];

            if (!empty($this->api_domain)) {
                $result['api_domain'] = $this->api_domain;
            }

            if (!empty($this->home_link)) {
                $result['origin'] = $result['lobby_url'] = $this->home_link;
            }
       	}

        return array($success, $result);
    }
}
