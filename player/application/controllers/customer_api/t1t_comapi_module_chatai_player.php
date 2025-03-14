<?php
trait t1t_comapi_module_chatai_player {

    public function getChatToken($trigger_from_chatbot = false, $args = []) {
		try {
            if($trigger_from_chatbot && !empty($args)){
                $api_key     = trim($args['api_key']);
                $username    = trim($args['username']);
                $chatRoomId  = trim($args['chatroomid']);
                $brand       = trim($args['brand']);
                $currency    = trim($args['currency']);
            }else{
                $api_key     = trim($this->input->post('api_key', true));
                $username    = trim($this->input->post('secret', true));
                $chatRoomId  = trim($this->input->post('chatroomid', true));
                $brand       = trim($this->input->post('brand', true));
                $currency    = trim($this->input->post('currency', true));
            }

            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            if(empty($username)){
                throw new Exception('Invalid value for secret', $this->errors['ERR_INVALID_MEMBER_CODE']);
            }

            if(empty($chatRoomId)){
                throw new Exception('Invalid value for chat room id', $this->errors['ERR_INVALID_ROOM_CODE']);
            }

            $token =  $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($username, $chatRoomId) {
                $token = $this->chat_library->getChatToken($username, $chatRoomId);
				return $token;
			});

            if(empty($token)){
                throw new Exception('Invalid value for token', $this->errors['ERR_INVALID_TOKEN']);
            }

			$ret = [
				'success'	=> true ,
                'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Authenticate Success',
                'result'    => [
                    'token'     => $token
                ]
            ];

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
			];
		}
		finally {
            if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
		}
	} // End function getChatToken()

    public function getPlayerInfo($trigger_from_chatbot = false, $args = []){
		try {
            $this->load->library(['chat_library']);
            $this->load->model(['player_model']);

            if($trigger_from_chatbot && !empty($args)){
                $api_key     = trim($args['api_key']);
                $token       = trim($args['token']);
                $username    = trim($args['username']);
                $brand       = trim($args['brand']);
                $currency    = trim($args['currency']);
            }else{
                $api_key     = trim($this->input->post('api_key', true));
                $token       = trim($this->input->post('token', true));
                $username    = trim($this->input->post('username', true));
                $brand       = trim($this->input->post('brand', true));
                $currency    = trim($this->input->post('currency', true));
            }

            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            $std_creds = [
                'api_key' => $api_key,
                'token' => $token,
                'username' => $username, // need to adjust params, request to add username
                'brand' => $brand,
                'currency' => $currency,
                'brand' => $brand,
            ];
            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            $message = null;
            $player =  $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($token, &$message) {
                $playerId = $this->chat_library->getPlayerIdByToken($token);
                if(empty($playerId)){
                    $message = 'Invalid Token, player not found';
                    return false;
                }

                $this->setLanguageByChatToken($token);
                $player = $this->player_model->getPlayerArrayById($playerId);
				return $player;
			});

            if(!empty($message)){
                throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
            }

            $output = [
                'username'  => $player['username'],
                'email'     => $player['email'],
                'created' => $player['createdOn']
            ];

			$ret = [
				'success'	=> true ,
				'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Verification complete',
				'result'    => [
                    'player'	=> $output
                ]
            ];
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
			];
		}
		finally {
            if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
		}
    } // End function getPlayerInfo()

    // Deprecated
    public function getPlayeDeposits($trigger_from_chatbot = false, $args = []){
        // try {
        //     $this->load->library(['chat_library']);
        //     $this->load->model(['player_model']);

        //     if($trigger_from_chatbot && !empty($args)){
        //         $api_key     = trim($args['api_key']);
        //         $token       = trim($args['token']);
        //         $start_date  = trim($args['requestedDateStart']);
        //         $end_date    = trim($args['requestedDateEnd']);
        //         $status      = trim($args['status']);
        //         $limit       = trim($args['limit']);
        //         $sort        = trim($args['sort']);
        //         $page        = trim($args['page']);
        //         $currency    = trim($args['currency']);
        //         $brand       = trim($args['brand']);
        //     }else{
        //         $api_key = trim($this->input->post('api_key', true));
        //         $token = trim($this->input->post('token', true));
        //         $start_date = trim($this->input->post('requestedDateStart', true));
        //         $end_date = trim($this->input->post('requestedDateEnd', true));
        //         $status = trim($this->input->post('status', true));
        //         $limit = trim($this->input->post('limit', true));
        //         $sort = trim($this->input->post('sort', true));
        //         $page = trim($this->input->post('page', true));
        //         $currency = trim($this->input->post('currency', true));
        //         $brand = trim($this->input->post('brand', true));
        //     }

        //     $time_start = !empty($start_date)?$this->playerapi_lib->convertDateTimeToMysql($start_date):$this->playerapi_lib->convertDateTimeToMysql( date('Y-m-d').' 00:00:00');
		// 	$time_end = !empty($end_date)?$this->playerapi_lib->convertDateTimeToMysql($end_date):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');
        //     $limit = !empty($limit) ? $limit : 20;
        //     $status = !empty($status) ? $status : [];
        //     $deposit_status = [];

        //     if(!empty($input_status)){
		// 		foreach($input_status as $val){
		// 			$match_result = $this->playerapi_lib->matchInputDepositStatus($val);
		// 			if(!empty($match_result)){
		// 				$deposit_status[] = $match_result;
		// 			}
		// 		}
		// 	}

        //     $std_creds = [
        //         'api_key' => $api_key,
        //         'token' => $token,
        //         'start_date' => $time_start,
        //         'end_date' => $time_end,
        //         'status' => $status,
        //         'limit' => $limit,
        //         'sort' => $sort,
        //         'page' => $page,
        //         'currency' => $currency,
        //         'brand' => $brand,
        //     ];

        //     $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

        //     $isValidToken = $this->isValidApiKey($api_key);
        //     if(!$isValidToken){
        //         throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
        //     }

        //     $player_id = $this->chat_library->getPlayerIdByToken($token);
        //     if(empty($player_id)){
        //         $message = 'Invalid Token, player not found';
        //         throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
        //     }

        //     $all_deposit_orders = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $time_start, $time_end, $limit, $sort, $deposit_status, $page) {
        //         $deposit_orders = $this->playerapi_model->getPaymentRequestsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $deposit_status, $page);
        //         $allowed_fields = ['time', 'channel', 'orderId', 'amount', 'status'];
        //         array_walk($deposit_orders['list'], function (&$item) use ($allowed_fields) {
        //             $item['channel'] = lang($item['paymentMethod_name']);
        //             $item['orderId'] = $item['secureId'];
        //             $item['time'] = $item['updatedAt'];
        //             $item['amount'] = $item['realAmount'];
        //             $item['status'] = lang('sale_orders.status.'.$item['status']);
        //             foreach ($item as $key => $value) {
        //                 if (!in_array($key, $allowed_fields)) {
        //                     unset($item[$key]);
        //                     continue;
        //                 }
        //             }
        //         });
        //         return $deposit_orders;
        //     });

		// 	$ret = [
		// 		'success'	=> true ,
		// 		'code'      => $this->errors['SUCCESS'],
		// 		'mesg'      => 'Verification complete'
        //     ];

        //     if(empty($all_deposit_orders['list'])){
        //         $ret['result'] = [];
        //         $ret['mesg'] = 'No deposit records found';
        //     }else{
        //         $ret['result'] = $all_deposit_orders;
        //     }
		// }
		// catch (Exception $ex) {
		// 	$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

		// 	$ret = [
		// 	    'success'   => false,
		// 	    'code'      => $ex->getCode(),
		// 	    'mesg'      => $ex->getMessage(),
		// 	    'result'    => null
		// 	];
		// }
		// finally {
		// 	if(!$trigger_from_chatbot){
        //         $this->returnApiResponseByArray($ret);
        //     }else{
        //         return $ret;
        //     }
		// }
    } // End function getPlayeDeposits()

    // Deprecated
    public function getPlayeWithdrawals($trigger_from_chatbot = false, $args = []){
        // try {
        //     $this->load->library(['chat_library']);
        //     $this->load->model(['player_model', 'wallet_model']);

        //     if($trigger_from_chatbot && !empty($args)){
        //         $api_key = trim($args['api_key']);
        //         $token = trim($args['token']);
        //         $start_date = trim($args['requestedDateStart']);
        //         $end_date = trim($args['requestedDateEnd']);
        //         $status = trim($args['status']);
        //         $limit = trim($args['limit']);
        //         $sort = trim($args['sort']);
        //         $page = trim($args['page']);
        //         $currency = trim($args['currency']);
        //         $brand = trim($args['brand']);
        //     }else{
        //         $api_key = trim($this->input->post('api_key', true));
        //         $token = trim($this->input->post('token', true));
        //         $start_date = trim($this->input->post('requestedDateStart', true));
        //         $end_date = trim($this->input->post('requestedDateEnd', true));
        //         $status = trim($this->input->post('status', true));
        //         $limit = trim($this->input->post('limit', true));
        //         $sort = trim($this->input->post('sort', true));
        //         $page = trim($this->input->post('page', true));
        //         $currency = trim($this->input->post('currency', true));
        //         $brand = trim($this->input->post('brand', true));
        //     }

        //     $time_start = !empty($start_date)?$this->playerapi_lib->convertDateTimeToMysql($start_date):$this->playerapi_lib->convertDateTimeToMysql( date('Y-m-d').' 00:00:00');
		// 	$time_end = !empty($end_date)?$this->playerapi_lib->convertDateTimeToMysql($end_date):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');
        //     $limit = !empty($limit) ? $limit : 20;
        //     $sort = !empty($sort) ? $sort : 'DESC';
        //     $status = !empty($status) ? $status : '9999';
        //     $status = $this->playerapi_lib->matchInputWithdrawalStatus($status);
        //     $page = !empty($page) ? $page : 1;
        //     $currency = !empty($currency) ? $currency : null;

        //     $isValidToken = $this->isValidApiKey($api_key);
        //     if(!$isValidToken){
        //         throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
        //     }

        //     $player_id = $this->chat_library->getPlayerIdByToken($token);
        //     if(empty($player_id)){
        //         $message = 'Invalid Token, player not found';
        //         throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
        //     }

        //     $std_creds = [
        //         'api_key' => $api_key,
        //         'token' => $token,
        //         'start_date' => $time_start,
        //         'end_date' => $time_end,
        //         'status' => $status,
        //         'limit' => $limit,
        //         'sort' => $sort,
        //         'page' => $page,
        //         'currency' => $currency,
        //         'brand' => $brand,
        //     ];

        //     $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

        //     $all_withdrawal_orders = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $time_start, $time_end, $limit, $sort, $status, $page) {
        //         $withdrawal_orders = $this->playerapi_model->getWithdrawalRequestsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $status, $page);
        //         $allowed_fields = ['time', 'channel', 'orderId', 'amount', 'status'];
        //         array_walk($withdrawal_orders['list'], function (&$item) use ($allowed_fields) {
        //             $item['channel'] = lang($item['bankAccount_bankName']);
        //             $item['orderId'] = $item['withdrawalCode'];
        //             $item['time'] = $item['updatedAt'];
        //             $item['status'] = $this->wallet_model->getStageName($item['dwStatus']);

        //             foreach ($item as $key => $value) {
        //                 if (!in_array($key, $allowed_fields)) {
        //                     unset($item[$key]);
        //                     continue;
        //                 }
        //             }
        //         });
        //         return $withdrawal_orders;
        //     });

        //     $ret = [
		// 		'success'	=> true ,
		// 		'code'      => $this->errors['SUCCESS'],
		// 		'mesg'      => 'Verification complete'
        //     ];

        //     if(empty($all_withdrawal_orders['list'])){
        //         $ret['result'] = [];
        //         $ret['mesg'] = 'No withdrawl records found';
        //     }else{
        //         $ret['result'] = $all_withdrawal_orders;
        //     }
        // }
        // catch (Exception $ex) {
        //     $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
        //     $ret = [
        //         'success'   => false,
        //         'code'      => $ex->getCode(),
        //         'mesg'      => $ex->getMessage(),
        //         'result'    => null
        //     ];
        // }
        // finally {
        //     if(!$trigger_from_chatbot){
        //         $this->returnApiResponseByArray($ret);
        //     }else{
        //         return $ret;
        //     }
        // }
    } // End function getPlayeWithdrawals()

    public function getPlayeDepositByOrder($trigger_from_chatbot = false, $args = []){
        try {
            $this->load->library(['chat_library']);
            $this->load->model(['player_model']);

            if($trigger_from_chatbot && !empty($args)){
                $api_key     = trim($args['api_key']);
                $token       = trim($args['token']);
                $orderId     = trim($args['orderId']);
                $currency    = trim($args['currency']);
            }else{
                $api_key = trim($this->input->post('api_key', true));
                $token = trim($this->input->post('token', true));
                $orderId = trim($this->input->post('orderId', true));
                $currency = trim($this->input->post('currency', true));
                $brand = trim($this->input->post('brand', true));
            }

            $std_creds = [
                'api_key' => $api_key,
                'token' => $token,
                'orderId' => $orderId,
                'currency' => $currency
            ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            $player_id = $this->chat_library->getPlayerIdByToken($token);
            if(empty($player_id)){
                $message = 'Invalid Token, player not found';
                throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
            }

            $deposit_order = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $orderId, $token) {
                $this->setLanguageByChatToken($token);
                $deposit = [];
                $order = $this->sale_order->getSaleOrderBySecureIdFromChat($orderId);
                if(!empty($order) && ($order['player_id'] == $player_id)){
                    $deposit = [
                        'Channel' => lang($order['paymentMethod_name']),
                        'OrderId' => $order['secure_id'],
                        'Time' => $order['updated_at'],
                        'Amount' => $order['amount'],
                        'Status' => lang('sale_orders.status.'.$order['status']),
                    ];
                }
                return $deposit;
            });

			$ret = [
				'success'	=> true ,
				'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Verification complete'
            ];

            if(empty($deposit_order)){
                $ret['result'] = [];
                $ret['mesg'] = 'No deposit records found';
            }else{
                $ret['result'] = $deposit_order;
            }
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => null
			];
		}
		finally {
			if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
		}
    } // End function getPlayeDepositByOrder()

    public function getPlayewithdrawalByOrder($trigger_from_chatbot = false, $args = []){
        try {
            $this->load->library(['chat_library']);
            $this->load->model(['player_model', 'wallet_model']);

            if($trigger_from_chatbot && !empty($args)){
                $api_key     = trim($args['api_key']);
                $token       = trim($args['token']);
                $orderId     = trim($args['orderId']);
                $currency    = trim($args['currency']);
            }else{
                $api_key = trim($this->input->post('api_key', true));
                $token = trim($this->input->post('token', true));
                $orderId = trim($this->input->post('orderId', true));
                $currency = trim($this->input->post('currency', true));
                $brand = trim($this->input->post('brand', true));
            }

            $std_creds = [
                'api_key' => $api_key,
                'token' => $token,
                'orderId' => $orderId,
                'currency' => $currency
            ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            $player_id = $this->chat_library->getPlayerIdByToken($token);
            if(empty($player_id)){
                $message = 'Invalid Token, player not found';
                throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
            }

            $withdrawal_orders = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $orderId, $token) {
                $this->setLanguageByChatToken($token);
                $withdrawal = [];
                $order = $this->wallet_model->getWalletAccountByTransactionCode($orderId);
                if(!empty($order) && ($order['playerId'] == $player_id)){
                    $withdrawal = [
                        'Channel' => lang($order['bankName']),
                        'OrderId' => $order['transactionCode'],
                        'Time' => $order['processDatetime'],
                        'Amount' => $order['amount'],
                        'Status' => $this->wallet_model->getStageName($order['dwStatus']),
                    ];
                }
                return $withdrawal;
            });

			$ret = [
				'success'	=> true ,
				'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Verification complete'
            ];

            if(empty($withdrawal_orders)){
                $ret['result'] = [];
                $ret['mesg'] = 'No withdrawl records found';
            }else{
                $ret['result'] = $withdrawal_orders;
            }
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => null
			];
		}
		finally {
			if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
		}
    } // End function getPlayewithdrawalByOrder()

    public function playerWithdrawalConditions($trigger_from_chatbot = false, $args = []){
        try {
            $this->load->library(['chat_library']);
            $this->load->model(['withdraw_condition']);

            if($trigger_from_chatbot && !empty($args)){
                $api_key = trim($args['api_key']);
                $token = trim($args['token']);
                $currency = trim($args['currency']);
                $brand = trim($args['brand']);
            }else{
                $api_key = trim($this->input->post('api_key', true));
                $token = trim($this->input->post('token', true));
                $currency = trim($this->input->post('currency', true));
                $brand = trim($this->input->post('brand', true));
                $currency = trim($this->input->post('currency', true));
            }

            $currency = !empty($currency) ? $currency : null;
            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            $player_id = $this->chat_library->getPlayerIdByToken($token);
            if(empty($player_id)){
                $message = 'Invalid Token, player not found';
                throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
            }

            $std_creds = [
                'api_key' => $api_key,
                'token' => $token,
                'currency' => $currency,
                'brand' => $brand,
            ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            $result = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $token) {
                $this->setLanguageByChatToken($token);
                $wcs = $this->withdraw_condition->computePlayerWithdrawalConditions($player_id);
                $wd_summary = [
                    'required_bet'		=> $wcs['totalRequiredBet'] ,
                    'current_total_bet'	=> $wcs['totalPlayerBet'] ,
                    'unfinished_bet'	 => $wcs['unfinished']
                ];
                return $wd_summary;
            });

            $ret = [
				'success'	=> true ,
				'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Verification complete',
                'result'    => $result
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
        }
    } // End function playerWithdrawalConditions()

    public function playerVipStatus($trigger_from_chatbot = false, $args = []){
        try {
            $this->load->library(['player_functions']);

            if($trigger_from_chatbot && !empty($args)){
                $api_key = trim($args['api_key']);
                $token = trim($args['token']);
                $currency = trim($args['currency']);
                $brand = trim($args['brand']);
            }else{
                $api_key = trim($this->input->post('api_key', true));
                $token = trim($this->input->post('token', true));
                $currency = trim($this->input->post('currency', true));
                $brand = trim($this->input->post('brand', true));
            }

            $currency = !empty($currency) ? $currency : null;

            $isValidToken = $this->isValidApiKey($api_key);
            if(!$isValidToken){
                throw new Exception('Invalid value for api key', $this->errors['ERR_INVALID_SECURE']);
            }

            $player_id = $this->chat_library->getPlayerIdByToken($token);
            if(empty($player_id)){
                $message = 'Invalid Token, player not found';
                throw new Exception($message, $this->errors['ERR_INVALID_TOKEN']);
            }

            $std_creds = [
                'api_key' => $api_key,
                'token' => $token,
                'currency' => $currency,
                'brand' => $brand,
            ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            $result = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $token) {
                $this->setLanguageByChatToken($token);
                $vip_full = $this->player_functions->getPlayerVipGroupDetails($player_id, 'force_desktop');

                // if (empty($vip_full)) {
                //     throw new Exception(lang("Error reading player VIP status"), self::CODE_VIPS_ERROR_READING_VIP_STATUS);
                // }

                $level_current  = $vip_full['current_vip_level'];
                $vip_res = [
                    'upgradeFormula' => $level_current['formula'] ,
                ];
                return $vip_res;
            });

            $ret = [
				'success'	=> true ,
				'code'      => $this->errors['SUCCESS'],
				'mesg'      => 'Verification complete',
                'result'    => $result
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            if(!$trigger_from_chatbot){
                $this->returnApiResponseByArray($ret);
            }else{
                return $ret;
            }
        }
    } // End function playerVipStatus(()

    public function translateResponse($key, $value){
        $message = '';
        $langKey = '';
        switch($key){
            case 'username': // chatBotGetPlayerInfo
                $langKey = lang('player.01');
                break;
            case 'email': // chatBotGetPlayerInfo
                $langKey = lang('Email Address');
                break;
            case 'created': // chatBotGetPlayerInfo
                $langKey = lang('player.38');
                break;
            case 'Channel': // chatBotGetPlayeDeposits, chatBotGetPlayeWithdrawals
                $langKey = lang('lang.bank');
                break;
            case 'OrderId': // chatBotGetPlayeDeposits, chatBotGetPlayeWithdrawals
                $langKey = lang('pay.sale_order_id');
                break;
            case 'Time': // chatBotGetPlayeDeposits, chatBotGetPlayeWithdrawals
                $langKey = lang('Updated at');
                break;
            case 'Amount': // chatBotGetPlayeDeposits, chatBotGetPlayeWithdrawals
                $langKey = lang('Amount');
                break;
            case 'Status': // chatBotGetPlayeDeposits, chatBotGetPlayeWithdrawals
                $langKey = lang('Payment Status');
                break;
            case 'required_bet': // chatBotPlayerWithdrawalConditions
                $langKey = lang('pay.totalRequiredBet');
                break;
            case 'current_total_bet': // chatBotPlayerWithdrawalConditions
                $langKey = lang('pay.totalPlayerBet');
                break;
            case 'unfinished_bet': // chatBotPlayerWithdrawalConditions
                $langKey = lang('mark.unfinished');
                break;
            case 'upgradeFormula': // chatBotPlayerVipStatus
                $langKey = lang('Upgrade Condition');
                break;
            default:
                $langKey = $key;
                break;
        }

        $message = $langKey . ': ' . $value;
        return $message;
    } // End function translateResponse()

    public function setLanguageByChatToken($token){
        $lang = $this->chat_library->getTokenLanguage($token);
        $this->language_function->setCurrentLanguage($this->language_function->langStrToInt($lang));
        $paramLang = $this->language_function->convertHtmlLang($lang);
        $this->utils->loadLanguage($paramLang, 'main', true);
    } // End function setLanguageByChatToken()

    // Deprecated
    public function chatBotWebhookOrigin(){
        /*
        $token = 'e442540c1337519cb6e952ffa846b794';
        $this->utils->debug_log(__METHOD__, $token);
        // check token in every request
        if (!isset($_GET['token']) || $_GET['token'] !== $token) {
            header('HTTP/1.0 401 Unauthorized');
            exit();
        }

        // verification request
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            exit($_GET['challenge']);
        }

        $request_body = $this->playerapi_lib->getRequestPramas();
		$this->utils->debug_log(__METHOD__, '=======request_body getRequestPramas', $request_body);

        $this->load->model(['player_model', 'sale_order']);
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $username = !empty($attributes['default_name']) ? $attributes['default_name'] : null;
        $orderId = !empty($attributes['DepositOrderNumber']) ? $attributes['DepositOrderNumber'] : null;

        $playerId = null;
        if(!empty($username)){
            $playerId = $this->player_model->getPlayerIdByUsername($username);
        }

        $this->utils->debug_log(__METHOD__, 'playerId:', $playerId);

        $deposit_order = [];
        $deposit = null;
        $deposit_txt = 'No deposit found';
        $currency = null;
        $info = [];

        if(!empty($playerId) && !empty($orderId)){
            $deposit_order = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $orderId, &$deposit, &$deposit_txt, &$info) {
                $order = $this->sale_order->getSaleOrderArrBySecureId($orderId);
                if(!empty($order) && ($order['player_id'] == $playerId)){
                    $deposit_txt = $order['secure_id'] . ' is ' . lang('sale_orders.status.'.$order['status']) . ' with amount ' . $order['amount'] . ' at ' . $order['updated_at'];
                    $deposit = [
                        'orderNum' => $order['secure_id'],
                        'amount' => $order['amount'],
                        'approvedTime' => $order['processed_approved_time'],
                        'timeoutAt' => $order['timeout_at'],
                        'createdAt' => $order['created_at'],
                        'updatedAt' => $order['updated_at'],
                        'status' => lang('sale_orders.status'.$order['status']),
                    ];
                }
                return ['deposit' => $deposit, 'deposit_txt' => $deposit_txt];
            });
        }

        $this->utils->debug_log(__METHOD__, 'deposit', $deposit_order);

        $deposit_txt = !empty($deposit_order['deposit_txt']) ? $deposit_order['deposit_txt'] : [];

        header('Content-Type: application/json');

        $response = array(
            // return custom attributes object
            'attributes' => array(
                'name' => $username,
                'playerId' => $playerId,
                'depositOrderNum' => $orderId
            ),
            // return responses
            'responses' => array(
                array(
                    'type' => 'text',
                    'message' => $deposit_txt
                )
            )
        );

        $this->utils->debug_log(__METHOD__, 'response', $response);
        echo json_encode($response);
        */
    }

    public function chatBotToken($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $room_id = !empty($attributes['default_id']) ? $attributes['default_id'] : null;
        $username = !empty($attributes['default_name']) ? $attributes['default_name'] : null;

        $args = [
            'api_key' => $api_key,
            'username' => $username,
            'chatroomid' => $room_id,
            'brand' => null,
            'currency' => null
        ];

        $ret = $this->getChatToken(true, $args);
        $this->utils->debug_log('chatBotToken result', $ret);

        $response = [];
        if(!empty($ret) && $ret['success']){
            foreach ($ret['result'] as $key => $value) {
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }

        return $response;
    } // End function chatBotToken()

    public function chatBotGetPlayerInfo($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $response = [];
        $chatToken = !empty($attributes['token']) ? $attributes['token'] : null;
        if(empty($chatToken)){
            $ret['mesg'] = 'Chat Token is empty !';
            $this->formatCbResponseFailed($response, $ret);
            return $response;
        }

        $args = [
            'api_key' => $api_key,
            'token' => $chatToken,
            'username' => null,
            'chatroomid' => null,
            'brand' => null,
            'currency' => null
        ];

        $ret = $this->getPlayerInfo(true, $args);
        $this->utils->debug_log('chatBotGetPlayerInfo result', $ret);

        if(!empty($ret) && $ret['success']){
            foreach($ret['result']['player'] as $key => $value){
                $this->formatCbResponseMessage($response, $key, $value);
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }

        return $response;
    } // End function chatBotGetPlayerInfo()

    public function chatBotGetPlayeDeposits($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $response = [];
        $chatToken = !empty($attributes['token']) ? $attributes['token'] : null;
        $orderId = !empty($attributes['orderId']) ? $attributes['orderId'] : null;

        if(empty($chatToken)){
            $ret['mesg'] = 'Chat Token is empty !';
            $this->formatCbResponseFailed($response, $ret);
            return $response;
        }

        $args = [
            'api_key' => $api_key,
            'token' => $chatToken,
            'orderId' => $orderId,
            'currency' => null,
            'brand' => null,
        ];

        $ret = $this->getPlayeDepositByOrder(true, $args);
        $this->utils->debug_log('chatBotGetPlayeDeposits result', $ret);

        if(!empty($ret) && $ret['success'] && !empty($ret['result'])){
            foreach($ret['result'] as $key => $value){
                $this->formatCbResponseMessage($response, $key, $value);
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }
        return $response;
    } // End function chatBotGetPlayeDeposits()

    public function chatBotGetPlayeWithdrawals($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $response = [];
        $chatToken = !empty($attributes['token']) ? $attributes['token'] : null;
        $orderId = !empty($attributes['orderId']) ? $attributes['orderId'] : null;

        if(empty($chatToken)){
            $ret['mesg'] = 'Chat Token is empty !';
            $this->formatCbResponseFailed($response, $ret);
            return $response;
        }

        $args = [
            'api_key' => $api_key,
            'token' => $chatToken,
            'orderId' => $orderId,
            'currency' => null,
            'brand' => null,
        ];

        $ret = $this->getPlayewithdrawalByOrder(true, $args);
        $this->utils->debug_log('chatBotGetPlayeWithdrawals result', $ret);

        if(!empty($ret) && $ret['success'] && !empty($ret['result'])){
            foreach($ret['result'] as $key => $value){
                $this->formatCbResponseMessage($response, $key, $value);
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }

        return $response;
    } // End function chatBotGetPlayeWithdrawals()

    public function chatBotPlayerWithdrawalConditions($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $response = [];
        $chatToken = !empty($attributes['token']) ? $attributes['token'] : null;
        if(empty($chatToken)){
            $ret['mesg'] = 'Chat Token is empty !';
            $this->formatCbResponseFailed($response, $ret);
            return $response;
        }

        $args = [
            'api_key' => $api_key,
            'token' => $chatToken,
            'currency' => null,
            'brand' => null,
        ];

        $ret = $this->playerWithdrawalConditions(true, $args);
        $this->utils->debug_log('playerWithdrawalConditions result', $ret);

        if(!empty($ret) && $ret['success']){
            foreach($ret['result'] as $key => $value){
                $this->formatCbResponseMessage($response, $key, $value);
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }

        return $response;
    } // End function chatBotPlayerWithdrawalConditions()

    public function chatBotPlayerVipStatus($api_key, $request_body){
        $attributes = !empty($request_body['attributes']) ? $request_body['attributes'] : null;
        $this->utils->debug_log(__METHOD__, 'attributes:', $attributes);

        $response = [];
        $chatToken = !empty($attributes['token']) ? $attributes['token'] : null;
        if(empty($chatToken)){
            $ret['mesg'] = 'Chat Token is empty !';
            $this->formatCbResponseFailed($response, $ret);
            return $response;
        }

        $args = [
            'api_key' => $api_key,
            'token' => $chatToken,
            'currency' => null,
            'brand' => null
        ];

        $ret = $this->playerVipStatus(true, $args);
        $this->utils->debug_log('playerVipStatus result', $ret);

        if(!empty($ret) && $ret['success']){
            foreach($ret['result'] as $key => $value){
                $this->formatCbResponseMessage($response, $key, $value);
                $this->formatCbResponseAttributes($response, $key, $value);
            }
        }else{
            $this->formatCbResponseFailed($response, $ret);
        }

        return $response;
    } // End function chatBotPlayerVipStatus()

    public function chatBotWebhook($action = null){
        $api_key = 'e442540c1337519cb6e952ffa846b794'; // hugebet
        // $api_key = '42f77fd0409566c6123d58551e85ee87'; // local
        $this->utils->debug_log(__METHOD__, $api_key);
        // check token in every request
        if (!isset($_GET['token']) || $_GET['token'] !== $api_key) {
            header('HTTP/1.0 401 Unauthorized');
            exit();
        }

        // verification request
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            exit($_GET['challenge']);
        }

        $request_body = $this->playerapi_lib->getRequestPramas();
		$this->utils->debug_log(__METHOD__, '=======request_body getRequestPramas', $request_body);
        $this->utils->debug_log('chatBotToken action', $action);

        $response = [];
        if(!empty($action)){
            switch($action){
                case 'cbToken':
                    $response = $this->chatBotToken($api_key, $request_body);
                    break;
                case 'cbGetPlayerInfo': // chat bot getPlayerInfo
                    $response = $this->chatBotGetPlayerInfo($api_key, $request_body);
                    break;
                case 'cbGetPlayeDeposits': // chat bot getPlayeDeposits
                    $response = $this->chatBotGetPlayeDeposits($api_key, $request_body);
                    break;
                case 'cbGetPlayeWithdrawals': // chat bot getPlayeWithdrawals
                    $response = $this->chatBotGetPlayeWithdrawals($api_key, $request_body);
                    break;
                case 'cbPlayerWithdrawalConditions': // chat bot playerWithdrawalConditions
                    $response = $this->chatBotPlayerWithdrawalConditions($api_key, $request_body);
                    break;
                case 'cbPlayerVipStatus': // chat bot playerVipStatus
                    $response = $this->chatBotPlayerVipStatus($api_key, $request_body);
                    break;
                default:
                $this->utils->debug_log('chatBotToken action is empty');
                    break;
            }
        }

        header('Content-Type: application/json');
        $this->utils->debug_log(__METHOD__, 'response', $response);
        echo json_encode($response);
    }
} // End trait t1t_comapi_module_chatai_player
