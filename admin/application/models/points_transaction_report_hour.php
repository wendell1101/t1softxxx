<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Total_player_game_hour
 *
 * General behaviors include :
 *
 * * Get last sync hour
 * * Sync total player game per hour
 * * Get first/last record
 * * Get total bets win and loss by player
 * * Get player game summary
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Points_transaction_report_hour extends BaseModel {

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "points_transaction_report_hour";

	public function sync(\DateTime $from, \DateTime $to, $playerId = null, $syncPlayerPoints = 'false') {
		$isEnabled = $this->utils->getConfig('enable_beting_amount_to_point');		
		if(!$isEnabled){
			$this->utils->debug_log('disabled enable_beting_amount_to_point');
			return true;
		}

		$fromStr = $from->format('Y-m-d H:i') . ':00';
		$toStr = $to->format('Y-m-d H:i') . ':59';
		$fromDateHourStr = $this->utils->formatDateHourForMysql($from);
        $toDateHourStr = $this->utils->formatDateHourForMysql($to);

		$playerIdSql = null;
		if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
			$playerIdSql = ' and tpgh.player_id=' . $this->db->escape($playerId);
		}

        //delete existing data
		$this->db->where('date_hour >=', $fromDateHourStr)
			->where('date_hour <=', $toDateHourStr);
		$this->db->delete('points_transaction_report_hour');
        $currencyKey=$this->utils->getActiveCurrencyKey();

		$now=$this->utils->getNowForMysql();
		// $this->utils->printLastSQL();
		$params=[$fromDateHourStr, $toDateHourStr];
		$t=time();
		$sql = <<<EOD
select
tpgh.game_description_id,
tpgh.game_platform_id,
tpgh.game_type_id,
concat(tpgh.player_id, '_', tpgh.game_platform_id, '_', tpgh.game_type_id, '_', tpgh.game_description_id, '_',tpgh.`date`, '_',tpgh.`hour`) as uniqueid,
uniqueid as source_uniqueid,
tpgh.player_id,
COALESCE(round(sum(tpgh.betting_amount),4),0) as betting_amount,
COALESCE(round(sum(tpgh.real_betting_amount),4),0) as real_betting_amount,
COALESCE(round(sum(tpgh.result_amount),4),0) as result_amount,
COALESCE(round(sum(tpgh.win_amount),4),0) as win_amount, 
COALESCE(round(sum(tpgh.loss_amount),4),0) as loss_amount,
'{$currencyKey}' as currency_key,
tpgh.date_hour,
tpgh.`hour`,
tpgh.`date`,
p.levelId as vip_id,
COALESCE(vip.bet_convert_rate,0) as bet_points_rate,
COALESCE((vip.bet_convert_rate * sum(tpgh.betting_amount))/100,0) as bet_points,
COALESCE(vip.winning_convert_rate,0) as win_points_rate,
COALESCE((vip.winning_convert_rate * sum(tpgh.win_amount))/100,0) as win_points,
COALESCE(vip.losing_convert_rate,0) as lose_points_rate,
COALESCE((vip.losing_convert_rate * sum(tpgh.loss_amount))/100,0) as lose_points

from total_player_game_hour as tpgh
join player as p on p.playerId=tpgh.player_id
join vipsettingcashbackrule as vip on vip.vipsettingcashbackruleId = p.levelId
LEFT JOIN vipsetting as vips on vips.vipSettingId=vip.vipSettingId
where date_hour >= ?
and date_hour <= ?
{$playerIdSql}
group by player_id, game_platform_id, game_type_id, game_description_id, date
EOD;
		$rows=$this->runRawSelectSQLArray($sql, $params);
		$this->utils->debug_log('Points_transaction_report_hour->sunc SQL', $sql);
		$this->utils->info_log('get rows from points_transaction_report_hour', count($rows), $params, 'cost', (time()-$t));
		$t=time();
		$cnt=0;
		$limit=100;
		$success=$this->runBatchInsertWithLimit($this->db, $this->tableName, $rows, $limit, $cnt);
		
        $this->utils->info_log('insert into points_transaction_report_hour', $cnt, 'cost', $params, (time()-$t));
		$this->load->model(array('point_transactions'));

		//when called trigger sync point_transactions
		if($success && !empty($rows) && $syncPlayerPoints=='true'){
			//sync points to do in cronjob
			//$this->load->model(array('point_transactions'));
			$this->point_transactions->syncPlayerPoints($from, $to, $rows, $playerId);
		}
		
		//check for possible deleted data
		$this->point_transactions->syncDeletedPlayerPoints($from, $to, $playerId);

		//unset($rows);
		return $rows;
	}

	public function getPointsTransactionReportHourByUniqueid($external_transaction_id) {
		$query = $this->db->query("SELECT *
			FROM {$this->tableName}		
			WHERE uniqueid = ?;", [$external_transaction_id]);

		$result = $query->result_array();
		$this->utils->debug_log('getPointsTransactionReportHourByUniqueid',$this->db->last_query());

		if (empty($result)) {			
			return null;
		}
		return $result;
	}

	public function getPointsTransactionReportHourByDateHour($external_transaction_id) {
		$query = $this->db->query("SELECT *
			FROM {$this->tableName}		
			WHERE uniqueid = ?;", [$external_transaction_id]);

		$result = $query->result_array();
		$this->utils->debug_log('getPointsTransactionReportHourByUniqueid',$this->db->last_query());

		if (empty($result)) {			
			return null;
		}
		return $result;
	}

	public function getPointsTransactionReportByUpdatedAt(\DateTime $fromDateTime, \DateTime $toDateTime) {
		$fromDateHourStr = $this->utils->formatDateHourForMysql($fromDateTime);
		$toDateHourStr = $this->utils->formatDateHourForMysql($toDateTime);
		$query = $this->db->query("SELECT ptrh.*, vip.points_limit, vip.points_limit_type,
		vip.vipsettingcashbackruleId vip_level_id, vip.vipLevelName vip_level_name, vips.groupName vip_group_name
			FROM {$this->tableName}	as ptrh	
			LEFT JOIN vipsettingcashbackrule as vip on vip.vipsettingcashbackruleId=ptrh.vip_id
			LEFT JOIN vipsetting as vips on vips.vipSettingId=vip.vipSettingId
			WHERE date_hour >= ? and date_hour <= ?
			ORDER BY date_hour asc;", [$fromDateHourStr, $toDateHourStr]);

		$result = $query->result_array();
		//$this->utils->debug_log('getPointsTransactionReportByUpdatedAt',$this->db->last_query());

		if (empty($result)) {			
			return null;
		}
		return $result;
	}

	public function getPointsTransactionReportByDateHour(\DateTime $fromDateTime, \DateTime $toDateTime) {
		$fromDateHourStr = $this->utils->formatDateHourForMysql($fromDateTime);
		$toDateHourStr = $this->utils->formatDateHourForMysql($toDateTime);
		$query = $this->db->query("SELECT *
			FROM {$this->tableName}		
			WHERE date_hour >= ? and date_hour <= ?;", [$fromDateHourStr, $toDateHourStr]);

		$result = $query->result_array();
		$this->utils->debug_log('getPointsTransactionReportByUpdatedAt',$this->db->last_query());

		if (empty($result)) {			
			return null;
		}
		return $result;
	}


}

///END OF FILE///////
