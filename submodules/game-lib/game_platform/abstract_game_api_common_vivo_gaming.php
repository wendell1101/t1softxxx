<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * API NAME: VIVOGAMING_API
 * Ticket Number: OGP-13988
 * 
 * @see Vivo gaming system (VGS) - funds transfer/two wallet-games integration v. 1.5
 * @see Vivo gaming system (VGS) - live games unified activation - v 1.7
 * @see 2 wallet reports - Reporting APIs – Funds Transfer Integration
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_vivo_gaming extends Abstract_game_api {

   /** 
    * * $serverId is for vivo aladin
    * @var
   */
   protected $original_gamelogs_table;
   protected $api_url;
	protected $casino_id;
	protected $operator_id;
	protected $operator_key;
	protected $server_id;
	protected $hash_passkey;
	protected $account_number;
	protected $account_pin;
   protected $currentAPI;
   protected $logo_setup;
   protected $default_selected_game_in_lobby;
   protected $IsInternalPop;
   protected $homeURL;
   protected $cashierURL;
   protected $demo_url;
   protected $logo_url;
   protected $sync_step_in_seconds;
   protected $serverId;
   protected $strictResponseCheck;

   /** 
    * True/False As default bets will be sent automatically when
    * betting times end, if passed “true” user should press “submit bets” button
    * for bets to be confirmed
   */
   protected $IsPlaceBetCTA; 
   
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const CODE_SUCCESS = 0; # Success code in API
    const CODE_BET_IN_PROGRESS = 107; # bet in progress code in API

    /** 
     * Actions of API
    */
    const ACTION_DEPOSIT = 'DEPOSIT';
    const ACTION_WITHDRAW = 'WITHDRAW';
    const ACTION_CHECK = 'CHECK';
    const ACTION_VALIDATE = 'VALIDATE';

    /**
     * selected game when launching in the lobby
     * Note: always lower case
     * (lobby, roulette, baccarat, blackjack)
     * @var const 
     */
    const SELECTED_GAME = [
      'lobby',
      'roulette',
      'baccarat',
      'blackjack',
      'casinoholdem'
    ];

    const SELECTED_GAME_WITHOUT_LOBBY = [
      'roulette',
      'baccarat',
      'blackjack',
      'casinoholdem',
      'gtm-lobby'
    ];

    /** 
     * lobby code
     * 
     * @var const
    */
    const LOBBY_CODE = 'lobby';

   /** 
     * vivo aladin lobby code
     * 
     * @var const
    */
    const ALADIN_LOBBY_CODE = 'gtm-lobby';

    /** 
     * Fields in original game logs  table, we want to detect changes for update in fields
     * 
     * player_login_name - (as used in transfer API / login request)
     * transaction_type - (BET:GameName / WIN:GameName)
     * transaction_type_id - (1 = Game Transaction, Other Transaction Types)
     * card_provider_id - For Cashier Accounts Only
     * card_number - or Cashier Accounts Only
     * @param constant MD5_FIELDS_FOR_ORIGINAL
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
      'accounting_transaction_id',
      'player_login_name',
      'transaction_date',
      'transaction_type',
      'transaction_type_id',
      'balance_before',
      'debit_amount',
      'credit_amount',
      'balance_after',
      'table_round_id',
      'table_id',
      'card_provider_id',
      'card_number',
      'game_name',
      'game_id',
    ];

    const SIMPLE_MD5_FIELDS_FOR_ORIGINAL = [
      'accounting_transaction_id',
      'player_login_name',
      'transaction_date',
      'debit_amount',
      'credit_amount',
      'table_round_id',
      'table_id',
    ];

    /**
     * Values of these fields will be rounded when calculating MD5
     * 
     * @param constant MD5_FLOAT_AMOUNT_FIELDS
     */
    const MD5_FLOAT_AMOUNT_FIELDS = [
      'balance_before',
      'debit_amount',
      'credit_amount',
      'balance_after',
    ];

    /** 
     * Fields in game_logs table, we want to detect changes for merge, and when original game logs.md5_sum table is empty
     * 
     * @param constant MD5_FIELDS_FOR_MERGE
    */
    const MD5_FIELDS_FOR_MERGE = [
      'external_uniqueid',
      'debit_amount',
      'credit_amount',
      'round',
      'game_code',
      'game_name',
      'round',
      'username',
      'before_balance',
      'after_balance',
      'transaction_date'
    ];

    /** 
     * Values of these fields will be rounded when calculating MD5
     * 
     * @param constant MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
    */
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
      'debit_amount',
      'credit_amount',
      'before_balance',
      'after_balance',
    ];

    /** 
     * This is the URI endpoints of API
     * 
     * @param constant URI_MAP
    */
    const URI_MAP = [
       self::API_createPlayer => '/IntegrationRequestHttp.aspx',
       self::API_depositToGame => '/IntegrationRequestHttp.aspx',
       self::API_withdrawFromGame => '/IntegrationRequestHttp.aspx',
       self::API_queryPlayerBalance => '/IntegrationRequestHttp.aspx',
       self::API_login => '/flash/loginplayer.aspx',
       self::API_isPlayerExist => '/IntegrationRequestHttp.aspx',
       self::API_syncGameRecords => '/IntegrationTwoWallet/GetHistoryApi.aspx',
       self::API_queryTransaction => '/IntegrationRequestHttp.aspx'
    ];

    public function __construct()
    {
       parent::__construct();

       $this->original_gamelogs_table = $this->getOriginalTable();
       $this->api_url = $this->getSystemInfo('url','https://www.1vivo.com');
       $this->casino_id = $this->getSystemInfo('casino_id','23');
       $this->operator_id = $this->getSystemInfo('operator_id','32537');
       $this->operator_key = $this->getSystemInfo('operator_key','A8PV5atK7l');
       $this->server_id = $this->getSystemInfo('server_id','');
       $this->hash_passkey = $this->getSystemInfo('hash_passkey','714918185865');
       $this->account_number = $this->getSystemInfo('account_number','133540');
       $this->account_pin = $this->getSystemInfo('account_pin','6434');
       $this->game_launcher_url = $this->getSystemInfo('game_launcher_url','https://games.vivogaming.com');
       $this->mobile_game_launcher_url = $this->getSystemInfo('mobile_game_launcher_url','');
       $this->logo_setup = $this->getSystemInfo('logo_setup','VIVO_LOGO');
       $this->default_selected_game_in_lobby = $this->getSystemInfo('default_selected_game_in_lobby','');
       $this->IsInternalPop = $this->getSystemInfo('IsInternalPop',true);
       $this->IsPlaceBetCTA = $this->getSystemInfo('IsPlaceBetCTA',true);
       $this->homeURL = $this->getSystemInfo('homeURL','');
       $this->cashierURL = $this->getSystemInfo('cashierURL','');
       $this->demo_url = $this->getSystemInfo('demo_url','https://games.vivogaming.com/?token=ICE2016-2&operatorid=1453&&serverid=3649143&language=EN&Application=lobby');
       $this->logo_url = $this->getSystemInfo('logo_url','');
       $this->sync_step_in_seconds = $this->getSystemInfo("sync_step_in_seconds",3600);
       $this->serverId = $this->getSystemInfo("server_id",null);
       $this->strictResponseCheck = false;
       $this->default_game_type = $this->getSystemInfo("default_game_type",'lobby');
       $this->lobby_launch_if_game_unique_category_is_set = $this->getSystemInfo("lobby_launch_if_game_unique_category_is_set",true);
       $this->substr_start = $this->getSystemInfo("substr_start",-9);

    }

    /**
     * Generate URL
     * 
     * @return string $url the url of current execution
     */
    public function generateUrl($apiName,$params)
    {
       switch($this->currentAPI){
         case "/IntegrationRequestHttp.aspx":
				$params_string = http_build_query($params);
				$url = $this->api_url.self::URI_MAP[$apiName]. "?" . $params_string;
			   break;
          case "/IntegrationRequest.aspx":
            $url = $this->api_url.self::URI_MAP[$apiName];
            break;
         case "/flash/loginplayer.aspx":
				$params_string = http_build_query($params);
				$url = $this->api_url.self::URI_MAP[$apiName]. "?" . $params_string;
			   break;
			case "/IntegrationTwoWallet/GetHistoryApi.aspx":
				$params_string = http_build_query($params);
				$url = $this->api_url.self::URI_MAP[$apiName]. "?" . $params_string;
			   break;
          default:
            $url = $this->api_url.self::URI_MAP[$apiName];
            break;
       }

       return $url;
    }

    /** 
     * Custom HTTP call
    */
    protected function customHttpCall($ch,$params)
    {
       switch($this->currentAPI){
         case "/IntegrationRequest.aspx":
            $this->setUpCurl($ch,$params);
            break;
         default:
            break;
       }
    }

    /**
     * Setup of Curl
     */
    protected function setUpCurl($ch,$params)
    {
       $xml_object = new SimpleXMLElement("<DATA></DATA>");
       $xml_data = $this->CI->utils->arrayToXml($params,$xml_object);

       curl_setopt($ch,CURLOPT_POST,true);
       curl_setopt($ch,CURLOPT_POSTFIELDS,$xml_data);
    }

    function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

      return array(false, null);
   }
   
   /** 
    * Process Result boolean
    * true - means success
    * false - means error
    *
    *@return boolean
   */
   public function processResultBoolean($responseResultId, $resultArr, $playerName = null)
   {
      $success = !empty($resultArr);

      # for strict check in deposit and withdraw, not empty response is not enough
      if($this->strictResponseCheck){
         $success = false;
         if(isset($resultArr['Status'])){
            $success = !empty($resultArr)&&$resultArr['Status'] != 'E';
         }
      }else{
         if(isset($resultArr['Status'])){
            $success = !empty($resultArr)&&$resultArr['Status'] != 'E';
         }
      }
		if (!$success) {
         $success = false;
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('VIVO_GAMING got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
      }
      
		return $success;
   }
   
   /** 
    * Convert Text API response to array
    *
    *@return array
   */
   protected function vivoResultTextToArray($resultTxt)
   {
      $resultArr = array();
      $resultTxt = str_replace(array('{','}'),array('',''),$resultTxt);

      foreach(explode(",",$resultTxt) as $exVal){
         $val = explode("=",$exVal);
         $resultArr[$val[0]] = isset($val[1])?$val[1]:'';
      }

      return $resultArr;
   }

   /**
    * Generate Hash for Parameter
    * @param string $gameUsername the name of user in game
    * @param int $amount the amount of money
    * @param int $uniqueid external unique id
    * @param int $hash_passkey  the password for hashing
    *
    *
   */
   protected function generateHash($gameUsername,$amount,$uniqueid,$hash_passkey)
   {
      $hash = md5($gameUsername.$amount.$uniqueid.$hash_passkey);

      return $hash;
   }

   /**
    * Generate Unique ID for Parameter
    *
    * @param string $gameUsername the name of user in game
    *
    * @return int $uniqueid the 9 digits unique id
    */
    protected function generateUniqueId($gameUsername)
    {
      $uniqueid = (integer) (substr(hexdec(md5(date('Y-m-d H:i:s').$gameUsername)),0,9)*100000000); //get a 9 digit random

      return $uniqueid;
    }

   /** 
     * Round down number, meaning 0.019 will be 0.01 instead round up 0.019 to 0.02
    */
    private function round_down($number,$precision = 3)
    {

        $fig = (int) str_pad('1', $precision, '0');

	    return (floor($number * $fig) / $fig);
    }

    /**
     * Convert the response text of API into as associative array with key is column_name and value is the value of column_name
     * 
     * @param string $resultText the result text from API
     * 
     * @return array $resultText the array of game records
     */
    protected function vivoLogsResultTextToArray($resultText)
    {    
      $resultArray = [];
      $resultText = explode("}{",$resultText);

      if(isset($resultText[1])){

         if(isset($resultText[0])){

            $row_count = explode("=",$resultText[0]);
            
            $resultArray['row_count'] = isset($row_count[1]) ? $row_count[1] : 0;
         }

         $fields = [
            'accounting_transaction_id',
            'player_login_name',
            'transaction_date',
            'transaction_type',
            'transaction_type_id',
            'balance_before',
            'debit_amount',
            'credit_amount',
            'balance_after',
            'table_round_id',
            'table_id',
            'card_provider_id',
            'card_number',
            'game_name',
            'game_id',
         ];

         $resultText = str_replace(array('{','}'),array('',''),$resultText[1]);

         if($resultText){
            foreach(explode('[NL]',$resultText) as $index => $separated_row){
               $delimited_rows = explode(';',$separated_row);
               foreach($fields as $key => $value){
                  $resultArray['data'][$index][$value] = $delimited_rows[$key];
               }
         }   
         }
      }
      
      return $resultArray;
    }
   
   /** 
    * Create Player
   */
   public function createPlayer($playerName,$playerId,$password,$email=null,$extra=null)
   {
      /*$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

      $is_deposit_success = $this->depositToGame($playerName, 0);

      if($is_deposit_success["success"]){
         
         parent::createPlayer($gameUsername, $playerId, $password, $email, $extra);

         return [
            'success'=>true,
            [
               'playerName' => $playerName
            ]
         ];
      }

      return [
         'success'=>false,
         [
            'playerName' => $playerName
         ]
      ];*/

      $this->strictResponseCheck = true;
      //will deposit 0 to create player
      $this->currentAPI = self::URI_MAP[self::API_createPlayer];
      $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
      $external_transaction_id = $this->casino_id.uniqid();
      $amount = 0;
      parent::createPlayer($gameUsername, $playerId, $password, $email, $extra);

      $context = [
         'callback_obj' => $this,
         'callback_method' => 'processResultForCreatePlayer',
         'playerName' => $playerName,
         'gameUsername' => $gameUsername,
         'playerId' => $playerId,
         'password' => $password,
         'email' => $email,
         'extra' => $extra,
         'external_transaction_id' => $external_transaction_id,
      ];

      $uniqueid =  $this->generateUniqueId($gameUsername);
      $hash = $this->generateHash($gameUsername,$amount,$uniqueid,$this->hash_passkey);
      //$password = $this->getPassword($playerName);

      $params = array(
         'CasinoID' => $this->casino_id,
         'OperatorID' => $this->operator_id,
         'UserName' => $gameUsername,
         'UserPWD' => $password,
         'UserID'=> $gameUsername,
         'AccountNumber'=> $this->account_number,
         'AccountPin'=> $this->account_pin,
         'Amount' => $amount,
         'TransactionType'=> self::ACTION_DEPOSIT,
         'TransactionID'=> $uniqueid,
         'Hash'=> $hash
      );

      $this->CI->utils->debug_log('VIVO_GAMING createPlayer params >>>>>>>>>>>>>>>>',$params);

      return $this->callApi(self::API_createPlayer, $params, $context);
   }

	public function processResultForCreatePlayer($params) {
      $this->utils->debug_log("VIVO_GAMING processResultForCreatePlayer params:", $params);

      $responseResultId = $this->getResponseResultIdFromParams($params);
      $resultXml = $this->getResultXmlFromParams($params);
      $arrayResult = json_decode(json_encode($resultXml),true);
      $playerName = $this->getVariableFromContext($params, 'playerName');
      $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
      $playerId = $this->getVariableFromContext($params, 'playerId');

      $result = [
         'exists' => false,
         'player' => $gameUsername,
         'response_result_id' => $responseResultId
      ];

      $this->utils->debug_log("VIVO_GAMING processResultForCreatePlayer arrayResult:", $arrayResult);

      $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

      if ($success) {

         $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

         return [
            'success'=>true,
            [
               'playerName' => $playerName,
               'player' => $gameUsername,
               'exists' => true,
               'response_result_id' => $responseResultId
            ]
         ];
       }

      return array($success, $result);
   }

   /**
    * Deposit To Game
    * TransactionID should be max 9 digits only
    */
    public function depositToGame($playerName,$amount,$transfer_secure_id=null)
    {
      $this->strictResponseCheck = true;
       $this->currentAPI = self::URI_MAP[self::API_depositToGame];
       $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       $external_transaction_id = $transfer_secure_id;

       if(strlen((string) $transfer_secure_id) > 9 ||  preg_match("/[a-z]/i", $transfer_secure_id)){
          $external_transaction_id = substr($transfer_secure_id,$this->substr_start);
       }

       $context = [
         'callback_obj' => $this,
         'callback_method' => 'processResultForDepositToGame',
         'playerName' => $playerName,
         'external_transaction_id' => $external_transaction_id,
       ];

       $hash = $this->generateHash($gameUsername,$amount,$external_transaction_id,$this->hash_passkey);
       $password = $this->getPassword($playerName);

       $params = array(
			'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $gameUsername,
			'UserPWD' => $password['password'],
			'UserID'=> $gameUsername,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> self::ACTION_DEPOSIT,
			'TransactionID'=> $external_transaction_id,
			'Hash'=> $hash
		);

      $this->CI->utils->debug_log('VIVO_GAMING depositToGame params >>>>>>>>>>>>>>>>',$params);
      
      return $this->callApi(self::API_depositToGame, $params, $context);
    }

    /** 
     * Process the depositToGame method
     * 
     * @param array $params parameter from depositToGame method
     * 
     * @return array
    */
    function processResultForDepositToGame($params)
    {
      $playerName = $this->getVariableFromContext($params,'playerName');
      $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
      $responseResultId = $this->getResponseResultIdFromParams($params);
      $resultXml = $this->getResultXmlFromParams($params);
      $arrayResult = json_decode(json_encode($resultXml),true);
      $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

      $result = [
         'response_result_id' => $responseResultId,
         'external_transaction_id' => $external_transaction_id,
         'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
         'reason_id'=>self::REASON_UNKNOWN
     ];

     if($success){
      $result['didnot_insert_game_logs'] = true;
      $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
      }else{
            $result['reason_id'] = $this->getReasons($arrayResult['StatusCode']);
            if($result['reason_id'] != self::REASON_UNKNOWN){
               $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
      }

      return [$success,$result];
   }

   /**
    * Get Reason of Failed Transactions/Response
    *
    * 100 - Operator does not exist
    * 101 - Account does not exist
    * 102 - user does not exist
    * 103 - user funds error
    * 104 - account funds error
    * 105 - unknown error
    * 106 - currency error
    * 107 - invalid Server I
    *
    * @param string $apiErrorCode the code from API
    *
    * @return int $reasonCode the reason code from abstract_game_api.php
    */
    public function getReasons($apiErrorCode)
    {
      switch($apiErrorCode){
         case 100:
            $reasonCode = self::REASON_OPERATOR_NOT_EXIST;
            break;
         case 101:
            $reasonCode = self::REASON_ACCOUNT_NOT_EXIST;
            break;
         case 102:
            $reasonCode = self::REASON_NOT_FOUND_PLAYER;
            break;
         case 103:
            $reasonCode = self::REASON_NO_ENOUGH_BALANCE;
            break;
         case 104:
            $reasonCode = self::REASON_ACCOUNT_FUNDS_ERROR;
            break;
         case 105:
            $reasonCode = self::REASON_UNKNOWN;
            break;
         case 106:
            $reasonCode = self::REASON_CURRENCY_ERROR;
            break;
         case 107:
            $reasonCode = self::REASON_INVALID_SERVER;
         default:
            $reasonCode = self::REASON_UNKNOWN;
      }

      return $reasonCode;
    }

   /** 
     * Withdraw money from a player’s game account
     * 
     * TransactionID should be max 9 digits only
     * 
     * @param string $playerName the username field in player table
     * @param int $amount the deposit amount
     * @param  int $transfer_secure_id the unique id for the transaction
    */
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
      $this->strictResponseCheck = true;
       $this->currentAPI = self::URI_MAP[self::API_withdrawFromGame];
       $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       $external_transaction_id = $transfer_secure_id;

       if(strlen((string) $transfer_secure_id) > 9 ||  preg_match("/[a-z]/i", $transfer_secure_id)){
         $external_transaction_id = substr($transfer_secure_id,$this->substr_start);
      }

       $context = [
         'callback_obj' => $this,
         'callback_method' => 'processResultForWithdrawFromGame',
         'playerName' => $playerName,
         'external_transaction_id' => $external_transaction_id
       ];

       $hash = $this->generateHash($gameUsername,$amount,$external_transaction_id,$this->hash_passkey);
       $password = $this->getPassword($playerName);

       $params = [
         'CasinoID' => $this->casino_id,
			'OperatorID' => $this->operator_id,
			'UserName' => $gameUsername,
			'UserPWD' => $password['password'],
			'UserID'=> $gameUsername,
			'AccountNumber'=> $this->account_number,
			'AccountPin'=> $this->account_pin,
			'Amount' => $amount,
			'TransactionType'=> self::ACTION_WITHDRAW,
			'TransactionID'=> $external_transaction_id,
			'Hash'=> $hash
       ];

       $this->CI->utils->debug_log('VIVO_GAMING withdrawFromGame params >>>>>>>>>>>>>>>>',$params);

       return $this->callApi(self::API_withdrawFromGame,$params,$context);
    }

    /** 
     * Process Result of withdrawFromGame method
     * 
     * @param array $params parameter from withdrawFromGame method
    */
    public function processResultForWithdrawFromGame($params)
    {
        $playername = $this->getVariableFromContext($params,'playerName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$playername);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['reason_id'] = $this->getReasons($arrayResult['StatusCode']);
            if($result['reason_id'] != self::REASON_UNKNOWN){
               $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return [$success,$result];
    }

   /** 
     * This method returns a player’s balance from the game system
     * 
     * @param string $playerName the player name in the system
    */
   public function queryPlayerBalance($playerName)
   {
     $this->currentAPI = self::URI_MAP[self::API_queryPlayerBalance];
     $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
     $amount = 0;

     $context = [
      'callback_obj' => $this,
      'callback_method' => 'processResultForQueryPlayerBalance',
      'playerName' => $playerName
     ];

     $uniqueid = $this->generateUniqueId($gameUsername);
     $hash = $this->generateHash($gameUsername,$amount,$uniqueid,$this->hash_passkey);
     $password = $this->getPassword($playerName);

     $params = [
      'CasinoID' => $this->casino_id,
      'OperatorID' => $this->operator_id,
      'UserName' => $gameUsername,
      'UserPWD' => $password['password'],
      'UserID'=> $gameUsername,
      'AccountNumber'=> $this->account_number,
      'AccountPin'=> $this->account_pin,
      'Amount' => $amount,
      'TransactionType'=> self::ACTION_CHECK,
      'TransactionID'=> $uniqueid,
      'Hash'=> $hash
     ];

     $this->CI->utils->debug_log('VIVO_GAMING queryPlayerBalance params >>>>>>>>>>>>>>>>',$params);

     return $this->callApi(self::API_queryPlayerBalance,$params,$context);
   }

   /**
     * Process queryPlayerBalance method
     * 
     * @param array $params the params of queryPlayerBalance method
     * 
     */
    public function processResultForQueryPlayerBalance($params)
    {
      $playerName = $this->getVariableFromContext($params,'playerName');
      $responseResultId = $this->getResponseResultIdFromParams($params);
      $resultXml = $this->getResultXmlFromParams($params);
      $arrayResult = json_decode(json_encode($resultXml),true);
      $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

      # set reason_id if possible
      if(isset($arrayResult['StatusCode'])){
         $result['reason_id'] = $this->getReasons($arrayResult['StatusCode']);
      }

      if($success){
         $result['exists'] = true;
         $result['balance'] = $this->round_down(floatval($arrayResult['Amount']));
      }else{
         $result['exists'] = null;
         $this->CI->utils->debug_log('VIVO_GAMING ERROR in processResultForQueryPlayerBalance result is >>>>>>>>>>>>>>>>',$result);
      }

      return [$success,$result];
    }

    public function isPlayerExist($playerName)
    {
      $this->currentAPI = self::URI_MAP[self::API_isPlayerExist];
      $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
      $amount = 0;
 
      $context = [
       'callback_obj' => $this,
       'callback_method' => 'processResultForIsPlayerExist',
       'playerName' => $playerName
      ];
 
      $uniqueid = $this->generateUniqueId($gameUsername);
      $hash = $this->generateHash($gameUsername,$amount,$uniqueid,$this->hash_passkey);
      $password = $this->getPassword($playerName);
 
      $params = [
       'CasinoID' => $this->casino_id,
       'OperatorID' => $this->operator_id,
       'UserName' => $gameUsername,
       'UserPWD' => $password['password'],
       'UserID'=> $gameUsername,
       'AccountNumber'=> $this->account_number,
       'AccountPin'=> $this->account_pin,
       'Amount' => $amount,
       'TransactionType'=> self::ACTION_CHECK,
       'TransactionID'=> $uniqueid,
       'Hash'=> $hash
      ];
 
      $this->CI->utils->debug_log('VIVO_GAMING isPlayerExist params >>>>>>>>>>>>>>>>',$params);
 
      return $this->callApi(self::API_isPlayerExist,$params,$context);
    }

    
   public function processResultForIsPlayerExist($params)
   {
      $playerName = $this->getVariableFromContext($params,'playerName');
      $responseResultId = $this->getResponseResultIdFromParams($params);
      $resultXml = $this->getResultXmlFromParams($params);
      $arrayResult = json_decode(json_encode($resultXml),true);
      $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

      # set reason_id if possible
      if(isset($arrayResult['StatusCode'])){
         $result['reason_id'] = $this->getReasons($arrayResult['StatusCode']);
      }

      if($success){
         $playerId = $this->getPlayerIdInPlayer($playerName);

         if(! empty($playerId)){
            # update flag to registered = true
            //$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
         }
         $result['exists'] = true;
      }else{

         if(isset($arrayResult['StatusCode'])){
            if($arrayResult["StatusCode"] == 102){
               $success = true;
               $result["exists"] = false;
            }else{
               $this->CI->utils->debug_log('VIVO_GAMING ERROR in processResultForIsPlayerExist with status code of >>>>>>>>>>>>>>>>',$arrayResult["StatusCode"]);
            }
         }else{
            $result['exists'] = null;
         $this->CI->utils->debug_log('VIVO_GAMING ERROR in processResultForIsPlayerExist result is >>>>>>>>>>>>>>>>',$result);
         }
      }

      return [$success,$result];
	}

    /** 
     * Login
    */
    function login($userName, $password = null)
    {
      $this->currentAPI = self::URI_MAP[self::API_login];
      $gameUsername = $this->getGameUsernameByPlayerUsername($userName);

		if($password==null){
			$password = $this->getPassword($userName);
		}
		$playerIp = $this->utils->getIP();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $userName
		);

		$params = array(
			'LoginName' => $gameUsername,
			'PlayerPassword' => $password['password'],
			'OperatorID' => $this->operator_id,
			'PlayerIP' => $playerIp
		);

      $this->CI->utils->debug_log('VIVO_GAMING login params >>>>>>>>>>>>>>>>',$params);

		return $this->callApi(self::API_login, $params, $context);
   }
   
   /** 
    * Process result for login
   */
   function processResultForLogin($params)
   {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultTxt = $this->getValueFromParams($params,'resultText');
      $arrayResult =$this->vivoResultTextToArray($resultTxt);
      $this->strictResponseCheck = false;
      $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

      if($success){
         if (isset($arrayResult['Token'])){
            $result = $arrayResult;
            $success = true;
            $this->CI->utils->debug_log('processResultForLoginPlayer ', $resultTxt);
         }else {
            $this->CI->utils->debug_log('error', 'cannot login player ' . $playerName . ' VIVO');
            $result = array();
         }
      }else{
         $this->CI->utils->debug_log('VIVO_GAMING ERROR', 'cannot login player ' . $playerName . ' VIVO');
			$result = array();
      }
      
		return array($success, $result);
	}

    /** 
     * Game Launch
     * 
     * FUN URL:
     * - https://games.vivogaming.com/?token=ICE2016-2&operatorid=1453&&serverid=3649143&language=EN&Application=lobby
    */
    public function queryForwardGame($playerName, $extra)
    {
      $returnArr = $this->login($playerName);

      if($returnArr['success']){
        
         # idenfity if demo game
         if($extra['game_mode'] == 'demo'){
            return [
               'success' => true,
               'url' => $this->demo_url,
            ];
         }

         $launcher = $this->game_launcher_url;
         #GET language FROM PLAYER DETAILS
         $playerId = $this->getPlayerIdFromUsername($playerName);
         $player_lang = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);
         $isMobile = isset($extra['is_mobile']) ? $extra['is_mobile'] : null;
         $extraLang = isset($extra['language']) ? $extra['language'] : null;
         $language =  $isMobile ? $this->getMobileLauncherLanguage($extraLang) : $this->getLauncherLanguage($extraLang);

         # identify if language is null, if so, get language from player details
         if($language == 'null'){
            $language = $player_lang;
         }

         # home url
         if (isset($extra['home_link'])) {
            $this->homeURL = $extra['home_link'];
         }

         if (isset($extra['extra']['t1_lobby_url'])){
            $this->homeURL = $extra['extra']['t1_lobby_url'];
         }
         
         # all params is not case sensitive, but values are.
         $params = array(
            'token' => $returnArr['Token'],
            'operatorID' => $returnArr['OperatorID'],
            'logoSetup' => $this->logo_setup,
            'isPlaceBetCTA' => $this->IsPlaceBetCTA,
            'language' => $language,
            'selectedGame' => $this->default_selected_game_in_lobby,
            'Logourl'=>$this->logo_url,
            'IsInternalPop' => $this->IsInternalPop,
            'HomeURL' => $this->homeURL,
            'Application' => $this->default_game_type
         );

         #IDENTIFY IF cashier link is present in parameters
         if(isset($extra['cashier_link']) && !empty($extra['cashier_link'])) {
            $params['CashierUrl'] = $extra['cashier_link'];
         }
         
          #IDENTIFY IF LAUNCH WITH GAME TYPE (game_type as application)
         if(isset($extra['game_type']) && in_array(strtolower($extra['game_type']),self::SELECTED_GAME_WITHOUT_LOBBY)){
            $params['Application'] = $extra['game_type'];
         }

         if(! empty($this->serverId)){
            $params['serverid'] = $this->serverId;
         }

          #IDENTIFY IF LAUNCH WITH TABLE ID (game_code as TableID)
         if(!empty($extra['game_code']) && $extra['game_code'] && $extra['game_code'] != 'null'){
            $params['TableID'] = $extra['game_code'];
         }

         # check if game_unique_category is set in extra param, isset is enough
         if(isset($extra['extra']['game_unique_category'])) {

            $unique_category = strtolower($extra['extra']['game_unique_category']);

            #check first if lobby
            if(strtolower($params['Application']) == self::LOBBY_CODE || strtolower($params['Application'])  == self::ALADIN_LOBBY_CODE){
               unset($params['TableID']);

               if(in_array($unique_category,self::SELECTED_GAME)){
                  $params['selectedGame'] = $unique_category;
               }else{
                  $params['selectedGame'] = self::LOBBY_CODE;
               }
            }else{
               if($this->lobby_launch_if_game_unique_category_is_set){
                  unset($params['TableID']);
                  $params['Application'] = self::LOBBY_CODE;

                  if( $params['Application'] == self::ALADIN_LOBBY_CODE){
                     $params['Application'] = self::ALADIN_LOBBY_CODE;
                  }

                  if(in_array($unique_category,self::SELECTED_GAME)){
                     $params['selectedGame'] = $unique_category;
                  }else{
                     $params['selectedGame'] = self::LOBBY_CODE;
                  }
               }else{
                  // we do nothing, normal launch
               }
            }
         }

         $params_string = http_build_query($params);
         
         $link = $launcher . "?" . $params_string; # the link of game

         return [
            'success'=>true,
            'url' => $link,
         ];
      }
      
         return [
            'success'=>false,
            'url'=>''
         ];
    }

    /** 
     * Process the game language in WEB platform
    */
    public function getLauncherLanguage($currentLang) 
    {
      switch ($currentLang) {
            case 'en-us':
            case 'en':
               $language = 'en';
               break;
            case 'es-es':
            case 'es-esp':
               $language = 'es';
               break;
            case 'ko-kp':
            case 'ko-prk':
            case 'ko-kr':
            case 'ko-kor':
               $language = 'ko';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
            case 'zh-chn':
               $language = 'ch';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
            case 'id-idn':
               $language = 'id';
               break;
            case 'ja-jp':
            case 'ja-jpn':
               $language = 'jp';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th-th':
            case 'th-tha':
               $language = 'th';
               break;
            case 'sv-se':
            case 'sv-swe':
               $language = 'se';
               break;
            case 'no-no':
            case 'no-nor':
               $language = 'no';
               break;
            case 'fr-fr':
            case 'fr-fra':
               $language = 'fr';
               break;
            case 'fi-fi':
            case 'fi-fin':
               $language = 'fi';
               break;
            case 'nl-nl':
            case 'nl-nld':
               $language = 'nl';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
            case "vi-vnm":
               $language = 'vt';
               break;
            case 'tr-tr':
            case 'tr-tur':
               $language = 'tr';
               break;
            case 'ar-ae':
            case 'ar-are':
               $language = 'ar';
               break;
            case 'fa-ir':
            case 'fa-irn':
               $language = 'fa';
               break;
            case 'pt-pt':
            case 'pt-prt':
               $language = 'pr';
               break;
            case 'tr-tr':
            case 'tr-tur':
               $language = 'tr';
               break;
            case 'el-gr':
            case 'el-grc':
               $language = 'gr';
               break;
            case 'it-it':
            case 'it-ita':
               $language = 'it';
               break;
            case 'ka-ge':
            case 'ka-geo':
               $language = 'ge';
               break;
            case 'ru-ru':
            case 'ru-rus':
               $language = 'ru';
               break;
            case 'de-de':
            case 'de-deu':
               $language = 'de';
               break;
            default:
               $language = 'en';
               break;
        }
        return $language;
   }

    /** 
     * Process the game language in mobile platform
    */
    public function getMobileLauncherLanguage($currentLang) 
    {
      switch ($currentLang) {
            case 'en-us':
            case 'en':
               $language = 'en';
               break;
            case 'es-es':
            case 'es-esp':
               $language = 'es';
               break;
            case 'ko-kp':
            case 'ko-prk':
            case 'ko-kr':
            case 'ko-kor':
               $language = 'ko';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
            case 'zh-chn':
               $language = 'ch';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
            case 'id-idn':
               $language = 'id';
               break;
            case 'ja-jp':
            case 'ja-jpn':
               $language = 'jp';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th-th':
            case 'th-tha':
               $language = 'th';
               break;
            case 'sv-se':
            case 'sv-swe':
               $language = 'se';
               break;
            case 'no-no':
            case 'no-nor':
               $language = 'no';
               break;
            case 'fr-fr':
            case 'fr-fra':
               $language = 'fr';
               break;
            case 'fi-fi':
            case 'fi-fin':
               $language = 'fi';
               break;
            case 'nl-nl':
            case 'nl-nld':
               $language = 'nl';
               break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
            case "vi-vnm":
               $language = 'vt';
               break;
            case 'tr-tr':
            case 'tr-tur':
               $language = 'tr';
               break;
            case 'ar-ae':
            case 'ar-are':
               $language = 'ar';
               break;
            case 'fa-ir':
            case 'fa-irn':
               $language = 'fa';
               break;
            case 'bg-bg':
            case 'bg-bgr':
               $language = 'bg';
               break;
            case 'mn-mn':
            case 'mn-mng':
               $language = 'mn';
               break;
            default:
               $language = 'en';
               break;
        }
        return $language;
   }

   public function syncOriginalGameLogs($token)
   {
      $this->currentAPI = self::URI_MAP[self::API_syncGameRecords];

      $startDate = clone parent::getValueFromSyncInfo($token,'dateTimeFrom');
      $endDate = clone parent::getValueFromSyncInfo($token,'dateTimeTo');
      $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
      $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
      $startDateTime->modify($this->getDatetimeAdjust());

      $start = clone $startDateTime;
      $end = clone $endDateTime;

      $context = [
         'callback_obj' => $this,
         'callback_method' => 'processResultForSyncOriginalGameLogs'
       ];

       $now=new DateTime();

       if($end > $now){
          $end = $now;
       }
       
       $step = $this->sync_step_in_seconds; # steps in seconds

       $api_result = [];
       $rowsCount = 0;

       while($start < $end){

         $endDate = $this->CI->utils->getNextTimeBySeconds($start,$step);

         if($endDate>$end){
				$endDate=$end;
         }
         
         $params = [
            'OperatorKey' => $this->operator_key,
            'FromDate' => $start->format('Y-m-d H:i:s'),
            'ToDate' => $endDate->format('Y-m-d H:i:s'),
            'ReportType' => 'ACCOUNT_TRANSACTIONS'
          ];

          $this->CI->utils->debug_log('VIVOGAMING params >>>>>>>>>>>>>>>>',$params);

         $api_result = $this->callApi(self::API_syncGameRecords,$params,$context);

         # we check if API call is success
         if(isset($api_result["success"]) && ! $api_result["success"]){

            $this->CI->utils->debug_log('VIVOGAMING ERROR in calling API: ',$api_result);
            
            break;
         }

         # we check if row count of API response is 10,000 meaning we need to split time frame of API call
         # 10,000 records is the max return of API

         if(isset($api_result["is_max_return"]) && $api_result["is_max_return"]){
            $rowsCount = isset($api_result['row_count']) ? $api_result['row_count'] : 0;
            $this->CI->utils->debug_log('VIVOGAMING is max return of API ',$api_result["is_max_return"]);

            $step = $step / 2; # we divide by two the step here, meaning cut to half the end date
         }else{
            $rowsCount = isset($api_result['data_count']) ? $api_result['data_count'] : 0;
            $start = $endDate;
         }

       }
       
       return [
         'success' => true,
         'rows_count' => $rowsCount,
         $api_result
       ];
   }


   /** 
     * Process Result of syncOriginalGameLogs method
    */
    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultTxt = $this->getValueFromParams($params,'resultText');
        $success = $this->processResultBoolean($responseResultId, $resultTxt);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false
        );

        if($success){

            $apiData = $this->vivoLogsResultTextToArray($resultTxt);
            $gameRecords = isset($apiData["data"]) ? $apiData["data"] : null;
            $row_count = isset($apiData["row_count"]) ? $apiData["row_count"] : null;
            
            #check if API response have data
            if(count($apiData) > 0){

               # check first if row count is  10,000
               if(! empty($row_count) && $row_count == 10000){

                  return [
                     true,
                     [
                        "row_count" => $row_count,
                        "is_max_return" => true
                     ]
                  ];
               }else{

                  $this->processGameRecords($gameRecords, $responseResultId);
                  
                  list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                     $this->original_gamelogs_table,
                     $gameRecords,
                     'external_uniqueid',
                     'external_uniqueid',
                     $this->getMd5FieldsForOriginal(),
                     'md5_sum',
                     'id',
                     self::MD5_FLOAT_AMOUNT_FIELDS
                  );
      
                  $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
      
                  $dataResult['data_count'] = count($gameRecords);

                  if (!empty($insertRows)) {
                     $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                  }
                  unset($insertRows);
         
                  if (!empty($updateRows)) {
                     $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                  }
                  unset($updateRows);
               }
            }
        }

        return [$success,$dataResult];
    }

    public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return self::SIMPLE_MD5_FIELDS_FOR_ORIGINAL;
        }

        return self::MD5_FIELDS_FOR_ORIGINAL;
    }

    public function getMD5Fields(){
        return [
            'md5_fields_for_original'=>$this->getMd5FieldsForOriginal(),
            'md5_float_fields_for_original'=>self::MD5_FLOAT_AMOUNT_FIELDS,
            'md5_fields_for_merge'=>self::MD5_FIELDS_FOR_MERGE,
            'md5_float_fields_for_merge'=>self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE,
        ];
    }

    public function processGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords)){
            
            foreach($gameRecords as $index => $record){
                $data['accounting_transaction_id'] = isset($record['accounting_transaction_id']) ? $record['accounting_transaction_id'] : null;
                $data['player_login_name'] = isset($record['player_login_name']) ? $record['player_login_name'] : null;
                $data['transaction_date'] = isset($record['transaction_date']) ? $this->gameTimeToServerTime($record['transaction_date']) : null;
                $data['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $data['transaction_type_id'] = isset($record['transaction_type_id']) ? $record['transaction_type_id'] : null;
                $data['balance_before'] = isset($record['balance_before']) ? ((double)$record['balance_before']) : null;
                $data['debit_amount'] = isset($record['debit_amount']) ? ((double)$record['debit_amount']) : null;
                $data['credit_amount'] = isset($record['credit_amount']) ? $record['credit_amount'] : null;
                $data['balance_after'] = isset($record['balance_after']) ? $record['balance_after'] : null;
                $data['table_round_id'] = isset($record['table_round_id']) ? $record['table_round_id'] : null;
                $data['table_id'] = isset($record['table_id']) ? $record['table_id'] : null;
                $data['card_provider_id'] = isset($record['card_provider_id']) ? $record['card_provider_id'] : null;
                $data['card_number'] = isset($record['card_number']) ? $record['card_number'] : null;
                $data['game_name'] = isset($record['game_name']) ? $record['game_name'] : null;
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                # default data
				    $data['external_uniqueid'] = $data['player_login_name'].'_'.$data['accounting_transaction_id'];
                $data['response_result_id'] = $responseResultId;
                
                $gameRecords[$index] = $data;
				unset($data);
            }
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

   public function syncMergeToGameLogs($token)
   {
      $enabled_game_logs_unsettle = false;
      return $this->commonSyncMergeToGameLogs($token,
       $this,
       [$this,'queryOriginalGameLogs'],
       [$this,'makeParamsForInsertOrUpdateGameLogsRow'],
       [$this, 'preprocessOriginalRowForGameLogs'],
       $enabled_game_logs_unsettle
      );
   }

    /** 
     * Query Original Game Logs for Merging
     * 
     * @param string $dateFrom where the date start for sync original
     * @param string $dataTo where the date end 
     * 
     * @return array 
    */
    public function queryOriginalGameLogs($dateFrom,$dateTo,$use_bet_time)
    {
        // only on time field `transaction_date`
        $sqlTime = 'original.transaction_date >= ? AND original.transaction_date <= ?';

        $sql = <<<EOD
            SELECT
                original.id as sync_index,
                original.player_login_name as username,
                original.accounting_transaction_id as round,
                original.debit_amount,
                original.credit_amount,
                original.response_result_id,
                original.transaction_date,
                original.table_id as game_code,
                original.game_name as game_name,
                original.balance_before as before_balance,
                original.balance_after as after_balance,
                original.table_round_id as round,
                original.external_uniqueid,
                original.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as original
            LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND
            gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.player_login_name = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id = ?
            WHERE
            {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['round']
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $bet_amount = $row['debit_amount'];
        $credit_amount = $row['credit_amount'];
        $result_amount = $credit_amount - $bet_amount;

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null, # //set game_type to null unless we know exactly game type name from original game logs
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' =>  $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['transaction_date'],
                'end_at' => $row['transaction_date'],
                'bet_at' => $row['transaction_date'],
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null # BET_TYPE_MULTI_BET or BET_TYPE_SINGLE_BET
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null     
        ];
    }

     /**
     * Prepare Original rows, include process unknown game, pack bet details, convert game status
     *
     * @param array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        $this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        # we process unknown game here
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        
        $bet_details = [
            'roundId' => $row['round'],
            'gameUsername' => $row['username'],
            'table_identifier' => $row['game_code']
        ];
        
        $row['game_description_id' ]= $game_description_id;
        $row['game_type_id'] = $game_type_id;
        $row['bet_details'] = $bet_details;
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
   }

   /** 
    * Query Transaction
   */
   public function queryTransaction($transactionId, $extra)
   {
      $this->strictResponseCheck = true;
      $this->currentAPI = self::URI_MAP[self::API_queryTransaction];
      $gameUsername = isset($extra['playerName']) ? $this->getGameUsernameByPlayerUsername($extra['playerName']) : null;
      $password = isset($extra['playerName']) ? $this->getPassword($extra['playerName']) : null;
      $amount = isset($extra['amount']) ? $extra['amount'] : null;

      $context = [
         'callback_obj' => $this,
         'callback_method' => 'processResultForQueryTransaction',
         'external_transaction_id' => $transactionId,
         'playerName' => isset($extra['playerName']) ? $extra['playerName'] : null
     ];

     $hash = $this->generateHash($gameUsername,$amount,$transactionId,$this->hash_passkey);

      $params = [
         'CasinoID' => $this->casino_id,
         'OperatorID' => $this->operator_id,
         'UserName' => $gameUsername,
         'UserPWD' => isset($password['password']) ? $password['password'] : null,
         'UserID'=> $gameUsername,
         'AccountNumber'=> $this->account_number,
         'AccountPin'=> $this->account_pin,
         'Amount' => $amount,
         'TransactionType'=> self::ACTION_VALIDATE,
         'TransactionID'=> $transactionId,
         'Hash'=> $hash
      ];

      $this->CI->utils->debug_log('VIVO_GAMING queryTransaction params >>>>>>>>>>>>>>>>',$params);
      
      return $this->callApi(self::API_queryTransaction, $params, $context);
   }

      /** 
     * Process Result for queryTransaction
     * 
     * @param array $params the params of queryTransaction method
     * 
     * @return array
    */
    public function processResultForQueryTransaction($params)
    {
        
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $playerName = $this->getVariableFromContext($params,'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
        ];

        if($success){
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['reason_id'] = $this->getReasons($arrayResult['StatusCode']);
            if($result['reason_id'] != self::REASON_UNKNOWN){
               $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
            $this->CI->utils->debug_log(__METHOD__.' ERROR in processResultForQueryTransaction with external_transaction_id of >>>>>>>>>>>>>',$external_transaction_id,'arrayResult: ',$arrayResult);
        }

        $this->CI->utils->debug_log(__METHOD__.' processResultForQueryTransaction >>>>>>>>>>>>>',$arrayResult);

        return [$success, $result];
    }

   public function syncPlayerAccount($username, $password, $playerId)
   {
      return $this->returnUnimplemented();
   }

   public function logout($playerName, $password = null)
   {
      return $this->returnUnimplemented();
   }

  public function queryPlayerInfo($playerName)
  {
      return $this->returnUnimplemented();
  }

//   public function changePassword($playerName, $oldPassword = null, $newPassword)
//   {
//       return $this->returnUnimplemented();
//   }

   public function blockPlayer($playerName)
   {
      $playerName = $this->getGameUsernameByPlayerUsername($playerName);
      $success = $this->blockUsernameInDB($playerName);
      return array("success" => true);
   }

   public function unblockPlayer($playerName)
   {
      $playerName = $this->getGameUsernameByPlayerUsername($playerName);
      $success = $this->unblockUsernameInDB($playerName);
      return array("success" => true);
   }

 }
 /*end of file*/