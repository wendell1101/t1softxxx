<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class smash_chat_service_api extends BaseController {

    const ERROR_NOT_FOUND_PLAYER = [
        'is_success' => false,
        'err_msg' => 'Invalid Token',
    ];

    const RETURN_OK = [
        'is_success' => true,
        'err_msg' => 'Success'
    ];

    const ERROR_BAD_REQUEST = [
        'is_success' => false,
        'err_msg' => 'Invalid Parameters'
    ];

    const ERROR_API_NOT_AVAILABLE = [
        'is_success' => false,
        'err_msg' => 'API Not Available'
    ];

    const ERROR_INVALID_SIGN = [
        'is_success' => false,
        'err_msg' => 'Invalid Sign'
    ];

    const ERROR_MERCHANT_CODE = [
        'is_success' => false,
        'err_msg' => 'Invalid Merchant Code'
    ];

    private $chat_config = null;
    private $currentPlayer = null;
    private $requestParams = null;
    public function __construct() {
        parent::__construct();
    }



    public function test() {

        // $this->load->library('chat/smash_chat');

        // $resp = $this->smash_chat->getChatUrl();

        $resp = $this->utils->getChatHTML(1, 'testt1dev');

        return $this->setResponse(self::RETURN_OK, ['response' => $resp]);
    }

    public function user_info() {
        $rule_set = [
            'unique_id' => 'required',
            'timestamp' => 'required',
            'merchant_code' => 'required',
            'token' => 'required',
            'sign' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $this->CI->load->model('player');

        $group_details = $this->CI->player->getVipGroupLevelDetails($this->currentPlayer['levelId']);

        return $this->setResponse(self::RETURN_OK, ['username' => $this->currentPlayer['username'], 'vipLevel' => intval($group_details['vipLevel'])]);
    }

    public function validateRequest($rule_set) {
        $this->chat_config = $this->utils->getConfig('p2p_chat_api')['smash_chat'];
        if($this->chat_config === null) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }


        return $is_valid;
    }

    public function preProcessRequest($functionName, $rule_set = []) {
        $params = $this->input->post() ?: [];

        if(empty($params)) {
            $params = file_get_contents('php://input');
            $params = json_decode($params, true) ?: [];
        }

        $this->requestParams = new stdClass();
        $this->requestParams->function = $functionName;
        $this->requestParams->params = $params;
        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if($params['sign'] != $this->generateSign($params)) {
            return $this->setResponse(self::ERROR_INVALID_SIGN, ['sign' => $this->generateSign($params)]);
        }

        if($params['merchant_code'] != $this->chat_config['merchant_code']) {
            return $this->setResponse(self::ERROR_MERCHANT_CODE);
        }

        if(isset($this->requestParams->params['token'])) {
            $this->CI->load->model('common_token');
            $this->currentPlayer = (array) $this->common_token->getPlayerInfoByToken($this->requestParams->params['token']);
            if(empty($this->currentPlayer)) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }
            else {
                $this->currentPlayer['playerId'] = $this->currentPlayer['player_id'];
            }
        }
    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);
        return $this->setOutput($data);
    }
    private function setOutput($data = []) {
        $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    private function generateSign($parameters) {
        unset($parameters['sign']);

        ksort($parameters);
        $sign = sha1(implode('', $parameters) . $this->chat_config['sign_key']);
        return $sign;
    }
}