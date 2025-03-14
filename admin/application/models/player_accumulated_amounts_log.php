<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Cloned from Dispatch_withdrawal_results
 *
 */
class Player_accumulated_amounts_log extends BaseModel {
	protected $tableName = 'player_accumulated_amounts_log';

    // about the field, accumulated_type for related amount
    const ACCUMULATED_TYPE_BET = 'bet';
    const ACCUMULATED_TYPE_DEPOSIT = 'deposit';
    const ACCUMULATED_TYPE_WIN = 'win';
    const ACCUMULATED_TYPE_LOSS = 'loss';


    // about the field, query_token
    const QUERY_TOKEN_BET_AMOUNT = 'bet_amount';
    const QUERY_TOKEN_DEPOSIT_AMOUNT = 'deposit_amount';
    const QUERY_TOKEN_IN_LEVEL = 'in_level_%s'; // params: player.levelId ={F.K.}=> vipsettingcashbackrule.vipsettingcashbackruleId
    // const QUERY_TOKEN_IS_BET_MET_WITH_LEVEL = 'is_bet_met_level_%s'; // params: player.levelId ={F.K.}=> vipsettingcashbackrule.vipsettingcashbackruleId
    // const QUERY_TOKEN_IS_DEPOSIT_MET_WITH_LEVEL = 'is_deposit_met_level_%s'; // params: player.levelId ={F.K.}=> vipsettingcashbackrule.vipsettingcashbackruleId

    // for QUERY_TOKEN_IS_BET_MET_WITH_LEVEL and QUERY_TOKEN_IS_DEPOSIT_MET_WITH_LEVEL
    const AMOUNT_IS_MET = 1;
    const AMOUNT_IS_NOT_MET = 0;

    const IS_MET_YES = 1;
    const IS_MET_NO = 0;

    // 'is_met' => array(
    //     'type' => 'INT',
    //     'null' => true,
    // ),
    // 'is_met_by_log_id' => array(
    //     'type' => 'BIGINT',
    //     'null' => true,
    // ),

	public function __construct() {
		parent::__construct();
	}

    /**
     * Log the data for referenced in upgrade level checking
     *
     * @param integer $player_id The player.plauerId field.
     * @param float $amount
     * @param string $accumulated_type
     * @param string $query_token
     * @param string $begin_datetime The datetime, format: "Y-m-d H:i:s".
     * @param string $end_datetime The datetime, format: "Y-m-d H:i:s".
	 * @param integer $is_met The field,"is_met"
	 * @param string $time_exec_begin The specified time for the fields,"created_at" and "updated_at".
     * @return integer The PK. id of inserted / effected data.
     */
	public function log_accumulated_amount( $player_id // #1
                                            , $amount // #2
                                            , $accumulated_type // #3
                                            , $query_token // #4
                                            , $begin_datetime // #5
                                            , $end_datetime // #6
                                            , $is_met = null // #7
											, $time_exec_begin = 'now' // #8
    ){

		$this->load->library('group_level_lib');
		$force_created_at_delay_by_request_time_sec_in_log_accumulated_amount = $this->utils->getConfig('force_created_at_delay_by_request_time_sec_in_log_accumulated_amount');
		$force_updated_at_delay_by_request_time_sec_in_log_accumulated_amount = $this->utils->getConfig('force_updated_at_delay_by_request_time_sec_in_log_accumulated_amount');
		if($force_created_at_delay_by_request_time_sec_in_log_accumulated_amount !== null){
			$_created_at = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_created_at_delay_by_request_time_sec_in_log_accumulated_amount);
		}else{
			$_created_at = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, 0);
		}
		if($force_updated_at_delay_by_request_time_sec_in_log_accumulated_amount !== null){
			$_updated_at = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_updated_at_delay_by_request_time_sec_in_log_accumulated_amount);
		}else{
			$_updated_at = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, 0);
		}

		$this->db->select('id')
			->from($this->tableName)
			->where('player_id', $player_id);
		$this->db->where('begin_datetime', $begin_datetime);
		$this->db->where('end_datetime', $end_datetime);
		$this->db->where('accumulated_type', $accumulated_type);
		$this->db->where('amount', $amount);
        $this->db->where('query_token', $query_token);

        if( $is_met !== null){
            $this->db->where('is_met', $is_met);
        }else{
            $this->db->where('is_met IS NULL', null);
        }



		$rowList = $this->runMultipleRowArray();

		$log_id = 0;
		$row = [];
		if( ! empty($rowList) ){
			$row = $rowList[0];
		}

		if( ! empty($row) ){
			$log_id = $row['id'];
		}

		$data = [];

        if( $is_met !== null){
            $data['is_met'] = $is_met;
        }

		if( !empty($log_id) ){
			// update "updated_at" field.
			$data['updated_at'] = $_updated_at;
			$this->update($log_id, $data);
		}else{
			// insert
			$data['player_id'] = $player_id;
			$data['begin_datetime'] = $begin_datetime;
			$data['end_datetime'] = $end_datetime;
			$data['accumulated_type'] = $accumulated_type;
            $data['query_token'] = $query_token;
			$data['amount'] = $amount;
			$data['created_at'] = $_created_at;
			$data['updated_at'] = $_created_at; // created_at and updated_at should be the same when insert.
			$log_id = $this->add($data); // insert_id
		}

		return $log_id;
	} // log_accumulated_amount




	/**
	 * Add a record
	 *
	 * @param array $params the fields of the table,"dispatch_withdrawal_results".
	 * @return void
	 */
	public function add($params) {
		$data = [];
		$data = array_merge($data, $params);
		return $this->insertRow($data);
	} // EOF add

	/**
	 * Update record by id
	 *
	 * @param integer $id
	 * @param array $data The fields for update.
	 * @return boolean|integer The affected_rows.
	 */
	public function update($id, $data = array() ) {

		return $this->updateRow($id, $data);
	} // EOF update

	/**
	 * Delete a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return boolean If true means delete the record completed else false means failed.
	 */
	public function delete($id){
		$this->db->where('id', $id);
		return $this->runRealDelete($this->tableName);
	} // EOF delete

	/**
	 * Get the rows by walletAccountId
	 *
	 * @param integer $conditions_id The field,"walletaccount.walletAccountId".
	 * @return array The rows.
	 */
    /**
     * Get the rows by player and query token in interval.
     *
     * @param integer $player_id The player.playerId field.
     * @param string $query_token The keyword for search condition.
     * @param string $beginDatetine The begin datatime for search condition.
     * @param string $endDatetime The end datatime for search condition.
     * @param integer $is_met The is_met field for search condition.
     * @param null|integer $limit The limit of the result rows.
     * @return array The rows
     */
	public function getDetailListByPlayerIdAndQueryToken($player_id, $query_token, $accumulated_type, $beginDatetine, $endDatetime = '', $is_met = null, $limit = 5){

        $this->db->select('*')
            ->from($this->tableName)
            ->where('player_id', $player_id);
        $this->db->where('updated_at >=', $beginDatetine);
        if(! empty($endDatetime) ){
            $this->db->where('updated_at <=', $endDatetime);
        }
        $this->db->where('query_token', $query_token);
        $this->db->where('accumulated_type', $accumulated_type);

        if($is_met !== null){
            $this->db->where('is_met', $is_met);
        }

        $this->db->order_by('updated_at', 'desc');

        if($limit !== null){
            $this->db->limit($limit);
        }

        $rowList = $this->runMultipleRowArray();

        return $rowList;
	}// EOF getDetailListByPlayerIdAndQueryToken

} // EOF Player_accumulated_amounts_log
