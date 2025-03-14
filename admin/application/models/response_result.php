<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * save to
 * response_results and resp_<YYYYmm>
 * if set config write_long_response_file_in_dir is not empty , then save file to `write_long_response_file_in_dir`
 *
 * config: enabled_new_resp_table=true, disable_response_result=false, enabled_resp_sync_table, disabled_response_results_table_only=false
 * enabled_write_resp_to_async_db, write_response_result_to_dir, max_allow_response_content
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Response_result extends BaseModel {

	private $today;
	private $month;

	function __construct() {
		parent::__construct();

		$this->today=date('Ymd');
		$this->month=date('Ym');
	}

	protected $tableName = "response_results";

	const FLAG_NORMAL = 1;
	const FLAG_ERROR = 2;

	const STATUS_ERROR=2;

	/**
	 * overview : get error result
	 *
	 * @param DateTime $dateTimeFrom
	 * @param DateTime $dateTimeTo
	 * @return array
	 */
	public function getErrorResult(\DateTime $dateTimeFrom, \DateTime $dateTimeTo) {
		$this->db->from($this->tableName)
			->where('created_at >=', $this->utils->formatDateTimeForMysql($dateTimeFrom))
			->where('created_at <=', $this->utils->formatDateTimeForMysql($dateTimeTo))
			->where('flag', self::FLAG_ERROR);

		return $this->runMultipleRow();
	}

	/**
	 * overview : save response result for file
	 *
	 * @param integer $systemTypeId
	 * @param integer $flag
	 * @param string $requstApi
	 * @param string $requestParams
	 * @param string $resultFilePath
	 * @param array $fields
	 * @return array
	 */
	public function saveResponseResultForFile($systemTypeId, $flag, $requstApi, $requestParams, $resultFilePath, $fields = null) {

		$insertData = array(
			"system_type_id" => $systemTypeId,
			"flag" => $flag,
			"filepath" => $resultFilePath,
			"request_api" => $requstApi,
			"request_params" => $requestParams,
			"created_at" => $this->utils->getNowForMysql(),
		);
		if (!empty($fields)) {
			$insertData = array_merge($insertData, $fields);
		}
		$this->db->insert($this->tableName, $insertData);
		return $this->db->insert_id();
	}

	public function writeContentToResponseFile($write_response_result_to_dir, $systemTypeId, $dataArr, $status) {

		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		$content=json_encode($dataArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		//add hours
		$dateDir = $write_response_result_to_dir."/".$this->utils->getAppPrefix()."/" . date('Y-m-d_H');
		$dir = $dateDir . "/" . $systemTypeId . "/";
		//create dir
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
			//chmod
			@chmod($dateDir, 0777);
			@chmod($dir, 0777);
		}
		$filename = $this->utils->getDatetimeNow() . "_" . random_string('alnum', 8) . ".json";
		$f = $dir . $filename;
		file_put_contents($f, $content);
		$this->utils->debug_log('write resp to file', $f);

		if($this->utils->getConfig('enabled_resp_sync_table')){
			$enabled_new_resp_table=$this->utils->getConfig('enabled_new_resp_table');
			//write to table
			$respSyncTable=$this->initRespSyncTable();
			$data=[
				'external_system_id'=>$systemTypeId,
				'response_result_id'=>$dataArr['response_result_id'],
				'status'=>$status,
				'created_at'=>$this->utils->getNowForMysql(),
				'filepath'=>$dir . $filename,
			];
			$data['external_request_id']=isset($dataArr['external_request_id']) ? $dataArr['external_request_id'] : null;
			$data['request_api']=$dataArr['request_api'];
			//encode again for json field
			if($enabled_new_resp_table){
				$data['request_params']=json_encode($dataArr['request_params']);
			}else{
				$data['request_params']=$dataArr['request_params'];
			}

			$data['status_code']=isset($dataArr['status_code']) ? $dataArr['status_code'] : null;
			// $data['status_text']=$dataArr['status_text'];
			$data['player_id']=isset($dataArr['player_id']) ? $dataArr['player_id'] : null;
			$data['related_id1']=isset($dataArr['related_id1']) ? $dataArr['related_id1'] : null;
			$data['related_id2']=isset($dataArr['related_id2']) ? $dataArr['related_id2'] : null;
			$data['related_id3']=isset($dataArr['related_id3']) ? $dataArr['related_id3'] : null;
			$data['error_code']=isset($dataArr['sync_id']) ? $dataArr['sync_id'] : null;
			$data['cost_ms']=isset($dataArr['costMs']) ? $dataArr['costMs'] : $this->utils->getExecutionTimeToNow()*1000;
			$data['full_url']=isset($dataArr['full_url']) ? $dataArr['full_url'] : null;
			$data['request_id']=$dataArr['request_id'];
			$data['external_transaction_id']=isset($dataArr['external_transaction_id']) ? $dataArr['external_transaction_id'] : null;
			$id=$this->insertData($respSyncTable, $data);
		}

		return $dir . $filename;
	}

	public function initRespSyncTable(){
		$dateStr=date('Ymd');
		return $this->utils->initRespSyncTableByDate($dateStr);
	}

    public function getRespDB(){
		$respDB=$this->db;
		$dataType='resp';
		$asyncConfig=$this->_getAsyncDataConfig($dataType, ['now'=>new DateTime()]);
		$enabled_write_resp_to_async_db=$this->utils->getConfig('enabled_write_resp_to_async_db');
		if($enabled_write_resp_to_async_db && !empty($asyncConfig)){
			$respDB=$this->_getDBFromAsyncConfig($asyncConfig);
		}
		return $respDB;
	}

	/**
	 * Store Response Data into File/Partition Table, with initRespTable().
	 *
	 * Check write_response_result_to_dir of CI.Config
	 * If spec. write_response_result_to_dir
	 * 	then write Content into file.
	 * 	else into partition Table of database.
	 *
	 * The Partition Tables Name Rule,
	 * "resp_"+ Ymd, ex:"resp_20190621".
	 *
	 * @param integer $systemTypeId F.K. external_system.id
	 * @param integer $resultId F.K. response_results.id
	 * @param array $dataArr
	 * @param integer $status default self::STATUS_NORMAL,
	 *
	 * @return string|integer Path+Filename or insert_id for success, else void.
	 */
	public function saveResponseFile($systemTypeId, $resultId, $dataArr, $status=self::STATUS_NORMAL, $external_request_id=null, $not_save_response_result = false){

		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		$enabled_new_resp_table=$this->utils->getConfig('enabled_new_resp_table');

		$write_response_result_to_dir=$this->utils->getConfig('write_response_result_to_dir');
		$dataArr['response_result_id']=$resultId;
		$dataArr['request_id']=$this->utils->getRequestId();
		if(!empty($external_request_id)){
			$dataArr['external_request_id']=$external_request_id;
		}else{
			//get it from log class
	        $_log = &load_class('Log');
	        $dataArr['external_request_id']=$_log->_external_request_id;
		}
		if($enabled_new_resp_table){
			//decode to array
			$dataArr['request_params']=json_decode($dataArr['request_params'], true);
		}
		if(!empty($write_response_result_to_dir)){
			//write to file
			return $this->writeContentToResponseFile($write_response_result_to_dir, $systemTypeId, $dataArr, $status);
		}else{
			//only for debug
			$this->utils->debug_log('data arr of response result', $dataArr);
			// add access url to content
			$dataArr['access_url']=$this->uri->uri_string();
			$dataArr['hostname']=$this->utils->getHostname();
			$dataArr['domain']=isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			$dataArr['client_ip']=$this->utils->tryGetRealIPWithoutWhiteIP();
			$content='';
			if($enabled_new_resp_table){
				$content=json_encode($dataArr);
			}else{
				$content=json_encode($dataArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			}
			// unset($dataArr['access_url'], $dataArr['hostname'], $dataArr['domain'], $dataArr['client_ip']);

			$max_allow_response_content=$this->utils->getConfig('max_allow_response_content');
			if(strlen($content)>$max_allow_response_content){
				//ignore it
				$content=''; //substr($content,0,$max_allow_response_content);
				$this->utils->error_log('ignore content because reached max_allow_response_content', $max_allow_response_content);
			}

			// for cashier
			if($not_save_response_result){
				$respTable=$this->initRespCashierTable();
				$data=[
					'system_type_id'=>$systemTypeId,
					'content'=>$content,
					'status'=>$status,
					"flag" => $dataArr['flag'],
					'created_at'=>$this->utils->getNowForMysql()
				];
			}else{
				$respTable=$this->initRespTable();
				$data=[
					'external_system_id'=>$systemTypeId,
					'response_result_id'=>$resultId,
					'content'=>$content,
					'status'=>$status,
					'created_at'=>$this->utils->getNowForMysql()
				];
			}

			$data['external_request_id']=isset($dataArr['external_request_id']) ? $dataArr['external_request_id'] : null;
			if($enabled_new_resp_table){
				//add new data
				$data['request_api']=$dataArr['request_api'];
				//encode again for json field
				$data['request_params']=json_encode($dataArr['request_params']);
				$data['status_code']=isset($dataArr['status_code']) ? $dataArr['status_code'] : null;
				// $data['status_text']=$dataArr['status_text'];
				$data['player_id']=isset($dataArr['player_id']) ? $dataArr['player_id'] : null;
				$data['related_id1']=isset($dataArr['related_id1']) ? $dataArr['related_id1'] : null;
				$data['related_id2']=isset($dataArr['related_id2']) ? $dataArr['related_id2'] : null;
				$data['related_id3']=isset($dataArr['related_id3']) ? $dataArr['related_id3'] : null;
				$data['error_code']=isset($dataArr['sync_id']) ? $dataArr['sync_id'] : null;
				//try decode json
				if(isset($dataArr['resultText']) && !empty($dataArr['resultText'])){
					try{
						$resultText=$dataArr['resultText'];
						$rltArr=json_decode($resultText, true);
						if(!empty($rltArr)){
							$data['decode_result']=json_encode($rltArr);
						}
					}catch(Exception $e){
						$this->utils->error_log('decode/encode json error', $e);
					}
				}
				$data['cost_ms']=isset($dataArr['costMs']) ? $dataArr['costMs'] : $this->utils->getExecutionTimeToNow()*1000;
				$data['full_url']=isset($dataArr['full_url']) ? $dataArr['full_url'] : null;
				$data['request_id']=$dataArr['request_id'];
				$data['external_transaction_id']=isset($dataArr['external_transaction_id']) ? $dataArr['external_transaction_id'] : null;
			}
			$id=$this->insertData($respTable, $data);
			// $enabled_write_long_response_file=$this->utils->getConfig('enabled_write_long_response_file');
			$write_long_response_file_in_dir=$this->utils->getConfig('write_long_response_file_in_dir');
			// print log
			$this->utils->debug_log('write_long_response_file_in_dir', $write_long_response_file_in_dir);
			if(!empty($write_long_response_file_in_dir)){
				// write response to file too
				// sample: /var/game_platform/resp/og_onestop_staging/2023-07-25_15/244/20230725154231_1uj9cuJ2.json
				$this->writeContentToResponseFile($write_long_response_file_in_dir, $systemTypeId, $dataArr, $status);
			}

			//update response_results
			if(!empty($id) && !empty($resultId) && !$not_save_response_result){
				$this->db->set('filepath', $respTable.'.'.$id)->where('id', $resultId);
				$this->runAnyUpdate($this->tableName);
			}else if($not_save_response_result){
				$this->db->set('filepath', $respTable.'.'.$id)->where('id', $id);
				$this->runAnyUpdate($respTable);
			}
			
			unset($content);

			return $id;
		}

	}

	/**
	 * overview : save response result
	 *
	 * @param $systemTypeId
	 * @param $flag
	 * @param $requstApi
	 * @param $requestParams
	 * @param $resultText
	 * @param $statusCode
	 * @param $statusText
	 * @param $extra
	 * @param null $fields
	 * @param bool|false $dont_save_response_in_api
	 * @param string $external_request_id from outside api request
	 * @param int $costMs
	 * @param bigint $transfer_request_id
	 * @param array $proxySettings
	 * @return mixed
	 */
	public function saveResponseResult($systemTypeId // #1
		, $flag // #2
		, $requstApi // #3
		, $requestParams // #4
		, $resultText // #5
		, $statusCode // #6
		, $statusText // #7
		, $extra // #8
		, $fields = [] // #9
		, $dont_save_response_in_api = false  // #10
		, $external_request_id=null //11
		, $costMs=null //12
		, $transfer_request_id=null //13
		, $proxySettings=null //14
	) {

		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		//save to file
		$filepath = null;
		//save always
		$dont_save_response_in_api=false;
		// if (!$dont_save_response_in_api) {
		// 	$filepath = $this->utils->saveToResponseFile($systemTypeId, $resultText);
		// }
		if ($flag != self::FLAG_NORMAL && $flag != self::FLAG_ERROR) {
			$flag = self::FLAG_NORMAL;
		}
		//check $resultText
		if(is_array($resultText)){
			$resultText=json_encode($resultText);
		} else if (is_object($resultText)) {
			if($resultText instanceof \SimpleXMLElement){
				$resultText=$resultText->asXML();
			} else if (method_exists($resultText, '__toString')) {
				$resultText = $resultText->__toString();
			} else if (method_exists($resultText, 'toString')) {
				$resultText = $resultText->toString();
			} else if (method_exists($resultText, 'toJson')) {
				$resultText = json_encode($resultText->toJson());
			}
		} else if (is_bool($resultText)) {
			$resultText = $resultText ? 'true' : 'false';
		}

		$insertData = array(
			"system_type_id" => $systemTypeId,
			"flag" => $flag,
			"status_code" => $statusCode,
			"status_text" => $statusText,
			"request_api" => $requstApi,
			"request_params" => $requestParams,
			"extra" => $extra,
			"created_at" => $this->utils->getNowForMysql(),
			"request_id" => $this->utils->getRequestId(),
			"sync_id"=>0,
		);

		if(!is_array($fields)){
			$fields=[];
		}

		if (!empty($fields)) {
			$insertData = array_merge($insertData, $fields);
		}

		if(!empty($statusText)){
			// $this->utils->debug_log('got statusText', $statusText);
			//save curl code
			$arr=explode(':', $statusText);
			if(!empty($arr) && is_array($arr) && count($arr)>0){
				$errCode=$arr[0];
				$insertData['sync_id']=$errCode;
			}
		}

		$write_response_result_to_dir=$this->utils->getConfig('write_response_result_to_dir');

		// for cashier
		$checkRequstApi = ['deposit_response', 'withdrawal_response', 'deposit', 'withdrawal'];
		if(in_array($requstApi, $checkRequstApi)){
			$not_save_response_result = true;
		}else{
			$not_save_response_result = false;
		}
		$this->utils->debug_log('===not_save_response_result===', $not_save_response_result, $write_response_result_to_dir);

		if(!empty($write_response_result_to_dir)){
			$insertData['resultText']=$resultText;
			$insertData['costMs']=$costMs;
			// $insertData['resultText']=$resultText;
			$filepath=$this->saveResponseFile($systemTypeId, null, $insertData,
				$flag==self::FLAG_ERROR ? self::STATUS_ERROR : self::STATUS_NORMAL, $external_request_id, $not_save_response_result);
			unset($insertData['resultText']);
			unset($insertData['costMs']);
			$this->utils->debug_log('save reponse file to dir', $filepath);
		}
		$this->utils->debug_log('save result to ', $filepath, 'fields', count($fields), 'flag', $flag,
			'dont_save_response_in_api', $dont_save_response_in_api, 'status code', @$insertData['sync_id']);
		//update filepath
		$insertData["filepath"]=$filepath;
		$disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
		if(!$disabled_response_results_table_only && !$not_save_response_result){
			$this->db->insert($this->tableName, $insertData);
			$id = $this->db->insert_id();
			$this->utils->debug_log('save response result', $id);
		}else{
			//default id
			$id=1;
			$this->utils->debug_log('disabled_response_results_table_only', $disabled_response_results_table_only);
		}
		
		if(!empty($id)){

			if(!$dont_save_response_in_api){
				$insertData['resultText']=$resultText;
				$insertData['costMs']=$costMs;
			}

			if($flag==self::FLAG_ERROR){
				// if($systemTypeId == SMS_API){ #For SMS API
					// $insertData['resultText']=$resultText;
				// }
				$this->utils->error_log('error response result', $id, $flag);
				if(empty($write_response_result_to_dir)){
					$file_id=$this->saveResponseFile($systemTypeId, $id, $insertData,
						self::STATUS_ERROR, $external_request_id, $not_save_response_result);
					$this->utils->debug_log('save reponse file to db', $file_id);
					if($disabled_response_results_table_only){
						$id=$file_id;
					}
				}
			}else{
				if(!$dont_save_response_in_api){
					// $insertData['resultText']=$resultText;
					if(empty($write_response_result_to_dir)){
						$file_id=$this->saveResponseFile($systemTypeId, $id, $insertData,
							self::STATUS_NORMAL, $external_request_id, $not_save_response_result);
						$this->utils->debug_log('save reponse file to db', $file_id);
						if($disabled_response_results_table_only){
							$id=$file_id;
						}
					}
				}else{
					$this->utils->debug_log('ignore response result file', $id);
				}
			}
		}else{
			$file_id=$this->saveResponseFile($systemTypeId, $id, $insertData,
				self::STATUS_NORMAL, $external_request_id, $not_save_response_result);
			$this->utils->debug_log('save reponse file to db', $file_id);
		}
		unset($insertData);

		if($not_save_response_result){
			$id=$file_id;
		}

		return $id;
	}

	public function getResponseResultFileById($id, $table_name=null){
		$table_name= empty($table_name) ? $this->initRespTable() : $table_name ;
		$this->db->from($table_name)->where('id', $id);

		return $this->runOneRowArray();
	}

	public function getResponseResultFileByResultId($id, $table_name=null){
		$table_name= empty($table_name) ? $this->initRespTable() : $table_name ;
		$this->db->from($table_name)->where('response_result_id', $id);

		return $this->runOneRowArray();
	}

	public function copyResultFile($respRlt){
		if(!empty($respRlt)){

			$data=[
				'external_system_id'=>$respRlt['external_system_id'],
				'response_result_id'=>$respRlt['response_result_id'],
				'content'=>$respRlt['content'],
				'status'=>$respRlt['status'],
				'created_at'=>$respRlt['created_at'],
			];

			$table_name=$this->initRespTable();

			$row=$this->getResponseResultFileById($respRlt["id"], $table_name);
			if(empty($row)){
				//use old response id
				$data['id']=$respRlt["id"];
			}

			// if( !$this->db->table_exists( 'resp_'.date('Ymd') ) ){
			// 	$this->utils->debug_log('copyResultFile error table `'.'resp_'.date('Ymd').'` not exist ');
			// 	return false;
			// }

			return $this->insertData($table_name, $data);
		}
	}

	/**
	 * overview : set response result to error
	 *
	 * @param $responseResultId
	 * @return mixed
	 */
	public function setResponseResultToError($responseResultId) {
		$this->db->where('id', $responseResultId);
		return $this->db->update($this->tableName, array("flag" => self::FLAG_ERROR));
	}

	public function setResponseCashierResultToError($responseResultId) {
		$table = 'resp_cashier_'.date('Ym');
		$this->db->where('id', $responseResultId);
		return $this->db->update($table, array("flag" => self::FLAG_ERROR));
	}

	/**
	 * overview : get response result by id
	 *
	 * @param $id
	 * @return stdClass
	 */
	public function getResponseResultById($id){
		$this->db->from($this->tableName)->where('id', $id);

		return $this->runOneRow();
	}

	public function getResponseCashierResultById($id){
		$table = 'resp_cashier_'.date('Ym');
		$this->db->from($table)->where('id', $id);

		return $this->runOneRow();
	}
	/**
	 * overview : copy result
	 *
	 * @param $respRlt
	 * @return array
	 */
	public function copyResult($respRlt){
		$data=[
			"system_type_id" => $respRlt->system_type_id,
			"content" => $respRlt->content,
			"filepath" => $respRlt->filepath,
			"note" => $respRlt->note,
			"created_at" => $respRlt->created_at,
			"request_api" => $respRlt->request_api,
			"request_params" => $respRlt->request_params,
			"status_code" => $respRlt->status_code,
			"status_text" => $respRlt->status_text,
			"extra" => $respRlt->extra,
			"flag" => $respRlt->flag,
			"player_id" => $respRlt->player_id,
			"related_id1" => $respRlt->related_id1,
			"related_id2" => $respRlt->related_id2,
			"related_id3" => $respRlt->related_id3,
			"sync_id" => $respRlt->sync_id,
			"full_url" => $respRlt->full_url,
			"external_transaction_id" => $respRlt->external_transaction_id,
		];

		$row=$this->getResponseResultById($respRlt->id);
		if(empty($row)){
			//use old response id
			$data['id']=$respRlt->id;
		}

		return $this->insertData($this->tableName, $data);
	}

    public function copyCashierResult($respRlt){
        if($this->utils->getConfig('disable_response_result')){
			return null;
		}
		$data=[
			"system_type_id" => $respRlt->system_type_id,
			"content" => $respRlt->content,
			"filepath" => $respRlt->filepath,
			"note" => $respRlt->note,
			"created_at" => $respRlt->created_at,
			"request_api" => $respRlt->request_api,
			"request_params" => $respRlt->request_params,
			"status_code" => $respRlt->status_code,
			"status_text" => $respRlt->status_text,
			"extra" => $respRlt->extra,
			"flag" => $respRlt->flag,
			"player_id" => $respRlt->player_id,
			"related_id1" => $respRlt->related_id1,
			"related_id2" => $respRlt->related_id2,
			"related_id3" => $respRlt->related_id3,
			"sync_id" => $respRlt->sync_id,
			"external_transaction_id" => $respRlt->external_transaction_id,
			"full_url" => $respRlt->full_url,
            "request_id" => $respRlt->request_id,
            "cost_ms" => $respRlt->cost_ms,
            "external_request_id" => $respRlt->external_request_id,
            "status" => $respRlt->status,
            "error_code" => $respRlt->error_code,
            "decode_result" => $respRlt->decode_result,
		];

		$row=$this->getResponseResultById($respRlt->id);
		if(empty($row)){
			//use old response id
			$data['id']=$respRlt->id;
		}
        $currentMonthByCreateAt = empty($respRlt->created_at) ? date('Ym') : date('Ym', strtotime($respRlt->created_at));
        $tableName =  $this->utils->initRespCashierTableByMonth($currentMonthByCreateAt);

		return $this->insertData($tableName, $data);
    }

    public function updateCashierResultFilepath($new_response_result_id, $respRlt){
        $currentMonthByCreateAt = empty($respRlt->created_at) ? date('Ym') : date('Ym', strtotime($respRlt->created_at));
        $respTable = $this->utils->initRespCashierTableByMonth($currentMonthByCreateAt);

        $filepath = $respTable.'.'.$new_response_result_id;
        $this->db->where('id', $new_response_result_id);
        return $this->db->update($respTable, array("filepath" => $filepath));
    }

	/**
	 * overview : get response result information by id
	 *
	 * @param $id
	 * @return array
	 */
	public function getResponseResultInfoById($id){
		$this->db->from($this->tableName)->where('id', $id);

		return $this->runOneRowArray();
	}

	/**
	 * Get record form  `resp_XXX` and `response_results`.`id`
	 *
	 * @param array $id The string, resp_XXX+ "."+ `response_results`.`id` .
	 */
	public function getRespResultByTableField($id){
		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		$arr=explode('.', $id);
		if(count($arr)>1){
			$tableName=$arr[0];
			$idValue=$arr[1];
			if(count($arr)==3){
				//exists db name
				$tableName=$arr[0].'.'.$arr[1];
				$idValue=$arr[2];
			}
			$dataType='resp';
			$asyncConfig=$this->_getAsyncDataConfig($dataType, ['now'=>new DateTime()]);
			$this->utils->debug_log('asyncConfig of '.$dataType, $asyncConfig);
			$enabled_write_resp_to_async_db=$this->utils->getConfig('enabled_write_resp_to_async_db');
			//get resp db
			$respDB=$this->getRespDB();
			$respDB->from($tableName)->where('id', $idValue);
			return $this->runOneRowArray($respDB);

		}
		return null;
	}

	public function updateResponseResultContentByFilepath($filepath, $content) {
		$arr = explode('.', $filepath);
		if(count($arr) == 2){
			$this->db->where('id', $arr[1]);
			return $this->db->update($arr[0], array("content" => $content));
		}
		if(count($arr) == 3){
			$table = $arr[0].".".$arr[1];
			$id = $arr[2];
			$this->db->where('id', $id);
			return $this->db->update($table, array("content" => $content));
		}

		return null;
	}

	public function initRespTable(){
		return $this->utils->initRespTableByDate($this->today);
	}

	public function initRespCashierTable(){
		return $this->utils->initRespCashierTableByMonth($this->month);
	}

	public function getMinCreatedAt(){
		$this->db->select_min('created_at')->from($this->tableName);

		return $this->runOneRowOneField('created_at');
	}

	public function getRespFileInfoByTableField($id){

		$row=$this->getRespResultByTableField($id);

		if(!empty($row)){

			$this->load->model(['player_model', 'external_system']);

			$row['content']=json_decode($row['content'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
			if(is_array($row['content']['request_params'])){
				$row['content']['decode_request_params']=$row['content']['request_params'];
			}else if(is_string($row['content']['request_params'])){
				$row['content']['decode_request_params']=json_decode($row['content']['request_params'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
			}else{
				$row['content']['decode_request_params']=$row['content']['request_params'];
			}
			// $row['content']['request_api'] = $row['content']['request_api']; // Patch for OGP-12954 在 payment api callback request 內加上可重複發送的機制.
			$row['content']['player']=$this->player_model->getUsernameById(@$row['content']['player_id']);
			$row['content']['system']=$this->external_system->getNameById($row['content']['system_type_id']);
		}

		return $row;
	}

	public function checkResendCallbackExists ($systemId, $orderId, $reSendBySecureId) {

        $query = $this->db->select('id')->from($this->tableName)
			->where('system_type_id', $systemId)
			->where('related_id1', $orderId)
			->where('extra', 'reSendBySecureId='.$reSendBySecureId);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return TRUE;
		} else {
			$this->utils->error_log('resend callback does not exist');
			return FALSE;
		}
	}

	/**
	 * update Data in Response Result
	 *
	 * @param int $id
	 * @param array $content
	 * @param int $playerId
	 * @param int $flag
	 *
	 * @return boolean
	 */
    public function updateResponseResultCommonData($id,$content=[],$playerId=null,$flag=2)
    {
		$content = $content;
		//$this->utils->debug_log('updateResponseResultCommonData content', $content);
        $this->db->where('id', $id)
            ->set([
				'content' => $content,
				'player_id' => $playerId,
				'flag' => $flag
            ]);

        return $this->runAnyUpdate($this->tableName);
    }

	public function readNewResponseById($id){
		$table_name=$this->initRespTable();
		$this->db->from($table_name)->where('id', $id);
		return $this->runOneRowArray();
	}

	public function copyNewResponse($respRlt){
		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		if(!empty($respRlt)){
			$table_name=$this->initRespTable();
			unset($respRlt['id']);
			return $this->runInsertData($table_name, $respRlt);
		}
	}

	public function updateNewResponse($respRlt){
		if($this->utils->getConfig('disable_response_result')){
			return null;
		}

		if(!empty($respRlt)){
			$table_name=$this->initRespTable();
			$id = $respRlt['id'];
			unset($respRlt['id']);
			// return $this->runUpdateData($table_name, $respRlt);
			return $this->updateData('id', $id, $table_name, $respRlt);
		}
	}

	public function updateResponseResultSpecificData($response_result_id, $data = [])
    {
		$this->db->where('id', $response_result_id);

		return $this->db->update($this->tableName, $data);
	}

	public function getOrderIdFormResp($target, $tableName,$systemId=null){
		$respDB=$this->getRespDB();
		$respDB->from($tableName)->where('request_api', 'deposit_response')->where('external_system_id', $systemId)->like('content',$target)->order_by('id', 'DESC')->limit(1);
		$response_result_id = "";
		$getDB=$this->runOneRowArray($respDB);
		if(!empty($getDB)){
			$response_result_id=$getDB['response_result_id'];
		}else{
			$this->utils->error_log('getOrderIdFormResp null');
			return "";
		}
		$get_response_result = $this->getResponseResultFileById($response_result_id,'response_results');
		if(!empty($get_response_result)){
			$order_id = $get_response_result['related_id1'];
			$this->utils->debug_log('===================== getOrderIdFormResp order_id',$order_id);
			return $order_id;
		}
		return "";
}
}
