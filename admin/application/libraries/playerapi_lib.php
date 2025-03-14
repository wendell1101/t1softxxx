<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * playerapi_lib
 *
 * @property Playerapi $ci
 */
class playerapi_lib {
	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library([ 'utils', 'language_function']);
		$this->ci->load->model(['player_model', 'sale_order', 'sale_orders_notes', 'walletaccount_notes', 'wallet_model',
								'playerbankdetails', 'financial_account_setting', 'player_attached_proof_file_model',
								'operatorglobalsettings','registration_setting']);
	}

	public function validParmasBasic($request_params, $validate_fields) {
		$validate_result = ['validate_flag' => true, 'validate_msg' => ''];
		// $this->ci->utils->debug_log(__METHOD__, '=======request_params', $request_params);
		$required_fields = [];
		foreach ($validate_fields as $key => $validate_item) {
			if($validate_item['required'] == true) {
				$required_fields[] = $validate_item['name'];
			}
		}
		$validate_require_result = $this->validateRequiredParams($request_params, $required_fields);
		if($validate_require_result['validate_flag'] == false) {
			return $validate_require_result;
		}
		$validate_type_result = $this->valiteParamsType($request_params, $validate_fields);
		if($validate_type_result['validate_flag'] == false) {
			return $validate_type_result;
		}
		return $validate_result;
	}

	public function validateRequiredParams($request_params, $required_fields) {
		$validate_flag = true;
		$validate_msg = 'Missing params: ';
		// $this->ci->utils->debug_log(__METHOD__, '=======request_params', $request_params);
		foreach ($required_fields as $field_key) {
			if (!array_key_exists($field_key, $request_params) && !array_key_exists($field_key, $_FILES)) {
				$validate_msg .= $field_key.', ';
				$this->ci->utils->debug_log(__METHOD__, '=======Missing required params', $field_key);
				$validate_flag = false;
			}
		}
		$validate_msg = substr($validate_msg, 0, -2);
		if($validate_flag) $validate_msg = '';

		$validate_result = array(
			'validate_flag' => $validate_flag,
			'validate_msg' => $validate_msg
		);

		return $validate_result;
	}

	public function valiteParamsType($request_params, $validate_fields) {
		$validate_flag = true;
		$validate_msg = 'Params type does not match: ';
		foreach ($validate_fields as $key => $validate_item) {
			if(is_null($validate_item['name']) && is_array($request_params)) {
				foreach ($request_params as $param_key => $param_value) {
					if(is_string($param_key)) {
						$this->ci->utils->debug_log(__METHOD__, '=======compare expected type = false. request_params key: ', $validate_item['name'], 'should not exist.');
						$validate_msg .= 'param key {'.$param_key.'} should not exist.';
						$validate_flag = false;
					}
					else if( $this->compareParamMatchExpectedType($param_value, $validate_item['type']) == false ) {
						$this->ci->utils->debug_log(__METHOD__, '=======compare expected type = false. param value: ', $param_value, 'expected_type', $validate_item['type']);
						$validate_msg .= json_encode($param_value).' is not '. $validate_item['type'].'. ';
						$validate_flag = false;
					}
				}
			}
			else if( !empty($request_params[$validate_item['name']]))  {
			// if( (!isset($request_params[$validate_item['name']]) || !$request_params[$validate_item['name']]) == false ) {
				if( $this->compareParamMatchExpectedType($request_params[$validate_item['name']], $validate_item['type']) == false ) {
					$this->ci->utils->debug_log(__METHOD__, '=======compare expected type = false. request_params name: ', $validate_item['name'], 'param value', $request_params[$validate_item['name']], 'expected_type', $validate_item['type']);
					$validate_msg .= '{'. $validate_item['name'].'} should be '. $validate_item['type'].'. ';
					$validate_flag = false;
				}
			}
			else if( isset($request_params[$validate_item['name']]) ) {
				if( ($request_params[$validate_item['name']] == 0) || ($request_params[$validate_item['name']] == '0') ) {
					if( $this->compareParamMatchExpectedType($request_params[$validate_item['name']], $validate_item['type']) == false ) {
						$this->ci->utils->debug_log(__METHOD__, '==========compare param with zero expected type = false. request_params name: ', $validate_item['name'], 'param value', $request_params[$validate_item['name']], 'expected_type', $validate_item['type']);
						$validate_msg .= '{'. $validate_item['name'].'} should be '. $validate_item['type'].'. ';
						$validate_flag = false;
					}
				}
			}

			if (isset($validate_item['allowed_content'])) {
				$paramName = $validate_item['name'];

				if (!$validate_item['required'] && (!isset($request_params[$paramName]) || $request_params[$paramName] === "")) {
					// No need to check allowed content for un-required fields without passing this param
				} else {
					if(is_array($request_params[$paramName])){
						if(!empty(array_diff($request_params[$paramName], $validate_item['allowed_content']))){

							$this->ci->utils->debug_log(
								__METHOD__,
								'==========compare param with fit allowed_content = false. request_params name: ',
								$paramName,
								'param value',
								$request_params[$paramName],
								'allowed_content',
								$validate_item['allowed_content']
							);
							$validate_msg .= '{' . $paramName . '} value should be ' . implode(" or ", $validate_item['allowed_content']) . '. ';
							$validate_flag = false;
						}

					}elseif(!in_array($request_params[$paramName], $validate_item['allowed_content'])){
						$this->ci->utils->debug_log(
							__METHOD__,
							'==========compare param with fit allowed_content = false. request_params name: ',
							$paramName,
							'param value',
							$request_params[$paramName],
							'allowed_content',
							$validate_item['allowed_content']
						);

						$validate_msg .= '{' . $paramName . '} value should be ' . implode(" or ", $validate_item['allowed_content']) . '. ';
						$validate_flag = false;
					}
				}
			}
		}
		$validate_msg = substr($validate_msg, 0, -2);
		if($validate_flag) $validate_msg = '';

		$validate_result = array(
			'validate_flag' => $validate_flag,
			'validate_msg' => $validate_msg
		);

		return $validate_result;
	}

	public function getRequestPramas() {
		$request_method = $this->ci->input->server('REQUEST_METHOD');
		$request_body = [];

		if($request_method == 'GET') {
			$request_body = $this->ci->input->get();
		}
		else if($request_method == 'POST') {
			$request_body = $this->ci->input->post();
			if(empty($request_body)) {
				$request_body = json_decode(file_get_contents('php://input'), true);
			}
		}
		if(empty($request_body)) $request_body =[];

		return $request_body;
	}

	public function loopCurrencyForAction($current_currency, callable $callbakcable) {
		$this->ci->utils->debug_log(__METHOD__,'current_currency : ', $current_currency);
		$res = [];

		if($this->ci->utils->isEnabledMDB()) {
			$multiple_databases=$this->ci->utils->getConfig('multiple_databases');
			if(!empty($multiple_databases)){
				$keys=array_keys($multiple_databases);
				$excludeList=$excludeList=[Multiple_db::SUPER_TARGET_DB];
				$inactive_currency_for_player_center = $this->ci->utils->getConfig('inactive_currency_for_player_center');
				if (!empty($inactive_currency_for_player_center)) {
					$excludeList = array_merge($excludeList, $inactive_currency_for_player_center);
				}
				foreach ($keys as $dbKey) {
					if(!empty($excludeList) && in_array($dbKey, $excludeList)){
						$this->ci->utils->debug_log('ignore db : '.$dbKey, $excludeList);
						continue;
					}
					if(!empty($dbKey)) {
						$_multiple_db=Multiple_db::getSingletonInstance();
						$_multiple_db->switchCIDatabase(strtolower($dbKey));

						$this->ci->utils->debug_log(__METHOD__,'callbakcable dbKey : ', $dbKey);
						$res[] = $callbakcable($dbKey);
					}
				}
				$_multiple_db=Multiple_db::getSingletonInstance();
				$_multiple_db->switchCIDatabase(strtolower($current_currency));
				return $res;
			}
		}
		else {
			return $res;
		}
	}

	public function convertDateTimeToMysql($dateStr, $datetime_format = 'Y-m-d H:i:s'){
        $dateStr = date($datetime_format, strtotime($dateStr));
        $d = DateTime::createFromFormat($datetime_format, $dateStr);
        $mysql_time = $this->ci->utils->formatDateTimeForMysql($d);
        return $mysql_time;
    }

	public function switchCurrencyForAction($currency_target_db, callable $callbakcable) {
		if($this->ci->utils->isEnabledMDB()) {
			if(!empty($currency_target_db)) {
				$_multiple_db=Multiple_db::getSingletonInstance();
				$_multiple_db->switchCIDatabase(strtolower($currency_target_db));
				return $callbakcable();
			}
			else {
				$default_currency = $this->ci->config->item('fallback_target_db');
				$_multiple_db=Multiple_db::getSingletonInstance();
				$_multiple_db->switchCIDatabase(strtolower($default_currency));
				return $callbakcable();
			}
		}
		else {
			return $callbakcable();
		}
	}

	public function compareParamMatchExpectedType($input_param, $expected_type) {
		// $this->ci->utils->debug_log('=================compareParamMatchExpectedType start', $input_param, $expected_type);
		$compare_status = false;
		switch ($expected_type) {
			case 'int':
				$map_type = 'integer';
				if(is_array($input_param)) {
					$compare_status = false;
				}
				else {
					$compare_status = (preg_match('/^\d+$/', $input_param)) ? true : false;
					// $compare_status = (preg_match('/^[+]?[0-9]+$/', $input_param) || ((int)$input_param == 0) ) ? true : false;
				}
				break;
			case 'bool':
				$map_type = 'boolean';
				$compare_status = (gettype($input_param) == $map_type) ? true : false;
				break;
			case 'positive_double':
				$compare_status = (($input_param != 0) && (is_numeric($input_param)) && ($input_param > 0) && ($input_param - (int)$input_param) >= 0) ? true : false;
				break;
			case 'double':
				$compare_status = ((is_numeric($input_param)) && ($input_param >= 0) && ($input_param - (int)$input_param) >= 0) ? true : false;
				break;
			case 'string':
				$map_type = 'string';
				$compare_status = (gettype($input_param) == $map_type) ? true : false;
				break;
			case 'array':
				$map_type = 'array';
				$compare_status = (gettype($input_param) == $map_type) ? true : false;
				break;
			case 'array[int]':
				if(is_array($input_param)){
					$compare_status = true;
					foreach($input_param as $key => $val){
						if(!(preg_match('/^\d+$/', $val))){
							$compare_status = false;
							break;
						};
					}
				}else{
					$compare_status = false;
				}
				break;
			case 'file':
			case 'file[]':
				$map_type = 'array';
				$compare_status = (gettype($input_param) == $map_type) ? true : false;
				break;
			case 'date-time':
				$format = 'Y-m-d H:i:s';
				$d = DateTime::createFromFormat($format, $input_param);
				$compare_status = $d && $d->format($format) === $input_param;

				if($compare_status == false) {
					$input_param = date("Y-m-d H:i:s", strtotime($input_param));
					$d = DateTime::createFromFormat($format, $input_param);
					$compare_status = $d && $d->format($format) === $input_param;
				}
				break;
			case 'date':
				$format = 'Y-m-d';
				$d = DateTime::createFromFormat($format, $input_param);
				$compare_status = $d && $d->format($format) === $input_param;
				break;
			case 'email':
				$compare_status = filter_var($input_param, FILTER_VALIDATE_EMAIL) ? true : false;
				break;
			case 'currency_id':
				$map_type = 'string';
				// $compare_status = (gettype($input_param) == $map_type) ? true : false;
				$compare_status = !is_string($input_param) || strpos($input_param, '_') === false ? false : true;

				if($compare_status) {
					$code_arr = $this->parseCurrencyAndIdFromCode($input_param);

					if (count($code_arr) != 2) {
						$compare_status =  false;
					}else{
						$allowed_currency = array_map('strtoupper',array_keys($this->ci->utils->getConfig('multiple_currency_list')));
						list($currency, $id) = $code_arr;
						$this->ci->utils->debug_log('=================compareParamMatchExpectedType currency, id, allowed_currency', $currency, $id, $allowed_currency, $input_param, gettype($currency), gettype($id));
						$compare_currency_type = (gettype($currency) == $map_type) ? true : false;
						$compare_currency_allowed = in_array($currency, $allowed_currency);
						$compare_status = ($compare_currency_type && $compare_currency_allowed) ? true : false;
					}
				}
				break;
			default:
				break;
		}

		return $compare_status;
	}

	public function buildPageOutput($data_arr, $page, $limit, $use_numeric_key=false) {
		if(count($data_arr) <= $limit) {
			$page = (int)1;
		}
		else if(count($data_arr) < ($page * $limit) ) {
			$page = (int)((count($data_arr) / $limit) + 1);
		}
		$total_count = 0;
		$result_count = 0;
		$list = [];
		$start_row = $limit * ($page - 1) + 1;
		$process_row = -10;

		foreach ($data_arr as $key => $value) {
			$this->ci->utils->debug_log('================key, process_row, start_row', $key, $process_row, $start_row);
			// $this->ci->utils->debug_log('================key value', $key, $value);
			if($start_row == $key + 1) {
			    $process_row = $start_row;
			}
			if(($process_row >= $key) && ($result_count < $limit)) {
				// $this->ci->utils->debug_log('================key, process_row, start_row', $key, $process_row, $start_row);
				if($use_numeric_key) {	//fixing OGP-26515
				    $list[] = $value;
				}
				else {
				    $list[$key] = $value;
				}
				$process_row++;
				$result_count++;
			}
			$total_count++;
		}

		$end_row = $limit * ($page - 1) + $result_count;
		$has_previous_page = ($page == 1) || empty($data_arr) ? false : true;
		$is_first_page = ($page == 1) || empty($data_arr) ? true : false;
		$is_last_page = ($total_count == $end_row) || empty($data_arr) ? true : false;
		$has_next_page = ($is_last_page) ? false : true;
		$total_pages =  ($total_count % $limit == 0) ? (int)($total_count / $limit) : (int)($total_count / $limit) + 1;
		$page_num = (int)$page;
		$pre_page = ($has_previous_page) ? $page_num - 1 : $page_num;
		$pre_page = empty($data_arr) ? (int)1 : $pre_page;
		$next_page = ($is_last_page) ? (int)$page_num : (int)$page_num + 1;
		$next_page = empty($data_arr) ? (int)1 : $next_page;
		$page_size = (int)$limit;
		$navigate_first_page = 1;
		$navigate_last_page = $total_pages;
		$navigate_pages = $total_pages;
		$navigate_page_nums = [];
		for($i=$navigate_first_page; $i<=$navigate_last_page;$i++) {
		    $navigate_page_nums[] = $i;
		}
		if(empty($data_arr)) {
			$start_row = (int)0;
			$end_row = (int)0;
		}

		$page_result = [
			'hasPreviousPage' =>  $has_previous_page,
			'hasNextPage' =>  $has_next_page,
			'isFirstPage' =>  $is_first_page,
			'isLastPage' => $is_last_page,
			'pageNum' => $page_num,
			'prePage' => $pre_page,
			'nextPage' => $next_page,
			'pageSize' => $page_size,
			'pages' => $total_pages,
			'startRow' => $start_row,
			'endRow' => $end_row,
			'total' => $total_count,
			'navigateFirstPage' => $navigate_first_page,
			'navigateLastPage' => $navigate_last_page,
			'navigatePages' => $navigate_pages,
			'navigatepageNums' => $navigate_page_nums,
		    'list' => $list
		];
		return $page_result;
	}

	public function buildPageOutputParams($data_arr, $page, $limit, $use_numeric_key=false) {
		if(count($data_arr) <= $limit) {
			$page = (int)1;
		}
		else if(count($data_arr) < ($page * $limit) ) {
			$page = (int)((count($data_arr) / $limit) + 1);
		}
		$total_count = 0;
		$result_count = 0;
		$list = [];
		$start_row = $limit * ($page - 1) + 1;
		$process_row = -10;

		foreach ($data_arr as $key => $value) {
			$this->ci->utils->debug_log('================key, process_row, start_row', $key, $process_row, $start_row);
			// $this->ci->utils->debug_log('================key value', $key, $value);
			if($start_row == $key + 1) {
			    $process_row = $start_row;
			}
			if(($process_row >= $key) && ($result_count < $limit)) {
				// $this->ci->utils->debug_log('================key, process_row, start_row', $key, $process_row, $start_row);
				if($use_numeric_key) {	//fixing OGP-26515
				    $list[] = $value;
				}
				else {
				    $list[$key] = $value;
				}
				$process_row++;
				$result_count++;
			}
			$total_count++;
		}

		$total_pages =  ($total_count % $limit == 0) ? (int)($total_count / $limit) : (int)($total_count / $limit) + 1;
		$page_num = (int)$page;
		$page_size = (int)$limit;

		$page_result = [
			'totalPages' => $total_pages,
			'currentPage' => $page_num,
			'totalRowsCurrentPage' => $result_count,
			'list' => $list
		];
		return $page_result;
	}

	public function customizeApiOutput($data_arr, $rebuild_key_arr = []) {
		$list = [];
		if(empty($data_arr)) {
			return $data_arr;
		}
		foreach ($data_arr as $key => $value) {
			$list[$key] = $value;
			foreach ($rebuild_key_arr as $rebuild_item) {
				$remove_key = [];
				switch ($rebuild_item) {
					case 'paymentMethod':
						$list[$key]['paymentMethod']['id'] = $value['paymentMethod_id'];
						$list[$key]['paymentMethod']['name'] = $value['paymentMethod_name'];
						array_push($remove_key,'paymentMethod_id', 'paymentMethod_name');
						break;
					case 'bank_account':
						$list[$key]['bankAccount']['accountHolderName'] = $value['bankAccount_accountHolderName'];
						$list[$key]['bankAccount']['accountNumber'] = $value['bankAccount_accountNumber'];
						$list[$key]['bankAccount']['bankName'] = lang($value['bankAccount_bankName']);
						array_push($remove_key, 'bankAccount_accountNumber', 'bankAccount_accountHolderName', 'bankAccount_bankName');
						break;
					case 'withdrawal_approve_date':
						$list[$key]['approvalDate'] = ($value['dwStatus'] == 'paid') ? $this->formatDateTime($value['processDatetime']) : '';
						array_push($remove_key, 'processDatetime');
						break;
					case 'player':
						$list[$key]['player']['createdAt'] = $this->formatDateTime($value['player_createdAt']);
						$list[$key]['player']['id'] = $value['player_id'];
						$list[$key]['player']['username'] = $value['player_user_name'];
						$list[$key]['player']['phoneNumber'] = $value['player_phone_number'];
						$list[$key]['player']['tag'] = [];
						if(!empty($value['player_tag_id_str'])) {
							$player_tag_id_arr = explode(",", $value['player_tag_id_str']);
							$player_tag_name_arr = explode(",", $value['player_tag_name_str']);
							foreach($player_tag_id_arr as $tag_key => $tag_item) {
								$list[$key]['player']['tag'][$tag_key]['id'] = $tag_item;
								$list[$key]['player']['tag'][$tag_key]['name'] = $player_tag_name_arr[$tag_key];
							}
						}
						array_push($remove_key, 'player_id', 'player_user_name', 'player_phone_number', 'player_createdAt', 'player_tag_id_str', 'player_tag_name_str');
						break;
					case 'deposit_order_comments':
						$order_comments = $this->getDepositOrderComments($value['id']);
						$list[$key]['comments'] = $order_comments;
						break;
					case 'deposit_order_enable_upload_file':
						$list[$key]['enabledUploadDepositSlip'] = true;
						if(!empty($value['system_id']) || !$this->ci->utils->isEnabledFeature('enable_deposit_upload_documents')) {
							$list[$key]['enabledUploadDepositSlip'] = false;
						}
						array_push($remove_key, 'system_id');
						break;
					case 'deposit_upload_file_list':
						$list[$key]['depositSlipList'] = $this->getDepositUoloadFileList($value['player_id'], $value['id']);
						break;
					case 'withdiawal_order_comments':
						$order_comments = $this->getWithdrawalOrderComments($value['id']);
						$list[$key]['comments'] = $order_comments;
						break;
					case 'deposit_order_status':
						$list[$key]['status'] = $this->matchOutputDepositStatus($value['status']);
						break;
					case 'withdrawal_order_status':
						$list[$key]['status'] = $this->matchOutputWithdrawalStatus($value['dwStatus']);
						array_push($remove_key, 'dwStatus');
						break;
					case 'player_address':
						$list[$key]['address'] = $value['address'].$value['address2'].$value['address3'];
						array_push($remove_key, 'address2', 'address3');
						break;
					case 'referer_player_id':
						$list[$key]['referer']['id'] = $value['referer_player_id'];
						$list[$key]['referer']['username'] = $this->ci->player_model->getPlayerUsername($value['referer_player_id'])['username'];
						array_push($remove_key, 'referer_player_id');
						break;
					case 'roulette_records':
						$list[$key]['bonusAmount'] = $value['bonus_amount'];
						$list[$key]['createdAt'] = $this->formatDateTime($value['created_at']);
						array_push($remove_key, 'player_id', 'promo_cms_id', 'bonus_amount', 'created_at');
						break;
					case 'country_code':
						$list[$key]['countryCode'] = $this->matchOutputCountryCode($value['countryCode']);
						break;
					case 'language':
						$list[$key]['language'] = $this->matchOutputLanguage($value['language']);
						break;
					case 'transactionType':
						$list[$key]['type'] = $this->matchOutputTransactionType($value['type']);
						break;
					case 'startAt':
					case 'endAt':
					case 'createdAt':
					case 'updatedAt':
					case 'betTime':
					case 'payoutTime':
					case 'bonusDate':
					case 'expirationDate':
					case 'startTime':
					case 'endTime':
					case 'approvalDate':
					case 'requestedDate':
					case 'invitedOn':
						$list[$key][$rebuild_item] = $this->formatDateTime($value[$rebuild_item]);
						break;
					default:
						# code...
						break;
				}
				$list[$key] = array_diff_key($list[$key], array_flip($remove_key));
			}
		}
		return $list;
	}

	public function getDepositUoloadFileList($player_id, $sale_order_id){
		$get_deposit_upload_file_path_for_api_list = [];
		$deposit_file_list_json_str = $this->ci->player_attached_proof_file_model->getDepositReceiptFileList($player_id, $sale_order_id, true);
		$deposit_file_list = json_decode($deposit_file_list_json_str, true);
		if(!empty($deposit_file_list)) {
			foreach ($deposit_file_list as $single_file_url) {
				$url_arr = explode('/', $single_file_url);
				$upload_file_id = $url_arr[count($url_arr) - 2];
				$player_internal_url = $this->ci->utils->getSystemUrl('player', NEW_PLAYER_CENTER_API_BASE_PATH);
				$single_deposit_upload_file_path_for_api = $player_internal_url.'payment/deposit/file/'.$sale_order_id.'/'.$upload_file_id;
				$get_deposit_upload_file_path_for_api_list[] = $single_deposit_upload_file_path_for_api;
			}
		}
		else {
			$get_deposit_upload_file_path_for_api_list = [];
		}
		return $get_deposit_upload_file_path_for_api_list;
	}

	public function convertOutputFormat($data_arr, $ignore_key_arr = []) {
		array_walk_recursive($data_arr, function(&$value ,$key) use($ignore_key_arr) {
			if(!in_array($key, $ignore_key_arr) ) {
				if(is_numeric($value)) {	// convert string to integer or double
					if(($value - (int)$value) == 0) {
						$value = (int)$value;
					}
					else {
						$value = (double)$value;
					}
				}
				if(is_null($value)) $value = '';	//convert null to empty string
			}
		});
		return $data_arr;
	}

	public function matchOutputAccountType($dwBank) {
		$output_status = '9999';
		// accountType. 1 = deposit 2 = withdrawal
		switch ($dwBank) {
			case Playerbankdetails::DEPOSIT_BANK:
				$output_status = '1';
				break;
			case Playerbankdetails::WITHDRAWAL_BANK:
				$output_status = '2';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputPaymentTypeFlag($payment_type_flag) {
		$output_status = '9999';
		// payment_type_flag. 1= BANK, 2 = EWALLET, 3 = CRYPTO, 4 = API, 5 = OTHERS
		switch ($payment_type_flag) {
			case Financial_account_setting::PAYMENT_TYPE_FLAG_BANK:
				$output_status = 'BANK';
				break;
			case Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET:
				$output_status = 'EWALLET';
				break;
			case Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO:
				$output_status = 'CRYPTO';
				break;
			case Financial_account_setting::PAYMENT_TYPE_FLAG_API:
				$output_status = 'API';
				break;
			case Financial_account_setting::PAYMENT_TYPE_FLAG_PIX:
				$output_status = 'PIX';
				break;
			default:
				$output_status = 'OTHERS';
				break;
		}
		return $output_status;
	}

	public function matchOutputDepositFlagStatus($payment_account_flag) {
		$output_status = '9999';
		// Payment method type. manual(1), 3rd party(2)
		switch ($payment_account_flag) {
			case MANUAL_ONLINE_PAYMENT:
			case LOCAL_BANK_OFFLINE:
				$output_status = '1';
				break;
			case AUTO_ONLINE_PAYMENT:
				$output_status = '2';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputDepositSecondCategoryFlagStatus($payment_account_second_category_flag) {
		$output_status = '9999';
		// Payment method type. BANK_TRANSFER(1), EBANK(2), WECHAT(3), ALIPAY(4), OTHERS(0)
		switch ($payment_account_second_category_flag) {
			case SECOND_CATEGORY_ONLINE_BANK:
			case SECOND_CATEGORY_BANK_TRANSFER:
			case SECOND_CATEGORY_ATM_TRANSFER:
				$output_status = '1';
				break;
			case SECOND_CATEGORY_QQPAY:
			case SECOND_CATEGORY_UNIONPAY:
			case SECOND_CATEGORY_QUICKPAY:
				$output_status = '2';
				break;
			case SECOND_CATEGORY_WEIXIN:
				$output_status = '3';
				break;
			case SECOND_CATEGORY_ALIPAY:
				$output_status = '4';
				break;
			case SECOND_CATEGORY_PIXPAY:
				$output_status = '6';
				break;
			case SECOND_CATEGORY_CRYPTOCURRENCY:
				$output_status = '5';
				break;
			default:
				$output_status = '0';
				break;
		}
		return $output_status;
	}

	public function matchInputDepositStatus($input_deposit_status) {
		$sale_order_status = null;
		// Status of input deposit status, OPEN(0), APPROVED(1), REJECTED(10), CANCELED(11), EXPIRED(12)
		switch ($input_deposit_status) {
			case '0':
				$sale_order_status = Sale_order::STATUS_PROCESSING;
				break;
			case '1':
				$sale_order_status = Sale_order::STATUS_SETTLED;
				break;
			case '10':
				$sale_order_status = Sale_order::STATUS_DECLINED;
				break;
			case '11':
				$sale_order_status = Sale_order::STATUS_CANCELLED;
				break;
			default:
				break;
		}
		return $sale_order_status;
	}

	public function matchOutputDepositStatus($sale_order_status) {
		$output_status = '9999';
		// Status of deposit request ouput status, OPEN(0), APPROVED(1), REJECTED(10), CANCELED(11), EXPIRED(12)
		switch ($sale_order_status) {
			case Sale_order::STATUS_PROCESSING:
				$output_status = '0';
				break;
			case Sale_order::STATUS_SETTLED:
				$output_status = '1';
				break;
			case Sale_order::STATUS_DECLINED:
				$output_status = '10';
				break;
			case Sale_order::STATUS_CANCELLED:
				$output_status = '11';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchInputCampaignStatus($input_campaign_status){
		$output_status = null;
		switch($input_campaign_status){
			case '0':
				// PENDING_APPROVAL
				$output_status = Player_promo::TRANS_STATUS_REQUEST;
				break;
			case '1':
				// APPROVED
				$output_status = [
								  Player_promo::TRANS_STATUS_APPROVED,
								  Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS,
								  Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION,
								  Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS,
								  Player_promo::TRANS_STATUS_FINISHED_MANUALLY_CANCELLED_WITHDRAW_CONDITION,
								  Player_promo::TRANS_STATUS_FINISHED_AUTOMATICALLY_CANCELLED_WITHDRAW_CONDITION,
								 ];
				break;
			case '10':
				// REJECTED
				$output_status = [
								  Player_promo::TRANS_STATUS_DECLINED,
								  Player_promo::TRANS_STATUS_DECLINED_FOREVER
								 ];
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputCampaignStatus($output_campaign_arr){

		foreach($output_campaign_arr as $key => $output_campaign){
			switch($output_campaign['status']){
				case "0":
					$output_campaign_arr[$key]['status'] = "PENDING_APPROVAL";
					break;
				case "1":
					$output_campaign_arr[$key]['status'] = "APPROVED";
					break;
				case "10":
					$output_campaign_arr[$key]['status'] = "REJECTED";
					break;
				default:
					break;
			}
		}
		return $output_campaign_arr;
	}

	public function matchOutputPromoDisplayType($display_type){
		$output = '9999';
		switch($display_type){
			case "1":
				$output = 1;
				break;
			case "2":
				$output = 2;
				break;
			case "0":
				$output = 3;
				break;
			default:
				break;
		}
		return $output;
	}

	public function matchInputPromoType($input_type_arr){
		foreach($input_type_arr as $key => $val){
			switch($val){
				case "11":
					$input_type_arr[$key] = '0';
					break;
				case "12":
					$input_type_arr[$key] = '1';
				default:
					// $input_type_arr = ["9999"];
					break;
			}
		}
		return $input_type_arr;
	}

	public function matchInputWithdrawalStatus($input_withdrawal_status) {
		$walletaccout_dwStatus = '9999';
		// Original status of withdraw request, CREATED(1),SYSTEM_REVIEW(2),REVIEW1(3),REVIEW2(4),REVIEW3(5),REVIEW4(6),REVIEW5(7),REVIEW6(8),PAYING(9),PAID(10),REJECTED(11),CANCELED(12)
		switch ($input_withdrawal_status) {
			case '0':
				$walletaccout_dwStatus = [Wallet_model::REQUEST_STATUS, Wallet_model::PENDING_REVIEW_STATUS, Wallet_model::PENDING_REVIEW_CUSTOM_STATUS, Wallet_model::LOCK_API_UNKNOWN_STATUS, 'CS0', 'CS1', 'CS2', 'CS3', 'CS4', 'CS5', Wallet_model::PAY_PROC_STATUS];
				break;
			case '1':
				$walletaccout_dwStatus = Wallet_model::PAID_STATUS;
				break;

			// case '3':
			// 	$walletaccout_dwStatus = 'CS0';
			// 	break;
			// case '4':
			// 	$walletaccout_dwStatus = 'CS1';
			// 	break;
			// case '5':
			// 	$walletaccout_dwStatus = 'CS2';
			// 	break;
			// case '6':
			// 	$walletaccout_dwStatus = 'CS3';
			// 	break;
			// case '7':
			// 	$walletaccout_dwStatus = 'CS4';
			// 	break;
			// case '8':
			// 	$walletaccout_dwStatus = 'CS5';
			// 	break;
			// case '9':
			// 	$walletaccout_dwStatus = Wallet_model::PAY_PROC_STATUS;
			// 	break;
			case '10':
				$walletaccout_dwStatus = Wallet_model::DECLINED_STATUS;
				break;
			case '9999':
				$walletaccout_dwStatus = null;
				break;
			default:
				break;
		}
		return $walletaccout_dwStatus;
	}

	public function matchOutputWithdrawalStatus($walletaccout_dwStatus) {
		$output_status = '9999';
		// Original status of withdraw request, CREATED(1),SYSTEM_REVIEW(2),REVIEW1(3),REVIEW2(4),REVIEW3(5),REVIEW4(6),REVIEW5(7),REVIEW6(8),PAYING(9),PAID(10),REJECTED(11),CANCELED(12)
		switch ($walletaccout_dwStatus) {
			case Wallet_model::PAID_STATUS:
				$output_status = '1';
				break;
			// case 'CS0':
			// 	$output_status = '3';
			// 	break;
			// case 'CS1':
			// 	$output_status = '4';
			// 	break;
			// case 'CS2':
			// 	$output_status = '5';
			// 	break;
			// case 'CS3':
			// 	$output_status = '6';
			// 	break;
			// case 'CS4':
			// 	$output_status = '7';
			// 	break;
			// case 'CS5':
			// 	$output_status = '8';
			// 	break;
			// case Wallet_model::PAY_PROC_STATUS:
			// 	$output_status = '9';
			// 	break;
			case 'CS0':
			case 'CS1':
			case 'CS2':
			case 'CS3':
			case 'CS4':
			case 'CS5':
			case Wallet_model::PAY_PROC_STATUS:
			case Wallet_model::REQUEST_STATUS:
			case Wallet_model::PENDING_REVIEW_STATUS:
			case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
			case Wallet_model::LOCK_API_UNKNOWN_STATUS:
				$output_status = '0';
				break;
			case Wallet_model::DECLINED_STATUS:
				$output_status = '10';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputWithdrawalConditionSourceTypeStatus($withdrawal_condition_source_type) {
		$output_source_type = '9999';
		// Source type of withdraw condition: DEPOSIT(0), BONUS(1)
		switch ($withdrawal_condition_source_type) {
			case '1':
				$output_source_type = '0';
				break;
			case '9':
				$output_source_type = '1';
				break;
			default:
				break;
		}
		return $output_source_type;
	}

	public function matchInputWithdrawalConditionIsFinishStatus($withdrawal_condition_isfinish_status) {
		$output_status = null;
		// withdraw condition is finish: finish(1), not finish(0)
		switch ($withdrawal_condition_isfinish_status) {
			case '1':
				$output_status = '1';
				break;
			case '2':
				$output_status = '0';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputWithdrawalConditionIsFinishStatus($withdrawal_condition_isfinish_status) {
		$output_status = '9999';
		// withdraw condition is finish: finish(1), not finish(0)
		switch ($withdrawal_condition_isfinish_status) {
			case '0':
				$output_status = '2';
				break;
			case '1':
				$output_status = '1';
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputRedirectTypeStatus($redirect_type) {
		$output_status = '9999';
		// Status of deposit request ouput status, FORM_POST(1), URL(2), REJECTED(10), CANCELED(11), ERROR(10)
		switch ($redirect_type) {
			case Abstract_payment_api::REDIRECT_TYPE_FORM:
				$output_status = '1';
				break;

			case Abstract_payment_api::REDIRECT_TYPE_URL:
				$output_status = '2';
				break;
			case Abstract_payment_api::REDIRECT_TYPE_QRCODE:
				$output_status = '3';
				break;

			case Abstract_payment_api::REDIRECT_TYPE_ERROR:
				$output_status = '10';
				break;
			// case '5':
			// 	$output_status = '1';
			// 	break;
			// case '8':
			// 	$output_status = '10';
			// 	break;
			// case '6':
			// 	$output_status = '11';
			// 	break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputContactPreference($preference_item_str) {
		$output_preference_item = null;
		switch ($preference_item_str) {
			case 'email':
				$output_preference_item = '1';
				break;
			case 'sms':
				$output_preference_item = '2';
				break;
			case 'phone_call':
				$output_preference_item = '4';
				break;
			case 'post':
				$output_preference_item = '8';
				break;
			default:
				break;
		}
		return $output_preference_item;
	}

	public function matchInputContactPreference($preference_item_number) {
		$output_preference_str = null;
		switch ($preference_item_number) {
			case '1':
				$output_preference_str = 'email';
				break;
			case '2':
				$output_preference_str = 'sms';
				break;
			case '4':
				$output_preference_str = 'phone_call';
				break;
			case '8':
				$output_preference_str = 'post';
				break;
			default:
				break;
		}
		return $output_preference_str;
	}

	public function matchOutputGender($gender) {
		$output_gender = '';
		// Source type of gender: Male, Female
		switch ($gender) {
			case 'Male':
				$output_gender = 'M';
				break;
			case 'Female':
				$output_gender = 'F';
				break;
			default:
				break;
		}
		return $output_gender;
	}
	
	public function matchOutputRegistrationFieldType($fieldType) {
		$output_field = '';
		switch ($fieldType) {
			case Registration_setting::FIELD_TYPE_FREE_INPUT:
				$output_field = 'input';
				break;
			case Registration_setting::FIELD_TYPE_SINGLE:
				$output_field = 'single';
				break;
			case Registration_setting::FIELD_TYPE_MULTIPLE:
				$output_field = 'multiple';
				break;
			default:
				break;
		}
		return $output_field;
	}

	public function matchInputGender($gender) {
		$output_gender = '';
		// Source type of gender: M, F
		switch ($gender) {
			case 'M':
				$output_gender = 'Male';
				break;
			case 'F':
				$output_gender = 'Female';
				break;
			default:
				break;
		}
		return $output_gender;
	}

	public function matchOutputLanguage($language) {
		$output_language = '';
		// Source type of language: 'Chinese', 'English', 'India', 'Thai', 'Vietnamese'
		switch ($language) {
			case 'Chinese':
			case 'ch':
			case 'cn':
				$output_language = 'zh-CN';
				break;
			case 'English':
			case 'en':
				$output_language = 'en-US';
				break;
			case 'India':
			case 'in':
				$output_language = 'hi-IN';
				break;
			case 'Thai':
			case 'th':
				$output_language = 'th-TH';
				break;
			case 'Vietnamese':
			case 'vn':
				$output_language = 'vi-VN';
				break;
			case 'Indonesian':
			case 'id':
				$output_language = 'id-ID';
				break;
			case 'Korean':
			case 'kr':
				$output_language = 'ko-KR';
				break;
			case 'Portuguese':
			case 'pt':
				$output_language = 'pt-BR';
				break;
			case 'Spanish':
			case 'es':
				$output_language = 'es-ES';
				break;
			case 'Kazakh':
			case 'kk':
				$output_language = 'kk-KZ';
                break;
            case 'Japanese':
            case 'ja':
                $output_language = 'ja-JP';
                break;
            case 'Chinese_Traditional':
            case 'hk':
                $output_language = 'zh-HK';
                break;
            case 'Filipino':
            case 'ph':
                $output_language = 'fil-PH';
                break;
			default:
				break;
		}
		return $output_language;
	}

	public function matchInputLanguage($language) {
		$output_language = '';
		// Source type of language: 'zh-CN', 'en-US', 'hi-IN', 'th-TH', 'vi-VN'
		switch ($language) {
			case 'zh-CN':
				$output_language = 'Chinese';
				break;
			case 'en-US':
				$output_language = 'English';
				break;
			case 'hi-IN':
				$output_language = 'India';
				break;
			case 'th-TH':
				$output_language = 'Thai';
				break;
			case 'vi-VN':
				$output_language = 'Vietnamese';
				break;
			case 'pt-PT':
				$output_language = 'Portuguese';
				break;
			case 'id-ID':
				$output_language = 'Indonesian';
				break;
			case 'ko-KR':
				$output_language = 'Korean';
				break;
			case 'es-ES':
				$output_language = 'Spanish';
				break;
			case 'kk-KZ':
				$output_language = 'Kazakh';
				break;
			default:
				break;
		}
		return $output_language;
	}

	public function matchOutputputCountry($country) {
		$output_country = '';
		switch ($country) {
			case 'United States':
				$output_country = 'USA';
				break;
			case 'China':
				$output_country = 'CHN';
				break;
			case 'Taiwan':
				$output_country = 'TWN';
				break;
			case 'Malaysia':
				$output_country = 'MYS';
				break;
			case 'Indonesia':
				$output_country = 'IDN';
				break;
			case 'Philippines':
				$output_country = 'PHL';
				break;
			case 'Thailand':
				$output_country = 'THA';
				break;
			case 'Vietnam':
				$output_country = 'VNM';
				break;
			default:
				break;
		}
		return $output_country;
	}

	public function matchInputCountry($country) {
		$output_country = '';
		// Source type of output_country: 'USA', 'CHN', 'TWN', 'MYS', 'IDN', 'PHL', 'THA', 'VNM'
		switch ($country) {
			case 'USA':
				$output_country = 'United States';
				break;
			case 'CHN':
				$output_country = 'China';
				break;
			case 'TWN':
				$output_country = 'Taiwan';
				break;
			case 'MYS':
				$output_country = 'Malaysia';
				break;
			case 'IDN':
				$output_country = 'Indonesia';
				break;
			case 'PHL':
				$output_country = 'Philippines';
				break;
			case 'THA':
				$output_country = 'Thailand';
				break;
			case 'VNM':
				$output_country = 'Vietnam';
				break;
			default:
				break;
		}
		return $output_country;
	}

	public function matchOutputCountryCode($country_code){
		$country_list = $this->getCountryCodeList();
		return array_key_exists($country_code, $country_list) ? $country_list[$country_code] : "";
	}

	public function matchInputCountryCode($country_code){
		$country_list = $this->getCountryCodeList();
		return array_search($country_code, $country_list) ?: "";
	}

	public function matchOutputTransactionType($transaction_type) {
		$output_type = '9999';
		switch ($transaction_type) {
			case Transactions::DEPOSIT:
				$output_type = '0';
				break;
			case Transactions::WITHDRAWAL:
				$output_type = '1';
				break;
			case Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET:
				$output_type = '2';
				break;
			case Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET:
				$output_type = '3';
				break;
			case Transactions::MEMBER_GROUP_DEPOSIT_BONUS:
			case Transactions::PLAYER_REFER_BONUS:
			case Transactions::RANDOM_BONUS:
				$output_type = '4';
				break;
			case Transactions::CASHBACK:
				$output_type = '5';
				break;
			case Transactions::ADD_BONUS:
				$output_type = '10';
				break;
			case Transactions::SUBTRACT_BONUS:
				$output_type = '11';
				break;
			case Transactions::MANUAL_ADD_BALANCE:
				$output_type = '12';
				break;
			case Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET:
				$output_type = '13';
				break;
			case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:
				$output_type = '14';
				break;
			// case Transactions::ADD_WITHDRAWAL_CONDITION:
			// 	$output_type = '15';
			// 	break;
			// case Transactions::PAYMENT_CHARGE:
			// 	$output_type = '16';
			// 	break;
			case Transactions::WITHDRAWAL_FEE_FOR_PLAYER:
				$output_type = '17';
				break;
			case Transactions::WITHDRAWAL_FEE_FOR_BANK:
				$output_type = '18';
				break;
			case Transactions::QUEST_BONUS:
				$output_type = '20';
				break;
			case Transactions::TOURNAMENT_BONUS:
				$output_type = '21';
				break;
			case Transactions::ROULETTE_BONUS:
				$output_type = '22';
				break;
			default:
				break;
		}
		return $output_type;
	}

	public function matchInputTransactionType($transaction_type) {
		$output_type = '9999';
		switch ($transaction_type) {
			case '0':
				$output_type = Transactions::DEPOSIT;
				break;
			case '1':
				$output_type = Transactions::WITHDRAWAL;
				break;
			case '2':
				$output_type = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;
				break;
			case '3':
				$output_type = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
				break;
			case '4':
				$output_type = [Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::RANDOM_BONUS];
				break;
			case '5':
				$output_type = Transactions::AUTO_ADD_CASHBACK_TO_BALANCE;
				break;
			case '10':
				$output_type = Transactions::ADD_BONUS;
				break;
			case '11':
				$output_type = Transactions::SUBTRACT_BONUS;
				break;
			case '12':
				$output_type = Transactions::MANUAL_ADD_BALANCE;
				break;
			case '13':
				$output_type = Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET;
				break;
			case '14':
				$output_type = Transactions::CASHBACK;
				break;
			// case '15':
			// 	$output_type = Transactions::ADD_WITHDRAWAL_CONDITION;
			// 	break;
			// case '16':
			// 	$output_type = Transactions::PAYMENT_CHARGE;
			// 	break;
			case '17':
				$output_type = Transactions::WITHDRAWAL_FEE_FOR_PLAYER;
				break;
			case '18':
				$output_type = Transactions::WITHDRAWAL_FEE_FOR_BANK;
				break;
			case '20':
				$output_type = Transactions::QUEST_BONUS;
				break;
			case '21':
				$output_type = Transactions::TOURNAMENT_BONUS;
				break;
			case '22':
				$output_type = Transactions::ROULETTE_BONUS;
				break;
			default:
				break;
		}
		return $output_type;
	}

	public function matchInputPlayerPromoStatus($player_promo_status) {
		$output_status = '9999';
		// Status of bonus, status including : PENDING_APPROVAL(0), APPROVED(1), REJECTED(10), EXPIRED(11)
		switch ($player_promo_status) {
			case '0':
				$output_status = Player_promo::TRANS_STATUS_REQUEST;
				break;
			case '1':
				$output_status = [Player_promo::TRANS_STATUS_APPROVED, Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS, Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION, Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS, Player_promo::TRANS_STATUS_FINISHED_MANUALLY_CANCELLED_WITHDRAW_CONDITION, Player_promo::TRANS_STATUS_FINISHED_AUTOMATICALLY_CANCELLED_WITHDRAW_CONDITION];
				break;
			case '10':
				$output_status = [Player_promo::TRANS_STATUS_DECLINED, Player_promo::TRANS_STATUS_DECLINED_FOREVER];
				break;
			case '11':
				$output_status = Player_promo::TRANS_STATUS_EXPIRED;
				break;
			default:
				break;
		}
		return $output_status;
	}

	public function matchOutputRouletteTypeInfo($type, $roulette_res) {
		$spin_times_data = $this->ci->utils->safeGetArray($roulette_res, 'spin_times_data', []);

		$data = [
            'totalTimes' => $this->ci->utils->safeGetArray($spin_times_data, 'total_times'),
            'usedTimes' => $this->ci->utils->safeGetArray($spin_times_data, 'used_times'),
            'remainTimes' => $this->ci->utils->safeGetArray($spin_times_data, 'remain_times'),
            'type' => $type,
            'base' => $this->ci->utils->safeGetArray($spin_times_data, 'base', 0),
            // '_remain_times' => $this->ci->utils->safeGetArray($spin_times_data, 'getRetention'),
            // 'valid_date' => $this->ci->utils->safeGetArray($spin_times_data, 'valid_date'),
            // 'available_list' => $this->ci->utils->safeGetArray($spin_times_data, 'available_list',[]),
        ];

        $this->configOutputData($data ,$spin_times_data, 'roulette');

		$next_datetime = '';

		switch ($type) {
			case 'Date':
				$tomorrow = strtotime('tomorrow');
				$next_datetime = date('Y-m-d H:i:s', strtotime('tomorrow'));
				$this->ci->utils->debug_log('================Date time info', $tomorrow, $next_datetime);
				$data['nextDateTime'] = $this->formatDateTime($next_datetime);
				break;
			case 'FixedWithExtra':
				if (isset($spin_times_data['last_apply_at'], $spin_times_data['cycle'])) {
					$last_apply_at = $spin_times_data['last_apply_at'];
					$period = $spin_times_data['cycle'];
					$hours_later = strtotime('+'.$period.' hours', time($last_apply_at));
					$next_datetime = date('Y-m-d H:i:s', $hours_later);
					$this->ci->utils->debug_log('================FixedWithExtra time info', $last_apply_at, $period, $next_datetime, $hours_later);
				}
				$data['nextDateTime'] = !empty($next_datetime) ? $this->formatDateTime($next_datetime) : '';
				break;
			case 'Reward':
			default:
				break;
		}
		return $data;
	}

	/**
	 * add optional output to target data.
	 * @param  [array] $target  add optional output to target data.
	 * @param  [array] $source  grasp the source data.
	 * @param  [string] $type   the scope of source data.
	 */
	public function configOutputData(&$target ,$source, $type){
		if(!is_array($target) && !is_array($source)){
			return;
		}
		switch ($type) {
			case 'roulette':
				if(isset($source['roulette_api_id'])){
					switch ($source['roulette_api_id']) {
						case Abstract_roulette_api::R29620_API:
						case Abstract_roulette_api::R30970_API:
						case Abstract_roulette_api::R32439_API:
						case Abstract_roulette_api::R31492_API:
							$target['accumulateAmount'] = $source['accumulateAmount'];
							$target['thresholdAmount']  = $source['threshold_amount'];
							$target['thresholdType']    = $source['threshold_type'];
						break;
					}
				}
			break;
		}
	}

	public function buildPreferenceData($input_preference_item_number_arr) { // $input_preference_item_number_arr ex:[1,2,4,8]
		$preference_data = [];
		$all_preference_item_arr = ['email', 'sms', 'phone_call', 'post'];
		foreach ($all_preference_item_arr as $preference_item_str) {
			$convert_preference_item_number = $this->matchOutputContactPreference($preference_item_str);
			if(in_array($convert_preference_item_number, $input_preference_item_number_arr)) {
				$preference_data['pref-data-'.$preference_item_str] = true;
			}
			else {
				$preference_data['pref-data-'.$preference_item_str] = false;
			}
		}
		return $preference_data;
	}

	public function getDepositOrderComments($sale_order_id) {
		$output_comments = [];
		$order_comments = $this->ci->sale_orders_notes->getSaleOrdersNotesWithOrderStatusBySaleOrderId($sale_order_id, [Sale_orders_notes::ACTION_LOG, Sale_orders_notes::EXTERNAL_NOTE]);
		// $this->utils->debug_log('================key, process_row, order_comments', $key, $process_row, $order_comments);
		if(!empty($order_comments)) {
			foreach ($order_comments as $comment_key => $comment_value) {
				$output_comments[$comment_key]['comment'] = $comment_value['content'];
				$output_comments[$comment_key]['createdAt'] = $this->formatDateTime($comment_value['created_at']);
				$output_comments[$comment_key]['createdBy']['id'] = $comment_value['created_by'];
				$output_comments[$comment_key]['createdBy']['name'] = $comment_value['username'];
				$output_comments[$comment_key]['fromStatus'] = $this->matchOutputDepositStatus($comment_value['before_status']);
				$output_comments[$comment_key]['toStatus'] = ($this->matchOutputDepositStatus($comment_value['after_status']) == 9999) ? $output_comments[$comment_key]['fromStatus'] : $this->matchOutputDepositStatus($comment_value['after_status']);
				$output_comments[$comment_key]['updatedAt'] = isset($comment_value['updated_at']) ? $this->formatDateTime($comment_value['updated_at']) : $this->formatDateTime($comment_value['created_at']);;
				$output_comments[$comment_key]['updatedBy']['id'] = $comment_value['created_by'];
				$output_comments[$comment_key]['updatedBy']['name'] = $comment_value['username'];
			}
		}
		return $output_comments;
		}

		public function getWithdrawalOrderComments($wallet_account_id) {
		$output_comments = [];
		$order_comments = $this->ci->walletaccount_notes->getWalletAccountNotesWithOrderStatusByWalletAccountId($wallet_account_id, [Walletaccount_notes::EXTERNAL_NOTE]);
		// $this->utils->debug_log('================key, process_row, order_comments', $key, $process_row, $order_comments);
		if(!empty($order_comments)) {
			foreach ($order_comments as $comment_key => $comment_value) {
				$output_comments[$comment_key]['comment'] = $this->formatPaymentNotes($order_comments, false);// $comment_value['content'];
				$output_comments[$comment_key]['createdAt'] = $this->formatDateTime($comment_value['created_at']);
				$output_comments[$comment_key]['createdBy']['id'] = $comment_value['created_by'];
				$output_comments[$comment_key]['createdBy']['name'] = $comment_value['username'];
				$output_comments[$comment_key]['fromStatus'] = $this->matchOutputWithdrawalStatus($comment_value['before_status']);
				$output_comments[$comment_key]['toStatus'] = ($this->matchOutputWithdrawalStatus($comment_value['after_status']) == 9999) ? $output_comments[$comment_key]['fromStatus'] : $this->matchOutputWithdrawalStatus($comment_value['after_status']);
				$output_comments[$comment_key]['updatedAt'] = $this->formatDateTime($comment_value['created_at']);
				$output_comments[$comment_key]['updatedBy']['id'] = $comment_value['created_by'];
				$output_comments[$comment_key]['updatedBy']['name'] = $comment_value['username'];
			}
		}
		return $output_comments;
	}

	public function formatPaymentNotes($withdrawalNotes, $display_last_notes = false) {
		$noteString = '';
		if(!empty($withdrawalNotes)){
			if ($display_last_notes) {
				$aNote = end($withdrawalNotes);
				if($aNote) {
					$aNote['content'] = html_entity_decode($aNote['content']) == $aNote['content'] ? htmlentities($aNote['content']) : html_entity_decode($aNote['content']);
					$noteString .= $aNote['content'];
				}
			} else {
				foreach ($withdrawalNotes as $aNote) {
					$aNote['content'] = html_entity_decode($aNote['content']) == $aNote['content'] ? htmlentities($aNote['content']) : html_entity_decode($aNote['content']);
					$noteString .= $aNote['content'];
				}
			}
		}
		return $noteString;
	}

	public function checkIfExecContinueAfterVerify($verify_result) {
		if(empty($verify_result) || empty($verify_result['passed']) || !$verify_result['passed']) {
			return false;
		}
		return true;
	}

	public function setVerifyResultErrorMsg($verify_result, $error_msg) {
		$verify_result['error_message'] = $error_msg;
		$verify_result['passed'] = false;
		return $verify_result;
	}

    public function formatDateTime($dateStr, $timeZone = null){
		$date = new \DateTime($dateStr);
		$date->setTimezone(new \DateTimeZone($timeZone ?: 'Etc/UTC'));

        // return $date->format("Y-m-d\TH:i:s.u\Z");
        return ($date->getTimestamp() < 0) ? $date->setTimestamp(0)->format("c") : $date->format("c");
    }

	public function getIsoLang($languageIndex) {
		$isoLang =  empty(Language_function::ISO2_LANG[$languageIndex]) ? 'en' : Language_function::ISO2_LANG[$languageIndex];
		return $isoLang;
	}

	public function getCountryCodeList(){
		$country_list = $this->ci->utils->getCountryIso2List();
		$encode_country = json_encode($country_list);
		$cache_key="getCountryCodeList-$encode_country";

		$cached_result = $this->ci->utils->getJsonFromCache($cache_key);
		if(!empty($cached_result)) {
			$this->ci->utils->debug_log(__METHOD__, 'getCountryCodeList from cache', ['cached_result' => $cached_result]);
			return $cached_result;
		}

		$ttl = $this->ci->utils->getConfig('country_code_list_cache_ttl');
		$this->ci->utils->saveJsonToCache($cache_key, $country_list, $ttl);
		$this->ci->utils->debug_log(__METHOD__, 'getCountryCodeList from cache', ['count' => count($country_list)]);

		return $country_list;
	}

	public function getCountryNameByPhoneCode($phoneCode){
		$country_phone_codes = $this->getCountryPhoneCodeList();
		$country_name = array_search($phoneCode, $country_phone_codes) ?: "";
		$this->ci->utils->debug_log(__METHOD__, ['phoneCode' => $phoneCode, 'country_phone_codes' => $country_phone_codes, 'country_name' => $country_name]);

		return $country_name;
	}

	public function getCountryPhoneCodeList($countryCode = null){
		$cache_key="getCountryPhoneCodeList".$countryCode;
		$cached_result = $this->ci->utils->getJsonFromCache($cache_key);

		$this->ci->utils->debug_log(__METHOD__, 'getCountryPhoneCodeList start', ['countryCode' => $countryCode, 'cache_key' => $cache_key,	'cached_result' => $cached_result]);
		if(!empty($cached_result)) {
			$this->ci->utils->debug_log(__METHOD__, 'getCountryPhoneCodeList from cache', ['cached_result' => $cached_result]);
			return $cached_result;
		}

		$country_list = $this->getCountryCodeList();
		$country_phone_codes = unserialize(COUNTRY_NUMBER_LIST_FULL);
		$country_phone_list = [];

		foreach ($country_list as $key => $value) {
			if (array_key_exists($key, $country_phone_codes)) {
				$phoneCode = is_array($country_phone_codes[$key]) ? implode(', ', $country_phone_codes[$key]) : $country_phone_codes[$key];
				$country_phone_list[$key] = $phoneCode;
			}
		}

		if(!empty($countryCode)) {
			$countryName =  $this->matchInputCountryCode($countryCode);
			$singleCode = [];

			if(!empty($countryName)){
				$singleCode[$countryName] = $country_phone_list[$countryName];
				$country_phone_list = $singleCode;
			}
			$this->ci->utils->debug_log(__METHOD__, 'getCountryPhoneCodeList by singleCode', ['country_phone_list' => $country_phone_list]);
		}

		$ttl = $this->ci->utils->getConfig('country_phone_code_list_cache_ttl');
		$this->ci->utils->saveJsonToCache($cache_key, $country_phone_list, $ttl);
		$this->ci->utils->debug_log(__METHOD__, 'getCountryPhoneCodeList end', ['count' => count($country_phone_list), 'country_phone_list' => $country_phone_list]);
		return $country_phone_list;
	}

	/**
	 * match new playerapi accumulation mode from group level.
	 * @param  [int] $accumulation source date from vip upgrade/downgrade setting page
	 * @return [int] transferred accumulation mode to player api constant
	 */
	public function matchAccumulationModeOnVIP($accumulation, $period_up_down)
	{
		$accumulation_mode = 'Period Range was not set';

        if(is_array($period_up_down)){
        	foreach ($period_up_down as $period_type => $time) {
        		if($period_type == 'daily'){
        			$accumulation_mode = Playerapi::CODE_VIP_RANGE_YESTERDAY;
        		}
        		if($period_type == 'weekly'){
        			$accumulation_mode = Playerapi::CODE_VIP_RANGE_LASTER_WEEK;
        		}
        		if($period_type == 'monthly'){
        			$accumulation_mode = Playerapi::CODE_VIP_RANGE_LAST_MONTH;
        		}
        	}
        }

        switch ($accumulation) {
            case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE:
                $accumulation_mode = Playerapi::CODE_VIP_RANGE_FROM_VIP_START;
                break;
            case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION:
                $accumulation_mode = Playerapi::CODE_VIP_RANGE_FROM_REGISTRATION;
                break;
        }

        return $accumulation_mode;
    }

    public function matchOutputProfileColumnName($field_name) {
		switch ($field_name) {
			case 'birthdate':
				$output_name = 'birthday';
				break;
			case 'citizenship':
				$output_name = 'countryCode';
				break;
			case 'dialing_code':
				$output_name = 'countryPhoneCode';
				break;
			case 'contactNumber':
				$output_name = 'phoneNumber';
				break;
			case 'imAccount':
				$output_name = 'im1';
				break;
			case 'imAccount2':
				$output_name = 'im2';
				break;
			case 'imAccount3':
				$output_name = 'im3';
				break;
			case 'pix_number':
				$output_name = 'cpfNumber';
				break;
			default:
				$output_name = $field_name;
				break;
		}
		return $output_name;
	}

	public function handleValidateField($fields, $setting) {
		$lengthOfFields = strlen($fields);
		$playerValidate = $this->ci->utils->getConfig('player_validator');
		$fieldsRule = isset($playerValidate[$setting]) ? $playerValidate[$setting] : [];
		$fieldsMin = isset($fieldsRule['min']) ? $fieldsRule['min'] : "";
		$fieldsMax = isset($fieldsRule['max']) ? $fieldsRule['max'] : "";

		$verify_result = ['passed' => true, 'error_message' => ''];

		if (isset($fieldsMin, $fieldsMax) && $fieldsMin == $fieldsMax && $lengthOfFields != intval($fieldsMin)) {
			$verify_result['passed'] = false;
			$verify_result['error_message'] = sprintf(lang('formvalidation.exact_length'), $setting, $fieldsMin);
		} else {
			if ($lengthOfFields < $fieldsMin) {
				$verify_result['passed'] = false;
				$verify_result['error_message'] = sprintf(lang('formvalidation.min_length'), $setting, $fieldsMin);
			}
			if ($lengthOfFields > $fieldsMax) {
				$verify_result['passed'] = false;
				$verify_result['error_message'] = sprintf(lang('formvalidation.max_length'), $setting, $fieldsMax);
			}
		}

		$this->ci->utils->debug_log('============'. __METHOD__ .' res', $verify_result, $fields, $setting, $lengthOfFields, $fieldsMin, $fieldsMax);

		return $verify_result;
	}

	public function getValidateFieldSetting($alias){
		$playerValidate = $this->ci->utils->getConfig('player_validator');
		$namesRegex = $this->ci->utils->getConfig('new_api_verify_names_invalid_chars');
		$fieldsRule = isset($playerValidate[$alias]) ? $playerValidate[$alias] : [];
		
		if(!empty($fieldsRule)){
			if($alias == 'username'){
				$usernameRegWithDetails = $this->ci->utils->getUsernameRegWithDetails();
                $isRestrictUsernameEnabled = $this->ci->utils->isRestrictUsernameEnabled();
				switch($usernameRegWithDetails['username_requirement_mode']){
					default:
					case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_USE_RESTRICT_REGEX:
						$fieldsRule['regexType'] = !empty($isRestrictUsernameEnabled) ? 
                            FIELD_REGEX_TYPE_REQUIRE_ALPHA_AND_NUMERIC
                            : FIELD_REGEX_TYPE_ALPHA_NUMERIC;
						break;
					case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBER_ONLY:
						$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_NUMERIC;
						break;
					case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_LETTERS_ONLY:
						$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_ALPHA;
						break;
					case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY:
						$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ALPHA_NUMERIC;
						break;
				}
				
				if(!empty($playerValidate['username']['regexType'])){
					$fieldsRule['regexType'] = $playerValidate['username']['regexType'];
				}
			}

			if($alias == 'password'){
				$password_min_max_enabled = $this->ci->utils->isPasswordMinMaxEnabled();
				if(!empty($password_min_max_enabled['min'])){
					$fieldsRule['min'] = $password_min_max_enabled['min'];
				}
				if(!empty($password_min_max_enabled['max'])){
					$fieldsRule['max'] = $password_min_max_enabled['max'];
				}
			}

			if($alias == 'contact_number'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_NUMERIC;
			}

			if($alias == 'cpf_number'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_NUMERIC;
			}
		}

		if(!empty($namesRegex)){
			if($alias == 'first_name'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_ALPHA;
			}

			if($alias == 'last_name'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_ALPHA;
			}

			if($alias == 'middleName'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_ALPHA;
			}

			if($alias == 'maternalName'){
				$fieldsRule['regexType'] = FIELD_REGEX_TYPE_ONLY_ALPHA;
			}
		}

		return $fieldsRule;
	}

	public function getIsoLangCountry($languageIndex = null) {
		if (empty($languageIndex)) {
			return Language_function::ISO_LANG_COUNTRY;
		}
		return Language_function::ISO_LANG_COUNTRY[$languageIndex];
	}

	public function filterPlayerProfileVisable(&$profileList, $visableCheckList){
		foreach ($visableCheckList as $profileKey => $fieldName) {
			if(!$this->ci->registration_setting->checkAccountInfoFieldAllowVisible($fieldName)){
				unset($profileList[$profileKey]);
			}
		}
	}

    public function stripHtmltagsAndDecodeSpecialChars($input){
        $input = strip_tags($input);
        $input = htmlspecialchars_decode($input);
        return $input;
    }

	public function matchOutputRegistrationNames($alias) {
		switch ($alias) {
			case 'birthdate':
				$output_name = 'birthday';
				break;
			case 'citizenship':
				$output_name = 'countryCode';
				break;
			case 'dialing_code':
				$output_name = 'countryPhoneCode';
				break;
			case 'contactNumber':
				$output_name = 'phoneNumber';
				break;
			case 'imAccount':
				$output_name = 'im1';
				break;
			case 'imAccount2':
				$output_name = 'im2';
				break;
			case 'imAccount3':
				$output_name = 'im3';
				break;
			case 'imAccount4':
				$output_name = 'im4';
				break;
			case 'imAccount5':
				$output_name = 'im5';
				break;
			case 'pix_number':
				$output_name = 'cpfNumber';
				break;
			case 'invitationCode':
				$output_name = 'referralCode';
				break;
			case 'sms_verification_code':
				$output_name = 'otpCode';
				break;
			case 'terms':
				$output_name = 'ageRestrictions';
				break;
			case 'affiliateCode':
				$output_name = 'affTrackingCode';
				break;
			default:
				$output_name = $this->ci->utils->toCamelCase($alias);
				break;
		}
		return $output_name;
	}

	public function adjustloopCurrencyDataStructure($data, $limit = 20, $page = 1){
		$this->ci->utils->debug_log(__METHOD__, 'data', $data, 'limit', $limit, 'page', $page);
		$newData = [
			"code" => $data["code"],
			"data" => [
				"totalRecordCount" => 0,
				"totalPages" => 1,
				"totalRowsCurrentPage" => 0,
				"currentPage" => $page,
				"list" => [],
			],
		];

		// 合併資料
		foreach ($data["data"] as $item) {
			$newData["data"]["totalRecordCount"] += $item["totalRecordCount"];
			$newData["data"]["list"] = array_merge($newData["data"]["list"], $item["list"]);
		}

		// 計算 total pages
		$newData["data"]["totalPages"] = ceil($newData["data"]["totalRecordCount"] / $limit);

		// 計算指定頁面的起始索引
		$startIndex = ($page - 1) * $limit;

		// 取得指定頁面的資料
		$newData["data"]["list"] = array_slice($newData["data"]["list"], $startIndex, $limit);

		// 計算當前頁的總數
		$newData["data"]["totalRowsCurrentPage"] = count($newData["data"]["list"]);
		return $this->convertOutputFormat($newData);
	}

	public function utf8convert($mesge, $key = null) {
		if (is_array($mesge)) {
			foreach ($mesge as $k => $value) {
				$mesge[$k] = $this->utf8convert($value, $k);
			}
		} elseif (is_string($mesge)) {
			$fixed = mb_convert_encoding($mesge, "UTF-8", "auto");
			return $fixed;
		}
		return $mesge;
	}

	public function parseCurrencyAndIdFromCode($code)
	{
		$codeArr = explode('_', $code);
		list($currency, $id) = $codeArr;
        return [$currency, (int)$id];
	}

	public function getRestrictionByPlayerId($playerId) {
		$launchGame = false;
		$player_tags = array_column($this->ci->player_model->getPlayerTags($playerId), 'tagId');
		$no_game_allowed_tag = json_decode($this->ci->operatorglobalsettings->getSettingJson('no_game_allowed_tag'), true);
		if (!empty($player_tags) && !empty($no_game_allowed_tag)) {
			foreach ($player_tags as $tag) {
				if (in_array($tag, $no_game_allowed_tag)) {
					$launchGame = true;
				}
			}
		}

		$restrictionArr = array(
			'launchGame' => $launchGame
		);
		return $restrictionArr;
	}
}
