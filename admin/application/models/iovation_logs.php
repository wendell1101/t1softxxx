<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Iovation Logs
 *
 *
 * @category Payment Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Iovation_logs extends BaseModel {

	public $tableName = 'iovation_logs';

	protected $idField = 'id';

	const ALLOW = 'A';
	const DENY = 'D';
	const REVIEW = 'R';	
	
	const SUCCESS ='0';	
	const FAILED ='1';

	const LOG_TYPE_registration = "registration";
	const LOG_TYPE_affiliateRegistration = "affiliateRegistration";
	const LOG_TYPE_affiliateLogin = "affiliateLogin";
	const LOG_TYPE_promotion = "promotion";
	const LOG_TYPE_depositSelectPromotion = "depositSelectPromotion";
	const LOG_TYPE_resendRegistration = "resendRegistration";
	const LOG_TYPE_resendPromotion = "resendPromotion";
	const LOG_TYPE_addEvidence = "addEvidence";
	const LOG_TYPE_updateEvidence = "updateEvidence";
	const LOG_TYPE_retractEvidence = "retractEvidence";

    public function __construct() {
        parent::__construct();
    }

	/**
	 * get log
	 * @param  string		$id
	 * @return boolean
	 */
	public function getLog($id, $use_monthly_table = false) {
        if ($use_monthly_table) {
            $table_name = $this->getCurrentYearMonthTable();
        } else {
            $table_name = $this->tableName;
        }

		$qry = $this->db->get_where($table_name, array('id' => $id));

        // check previous table
        if (empty($qry) && $use_monthly_table) {
            $qry = $this->db->get_where($this->getPreviousYearMonthTable(), array('id' => $id));
        }

		return $this->getOneRow($qry);
    }   

	/**
	 * get log by username
	 * @param  string		$id
	 * @return boolean
	 */
	public function getLogByPlayerId($player_id) {
		$qry = $this->db->get_where($this->tableName, array('player_id' => $player_id));
		return $this->getOneRow($qry);
    }    

	/**
	 * get log by username
	 * @param  string		$id
	 * @return boolean
	 */
	public function getLogByAccountCode($account_code) {
		$qry = $this->db->get_where($this->tableName, array('account_code' => $account_code));
		return $this->getOneRow($qry);
    } 

    public function initializeIovationLogsYearMonthTables($table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->getCurrentYearMonthTable($table_name);
        $this->getPreviousYearMonthTable($table_name);
        $this->getNextYearMonthTable($table_name);
    }

    public function createYearMonthTable($table_name = null, $year_month = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        if (empty($year_month)) {
            // default current month
            $year_month = $this->utils->getThisYearMonth();
        }

        $ym_table_name = "{$table_name}_{$year_month}";

        if (!$this->utils->table_really_exists($ym_table_name)) {
            try {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $table_name, 'create table: ' . $ym_table_name);
                $this->runRawUpdateInsertSQL("CREATE TABLE {$ym_table_name} LIKE {$table_name}");
            } catch(Exception $e) {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $table_name, 'create table failed: ' . $ym_table_name, $e);
            }
        }

        return $ym_table_name;
    }

    public function getCurrentYearMonthTable($table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        return $this->createYearMonthTable($table_name, $this->utils->getThisYearMonth());
    }

    public function getPreviousYearMonthTable($table_name = null, $date = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        if (!empty($date)) {
            $ym = $this->utils->getPreviousYearMonthByDate($date);
        } else {
            $ym = $this->utils->getLastYearMonth();
        }

        return $this->createYearMonthTable($table_name, $ym);
    }

    public function getNextYearMonthTable($table_name = null, $date = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        if (!empty($date)) {
            $ym = $this->utils->getNextYearMonthByDate($date);
        } else {
            $ym = $this->utils->getLastYearMonth();
        }

        return $this->createYearMonthTable($table_name, $ym);
    }

    public function getYearMonthTableByDate($table_name = null, $date) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $ym_table_name = $this->getCurrentYearMonthTable($table_name);
        $ym = $this->utils->getYearMonthByDate($date);
        $temp_table_name = $table_name . '_' .  $ym;

        if ($this->utils->table_really_exists($temp_table_name)) {
            $ym_table_name = $temp_table_name;
        }

        return $ym_table_name;
    }
}

/* End of file Iovation_logs.php */
/* Location: ./application/models/iovation_logs.php */
