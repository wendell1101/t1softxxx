<?php
require_once dirname(__FILE__) . '/base_model.php';

class Player_get_response_contact extends BaseModel {

	protected $tableName = 'get_response_contacts';

	function __construct() {
		parent::__construct();
	}

	/**
	 * overview : sync
	 *
	 * @param DateTime 	$from
	 * @param DateTime 	$to
	 * @param int		$playerId
	 * @return array
	 */
	public function sync(\DateTime $from, \DateTime $to, $playerId = null) {

		$fromStr = $from->format('Y-m-d H:i:') . '00';
		$toStr = $to->format('Y-m-d H:i:') . '59';

		$playerIdSql = null;
		if (!empty($playerId)) {
			$playerIdSql = ' and player_id=' . $this->db->escape($playerId);
		}

        $config = $this->utils->getConfig('third_party_get_response');
        $balance_limit_1 = isset($config['balance_limit_1'])?$config['balance_limit_1']:50;
        $balance_limit_2 = isset($config['balance_limit_2'])?$config['balance_limit_2']:10;

        //get all players by last login date
        $params=[$fromStr, $toStr];
		$t=time();
		$sql = <<<EOD
select
p.playerId player_id,
p.username player_username,
gr.contact_id,
p.email email,
p.verified_email confirm_email_status,
p.createdOn date_registered,
p.lastLoginTime date_last_login,
null date_last_deposit,
null date_first_deposit,
p.approved_deposit_count deposit_count,
p.approvedWithdrawCount withdraw_count,
null wallet_balance,
gr.game_data game_data,
null balance_limit,
null md5_sum,
pr.lastActivityTime pr_last_activity_time
from player as p
left join get_response_contacts as gr on gr.player_id=p.playerId
left join player_runtime as pr on pr.playerId=p.playerId
where pr.lastActivityTime >= ?
and pr.lastActivityTime <= ?
{$playerIdSql}
group by p.playerId
order by pr.lastActivityTime asc;

EOD;
		$rows=$this->runRawSelectSQLArray($sql, $params);
		$this->utils->info_log('get rows from game_logs', count($rows), $params, 'cost', (time()-$t));
        $this->load->model(array('common_token', 'transactions', 'wallet_model', 'total_player_game_hour'));
        $this->utils->printLastSQL();
        foreach($rows as &$row){
            $row['last_activity_time'] = null;
            if(isset($row['pr_last_activity_time'])){
                $row['last_activity_time'] = $row['pr_last_activity_time'];
                unset($row['pr_last_activity_time']);
            }
            
            $playerId = (int)$row['player_id'];

            $row['player_token'] = $this->common_token->getValidPlayerToken($playerId);

            $row['external_unique_id'] = $row['player_id'];

            //get player last deposit
            $row['date_last_deposit'] = $this->transactions->getLastDepositDate($playerId);
            //get player first deposit
            $row['date_first_deposit'] = $this->transactions->getFirstDepositDate($playerId);
            //get player total wallet balance
            $walletbalance = $row['wallet_balance'] = $this->wallet_model->getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId);
            
            //get player game data
            $existingGameData = (empty($row['game_data'])?[]:json_decode($row['game_data'], true));
                      
            $date_last_sports = $this->total_player_game_hour->getPlayerLastBetInGameType($playerId, 'sports');
            if(!empty($date_last_sports)&&isset($date_last_sports['date'])){
                $existingGameData['date_last_sports'] = date('Y-m-d', strtotime($date_last_sports['date']));
            }

            $date_last_livecasino = $this->total_player_game_hour->getPlayerLastBetInGameType($playerId, ['live_dealer', 'table_games']);
            if(!empty($date_last_livecasino)&&isset($date_last_livecasino['date'])){
                $existingGameData['date_last_livecasino'] = date('Y-m-d', strtotime($date_last_livecasino['date']));
            }
           
            $date_last_casino = $this->total_player_game_hour->getPlayerLastBetInGameType($playerId, 'slots');
            if(!empty($date_last_casino)&&isset($date_last_casino['date'])){
                $existingGameData['date_last_casino'] = date('Y-m-d', strtotime($date_last_casino['date']));
            }

            $date_last_virtual = $this->total_player_game_hour->getPlayerLastBetInGameType($playerId, 'virtual_sports');
            if(!empty($date_last_virtual)&&isset($date_last_virtual['date'])){
                $existingGameData['date_last_virtual'] = date('Y-m-d', strtotime($date_last_virtual['date']));
            }

            $date_last_esports = $this->total_player_game_hour->getPlayerLastBetInGameType($playerId, 'e_sports');
            if(!empty($date_last_esports)&&isset($date_last_esports['date'])){
                $existingGameData['date_last_esports'] = date('Y-m-d', strtotime($date_last_esports['date']));
            }
            
            $row['game_data'] = json_encode($existingGameData);

            //get player balance limit data
            $balanceLimit= [];
            $balanceLimit['balance_limit_1'] = null;
            if($walletbalance<=$balance_limit_1&&$walletbalance>=$balance_limit_2){
                $balanceLimit['balance_limit_1'] = $walletbalance;
            }

            $balanceLimit['balance_limit_2'] = null;
            if($walletbalance<$balance_limit_2){
                $balanceLimit['balance_limit_2'] = $walletbalance;
            }

            $row['balance_limit'] = json_encode($balanceLimit);

            //get player withdrawal data
            $existingWDData = (empty($row['withdrawal_data'])?[]:json_decode($row['withdrawal_data'], true));

            //get player last withdrawal
            $lastWithdrawal = $this->transactions->getLastWithdrawal($playerId);
            if($lastWithdrawal){
                if(isset($lastWithdrawal['created_at'])&&!empty($lastWithdrawal['created_at'])){
                    $existingWDData['withdrawal_date'] = $lastWithdrawal['created_at'];
                    $existingWDData['withdrawal_amount'] = $lastWithdrawal['amount'];
                    $existingWDData['withdrawal_currency'] = $this->utils->getDefaultCurrency();
                    //$existingWDData['withdrawal_number'] = $lastWithdrawal['request_secure_id'];
                }
            }

            $existingWDData['withdrawal_count'] = $row['withdraw_count'];

            $row['withdrawal_data'] = json_encode($existingWDData);

            
            //process md5_sum
            $md5String = $row['player_id'].$row['last_activity_time'];
            $row['md5_sum'] = md5($md5String);

            // clean data before saving
            unset($row['withdraw_count']);

            //cehck if data exist
            $whereParams = ['external_unique_id'=>$row['external_unique_id']];
            $qry = $this->db->get_where($this->tableName, $whereParams);
            $_oldData = $this->getOneRow($qry);
            if(!empty($_oldData)){
                $success = $this->updateData('external_unique_id', $row['external_unique_id'], $this->tableName, $row);
            }else{                
                $success = $this->insertData($this->tableName, $row);
            }

            $this->utils->debug_log('========= error insert update ============================ data', $row, $success);
        }

	}

    public function getData($fromDateTime, $toDateTime, $playerId=null) {
        $result = [];
        $fromDateHourStr = $this->utils->formatDateTimeForMysql(new DateTime($fromDateTime));
        $toDateHourStr = $this->utils->formatDateTimeForMysql(new DateTime($toDateTime));

        $this->db->select('get_response_contacts.*');
        $this->db->where("updated_at >=", $fromDateHourStr);
        $this->db->where("updated_at <=", $toDateHourStr);
        if(!empty($playerId)){
            $this->db->where('player_id', $playerId);
        }
        $qry = $this->db->get($this->tableName);
        $result = $this->getMultipleRowArray($qry);
        $this->utils->printLastSQL();
        return $result;

    }

    public function updateContactId($email, $contactid) {
        $row = [];
        $row['contact_id']=$contactid;
        return $this->updateData('email', $email, $this->tableName, $row);

    }

}

/////end of file///////