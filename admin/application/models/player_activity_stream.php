<?php
require_once dirname(__FILE__) . '/base_model.php';

class Player_activity_stream extends BaseModel {
    protected $tableName = 'player_activity_stream';

   
    const PLAYER_ACTION_TYPE_LOGIN = 'login';
    const PLAYER_ACTION_DEPOSIT = 'deposit';
	const PLAYER_ACTION_WITHDRAW = 'withdraw';
	const PLAYER_ACTION_LAUNCH_GAME_REAL = 'launch_game_real';
	const PLAYER_ACTION_LAUNCH_GAME_DEMO = 'launch_game_demo';
	const PLAYER_ACTION_LAUNCH_GAME_LOBBY = 'launch_game_lobby';


    function __construct() {
        parent::__construct();
    }

    protected function getPlayerActionTypes() {
        $defaultActionMapping = [
            self::PLAYER_ACTION_DEPOSIT => self::PLAYER_ACTION_DEPOSIT,
            self::PLAYER_ACTION_WITHDRAW => self::PLAYER_ACTION_WITHDRAW,
        ];
    
        $playerCenterRequestApiMapping = $this->utils->getConfig('player_activity_logs_player_center_request_api_mapping') ?: [];

        return array_merge(
            $defaultActionMapping,
            $playerCenterRequestApiMapping
        );
    }

    public function getPlayerActionType($request_api) {
        $playerActionTypes = $this->getPlayerActionTypes();
        return isset($playerActionTypes[$request_api]) ? $playerActionTypes[$request_api] : null;
    }

    public function queryPlayerActivityLogs($tableName, $start_date, $end_date){
        $requestApis = $this->utils->getConfig('player_activity_logs_player_center_request_api_mapping') ?: [];
        if (!$requestApis) {
            $requestApis = [];  // Default to empty array if null or other value
        }
        $player_activity_logs_request_api_mapping = array_keys($requestApis);

        $allowedRequestApi = "'" . implode("','", $player_activity_logs_request_api_mapping) . "'";

        $sql=<<<EOD
SELECT
	original.id,
    original.external_system_id,
    original.response_result_id,
    original.content,
    original.request_api,
    original.request_params,
    original.status_code,
    original.player_id,
    original.error_code,
    original.cost_ms,
    original.decode_result,
    original.external_transaction_id,
    original.full_url,
    original.request_id,
    original.status,
    original.created_at
FROM {$tableName} as original
WHERE
original.request_api IN ({$allowedRequestApi}) 
AND original.created_at >=?  
AND original.created_at <=?
EOD;
        $params = [
            $start_date,
            $end_date
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryPlayerActivityLogs: raw_query', $this->CI->db->last_query());
        return $rlt;
    }


    public function queryPlayerActivityLogsToBeDeleted() {
        $player_activity_logs_allowed_days = $this->utils->getConfig('player_activity_logs_allowed_days');
        // Get the current date minus allowed_days
        $date = date('Y-m-d', strtotime("-{$player_activity_logs_allowed_days} days"));
        $tableName = $this->tableName;


    $sql = <<<EOD
SELECT
    id
FROM {$tableName} AS original
WHERE
    original.date_time <= ?
EOD;

        $params = [
            $date,
        ];

        $rlt = $this->runRawSelectSQLArray($sql, $params);

        $this->utils->debug_log('queryPlayerActivityLogsToBeDeleted: raw_query', $this->CI->db->last_query());

        return array_column($rlt, 'id');
    }
    
    public function savePlayerActivityLogs($data){
        $t=time();
		$cnt=0;
		$limit=500;
        if(!empty($data)){
           $success=$this->runBatchInsertWithLimit($this->db, $this->tableName, $data, $limit, $cnt, true);
            $this->utils->info_log('insert into player_activity_stream', $cnt, 'cost', (time()-$t));
            $enable_delete_old_records = $this->utils->getConfig('player_activity_logs_enable_delete_old_records');
            if($enable_delete_old_records){
                $this->deleteOldRecords();
            }
            return [
                'success' => $success
            ];
        }
    } 
    
    public function deleteOldRecords(){
        $data = $this->queryPlayerActivityLogsToBeDeleted();
        $t=time();
		$cnt=0;
		$limit=500;
        if(!empty($data)){
            $success=$this->runBatchDeleteByIdWithLimit($this->tableName, $data, 'id', $limit, $this->db);
            $this->utils->info_log('delete old records from player_activity_stream', $cnt, 'cost', (time()-$t));
            return [
                'success' => $success
            ];
        }
    }

    public function queryPlayerActivityLogsPaginated($start_date, $end_date, $page = 1, $perPage = 1000){
        $tableName = 'player_activity_stream';
       
        $offset = ($page - 1) * $perPage;
        $sql=<<<EOD
SELECT
	original.request_id,
	original.player_id,
	original.player_activity_action_type,
	original.client_ip,
	original.device_type,
	original.status,
	original.http_status_code,
	original.request_params,
	original.created_at,
    original.domain,
    original.extra_info,
    p.username,
	p.lastLoginTime
FROM
	{$tableName} as original
    LEFT JOIN player as p on p.playerId=original.player_id
WHERE 1	
AND original.created_at >=?
AND original.date_time <=?
ORDER BY original.date_time ASC
LIMIT ? OFFSET ?
EOD;
        $params = [
            $start_date,
            $end_date,
            $perPage,  #Limit
            $offset    # Offset
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryPlayerActivityLogs: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function queryPlayerActivityLogsPaginatedTotalCount($start_date, $end_date){
        $tableName = 'player_activity_stream';
        $sql=<<<EOD
SELECT
	count(original.id) as total_count
FROM
	{$tableName} as original
WHERE 1	
AND original.date_time >=?
AND original.date_time <=?
EOD;
        $params = [
            $start_date,
            $end_date,
        ];

        $rlt =  $this->runOneRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryPlayerActivityLogsCount: raw_query', $this->CI->db->last_query());
        return isset($rlt['total_count']) ? (int) $rlt['total_count'] : 0;
    }

    public function queryDepositTransactions($tableName, $start_date, $end_date){

        $sql=<<<EOD
SELECT
	player_id,
	system_id,
	amount,
	status,
	notes,
	created_at,
	updated_at,
	external_order_id,
	ip,
	geo_location,
	payment_account_id,
	currency,
	wallet_id,
	payment_type_name,
	payment_account_name,
	payment_account_number,
	payment_branch_name,
	transaction_id,
	secure_id,
	direct_pay_extra_info,
	browser_user_agent,
	process_time
FROM {$tableName} as original
WHERE 1
AND original.process_time >=?  
AND original.process_time <=?
EOD;
        $params = [
            $start_date,
            $end_date
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryTransactions: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function queryWithdrawTransactions($tableName, $start_date, $end_date){

        $sql=<<<EOD
SELECT
	walletAccountId,
	playerAccountId,
	walletType,
	amount,
	processedBy,
	processDateTime as process_time,
	notes,
	dwStatus as status,
	dwDateTime as date_time,
	transactionType,
	dwIp as ip,
	dwlocation as geo_location,
	transactionCode,
	playerId,
	transaction_id,
	paymentAPI,
	player_bank_details_id, 
	bankAccountFullName,
	bankAccountNumber,
	bankName,
	browser_user_agent,
	withdrawal_bank_fee,
	withdrawal_fee_amount
FROM {$tableName} as original
WHERE 1
AND original.processDateTime >=?  
AND original.processDateTime <=?
EOD;
        $params = [
            $start_date,
            $end_date
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryWithdrawTransactions: raw_query', $this->CI->db->last_query());
        return $rlt;
    }
}

/////end of file///////