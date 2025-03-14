<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 * * Create Point Transaction
 * * Load template
 * * Direct payment
 * * Redirect to live chat
 * * Admin service
 * * Payment transaction
 *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Point_transactions extends BaseModel {

	protected $tableName = 'point_transactions';
	protected $idField = 'id';

	# TRANSACTION_TYPE
	const DEPOSIT_POINT = 1;
	const BET_POINT = 2;
	const WIN_POINT = 3;
	const LOSS_POINT = 4;
	const DEDUCT_POINT = 5;
	const MANUAL_ADD_POINTS = 6;
	const MANUAL_DEDUCT_POINTS = 7;
	const DEDUCT_BET_POINT = 8;

	# FROM/TO
	const ADMIN = 1;
	const PLAYER = 2;
	const AFFILIATE = 3;

	# STATUS
	const APPROVED = 1;
	const DECLINED = 2;

	# FLAG
	const MANUAL = 1;
	const PROGRAM = 2;

	# FROM/TO IS FOR SYSTEM (e.g. CRONJOBS)
	const SYSTEM_ID = 1;

	# ADD/DEDUCT (COL ACTION IN DB)
	const ADD = 1;
	const DEDUCT = 2;

	/**
	 * overview : Point_transactions constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * overview : create point transaction
	 *
	 * @param $fromId
	 * @param $playerId
	 * @param $point
	 * @param $beforePointBalance
	 * @param $newPointBalance
	 * @param null $saleOrderId
	 * @param null $playerPromoId
	 * @param null $transaction_type
	 * @param null $additionNote
	 * @param null $promo_category
	 * @param null $action
	 * @return bool
	 */
	public function createPointTransaction(
		$fromId,
		$playerId,
		$point,
		$beforePointBalance,
		$newPointBalance,
		$saleOrderId = null,
		$playerPromoId = null,
		$transaction_type = null,
		$additionNote = null,
		$promo_category = null,
		$action = self::ADD,
		$extra = null
	) {

		if ($playerId/* && $point*/) {

			//allow only if points = 0 if bet to points or deposit to points
			if (!$point && $transaction_type != self::BET_POINT && $transaction_type != self::DEPOSIT_POINT) {
				return false;
			}

			$this->load->model(array('wallet_model'));
			// $totalBeforePointBalance = $this->wallet_model->getTotalBalance($playerId);
			if($transaction_type != self::DEDUCT_POINT && 
			$transaction_type != self::MANUAL_DEDUCT_POINTS  && 
			$transaction_type != self::DEDUCT_BET_POINT) {
				$note = $fromId . ' add point ' . $point . ' to ' . $playerId;
			}elseif($transaction_type == self::DEDUCT_POINT) {
                $note = 'Deducted to points. deducted ' . $point . ' ' . $this->utils->pluralize('point', 'points', $point) . '. Running ' . $this->utils->pluralize('point', 'points', $newPointBalance) . ' ' . $newPointBalance;
            } else {
				$note = $fromId . ' deducted point ' . $point . ' from ' . $playerId;
			}

			if (!empty($playerPromoId)) {
				$note = $note . ' because player promotion: ' . $playerPromoId;
			}

			if (!empty($additionNote)) {
				$note = $note . ' ' . $additionNote;
				if($transaction_type == self::BET_POINT || 
				$transaction_type == self::DEPOSIT_POINT) {
					$note = $additionNote;
				} 
			}

			if (empty($fromId)) {
				$fromId = self::SYSTEM_ID;
				$fromType = self::SYSTEM_ID; //cronjob
				$flag = self::PROGRAM;
			} else {
				$fromType = self::ADMIN; //admin user
				$flag = self::MANUAL;
			}

			$transactionDetails = array(
				'point' => $point,
				'transaction_type' => $transaction_type,
				'from_id' => $fromId,
				'from_type' => $fromType,
				'to_id' => $playerId,
				'to_type' => self::PLAYER,
				'note' => $note,
				'before_balance' => $beforePointBalance,
				'after_balance' => ($transaction_type == self::DEDUCT_POINT || $transaction_type == self::MANUAL_DEDUCT_POINTS || $transaction_type == self::DEDUCT_BET_POINT) ? ($beforePointBalance - $point): ($beforePointBalance + $point),
				'status' => self::APPROVED,
				'flag' => $flag, //manual or program
				'created_at' => $this->utils->getNowForMysql(),
				'updated_at' => $this->utils->getNowForMysql(),
				'order_id' => $saleOrderId,
				'player_promo_id' => $playerPromoId,
				'total_before_balance' => $newPointBalance,
				'promo_category' => $promo_category,
				'action' => $action,
			);

			
			if(!empty($extra) && is_array($extra)){
				$transactionDetails['external_transaction_id'] = isset($extra['external_transaction_id'])?$extra['external_transaction_id']:null;
				$transactionDetails['current_rate'] = isset($extra['current_rate'])?$extra['current_rate']:null;
				$transactionDetails['source_amount'] = isset($extra['source_amount'])?$extra['source_amount']:null;				
				$transactionDetails['sub_wallet_id'] = isset($extra['sub_wallet_id'])?$extra['sub_wallet_id']:null;
				$transactionDetails['date_hour'] = isset($extra['date_hour'])?$extra['date_hour']:null;
				$transactionDetails['date_within'] = isset($extra['date_within'])?$extra['date_within']:date('Y-m-d');				
				$transactionDetails['date_hour'] = isset($extra['date_hour'])?$extra['date_hour']:null;			

				$transactionDetails['calculated_points'] = isset($extra['calculated_points'])?$extra['calculated_points']:$point;
				$transactionDetails['points_limit_type'] = isset($extra['points_limit_type'])?$extra['points_limit_type']:null;				
				$transactionDetails['points_limit'] = isset($extra['points_limit'])?$extra['points_limit']:0;
				$transactionDetails['forfieted_points'] = isset($extra['forfieted_points'])?$extra['forfieted_points']:0;				
				$transactionDetails['vip_level_id'] = isset($extra['vip_level_id'])?$extra['vip_level_id']:null;
				$transactionDetails['vip_group_name'] = isset($extra['vip_group_name'])?$extra['vip_group_name']:null;				
				$transactionDetails['vip_level_name'] = isset($extra['vip_level_name'])?$extra['vip_level_name']:null;
			}

			$rtn_id = $this->insertRow($transactionDetails);
			return $rtn_id;
		}
		return false;
	}

	/**
	 * overview : get all player points
	 *
	 * @param int		$playerId
	 * @param int		$limit
	 * @param int		$offset
	 * @param string	$search
	 * @param bool|false $isCount
	 * @return null
	 */
	public function pointsHistory($playerId, $limit, $offset, $search, $isCount = false) {

		if ($isCount) {
			$this->db->select('count(pt.id) as cnt');
		} else {
			$this->db->select('pt.id,
				pt.created_at,
				pt.point, pt.note,
				pt.before_balance,
				pt.after_balance,
				pt.payment_account_id,
				pa.payment_account_name,
				es.system_code,
				pt.sub_wallet_id,
				pt.transaction_type,
				pt.flag,
                pt.status,
                pt.action,
                pcs.promoName'
			);
		}

		if (isset($search['from'], $search['to'])) {
			$this->db->where("pt.created_at BETWEEN '" . $search['from'] . "' AND '" . $search['to'] . "'");

		}

		if (isset($limit, $offset)) {
			$this->db->limit($limit, $offset);
		}

		$this->db->from('point_transactions pt');
		$this->db->join('external_system es', 'es.id = pt.sub_wallet_id', 'left');
		$this->db->join('payment_account pa', 'pa.id = pt.payment_account_id', 'left');
		$this->db->join('playerpromo pp', 'pp.playerpromoId = pt.player_promo_id', 'left');
		$this->db->join('promocmssetting pcs', 'pcs.promoCmsSettingId = pp.promoCmsSettingId', 'left');
		$this->db->order_by('pt.created_at', 'desc');
		$query = $this->db->get();

		if ($isCount) {
			return $this->getOneRowOneField($query, 'cnt');

		} else {

			return $query->result_array();

		}

	}

	/**
	 * over view get player point total
	 * @param  [type] $playerId [description]
	 * @return [type] float     [description]
	 */
	public function pointTotal($playerId) {
		$this->db->select('pt.id,
			pt.created_at,
			pt.point, pt.note,
			pt.before_balance,
			pt.after_balance,
			pt.payment_account_id,
			pt.sub_wallet_id,
			pt.transaction_type,
			pt.flag,
	        pt.status,
	        pt.action'
		);

		$this->db->limit(0, 1);
		$this->db->from('point_transactions pt');
		$this->db->order_by('pt.created_at', 'desc');
		$query = $this->db->get();
		return $this->getOneRowOneField($query, 'after_balance');
	}

	public function getShoppingPointExpirationAt(){

		$expiration_setting = [];
        $this->load->model(array('operatorglobalsettings'));
        if ($this->operatorglobalsettings->existsSetting('shopping_point_expiration_setting')) {
            $expiration_setting = $this->operatorglobalsettings->getSettingJson('shopping_point_expiration_setting');
        }

		if(!empty($expiration_setting)) {

			$now = $this->utils->getNowForMysql();
			// var_dump($now);
			$intervaltype = $expiration_setting['intervaltype'];
			switch ($intervaltype) {
				case 'yearly':
					# yearly_calendar, yearly_time
					$yearly_date = date('Y-m-d', strtotime($expiration_setting['yearly_calendar']));
                    $yearly_time = date('H:i:00', strtotime($expiration_setting['yearly_time']));
					$exp_date = date('Y-m-d H:i:s', strtotime("$yearly_date $yearly_time"));
					if ($now < $exp_date) {
						$exp_date = date('Y-m-d H:i:s', strtotime("$exp_date -1 year"));

					}
					$expiration_at = $exp_date;
					break;

				case 'monthly':
					# monthly_calendar, monthly_time
					$monthly_date = $expiration_setting['monthly_calendar'];
					$monthly_time = date('H:i:00', strtotime($expiration_setting['monthly_time']));
					$current_date = date('Y-m-d H:i:s', strtotime($now));

					if($monthly_date == 'last'){

						$exp_date = date('Y-m-t H:i:s', strtotime($monthly_time));
						if($current_date < $exp_date) {
							$expiration_at = date("Y-m-t $monthly_time", strtotime("-1 month"));
						} else {
							$expiration_at = $exp_date;
						}
					} else {

						$exp_date = date("Y-m-$monthly_date H:i:s", strtotime("$monthly_time"));
						if ($current_date < $exp_date) {
							$expiration_at = date('Y-m-d H:i:s', strtotime("$exp_date -1 month"));
						} else {
							$expiration_at = $exp_date;
						}
					}
					break;

				case 'weekly':
					# weekly_date , weekly_time
					$weekly_date = date('Y-m-d', strtotime($expiration_setting['weekly_date']));
					$weekly_time = date('H:i:00', strtotime($expiration_setting['weekly_time']));
					$exp_date = date('Y-m-d H:i:s', strtotime("$weekly_date $weekly_time"));
					$current_date = date('Y-m-d H:i:s', strtotime($now));
					if($current_date < $exp_date) {
						$expiration_at = date('Y-m-d H:i:s', strtotime("$exp_date -1 week"));
					} else {
						$expiration_at = $exp_date;
					}
					break;

				case 'daily':
					# daily_time
					$exp_time = date('Y-m-d H:i:00', strtotime($expiration_setting['daily_time']));
					$current_time = date('Y-m-d H:i:s', strtotime($now));
					if($current_time < $exp_time){

						$expiration_at = date('Y-m-d H:i:00', strtotime($exp_time.' -1 day'));

					} else {

						$expiration_at = $exp_time;
					}
					break;

				default:
					$expiration_at = false;
					break;
			}
		} else {
			$expiration_at =  false;
		}
		// var_dump($expiration_at);
		// var_dump($expiration_setting);
		return $expiration_at;
	}

	public function getPlayerTotalPoints($playerId,  $expiration_at = false) {
		$this->db->select('
				pt.id,
				sum(pt.point) as points,
				pt.transaction_type,
				pt.action,
				pt.to_id
			');

		$this->db->from($this->tableName . ' as pt');
		$this->db->where('pt.transaction_type !=', self::DEDUCT_BET_POINT);
		$this->db->where('pt.transaction_type !=', self::DEDUCT_POINT);
		$this->db->where('pt.transaction_type !=', self::MANUAL_DEDUCT_POINTS);
		$playerId ? $this->db->where('pt.to_id', $playerId) : "";
		if($expiration_at) {
			$this->db->where('pt.created_at >', $expiration_at);
		}
		$this->db->group_by('pt.transaction_type');
		$query = $this->db->get();
		// var_dump($this->getMultipleRowArray($query));exit();

		// $totalDeductPts = $this->getPlayerTotalDeductedPoints($playerId)['points'];
		// var_dump($totalDeductPts);exit();
		return $this->getMultipleRowArray($query);
	}

	public function getPlayerTotalDeductedPoints($playerId, $expiration_at = false) {
		$this->db->select('
				sum(pt.point) as points,
			');

		$this->db->from($this->tableName . ' as pt');
		$this->db->group_by('pt.transaction_type');		
		$this->db->where_in('pt.transaction_type', [self::DEDUCT_POINT,self::MANUAL_DEDUCT_POINTS,self::DEDUCT_BET_POINT]);
        if ($expiration_at) {
            $this->db->where('pt.created_at >', $expiration_at);
        }
		$playerId ? $this->db->where('pt.to_id', $playerId) : "";
		$query = $this->db->get();
		$res = $this->getOneRowArray($query);
		// var_dump($this->db->last_query());exit();
		return $res;
	}

	public function getPlayerAvailablePoints($playerId) {
		$expiration_at = $this->getShoppingPointExpirationAt();
		$totalPoints = $this->getPlayerTotalPoints($playerId, $expiration_at);
		$playerTotalDeductedPoints = 0;
		$playerTotalPoints = 0;
		if (!empty($totalPoints)) {
			$playerTotalPoints = array_sum(array_column($totalPoints, 'points'));
		}
		$deductedPointsDetail = $this->getPlayerTotalDeductedPoints($playerId, $expiration_at);
		if (!empty($deductedPointsDetail) && key_exists('points', $deductedPointsDetail)) {
			$playerTotalDeductedPoints = $deductedPointsDetail['points'];
		}
		$remainingPoints = $playerTotalPoints - $playerTotalDeductedPoints;

		//deduct frozen points
		/*$this->load->model(['player_points']);
		$frozen = $this->player_points->getFozenPlayerPoints($playerId);

		$remainingPoints = $remainingPoints-$frozen;*/

		return $remainingPoints;
	}
	
	//function for getting points awarded so far for bet
	public function getTotalPointsTransactionsByExternalTransactionId($externalTransactionId, $playerId, $gamePlatformId) {
		$query = $this->db->query("SELECT SUM(source_amount) as total_bet_amount, 			
			external_transaction_id, 			
			sub_wallet_id as game_platform_id,
			sub_wallet_id  
			FROM {$this->tableName}		
			WHERE external_transaction_id = ?
			AND (transaction_type = ? OR transaction_type = ?)
			AND to_id = ?
			AND sub_wallet_id = ?
			GROUP BY external_transaction_id;", [$externalTransactionId, Point_transactions::BET_POINT, 
			Point_transactions::DEDUCT_BET_POINT, $playerId, $gamePlatformId]);

		$result = $query->row_array();
		$this->utils->debug_log('getTotalPointsTransactionsByExternalTransactionId',$this->db->last_query());
		if (empty($result)) {
			return null;
		} else {
			return $result;
		}
	}

	public function getPointsTransactionsByDatehour($from, $to) {
		$params = [Point_transactions::BET_POINT,
		Point_transactions::DEDUCT_BET_POINT,$from, $to, Point_transactions::BET_POINT, 
		Point_transactions::DEDUCT_BET_POINT];

		$query = $this->db->query("SELECT SUM(source_amount) as total_bet_amount, 			
			external_transaction_id, to_id  as player_id, sub_wallet_id as game_platform_id, date_hour,
			SUM(IF(transaction_type=?,`point`,0)) as total_added_points,
			SUM(IF(transaction_type=?,`point`,0)) as total_deducted_points,
			current_rate
			FROM {$this->tableName}		
			WHERE date_hour >= ? and date_hour <= ?
			AND (transaction_type = ? OR transaction_type = ?)
			GROUP BY external_transaction_id;", $params);

		$result = $query->result_array();
		$this->utils->debug_log('getPointsTransactionsGroupedByDatehour',$this->db->last_query());
		if (empty($result)) {
			return [];
		} else {
			return $result;
		}
	}

	public function syncPlayerPoints(\DateTime $from, \DateTime $to, $pointsTransactionsReportHours = null, $playerId = null) {
		if(empty($pointsTransactionsReportHours)){
			return true;
		}

		$this->load->library(array('authentication'));

		$fromStr = $from->format('Y-m-d H:i') . ':00';
		$toStr = $to->format('Y-m-d H:i') . ':59';
		$fromDateHourStr = $this->utils->formatDateHourForMysql($from);
		$toDateHourStr = $this->utils->formatDateHourForMysql($to);

		$totalAddedPoints = 0;
		$totalDeductedPoints = 0;

		$this->utils->debug_log('========= START syncPlayerPoints ============================',$fromStr,$toStr,$fromDateHourStr,$toDateHourStr);
		
		//for each points transactions report hour inserted check if existing points_transactions updated
		foreach($pointsTransactionsReportHours as $row){

			//initialize reusable variables
			$playerId = $row['player_id'];

			//process limit
			$playerVipInfo=$this->CI->group_level->getPlayerGroupLevelLimitInfo($playerId);			

			$pointLimit = $playerVipInfo['points_limit'];
			$pointLimitType = $playerVipInfo['points_limit_type'];

			$gamePlatformId = $row['game_platform_id'];			
			$reportBettingAmount = (float)$row['betting_amount'];
			$externalTransactionId = $row['uniqueid'];
			$dateHour=$row['date_hour'];
			$dateWithin=$row['date'];
			$loggedAdminUserId = method_exists($this->authentication, 'getUserId') ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;

			$betToPointsRate = floatval($row['bet_points_rate']);
			if($betToPointsRate<=0){
				//skip if bet conversion rate is 0
				continue;
			}
			
			//get betAmount awarded so far,
			$pointsAwardedSoFar = $this->getTotalPointsTransactionsByExternalTransactionId($externalTransactionId, 										
										$playerId,
										$gamePlatformId);
			
			$totalAwardedSofar = isset($pointsAwardedSoFar['total_bet_amount'])?floatval($pointsAwardedSoFar['total_bet_amount']):0;

			$amount = $this->utils->truncateAmountDecimal($reportBettingAmount - $totalAwardedSofar, 4);								

			$this->utils->debug_log('========= createPointTransaction getTotalPointsTransactionsByExternalTransactionId ============================', 
			'pointsAwardedSoFar',$pointsAwardedSoFar, 
			'totalAwardedSofar', $totalAwardedSofar, 
			'reportBettingAmount', $reportBettingAmount, 
			'amount', $amount);

			//process points
			$success = true;
			if($amount==0){
				//skip no changes in the amount
				continue;
			}


			if($amount > 0){
				//old report increased, need to add more using the current rate for points
				//var_dump(['ADD',$pointsAwardedSoFar,$row]);
				$amountToAdd = abs($amount);								

				$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_POINTS_BALANCE, $playerId, function ()
				use ($row, $playerId, $amountToAdd, $loggedAdminUserId, $betToPointsRate, $externalTransactionId, $gamePlatformId, $totalAwardedSofar,$amount,$dateHour,&$totalAddedPoints,$dateWithin,$pointLimit,$pointLimitType,$playerVipInfo) {

					$beforePointBalance = $this->utils->truncateAmountDecimal($this->point_transactions->getPlayerAvailablePoints($playerId),4);
					$point = $this->utils->truncateAmountDecimal($amountToAdd * $betToPointsRate / 100, 4);		

					$getPointsToAddWithinLimit = $this->getPointsToAddWithinLimit($playerId, $pointLimit, $pointLimitType, $point, $dateWithin);
					$this->utils->debug_log('========= getPointsToAddWithinLimit response',$getPointsToAddWithinLimit);
					
					$point = $getPointsToAddWithinLimit['points_allowed_to_add'];
					$reason = 'Bet to points. '. $getPointsToAddWithinLimit['remarks'];
					
					$newPointBalance = $this->utils->truncateAmountDecimal($beforePointBalance + $point, 4);

					$extra['date_hour']=$dateHour;
					$extra['external_transaction_id']=$externalTransactionId;
					$extra['source_amount']=$amountToAdd;
					$extra['current_rate']=$betToPointsRate;
					$extra['sub_wallet_id']=$gamePlatformId;
					$extra['date_within']=$dateWithin;
					$extra['calculated_points']=$getPointsToAddWithinLimit['points_wanted_to_add'];
					$extra['points_limit_type']=$pointLimitType;
					$extra['points_limit']=$pointLimit;
					$extra['forfieted_points']=$getPointsToAddWithinLimit['forfieted_points'];
					$extra['vip_level_id']=isset($playerVipInfo['vip_level_id'])?$playerVipInfo['vip_level_id']:null;
					$extra['vip_group_name']=isset($playerVipInfo['vip_level_name'])?$playerVipInfo['vip_level_name']:null;
					$extra['vip_level_name']=isset($playerVipInfo['vip_group_name'])?$playerVipInfo['vip_group_name']:null;
					
					$this->utils->debug_log('========= createPointTransaction add points ============================', 'amountToDeduct',$amountToAdd, 'extra', $extra, 'newPointBalance', $newPointBalance, 'point', $point, 'totalAwardedSofar', $totalAwardedSofar);

					$success = $this->point_transactions->createPointTransaction($loggedAdminUserId, $playerId, $point, 
					$beforePointBalance, $newPointBalance, null, null, Point_transactions::BET_POINT, $reason, null, Point_transactions::ADD, $extra);
					
					$this->player_model->updatePlayerPointBalance($playerId, $newPointBalance);
					
					if(!$success){
						return false;
					}
					$totalAddedPoints+=$point;
					return true;
				});

				if(!$success){
					continue;
				}

			}elseif($amount < 0){
				//var_dump(['DEDUCT',$row]);
				//old report decreases, need to deduct more using the current rate for points
				$amountToDeduct = abs($amount);	
				
				$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_POINTS_BALANCE, $playerId, function ()
				use ($playerId, $amountToDeduct, $loggedAdminUserId, $betToPointsRate, $externalTransactionId, $gamePlatformId, $totalAwardedSofar,$amount,$dateHour,&$totalDeductedPoints,$dateWithin) {
					$beforePointBalance = $this->utils->truncateAmountDecimal($this->point_transactions->getPlayerAvailablePoints($playerId),4);
					
					$point = $this->utils->truncateAmountDecimal($amountToDeduct * $betToPointsRate / 100, 4);					

					if($point==0){
						return true;
					}
					$newPointBalance = $this->utils->truncateAmountDecimal($beforePointBalance - $point, 4);

					$reason = 'Points deducted from bet.';

					$extra['date_hour']=$dateHour;
					$extra['external_transaction_id']=$externalTransactionId;
					$extra['source_amount']=$amountToDeduct * -1;
					$extra['current_rate']=$betToPointsRate;
					$extra['sub_wallet_id']=$gamePlatformId;
					$extra['date_within']=$dateWithin;

					$this->utils->debug_log('========= createPointTransaction deduct points ============================', 'amountToDeduct',$amountToDeduct, 'extra', $extra, 'newPointBalance', $newPointBalance, 'point', $point, 'totalAwardedSofar', $totalAwardedSofar);

					$success = $this->point_transactions->createPointTransaction($loggedAdminUserId, $playerId, $point, 
					$beforePointBalance, $newPointBalance, null, null, Point_transactions::DEDUCT_BET_POINT, $reason, null, Point_transactions::DEDUCT, $extra);
					
					$this->player_model->updatePlayerPointBalance($playerId, $newPointBalance);
					
					if(!$success){
						return false;
					}
					$totalDeductedPoints+=$point;
					return true;
				});

				if(!$success){
					$this->utils->error_log('========= ERROR calculate_bet_to_points_hourly add points ============================', 
					$row);
					
					continue;
				}
			}


		}//end foreach record

		$result = ['record_count'=>count($pointsTransactionsReportHours),
		'total_added_points'=>$totalAddedPoints,
		'total_deducted_points'=>$totalDeductedPoints];

		$this->utils->debug_log('========= END syncPlayerPoints ============================',$result);
		return $result;
	}

	public function syncDeletedPlayerPoints(\DateTime $from, \DateTime $to, $playerId = null) {
		$this->load->library(array('authentication'));

		$loggedAdminUserId = method_exists($this->authentication, 'getUserId') ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;

		$fromStr = $from->format('Y-m-d H:i') . ':00';
		$toStr = $to->format('Y-m-d H:i') . ':59';
		$fromDateHourStr = $this->utils->formatDateHourForMysql($from);
		$toDateHourStr = $this->utils->formatDateHourForMysql($to);

		$this->utils->debug_log('========= START syncDeletedPlayerPoints rollback points ============================',$fromStr,$toStr,$fromDateHourStr,$toDateHourStr);
		

		$rolledBackrecords=0;
		$totalDeductedPoints=0;

		//now check if records might be deleted from the report
		//get points transactions uniqueid external transaction is
		$pointsDeleted=[];
		$pointsTransactionSoFar = $this->getPointsTransactionsByDatehour($fromDateHourStr,$toDateHourStr,$playerId);
		
		foreach($pointsTransactionSoFar as $pointsTransactionSoFarRow){
			
			$doesExist = $this->points_transaction_report_hour->getPointsTransactionReportHourByUniqueid($pointsTransactionSoFarRow['external_transaction_id']);
			
			if(empty($doesExist)){
				$this->utils->debug_log('syncDeletedPlayerPoints missing from points_transaction_report_hour', $pointsTransactionSoFarRow);

				//deduct awarded points
				$success = false;
				//old report decreases, need to deduct more using the current rate for points
				$total_added_points = isset($pointsTransactionSoFarRow['total_added_points'])?floatval($pointsTransactionSoFarRow['total_added_points']):0;	
				$total_deducted_points = isset($pointsTransactionSoFarRow['total_deducted_points'])?floatval($pointsTransactionSoFarRow['total_deducted_points']):0;	
				$total_points = $total_added_points-$total_deducted_points;
				$amount = $pointsTransactionSoFarRow['total_bet_amount'];
				$playerId=$pointsTransactionSoFarRow['player_id'];
				$externalTransactionId= $pointsTransactionSoFarRow['external_transaction_id'];
				$dateHour= $pointsTransactionSoFarRow['date_hour'];
				$betToPointsRate = $pointsTransactionSoFarRow['current_rate'];
				$gamePlatformId = $pointsTransactionSoFarRow['game_platform_id'];
				$dateWithin = isset($pointsTransactionSoFarRow['date']) ? $pointsTransactionSoFarRow['date'] : '';

				if($amount<=0){
					continue;
				}

				$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_POINTS_BALANCE, $playerId, function ()
				use ($playerId, $loggedAdminUserId, $total_points, $externalTransactionId, $gamePlatformId,$amount,$dateHour,$dateWithin) {
					$beforePointBalance = $this->utils->truncateAmountDecimal($this->point_transactions->getPlayerAvailablePoints($playerId),4);
					
					if($total_points==0){
						return true;
					}
					$newPointBalance = $this->utils->truncateAmountDecimal($beforePointBalance - $total_points, 4);

					$reason = 'Points deducted transaction is removed.';

					$extra['date_hour']=$dateHour;
					$extra['date_within']=$dateWithin;
					$extra['external_transaction_id']=$externalTransactionId;
					$extra['source_amount']=$amount*-1;
					$extra['current_rate']=0;
					$extra['sub_wallet_id']=$gamePlatformId;

					$this->utils->debug_log('========= createPointTransaction deduct points data removed ============================', 'extra', $extra, 'newPointBalance', $newPointBalance, 'point', $total_points);

					$success = $this->point_transactions->createPointTransaction($loggedAdminUserId, $playerId, $total_points, 
					$beforePointBalance, $newPointBalance, null, null, Point_transactions::DEDUCT_BET_POINT, $reason, null, Point_transactions::DEDUCT, $extra);
					
					$this->player_model->updatePlayerPointBalance($playerId, $newPointBalance);
					
					if(!$success){
						return false;
					}
					return true;
				});

				if($success){
					$rolledBackrecords++;					
					$totalDeductedPoints+=$total_points;
					continue;
				}else{
					$this->utils->error_log('========= ERROR syncDeletedPlayerPoints rollback points ============================', 
					$pointsTransactionSoFarRow);
				}
				
			}
		}
		$result=['record_count'=>count($pointsTransactionSoFar),
		'rollbacked_points_count'=>$rolledBackrecords,
		'total_points_deducted'=>$totalDeductedPoints];
		$this->utils->debug_log('========= END syncDeletedPlayerPoints rollback points ============================',$result);

		return $result;
	}

	/**
	 * Get total points awarded in a given date range
	 */
	public function getTotalPointsAddedByLimit($limitFrom, $limitTo, $playerId){
		$params = [
			Point_transactions::DEPOSIT_POINT,
			Point_transactions::BET_POINT,
			Point_transactions::DEDUCT_BET_POINT,		

			Point_transactions::DEPOSIT_POINT,
			Point_transactions::BET_POINT,
			Point_transactions::DEDUCT_BET_POINT,
			$limitFrom, 
			$limitTo,
			$playerId
		];

		$query = $this->db->query("SELECT 			
			SUM(IF(transaction_type=?,`point`,0)) as total_added_deposit_points,
			SUM(IF(transaction_type=?,`point`,0)) as total_added_bet_points,
			SUM(IF(transaction_type=?,`point`,0)) as total_deducted_bet_points,
			((SUM(IF(transaction_type=?,`point`,0)) + SUM(IF(transaction_type=?,`point`,0))) - SUM(IF(transaction_type=?,`point`,0))) total_added_points
			FROM {$this->tableName}		
			WHERE `date_within` >= ? and `date_within` <= ?
			and to_id = ?;", $params);

		$result = $query->result_array();
		$this->utils->debug_log('getTotalPointsAddedByLimit',$this->db->last_query());
		if (empty($result)) {
			return [];
		} else {
			return $result;
		}
	}

	public function getPointsToAddWithinLimit($playerId, $pointLimit, $pointsLimitType, $pointsWantedToAdd, $dateWithin = null){
		$this->utils->debug_log('========= getPointsToAddWithinLimit params', 
					'totalPointsAddedSoFarByLimitAmount',
					'playerId', $playerId, 
					'pointLimit',$pointLimit,
					'pointsLimitType',$pointsLimitType,
					'pointsWantedToAdd',$pointsWantedToAdd,
					'dateWithin',$dateWithin);
		
		$getPointsToAddWithinLimitLimit = [
			'points_wanted_to_add'=>$pointsWantedToAdd,
			'points_allowed_to_add'=>$pointsWantedToAdd,
			'forfieted_points'=>0,
			'exceeds_limit'=>false,
			'point_limit'=>$pointLimit,
			'point_limit_type'=>$pointsLimitType,
			'remarks'=>'',
			'date_within'=>$dateWithin
		];

		if(!$pointLimit || $pointLimit<=0 || $pointsWantedToAdd <=0){
			$remarks = 'Added '.$pointsWantedToAdd . ' points.';
			$getPointsToAddWithinLimitLimit['remarks'] = '';
		}else{
			
			if(empty($dateWithin)){
				$dateWithinObj = new DateTime();
				$dateWithin = $dateWithinObj->format('Y-m-d');
			}

			//get date range from limit
			list($limitFrom, $limitTo) = $this->utils->getRangeFromType($pointsLimitType, $dateWithin);

			//get all added points within the range (total_added_deposit_points, total_added_bet_points, total_deducted_bet_points)
			$totalPointsAddedSoFarByLimit = $this->getTotalPointsAddedByLimit($limitFrom, $limitTo, $playerId);
			$totalPointsAddedSoFarByLimitAmount = isset($totalPointsAddedSoFarByLimit[0]['total_added_points'])?$totalPointsAddedSoFarByLimit[0]['total_added_points']:0;

			$_x = $pointLimit - $totalPointsAddedSoFarByLimitAmount;
			$pointsAllowedToAdd = $pointsWantedToAdd;
			$exceedsLimit = false;
			if($pointsWantedToAdd>$_x){
				$pointsAllowedToAdd = $_x;								 
				$exceedsLimit = true;
			}
			
			$forfeitedPoints = ($pointsWantedToAdd-$pointsAllowedToAdd);

			if($pointsAllowedToAdd<0){
				$forfeitedPoints = $pointsAllowedToAdd;
				$pointsAllowedToAdd = 0;
			}

			$remarks = 'Added '.$pointsAllowedToAdd . ' points.';

			if($exceedsLimit){
				$remarks .= ' Computed points: '.$pointsWantedToAdd.' - Forfeited points: '.$forfeitedPoints.'. Note: Computed Points exceeds '.$pointsLimitType.' points limit of '.$pointLimit.'. Previous Running Points : '.$totalPointsAddedSoFarByLimitAmount;
			}
			
			$getPointsToAddWithinLimitLimit = [
				'points_wanted_to_add'=>$pointsWantedToAdd,
				'points_allowed_to_add'=>$pointsAllowedToAdd,
				'forfieted_points'=>$forfeitedPoints,
				'exceeds_limit'=>$exceedsLimit,
				'point_limit'=>$pointLimit,
				'point_limit_type'=>$pointsLimitType,
				'remarks'=>$remarks,
				'date_within'=>$dateWithin
			];
		}

		return $getPointsToAddWithinLimitLimit;
	}

	public function calculateDepositToPoints($playerId, $depositAmount, $identifier, $conversionRate,$pointLimit,$pointLimitType,$point,$dateWithin=null){
		$extra = [];
		$getPointsToAddWithinLimit = $this->CI->point_transactions->getPointsToAddWithinLimit($playerId, $pointLimit, $pointLimitType, $point, $dateWithin);
		$this->utils->debug_log('========= getPointsToAddWithinLimit response',$playerId,$pointLimit,$pointLimitType,$point,$dateWithin,'result',$getPointsToAddWithinLimit);
		
		$point = $getPointsToAddWithinLimit['points_allowed_to_add'];
		$result = $getPointsToAddWithinLimit;
		if(empty($dateWithin)){
			$dateWithin = date('Y-m-d');
		}
		
		$extra['date_hour']=date('YmdH', strtotime($dateWithin));
		$extra['external_transaction_id']=$identifier;
		$extra['source_amount']=$depositAmount;
		$extra['current_rate']=$conversionRate;
		$extra['sub_wallet_id']=null;
		$extra['date_within']=$dateWithin;

		$extra['calculated_points']=$getPointsToAddWithinLimit['points_wanted_to_add'];
		$extra['points_limit_type']=$pointLimitType;
		$extra['points_limit']=$pointLimit;
		$extra['forfieted_points']=$getPointsToAddWithinLimit['forfieted_points'];

		$playerVipInfo=$this->CI->group_level->getPlayerGroupLevelLimitInfo($playerId);
		$extra['vip_level_id']=isset($playerVipInfo['vip_level_id'])?$playerVipInfo['vip_level_id']:null;
		$extra['vip_group_name']=isset($playerVipInfo['vip_level_name'])?$playerVipInfo['vip_level_name']:null;
		$extra['vip_level_name']=isset($playerVipInfo['vip_group_name'])?$playerVipInfo['vip_group_name']:null;


		$result['extra']=$extra;
		return $result;
	}

}
/* End of file Point Transactions.php */
/* Location: ./application/models/point_transactions.php */
