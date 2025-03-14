<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/../base_model.php';

/**
 *
 * promo rule
 *
 */
abstract class Abstract_promo_rule extends BaseModel{

    // private static $instance;

    /**
     * don't call
     */
	public function __construct(){
		parent::__construct();
	}

	protected $playerId = null;
	protected $promorule = null;
	protected $promorulesId=null;
	protected $playerBonusAmount = null;
	protected $depositAmount = null;
	protected $levelName=null;
	protected $vipGroupName=null;
	protected $levelId=null;
	protected $vipGroupId=null;
	protected $levelFullName=null;
	protected $log_str='';
	protected $dry_run=false;
	protected $extra_info=null;

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){

		$this->appendToDebugLog('start init:'.$this->getClassName());

		$this->load->model(['player_model', 'group_level', 'operatorglobalsettings']);
		$this->log_str='';
		$this->playerId = $playerId;
		$this->promorule = $promorule;
		$this->promorulesId = isset($this->promorule['promorulesId']) ? $this->promorule['promorulesId'] : null;
		$this->playerBonusAmount = floatval($playerBonusAmount);
		$this->depositAmount = floatval($depositAmount);
		$this->non_promo_withdraw_setting = $this->operatorglobalsettings->getSettingDoubleValue('non_promo_withdraw_setting');
		if(!empty($playerId)){
			$this->player= $this->player_model->getPlayerArrayById($playerId);

			$vipInfo=$this->group_level->getPlayerGroupLevelInfo($playerId);
			if(!empty($vipInfo)){
				$this->levelId=$vipInfo['levelId'];
				$this->levelName=$vipInfo['vipLevelName'];
				$this->vipGroupId=$vipInfo['vipSettingId'];
				$this->vipGroupName=$vipInfo['groupName'];
				$this->vipLevel = $vipInfo['vipLevel'];
				$this->levelFullName=$this->vipGroupName.' - '.$this->levelName;
			}
		}

		$this->appendToDebugLog('after init:'.$this->getClassName(), [
			'playerId'=>$this->playerId, 'promorulesId'=>$this->promorulesId,
			'playerBonusAmount'=>$this->playerBonusAmount, 'depositAmount'=>$this->depositAmount,
			'non_promo_withdraw_setting'=>$this->non_promo_withdraw_setting,
		]);
	}

	public function appendToDebugLog($log, $context=null){
		if(!is_string($log)){
			$log=var_export($log, true);
		}
		$msg=$log.' '.var_export($context, true);
		$this->log_str.=$msg."\n";
		$this->utils->debug_log($msg, ['class'=>$this->getClassName(), 'player_id' => $this->playerId, 'promorulesId' => $this->promorulesId,
			'playerBonusAmount' => $this->playerBonusAmount, 'depositAmount' => $this->depositAmount]);
	}

    public function returnUnimplemented() {
        return ['success' => false, 'unimplemented' => true];
    }

    public function run($func, $description, &$extra_info, $dry_run){
		$this->dry_run=$dry_run;
		$this->extra_info=$extra_info;

		$this->appendToDebugLog('start '.$func.'===========================');

    	$result=$this->$func($description, $extra_info, $dry_run);

    	$this->appendToDebugLog('result of '.$func.'===========================', ['result'=>$result]);
		if( empty($extra_info['debug_log']) ){
			$extra_info['debug_log'] = '';
		}
		$extra_info['debug_log'].=$this->log_str;

		return $result;
    }

	/**
	 * get current promo rule class name
	 * @return string
	 */
	public abstract function getClassName();

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info exchange data
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message_lang'=> errorMessageLang]
	 */
	protected abstract function runBonusConditionChecker($description, &$extra_info, $dry_run);

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info exchange data
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected abstract function generateWithdrawalCondition($description, &$extra_info, $dry_run);

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected abstract function generateTransferCondition($description, &$extra_info, $dry_run);

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info exchange data
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected abstract function releaseBonus($description, &$extra_info, $dry_run);


	//==============helper
	const EMPTY_DATETIME='0000-00-00 00:00:00';

	const DEPOSIT_TYPE_LAST = 'last';
	const DEPOSIT_TYPE_FIRST = 'first';

	const TO_TYPE_NOW = 'now';
	const DATE_YESTERDAY_START = 'yesterday_start';
	const DATE_YESTERDAY_END = 'yesterday_end';
	const DATE_TODAY_START = 'today_start';
	const DATE_TODAY_END = 'today_end';
	const DATE_LAST_WEEK_START = 'last_week_start';
	const DATE_LAST_WEEK_END = 'last_week_end';
	const DATE_THIS_WEEK_START = 'this_week_start';
	const DATE_THIS_WEEK_END = 'this_week_end';
	const LAST_RELEASE_BONUS_TIME = 'last_release_bonus_time';
	const DATE_LAST_MONTH_START = 'last_month_start';
	const DATE_LAST_MONTH_END = 'last_month_end';
	const DATE_THIS_MONTH_START = 'this_month_start';
	const DATE_THIS_MONTH_END = 'this_month_end';
	const DATE_THIS_WEEK_CUSTOM = 'this_week_custom';
	const REGISTER_DATE = 'register_date';
	const BEFORE_LAST_LOGIN_TIME = 'before_last_login_time';

	const DATE_TYPE_TODAY      = 'today';
	const DATE_TYPE_YESTERDAY  = 'yesterday';
	const DATE_TYPE_BEFORE_YESTERDAY = 'beforeyesterday';
	const DATE_TYPE_THIS_WEEK  = 'thisweek';
	const DATE_TYPE_THIS_MONTH = 'thismonth';
	const DATE_TYPE_CUSTOMIZE  = 'customize';
	const DATE_TYPE_YEAR       = 'year';

	const RESCUE_MODE_DEPOSIT_MINUS_WITHDRAWAL='deposit minus withdrawal';

	public function callHelper($funcName, $params){
		$val=null;
		if($this->process_mock($funcName, $val)){
			return $val;
		}

		return call_user_func_array([$this,$funcName], $params);

	}

	public function process_mock($name, &$val, $type=null){

		if($this->dry_run && (isset($this->extra_info['mock'][$name]) || isset($this->extra_info['mock']['promo_rule_class_mock'][$name])) ){
			$val=isset($this->extra_info['mock'][$name]) ? $this->extra_info['mock'][$name] : $this->extra_info['mock']['promo_rule_class_mock'][$name];
			if($type=='bool' || $type=='boolean'){
				$this->appendToDebugLog('convert bool mock:'.$name.', val:'.$val.', type:'.$type);
				$val=$val=='true' || $val=='on' || $val=='1';
			}

			$this->appendToDebugLog('process mock:'.$name.', type:'.$type, ['val'=>$val]);
			return true;
		}

		return false;
	}

	private function getLastSQL(){
		return $this->db->last_query();
	}

	/**
	 * get last deposit record , no exist withdrawal after deposit
	 *
	 * @param  string  $start
	 * @param  string  $end
	 * @param  boolean $check_transfer
	 * @return array $row
	 */
	public function getLastDepositByDate($start, $end, $check_transfer=false) {
		//desc and first row is last deposit
		$orderBy='desc';
		return $this->getAnyDepositByDate($start, $end, 'first', null, null, $check_transfer, $orderBy);
	}

	public function getDateByMinusDay($noOfDays, $targetDay = 'now', $format = 'Y-m-d'){
		$d = new DateTime($targetDay);
		$d->modify('-' . $noOfDays . ' day');
		return $d->format($format);
	}

	public function playerHasDepositBetweenDate($from = null, $to = null, $playerId = null){
		$result = false;
		$last_deposit_date = null;

		if(empty($from) || empty($to)){
			return $result;
		}

		if(empty($playerId)){
			$playerId = $this->playerId;
		}

		$lastTransactions = $this->getPlayerLastTransactions($playerId);
		if(empty($lastTransactions)){
			return $result;
		}

		$last_deposit_date = $lastTransactions['last_deposit_date'];
		if(empty($lastTransactions['last_deposit_date'])){
			return $result;
		}

		$deposit_date = $this->utils->formatDateForMysql(new DateTime($last_deposit_date));
		$minDate = $this->utils->formatDateForMysql(new DateTime($from));
		$maxDate = $this->utils->formatDateForMysql(new DateTime($to));

		if( ($minDate <= $deposit_date) && ($deposit_date <= $maxDate) ){
			$result = true;
		}

		$this->appendToDebugLog('lastTransactions: '.$playerId,[
			'from'=> $from, 'to'=> $to, 'last_deposit_date'=>$last_deposit_date, 'result'=>$result
		]);

		return $result;
	}

	public function getPlayerLastTransactions($playerId){
		$this->load->model(array('transactions'));
		return $this->transactions->getPlayerLastTransactionByPlayerId($playerId);
	}

	public function isDepositInPeriod($last_n_days_have_deposit = null, $currentDate = null){
		$hasDeposit = true;

		if(empty($last_n_days_have_deposit)){
			return $hasDeposit;
		}

		if(empty($currentDate)){
			$currentDate = $this->utils->getTodayForMysql();
		}

		$from = $this->callHelper('getDateByMinusDay', [$last_n_days_have_deposit]);
		$hasDeposit = $this->callHelper('playerHasDepositBetweenDate', [$from, $currentDate]);

        if(!$hasDeposit){
            $this->appendToDebugLog('not deposit in period',['hasDeposit'=>$hasDeposit]);
        }

        return $hasDeposit;
	}

	/**
	 * get any times deposit
	 * @param  string  $start
	 * @param  string  $end
	 * @param  mixin  $times  'first', 'last', number
	 * @param  int  $min
	 * @param  int  $max
	 * @param  boolean $check_transfer check transfer transaction
	 * @return array $row
	 */
	public function getAnyDepositByDate($start, $end, $times, $min, $max, $check_transfer=false, $orderBy='asc') {

		$this->load->model(array('transactions'));
		// $today = $this->utils->getTodayForMysql();
		//before transfer and withdraw

		$disable_after_type=[Transactions::WITHDRAWAL];
		if($check_transfer){
			$disable_after_type=[Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET,
				Transactions::TRANSFER_TO_MAIN_FROM_BALANCE_AFFILIATE,
				Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET,
				Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET,
				Transactions::WITHDRAWAL];
		}

		$row = $this->transactions->getAvailableDepositInfoByDate($this->playerId,
			$start, $end, $min, $max, $times, $disable_after_type, $orderBy);
		if ($row) {
			if(is_object($row)){
				$row=(array)$row;
			}
			return $row;
		}

		return null;
	}

    /**
     * check if request withdrawal after deposit successfully
     * @param $dateTimeStr
     * @param string $orderBy
     * @return array
     */
    public function isRequestWithdrawalAfterDeposit($startDateTimeStr, $orderBy='asc'){
	    $result = [];
        $this->load->model(['wallet_model']);

        $depositDateTime = new DateTime($startDateTimeStr);
        $startDatetime = $this->utils->formatDateTimeForMysql($depositDateTime);
        $endDateTime = $this->utils->getNowForMysql();
        $row = $this->wallet_model->getPlayerRequestWithdrawalByDateTime($this->playerId, $startDatetime, $endDateTime, $orderBy);

        $this->appendToDebugLog('isRequestWithdrawalAfterDeposit', ['row'=>$row]);

        if(!empty($row)){
            $result = true;
        }
        return $result;
	}

	/**
	 * search upgrade or downgrade record
	 * @param  int $type
	 * @param  string $fromDate
	 * @param  string $toDate
	 * @param  int $fromLevelId
	 * @param  int $toLevelId
	 * @return int
	 */
	public function searchFirstGradeRecord($type, $fromDate, $toDate, $fromLevelId, $toLevelId){
		$this->load->model(['group_level']);

		$recordId=null;
		//vipsettingcashbackruleId to newvipId
		//request_grade=Group_level::RECORD_UPGRADE or Group_level::RECORD_DOWNGRADE
		if($type==Group_level::RECORD_UPGRADE || $type==Group_level::RECORD_DOWNGRADE){
			$this->group_level->queryFirstGradeRecordIdBy($this->playerId, $type, $fromDate, $toDate, $fromLevelId, $toLevelId);
		}

		return $recordId;
	}

	public function searchAllUpgradeRecords($from_date, $to_date) {
		$this->load->model([ 'group_level' ]);

		$upg_res = $this->group_level->searchAllUpgradeRecords($this->playerId, $from_date, $to_date);

		return $upg_res;
	}

	/**
	 * Call group_level::searchAllGradeRecords()
	 *
	 * @param string|null $from_date
	 * @param string|null $to_date
	 * @param string|null $changedGrade
	 * @param boolean $isCrossGroupFiltered
	 * @return array
	 */
	public function searchAllGradeRecords($from_date = null, $to_date = null, $changedGrade = null, $isCrossGroupFiltered = true) {
		$this->load->model([ 'group_level' ]);
		$upg_res = $this->group_level->searchAllGradeRecords($this->playerId, $from_date, $to_date, $changedGrade, $isCrossGroupFiltered);
		return $upg_res;
	}

	/**
	 * getBetsAndDepositByDate
	 * @param  string $fromDate
	 * @param  string $toDate
	 * @return array [$bets, $deposit]
	 */
	public function getBetsAndDepositByDate($fromDate, $toDate){
		$this->load->model(['player_model']);

		return $this->player_model->getBetsAndDepositByDate($this->playerId, $fromDate, $toDate);
	}

    public function getPlayerBetByDate($fromDate, $toDate, $gamePlatformId = null, $gameTypeId = null){
	    $this->load->model(['total_player_game_day']);
	    return $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($this->playerId, $fromDate, $toDate, $gamePlatformId, $gameTypeId);

	}

    public function getPlayerTotalBetWinLoss( $fromDate // #1
                                            , $toDate // #2
                                            , $total_player_game_table = 'total_player_game_day' // #3
                                            , $where_date_field = 'date' // #4
                                            , $where_game_platform_id = null // #5
                                            , $where_game_type_id = null // #6
    ){
        $this->load->model(['total_player_game_day']);
        $playerGameTotal = $this->total_player_game_day->getPlayerTotalBetWinLoss($this->playerId, $fromDate, $toDate, $total_player_game_table, $where_date_field, $where_game_platform_id, $where_game_type_id);
        return $playerGameTotal;
	}

    public function sum_cashback_amount($fromDate, $toDate){
        //sum
        $this->CI->load->model(array('transactions'));
        return $this->returnAmount($this->CI->transactions->sumCashback(
            $this->playerId, $fromDate, $toDate));
	}

    /**
     * getBetAndDepositDateByDate
     * @param  string $fromDate
     * @param  string $toDate
     * @return array [$bets, $deposit]
     */
    public function getBetAndDepositDateByDate($fromDate, $toDate){
        $this->load->model(['total_player_game_hour', 'transactions']);
        $bet_date = $this->total_player_game_hour->getPlayerTotalBetDateByDatetime($this->playerId, $fromDate, $toDate);
        $deposit_date = $this->transactions->getPlayerTotalDepositDateByDatetime($this->playerId, $fromDate, $toDate);
        return [$bet_date, $deposit_date];
	}

	public function getPlayerBetGroupByGameTagByDate($playerId, $fromDate, $toDate){
        $this->load->model(['total_player_game_day']);
		$result = $this->total_player_game_day->getPlayerBetGroupByGameTagByDate($playerId, $fromDate, $toDate);
        return $result;
	}

    private function returnAmount($amt) {
        if (!$amt) {
            $amt = 0;
        }

        return $this->CI->utils->roundCurrencyForShow($amt);
    }

    public function sum_withdrawal_amount($from_datetime, $to_datetime, $min_amount) {
        $val=null;
        if($this->process_mock('sum_withdrawal_amount', $val)){
            return $val;
        }

        //sum
        $this->CI->load->model(array('transactions'));
        return $this->returnAmount($this->CI->transactions->sumWithdrawAmount(
            $this->playerId, $from_datetime, $to_datetime, $min_amount));
    }

    public function sum_deposit_amount($from_datetime, $to_datetime, $min_amount) {
        $val=null;
        if($this->process_mock('sum_deposit_amount', $val)){
            return $val;
        }

        //sum
        $this->CI->load->model(array('transactions'));
        return $this->returnAmount($this->CI->transactions->sumDepositAmount(
            $this->playerId, $from_datetime, $to_datetime, $min_amount));
    }

    public function sum_deposit_amount_by_sale_order($from_datetime, $to_datetime, $min_amount) {
        $val=null;
        if($this->process_mock('sum_deposit_amount_by_sale_order', $val)){
            return $val;
        }

        //sum
        $this->CI->load->model([ 'sale_order' ]);
        return $this->returnAmount($this->CI->sale_order->sumDepositRequestsByDate(
            $this->playerId, $from_datetime, $to_datetime, $min_amount));
    }

    public function getPlayerFixedDeposit($from_datetime, $to_datetime, $fixed_deposit) {
        $this->CI->load->model(array('transactions'));
        return $this->returnAmount($this->CI->transactions->getPlayerDepositFixedAmount(
            $this->playerId, $from_datetime, $to_datetime, $fixed_deposit));
    }

    public function getTopDepositByDate($fromDate, $toDate){
        $this->load->model(array('transactions'));
        $topDeposit = $this->transactions->getPlayerSingleMaxDeposit($this->playerId, $fromDate, $toDate);
        return $topDeposit;
    }

	public function countDepositByPlayerId($from_datetime, $to_datetime){
		$this->CI->load->model(array('transactions'));
		$cnt = $this->CI->transactions->countDepositByPlayerId($this->playerId, $from_datetime, $to_datetime);
		return $cnt;
	}

	public function getLastTransfer($fromDate, $toDate){
        $this->load->model(array('transactions'));
        $searchLastTransfer = $this->transactions->searchLastTransfer($this->playerId, $fromDate, $toDate);
        return $searchLastTransfer;
    }

	public function hasAnyDeposit($fromDate = null, $toDate = null){
        $this->load->model(array('transactions'));
        $hasAnyDeposit = $this->transactions->hasAnyDeposit($this->playerId, $fromDate, $toDate);
        return $hasAnyDeposit;
    }

	/**
	 * search upgrade level history
	 * @return int levelId or array
	 */
	public function getLastUpgradeLevelOrCurrentLevel($endAt, $type=null, $returnLastRecord = false){
		$levelId=$this->levelId;
		$this->load->model(['group_level']);
		$fromDate=$this->player['createdOn'];
		$toDate=$endAt;
		$playerId=$this->playerId;
		$row=$this->group_level->queryLastGradeRecordRowBy($playerId, $fromDate, $toDate, $type);

		$this->appendToDebugLog('queryLastGradeRecordRowBy: '.$playerId,[
			'fromDate'=> $fromDate, 'toDate'=> $toDate , 'row'=>$row, 'sql'=>$this->getLastSQL()
		]);

		if(!empty($row)){
		    if($returnLastRecord){
		        $levelId=$row;
            }else{
                $levelId=$row['newvipId'];
            }
		}

		return $levelId;
	}

	public function getLastMonthEndTime(){
		$d=new DateTime();
		$d->modify('-1 month');
		$d->modify('+1 day');
		return $d->format('Y-m-t').' '.Utils::LAST_TIME;
	}

	public function isCheckingBeforeDeposit(){
		if(isset($this->extra_info['is_checking_before_deposit'])){
			return $this->extra_info['is_checking_before_deposit'];
		}

		return false;
	}

	public function calcWithdrawConditionAndCheckMaxBonus($percentage, $times, $timesForDeposit=null){

		$this->appendToDebugLog('calcWithdrawConditionAndCheckMaxBonus init',[
			'playerBonusAmount'=>$this->playerBonusAmount, 'depositAmount'=>$this->depositAmount,
			'percentage'=>$percentage, 'times'=>$times, 'timesForDeposit'=>$timesForDeposit,
		]);
		if($this->playerBonusAmount<=0 || $this->depositAmount<=0){
			return 0;
		}

		if($timesForDeposit===null){
			$timesForDeposit=$this->non_promo_withdraw_setting;
		}

		$playerBonusAmount=$this->playerBonusAmount;
		$depositAmount=$this->depositAmount;
		$withdrawal_condition_amount=($playerBonusAmount+$depositAmount)*$times;
		$depositWithBonus=round($playerBonusAmount/$percentage, 2);
		//means more than max bonus
		if($depositAmount>$depositWithBonus){
			$depositWithoutBonus=round(($depositAmount-$depositWithBonus), 2);
			$withdrawal_condition_amount=($playerBonusAmount+$depositWithBonus)*$times
				+$depositWithoutBonus*$timesForDeposit;
			$this->appendToDebugLog('calcWithdrawConditionAndCheckMaxBonus more than max bonus',
				['depositWithBonus'=>$depositWithBonus, 'depositWithoutBonus'=>$depositWithoutBonus,
				'withdrawal_condition_amount'=>$withdrawal_condition_amount]);
		}else{
			$this->appendToDebugLog('calcWithdrawConditionAndCheckMaxBonus normal',
				['depositWithBonus'=>$depositWithBonus,
				'withdrawal_condition_amount'=>$withdrawal_condition_amount]);
		}

		return $withdrawal_condition_amount;
	}

	/**
	 * getLevelListDownToFirstLevel
	 * @param int $levelId
	 * @return int levelId
	 */
	public function getLevelIdListDownToFirstLevel($levelId){
		if(!empty($levelId)){
			$this->load->model(['group_level']);
			//high level to low level
			$rows=$this->group_level->getLevelListOnSameGroupFrom($levelId);

			$this->appendToDebugLog('getLevelIdListDownToFirstLevel: '.$levelId,[
				'rows'=> $rows,
			]);

			return $rows;
		}
		return null;
	}

	public function get_date_type($type, $extra_info = NULL){

		$val=null;
		if($this->process_mock('get_date_type_'.$type, $val)){
			return $val;
		}

		switch ($type){
			case self::DATE_YESTERDAY_START:
				$d=$this->utils->getYesterdayForMysql().' '.Utils::FIRST_TIME;
				break;
			case self::DATE_YESTERDAY_END:
				$d=$this->utils->getYesterdayForMysql().' '.Utils::LAST_TIME;
				break;
			case self::DATE_TODAY_START:
				$d=$this->utils->getTodayForMysql().' '.Utils::FIRST_TIME;
				break;
			case self::DATE_TODAY_END:
				$d=$this->utils->getTodayForMysql().' '.Utils::LAST_TIME;
				break;
			case self::TO_TYPE_NOW:
				$d=$this->utils->getNowForMysql();
				break;
            case self::DATE_LAST_WEEK_START:
                $week_start = (isset($extra_info['week_start'])) ? $extra_info['week_start'] : 'sunday';

                $previous_week = strtotime('-1 week +1 day', strtotime($this->utils->getNowForMysql()));
                $dt = new DateTime();
                $dt->setTimestamp(strtotime('last ' . $week_start . ' midnight', $previous_week));

                $d=$this->utils->formatDateForMysql($dt).' '.Utils::FIRST_TIME;
                break;
            case self::DATE_LAST_WEEK_END:
                $week_start = (isset($extra_info['week_start'])) ? $extra_info['week_start'] : 'sunday';
                $previous_week = strtotime("-1 week +1 day", strtotime($this->utils->getNowForMysql()));
                $dt = new DateTime();
                $dt->setTimestamp(strtotime('last ' . $week_start . ' +6 day', $previous_week));

                $d=$this->utils->formatDateForMysql($dt).' '.Utils::LAST_TIME;
                break;
            case self::DATE_THIS_WEEK_START:
            	//always monday
                $d=date('Y-m-d H:i:s', strtotime('midnight monday this week'));
                break;
            case self::DATE_THIS_WEEK_END:
            	//to now
                $d=$this->utils->getNowForMysql();
				break;
			case self::DATE_THIS_WEEK_CUSTOM:
				$d=date('Y-m-d H:i:s', strtotime('midnight'. $extra_info .'this week'));
				break;
			case self::DATE_LAST_MONTH_START:
				$d=date('Y-m-d', strtotime('first day of last month')).' '.Utils::FIRST_TIME;
				break;
			case self::DATE_LAST_MONTH_END:
				$d=date('Y-m-d', strtotime('last day of last month')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_THIS_MONTH_START:
				//always 01 of current month
				$d=date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
				break;
			case self::DATE_THIS_MONTH_END:
				//always 30, 31 or 28,29
				$d=date('Y-m-d H:i:s', strtotime('midnight first day of next month -1 second'));
				break;
			case self::REGISTER_DATE:
				$getPlayerInfoById = $this->getPlayerInfoById($this->playerId);
				$d=$getPlayerInfoById['playerCreatedOn'];
				break;
			case self::BEFORE_LAST_LOGIN_TIME:
				$getPlayerInfoById = $this->getPlayerInfoById($this->playerId);
				$d=$getPlayerInfoById['before_last_login_time'];
				break;
			case self::LAST_RELEASE_BONUS_TIME:
				$this->load->model(['player_promo']);
				$row=$this->player_promo->getLastReleasedPlayerPromo($this->playerId, $this->promorule['promorulesId']);
				if(!empty($row)){
					$d=$row['dateProcessed'];
				}else{
					$d=self::EMPTY_DATETIME;
				}

				break;
			default:
				$d=new DateTime($type);
				$d=$this->utils->formatDateTimeForMysql($d);
				break;
		}

		return $d;
	}

	public function get_request_count_player_promo($playerId, $promocmssettingId = 0, $promorulesId = 0, $todayYmd = null){
		$val=null;
		if($this->process_mock('get_request_count_player_promo', $val)){
			return $val;
		}
		$requestCount=$this->player_promo->getDailyPromoRequestByPlayerIdElasticCmsRulesId($playerId, $promocmssettingId, $promorulesId, $todayYmd);
		return $requestCount;
	}

	public function get_last_released_player_promo($promorulesId, $date_type, $extra_info = NULL){
		$val=null;
		if($this->process_mock('already_released_promo_rule', $val)){
			return $val;
		}

		$result=false;
		$start=null;
		$end=null;

		switch ($date_type){
			case self::DATE_TYPE_TODAY:
				$start=date('Y-m-d').' '.Utils::FIRST_TIME;
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_YESTERDAY:
				$start=date('Y-m-d', strtotime('yesterday')).' '.Utils::FIRST_TIME;
				$end=date('Y-m-d', strtotime('yesterday')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_BEFORE_YESTERDAY:
				$start=date('Y-m-d', strtotime('2 days ago')).' '.Utils::FIRST_TIME;
				$end=date('Y-m-d', strtotime('2 days ago')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_THIS_WEEK:
				$start = $this->get_date_type(self::DATE_THIS_WEEK_START);
				$end = $this->get_date_type(self::DATE_THIS_WEEK_END);
				break;
			case self::DATE_TYPE_THIS_MONTH:
				$start = $this->get_date_type(self::DATE_THIS_MONTH_START);
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_CUSTOMIZE:
				$start = $extra_info['start'];
				$end = $extra_info['end'];
				break;
			case self::DATE_TYPE_YEAR:
				$start = date('Y-m-d', strtotime('first day of january this year')).' '.Utils::FIRST_TIME;
				$end = $this->get_date_type(self::DATE_TODAY_END);
				break;
			default:
				break;
		}

		if(!empty($this->playerId) && !empty($promorulesId)){
			$this->load->model(array('player_promo'));


			$row=$this->player_promo->getLastReleasedPlayerPromo($this->playerId, $promorulesId, $start, $end);
			$this->appendToDebugLog('get_last_released_player_promo: '.$this->playerId,[
				'start'=> $start, 'end' => $end, 'row.count:', empty($row)? 0:count($row)
			]);
			if(!empty($extra_info['returnOneRow'])){
                $result=$row;
            }else{
                if(empty($row)){
                    $result=false;
                }else{
                    $result=true;
                }
            }
		}

		return $result;

	}

		public function exist_unfinished_wc_by_promorulesId($promorulesId, $date_type, $extra_info = NULL){
		$val=null;
		if($this->process_mock('exist_unfinished_wc_by_promorulesId', $val)){
			return $val;
		}

		$result=false;
		$start=null;
		$end=null;

		switch ($date_type){
			case self::DATE_TYPE_TODAY:
				$start=date('Y-m-d').' '.Utils::FIRST_TIME;
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_YESTERDAY:
				$start=date('Y-m-d', strtotime('yesterday')).' '.Utils::FIRST_TIME;
				$end=date('Y-m-d', strtotime('yesterday')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_BEFORE_YESTERDAY:
				$start=date('Y-m-d', strtotime('2 days ago')).' '.Utils::FIRST_TIME;
				$end=date('Y-m-d', strtotime('2 days ago')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_THIS_WEEK:
				$start = $this->get_date_type(self::DATE_THIS_WEEK_START);
				$end = $this->get_date_type(self::DATE_THIS_WEEK_END);
				break;
			case self::DATE_TYPE_THIS_MONTH:
				$start = $this->get_date_type(self::DATE_THIS_MONTH_START);
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_CUSTOMIZE:
				$start = $extra_info['start'];
				$end = $extra_info['end'];
				break;
			case self::DATE_TYPE_YEAR:
				$start = date('Y-m-d', strtotime('first day of january this year')).' '.Utils::FIRST_TIME;
				$end = $this->get_date_type(self::DATE_TODAY_END);
				break;
			default:
				break;
		}

		if(!empty($this->playerId) && !empty($promorulesId)){
			$this->load->model(array('withdraw_condition'));
			$row=$this->withdraw_condition->existUnfinishedWithdrawalConditionByPromorulesId($this->playerId, $promorulesId, $start, $end);
			$this->appendToDebugLog('exist_unfinished_wc_by_promorulesId: '.$this->playerId,[
				'start'=> $start, 'end' => $end, 'row.count:' => empty($row)? 0:count($row), 'promorulesId' => $promorulesId
			]);
			if(!empty($extra_info['returnOneRow'])){
                $result=$row;
            }else{
                if(empty($row)){
                    $result=false;
                }else{
                    $result=true;
                }
            }
		}

		return $result;
	}

	/**
	 * Get all released data in playerpromo table.
	 *
	 * @param integer $promorulesId The field, "playerpromo.promorulesId".
	 * @param string $date_type The datetime range in the WHERE clause.
	 * @param array $extra_info For get the datetime range, while date_type = Abstract_promo_rule::DATE_TYPE_CUSTOMIZE.
	 * @return false|array The rows.
	 */
    public function get_all_released_player_promo($promorulesId, $date_type = null, $extra_info = NULL) {
        $result = false;

        switch ($date_type){
            case self::DATE_TYPE_TODAY:
                $start = date('Y-m-d').' '.Utils::FIRST_TIME;
                $end = date('Y-m-d').' '.Utils::LAST_TIME;
                break;
            case self::DATE_TYPE_YESTERDAY:
                $start = date('Y-m-d', strtotime('yesterday')).' '.Utils::FIRST_TIME;
                $end = date('Y-m-d', strtotime('yesterday')).' '.Utils::LAST_TIME;
                break;
            case self::DATE_TYPE_THIS_WEEK:
                $start = $this->get_date_type(self::DATE_THIS_WEEK_START);
                $end = $this->get_date_type(self::DATE_THIS_WEEK_END);
                break;
            case self::DATE_TYPE_THIS_MONTH:
                $start = $this->get_date_type(self::DATE_THIS_MONTH_START);
                $end = date('Y-m-d').' '.Utils::LAST_TIME;
                break;
            case self::DATE_TYPE_CUSTOMIZE:
                $start = $extra_info['start'];
                $end = $extra_info['end'];
                break;
            case self::DATE_TYPE_YEAR:
                $start = date('Y-m-d', strtotime('first day of january this year')).' '.Utils::FIRST_TIME;
                $end = $this->get_date_type(self::DATE_TODAY_END);
                break;
            default:
                $start = null;
                $end = null;
                break;
        }

        if(!empty($this->playerId) && !empty($promorulesId)){
            $this->load->model(array('player_promo'));

            $level_id = null;
            $ignore_player_id = false;
            if(!empty($extra_info['level_id']) && !empty($extra_info['ignore_player_id'])){
                $level_id = $extra_info['level_id'];
                $ignore_player_id = $extra_info['ignore_player_id'];
            }

            $row = $this->player_promo->getAllReleasedPlayerPromo($this->playerId, $promorulesId, $start, $end, $level_id, $ignore_player_id);

			$this->appendToDebugLog('get_all_released_player_promo: '.$this->playerId, ['start'=> $start, 'end' => $end, 'result.count:' => empty($row)?0:count($row),
                'level_id' => $level_id, 'ignore_player_id', $ignore_player_id]);

            if(!empty($row)){
                $result = $row;
            }

        }

        return $result;
	} // EOF get_all_released_player_promo

    public function get_all_released_player_promo_by_times($promorulesId = null, $limit = null){
        $result = [];

        if(!empty($this->playerId)){
            $this->load->model(array('player_promo'));
            $row = $this->player_promo->getReleasedPlayerPromoByTimes($promorulesId, $this->playerId, $limit);

            if(!empty($row)){
                $result = $row;
            }
        }

        return $result;
    }

	public function count_approved_promo($promorulesId, $date_type, $extra_info = NULL) {
        $val=null;
		if($this->process_mock('count_approved_promo_by_date', $val)){
		    return $val;
        }

        $result = 0;
		$start = null;
		$end = null;

		switch ($date_type){
			case self::DATE_TYPE_TODAY:
				$start=date('Y-m-d').' '.Utils::FIRST_TIME;
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_YESTERDAY:
				$start=date('Y-m-d', strtotime('yesterday')).' '.Utils::FIRST_TIME;
				$end=date('Y-m-d', strtotime('yesterday')).' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_THIS_WEEK:
				$start = $this->get_date_type(self::DATE_THIS_WEEK_START);
				$end = $this->get_date_type(self::DATE_THIS_WEEK_END);
				break;
			case self::DATE_TYPE_THIS_MONTH:
				$start = $this->get_date_type(self::DATE_THIS_MONTH_START);
				$end=date('Y-m-d').' '.Utils::LAST_TIME;
				break;
			case self::DATE_TYPE_CUSTOMIZE:
				$start = $extra_info['start'];
				$end = $extra_info['end'];
				break;
			case self::DATE_TYPE_YEAR:
				$start = date('Y-m-d', strtotime('first day of january this year')).' '.Utils::FIRST_TIME;
				$end = $this->get_date_type(self::DATE_TODAY_END);
				break;
		}

		if(!empty($this->playerId) && !empty($promorulesId)){

			$this->load->model(array('player_promo'));

			$this->appendToDebugLog('count_approved_promo_by_date: '.$this->playerId,[
				'start'=> $start, 'end' => $end
			]);
			$cnt = $this->player_promo->countPlayerPromo($this->playerId, $promorulesId, $start, $end);
			if(empty($cnt)){
				$result= 0;
			}else{
				$result= $cnt;
			}
		}
		return $result;
	}

    public function countRequestPromoFromSameIp($ip, $fromDate, $toDate){
        $this->load->model(['player_promo']);
        $result = $this->player_promo->existsPlayerPromoFromSameIpByDate($this->promorulesId, $ip, $fromDate, $toDate);
        return $result;
	}

	public function getPlayerLoginIpByDate($playerId, $fromDate, $toDate){
		$this->load->model(['player_login_report']);
		$result = $this->player_login_report->getPlayerLoginIpByDate($playerId, $fromDate, $toDate);
		return $result;
	}

	/**
	 * totalDepositByPlayerAndDateTime
	 * @param  int $playerId
	 * @param  string $startDatetime
	 * @param  string $endDatetime
	 * @return boolean $exists
	 */
	public function totalDepositByPlayerAndDateTime($playerId, $startDatetime, $endDatetime) {
		$this->load->model(['transactions']);
		return $this->transactions->totalDepositByPlayerAndDateTime($playerId, $startDatetime, $endDatetime);
	}

    /**
     * totalDepositAndDateByDateTime
     * @param  int $playerId
     * @param  string $startDate
     * @param  string $endDate
     * @param  int $min_amount
     * @return array
     */
    public function getConsecutiveDepositAndDateByDateTime($playerId, $startDate, $endDate, $min_amount) {
        $this->load->model(['transactions']);
        return $this->transactions->getConsecutiveDepositAndDateByDateTime($playerId, $startDate, $endDate, $min_amount);
    }

	/**
	 * getTotalAmountFromHourlyReportByPlayerAndDateTime
	 * @param  int $playerId
	 * @param  string $startDatetime
	 * @param  string $endDatetime
	 * @return array [$totalBet, $totalResult, $totalWin, $totalLoss]
	 */
	public function getTotalAmountFromHourlyReportByPlayerAndDateTime($playerId, $startDatetime, $endDatetime) {
		//from game report
		$this->load->model(['total_player_game_hour']);
		return $this->total_player_game_hour->getTotalAmountFromHourlyReportByPlayerAndDateTime(
			$playerId, $startDatetime, $endDatetime);
	}

	/**
	 * totalReleasedBonusByPlayerAndDateTime
	 * @param  int $playerId
	 * @param  string $startDatetime
	 * @param  string $endDatetime
	 * @return double $total_bonus
	 */
	public function totalReleasedBonusByPlayerAndDateTime($playerId, $startDatetime, $endDatetime) {
		//from game report
		$this->load->model(['transactions']);
        return $this->returnAmount($this->CI->transactions->totalReleasedBonusByPlayerAndDateTime(
            $playerId, $startDatetime, $endDatetime));
	}

	/**
	 * Pluck an array of values from an array. (Only for PHP 5.3+)
	 *
	 * @param  $array - data
	 * @param  $key - value you want to pluck from array
	 *
	 * @return plucked array only with key data
	 *
	 * Ref. to https://gist.github.com/ozh/82a17c2be636a2b1c58b49f271954071
	 */
	public function array_pluck($array, $key) {
		return array_map(function($v) use ($key) {
		return is_object($v) ? $v->$key : $v[$key];
		}, $array);
	}

	/**
	 * Returns playerdetails.birthdate for player
	 * @param	int		$player_id		== player.playerId
	 * @return	date
	 */
	public function getPlayerBirthdate($player_id = null) {
		if (empty($player_id)) {
			$player_id = $this->playerId;
		}
		$player_details = $this->player_model->getAllPlayerDetailsById($player_id);
		$birthdate = $player_details['birthdate'];

		return $birthdate;
	}

	public function existsTransByTypesAfter($playerId, $promorule, $created_at, $extra_info){
		if($promorule['donot_allow_any_withdrawals_after_deposit']){
			if($this->transactions->existsTransByTypesAfter($created_at, $playerId, [Transactions::WITHDRAWAL], [Transactions::STATUS_NORMAL])){
				$extra_info['error_message']='notify.promo_donot_allow_any_withdrawals_after_deposit';

				$this->appendToDebugLog($extra_info['debug_log'], 'WITHDRAWAL promo_donot_allow_any_withdrawals_after_deposit',
					['created_at'=>$created_at, 'playerId'=>$playerId]);

				return true;
			}
		}

		if($promorule['donot_allow_any_despoits_after_deposit']){
			if($this->transactions->existsTransByTypesAfter($created_at, $playerId, [Transactions::DEPOSIT], [Transactions::APPROVED])){
				$extra_info['error_message']='notify.promo_donot_allow_any_despoits_after_deposit';
				$this->appendToDebugLog($extra_info['debug_log'], 'DEPOSIT promo_donot_allow_any_despoits_after_deposit',
					['created_at'=>$created_at, 'playerId'=>$playerId]);
				return true;
			}
		}

		if($promorule['donot_allow_any_available_bet_after_deposit']){
			$this->CI->load->model(['game_logs']);

			list($totalBet, $totalWin, $totalLoss) = $this->CI->game_logs->getTotalBetsWinsLossByPlayers($playerId, $created_at, $this->CI->utils->getNowForMysql());
			$totalWin = (float)$totalWin;
			$totalLoss = (float)$totalLoss;
			if($totalWin != 0 || $totalLoss != 0){
				$extra_info['error_message']='notify.promo_donot_allow_any_available_bet_after_deposit';
				$this->appendToDebugLog($extra_info['debug_log'], 'DEPOSIT promo_donot_allow_any_available_bet_after_deposit',
					['created_at'=>$created_at, 'playerId'=>$playerId]);
				return true;
			}
		}

		if($promorule['donot_allow_any_transfer_after_deposit']){
			if($this->transactions->existsTransByTypesAfter($created_at, $playerId, [
				Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET, Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET,
				Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET, Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET], [Transactions::STATUS_NORMAL])){
				$extra_info['error_message']='notify.promo_donot_allow_any_transfer_after_deposit';

				$this->appendToDebugLog($extra_info['debug_log'],
					'TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET/TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET/MANUAL_ADD_BALANCE_ON_SUB_WALLET/MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET promo_donot_allow_any_transfer_after_deposit',
					['created_at'=>$created_at, 'playerId'=>$playerId]);

				return true;
			}
		}

		if($this->utils->getConfig('promo_donot_allow_exists_any_bet_after_deposit')){
			if($promorule['donot_allow_exists_any_bet_after_deposit']){
				$this->load->model(['game_logs']);
				if($this->game_logs->existsAnyBetRecord($playerId, $created_at, $this->utils->getNowForMysql())){
					$extra_info['error_message']='notify.promo_donot_allow_exists_any_bet_after_deposit';
					$this->appendToDebugLog($extra_info['debug_log'], 'existsAnyBetRecord promo_donot_allow_exists_any_bet_after_deposit',
						['created_at'=>$created_at, 'playerId'=>$playerId]);
					return true;
				}
			}
		}

		$deposit_promotion_disabled_transaction_type_list=$this->utils->getConfig('deposit_promotion_disabled_transaction_type_list');

		$disabledTransType = [];

		foreach($deposit_promotion_disabled_transaction_type_list as $transType){
			switch($transType){
				case 'transfer':
					$disabledTransType[] = Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET;
					$disabledTransType[] = Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET;
					$disabledTransType[] = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;
					$disabledTransType[] = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
					break;
				case 'deposit':
				case 'withdrawal':
				default:
					# code...
					break;
			}
		}

		$this->utils->debug_log('disable_after_type', $disabledTransType);

		// if(!$this->getPromotionRules('enabled_notallow_transaction_type')){
		// 	$disabledTransType=null;
		// }

		if(empty($disabledTransType)){
			return false;
		}

		if($this->transactions->existsTransByTypesAfter($created_at, $playerId, $disabledTransType)){
			$extra_info['error_message']='notify.promo_donot_allow_any_transfer_after_deposit';
			return true;
		}

		return false;
	}

	/**
	 * check exist usdt account
	 * @param  string $playerId
	 * @return boolean $exist_usdt_account
	 */
	public function getCryptoAccountByPlayerId($playerId, $type = 'deposit', $cryptoCurrency = 'USDT'){
		$this->load->model(['playerbankdetails']);
		return $this->playerbankdetails->getCryptoAccountByPlayerId($playerId, $type, $cryptoCurrency);
	}

	/**
	 * get player exist usdt deposit
	 * @param  string $playerId
	 * @param  string $periodFrom
	 * @param  string $periodTo
	 * @param  string $orderBy
	 * @return array $usdtOrder
	 */
	public function getUsdtDepositListBy($playerId, $limit = null, $orderBy = null, $periodFrom = null, $periodTo = null){
		$this->load->model(['transactions']);
		return $this->transactions->getUsdtDepositListBy($playerId, $limit, $orderBy, $periodFrom, $periodTo);
	}

	/**
	 * get player info by playerId
	 * @param  string $playerId
	 * @return array $getPlayerInfoById
	 */
	public function getPlayerInfoById($playerId){
		$this->load->model(['player_model']);
		return $this->player_model->getPlayerInfoById($playerId);
	}

	#dscsdcds
	/**
	 * get getPlayerActivePromo
	 * @param  string $playerId
	 * @return array $getPlayerInfoById
	 */
	public function getPlayerActivePromo($playerId){
		$this->load->model(['player_promo']);

		$result = false;

		if (!empty($playerId)) {
			return $this->player_promo->getPlayerActivePromo($playerId);
		}
		return $result;
	}

	public function isPlayerActive($player_id = null){
		if(empty($player_id)){
			$player_id = $this->playerId;
		}

		$isActive = false;
		$playerStatus = $this->utils->getPlayerStatus($player_id);
		if($playerStatus == 0){
			$isActive = true;
		}
		$this->appendToDebugLog('playerStatus', ['isActive'=>$isActive]);
		return $isActive;
	}

	public function listDepositTransactions($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount = '') {
		$this->CI->load->model([ 'transactions' ]);
        return $this->CI->transactions->getDepositListBy($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount);
    }

    public function getPlayerDepositListByDates($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount = '') {
		$this->CI->load->model([ 'transactions' ]);
        $result = $this->CI->transactions->getPlayerDepositListByDates($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount);

        $this->utils->printLastSQL();
        return $result;
    }

    public function getPlayerBetListByDates($player_id, $from_datetime, $to_datetime, $min_amount = '', $max_amount = '', $gamePlatformId = '') {
		$this->CI->load->model([ 'total_player_game_hour' ]);
        $result = $this->CI->total_player_game_hour->getPlayerBetListByDates($player_id, $from_datetime, $to_datetime, $min_amount, $max_amount);

        $this->utils->printLastSQL();
        return $result;
    }

	public function getPlayerBonusAmount($player_id, $promorules_id, $from_datetime, $to_datetime){
		$this->load->model(['player_promo']);
		$paidBonus = $this->player_promo->sumBonusAmount($player_id, $promorules_id, $from_datetime, $to_datetime);
		return $this->returnAmount($paidBonus);
	}

	/**
	 * getRandomItemByOdds
	 *
	 * $bonus_settings:
	[
        {"odds": 81.2, "bonus": 2, "count": 100000, "is_default": true},
        {"odds": 12.3, "bonus": 10, "count": 100},
        {"odds": 5.5, "bonus": 40, "count": 20},
        {"odds": 1, "bonus": 100, "count": 2}
    ]
	 * @param  array $bonus_settings
	 * @return array item of $bonus_settings
	 */
	public function getRandomItemByOdds($bonus_settings){
		$result=null;
		if(!empty($bonus_settings)){
			$range=0;
			$oddsList=[];
			$defaultItem=null;
			//search default item and sort odds list
			foreach ($bonus_settings as $idx=>$item) {
				$odds=intval($item['odds']*100);
				$oddsList[$odds]=$idx;
				if(array_key_exists('is_default', $item) && $item['is_default']){
					$defaultItem=$item;
				}
				$range+=$odds;
			}
			ksort($oddsList);
			//generate random item
			$choice=rand(1, $range);
			$this->appendToDebugLog('range: '.$range.', choice: '.$choice, ['oddsList'=>$oddsList]);
			foreach ($oddsList as $odds => $idx) {
				if($choice<=$odds){
					//got it
					$result=$bonus_settings[$idx];
					$this->appendToDebugLog('got result: '.$idx, ['result'=>$result]);
					break;
				}
			}
			// set default item
			if($result===null){
				$result=$defaultItem;
			}
		}

		return $result;
	}

	public function getConditionSchema() {
		return [];
	}

	public function getReleaseSchema() {
		return [];
	}

    /**
     * Check Not Allow Other Promo On The Same Day
     *
     * @param array $promorule_ids The mutually Exclusived List in promorule id.
     * @param &boolean $isReleasedBonusToday
     * @return void
     */
    public function _checkNotAllowOtherPromoOnTheSameDay($promorule_ids, &$isReleasedBonusToday){
        $releasedBonusToday = [];
        $isReleasedBonusToday = false;

        if(!empty($promorule_ids) && is_array($promorule_ids)){
            foreach ($promorule_ids as $promorule_id){
                $get_last_released_player_promo = $this->callHelper('get_last_released_player_promo',[$promorule_id, self::DATE_TYPE_TODAY]);
                if($get_last_released_player_promo){
                    $releasedBonusToday[$promorule_id] = $get_last_released_player_promo;
                }
            }
        }

        if(!empty($releasedBonusToday)){
            $isReleasedBonusToday = true;
            $this->appendToDebugLog('exist other promo on the same day(1188)', ['releasedBonusToday' => $releasedBonusToday]);
        }
    }

	public function getPlayerPromoByReferralId($referralId){
		$this->load->model(['player_promo']);
		$result = $this->player_promo->getPlayerPromoByReferralId($this->promorulesId, $referralId);
		return $result;
	}

	public function _checkNotAllowOtherPromoRecords($promorule_ids, &$existPromoRecord, $date_type = null){
        $releasedRecords = [];
        $existPromoRecord = false;

        if(!empty($promorule_ids) && is_array($promorule_ids)){
            foreach ($promorule_ids as $promorule_id){
                $get_last_released_player_promo = $this->callHelper('get_last_released_player_promo',[$promorule_id, $date_type]);
                if($get_last_released_player_promo){
                    $releasedRecords[$promorule_id] = $get_last_released_player_promo;
                }
            }
        }

        if(!empty($releasedRecords)){
            $existPromoRecord = true;
            $this->appendToDebugLog('exist other promo records', ['existPromoRecord' => $existPromoRecord]);
        }
    }

	/**
     * Check Not Allow Other Promo Records
     *
     * @param array $promorule_ids The mutually Exclusived List in promorule id.
     * @return array item of released bonus promorule id
     */
    public function existUnifinishWcRecords($promorule_ids, $date_type = null, $extra_info = null){
        $unfinished_wc = [];
        $existUnfinishedWc = false;

        if(!empty($promorule_ids) && is_array($promorule_ids)){
			if(!empty($extra_info['updateWithdrawalCondition'])){
				$this->load->model(['withdraw_condition']);
				$this->withdraw_condition->getPlayerWithdrawalCondition($this->playerId);
				$this->appendToDebugLog('update withdrawal condition', ['updateWithdrawalCondition' => $extra_info['updateWithdrawalCondition']]);
			}
            foreach ($promorule_ids as $promorule_id){
                $exist_unfinished_wc = $this->callHelper('exist_unfinished_wc_by_promorulesId',[$promorule_id, $date_type, $extra_info]);
                if($exist_unfinished_wc){
                    $unfinished_wc[$promorule_id] = $exist_unfinished_wc;
                }
            }
        }

        if(!empty($unfinished_wc) ){
            $existUnfinishedWc = true;
            $this->appendToDebugLog('exist unfinished wc', ['existUnfinishedWc' => $unfinished_wc]);
        }

		return $existUnfinishedWc;
    }	
}
