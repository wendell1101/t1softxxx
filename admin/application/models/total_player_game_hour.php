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
class Total_player_game_hour extends BaseModel {

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "total_player_game_hour";

	/**
	 * overview : get last sync hour
	 *
	 * @return string
	 */
	public function getLastSyncHour() {
		$this->db->order_by('date_hour desc');
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'hour');
	}

	/**
	 * overview : check if unique id already exist
	 *
	 * @param string $uniqueId
	 * @return boolean
	 */
	function isUniqueIdAlreadyExists($uniqueId) {
		$qry = $this->db->get_where($this->tableName, array('uniqueid' => $uniqueId));
		if ($this->getOneRow($qry) == null) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * overview : sync to total player game hour
	 *
	 * @param string $data
	 * @return array
	 */
	function syncToTotalPlayerGameHour($data) {
		if ($this->isUniqueIdAlreadyExists($data['uniqueid'])) {
			$this->db->where('uniqueid', $data['uniqueid']);
			return $this->db->update($this->tableName, $data);
		} else {
			return $this->db->insert($this->tableName, $data);
		}
	}

    /**
     * overview : get all record of all player
     *
     * @param datetime $dateFrom
     * @param datetime $dateTo
     * @return boolean
     */
    function getAllRecordOfPlayer($hourFrom, $hourTo, $playerId, $gameDescriptionId) {
        $result = [];
        $fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($hourFrom));
        $toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($hourTo));

        $this->db->select("player_id, game_platform_id, game_type_id, game_description_id, date,sum(betting_amount) as betting_amount", false);
        $this->db->where("date_hour >=", $fromDateHourStr);
        $this->db->where("date_hour <=", $toDateHourStr);
        $this->db->where('player_id', $playerId);
        $this->db->where_in('game_description_id', $gameDescriptionId);
        $this->db->group_by(["game_platform_id", "game_type_id", "game_description_id"]);
        $qry = $this->db->get($this->tableName);
        $rows = $this->getMultipleRow($qry);

        $this->utils->printLastSQL();

        if(!empty($rows)){
            foreach($rows as $row){
                $result[$row->game_description_id] = $row->betting_amount;
            }
        }
        $this->utils->debug_log('get all record of player result', $result);
        return $result;

    }


	/**
	 * overview : get all record per day of all player
	 *
	 * @param datetime $dateFrom
	 * @param datetime $dateTo
	 * @return boolean
	 */
	function getAllRecordPerDayOfAllPlayer($dateFrom, $dateTo) {
		$this->db->select("player_id, game_platform_id, game_type_id, game_description_id, date,sum(betting_amount) as betting_amount,sum(result_amount) as result_amount", false);
		$this->db->where("date >=", $dateFrom);
		$this->db->where("date <=", $dateTo);
		$this->db->group_by(array("player_id", "game_platform_id", "game_type_id", "game_description_id", "date"));
		$qry = $this->db->get($this->tableName);
		return $this->getMultipleRow($qry);

		// $qry = $this->db->query("SELECT * FROM $this->tableName
		// 							WHERE date >= '" . $dateFrom . "'
		// 							AND date <= '" . $dateTo . "'
		// 						");
		// return $this->getMultipleRow($qry);
	}

	/**
	 * overview : get all record per day of player
	 *
	 * @param datetime	$dateFrom
	 * @param datetime	$dateTo
	 * @param int		$playerId
	 * @return array
	 */
	function getAllRecordPerDayOfPlayer($dateFrom, $dateTo, $playerId) {
		$this->db->select("player_id, game_platform_id, game_type_id, game_description_id, date,sum(betting_amount) as betting_amount,sum(result_amount) as result_amount", false);
		$this->db->where("date >=", $dateFrom);
		$this->db->where("date <=", $dateTo);
		$this->db->where("player_id", $playerId);
		$this->db->group_by(array("player_id", "game_platform_id", "game_type_id", "game_description_id", "date"));
		$qry = $this->db->get($this->tableName);
		return $this->getMultipleRow($qry);

		// $qry = $this->db->query("SELECT * FROM $this->tableName
		// 							WHERE date >= '" . $dateFrom . "'
		// 							AND date <= '" . $dateTo . "'
		// 							AND player_id <= '" . $playerId . "'
		// 						");
		// return $this->getMultipleRow($qry);
	}

	/**
	 * overview : get operator record per hour
	 *
	 * @param datetime	$dateFrom
	 * @param datetime	$dateTo
	 * @return null
	 */
	public function getOperatorRecordPerHour($dateFrom, $dateTo) {
		$this->db->select("game_platform_id, game_type_id, game_description_id, date,hour,sum(betting_amount) as betting_amount,sum(result_amount) as result_amount", false);

		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateFrom));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTo));
		$this->db->where('date_hour >=', $fromDateHourStr);
		$this->db->where('date_hour <=', $toDateHourStr);

		$this->db->group_by(array("game_platform_id", "game_type_id", "game_description_id", "date", "hour"));
		$qry = $this->db->get($this->tableName);

		// $this->utils->debug_log($this->db->last_query());
		return $this->getMultipleRow($qry);

		// $qry = $this->db->query("SELECT * FROM $this->tableName
		// 							WHERE date >= '" . $dateFrom . "'
		// 							AND date <= '" . $dateTo . "'
		// 						");
		// return $this->getMultipleRow($qry);
	}

	/**
	 * overview : get first record date time
	 *
	 * @return datetime
	 */
	public function getFirstRecordDateTime() {
		$this->db->order_by('date asc');
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'date');
	}

	/**
	 * overview : get last record date time
	 *
	 * @return datetime
	 */
	public function getLastRecordDateTime() {
		$this->db->order_by('date desc');
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'date');
	}

	/**
	 * overview : get player total bets
	 * @param int		$playerId
	 * @param datetime	$gameRecordStartDate
	 * @param datetime	$gameRecordEndDate
	 * @param array $gameList
	 * @return array
	 */
	public function getPlayerTotalBet($playerId, $gameRecordStartDate, $gameRecordEndDate, $gameList = null) {
		//var_dump($playerName);exit();
		$this->db->select('sum(betting_amount) as currentTotalBet')->from($this->tableName);
		$this->db->where('player_id', $playerId);

		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordStartDate));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordEndDate));
		$this->db->where('date_hour >=', $fromDateHourStr);
		$this->db->where('date_hour <=', $toDateHourStr);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * overview : get player total loss
	 *
	 * @param int		$playerId
	 * @param datetime	$gameRecordStartDate
	 * @param datetime	$gameRecordEndDate
	 * @param array		$gameList
	 * @return array
	 */
	public function getPlayerTotalLoss($playerId, $gameRecordStartDate, $gameRecordEndDate, $gameList = null) {
		//var_dump($playerName);exit();
		$this->db->select('sum(if(betting_amount<0, betting_amount, 0)) as currentTotalLoss', false)->from($this->tableName);
		$this->db->where('player_id', $playerId);

		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordStartDate));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordEndDate));
		$this->db->where('date_hour >=', $fromDateHourStr);
		$this->db->where('date_hour <=', $toDateHourStr);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * overview : get player total win
	 *
	 * @param int		$playerId
	 * @param datetime	$gameRecordStartDate
	 * @param datetime	$gameRecordEndDate
	 * @param array		$gameList
	 * @return array
	 */
	public function getPlayerTotalWin($playerId, $gameRecordStartDate, $gameRecordEndDate, $gameList = null) {
		//var_dump($playerName);exit();
		$this->db->select('sum(if(betting_amount>0, betting_amount, 0)) as currentTotalWin', false)->from($this->tableName);
		$this->db->where('player_id', $playerId);

		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordStartDate));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($gameRecordEndDate));
		$this->db->where('date_hour >=', $fromDateHourStr);
		$this->db->where('date_hour <=', $toDateHourStr);

		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * Used when calculating agency commission, determines the 'percentage' for each game platform and game type
	 * based on bet amount, for each player. This percentage is used when calculating corresponding fees.
	 *
	 * @param  Array $player_ids This agent's players' IDs.
	 * @param  String $start_date Commission calculation start date. SQL Formatted DateTime string.
	 * @param  String $end_date   Commission calculation end date. SQL Formatted DateTime string.
	 * @return Array Percentage by game platform ID. $game_platform_id => $game_type_id => $percentage. Percentage values sum up to 1.
	 */
	public function get_bet_percentage_by_platform_and_type($player_ids, $start_date, $end_date) {
		$this->db->select(array('game_platform_id', 'game_type_id', 'player_id'));
		$this->db->select_sum('betting_amount', 'total_bets')
						->select_sum('result_amount', 'total_results');
		$this->db->from($this->tableName);
		$this->db->where_in('player_id', $player_ids);

		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($start_date));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($end_date));
		$this->db->where('date_hour >=', $fromDateHourStr);
		$this->db->where('date_hour <=', $toDateHourStr);

		$this->db->group_by(array('game_platform_id', 'game_type_id', 'player_id'));

		$query = $this->db->get();
		$rows = $query->result_array();
		$result = array(); # indexed by game_platform_id, game_type_id, player_id
		$totalBet = array(); # indexed by player_id

		# Save rows into data structure
		foreach($rows as $row) {
			if(!array_key_exists($row['game_platform_id'], $result)) {
				$result[$row['game_platform_id']] = array();
			}
			if(!array_key_exists($row['player_id'], $totalBet)) {
				$totalBet[$row['player_id']] = 0;
			}

			// OGP-14162 for bonus bets whose total_bets = 0, we use total_results to calculate its percentage
			$total_bets = empty($row['total_bets']) ? $row['total_results'] : $row['total_bets'];
			$result[$row['game_platform_id']][$row['game_type_id']][$row['player_id']] = $total_bets;
			$totalBet[$row['player_id']] += $total_bets;
		}

		# calculate bet percentage per player per game type
		foreach($result as $game_platform_id => $game_types) {
			foreach($game_types as $game_type_id => $bet_data) {
				foreach($bet_data as $player_id => $bet) {
					if(empty($totalBet[$player_id])) {
						$result[$game_platform_id][$game_type_id][$player_id] = 0;
					} else {
						$result[$game_platform_id][$game_type_id][$player_id] = $bet / $totalBet[$player_id];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * overview : sync
	 *
	 * @param DateTime 	$from
	 * @param DateTime 	$to
	 * @param int		$playerId
	 * @return array
	 */
	public function sync(\DateTime $from, \DateTime $to, $playerId = null, $gamePlatfomrId= null) {

		$fromStr = $from->format('YmdH') . '00';
		$toStr = $to->format('YmdH') . '59';

		// $fromStr = $this->utils->formatDateTimeForMysql($from);
		// $toStr = $this->utils->formatDateTimeForMysql($to);
		$fromDateHourStr = $this->utils->formatDateHourForMysql($from);
		$toDateHourStr = $this->utils->formatDateHourForMysql($to);

		$playerIdSql = null;
		if (!empty($playerId)) {
			$playerIdSql = ' and player_id=' . $this->db->escape($playerId);
		}
		$gamePlatformSQL=null;
		if(!empty($gamePlatformId)){
			//for insert
			$gamePlatformSQL=' and game_platform_id='.intval($gamePlatformId);
		}

		// delete total_player_game_hour
		$this->db->where('date_hour >=', $fromDateHourStr)
			->where('date_hour <=', $toDateHourStr);
		if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
		}
		if(!empty($gamePlatformId)){
			$this->db->where('game_platform_id', $gamePlatformId);
		}
		$this->db->delete('total_player_game_hour');

		$now=$this->utils->getNowForMysql();
		// $this->utils->printLastSQL();
		$params=[$fromStr, $toStr];
		$t=time();
		$sql = <<<EOD
select
player_id, round(sum(betting_amount),4) as betting_amount, round(sum(real_betting_amount), 4) as real_betting_amount,
round(sum(result_amount), 4) as result_amount,
round(sum(win_amount), 4) as win_amount, round(sum(loss_amount), 4) as loss_amount,
hour, `date`, '$now' as updated_at, round(sum(bet_for_cashback), 4) as bet_for_cashback,
substr(date_minute,1,10) as date_hour, game_platform_id, game_type_id, game_description_id,
concat(player_id, '_', game_platform_id, '_', game_type_id, '_', game_description_id, '_',`date`, '_', hour) as uniqueid

from total_player_game_minute
where date_minute >= ?
and date_minute <= ?
{$playerIdSql}
{$gamePlatformSQL}
group by player_id, game_platform_id, game_type_id, game_description_id, substr(date_minute,1,10)
EOD;
		$rows=$this->runRawSelectSQLArray($sql, $params);
		$this->utils->info_log('get rows from total_player_game_minute', count($rows), $params, 'cost', (time()-$t));

		$t=time();
		$cnt=0;
		$limit=500;
		$success=$this->runBatchInsertWithLimit($this->db, 'total_player_game_hour', $rows, $limit, $cnt);
		unset($rows);
		$this->utils->info_log('insert into total_player_game_hour', $cnt, $params, 'cost', (time()-$t));

		if($this->utils->getConfig('enabled_total_player_game_hour_additional')){
			// delete total_player_game_hour_additional
			$this->db->where('date_hour >=', $fromDateHourStr)
				->where('date_hour <=', $toDateHourStr);
			if (!empty($playerId)) {
				$this->db->where('player_id', $playerId);
			}
			if(!empty($gamePlatformId)){
				$this->db->where('game_platform_id', $gamePlatformId);
			}
			$this->runRealDelete('total_player_game_hour_additional');
			$delCnt=$this->db->affected_rows();
			$this->utils->info_log('deleted total_player_game_hour_additional', $delCnt);

			$t=time();
			//insert into additional
			$addSql = <<<EOD
select
player_id, game_platform_id, round(sum(ifnull(rent,0)), 4) as rent, '$now' as updated_at,
substr(date_minute,1,10) as date_hour,
substr(uniqueid, 1, LENGTH(uniqueid)-3) as uniqueid
from total_player_game_minute_additional
where date_minute >= ?
and date_minute <= ?
{$playerIdSql}
{$gamePlatformSQL}
group by substr(uniqueid, 1, LENGTH(uniqueid)-3)
EOD;
			$addRows=$this->runRawSelectSQLArray($addSql, $params);
			$this->utils->info_log('get rows from total_player_game_minute_additional', count($addRows), $params, 'cost', (time()-$t));

			$t=time();
			$addCnt=0;
			$addSucc=$this->runBatchInsertWithLimit($this->db, 'total_player_game_hour_additional', $addRows, $limit, $addCnt);
			unset($addRows);
			$this->utils->info_log('insert into total_player_game_hour_additional', $addCnt, $addSucc, 'cost', (time()-$t));
			if($cnt!=$addCnt || !$addSucc){
				$this->utils->error_log('insert into total_player_game_hour_additional failed', $cnt, $addCnt, $addSucc);
			}
		}

		return $success;

// 		$sql = <<<EOD
// insert into total_player_game_hour(
// player_id, betting_amount, real_betting_amount, result_amount, win_amount, loss_amount,
// hour, `date`, updated_at, bet_for_cashback,
// date_hour, game_platform_id, game_type_id, game_description_id,
// uniqueid
// )

// select
// player_id, round(sum(betting_amount),4) as betting_amount, round(sum(real_betting_amount), 4) as real_betting_amount, round(sum(result_amount), 4) as result_amount,
// round(sum(win_amount), 4) as win_amount, round(sum(loss_amount), 4) as loss_amount,
// hour, `date` as game_date, '$now' as updated_at, round(sum(bet_for_cashback), 4),
// substr(date_minute,1,10), game_platform_id, game_type_id, game_description_id,
// concat(player_id, '_', game_platform_id, '_', game_type_id, '_', game_description_id, '_',`date`, '_', hour)

// from total_player_game_minute
// where date_minute >= ?
// and date_minute <= ?
// {$playerIdSql}
// group by player_id, game_platform_id, game_type_id, game_description_id, substr(date_minute,1,10)
// EOD;

		// $qry = $this->db->query($sql, array($fromStr, $toStr));

		// return $this->db->affected_rows();

	}

	/**
	 * overview : get total bets wins loss by player
	 *
	 * @param mixin		$playerIds, int or array of player id
	 * @param datetime	$dateTimeFrom
	 * @param datetime	$dateTimeTo
	 * @param int		$gamePlatformId
	 * @param int		$gameDescriptionId The game descripttion filter
	 * @param boolean	$filterZeroRealBettingAmount The real_betting_amount more then zero condition.
	 * @return array
	 */
	public function getTotalBetsWinsLossByPlayers(  $playerIds // # 1
													, $dateTimeFrom // # 2
													, $dateTimeTo // # 3
													, $gamePlatformId = null // # 4
													, $gameDescriptionId = null // # 5
													, $filterZeroRealBettingAmount = false // # 6
	) {
		$totalBet = 0;
		$totalWin = 0;
		$totalLoss = 0;

		if (!empty($playerIds)) {

			$this->db->select_sum('betting_amount', 'total_bet')
				->select_sum('loss_amount', 'total_loss')
				->select_sum('win_amount', 'total_win')
				->from('total_player_game_hour')
			;

			if (is_array($playerIds)) {
				$this->db->where_in('player_id', $playerIds);
			} else {
				$this->db->where('player_id', $playerIds);
			}

			if (!empty($gamePlatformId)) {
				if (is_array($gamePlatformId)) {
					$this->db->where_in('game_platform_id', $gamePlatformId);
				} else {
					$this->db->where('game_platform_id', $gamePlatformId);
				}
			}
			if ( ! empty($gameDescriptionId)) {
				if (is_array($gameDescriptionId)) {
					$this->db->where_in('game_description_id', $gameDescriptionId);
				} else {
					$this->db->where('game_description_id', $gameDescriptionId);
				}
			}

			if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
				$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
				$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));

				$this->db->where('date_hour >=', $fromDateHourStr);
				$this->db->where('date_hour <=', $toDateHourStr);
			}

			if( $filterZeroRealBettingAmount ){
				$this->db->where('real_betting_amount >', '0');
			}

			$row = $this->runOneRow();
			$totalBet = $row->total_bet;
			$totalWin = $row->total_win;
			$totalLoss = $row->total_loss;
			// $this->utils->printLastSQL();
		}
		return array($totalBet, $totalWin, $totalLoss);
	}

    /**
     * detail: get total bet date for a certain player and date range
     *
     * @param int $playerId total_player_game_hour player_id field
     * @param string $from total_player_game_hour date
     * @param string $to total_player_game_hour date
     *
     * @return array
     */
    public function getPlayerTotalBetDateByDatetime($playerId, $from, $to){
        $this->db->distinct()
            ->select('date')
            ->from($this->tableName)
            ->where('player_id', $playerId)
            ->where('date >=', $from)
            ->where('date <=', $to);

        return $this->runMultipleRowOneFieldArray('date');
    }

	/**
	 * overview : get player total bets by date time
	 *
	 * @param int		$playerId
	 * @param datetime	$dateTimeFrom
	 * @param datetime	$dateTimeTo
	 * @param int		$gamePlatformId
	 * @return array
	 */
	public function getPlayerTotalBetsWinsLossByDatetime($playerId, $dateTimeFrom, $dateTimeTo, $gamePlatformId = null) {
		return $this->getTotalBetsWinsLossByPlayers(array($playerId), $dateTimeFrom, $dateTimeTo, $gamePlatformId);
	}

	/**
	 * overview : sum amount
	 *
	 * @param  datetime	$datehour
	 * @return array
	 */
	public function sumAmount($datehour) {
		$this->db->select_sum('betting_amount')->select_sum('result_amount')
			->select_sum('loss_amount')->select_sum('win_amount')
			->from($this->tableName)
			->where('date_hour', $datehour);

		$row = $this->runOneRow();
		$bet_amount = 0;
		$result_amount = 0;
		$win_amount = 0;
		$loss_amount = 0;
		if (!empty($row)) {
			$bet_amount = $row->betting_amount;
			$result_amount = $row->result_amount;
			$win_amount = $row->win_amount;
			$loss_amount = $row->loss_amount;
		}
		return array(floatval($bet_amount), floatval($result_amount), floatval($win_amount), floatval($loss_amount));
	}

	/**
	 * overview : get top ten win players
	 *
	 * @param int	$gameDescriptionId
	 * @return array
	 */
	public function getTopTenWinPlayers($gameDescriptionId) {
		$this->load->model(array('player_model', 'game_description_model'));
		$dateFrom = new DateTime('7 days ago');
		$dateTimeFrom = $dateFrom->format('YmdH');
		$dateTo = new DateTime();
		$dateTimeTo = $dateTo->format('YmdH');
		$min_win_amount_for_top10 = $this->utils->getConfig('min_win_amount_for_top10');

		// $min_win_amount = $this->utils->getConfig('min_win_amount_for_newest') ? $this->utils->getConfig('min_win_amount_for_newest') : 900;
		$this->db->select('player_id, game_description_id');
		$this->db->select_sum('win_amount', 'total_win_amount');
		$this->db->from('total_player_game_hour');
		$this->db->join('game_description', 'game_description.id = total_player_game_hour.game_description_id');
		$this->db->join('game_type', 'game_type.id = total_player_game_hour.game_type_id');
		$this->db->group_by(array('player_id', 'game_description_id'));
		$this->db->having('total_win_amount >=', $min_win_amount_for_top10);
		$this->db->order_by('total_win_amount', 'desc');
		$this->db->limit(10);

		# OG-1578 hide unknown game in get_top_ten_win_players
		$this->db->where('game_description.game_code !=', 'unknown');
		$this->db->where('game_type.game_type !=', 'unknown');

		if (!empty($gameDescriptionId)) {
			$this->db->where('game_description_id', $gameDescriptionId);
		}

		if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
			$this->db->where('date_hour >=', $dateTimeFrom);
			$this->db->where('date_hour <=', $dateTimeTo);
		}

		$qry = $this->db->get();

		// 		$sql = <<<EOD
		// SELECT fq.`game_description_id`, MAX(CONCAT(LPAD(fq.total_win_amount,5,'0'),'_',fq.username)) as username FROM (SELECT `player_id` AS username, `game_description_id`, SUM(`win_amount`) AS total_win_amount
		// FROM (`total_player_game_hour`)
		// WHERE win_amount IS NOT NULL
		// AND date_hour >= {$dateTimeFrom}
		// AND date_hour <= {$dateTimeTo}
		// and
		// GROUP BY `player_id`, `game_description_id`)
		//  fq
		// GROUP BY `game_description_id`
		// ORDER BY `total_win_amount` desc
		// EOD;
		// 		$qry = $this->db->query($sql);
		// $cnt = 0;
		$data = array();
		if ($qry && $qry->num_rows() > 0) {
			foreach ($qry->result_array() as $row) {
				$row['total_win_amount'] = floatval($row['total_win_amount']);
				$row['username'] = $this->player_model->getUsernameById($row['player_id']);
				$gameDesc = $this->game_description_model->getGameDescription($row['game_description_id']);
				$row['game_name'] = '';
				$row['game_code'] = '';
				if (!empty($gameDesc)) {
					$row['game_name'] = $this->game_description_model->getGameName($gameDesc);
					$row['game_code'] = $gameDesc->game_code;
				}
				// if ($row['game_code'] != 'unknown') {
				// 	if ($cnt < 10) {
				$data[] = $row;
				// 	} else {
				// 		break;
				// 	}
				// 	$cnt++;
				// }
			}
		}
		return $data;
	}

	/**
	 * overview : get newest ten win players
	 *
	 * @param int	$gameDescriptionId
	 * @return array
	 */
	public function getNewestTenWinPlayers($gameDescriptionId) {
		$min_win_amount = $this->utils->getConfig('min_win_amount_for_newest');

		$this->load->model('player_model');
		$this->db->select('player_id as username,result_amount');
		$this->db->where('result_amount >', $min_win_amount);
		if (!empty($gameDescriptionId)) {
			$this->db->where('game_description_id', $gameDescriptionId);
		}
		// $this->db->where('flag', self::FLAG_GAME);
		$this->db->order_by('date_hour', 'desc');
		$this->db->limit(10);

		$qry = $this->db->get($this->tableName);
		if ($qry && $qry->num_rows() > 0) {
			foreach ($qry->result_array() as $row) {
				$row['username'] = $this->player_model->getUsernameById($row['player_id']);
				$data[] = $row;
			}
			return $data;
		}
	}

	/**
	 * overview : get result by players
	 *
	 * @param int		$playerIds
	 * @param datetime	$dateTimeFrom
	 * @param datetime	$dateTimeTo
	 * @param int		$gamePlatformId
	 * @return int
	 */
	public function getResultByPlayers($playerIds, $dateTimeFrom, $dateTimeTo, $gamePlatformId = null, $gameTypeId = null) {
		$totalResult = 0;

		if (!empty($playerIds)) {

			$this->db->select_sum('result_amount', 'total_result')
				->from('total_player_game_hour')
			;

			if (is_array($playerIds)) {
				$this->db->where_in('player_id', $playerIds);
			} else {
				$this->db->where('player_id', $playerIds);
			}

			if (!empty($gamePlatformId)) {
				if (is_array($gamePlatformId)) {
					$this->db->where_in('game_platform_id', $gamePlatformId);
				} else {
					$this->db->where('game_platform_id', $gamePlatformId);
				}
			}

            if (!empty($gameTypeId)) {
                if (is_array($gameTypeId)) {
                    $this->db->where_in('game_type_id', $gameTypeId);
                } else {
                    $this->db->where('game_type_id', $gameTypeId);
                }
            }

			if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
				$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
				$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));

				$this->db->where('date_hour >=', $fromDateHourStr);
				$this->db->where('date_hour <=', $toDateHourStr);
			}

			$row = $this->runOneRow();
			$totalResult = $row->total_result;
			// $this->utils->printLastSQL();
		}
		return $totalResult;
	}

	/**
	 * overview : total player betting amount
	 *
	 * @param int		$playerId
	 * @param datetime	$dateTimeFrom
	 * @param datetime	$dateTimeTo
	 * @param int		$promoId
	 * @return array
	 */
	public function totalPlayerBettingAmount($playerId, $dateTimeFrom, $dateTimeTo = null, $promoId = null) {

		$playerGames = null;
		if ($promoId) {
			$this->load->model(array('promorules'));
			$playerGames = $this->promorules->getPlayerGames($promoId);
		}

		$this->db->select_sum('betting_amount', 'totalBetAmount')
			->from('total_player_game_hour')
			->where('player_id', $playerId);

		if (empty($dateTimeTo)) {
			$dateTimeTo = $this->utils->getNowForMysql();
		}
		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));

		$this->db->where('date_hour >=', $fromDateHourStr)->where('date_hour <=', $toDateHourStr);

		if ($promoId) {
			// $playerGames = $this->getPlayerGames($promoId);
			if (!empty($playerGames)) {
				$this->db->where_in('game_description_id', $playerGames);
			}
		}

		$qry = $this->db->get();
		$rows = array(array('totalBetAmount' => 0));
		if ($qry->num_rows() > 0) {
			$rows = $qry->result_array();
		}

		return $rows;

	}

	/**
	 * overview : total player betting amount with limit by vip
	 *
	 * @param int		$playerId
	 * @param string	$dateTimeFrom
	 * @param string	$dateTimeTo
	 * @param array		$gameDescIdArr
	 * @return array
	 */
	public function totalPlayerBettingAmountWithLimitByVIP($playerId, $dateTimeFrom, $dateTimeTo = null, $gameDescIdArr = null) {

		// $playerGames = null;
		// if ($promoId) {
		// 	$this->load->model(array('promorules'));
		// 	$playerGames = $this->promorules->getPlayerGames($promoId);
		// }

		$this->db->select_sum('bet_for_cashback', 'totalBetAmount')
			->from('total_player_game_hour')
			->where('player_id', $playerId);

		if (empty($dateTimeTo)) {
			$dateTimeTo = $this->utils->getNowForMysql();
		}
		$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
		$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));

		$this->db->where('date_hour >=', $fromDateHourStr)->where('date_hour <=', $toDateHourStr);

		if (!empty($gameDescIdArr)) {
			$this->db->where_in('game_description_id', $gameDescIdArr);
		}

		$qry = $this->db->get();

		// $this->utils->printLastSQL();

		$betting_amount=0;
		// $rows = array(array('totalBetAmount' => 0));
		if ($qry->num_rows() > 0) {
			// $rows = $qry->result_array();
			$row=$qry->row_array();
			$betting_amount=$row['totalBetAmount'];
		}

		// return $rows;
		return $betting_amount;

		// $rows = array(array('totalBetAmount' => 0));
		// if ($qry->num_rows() > 0) {
		// 	$rows = $qry->result_array();
		// }

		// return $rows;

	}

	/**
	 * overview : get total bets wins loss group by name
	 *
	 * @param int		$playerIds
	 * @param datetime	$dateTimeFrom
	 * @param datetime	$dateTimeTo
	 * @return array
	 */
	public function getTotalBetsWinsLossGroupByGamePlatformByPlayers($playerIds, $dateTimeFrom, $dateTimeTo) {
		$result = array();

		$totalBet = 0;
		$totalWin = 0;
		$totalLoss = 0;

		if (!empty($playerIds)) {

			$this->db->select_sum('betting_amount', 'total_bet')
				->select_sum('loss_amount', 'total_loss')
				->select_sum('win_amount', 'total_win')
				->select('game_platform_id')
				->from('total_player_game_hour')
				->group_by('game_platform_id')
			;

			if (is_array($playerIds)) {
				$this->db->where_in('player_id', $playerIds);
			} else {
				$this->db->where('player_id', $playerIds);
			}

			if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
				$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
				$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));

				$this->db->where('date_hour >=', $fromDateHourStr);
				$this->db->where('date_hour <=', $toDateHourStr);
			}

			$rows = $this->runMultipleRow();
			if (!empty($rows)) {
				foreach ($rows as $row) {
					$totalBet = $row->total_bet;
					$totalWin = $row->total_win;
					$totalLoss = $row->total_loss;
					$result[$row->game_platform_id] = array($totalBet, $totalWin, $totalLoss);
				}
			}
			// $this->utils->printLastSQL();
		}
		return $result;
	}

	/**
	 * overview : get summary
	 *
	 * @param integer $player_id player.playerId
	 * @param array $criteria extra where condition in array.
	 * @return bool
	 */
	public function getSummary($player_id, $criteria = array() ) {

		$this->db->select('game_platform_id id');
		$this->db->select_sum('betting_amount', 'bet_sum');
		$this->db->select('count(betting_amount) bet_count');
		$this->db->select_sum('win_amount', 'gain_sum');
		$this->db->select_sum('(result_amount >= 0)', 'gain_count');
		$this->db->select_sum('loss_amount', 'loss_sum');
		$this->db->select_sum('(result_amount < 0)', 'loss_count');
		// $this->db->select_sum('result_amount', 'gain_loss_sum');
		$this->db->select_sum('win_amount - loss_amount', 'gain_loss_sum');#match calculation on gameReports
		$this->db->select('count(result_amount) gain_loss_count');
		$this->db->from('total_player_game_hour');
		$this->db->where('player_id', $player_id);
		if ( ! empty($criteria) ){
			$this->db->where($criteria);
		}
		$this->db->group_by('game_platform_id');
		$query = $this->db->get();
		// $sql = $this->db->last_query();
	 // 	echo $sql;exit();
		$result = $query->result_array();
		$list = array();
		foreach ($result as $row) {
			$list[$row['id']] = array(
				'bet' => array(
					'sum' => $row['bet_sum'],
					'count' => $row['bet_count'],
					'ave' => $row['bet_count'] ? $row['bet_sum'] / $row['bet_count'] : 0,
				),
				'gain' => array(
					'sum' => $row['gain_sum'],
					'count' => $row['gain_count'],
					'percent' => $row['bet_count'] ? (($row['gain_count'] / $row['bet_count']) * 100) : 0,
					'ave' => $row['gain_count'] ? $row['gain_sum'] / $row['gain_count'] : 0,
				),
				'loss' => array(
					'sum' => $row['loss_sum'],
					'count' => $row['loss_count'],
					'percent' => $row['bet_count'] ? (($row['loss_count'] / $row['bet_count']) * 100) : 0,
					'ave' => $row['loss_count'] ? $row['loss_sum'] / $row['loss_count'] : 0,

				),
				'gain_loss' => array(
					'sum' => $row['gain_loss_sum'],
					'count' => $row['gain_loss_count'],
				),
				'result_percentage' => array(
					'percent' => $row['bet_sum'] ? (($row['gain_loss_sum'] / $row['bet_sum']) * 100) : 0,
				)
			);
		}
		return $list ?: false;
	}

	public function getActivePlayersToday(){

		$this->db->select("*")
				 ->from("total_player_game_hour")
				 ->group_by("player_id");
		$query = $this->db->get();
		return $query->result_array();

	}

	public function getActivePlayersCountByHour($date_hour){

		$query = $this->db->select("player_id")
				 ->from("total_player_game_hour")
				 ->where("date_hour", $date_hour);

		$query->join("player as p", "p.playerId = total_player_game_hour.player_id")->group_by("player_id");

        $query = $query->get()->result_array();
		$countPlayerActive = "0";

		if(!empty($query)&& is_array($query)) {
			foreach ($query as $key) {
				$countPlayerActive += 1;
			}
		}
		return $countPlayerActive;

	}

	/**
	 * Return top n players by bet amount within given date interval
	 * OGP-11382
	 * @param	datestring	$dt_from	From datetime, default today 00:00
	 * @param	datestring	$dt_to		To datetime, default today 23:59:50
	 * @param	int			$count		Number of players to return, default 10
	 * @return	array 		Query result, array of rows
	 */
	public function topPlayersByBetAmountWithinDate($dt_from = null, $dt_to = null, $count = 10) {
		if (empty($dt_from)) { $dt_from = date('c', strtotime('today 00:00:00')); }
		if (empty($dt_to)) { $dt_to = date('c', strtotime('today 23:59:59')); }
		$dh_from = $this->utils->formatDateHourForMysql(new DateTime($dt_from));
		$dh_to = $this->utils->formatDateHourForMysql(new DateTime($dt_to));

		$this->db->select([ 'P.username', 'SUM(betting_amount) as total_bets'])
			->from("{$this->tableName} AS G")
			->join('player AS P', 'G.player_id = P.playerId', 'left')
			->where("date_hour BETWEEN '{$dh_from}' AND '{$dh_to}'", null, false)
			->group_by('G.player_id')
			->order_by('total_bets', 'desc')
			->limit($count)
		;

		$res = $this->runMultipleRowArray();

		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $res;
	}

	/**
	 * Return top n games by bet amount within given date interval
	 * OGP-11382
	 * @param	datestring	$dt_from	From datetime, default today 00:00
	 * @param	datestring	$dt_to		To datetime, default today 23:59:59
	 * @param	int			$count		Number of players to return, default 10
	 * @return	array 		Query result, array of rows
	 */
	public function topGamesByBetAmountWithinDate($dt_from = null, $dt_to = null, $count = 10) {
		if (empty($dt_from)) { $dt_from = date('c', strtotime('today 00:00:00')); }
		if (empty($dt_to)) { $dt_to = date('c', strtotime('today 23:59:59')); }
		$dh_from = $this->utils->formatDateHourForMysql(new DateTime($dt_from));
		$dh_to = $this->utils->formatDateHourForMysql(new DateTime($dt_to));

		$this->db->select([ 'D.game_name', 'D.english_name', 'T.game_type_lang', 'SUM(betting_amount) as total_bets'])
			->from("{$this->tableName} AS G")
			->join('game_description AS D', 'G.game_description_id = D.id', 'left')
			->join('game_type AS T', 'G.game_type_id = T.id', 'left')
			->where("date_hour BETWEEN '{$dh_from}' AND '{$dh_to}'", null, false)
			->group_by('G.game_description_id')
			->order_by('total_bets', 'desc')
			->limit($count)
		;

		$res = $this->runMultipleRowArray();

		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		// Translate i18n game name
		if (is_array($res)) {
			foreach ($res as &$row) {
				$candidates = $row;
				unset($candidates['betting_amount']);
				foreach ($candidates as $cand) {
					if (!empty($cand)) {
						$game_lang_json = $cand;
						break;
					}
				}

				$row['game_lang'] = $game_lang_json;
			}
		}

		return $res;
	}

	/**
	 * getTotalAmountFromHourlyReportByPlayerAndDateTime
	 * @param  int $playerId
	 * @param  string $dateTimeFrom
	 * @param  string $dateTimeTo
	 * @param  mixix $gamePlatformId int or array
	 * @param array|integer gameDescriptionId OR gameDescriptionId list(array).
	 * @param boolean $filterZeroRealBettingAmount If true then add the conditiion,"real_betting_amount > 0", else ignore.
	 * @param string $total_player_game_table The query data-table, total_player_game_XXX, ex: total_player_game_day, total_player_game_hour, total_player_game_minute, total_player_game_month and total_player_game_year.
	 * @param string $where_date_field The where condition field. Depend on $total_player_game_table param and need to review/adjust $dateTimeFrom and $dateTimeTo format.
	 * - if $total_player_game_table = total_player_game_year, recommand be year.
	 * - if $total_player_game_table = total_player_game_month, recommand be month.
	 * - if $total_player_game_table = total_player_game_day, recommand be date.
	 * - if $total_player_game_table = total_player_game_hour, recommand be date_hour.
	 * - if $total_player_game_table = total_player_game_minute, recommand be date_minute.
	 *
	 * @return array [$totalBet, $totalResult, $totalWin, $totalLoss]
	 */
	public function getTotalAmountFromHourlyReportByPlayerAndDateTime(	$playerId // # 1
																		, $dateTimeFrom// # 2
																		, $dateTimeTo// # 3
																		, $gamePlatformId = null// # 4
																		, $gameDescriptionId = null// # 5
																		, $filterZeroRealBettingAmount = false// # 6
																		, $total_player_game_table='total_player_game_hour' // # 7
																		, $where_date_field = 'date_hour' // # 8
	) {
		$totalBet = 0;
		$totalWin = 0;
		$totalLoss = 0;
		$totalResult = 0;

		if (!empty($playerId)) {

			$this->db->select_sum('betting_amount', 'total_bet')
				->select_sum('loss_amount', 'total_loss')
				->select_sum('win_amount', 'total_win')
				->select_sum('result_amount', 'total_result')
				->from($total_player_game_table)
			;

			$this->db->where('player_id', $playerId);

			if (!empty($gamePlatformId)) {
				if (is_array($gamePlatformId)) {
					$this->db->where_in('game_platform_id', $gamePlatformId);
				} else {
					$this->db->where('game_platform_id', $gamePlatformId);
				}
			}
			if (!empty($gameDescriptionId)) {
				if (is_array($gameDescriptionId)) {
					$this->db->where_in('game_description_id', $gameDescriptionId);
				} else {
					$this->db->where('game_description_id', $gameDescriptionId);
				}
			}

			if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
				if($total_player_game_table == 'total_player_game_hour'){ // keep original
					$fromDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
					$toDateHourStr = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
				}else{
					$fromDateHourStr = $dateTimeFrom;
					$toDateHourStr = $dateTimeTo;
				}


				$this->db->where($where_date_field. ' >=', $fromDateHourStr);
				$this->db->where($where_date_field. ' <=', $toDateHourStr);
			}
			if( $filterZeroRealBettingAmount ){
				$this->db->where('real_betting_amount >', '0');
			}

			$row = $this->runOneRowArray();
			$totalBet = $row['total_bet'];
			$totalResult = $row['total_result'];
			$totalWin = $row['total_win'];
			$totalLoss=$row['total_loss'];
			// $this->utils->printLastSQL();
		}
		return array($totalBet, $totalResult, $totalWin, $totalLoss);
	}

	public function getRecordByDateHour($dateHour) {
        $this->db->select("SUM(betting_amount) betting_amount, date_hour, player_id, game_platform_id");
        $this->db->from($this->tableName);
		$this->db->where("date_hour",$dateHour);
		$this->db->group_by('player_id, game_platform_id');
        return $this->runMultipleRowArray();
	}

	/**
	 * detail: get player betting list by dates
	 *
	 * @param int $playerId player_id field
	 * @param string $periodFrom
	 * @param string $periodTo
	 *
	 * @return array
	 */
	public function getPlayerBetListByDates($playerId, $periodFrom, $periodTo, $minAmount = null, $maxAmount = null, $gamePlatformId = null) {
		$this->db->select('SUM(betting_amount) betting_amount, id, player_id, date, game_platform_id')->from($this->tableName)
			// ->where('transaction_type', self::DEPOSIT)
			// ->where('to_type', self::PLAYER)
			->where('player_id', $playerId)
			->where('date >=', $periodFrom)
			->where('date <=', $periodTo);

		if (!empty($gamePlatformId)) {
			if (is_array($gamePlatformId)) {
				$this->db->where_in('game_platform_id', $gamePlatformId);
			} else {
				$this->db->where('game_platform_id', $gamePlatformId);
			}
		}

		if (!empty($minAmount)) {
			$this->db->where('betting_amount >=', $minAmount);
		}

		if (!empty($maxAmount)) {
			$this->db->where('betting_amount <=', $maxAmount);
		}

		$this->db->group_by('date');
		$this->db->order_by('date', 'asc');

		return $this->runMultipleRowArray();
	}

	/**
	 * detail: get player last bet in game type
	 *
	 * @param int $playerId player_id field
	 * @param string $periodFrom
	 * @param string $periodTo
	 *
	 * @return array
	 */
	public function getPlayerLastBetInGameType($playerId, $gameTypeCode) {
		$this->db->select('`date`')->from($this->tableName)
            ->join('game_type', 'game_type.id = total_player_game_hour.game_type_id')
			->where('player_id', $playerId);

            if(is_array($gameTypeCode)){
                $this->db->where_in('game_type_code', $gameTypeCode);
            }else{
                $this->db->where('game_type_code', $gameTypeCode);
            }


		$this->db->order_by('date', 'desc');

		return $this->runOneRowArray();;
	}

	public function getDistinctTotalActiveMembers($periodFrom, $periodTo){
		$this->db->select('COUNT(distinct total_player_game_hour.player_id) cnt');
		$this->db->from($this->tableName);
		$this->db->where('total_player_game_hour.date >=', $periodFrom);
		$this->db->where('total_player_game_hour.date <=', $periodTo);

		$cnt = $this->runOneRowOneField('cnt');
		return $cnt;
	}

    public function getTopGamesByPlayers($date, $limit = 20, $tags = []) {
        $select = [
            'gr.game_platform_id',
            'gt.game_type_code',
            'gd.external_game_id',
            'gd.english_name AS game_name',
            'count(DISTINCT gr.player_id) AS total_players',
            'sum(gr.betting_amount) AS total_bets',
        ];

        $this->db->select($this->selectData($select));
        $this->db->from("{$this->tableName} AS gr");
        $this->db->join('game_description AS gd', 'gr.game_description_id = gd.id', 'LEFT');
        $this->db->join('game_type AS gt', 'gd.game_type_id = gt.id', 'LEFT');
        $this->db->join('external_system AS e', 'e.id = gr.game_platform_id', 'LEFT');
        $this->db->where('gr.date', $date);
        $this->db->where('e.flag_show_in_site', self::DB_TRUE);
        $this->db->where('e.status', self::DB_TRUE);

        $this->utils->debug_log(__METHOD__, 'tags', $tags);

        if (!empty($tags) && is_array($tags)) {
            $this->db->join('game_tag_list', 'gd.id = game_tag_list.game_description_id', 'LEFT');
            $this->db->join('game_tags', 'game_tag_list.tag_id = game_tags.id', 'LEFT');
            $this->db->where_in('game_tags.tag_code', $tags);
        }

        $this->db->group_by('gd.external_game_id');
        $this->db->order_by('total_players DESC, total_bets DESC');
        $this->db->limit($limit);
        return $this->runMultipleRowArray();
    }

    public function getTopGamesByPlayerBetAndCount() {
        $select = [
            'gr.game_description_id',
            'gr.game_platform_id',
            'gd.game_type_id',
            'count(DISTINCT gr.player_id) AS total_players',
            'sum(gr.betting_amount) AS total_bets',
        ];

        $this->db->select($this->selectData($select));
        $this->db->from("{$this->tableName} AS gr");
        $this->db->join('game_description AS gd', 'gr.game_description_id = gd.id', 'LEFT');
        $this->db->where('gr.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $this->db->group_by('gr.game_description_id');
        $this->db->order_by('total_players DESC, total_bets DESC');
        return $this->runMultipleRowArray();
    }

	
	public function sumGameLogsByPlayerPerGameType($playerId, $start_date = null, $end_date = null) {
		$sql = <<<EOD
SELECT 
SUM(tpgh.betting_amount) AS total_betting_amount,
gt.game_type_code
FROM 
total_player_game_hour AS tpgh
LEFT JOIN 
game_type AS gt ON gt.id = tpgh.game_type_id
WHERE 
tpgh.player_id = ?
AND gt.game_type_code <> ?
AND tpgh.date >= ? AND tpgh.date <= ?
GROUP BY 
gt.game_type_code
EOD;
	
		$params = [$playerId, 'unknown', $start_date, $end_date];
	
		$query = $this->db->query($sql, $params);
		return $query->result_array();
	}
	
}

///END OF FILE///////
