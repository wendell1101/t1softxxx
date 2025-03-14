<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Fast_track_service_api extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    const ERROR_CRM_NOT_ACTIVE = [
        'status' => 400,
        'error' => 'Fast Track Integration Not Active'
    ];

    const ERROR_IP_NOT_WHITELISTED = [
        'status' => 403,
        'error' => 'Access Denied'
    ];

    const ERROR_INVALID_API_KEY = [
        'status' => 403,
        'error' => 'Invalid API Key'
    ];

    const RETURN_OK = [
        'status' => 200
    ];

    const RETURN_FAILED = [
        'status' => 400,
        'error' => 'There was an issue processing your request'
    ];

    const RETURN_NOT_FOUND = [
        'status' => 404,
        'error' => 'Not Found'
    ];

    const CREDIT_FUNDS_TYPE_CASHBACK = 'cashback';
    const CREDIT_FUNDS_TYPE_WHEEL = 'wheel';

    private $requestMethod;

    private function preProcessRequest($method) {
        $this->requestMethod = $method;
        $ip_whitelist = $this->utils->getConfig('fast_track_ip_whitelist');
        $client_ip = $this->input->ip_address();
        if(!in_array($client_ip, $ip_whitelist)) {
            return $this->setResponse(self::ERROR_IP_NOT_WHITELISTED);
        }
        if(!$this->utils->getConfig('enable_fast_track_integration')) {
            return $this->setResponse(self::ERROR_CRM_NOT_ACTIVE);
        }
        $headers = $this->input->request_headers();
        if(!isset($headers['X-Api-Key'])) {
            return $this->setResponse(self::ERROR_INVALID_API_KEY);
        }
        if($headers['X-Api-Key'] != $this->utils->getConfig('fast_track_api_key')) {
            return $this->setResponse(self::ERROR_INVALID_API_KEY);
        }
    }

    public function userdetails($player_id) {
        $this->preProcessRequest(__FUNCTION__);
        $this->load->model(['player_model']);
        $this->load->model(['third_party_login']);

        $player = $this->player_model->getAllPlayerInformations($player_id);
        if($player) {
            $vip_level = $this->player_model->getPlayerCurrentLevel($player_id)[0];
            $line_user_id = $this->third_party_login->getLineInfoByPlayerId($player_id);

            $currency = $this->utils->getCurrentCurrency();
            $return = [
                'address' => $player->address ?: '',
                'birth_date' => $player->birthdate ?: '',
                'city' => $player->city ?: '',
                'country' => $player->country ?: '',
                'currency' => $currency['currency_code'],
                'email' => $player->email,
                'is_blocked' => $player->blocked == 1 ? true : false,
                'is_excluded' => $player->blocked == 7 ? true : false,
                'language' => $player->language ?: '',
                'first_name' => $player->firstName,
                'last_name' => $player->lastName,
                'mobile' => $player->contactNumber,
                'mobile_prefix' => $player->dialing_code,
                'origin' => $player->registrationWebsite ?: '',
                'postal_code' => $player->zipcode ?: '',
                'roles' => [
                    lang($vip_level['groupName']) . ' - ' . lang($vip_level['vipLevelName'])
                ],
                'sex' => $player->gender ?: '',
                'title' => '',
                'user_id' => $player->playerId,
                'username' => $player->username,
                // 'verified_at' => str_replace('+00:00', 'Z', gmdate('c', strtotime($player->email_verify_exptime))),
                'verified_at' => str_replace('+00:00', 'Z', gmdate('c', 0)), // TODO: fix when KYC is done
                // 'verified_at' => str_replace('+00:00', 'Z', gmdate('c', strtotime('2020-08-27 20:46:30'))), // TODO: fix when KYC is done
                'registration_code' => $player->tracking_code ?: '', // TODO: 
                'registration_date' => str_replace('+00:00', 'Z', gmdate('c', strtotime($player->createdOn))),
                'affiliate_reference' => $player->tracking_code ?: '',
                'market' => "th", // TODO:
                'segmentation' => [
                    'line_user_id' => !empty($line_user_id) ? $line_user_id->line_user_id : '',
                    // 'kyc_level' => '0' // TODO:
                ]
            ];

            return $this->setResponse(self::RETURN_OK, $return);
        }
        else {
            return $this->setResponse(self::RETURN_NOT_FOUND);
        }
    }

    public function userblocks($player_id) {
        $this->preProcessRequest(__FUNCTION__);
        $this->load->model(['player_model']);

        $player = $this->player_model->getAllPlayerInformations($player_id);

        if($player) {
            $return = [
                'blocks' => [
                    [
                        'active' => $player->blocked == 1 ? true : false,
                        'type' => 'Blocked',
                        'note' => 'n/a',
                    ],
                    [
                        'active' => $player->blocked == 7 ? true : false,
                        'type' => 'Excluded',
                        'note' => 'n/a',
                    ]
                ]
            ];

            return $this->setResponse(self::RETURN_OK, $return);
        }
        else {
            return $this->setResponse(self::RETURN_NOT_FOUND);
        }
    }
    
    public function reconciliation() {
        $this->preProcessRequest(__FUNCTION__);
        $params = json_decode(file_get_contents("php://input"));
        $this->load->model(['player_model']);

        //block status
        //block =0 active
        //block =1 block
        //block =5 suspended
        //block =8 Failed login attempts
        //block =7 Self excluded
        switch($params->field) {
            case 'blocked':
                $players = $this->player_model->getAllPlayerByStatus(1, 'playerId');
            break;
            case 'excluded':
                $players = $this->player_model->getAllPlayerByStatus(7, 'playerId');
            case 'active':
                $players = $this->player_model->getAllPlayerByStatus(0, 'playerId');
            break;
        }
        $player_ids = [];
        if($players) {
            $player_ids = array_column($players, 'playerId');
        }
        $return = [
            'users' => $player_ids,
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now')))
        ];

        return $this->setResponse(self::RETURN_OK, $return);

    }

    public function userconsents($player_id) {
        $this->preProcessRequest(__FUNCTION__);
        $this->load->model('communication_preference_model');
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if($this->utils->isEnabledFeature('enable_communication_preferences')) {
                $this->load->model('communication_preference_model');
                $preference = $this->communication_preference_model->getCurrentPreferences($player_id);
                $return = [
                    'consents' => [
                        [
                            'opted_in' => property_exists($preference, 'email') ?  (boolean) $preference->email : false,
                            'type' => 'Email'
                        ],
                        [
                            'opted_in' => property_exists($preference, 'sms') ?  (boolean) $preference->sms : false,
                            'type' => 'SMS'
                        ],
                        [
                            'opted_in' => property_exists($preference, 'phone_call') ?  (boolean) $preference->phone_call : false,
                            'type' => 'Telephone'
                        ],
                        [
                            'opted_in' => property_exists($preference, 'post') ?  (boolean) $preference->post : false,
                            'type' => 'PostMail'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'SiteNotification'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'PushNotification'
                        ]
                    ]
                ];

                return $this->setResponse(self::RETURN_OK, $return);
            }
            else  {
                $preference = $this->communication_preference_model->getCurrentPreferences($player_id);
                $return = [
                    'consents' => [
                        [
                            'opted_in' => false,
                            'type' => 'Email'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'SMS'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'Telephone'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'PostMail'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'SiteNotification'
                        ],
                        [
                            'opted_in' => false,
                            'type' => 'PushNotification'
                        ]
                    ]
                ];

                return $this->setResponse(self::RETURN_OK, $return);
            }
        }
        else {
            $params = json_decode(file_get_contents("php://input"));
            $data = [];
            foreach($params->consents as $pref) {

                if($pref->type === 'Email') {
                    $data['pref-data-email'] = $pref->opted_in;
                    // break;
                }
                else if($pref->type === 'SMS') {
                    $data['pref-data-sms'] = $pref->opted_in;
                }
                else if($pref->type === 'Telephone') {
                    $data['pref-data-phone_call'] = $pref->opted_in;
                }
                else if($pref->type === 'PostMail') {
                    $data['pref-data-post'] = $pref->opted_in;
                }
            }

            $update_preferences = $this->communication_preference_model->updatePlayerCommunicationPreference($player_id, $data);

            $return = [
                'message' => 'ok'
            ];
            if($update_preferences){
                return $this->setResponse(self::RETURN_OK, $return);
            }
            return $this->setResponse(self::RETURN_FAILED);
        }
    }

    public function bonusCredit() {
        $this->preProcessRequest(__FUNCTION__);
        $params = json_decode(file_get_contents("php://input"));
        
        $this->load->model(['fast_track_bonus_crediting']);
        $expiration_date = null;
        $this->fast_track_bonus_crediting->addPromoToPlayer($params->user_id, $params->promo_rule_id, $expiration_date, $params->bonus_code, json_encode($params));

        $return = [
            'message' => 'ok'
        ];
        return $this->setResponse(self::RETURN_OK, $return);
        return $this->setResponse(self::RETURN_NOT_FOUND);
    }

    public function bonusCreditFunds() {
        $this->preProcessRequest(__FUNCTION__);

        $origin_params = file_get_contents("php://input");
        $params = json_decode($origin_params, true);

        $this->CI->utils->debug_log('bonusCreditFunds origin params', $origin_params);

        $this->load->model(['player_model']);
        $player = $this->player_model->getAllPlayerInformations($params['user_id']);
        if(empty($player)) {
            $return = ['message' => 'not found player'];
            return $this->setResponse(self::RETURN_NOT_FOUND, $return);
        }

        if(empty($params['amount'])){
            $return = ['message' => 'amount <= 0'];
            return $this->setResponse(self::RETURN_FAILED, $return);
        }

        $this->load->model(['fast_track_bonus_crediting']);
        $type = self::CREDIT_FUNDS_TYPE_CASHBACK;
        if(isset($params['type'])) {
            $type = $params['type'];
        }
        $success = $this->fast_track_bonus_crediting->requestBonusCreditFunds($params['user_id'], $origin_params, $type);

        if(!empty($success)){
            if($type == self::CREDIT_FUNDS_TYPE_CASHBACK) {
                $this->load->library(['player_cashback_library']);
                $user_id = $this->authentication->getUserId();
                $reason = 'Bonus Credit Funds Auto Add Cashback';
                $transaction = null;
                $result = $this->player_cashback_library->autoAddCashbackToBalance($params['user_id'], 'Main Wallet', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, $params['amount'], $user_id, $reason, $transaction);
                //TODO SONY: save transaction to db
                $result['success'] = true;
                if(!$result['success']){
                    if (isset($rlt['message']) && !empty($rlt['message'])) {
                        $data = $rlt['message'];
                        return $this->setResponse(self::RETURN_FAILED, $data);
                    }
                }else{
                    $return = ['message' => 'ok'];
                    return $this->setResponse(self::RETURN_OK, $return);
                }
            }
            else {
                // don't process non cashback
                $return = ['message' => 'ok'];
                return $this->setResponse(self::RETURN_OK, $return);
            }
        }

        return $this->setResponse(self::RETURN_FAILED);
    }

    public function authenticate() {
        $this->preProcessRequest(__FUNCTION__);
        $params = json_decode(file_get_contents("php://input"));

        $this->load->model(array('common_token'));
        $player_id = $this->common_token->getPlayerIdByToken($params->sid);

        if(!empty($player_id)) {
            $return = [
                'user_id' => $player_id
            ];
            return $this->setResponse(self::RETURN_OK, $return);
        }
        return $this->setResponse(self::RETURN_NOT_FOUND);
    }

    private function setResponse($return_code, $data = []) {
        $status_code = $return_code['status'];
        unset($return_code['status']);
        $data = array_merge($return_code, $data);
        return $this->setOutput($data);
    }

    private function setOutput($data = [], $status_code = 200) {
        $systemId=CRM_API;
        // $flag = $data['error'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $data = json_encode($data);
        $requestParams = json_encode(json_decode(file_get_contents("php://input")));

        $this->CI->response_result->saveResponseResult(
            CRM_API,
            Response_result::FLAG_NORMAL,
            $this->requestMethod,
            $requestParams,
            $data,
            $status_code,
            null,
            null,
            [
                'full_url' => $this->CI->utils->currentUrl()
            ]
        );

        $this->output->set_content_type('application/json')->set_output($data)->set_status_header($status_code);
        $this->output->_display();
        exit();
    }
}