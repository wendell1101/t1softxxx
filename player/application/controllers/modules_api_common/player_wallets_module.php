<?php

/**
 * uri: /wallets
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 */
trait player_wallets_module {

    private $game_api = null;
    public function wallets($action= null, $additional = null) {

        $request_method = $this->input->server('REQUEST_METHOD');
        if(!$this->initApi()){
            return;
        }

        if($action=='collect-all-balances'){
            if($request_method == 'POST') {
                return $this->transferAllToMainWallet();
            }
        }
        else if(is_numeric($action)){
            $validate_fields = [
                ['name' => 'additional', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => ['deposit', 'withdraw', null]],
            ];

            $request_body['additional'] = $additional;
            $this->utils->debug_log('[' . __METHOD__ . '] Request Body', $request_body);
            $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

            if(!$is_validate_basic_passed['validate_flag']) {
                $result['code'] = self::CODE_INVALID_PARAMETER;
                $result['errorMessage']= $is_validate_basic_passed['validate_msg'];

                $this->utils->debug_log('[' . __METHOD__ . '] Validation Failed', $is_validate_basic_passed['validate_msg']);
                return $this->returnErrorWithResult($result);
            }

            $this->game_api = $this->utils->loadExternalSystemLibObject($action);

            if($additional=='deposit' && $request_method == 'POST') {
                return $this->depositToGame($this->game_api->getPlatformCode());

            }
            else if($additional=='withdraw' && $request_method == 'POST') {
                return $this->withdrawFromGame($this->game_api->getPlatformCode());

            }
            else if(empty($additional)){
                if($request_method == 'GET') {
                    $response = $this->getSubWalletInfo($this->game_api->getPlatformCode());
                    if($response['code'] == self::CODE_OK) {
                        return $this->returnSuccessWithResult($response);
                    }
                    else {
                        return $this->returnErrorWithCode($response['code'], null, $response['data']);
                    }
                }
            }
        }
        else if(empty($action)){

            if($request_method == 'GET') {
                return $this->getPlayerWalletInformation();
            }
        }

        return $this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
    }

    private function getPlayerWalletInformation() {

        $add_prefix = true;
        $is_lock_failed = false;
        $big_wallet = null;

        $found_error = false;

        $success = $this->wallet_model->lockAndTransForPlayerBalance($this->player_id, function () use (&$big_wallet){
            $big_wallet = $this->wallet_model->getBigWalletByPlayerId($this->player_id);
            $this->utils->debug_log('query_player_total_balance bigWallet',$big_wallet);
            return true;

        }, $add_prefix, $is_lock_failed);

        if($success) {

            $sub_wallets = [];

            $mainWalletfrozen = $this->utils->formatCurrencyNumber($big_wallet['main']['frozen']);
    
            $mainWallet = $this->utils->formatCurrencyNumber($big_wallet['main']['total_nofrozen']);

            $result = [
                'gameApiId' => Wallet_model::MAIN_WALLET_ID,
                'gameApiName' => 'Main Wallet',
                'currency' => $this->utils->getActiveCurrencyKey(),
                'balance' => floatval($mainWallet),
                'pending' => floatval($mainWalletfrozen),
                'dirty' => true,
                'lastSync' => $this->playerapi_lib->formatDateTime($this->utils->getNowForMysql()),
                'allowDecimalTransfer' => true,
                'status' => 1,
            ];

            $sub_wallets[] = $result;

            if(isset($big_wallet['sub'])) {
                foreach($big_wallet['sub'] as $key => $value){
                    $game_platform_id = (int)$key;

                    $sub_wallet_balance = isset($value['total_nofrozen']) ? floatval($value['total_nofrozen']) : 0;

                    $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
                    $refresh_balance = $this->utils->getConfig('player_center_api_enable_refresh_subwallet');
                    if($sub_wallet_balance>0 && $refresh_balance){
                        $found_error = empty($api);
                        if(!empty($api) && !$api->isSeamLessGame()) {
                            $response = $api->queryPlayerBalance($this->username);
                            if($response && $response['success']){
                                $sub_wallet_balance = floatval($response['balance']);

                                $success = $this->wallet_model->lockAndTransForPlayerBalance($this->player_id, function () use ($game_platform_id, $response){
                                    return $this->wallet_model->updateSubWalletsOnBigWallet($this->player_id, [$game_platform_id=>$response]);
                                });
                            }
                            else {
                                $found_error = true;
                                $this->utils->debug_log('[' . __METHOD__ . '] Sub Wallet Update Failed', ['game_platform_id', $game_platform_id, 'response' => $response]);
                            }
                        }
                    }

                    $this->load->model(['external_system']);
                    $system_info = $this->external_system->getSystemById($game_platform_id);
                    $result = [
                        'gameApiId' => $game_platform_id,
                        'gameApiName' => $system_info->system_code,
                        'currency' => $this->utils->getActiveCurrencyKey(),
                        'balance' => $sub_wallet_balance,
                        'pending' => 0,
                        'dirty' => true,
                        'lastSync' => $this->playerapi_lib->formatDateTime($this->utils->getNowForMysql()),
                        'allowDecimalTransfer' => !$api->onlyTransferPositiveInteger(),
                        'status' => $this->getGameApiStatus($api),
                    ];

                    $sub_wallets[] = $result;
                }
            }

            if($found_error){
                return $this->returnErrorWithCode(Playerapi::CODE_EXTERNAL_API_ERROR);
            }

            $detail=[
                'code' => Playerapi::CODE_OK,
                'data' => $sub_wallets
            ];
            return $this->returnSuccessWithResult($detail);
        }
        else if($is_lock_failed) {
            return $this->returnErrorWithCode(Playerapi::CODE_SERVER_ERROR);
        }
        else {
            $this->utils->debug_log('[' . __METHOD__ . '] Get Big Wallet Failed');
            return $this->returnErrorWithCode(Playerapi::CODE_SERVER_ERROR);
        }
    }

