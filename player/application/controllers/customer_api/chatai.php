<?php
/**
 * CHATAI exclusive API endpoint
 * OGP-35144
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 * @author 	Rupert Chen
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_chatai_player.php';

class Chatai extends T1t_ac_tmpl {

    use t1t_comapi_module_chatai_player;

    // cloned form t1t_comapi_module_ole777_reward_sys
    protected $errors = [
		'SUCCESS'                 => 0 ,
		'ERR_INVALID_SECURE'      => 101 ,
		'ERR_INVALID_MEMBER_CODE' => 102 ,
		'ERR_INVALID_ROOM_CODE'   => 103 ,
        'ERR_INVALID_TOKEN'       => 104 ,
		'ERR_FAILURE'             => 190 ,
	];

	protected $black_list_enabled = false;
	protected $black_list = [];

	protected $white_list_enabled = true;
	protected $white_list = [
		'apiEcho' ,
		'apiPostEcho' ,
        'getChatToken',                 // OGP-35144
        'getPlayerInfo',                // OGP-35144
        'getPlayeDeposits',             // OGP-35385
        'getPlayeDepositByOrder',     // OGP-35385
        'getPlayeWithdrawals',          // OGP-35381
        'getPlayewithdrawalByOrder',  // OGP-35381
        'playerWithdrawalConditions',   // OGP-35386
        'playerVipStatus',              // OGP-35386
        'chatBotWebhook',
        'chatBotToken'
	];

	function __construct() {
		parent::__construct();
        $this->load->library(['playerapi_lib', 'chat_library', 'language_function']);
        $this->load->model(['playerapi_model']);
	}

    public function formatCbResponseMessage(&$response, $key, $value){
        $response['responses'][] = [
            'type' => 'text',
            'message' => $this->translateResponse($key, $value)
        ];
    }

    public function formatCbResponseAttributes(&$response, $key, $value){
        $response['attributes'][$key] = $value;
    }

    public function formatCbResponseFailed(&$response, $ret){
        $message = !empty($ret['mesg']) ? $ret['mesg'] : 'Get Token Failed';
        $response['responses'][] = [
            'type' => 'text',
            'message' => $message
        ];
    }
}
