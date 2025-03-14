<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Ticket Number: OGP-17658
 * Wallet Type(Transfer/Seamless) : Transfer
 * 
 * @see Transfer Wallet Integration Guide Version 1.01
 * @see Game Info Specification Version 1.03
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Game_api_common_ea_gaming extends Abstract_game_api
 {
     public $demo_site_url;

    /** default original game logs table @var const*/
    const OGL = 'ea_game_logs';

    /** Code for success and description @var const */
    const CODE_SUCCESS = 0;
    const DESC_SUCCESS = 'SUCCESS';

    /** Code for error and description @var const */
    const CODE_ERROR = 001;
    const DESC_ERROR = 'ERR_INVALID_REQ';

    /** callback endpoints in our side @var const */
    const LOGIN_AUTHENTICATE = 'login_authenticate';
    const SINGLE_LOGIN_AUTHENTICATE = 'single_login_authenticate';
    const AUTO_CASHIER_GET_TICKET = 'auto_cashier_get_ticket';
    const LOGIN_CASHIER = 'login_cashier';

    const CALLBACK_ENDPOINTS = [
        self::LOGIN_AUTHENTICATE,
        self::SINGLE_LOGIN_AUTHENTICATE,
        self::AUTO_CASHIER_GET_TICKET,
        self::LOGIN_CASHIER
    ];

    /** attribute name in xml @var const */
    const ATTR_USERID = 'userid';
    const ATTR_PASSWORD = 'password';
    const ATTR_UUID = 'uuid';
    const ATTR_CLIENTIP = 'clientip';
    const ATTR_STATUS = 'status';
    const ATTR_PAYMENT_ID = 'paymentid';
    const ATTR_ERROR_DESC = 'errordesc';
    const ATTR_BALANCE = 'balance';
    const ATTR_USERNAME = 'username';
    const ATTR_SIGN = 'sign';
    const ATTR_DATE = 'date';
    const ATTR_TICKET = 'ticket';

    /** actions of API @var const */
    const ACTION_DEPOSIT = 'cdeposit';
    const ACTION_WITHDRAW = 'cwithdrawal';
    const ACTION_DEPOSIT_CONFIRM = 'cdeposit-confirm';
    const ACTION_QUERY_PLAYER_BALANCE = 'ccheckclient';

    /**
     * URI MAP of Game API Endpoints
     * 
     * @var const URI_MAP
     */
    const URI_MAP = [
        self::API_depositToGame => '/deposit/',
        self::API_withdrawFromGame => '/withdrawal/',
        self::API_queryPlayerBalance => '/checkclient/'
    ];

    /** 
     * Fields in table, we want to detect changes for update in fields
     * @var constant MD5_FIELDS_FOR_ORIGINAL 
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_code',
        'provider_game_type',
        'deal_id',
        'start_at',
        'end_at',
        'status',
        'bet_amount',
        'handle',
        'hold',
        'payout_amount',
        'valid_turnover',
        'login_name',
        'bet_result',
        'bet_details',
        'deal_details',
        'external_unique_id'
    ];

   # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'handle',
        'hold',
        'payout_amount',
        'valid_turnover'
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'deal_id',
        'game_code',
        'bet_amount',
        'hold',
        'start_at',
        'end_at',
        'status',
        'valid_turnover'
     ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'hold',
        'valid_turnover'
    ];

    /**
     * Testing Account in staging
     * 
     * @var array
     */
    protected  $testAccounts = ['scsstgttc01','scsstgttc02','scsstgttc03','scsstgttc04','scsstgttc05'];

    /** @var int $testingCurrencyCode */
    protected $testingCurrencyCode = 1111;

    public function __construct()
    {
        parent::__construct();

        /** Game API settings */
        $this->api_url = $this->getSystemInfo('url','http://test-eas.ea-mission.com');

        /** extra info */
        $this->account_env = $this->getSystemInfo("account_env",'scs188');
        $this->playerPasswordSuffix = $this->getSystemInfo("playerPasswordSuffix",'scs188');
        $this->originalGameLogsTable = $this->getSystemInfo('originalGameLogsTable',self::OGL);
        $this->aCode = $this->getSystemInfo('aCode',null);
        $this->vendorId = $this->getSystemInfo('vendorId',1);
        $this->currencyId = $this->getSystemInfo('currencyId',764);
        $this->gameLaunchUrlWeb = $this->getSystemInfo('game_launch_url_web','https://test-scs188.ea-livegame.com');
        $this->gameLaunchUrlMobile = $this->getSystemInfo('game_launch_url_mobile','https://test-scs188m.ea-livegame.com');
        $this->gameLanguage = $this->getSystemInfo('game_language');
        $this->cashierKey = $this->getSystemInfo('cashierKey','2tg8Y02he4b8xz1qmeh3');
        $this->cashierPage = $this->getSystemInfo('cashierPage');
        $this->ftpPath = $this->getSystemInfo('ftp_game_record_path','/var/game_platform/scs188staging/EA/LIVE_CASINO/');

        /** Game API config */
        $this->testingCurrencyCode = $this->getSystemInfo('testingCurrencyCode',$this->testingCurrencyCode);
        $this->testAccounts = $this->getSystemInfo('testAccounts',$this->testAccounts);
        $this->isRedirect = $this->getSystemInfo('isRedirect',true);

        /** other property */
        $this->setPropertiesInResponse = false;
        $this->clientIp = $this->getSystemInfo('clientIP');

        $this->demo_site_url = $this->getSystemInfo('demo_site_url', 'https://n2-live.com/index_en.html');
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    /**
     * Deposit To Game
     * 
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     * 
     * @return
     */
    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        # check if demo account
        if($this->isGameAccountDemoAccount($gameUsername)['is_demo_flag'] ||
           in_array($gameUsername,$this->testAccounts)
        ){
            $this->currencyId =$this->testingCurrencyCode;
        }

        $refNo = $this->utils->getTimestampNow(). random_string('alnum', 20);
        if(empty($transfer_secure_id)){
            $transfer_secure_id = $refNo;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id' => $transfer_secure_id
        ];

        $params = [
            'request' => [
                'action_attr' => self::ACTION_DEPOSIT,
                'element' => [
                    'id_attr' => "D". $this->utils->getTimestampNow(),
                    'properties' => [
                        [
                            'name_attr' => 'userid',
                            $gameUsername
                        ],
                        [
                            'name_attr' => 'acode',
                            null
                        ],
                        [
                            'name_attr' => 'vendorid',
                            $this->vendorId
                        ],
                        [
                            'name_attr' => 'currencyid',
                            $this->currencyId
                        ],
                        [
                            'name_attr' => 'amount',
                            number_format($amount,2,".","")
                        ],
                        [
                            'name_attr' => 'refno',
                            $transfer_secure_id
                        ]
                    ]
                ]
            ]
        ];

        return $this->callApi(self::API_depositToGame,$params,$context);
    }

    /** 
     * Process Deposit
     * @param $params
     * @return array
     */
    public function processResultForDepositToGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $depositResult = $this->getResultXmlFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$depositResult,$playerName);
        $attrs = $this->getAttrByPathInXml($depositResult,[self::ATTR_STATUS]);
        $status = isset($attrs[self::ATTR_STATUS]) ? (int)$attrs[self::ATTR_STATUS] : 1;

        # just need to call it, as per game provider we should base in cdeposit response to check if deposit is success
        $confirmDeposit = $this->confirmDepositToGame($playerName, $depositResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if($success){
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['reason_id'] = $this->getReasons($status);
            if($result['reason_id'] != self::REASON_UNKNOWN){
               $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' result >>>>>>>>',json_encode($depositResult),'response_result_id',$responseResultId,'confirmDeposit',$confirmDeposit);

        return [
            $success,
            $result
        ];
    }

    /**
     * Confirm The Deposit from game provider
     * 
     * @param mixed $playerName
     * @param xml $depositResult
     * @return mixed
     * 
     */
    public function confirmDepositToGame($playerName,$depositResult)
    {
        $attrs = $this->getAttrByPathInXml($depositResult,[self::ATTR_STATUS,self::ATTR_PAYMENT_ID,self::ATTR_ERROR_DESC]);
        $status = isset($attrs[self::ATTR_STATUS]) ? (int)$attrs[self::ATTR_STATUS] : null;
        $paymentId = isset($attrs[self::ATTR_PAYMENT_ID]) ? (int)$attrs[self::ATTR_PAYMENT_ID] : null;
        $errDesc = isset($attrs[self::ATTR_ERROR_DESC]) ? (int)$attrs[self::ATTR_ERROR_DESC] : null;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmDepositToGame',
            'playerName' => $playerName
        ];

        $params = [
            'request' => [
                'action_attr' => self::ACTION_DEPOSIT_CONFIRM,
                'element' => [
                    'id_attr' => "D". $this->utils->getTimestampNow(),
                    'properties' => [
                        [
                            'name_attr' => 'acode',
                            null
                        ],
                        [
                            'name_attr' => 'status',
                            $status
                        ],
                        [
                            'name_attr' => 'paymentid',
                            $paymentId
                        ],

                        [
                            'name_attr' => 'errdesc',
                            $errDesc
                        ]
                    ]
                ]
            ]
        ];

        return $this->callApi(self::API_depositToGame,$params,$context);
    }

    /** 
     * as per game provider, we should not base the deposit success here, we should base in cdeposit
     * 
     * Process Deposit Confirm
    */
    public function processResultForConfirmDepositToGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);

        return [
            true,
            [
                'response_result_id' => $responseResultId
            ]
        ];
    }

    /**
     * Deposit To Game
     * 
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     * 
     * @return
     */
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        # check if demo account
        if($this->isGameAccountDemoAccount($gameUsername)['is_demo_flag'] ||
           in_array($gameUsername,$this->testAccounts)
        ){
            $this->currencyId =$this->testingCurrencyCode;
        }

        $refNo = $this->utils->getTimestampNow(). random_string('alnum', 20);
        if(empty($transfer_secure_id)){
            $transfer_secure_id = $refNo;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id' => $transfer_secure_id
        ];

        $params = [
            'request' => [
                'action_attr' => self::ACTION_WITHDRAW,
                'element' => [
                    'id_attr' => "W". $this->utils->getTimestampNow(),
                    'properties' => [
                        [
                            'name_attr' => 'userid',
                            $gameUsername
                        ],
                        [
                            'name_attr' => 'vendorid',
                            $this->vendorId
                        ],
                        [
                            'name_attr' => 'currencyid',
                            $this->currencyId
                        ],
                        [
                            'name_attr' => 'amount',
                            number_format($amount,2,".","")
                        ],
                        [
                            'name_attr' => 'refno',
                            $transfer_secure_id
                        ]
                    ]
                ]
            ]
        ];

        return $this->callApi(self::API_withdrawFromGame,$params,$context);
    }

    /** 
     * Process Withdraw
     * @param $params
     * @return array
     */
    public function processResultForWithdrawFromGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $WithdrawResult = $this->getResultXmlFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$WithdrawResult,$playerName);
        $attrs = $this->getAttrByPathInXml($WithdrawResult,[self::ATTR_STATUS]);
        $status = isset($attrs[self::ATTR_STATUS]) ? (int)$attrs[self::ATTR_STATUS] : 1;

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if($success){
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $success = false;
            $result['reason_id'] = $this->getReasons($status);
            if($result['reason_id'] != self::REASON_UNKNOWN){
               $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' result >>>>>>>>',json_encode($WithdrawResult),'response_result_id',$responseResultId);

        return [
            $success,
            $result
        ];
    }

    /** 
     * Query Player Balance thru Game Provider API
     * 
     * @param string $playerName
     * 
     * @return
    */
    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        # check if demo account
        if($this->isGameAccountDemoAccount($gameUsername)['is_demo_flag'] ||
           in_array($gameUsername,$this->testAccounts)
        ){
            $this->currencyId =$this->testingCurrencyCode;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        ];

        $params = [
            'request' => [
                'action_attr' => self::ACTION_QUERY_PLAYER_BALANCE,
                'element' => [
                    'id_attr' => "C". $this->utils->getTimestampNow(),
                    'properties' => [
                        [
                            'name_attr' => 'userid',
                            $gameUsername
                        ],
                        [
                            'name_attr' => 'vendorid',
                            $this->vendorId
                        ],
                        [
                            'name_attr' => 'currencyid',
                            $this->currencyId
                        ],
                    ]
                ]
            ]
        ];

        return $this->callApi(self::API_queryPlayerBalance,$params,$context);
    }

    /**
     * Process Query Player Balance
     */
    public function processResultForQueryPlayerBalance($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultXmlFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$playerName,self::API_queryPlayerBalance);
        $attrs = $this->getAttrByPathInXml($resultArr,[self::ATTR_BALANCE,self::ATTR_STATUS]);
        $balance = isset($attrs[self::ATTR_BALANCE]) ? $attrs[self::ATTR_BALANCE] : false;
        $status = isset($attrs[self::ATTR_STATUS]) ? (int)$attrs[self::ATTR_STATUS] : 1;

        if($success){
            $result['exists'] = true;
            $result['balance'] = $this->round_down(floatval($balance));
        }else{
            /** status code only available only in error for now */
            $result['reason_id'] = $this->getReasons($status);
            $result['exists'] = null;
            $this->CI->utils->debug_log(__METHOD__.' ERROR Getting player balance, result is >>>>>>>>',json_encode($result));
        }

        return [
            $success,
            $result
        ];
    }

    /** 
     * Query Transaction of Player thru Game Provider API
     * 
     * @param string $playerName
     * @param array $extra
     * 
     * @return
    */
    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    /**
     * Game Launching
     * 
     * @param string $playerName
     * @param array $extra
     * 
     * @return
     */
    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $activeToken = $this->getPlayerTokenByUsername($playerName);
        $lang = isset($extra['language']) ? $extra['language'] : null;
        $gameUrl = $this->gameLaunchUrlWeb;
        $success = false;

        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo' || $extra['game_mode'] == 'fun'))
        {
            return [
                'redirect' => $this->isRedirect,
                'url' => $this->demo_site_url,
                'success' => true
            ];
        }

        if(isset($extra['is_mobile']) && $extra['is_mobile']){
            $gameUrl = $this->gameLaunchUrlMobile;
        }

        if($this->isTokenValid($activeToken)){
            $success = true;

            $params = [
                'userid' => $gameUsername,
                'uuid' => $activeToken,
                'lang' => $this->getLauncherLanguage($lang)
            ];
    
            if(!empty($this->gameLanguage)){
                $params['lang'] = $this->gameLanguage;
            }
    
            $urlParams = http_build_query($params);

            $url = rtrim($gameUrl,'/').'/?'.$urlParams;

            $this->CI->utils->debug_log(__METHOD__.' Game URL and params >>>>>>>>',$params);

            return [
                'redirect' => $this->isRedirect,
                'url' => $url,
                'success' => $success
            ];

        }else{

            $this->CI->utils->debug_log(__METHOD__.' Error in token >>>>>>>>',$activeToken);

            return [
                'url' => null,
                'success' => $success
            ];
        }
    }

    /** 
     * Get Proper Game Language
     * @param mixed $currentLang
     * @return int
    */
    public function getLauncherLanguage($currentLang) 
    {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 2;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 12;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 11;
                break;
            case "en-us":
                $language = 3;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-th":
                $language = 8;
                break;
            default:
                $language = 3;
                break;
        }
        return $language;
    }

    /**
     * Abstract in Parent Class
     * Sync Original Game Logs
     * 
     * @param string $token token from sync Information, found in \Game_platform_manager::class@syncGameRecordsNoMergeOnOnePlatform
     * 
     * @return
     */
    public function syncOriginalGameLogs($token)
    {
        $ftpPath =  rtrim($this->ftpPath,'/').'/';

        $playerName = $this->getValueFromSyncInfo($token, 'playerName');
        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');

        $dateTimeFrom=new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo=new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $day_diff = $dateTimeTo->diff($dateTimeFrom)->format("%a");
        $rows_count = 0;


        if($day_diff > 0){

            for($i = 0;$i < $day_diff;$i++){

                if($i == 0){
                    $directory = $ftpPath . $dateTimeFrom->format('Ymd');
                    $res = $this->retrieveXMLFromLocal($directory,$dateTimeFrom,$dateTimeTo,$playerName,$syncId);

                    $rows_count += (isset($res['data_count']) ? $res['data_count'] : 0);
                }else{
                    $dateTimeFrom->modify('+1 day');
                    $directory = $ftpPath . $dateTimeFrom->format('Ymd');
                    $res = $this->retrieveXMLFromLocal($directory,$dateTimeFrom,$dateTimeTo,$playerName,$syncId);

                    $rows_count += (isset($res['data_count']) ? $res['data_count'] : 0);
                }

                $this->CI->utils->debug_log(__METHOD__.' more than 1 day date diff details: ',$day_diff,'i',$i,'directory',$directory,'dateFrom',$dateTimeFrom);
            }

        }else{
            $directory = $ftpPath . $dateTimeFrom->format('Ymd');
            $res = $this->retrieveXMLFromLocal($directory,$dateTimeFrom,$dateTimeTo,$playerName,$syncId);

            $rows_count += (isset($res['data_count']) ? $res['data_count'] : 0);

            $dateTimeFrom->modify('+1 day');
            $directory = $ftpPath . $dateTimeFrom->format('Ymd');
            $res = $this->retrieveXMLFromLocal($directory,$dateTimeFrom,$dateTimeTo,$playerName,$syncId);

            $rows_count += (isset($res['data_count']) ? $res['data_count'] : 0);

            $this->CI->utils->debug_log(__METHOD__.' less than 1 day date diff details: ',$day_diff,'directory',$directory);
        }


        return [
            'success' => true,
            'rows_count' => $rows_count,
        ];
    }

    /**
     * Retrieve XML files into FTP directory
     * 
     * @param string $directory
     * @param datetime $dateTimeFrom
     * @param datetime $dateTimeTo
     * @param string $playerName
     * @param int $syncId
     * 
     * @return array
     */
    public function retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId)
    {
        $this->CI->utils->info_log(__METHOD__. ' CURRENT XML DIRECTORY ',$directory);
        $results = [
            'data_count' => 0
        ];

        if(is_dir($directory)){
            $ftpPathArray = array_diff(scandir($directory), array('..', '.','gameinfolist.txt'));
            $dateNow = new DateTime();

            if($dateTimeTo > $dateNow){
               $dateTimeTo = $dateNow;
            }

            foreach($ftpPathArray as $xml){
                $filepath = $directory.'/'.$xml;
                if(file_exists($filepath)){
                    $responseResultId = $this->saveResponseResultForFile(true,'syncGameRecords', $this->getPlatformCode(), $filepath, array('sync_id' => $syncId));

                    $this->CI->utils->debug_log(__METHOD__.' FILE PATH CURRENT SYNCING',$filepath);
                    $result = $this->extractXMLRecord($filepath, $responseResultId);
                    if(isset($result['data_count']) && $result['data_count'] != 0){
                        $results['data_count'] += $result['data_count'];
                    }

                }else{
                    $this->CI->utils->error_log(__METHOD__.' ERROR FILE NOT FOUND',$filepath);
                }
            }
        }

        return $results;
    }

    /**
     * @param string $filepath
     * @param int $responseResultId
     * @return array
     */
    public function extractXMLRecord($filePath,$responseResultId=null)
    {
        $xmlData = simplexml_load_string(file_get_contents($filePath,true));
        $results = [
            'data_count' => 0
        ];

        if(is_object($xmlData->game)){
            foreach($xmlData->game as $game){
                $game_code = (string)$game->attributes()->{'code'};
                $gameRecords['game_code'] = $game_code;
                if(is_object($game->deal)){
                    foreach($game->deal as $deal){
                        $gameRecords['provider_game_type'] = (string)$deal->attributes()->{'code'};
                        $gameRecords['deal_id'] = (string)$deal->attributes()->{'id'};
                        $gameRecords['start_at'] = (string)$deal->attributes()->{'startdate'};
                        $gameRecords['end_at'] = (string)$deal->attributes()->{'enddate'};
                        $gameRecords['status'] = (string)$deal->attributes()->{'status'};

                        if(is_object($deal->betinfo->clientbet)){
                            foreach($deal->betinfo->clientbet as $clientbet){
                                 $gameRecords['bet_amount'] = (double)$clientbet->attributes()->{'bet_amount'};
                                $gameRecords['handle'] = (double)$clientbet->attributes()->{'handle'};
                                $gameRecords['hold'] = (double)$clientbet->attributes()->{'hold'};
                                $gameRecords['payout_amount'] = (double)$clientbet->attributes()->{'payout_amount'};
                                $gameRecords['login_name'] = (string)$clientbet->attributes()->{'login'};
                                $gameRecords['valid_turnover'] = (double)$clientbet->attributes()->{'valid_turnover'};
                                $gameRecords['bet_details'] = (array)$clientbet->betdetail;
                            }
                        }
                        if(is_object($deal->dealdetails)){
                            $gameRecords['deal_details'] = (array)$deal->dealdetails;
                        }
                        if(is_object($deal->results)){
                            $gameRecords['bet_result'] = (string)$deal->results->result;
                        }

                        $result = $this->processResultForSyncGameRecords($gameRecords,$responseResultId);
                        if(isset($result['data_count']) && $result['data_count'] != 0){
                            $results['data_count'] += $result['data_count'];
                        }

                    }
                }
            }
        }
        return $results;
    }

    /**
     * Process Date
     * 
     * @param array $data
     * @param int $responseResultId
     * @return array
     */
    public function processResultForSyncGameRecords($data,$responseResultId=null)
    {
        $this->CI->load->model(array('original_game_logs_model'));
        $gameRecords = !empty($data) ? $data : [];

        $dataResult = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        ];

        if(!empty($gameRecords)){
            $dataResult = $this->insertOgl($gameRecords,$responseResultId);
        }
        return $dataResult;
    }

    /**
     * Insert Original Game logs daata
     * 
     * @param array $gameRecord
     * @param int $responseResultId
     * 
     * @return mixed
     * 
     */
    public function insertOgl($gameRecord,$responseResultId)
    {

        $gameRecords = $this->processGameRecords($gameRecord,$responseResultId);

        $dataResult = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        ];

        if(! empty($gameRecords)){
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalGameLogsTable,
                $gameRecords,
                'external_unique_id',
                'external_unique_id',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
    
            $dataResult['data_count'] += is_array($gameRecords) ? count($gameRecords) : 0;
    
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);
    
            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);

        }

        return $dataResult;
    }

    public function processGameRecords($gameRecords, $responseResultId)
    {
        $dataRecords = [];

        if(! empty($gameRecords)){
            $dataRecords = $this->processDataRecords($dataRecords,$gameRecords,$responseResultId);
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalGameLogsTable, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalGameLogsTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }
    public function processDataRecords(&$dataRecords,$gameRecords,$responseResultId)
    {
        $elapsed=intval($this->CI->utils->getExecutionTimeToNow()*1000);
        $data['game_code'] = isset($gameRecords['game_code']) ? $gameRecords['game_code'] : null;
        $data['provider_game_type'] = isset($gameRecords['provider_game_type']) ? $gameRecords['provider_game_type'] : null;
        $data['deal_id'] = isset($gameRecords['deal_id']) ? $gameRecords['deal_id'] : null;
        $data['start_at'] = isset($gameRecords['start_at']) ?  $this->gameTimeToServerTime($gameRecords['start_at']) : null;
        $data['end_at'] = isset($gameRecords['end_at']) ?  $this->gameTimeToServerTime($gameRecords['end_at']) : null;
        $data['status'] = isset($gameRecords['status']) ? $gameRecords['status'] : 2; # 2 = repair
        $data['bet_amount'] = isset($gameRecords['bet_amount']) ? $this->gameAmountToDB($gameRecords['bet_amount']) : null;
        $data['login_name'] = isset($gameRecords['login_name']) ? $gameRecords['login_name'] : null;
        $data['external_unique_id'] = $data['deal_id'] .'-'. $data['provider_game_type'].'-'. $data['login_name'];
        $data['handle'] = isset($gameRecords['handle']) ? $this->gameAmountToDB($gameRecords['handle']) : null;
        $data['hold'] = isset($gameRecords['hold']) ? $this->gameAmountToDB($gameRecords['hold']) : null;
        $data['payout_amount'] = isset($gameRecords['payout_amount']) ? $this->gameAmountToDB($gameRecords['payout_amount']) : null;
        $data['valid_turnover'] = isset($gameRecords['valid_turnover']) ? $this->gameAmountToDB($gameRecords['valid_turnover']) : null;
        $data['bet_details'] = json_encode($gameRecords['bet_details']);
        $data['deal_details'] = json_encode($gameRecords['deal_details']);
        $data['bet_result'] = isset($gameRecords['bet_result']) ? $gameRecords['bet_result'] : null;
        $data['response_result_id'] = $responseResultId;
        $data['elapsed_time'] = $elapsed;
        $dataRecords[$data['external_unique_id']] = $data;

        return $dataRecords;
    }

    /** 
     * Abstract in Parent Class
     * Merge Game Logs from Sync Original
     * 
     * @param string $token token from sync Information, found in \Game_platform_manager::class@mergeGameLogs
     * 
     * @return
    */
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $table = $this->originalGameLogsTable;
        $sqlTime='ea.end_at >= ? and ea.end_at <= ?';
        if($use_bet_time){
            $sqlTime='ea.start_at >= ? and ea.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
    ea.id as sync_index,
    ea.deal_id,
    ea.game_code,
    ea.bet_amount as real_betting_amount,
    ea.payout_amount,
    ea.valid_turnover as bet_amount,
    ea.hold as result_amount,
    ea.start_at,
    ea.end_at,
    ea.status,
    ea.bet_result,

    ea.external_unique_id as external_uniqueid,
    ea.updated_at,
    ea.md5_sum,
    ea.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as ea
    LEFT JOIN game_description ON ea.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON ea.login_name = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['deal_id']
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null # no after balance in API response
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['deal_id'],
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
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        switch($row['status']) {
            case 1:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 2:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }
        $betDetails = [
            'round_id' => $row['deal_id'],
            'bet_status' => ($row['status'] == 1 ? 'Normal' : 'Repair'),
            'bet_result' => $row['bet_result']
        
        ];
        $row['bet_details'] = $betDetails;
    }


    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    /**
     * Generate API URL
     * 
     * @param string $apiName
     * @param array $params
     * 
     * @return
     */
    public function generateUrl($apiName,$params)
    {
        $apiUri = self::URI_MAP[$apiName];

        $url = rtrim($this->api_url,'/') . $apiUri . $this->account_env;

        $this->CI->utils->debug_log(__METHOD__.' >>>>>>>>',$url);

        return $url;
    }

    /** 
     * @param $array $params
     * @return array
     */
    public function getHttpHeaders($params)
    {
        return [
            'Content-Type' => 'application/xml'
        ];
    }

    /** 
     * Custom HTTP call
     * @param string $ch
     * @param array $params
     * @return void
    */
    protected function customHttpCall($ch, $params)
    {
        $xml_data = $this->CI->utils->arrayToXmlStandAlone($params,false,false);

        $this->CI->utils->debug_log(__METHOD__.' xml >>>>>>>>',$xml_data);

        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    }
    /** 
     * Create Player to Game Provider or in our Database
     * 
     * 
     * @param string $playerName
     * @param int $playerId
     * @param string $password
     * @param string $email
     * @param array $extra
     * 
     * @return mixed
    */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        // check if test player, if so, is_demo_flag in game_provider_auth must be 1
        if(in_array($gameUsername,$this->testAccounts)){
            $extra['is_demo_flag'] = true;
        }

        if(!empty($gameUsername)){
            $is_demo = ( in_array($gameUsername,$this->testAccounts) ) ? true : false;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE, $is_demo);
            return array("success" => true);
        }
       // it will create player on game_provider_auth table
       $return = parent::createPlayer($playerName,$playerId,$password,$email,$extra);
       $success = false;
       $message = "Unable to create account for EA GAMING";

       if($return){
          $success = true;
          $message = "Unable to create account for EA GAMING";
       }

       $this->CI->utils->debug_log(__METHOD__." is:",$success);

       return [
         "success" => $success,
         "message" => $message
       ];
    }

    /** 
     * Change the password of player in our SBE
     * 
     * Important Note: since game provider do not see the password of player, we cannot apply this method to change the passwword of player in our DB, because if we do that we cannot recover password of player in game provider
     * 
     * @param string $playerName
     * @param string $oldPassword
     * @param string $newPassword
     * 
     * @return array
    */
    public function changePassword($playerName, $oldPassword, $newPassword)
    {
        return $this->returnUnimplemented();
    }

    /**
     * Callback for Login Player
     * Overview: this will check if the player is exist in our side, game provider will
     * 
     */
    public function callback($request,$method=null)
    {
        if(in_array($method,self::CALLBACK_ENDPOINTS)){

            /** set demo currency for demo account */
            $this->processDemoAccount($request);

            $responseData = $this->$method($request,$extra=[]);

        }else{
            $this->setErrorInResponse($responseData,self::CODE_ERROR,self::DESC_ERROR);
        }

        return $responseData;
    }

    /**
     * 
     * Login function is used when player login to EA game by manually type in User ID
     * and Password in EA’s login page. EA system initiates login request. If merchant
     * response with success and player account does not exist in EA system, player
     * account will be created in EA database.
     * Login Authenticate
     * 
     * @param xml $request
     * @param array $extra
     * 
     * @return array
     */
    protected function login_authenticate($request,$extra=[])
    {
        $action = isset($request->attributes()->{'action'}) ? $request->attributes()->{'action'} : null;
        $responseData = [
            'request' => [
                'action_attr' => $action,
                'element' => [
                    'id_attr' => null,
                    'properties' => [
                    ]
                ]
            ]
        ];

        $attrs = $this->getAttrByPathInXml($request,[self::ATTR_USERID,self::ATTR_PASSWORD]);

        $result = $this->executePlayerCheckpoint($request,$responseData,$attrs);

        if($result != self::CODE_SUCCESS){
            return $result;
        }

        /** set propertis to response, if success */
        $properties = [
            [
                'name_attr' => 'errdesc',
                self::DESC_SUCCESS
            ],
            [
                'name_attr' => 'status',
                self::CODE_SUCCESS
            ],
            [
                'name_attr' => 'currencyid',
                $this->currencyId
            ],
            [
                'name_attr' => 'vendorid',
                $this->vendorId
            ],
            [
                'name_attr' => 'acode',
                $this->aCode
            ],
            [
                'name_attr' => 'username',
                $attrs[self::ATTR_USERID]
            ],
            [
                'name_attr' => 'userid',
                $attrs[self::ATTR_USERID]
            ],
        ];

        $this->addProperties($responseData,$properties);

        return $responseData;
    }

    /**
     * 
     * When player login to Merchant’s website and request to enter EA game,
     * Merchant’s server will redirect the player to EA’s URL appended with player’s
     * information parameter. EA will validate the parameter carried in the URL, and then
     * generate a Web Single Login Validation Request Message and send to
     * Merchant’s Web Single Login Authentication URL through HTTPS POST method.
     * Merchant processed the request message, and return EA with a Web Single Login
     * Validation Response Message.
     * 
     * @param xml $request
     * @param array $extra
     * 
     * @return array
     */
    protected function single_login_authenticate($request,$extra=[])
    {
        $attrs = $this->getAttrByPathInXml($request,[self::ATTR_USERID,self::ATTR_UUID,self::ATTR_CLIENTIP]);
        $uuid = isset($attrs[self::ATTR_UUID]) ? (string)$attrs[self::ATTR_UUID] : null;
        $clientIP = isset($attrs[self::ATTR_CLIENTIP]) ? (string)$attrs[self::ATTR_CLIENTIP] : null;

        if(! empty($this->clientIp)){
            $clientIP = $this->clientIp;
        }

        $action = isset($request->attributes()->{'action'}) ? $request->attributes()->{'action'} : null;
        $responseData = [
            'response' => [
                'action_attr' => $action,
                'element' => [
                    'id_attr' => null,
                    'properties' => [
                    ]
                ]
            ]
        ];

        $result = $this->executePlayerCheckpoint($request,$responseData,$attrs);

        if($result != self::CODE_SUCCESS){
            return $result;
        }

        /** set propertis to response, if success */
        $properties = [
            [
                'name_attr' => 'status',
                self::CODE_SUCCESS
            ],
            [
                'name_attr' => 'errdesc',
                self::DESC_SUCCESS
            ],
            [
                'name_attr' => 'acode',
                $this->aCode
            ],
            [
                'name_attr' => 'currencyid',
                $this->currencyId
            ],
            [
                'name_attr' => 'clientip',
                $clientIP
            ],
            [
                'name_attr' => 'vendorid',
                $this->vendorId
            ],
            [
                'name_attr' => 'uuid',
                $uuid
            ],
            [
                'name_attr' => 'username',
                $attrs[self::ATTR_USERID]
            ],
            [
                'name_attr' => 'userid',
                $attrs[self::ATTR_USERID]
            ],
        ];

        $this->addProperties($responseData,$properties,'response');

        return $responseData;

    }

    /** 
     * 
     * Auto cashier function enables player to access merchant cashier page directly
     * and conveniently from EA game with seamless login at background to instantly
     * deposit credit from merchant’s wallet to EA’s wallet when player is having low
     * credit.
     *
     * @param xml $request
     * @param array $extra
     * 
     * @return array
    */
    protected function auto_cashier_get_ticket($request,$extra=[])
    {
        $attrs = $this->getAttrByPathInXml($request,[self::ATTR_USERNAME,self::ATTR_SIGN,self::ATTR_DATE]);
        $username = isset($attrs[self::ATTR_USERNAME]) ? (string)$attrs[self::ATTR_USERNAME] : null;
        $playerUsername = $this->getPlayerUsernameByGameUsername($username);
        $token = $this->getPlayerTokenByUsername($playerUsername);


        $result = $this->executePlayerCheckpoint($request,$responseData,$attrs);

        if($result != self::CODE_SUCCESS){
            return $result;
        }
        $action = isset($request->attributes()->{'action'}) ? $request->attributes()->{'action'} : null;
        $id = isset($request->element->attributes()->{'id'}) ? $request->element->attributes()->{'id'} : null;
        $responseData = [
            'response' => [
                'action_attr' => $action,
                'element' => [
                    'id_attr' => $id,
                    'properties' => [
                    ]
                ]
            ]
        ];

        /** set propertis to response, if success */
        $properties = [
            [
                'name_attr' => 'status',
                self::CODE_SUCCESS
            ],
            [
                'name_attr' => 'username',
                $username
            ],
            [
                'name_attr' => 'ticket',
                $token
            ]
        ];

        $this->addProperties($responseData,$properties,'response');

        return $responseData;
    }

    /** 
     * EA will redirect player to Merchant’s Cashier URL
     *
     * @param xml $request
     * @param array $extra
     * 
     * @return array
    */
    protected function login_cashier($request,$extra=[])
    {
        $cashierPage = $this->cashierPage ? $this->cashierPage : $this->CI->utils->getSystemUrl('player');
        $playerUsername = $this->getPlayerUsernameByGameUsername($request['username']);
        $forwardResult = $this->forwardToWhiteDomain($playerUsername, $cashierPage);
        $success = false;
        $forward_url = $cashierPage;
        

        if($this->getPlayerIdByToken($request['ticket'])){
            if(isset($forwardResult['success']) && $forwardResult['success']){
                $success = true;
                $forward_url = $forwardResult['forward_url']."&game_platform_id=".$this->getPlatformCode();
            }
        }

        return [
            'success' => $success,
            'forward_url' => $forward_url
        ];
    }


    /** -----------------------UTILS----------------------- */

    /** 
     * Round down number, meaning 0.019 will be 0.01 instead round up 0.019 to 0.02
    */
    private function round_down($number,$precision = 3)
    {

        $fig = (int) str_pad('1', $precision, '0');

	    return (floor($number * $fig) / $fig);
    }

    /**
     * Process Request if Success or not
     * 
     */
    public function processResultBoolean($responseResultId,$result,$playerName=null,$method=null)
    {
        $success = false;
        $attrs = $this->getAttrByPathInXml($result,[self::ATTR_STATUS,self::ATTR_BALANCE]);
        $status = isset($attrs[self::ATTR_STATUS]) ? (int)$attrs[self::ATTR_STATUS] : 1;
        $balance = isset($attrs[self::ATTR_BALANCE]) ? floatval($attrs[self::ATTR_BALANCE]) : false;
        $error = '';

        if($method === self::API_queryPlayerBalance){
            if($balance){
                $success = true;
            }else{
                $error = 'ERROR';
                $this->setResponseResultToError($responseResultId);
            }
        }else{
            if($status === self::CODE_SUCCESS){
                $success = true;
            }else{
                $error = 'ERROR';
                $this->setResponseResultToError($responseResultId);
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' '.$error.' response result ID here: >>>>>>>>',$responseResultId,'playerName',$playerName,'result',json_encode($result),'success',$success);

        return $success;
    }

    /** 
     * Check if token is valid
     * 
     * @param string $token the token to validate
     * 
     * @return boolean
    */
    public function isTokenValid($token)
    {
        $playerInfo = parent::getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /** 
     * Execute Player Checkpoint:
     * 1.) check if player exist in game_provider_auth
     * 2.) check if player blocked in game_provider_auth
     * 3.) check if player password is not matched compare to game provider request
     * 
     * @param object $request
     * @param array $responseData
     * @param array $attrs
     * 
     * @return mixed;
    */
    public function executePlayerCheckpoint($request,&$responseData,$attrs)
    {
        $name = isset($attrs[self::ATTR_USERID]) ? (string)$attrs[self::ATTR_USERID] : (string)$attrs[self::ATTR_USERNAME];
        $playerName = $this->getPlayerUsernameByGameUsername($name);
        $isBlocked = $this->isBlocked($playerName);
        $playerPassword = $this->getPasswordFromPlayer($playerName);
        $passwordInRequest = isset($attrs[self::ATTR_PASSWORD]) ? $attrs[self::ATTR_PASSWORD]: null;
        $uuidInRequest = isset($attrs[self::ATTR_UUID]) ? (string)$attrs[self::ATTR_UUID]: null;
        $username = isset($attrs[self::ATTR_USERNAME]) ? (string)$attrs[self::ATTR_USERNAME] : null;
        $sign = isset($attrs[self::ATTR_SIGN]) ? (string)$attrs[self::ATTR_SIGN] : null;
        $date = isset($attrs[self::ATTR_DATE]) ? (string)$attrs[self::ATTR_DATE] : null;

        # for cashier login
        if(! is_null($sign)){
            $ourSign = md5(strtolower($username).$date.$this->cashierKey);
            if($sign != $ourSign){
                $this->setErrorInResponse($responseData,614,'ERR_SIGN_FAILURE');
                return $responseData;
            }
        }

        if(is_null($playerName)){
            $this->setErrorInResponse($responseData,101,'ERROR_INVALID_ACCOUNT_ID');
            return $responseData;
        }

        if($isBlocked){
            $this->setErrorInResponse($responseData,104,'ERROR_ACCOUNT_SUSPENDED');
            return $responseData;
        }

        /** validate password only if password exist in request params */
        if(! is_null($passwordInRequest)){
            if($playerPassword != $passwordInRequest){
                $this->setErrorInResponse($responseData,613,'ERR_LOGIN_DENIED');
                return $responseData;
            }
        }

        /** validate uuid(our common token) only if uuid exist in request params */
        if(! is_null($uuidInRequest)){
            if(! $this->isTokenValid((string)$uuidInRequest)){
                $this->setErrorInResponse($responseData,614,'ERR_SIGN_FAILURE');
                return $responseData;
            }
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Process Demo Account
     * @param object $request
     */
    public function processDemoAccount($request)
    {
        $attrs = $this->getAttrByPathInXml($request,[self::ATTR_USERID,self::ATTR_USERNAME]);
        $gameUsername = isset($attrs[self::ATTR_USERID]) ? (string)$attrs[self::ATTR_USERID] : null;
        $name = null;
        if(isset($attrs[self::ATTR_USERID])){
            $name = (string)$attrs[self::ATTR_USERID];
        }
        if(isset($attrs[self::ATTR_USERNAME])){
            $name = (string)$attrs[self::ATTR_USERNAME];
        }
        $playerName = $this->getPlayerUsernameByGameUsername($name);


        if(! is_null($playerName)){
            if($this->isGameAccountDemoAccount($gameUsername)['is_demo_flag'] ||
                in_array($gameUsername,$this->testAccounts)
            ){
                $this->currencyId = $this->testingCurrencyCode;
            }
        }
    }

    /** 
     * Set property setPropertiesInResponse to true
     * @param boolean|true $boolean
     * @return void
    */
    public function setPropertiesInResponse($boolean=true)
    {
        $this->setPropertiesInResponse = $boolean;
    }

    /** 
     * Set Error in Array Response
     * @param array $responseData
     * @param int $code
     * @param string $desc
     * @return void
    */
    public function setErrorInResponse(&$responseData,$code,$desc)
    {
        $properties = $responseData['request']['element']['properties'] ?: null;

        if(! is_null($properties)){

            foreach($properties as $key => $val){
                if($val['name_attr'] == 'status'){
                    $responseData['request']['element']['properties'][$key][0] = $code;
                }
                if($val['name_attr'] == 'errdesc'){
                    $responseData['request']['element']['properties'][$key][0] = $desc;
                }
            }

        }
    }

    /** 
     * Override the userid and password attribute
     * 
     * @param object $request
     * @param array $attr
     * 
     * @return array
    */
    public function getAttrByPathInXml($request,$attr=[])
    {
        $element = isset($request->element)? $request->element : null;
        $attrs = [];

        if(! is_null($element)){
            if(count($attr)>0){
                foreach($attr as $val){
                    $path = "properties[@name="."'".$val."'"."]";
                    $attrVal = isset($request->element->xpath($path)[0]) ? $request->element->xpath($path)[0] : null;
                    if(! is_null($attrVal)){
                        $attrs[$val] = $request->element->xpath($path)[0];
                    }
                }
            }
        }

        return $attrs;
    }

    /** 
     * Add properties into response Data
     * @param array $responseData
     * @param array $properties
     * @param string $key
     * @return void
    */
    public function addProperties(&$responseData,$properties=[],$key='request')
    {
        if(count($properties) > 0){
            foreach($properties as  $val){
                array_unshift($responseData[$key]['element']['properties'],$val);
            }
        }
    }

    /**
    * Get Reason of Failed Transactions/Response
    *
    * 0   - SUCCESS
    * 001 - ERR_INVALID_REQ
    * 002 - ERR_INVALID_IP
    * 003 - ERR_SYSTEM_OPR
    * 004 - ERR_OVER_ACODES
    * 005 - ERR_OVER_DATES
    * 101 - ERROR_INVALID_ACCOUNT_ID
    * 102 - ERROR_ALREADY_LOGIN
    * 103 - ERROR_DATABASE_ERROR
    * 104 - ERROR_ACCOUNT_SUSPENDED
    * 105 - ERROR_INVALID_CURRENCY
    * 201 - ERR_INVALID_REQ
    * 202 - ERR_DB_OPEATION
    * 203 - ERR_INVALID_CLIENT
    * 204 - ERR_EXCEED_AMOUNT
    * 205 - ERR_INVALID_VENDOR
    * 206 - ERR_LOCKED_CLIENT
    * 211 - ERR_CLIENT_IN_GAME
    * 212 - ERR_MAX_xxx_WITHDRAWAL_DAILY
    * 213 - ERR_PLAYTHROUGHFACTOR_NOTREACH
    * 301 - ERR_INVALID_VENDOR
    * 306 - ERR_INVALID_CURRENCYID
    * 401 - ERR_DUPLICATE_REFNO
    * 402 - ERR_INVALID_PREFIX|ERR_INVALID_IP
    * 403 - ERR_INVALID_AMOUNT
    * 404 - ERR_ILLEGAL_DECIMAL
    * 501 - ERR_INVALID_ACODE
    * 502 - ERR_INVALID_BEGINDATE
    * 503 - ERR_INVALID_ENDDATE
    * 504 - ERR_INVALID_ENDDATELOWBEGINDATE
    * 505 - ERR_INVALID_RETURN_ACODE
    * 506 - ERR_DATA_ SNAPSHOT
    * 601 - ERR_INVALID_LOGIN_URL
    * 602 - ERR_INVALID_AUTO_LOGIN_URL
    * 603 - ERR_INVALID_ACTION
    * 604 - ERR_INVALID_DATE
    * 605 - ERR_ENDDATE_SMALLER_THEN_BEGINDATE
    * 606 - ERR_INVALID_PREFIX
    * 607 - ERR_DATA_MIGRATED
    * 611 - ERR_INVALID_USER
    * 612 - ERR_BLACKLISTED_USER
    * 613 - ERR_LOGIN_DENIED
    * 614 - ERR_SIGN_FAILURE
    * 615 - ERR_REQUEST_EXPIRED
    * 616 - ERR_SYSTEM_ERROR
    * 701 - ERR_DBCHECK_CONTROL
    *
    * @param string $apiErrorCode the code from API
    *
    * @return int $reasonCode the reason code from abstract_game_api.php
    *
    */
    public function getReasons($apiErrorCode)
    {
      switch($apiErrorCode){
        case 1:
            $reasonCode = self::REASON_PARAMETER_ERROR;
            break;
        case 101:
            $reasonCode = self::REASON_ACCOUNT_NOT_EXIST;
            break;
        case 212:
            $reasonCode = self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
            break;
         case 205:
            $reasonCode = self::REASON_OPERATOR_NOT_EXIST;
            break;
        case 306:
            $reasonCode = self::REASON_CURRENCY_ERROR;
            break;
         case 401:
            $reasonCode = self::REASON_DUPLICATE_TRANSFER;
            break;
        case 402:
            $reasonCode = self::REASON_IP_NOT_AUTHORIZED;
            break;
        case 403:
            $reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
            break;
         default:
            $reasonCode = self::REASON_UNKNOWN;
         break;
      }

      return $reasonCode;
    }
 }
  /** END OF FILE */