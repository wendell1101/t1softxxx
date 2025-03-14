<?php
require_once dirname(__FILE__) . '/base_model.php';

class Game_logs_stream extends BaseModel {
    protected $tableName = 'game_logs_stream';

    protected $outletMapping;
    protected $defaultPageLimit;

    public $outlet_lists=[];

    function __construct() {
        parent::__construct();
         // Initialize reverse mapping based on OUTLET_LISTS
         $this->defaultPageLimit = $this->utils->getConfig('game_provider_default_row_limit');
        $this->outlet_lists = $this->utils->getConfig('fastwin_outlet_mapping');
         foreach ($this->outlet_lists as $mainCode => $outlets) {
            foreach ($outlets as $outlet) {
                $this->outletMapping[$outlet] = $mainCode;
            }
        }
    }


    public function getMainOutletCode($outlet)
    {
        // Check if the outlet exists in the reverse mapping
        return isset($this->outletMapping[$outlet]) ? $this->outletMapping[$outlet] : null;
    }

    public function isMainOutletCode($outlet)
    {
        // Check if the outlet is a main code by verifying it's a key in OUTLET_LISTS
        return array_key_exists($outlet, $this->outlet_lists);
    }

    public function queryGameLogStream($start_date, $end_date, $page = 1, $perPage = 100, $game_platform_id=null){
        $tableName = $this->tableName;
		$customTable = $this->utils->getConfig('game_provider_default_table_name');
		$exclude_tag_ids = $this->utils->getConfig('fastwin_mx_api_exclude_player_tag_ids');
        if(isset($customTable) && !empty($customTable)){
            $tableName = $customTable;
        }

        $additionalWhereCondition = '';
        $additionalJoins = '';

        if ($game_platform_id !== null) {
            $additionalWhereCondition = " AND {$tableName}.game_platform_id = ?";
        }

        $startAtField = 'end_at';
        $endAtField = 'end_at';
        $mx_api_where_condition_date = $this->utils->getConfig('mx_api_where_condition_date');
        if(is_array($mx_api_where_condition_date) && !empty($mx_api_where_condition_date)){
            $startAtField = $mx_api_where_condition_date['start_at'];
            $endAtField = $mx_api_where_condition_date['end_at'];
        }

  
        if (!empty($exclude_tag_ids) && is_array($exclude_tag_ids)) {
            $flattened_ids = [];
            array_walk_recursive($exclude_tag_ids, function ($id) use (&$flattened_ids) {
                $flattened_ids[] = $id;
            });

            if (!empty($flattened_ids)) {
                $playerIds = $this->excludePlayerIdsWithSpecificTag($flattened_ids);
                $excludePlayerIds = implode(',', $playerIds);
                $additionalJoins = "LEFT JOIN playertag ON playertag.playerId = {$tableName}.player_id";
                $additionalWhereCondition .= " AND {$tableName}.player_id NOT IN ($excludePlayerIds)";
            }
        }

        $offset = ($page - 1) * $perPage;
        $sql=<<<EOD
SELECT 
{$tableName}.start_at as game_date,
{$tableName}.external_uniqueid,
{$tableName}.`table` as round_id,
{$tableName}.bet_amount,
{$tableName}.win_amount,
{$tableName}.loss_amount,
{$tableName}.real_betting_amount,
{$tableName}.result_amount,
{$tableName}.end_at,
{$tableName}.updated_at,
{$tableName}.bet_for_cashback,
{$tableName}.bet_details,
player.username as player_username,
player.playerId as player_id,
player.username as player_username,
fastwin_outlet.networkcode as outlet_code,
fastwin_outlet.main_outlet,
game_description.external_game_id,
game_description.english_name as game_name,
external_system.system_name as game_provider
from {$tableName}
LEFT JOIN player on {$tableName}.player_id=player.playerId
LEFT JOIN game_description on game_description.id = {$tableName}.game_description_id
LEFT JOIN external_system on external_system.id={$tableName}.game_platform_id
LEFT JOIN playerdetails_extra on playerdetails_extra.playerId={$tableName}.player_id
LEFT JOIN fastwin_outlet on fastwin_outlet.encryptcode = playerdetails_extra.storeCode
{$additionalJoins}
WHERE
{$tableName}.{$startAtField} >=? and {$tableName}.{$endAtField} <=? 
{$additionalWhereCondition}
LIMIT ? OFFSET ?
EOD;
        $params = [
            $start_date,
            $end_date,
            $perPage,  #Limit
            $offset    # Offset
        ];

        if(!empty($additionalWhereCondition) && $game_platform_id != null){
            $params = [
                $start_date,
                $end_date,
                $game_platform_id,
                $perPage,  #Limit
                $offset    # Offset
            ];
        }

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryGameLogStream: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function queryGameLogStreamCount($start_date, $end_date, $game_platform_id=null) {
        $tableName = $this->tableName;
		$customTable = $this->utils->getConfig('game_provider_default_table_name');
        $exclude_tag_ids = $this->utils->getConfig('fastwin_mx_api_exclude_player_tag_ids');
        if(isset($customTable) && !empty($customTable)){
            $tableName = $customTable;
        }
        $additionalWhereCondition = '';
        $additionalJoins = '';

        if ($game_platform_id !== null) {
            $additionalWhereCondition = " AND {$tableName}.game_platform_id = ?";
        }

        if (!empty($exclude_tag_ids) && is_array($exclude_tag_ids)) {
            $flattened_ids = [];
            array_walk_recursive($exclude_tag_ids, function ($id) use (&$flattened_ids) {
                $flattened_ids[] = $id;
            });

            if (!empty($flattened_ids)) {
                $playerIds = $this->excludePlayerIdsWithSpecificTag($flattened_ids);
                $excludePlayerIds = implode(',', $playerIds); 
                $additionalJoins = "LEFT JOIN playertag ON playertag.playerId = {$tableName}.player_id";
                $additionalWhereCondition .= " AND {$tableName}.player_id NOT IN ($excludePlayerIds)";
            }
        }

        $startAtField = 'end_at';
        $endAtField = 'end_at';
        $mx_api_where_condition_date = $this->utils->getConfig('mx_api_where_condition_date');
        if(is_array($mx_api_where_condition_date) && !empty($mx_api_where_condition_date)){
            $startAtField = $mx_api_where_condition_date['start_at'];
            $endAtField = $mx_api_where_condition_date['end_at'];
        }

        // SQL query to get the total count of records without limit and offset
        $sql = <<<EOD
SELECT 
    COUNT({$tableName}.id) AS total_count
FROM {$tableName}
LEFT JOIN player ON {$tableName}.player_id = player.playerId
LEFT JOIN game_description ON game_description.id = {$tableName}.game_description_id
LEFT JOIN external_system ON external_system.id = {$tableName}.game_platform_id
LEFT JOIN playerdetails_extra ON playerdetails_extra.playerId = {$tableName}.player_id
LEFT JOIN fastwin_outlet ON fastwin_outlet.encryptcode = playerdetails_extra.storeCode
{$additionalJoins}
WHERE
    {$tableName}.{$startAtField} >=? and {$tableName}.{$endAtField} <=? 
    {$additionalWhereCondition}
EOD;

        $params = [
            $start_date,
            $end_date
        ];

        if(!empty($additionalWhereCondition) && $game_platform_id != null){
            $params = [
                $start_date,
                $end_date,
                $game_platform_id
            ];
        }

        $result = $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryGameLogStreamCount: raw_query', $this->CI->db->last_query());
        
        return isset($result[0]['total_count']) ? (int) $result[0]['total_count'] : 0;
    }

    public function queryPlayerTransactionsSummaryByOutlet($start_date, $end_date){
        $this->load->model(array('transactions'));
        $depositTransTypes = [
            Transactions::DEPOSIT,
            Transactions::MANUAL_ADD_BALANCE
        ];

        $withdrawTransTypes = [
            Transactions::WITHDRAWAL,
            Transactions::WITHDRAW_FROM_AFFILIATE,
            Transactions::INTERNALWITHDRAWAL,
            Transactions::WITHDRAW_FROM_AGENT,
        ];

        $depositTransTypeList = implode(',', $depositTransTypes);
        $withdrawTransTypeList = implode(',', $withdrawTransTypes);
        $sql=<<<EOD
SELECT
original.main_outlet,
t.amount,
SUM(
    CASE
        WHEN t.transaction_type IN ($depositTransTypeList) THEN t.amount
        ELSE 0
    END
) AS total_deposit_amount,
SUM(
    CASE
        WHEN t.transaction_type IN ($withdrawTransTypeList) THEN t.amount
        ELSE 0
    END
) AS total_withdraw_amount
FROM
    fastwin_outlet AS original
    LEFT JOIN playerdetails_extra AS pe ON pe.storeCode = original.encryptcode
    LEFT JOIN transactions AS t ON t.to_id = pe.playerId AND t.status = 1 AND t.created_at >=? AND t.created_at<=?
GROUP BY
    original.main_outlet
    ORDER BY total_deposit_amount DESC
EOD;

        $params = [
            $start_date,
            $end_date,
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);

        $precision =  $this->utils->getConfig('fastwin_mx_api_precision');
        if(!$precision){
            $precision = 2;
        }

        $rlt = array_map(function ($item) use($precision){
            $item['total_deposit_amount'] = floatval($item['total_deposit_amount']);
            $item['total_withdraw_amount'] = floatval($item['total_withdraw_amount']);
            $item['total_gh'] = $this->utils->dBtoGameAmount($item['total_deposit_amount'] - $item['total_withdraw_amount'],$precision);
            return $item;
        }, $rlt);
        $this->utils->debug_log('queryPlayerTransactionsSummaryByOutlet: raw_query', $this->CI->db->last_query());
        return $rlt;
    }


    public function queryTransactionsWithAgency($start_date, $end_date, $page = 1, $perPage = 1000){
        $tableName = 'transactions';
       
        $offset = ($page - 1) * $perPage;
        $sql=<<<EOD
SELECT 
    p.username,
	original.request_secure_id as trans_id,
	original.transaction_type,
	outlet.main_outlet,
	outlet.networkcode,
CASE 
        WHEN original.transaction_type = 2 THEN -original.amount 
        ELSE original.amount 
END AS amount,
    original.created_at,
CASE
	WHEN original.transaction_type = 1 then payment_account.payment_account_name
	WHEN original.transaction_type = 2 then walletaccount.bankName
	ELSE NULL
END AS payment_account_name,
original.before_balance
FROM {$tableName} as original
LEFT JOIN playerdetails_extra AS pe ON pe.playerId = original.to_id
LEFT JOIN fastwin_outlet AS outlet ON outlet.encryptcode = pe.storeCode
LEFT JOIN player as p on p.playerId = original.to_id
LEFT JOIN payment_account on payment_account.id=original.payment_account_id
LEFT JOIN walletaccount ON walletaccount.transaction_id=original.id
where original.status=1
AND original.transaction_type in(1,2)
AND original.created_at >=?
AND original.created_at <=?
ORDER BY original.created_at ASC
LIMIT ? OFFSET ?
EOD;
        $params = [
            $start_date,
            $end_date,
            $perPage,  #Limit
            $offset    # Offset
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryTransactionsWithAgency: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function queryTransactionsWithAgencyCount($start_date, $end_date){
        $tableName = 'transactions';
        $sql=<<<EOD
SELECT 
    COUNT(original.id) as total_count
FROM {$tableName} as original
LEFT JOIN playerdetails_extra AS pe ON pe.playerId = original.to_id
LEFT JOIN fastwin_outlet AS outlet ON outlet.encryptcode = pe.storeCode
LEFT JOIN player as p on p.playerId = original.to_id
LEFT JOIN payment_account on payment_account.id=original.payment_account_id
LEFT JOIN walletaccount ON walletaccount.transaction_id=original.id
where original.status=1
AND original.transaction_type in(1,2)
AND original.created_at >=?
AND original.created_at <=?
EOD;
        $params = [
            $start_date,
            $end_date,
        ];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryTransactionsWithAgencyCount: raw_query', $this->CI->db->last_query());
        return isset($rlt[0]['total_count']) ? (int) $rlt[0]['total_count'] : 0;
    }

    private function excludePlayerIdsWithSpecificTag($tagIds){
        $excludetagIds = implode(',', $tagIds); 
        $tableName='playertag';
        $sql=<<<EOD
SELECT 
    original.playerId
FROM {$tableName} as original
WHERE tagId IN (?)
EOD;
        $params = [$excludetagIds];
        $rlt = $this->runRawSelectSQLArray($sql, $params);
        return array_column($rlt, 'playerId');
    }


    public function getPlayers($start_date, $end_date, $page = 1, $perPage = 100, $extra=null){
        $tableName = 'player';
        $userName = isset($extra['username']) ? $extra['username'] : null;
        $additionalWhereCondition = '';

        if ($userName !== null) {
            $additionalWhereCondition = " AND original.username = ?";
        }

        $offset = ($page - 1) * $perPage;
        $sql=<<<EOD
SELECT
	original.playerId,
	original.username,
	original.createdOn,
	playerdetails.firstName,
	playerdetails.lastName,
	playerdetails.birthdate,
	playerdetails.birthplace,
	playerdetails.contactNumber,
	playerdetails.country,
	playerdetails.citizenship,
	playerdetails.address,
	playerdetails_extra.middleName,
	playerdetails_extra.natureWork,
	playerdetails_extra.sourceIncome,
	playerdetails_extra.storeCode,
    tag.tagName as playerBlockReasonFromTag,
    blocked_players.reason as playerBlockReason
FROM {$tableName} as original
	LEFT JOIN playerdetails ON original.playerId = playerdetails.playerId
	LEFT JOIN playerdetails_extra ON original.playerId = playerdetails_extra.playerId
	LEFT JOIN playertag ON original.playerId = playertag.playerId
	LEFT JOIN tag ON playertag.tagId = tag.tagId
	LEFT JOIN blocked_players ON original.playerId = blocked_players.player_id
WHERE
	original.createdOn >=?  AND original.createdOn <=?
    {$additionalWhereCondition}
LIMIT ? OFFSET ?
EOD;
        $params = [
            $start_date,
            $end_date,
            $perPage,  #Limit
            $offset    # Offset
        ];

        if(!empty($additionalWhereCondition) && $userName != null){
            $params = [
                $start_date,
                $end_date,
                $userName,
                $perPage,  #Limit
                $offset    # Offset
            ];
        }

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('MX API getPlayers: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function getPlayersCount($start_date, $end_date, $extra=null){
        $tableName = 'player';
        $userName = isset($extra['username']) ? $extra['username'] : null;
        $additionalWhereCondition = '';

        if ($userName !== null) {
            $additionalWhereCondition = " AND original.username = ?";
        }

        $sql=<<<EOD
SELECT
    COUNT(original.playerId) AS total_count
FROM {$tableName} as original
	LEFT JOIN playerdetails ON original.playerId = playerdetails.playerId
	LEFT JOIN playerdetails_extra ON original.playerId = playerdetails_extra.playerId
    LEFT JOIN playertag ON original.playerId = playertag.playerId
	LEFT JOIN tag ON playertag.tagId = tag.tagId
    LEFT JOIN blocked_players ON original.playerId = blocked_players.player_id
WHERE
	original.createdOn >=?  AND original.createdOn <=?
    {$additionalWhereCondition}
EOD;
        $params = [
            $start_date,
            $end_date,
        ];

        if(!empty($additionalWhereCondition) && $userName != null){
            $params = [
                $start_date,
                $end_date,
                $userName
            ];
        }

        $result =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('MX API getPlayersCount: raw_query', $this->CI->db->last_query());
        return isset($result[0]['total_count']) ? (int) $result[0]['total_count'] : 0;
    }

    public function getLastPlayedDateByPlayerId($playerId)
    {
        $this->db->select('betting_time')
                 ->from('player_last_played')
                 ->where('player_id', $playerId);
        $query = $this->db->get();
        $row = $query->row();
        // return $row ? date('Y-m-d', strtotime($row->betting_time)) : null;
        return $row ? $row->betting_time : null; 
    }

    public function replace_player_last_bet($from, $to){
        $sql=<<<EOD
REPLACE INTO player_last_played (player_id, betting_time)
SELECT player_id, MAX(bet_at) AS betting_time
FROM game_logs_stream
WHERE bet_at >= ? AND bet_at <= ? 
GROUP BY player_id
EOD;
        $params = [
            $from,
            $to,
        ];

        $cnt = $this->runRawUpdateInsertSQL($sql, $params);
        return $cnt;
    }

    public function queryPromoTransactionsWithAgency($start_date, $end_date, $page = 1, $perPage = 1000, $extra=[]){
        $tableName = 'playerpromo';
        
        $playerUsername = isset($extra['player_username']) ? $extra['player_username'] : null;

        $additionalWhereCondition = '';
        $additionalJoins = '';
        $promoIds = $this->utils->getConfig('fastwin_mx_api_allowed_promo_ids');


        if (!empty($promoIds) && is_array($promoIds)) {
            $promoIds = implode(',', $promoIds); 
            $additionalWhereCondition .= " AND promorules.promorulesId  IN ($promoIds)";
        }

        if(!empty($playerUsername)){
            $additionalWhereCondition .= " AND p.username = ?";
        }
       
        $offset = ($page - 1) * $perPage;
        $sql=<<<EOD
SELECT
    p.username,
    outlet.main_outlet,
    outlet.networkcode,
    original.bonusAmount as amount,
    promorules.promoName,
    original.playerpromoid as trans_id,
    original.dateProcessed as created_at,
    pcms.promo_code,
    t.before_balance
FROM
	{$tableName} as original
    LEFT JOIN playerdetails_extra AS pe ON pe.playerId = original.playerId
    LEFT JOIN fastwin_outlet AS outlet ON outlet.encryptcode = pe.storeCode
    LEFT JOIN player as p on p.playerId = original.playerId
    LEFT JOIN promorules on promorules.promorulesId = original.promorulesId
    LEFT JOIN transactions as t on t.player_promo_id=original.playerpromoId
    LEFT JOIN promocmssetting as pcms on pcms.promoCmsSettingId = original.promoCmsSettingId
WHERE 1
AND original.transactionStatus=1
{$additionalWhereCondition}
AND original.dateProcessed>=?
AND original.dateProcessed<=?
ORDER BY original.dateProcessed ASC
LIMIT ? OFFSET ?
EOD;

         $params = !empty($playerUsername) ? [$playerUsername, $start_date, $end_date, $perPage, $offset] : [$start_date, $end_date, $perPage, $offset];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryPromoTransactionsWithAgency: raw_query', $this->CI->db->last_query());
        return $rlt;
    }

    public function queryPromoTransactionsWithAgencyCount($start_date, $end_date, $extra=[]){
        $tableName = 'playerpromo';
        
        $playerUsername = isset($extra['player_username']) ? $extra['player_username'] : null;

        $additionalWhereCondition = '';
        $additionalJoins = '';
        $promoIds = $this->utils->getConfig('fastwin_mx_api_allowed_promo_ids');


        if (!empty($promoIds) && is_array($promoIds)) {
            $promoIds = implode(',', $promoIds); 
            $additionalWhereCondition .= " AND promorules.promorulesId  IN ($promoIds)";
        }

        if(!empty($playerUsername)){
            $additionalWhereCondition .= " AND p.username = ?";
        }


        $sql=<<<EOD
SELECT 
    COUNT(original.playerpromoid) as total_count
FROM {$tableName} as original
    LEFT JOIN playerdetails_extra AS pe ON pe.playerId = original.playerId
    LEFT JOIN fastwin_outlet AS outlet ON outlet.encryptcode = pe.storeCode
    LEFT JOIN player as p on p.playerId = original.playerId
    LEFT JOIN promorules on promorules.promorulesId = original.promorulesId
    LEFT JOIN transactions as t on t.player_promo_id=original.playerpromoId
    LEFT JOIN promocmssetting as pcms on pcms.promoCmsSettingId = original.promoCmsSettingId 
WHERE 1
AND original.transactionStatus=1
AND original.dateProcessed>=?
AND original.dateProcessed<=?
{$additionalWhereCondition}
EOD;
        $params = !empty($playerUsername) ? [$playerUsername, $start_date, $end_date] : [$start_date, $end_date];

        $rlt =  $this->runRawSelectSQLArray($sql, $params);
        $this->utils->debug_log('queryTransactionsWithAgencyCount: raw_query', $this->CI->db->last_query());
        return isset($rlt[0]['total_count']) ? (int) $rlt[0]['total_count'] : 0;
    }
}
/////end of file///////