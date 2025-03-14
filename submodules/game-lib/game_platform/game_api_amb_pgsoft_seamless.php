<?php
/**
 * AMB PGSoft game integration
 * OGP-25791
 *
 * @author  Kristallynn Tolentino
 *
 *
 * API DOC: https://uat.ambsuperapi.com
 *
 */

require_once dirname(__FILE__) . '/game_api_hotgraph_seamless.php';

class Game_api_amb_pgsoft_seamless extends Game_api_hotgraph_seamless {

    const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

    const URI_MAP = [
        self::API_createPlayer => "/seamless/logIn",
        self::API_login => "/seamless/logIn",
        self::API_syncGameRecords => "/seamless/betTransactionsV2",
        self::API_queryGameListFromGameProvider => '/seamless/games',
    ];

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://api.hentory.io');
        $this->lang = $this->getSystemInfo('lang','th_TH');
        $this->currency = $this->getSystemInfo('currency', 'THB');

        $this->product_id = $this->getSystemInfo('product_id', 'PGSOFT2');
        $this->x_api_key = $this->getSystemInfo('x_api_key', 'f9274b03-6f88-4d04-b7fc-52baaa7714d7');
        $this->agent_username = $this->getSystemInfo('agent_username', 'superdt002dev');
        $this->default_gamecode = $this->getSystemInfo('default_gamecode', '');
        $this->default_game_launch = $this->getSystemInfo('default_game_launch', '');

        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return AMB_PGSOFT_SEAMLESS_API;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function generateUrl($apiName, $params) {
        $uri = self::URI_MAP[$apiName];

        if($params['actions']['method']==self::GET){
            $url = $this->api_url . $uri. '?' . http_build_query($params["params"]);
        }else{
            $url = $this->api_url . $uri;
        }

        return $url;
    }

    protected function customHttpCall($ch, $params) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->agent_username.":".$this->x_api_key)
        ];

        $function = $params["actions"]['function'];

        if($params["actions"]["method"] == self::POST){

            unset($params["actions"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            if(isset($params["json_body"])){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["json_body"]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

        }else{
            unset($params["actions"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            if($function == self::API_queryGameListFromGameProvider){
                curl_setopt($ch, CURLOPT_POST, FALSE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params["params"]));
            }
        }

	}


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for AMB PGSOFT";
        if($return){
            $success = true;
            $message = "Successfull create account for AMB PGSOFT.";
        }

        return array("success" => $success, "message" => $message);
    }


    public function login($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $player_token = $this->getPlayerTokenByUsername($playerName);


        if ($this->utils->is_mobile()) {
            $is_mobile = true;
        }else{
            $is_mobile = false;
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $json_body = array(
            'username' => $gameUsername,
            'productId' => $this->product_id,
            'gameCode' => $extra['game_code'],
            'isMobileLogin' => $is_mobile,
            'sessionToken' => $player_token,
            'betLimit' => []
        );

        $params = array(
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_login,
                "method" => self::POST
            ]
        );

        return $this->callApi(self::API_login, $params, $context);
	}

    public function processResultForLogin($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        if(isset($resultArr['data']) && !empty($resultArr['data'])){
            $result = array(
                "response_result_id" => $responseResultId,
                "success" => $success,
                "request_id" => $resultArr['reqId'],
                "code" => $resultArr['code'],
                "data" => $resultArr['data']['url'],
                "message" => $resultArr['message']
            );
        }else{
            $result = array(
                "response_result_id" => $responseResultId,
                "success" => $success,
                "request_id" => $resultArr['reqId'],
                "code" => $resultArr['code'],
                "data" => $this->default_game_launch,
                "message" => $resultArr['message'],
            );
        }

		return array($success, $result);
	}

    public function queryGameListFromGameProvider($extra=null){

        $context = array(
			'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
		);

        $param_product = array(
            'productId' => $this->product_id
        );

        $params = array(
            "params" => $param_product,
            "actions" => [
                "function" => self::API_queryGameListFromGameProvider,
                "method" => self::GET
            ]
        );

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {

        $resultArr = $this->getResultJsonFromParams($params);

        $this->CI->utils->debug_log('TEST-AMBPGSOFT-RESULTARR', $resultArr);

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result['games'] = [];

        if($success){
            $this->CI->utils->debug_log('TEST-AMBPGSOFT-RESULTARR', $resultArr);
            if(isset($resultArr['data']['games'])){
                $result['games'] = $resultArr['data']['games'];
                if(!empty($result['games'])){
                    $this->updateGameList($result['games']);
                }
            }
        } else {
            $this->CI->utils->debug_log('TEST-TAL-ERROR', $resultArr);
            if(isset($resultArr['message'])){
                $result['message'] = $resultArr['message'];
            }
        }
        return array($success, $result);
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $this->preProccessGames($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            'amb_pgsoft_gamelist',
            $games,
            'external_uniqueid',
            'external_uniqueid',
            ['game_name','game_type','game_code'],
            'md5_sum',
            'id',
            []
        );

        $dataResult = [
            'data_count' => count($games),
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertGameList($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertGameList($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE AMB_PGSOFT GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW AMB_PGSOFT GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type | Game Category |\n";
            $body .= "| :--- | :--- | :--- |\n";
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal('amb_pgsoft_gamelist', $record);
                    $body .= "| {$record['game_name']} | N/A | {$record['game_code']} | {$record['game_type']} | {$record['game_category']} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal('amb_pgsoft_gamelist', $record);
                    $body .= "| {$record['game_name']} | N/A | {$record['game_code']} | {$record['game_type']} | {$record['game_category']} |\n";
                }
                $dataCount++;
                unset($record);
            }
            $this->sendMatterMostMessage($caption, $body);
        }
        return $dataCount;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
            "#amb_pgsoft Game"
        ];

        $channel = $this->utils->getConfig('game_list_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $channel = $channel;
        $user = 'amb_pgsoft Game List';

        sendNotificationToMattermost($user, $channel, [], $message);

    }

    public function preProccessGames(&$games) {
        if(!empty($games)){
            foreach ($games as $key => $game) {
                $data['game_name'] = $game['name'];
                $data['game_category'] = $game['category'];
                $data['game_type'] = $game['type'];
                $data['game_code'] = $game['code'];
                $data['game_rank'] = $game['rank'];

                $data['external_uniqueid'] = $game['code'];
                $data['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($data, ['game_name','game_type','game_code']);
                $games[$key] = $data;
                unset($data);
            }
        }
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                if($transaction['amount'] < 0){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }else{
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                }
                // $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                // if(in_array($transaction['trans_type'], ['debit'])){
                    // $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                // }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','th-th']);
    }
    
}