    private function depositToGame($game_platform_id, $amount = null) {
        if($amount == null) {
            $amount = $this->getParam('amount');
        }
        $validate_fields = [
            ['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
        ];

        $request_body = $this->playerapi_lib->getRequestPramas();
        $this->utils->debug_log('[' . __METHOD__ . '] Request Body', $request_body);
        $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

        if(!$is_validate_basic_passed['validate_flag']) {
            $result['code'] = self::CODE_INVALID_PARAMETER;
            $result['errorMessage']= $is_validate_basic_passed['validate_msg'];

            $this->utils->debug_log('[' . __METHOD__ . '] Validation Failed', $is_validate_basic_passed['validate_msg']);
            return $this->returnErrorWithResult($result);
        }

        $result = $this->utils->transferWallet($this->player_id, $this->username, Wallet_model::MAIN_WALLET_ID, $game_platform_id, $amount);
        $responseResultId = isset($result['response_result_id']) ? $result['response_result_id'] : null;
        $external_transaction_id = isset($result['external_transaction_id']) ? $result['external_transaction_id'] : null;
        $transfer_status = isset($result['transfer_status']) ? $result['transfer_status'] : Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
        $reason_id = isset($result['reason_id']) ? $result['reason_id'] : Abstract_game_api::REASON_UNKNOWN;


        if (isset($result['success']) && $result['success']) {
            $this->utils->debug_log('[' . __METHOD__ . '] Deposit Success', [ 'player' => $this->username, 'to' => $game_platform_id, 'amount' => $amount]);
            $result = [
                'code' => self::CODE_OK,
                'data' => [
                    'success' => true,
                    'transaction_id' => $external_transaction_id,
                    'transfer_status' => $transfer_status,
                    'reason_id' => $reason_id,
                    'response_result_id' => $responseResultId
                ]
            ];
        } else {
            $this->utils->debug_log('[' . __METHOD__ . '] Deposit Failed', [ 'player' => $this->username, 'to' => $game_platform_id, 'amount' => $amount]);
            return $this->returnErrorWithCode($this->getTransferReason($reason_id));
        }
        return $this->returnSuccessWithResult($result);

    }

    private function withdrawFromGame($game_platform_id, $amount = null) {
        if($amount == null) {
            $amount = $this->getParam('amount');
        }
        $validate_fields = [
            ['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
        ];

        $request_body = $this->playerapi_lib->getRequestPramas();
        $this->utils->debug_log('[' . __METHOD__ . '] Request Body', $request_body);
        $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

        if(!$is_validate_basic_passed['validate_flag']) {
            $result['code'] = self::CODE_INVALID_PARAMETER;
            $result['errorMessage']= $is_validate_basic_passed['validate_msg'];

            $this->utils->debug_log('[' . __METHOD__ . '] Validation Failed', $is_validate_basic_passed['validate_msg']);
            return $this->returnErrorWithResult($result);
        }

        $result = $this->utils->transferWallet($this->player_id, $this->username, $game_platform_id, Wallet_model::MAIN_WALLET_ID, $amount);
        $responseResultId = isset($result['response_result_id']) ? $result['response_result_id'] : null;
        $external_transaction_id = isset($result['external_transaction_id']) ? $result['external_transaction_id'] : null;
        $transfer_status = isset($result['transfer_status']) ? $result['transfer_status'] : Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN;
        $reason_id = isset($result['reason_id']) ? $result['reason_id'] : Abstract_game_api::REASON_UNKNOWN;


        if (isset($result['success']) && $result['success']) {
            $this->utils->debug_log('[' . __METHOD__ . '] Withdraw Success', [ 'player' => $this->username, 'to' => $game_platform_id, 'amount' => $amount]);
            $result = [
                'code' => self::CODE_OK,
                'data' => [
                    'success' => true,
                    'transaction_id' => $external_transaction_id,
                    'transfer_status' => $transfer_status,
                    'reason_id' => $reason_id,
                    'response_result_id' => $responseResultId
                ]
            ];
        } else {
            $this->utils->debug_log('[' . __METHOD__ . '] Withdraw Failed', [ 'player' => $this->username, 'to' => $game_platform_id, 'amount' => $amount]);
            return $this->returnErrorWithCode($this->getTransferReason($reason_id));
        }
        return $this->returnSuccessWithResult($result);

    }

    private function getTransferReason($reason_id) {
        switch($reason_id) {
            case Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
                return self::CODE_INSUFFICIENT_BALANCE;
            default:
                return self::CODE_GENERAL_CLIENT_ERROR;
        };
    }
    private function getGameApiStatus($api) {
        if($api->isMaintenance()) {
            return 9;
        }
        else if($api->isDisabled()) {
            return 0;
        }
        else {
            return 1;
        }
    }

    private function getSubWalletInfo($game_platform_id)
    {

        $game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $response = $game_api->queryPlayerBalance($this->username);
        if($response && $response['success']) {
            $player_id = $this->player_id;
            $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
            $add_prefix = true;
            $is_lock_failed = false;
            $this->load->model(['external_system']);
            $system_info = $this->external_system->getSystemById($game_platform_id);
            $result = [
                'code' => self::CODE_OK,
                'data' => [
                    'gameApiId' => $game_platform_id,
                    'gameApiName' => $system_info->system_code,
                    'currency' => $this->utils->getActiveCurrencyKey(),
                    'balance' => $response['balance'],
                    'pending' => 0,
                    'dirty' => false,
                    'lastSync' => str_replace('+00:00', 'Z', gmdate('c')),
                    'allowDecimalTransfer' => !$api->onlyTransferPositiveInteger(),
                    'status' => $this->getGameApiStatus($api),
                ]
            ];

            $success = $this->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($game_platform_id, $response, &$result, $player_id){
                $big_wallet = $this->wallet_model->getBigWalletByPlayerId($player_id);
                $result['dirty'] = $this->wallet_model->refreshSubWalletByBigWallet($big_wallet, $game_platform_id, $response['balance']);

                return $this->wallet_model->updateSubWalletsOnBigWallet($player_id, [$game_platform_id => $response]);

            }, $add_prefix, $is_lock_failed);

            $this->utils->debug_log('[' . __METHOD__ . '] Update Subwallet', [ 'success' => $success, 'lock_failed' => $is_lock_failed]);
            return $result;

        }else{

            $this->utils->debug_log('[' . __METHOD__ . '] Fetch Balance From API Failed', [ 'game_platform_id' => $game_platform_id, 'response' => $response]);
            return [
                'code' => self::CODE_EXTERNAL_API_ERROR,
                'data' => $response
            ];
        }

    }

    private function transferAllToMainWallet()
    {
        $response = $this->utils->transferAllWallet($this->player_id, $this->username, Wallet_model::MAIN_WALLET_ID);

        if($response && $response['success']) {
            $result = [
                'code' => self::CODE_OK,
                'data' => [
                    'success' => true,
                    'message' => $response['message']
                ]
            ];

            $this->utils->debug_log('[' . __METHOD__ . '] Transfer All To Main Successful', ['response' => $response]);
            return $this->returnSuccessWithResult($result);
        }
        else {
            $result['errorMessage'] = isset($response['message']) ? $response['message'] : '';
            $this->utils->debug_log('[' . __METHOD__ . '] Transfer All To Main Failed', ['response' => $response]);
            return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
        }
    }

}
