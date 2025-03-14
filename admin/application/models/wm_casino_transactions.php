<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Wm_casino_transactions extends Base_game_logs_model {

	public $tableName = "wm_casino_transactions";

	const TRANSTYPE_CALLBALANCE = 'CallBalance';
	const TRANSTYPE_POINTINOUT = 'PointInout';
	const TRANSTYPE_TIMEOUTBETRETURN = 'TimeoutBetReturn';
	const TRANSTYPE_SEND_MEMBER_REPORT = 'SendMemberReport';


    const CODE_SLOT_FINISH = '0';
    const CODE_POINT_INCREASE = '1'; // code: 1:when member wins
    const CODE_POINT_DECREASE = '2'; // code: 2:when memeber bet
    const CODE_POINT_INCREASE_BY_GAME_RESET = '3'; // code: 3:when a round change and affected this member :lose change to win
    const CODE_POINT_DECREASE_BY_GAME_RESET = '4'; // when a round change and affected this member :win change to lose
    const CODE_RE_PAYOUT = 5; // for #5code: 5:manual adding credit to member or manual deducting credit from member

    const CODES = [
        self::CODE_SLOT_FINISH=>'0',
        self::CODE_POINT_INCREASE=>'1',
        self::CODE_POINT_DECREASE=>'2',
        self::CODE_POINT_INCREASE_BY_GAME_RESET=>'3',
        self::CODE_POINT_DECREASE_BY_GAME_RESET=>'4',
        self::CODE_RE_PAYOUT=>5
    ];

	public $origTableName = "wm_casino_transactions";
 
	function __construct() {
		parent::__construct();
    }

	public function setTableName($table){
		$this->tableName = $table;
	}

	public function setOrigableName($table){
		$this->origTableName = $table;
	}
	
	public function getTransaction($table, $uniqueId) {
        $qry = $this->db->get_where($table, array('external_uniqueid' => $uniqueId));
        $transaction = $this->getOneRow($qry);
		if ($transaction) {
			return true;
		} else {
			return false;
		}
	}  
	
	public function getRoundData($table, $roundId) {
		$query = $this->db->get_where($table, array('round_id' => $roundId));
		return $query->result_array();	
	}

	public function updateTransaction($externalUniqueId, $data, $table = null) {
        return $this->updateData('external_uniqueid', $externalUniqueId, $table, $data);		
	}

	public function updateTransactionByKeyValue($key, $value, $data, $table = null) {
        return $this->updateData($key, $value, $table, $data);		
	}

	public function updateTransactionArr($whereArr, $updateDataArr, $table = null) {
        return $this->editData($table, $whereArr, $updateDataArr);		
	}

    public function getTransactionsPreviousTable($origTableName = null){
		if(is_null($origTableName)){
			$origTableName = $this->origTableName;
		}

        $d = new DateTime('-1 month');                 
        $monthStr = $d->format('Ym');
        
        return $this->initTransactionsMonthlyTableByDate($monthStr, $origTableName);        
    }

	public function initTransactionsMonthlyTableByDate($yearMonthStr, $origTableName){

		$tableName = $origTableName.'_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
            
			try{
                $this->CI->db->query("CREATE TABLE $tableName LIKE wm_casino_transactions");
			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}



	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}



}

///END OF FILE///////