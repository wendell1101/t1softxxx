<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_twain extends Abstract_game_api_tot_seamless_gateway {
    public $client_url;

    public function getPlatformCode() {
        return T1_TWAIN_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode() {
        return TWAIN_SEAMLESS_GAME_API;
    }

    public function __construct() {
        parent::__construct();
        $this->client_url = $this->getSystemInfo('client_url');
    }

    public function processResultForQueryForwardGame($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = [
            'url' => '',
            'params' => [],
        ];

        $params = [
            'clientUrl' => '',
        ];

        if ($success) {
            if (!empty($resultArr['detail']['launcher']['url'])) {
                $result['url'] =  $resultArr['detail']['launcher']['url'];
            }

            if (!empty($this->client_url)) {
                $params['clientUrl'] = $this->client_url;
                $result['params'] =  $params;
            }
        }

        return array($success, $result);
    }
}