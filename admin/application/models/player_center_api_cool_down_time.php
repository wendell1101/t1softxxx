<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Cloned from Dispatch_withdrawal_results
 *
 */
class Player_center_api_cool_down_time extends BaseModel {
	protected $tableName = 'player_center_api_cool_down_time';

	protected $settingListOfConfigure = [];

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get the setting of the Config by the class and the method.
	 *
	 * @param string $class The class name
	 * @param string $method The method name
	 * @return array The element of the setting, "player_center_api_cool_down_time" of the configure.
	 */
	public function getMatchedSettingFromConfig($class, $method){
		$matched = [];
		if( empty($this->settingListOfConfigure) ){
			$this->settingListOfConfigure = $this->utils->getConfig('player_center_api_cool_down_time');
		}

		$matched_list = array_filter($this->settingListOfConfigure, function($v, $k) use ($class, $method){
			// $v, $k
			return $v['class'] == $class && $v['method'] == $method;
		}, ARRAY_FILTER_USE_BOTH);
		$matched_list = array_values($matched_list); // resort index
		if( ! empty($matched_list) ){
			$matched = $matched_list[0];
		}
		return $matched;
	} // EOF getMatchedSettingFromConfig

	/**
	 * Update the created_at for renew the data for cool down time referenced.
	 *
	 * @param string $class The class name
	 * @param string $method The method name
	 * @param string $username The field, "player.username".
	 * @param integer $cool_down_sec The cool down time.
	 * @return integer $renewid The P.K., affected id.
	 */
	public function renewlog($class, $method, $username, $cool_down_sec){
		$renewid = null;
		$cache_key = $this->gen_cache_key($class, $method, $username, $cool_down_sec);
		$row = $this->getLatestRowByCache_key($cache_key);
		if( empty($row) ){
			$inserted_id = $this->log($class, $method, $username, $cool_down_sec);
			$renewid = $inserted_id;
		}else{
			$data = [];
			$data['created_at'] = $this->utils->getNowForMysql();
			$rlt = $this->update($row['id'], $data );
			if( !empty($rlt) ){
				$affected_id = $row['id'];
				$renewid = $affected_id;
			}
		}
		$this->utils->debug_log('OGP-25476.61.renewid:', $renewid
						, "inserted_id", (empty($inserted_id)? null: $inserted_id)
						, "affected_id", (empty($affected_id)? null: $affected_id)
				);
		return $renewid;
	} // EOF renewlog

	/**
	 * log the data for cool down time referenced.
	 *
	 * @param string $class The class name
	 * @param string $method The method name
	 * @param string $username The field, "player.username".
	 * @param integer $cool_down_sec The cool down time.
	 * @return integer The P.K., inserted id.
	 */
	public function log($class, $method, $username, $cool_down_sec){
		$params = [];
		$params['class'] = $class;
		$params['method'] = $method;
		$params['username'] = $username;
		$params['cool_down_sec'] = $cool_down_sec;
		$cache_key = $this->gen_cache_key($class, $method, $username, $cool_down_sec);
		$params['cache_key'] = $cache_key;

		return $this->add($params);
	}// EOF log

	/**
	 * Generate the field, "cache_key".
	 *
	 * @param string $class The name of the class. Usually from $this->CI->router->class .
	 * @param string $method The method name of the class. Usually from $this->CI->router->method .
	 * @param string $username The field, "player.username".
	 * @param integer $cool_down_sec The cool down sec.
	 * @return string The value of the field, "cache_key".
	 */
	public function gen_cache_key($class, $method, $username, $cool_down_sec){
		$cache_key_element_list = [];
		$cache_key_element_list[] = $class;
		$cache_key_element_list[] = $method;
		$cache_key_element_list[] = $username;
		$cache_key_element_list[] = $cool_down_sec;
		$cache_key = implode('-', $cache_key_element_list);
		return $cache_key;
	}// EOF gen_cache_key

	/**
	 * Parse the string, cache_key to the info. "class", "method", "username", "cool_down_sec".
	 *
	 * @param string $cache_key
	 * @return array The array is mapping to "class", "method", "username", "cool_down_sec" by key.
	 */
	public function parse_cache_key($cache_key){
		$cache_key_info = [];
		list($class, $method, $username, $cool_down_sec) = explode('-', $cache_key);
		return [$class, $method, $username, $cool_down_sec];
	}// EOF parse_cache_key

	/**
	 * Get Exceeded Cool Down Rows by cache_key
	 *
	 * @param string $cache_key The cache_key is combined by class, method, username, cool_down_sec
	 * @param string $currDatetime The currect the date time.
	 * @return array
	 */
	public function getLatestRowByCache_key($cache_key, $currDatetime = 'now'){
		list($class, $method, $username, $cool_down_sec) = $this->parse_cache_key($cache_key);
		$currDT = new DateTime($currDatetime);

		$params = [];
		$tableName = $this->tableName;
		$sql = <<<EOF
		SELECT *
		, DATE_ADD(created_at, INTERVAL $cool_down_sec second) AS cool_down_datetime
		FROM $tableName
		WHERE cache_key = "$cache_key"
		ORDER BY created_at DESC
		LIMIT 1
EOF;
		$qry = $this->db->query($sql, $params);
		$row = $this->getOneRowArray($qry);
		unset($qry);

		if( ! empty($row) ){
			$cool_downDT = new DateTime($row['cool_down_datetime']);
			$cool_down_timestamp = $cool_downDT->getTimestamp();
			$curr_timestamp = $currDT->getTimestamp();
			if( $cool_down_timestamp < $curr_timestamp ){
				// The data is over cool down time
				$row['is_in_cool_down'] = false;
			}else if( $cool_down_timestamp >= $curr_timestamp ){
				// Got the data, that is in cool down time
				// it means should disallow response
				$row['is_in_cool_down'] = true;
			}else{
				// default, ignore the cool down time
				$row['is_in_cool_down'] = false;
			}
		}else{
			// not found, it means the first called by cache_key.
		}

        return $row;
	}// EOF getLatestRowByCache_key



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
	 * clear cooldown expired data
	 *
	 * @param string $class
	 * @param [type] $method
	 * @param integer $cool_down_sec
	 * @param [type] $affected_rows
	 * @return void
	 */
	public function clear_cooldown_expired($class, $method, $cool_down_sec = 60, &$affected_rows = null){

		$table = $this->tableName;
		$cool_down_sec += 1; // 1 sec for flexible partition

		$sql_select_prefix = <<<EOF
SELECT `id`
, `created_at`
, $cool_down_sec AS `cool_down_sec`
EOF;
		$sql_delete_prefix = <<<EOF
DELETE
EOF;
		$sql_formater = <<<EOF
		%s
		FROM {$table}
		WHERE created_at < DATE_SUB(NOW(), INTERVAL $cool_down_sec second)
		AND class = '$class'
		AND method = '$method'
EOF;

		$sql_select = sprintf($sql_formater, $sql_select_prefix);
		$qry = $this->db->query($sql_select, []);
		$affected_rows = $this->getMultipleRowArray($qry);

		$sql_delete = sprintf($sql_formater, $sql_delete_prefix);
		$q=$this->db->query($sql_delete);
		unset($q);
		$affected_count = count($affected_rows);
		return $affected_count;
	} // EOF clear_cooldown_expired




} // EOF Player_accumulated_amounts_log
