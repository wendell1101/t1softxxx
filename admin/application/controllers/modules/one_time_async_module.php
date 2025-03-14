<?php

trait one_time_async_module
{

	/**
	 * OGP-26992
	 *
	 */
	public function getFriendReferralStatisticReport($returnJson = false, $disableCache = false, $_playerId = null)
	{
		if ($this->utils->getConfig('disable_getFriendReferralStatisticReport')) {
			return $this->returnErrorStatus(403, true);
		}

		$result = [];
		$default_config = [
			"isDebugMode" => false,
			"eligibility" => [
				"fromDate" => '2022-09-23',
				"toDate" => '2022-10-22',
				"threshold" => 500,
			],
			"referral" => [
				"fromDate" => '2022-10-23',
				"toDate" => '2022-10-31',
				"denominator" => 10,
				"times" => 0.5,
				"bonusType" => 'floor'
			],
		];

		$overwrite_config = $this->CI->utils->getConfig('overwrite_getFriendReferralStatisticReport') ?: [];
		$config = array_replace_recursive($default_config, $overwrite_config);
		$isDebugMode = isset($config['isDebugMode']) ? $config['isDebugMode'] : false;

		$denominator = $config['referral']['denominator'];
		$times = $config['referral']['times'];
		$bonusType = $config['referral']['bonusType'];

		$this->CI->utils->debug_log("getFriendReferralStatisticReport config ", $config);


		//init period1
		$period1_fromDate = $config["eligibility"]["fromDate"];
		$period1_toDate = $config["eligibility"]["toDate"];
		$result['eligibilityDetail'] = array(
			'period' => ['from' => $period1_fromDate, 'to' => $period1_toDate],
			'profit' => 0,
			'eligibility' => 'N',
		);

		//init period2
		$period2_fromDate = $config["referral"]["fromDate"];
		$period2_toDate = $config["referral"]["toDate"];
		$result['referralDetail'] = array(
			'period' => ['from' => $period2_fromDate, 'to' => $period2_toDate],
			'refCount' => 0,
			'refundPercent' => 0,
			'refundAmount' => 0,
		);
		$this->CI->load->model(['player_model']);
		$checkPlayerId = false;
		if ($this->CI->authentication->isLoggedIn() && !$this->utils->getConfig('disable_getFriendReferralStatisticReport') || !empty($_playerId)) {

			//     'fake_data' => [
			//         'profit' => 60000,
			//         'refCount' => 20
			//     ]
			$fake_data = isset($config['fake_data']) ? $config['fake_data'] : null;
			$playerId = $_playerId ?: $this->CI->authentication->getPlayerId();

			$checkPlayerId = $this->CI->player_model->getPlayerArrayById($playerId) ?: false;
			if (!!$checkPlayerId) {

				// $playerUsername = $this->CI->authentication->getUsername();
				$playerUsername = $this->CI->player_model->getUsernameById($playerId);
				//check eligibility
				$key = "getFriendReferralStatisticReport-playerGameTotal-{$playerId}";
				$playerGameTotalCache = $this->utils->getJsonFromCache($key);

				if (empty($playerGameTotalCache) || $disableCache == 1) {
					$this->CI->load->model(['total_player_game_day']);
					$playerGameTotal = $this->CI->total_player_game_day->getPlayerTotalBetWinLoss(
						$playerId,
						$period1_fromDate,
						$period1_toDate,
						'total_player_game_day', //$total_player_game_table
						'date', //$where_date_field
						null, //$where_game_platform_id
						null //$where_game_type_id
					);
					$ttl = 100 * 60;
					$this->utils->saveJsonToCache($key, $playerGameTotal, $ttl);
				} else {
					$playerGameTotal = $playerGameTotalCache;
				}

				$profit = isset($fake_data['profit']) ? $fake_data['profit'] : ($playerGameTotal['total_loss'] - $playerGameTotal['total_win']); //1000;
				$threshold = $config["eligibility"]["threshold"];
				$eligibility = ($profit >= $threshold) ? 'Y' : 'N';
				$result['eligibilityDetail'] = array_replace_recursive(
					$result['eligibilityDetail'],
					array(
						'threshold' => $threshold,
						'profit' => round($profit, 2),
						'eligibility' => $eligibility,

					)
				);
				if ($isDebugMode) {
					$result['playerId'] = $playerId;
					$result['username'] = $playerUsername;
					$result['eligibilityDetail']['playerGameTotal'] = $playerGameTotal;
				}

				$this->CI->utils->debug_log(
					"getFriendReferralStatisticReport result p1  ",
					array(
						'playerId' => $playerId,
						'threshold' => $threshold,
						'profit' => $profit,
						'eligibility' => $eligibility,
						'playerGameTotal' => $playerGameTotal,
					)
				);


				if ($eligibility == 'Y') {
					$fromDatetime = date('Y-m-d 00:00:00', strtotime($period2_fromDate));
					$toDatetime = date('Y-m-d 23:59:59', strtotime($period2_toDate));

					$key = "getFriendReferralStatisticReport-referralInfos-{$playerId}";
					$referralInfosCache = $this->utils->getJsonFromCache($key);

					if (empty($referralInfosCache) || $disableCache == 1) {
						$this->CI->load->model(['player_friend_referral']);
						$_referral_infos = $this->CI->player_friend_referral->getPlayerTotalFriendRefferalCountByDatetimeAndStatus($fromDatetime, $toDatetime, $playerId);
						$ttl = 30 * 60;
						$this->utils->saveJsonToCache($key, $_referral_infos, $ttl);
					} else {
						$_referral_infos = $referralInfosCache;
					}

					$this->CI->utils->debug_log("<===== Referral Infos =======>", $_referral_infos);
					if (!empty($_referral_infos)) {

						$referral_infos = $_referral_infos[0];
						// check referral
						$refCount = isset($fake_data['refCount']) ? $fake_data['refCount'] : (isset($referral_infos['total_referral']) ? $referral_infos['total_referral'] : 0);
						$_refundPercent = floor($refCount / $denominator) * 10 * $times;
						// $_refundPercent = floor($refCount) * 10;
						$refundPercent = $_refundPercent >= 100 ? 100 : $_refundPercent;
						$refundAmount = ($profit * $refundPercent) / 100;
						switch ($bonusType) {
							case 'floor':
							default:
								$refundAmount = floor($refundAmount);
								break;

							case 'round':
								$refundAmount = round($refundAmount, 2);
								break;
						}
						$result['referralDetail'] = array_replace_recursive(
							$result['referralDetail'],
							array(
								'refCount' => (int)$refCount,
								'refundPercent' => $refundPercent,
								'refundAmount' => $refundAmount,
							)
						);
						if ($isDebugMode) {
							$result['referralDetail']['ref_period'] = ['from' => $fromDatetime, 'to' => $toDatetime];
							$result['referralDetail']['referral_infos'] = $referral_infos;
						}
						$this->CI->utils->debug_log(
							"getFriendReferralStatisticReport result p2 found referral_infos ",
							array(
								'playerId' => $playerId,
								'refCount' => (int)$refCount,
								'refundPercent' => $refundPercent,
								'refundAmount' => $refundAmount,
								'_period' => ['from' => $fromDatetime, 'to' => $toDatetime],
								'referral_infos' => $referral_infos
							)
						);
					} else {
						$this->CI->utils->debug_log(
							"getFriendReferralStatisticReport result p2 not found referral_infos ",
							array(
								'playerId' => $playerId,
								'ref_period' => ['from' => $fromDatetime, 'to' => $toDatetime]
							)
						);
					}
				} else {
					$this->CI->utils->debug_log(
						"getFriendReferralStatisticReport result p2 eligibility not met",
						array(
							'playerId' => $playerId,
							'threshold' => $threshold,
							'profit' => $profit,
						)
					);
				}
			}
		}

		if ($isDebugMode) {
			$result['config'] = $config;
			$result['checkPlayerId'] = !!$checkPlayerId;
		}
		if ($returnJson) {

			return $this->returnJsonResult(array('success' => true, 'result' => $result));
		}

		return $this->returnJsonpResult(array('success' => true, 'result' => $result));
	}

	public function getFriendReferralStatisticReportNova($promoCmsId, $_playerId = _COMMAND_LINE_NULL, $returnJson = false, $disableCache = false)
	{
		if ($this->utils->getConfig('disable_getFriendReferralStatisticReportNova')) {
			return $this->returnErrorStatus(403, true);
		}
		$countRef = $target1times = $target2times = $bonus = 0;
		$hasLeftTimes = false;

		$this->CI->utils->debug_log("getFriendReferralStatisticReportNova config ");

		$this->CI->load->model(['player_model']);
		$checkPlayerId = false;
		if ($this->CI->authentication->isLoggedIn() && !$this->utils->getConfig('disable_getFriendReferralStatisticReportNova') || ($_playerId != _COMMAND_LINE_NULL)) {

			if ($promoCmsId) {
				$this->CI->load->model(['promorules']);
				list($promorule, $promoCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($promoCmsId);
				$formula = json_decode($promorule['formula'], true);
				if (!empty($formula['bonus_release'])) {
					$description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);

					$ttl = 20 * 60; // 20 minutes
					// $this->utils->saveJsonToCache($cache_key, $description, $ttl);
				}
			}

			$playerId = ($_playerId == _COMMAND_LINE_NULL) ? $this->CI->authentication->getPlayerId() : $_playerId;
			$checkPlayerId = $this->CI->player_model->getPlayerArrayById($playerId) ?: false;
			if (!!$checkPlayerId) {

				// $playerUsername = $this->CI->authentication->getUsername();
				$playerUsername = $this->CI->player_model->getUsernameById($playerId);
			}
			$this->CI->load->model(['player_friend_referral']);
			$fromDatetime = date('Y-m-d 00:00:00', strtotime($description['allow_after_datetime']));
			$toDatetime = date('Y-m-d 23:59:59', strtotime($description['allow_end_datetime']));

			$_referral_infos = $this->CI->player_friend_referral->getPlayerTotalFriendRefferalCountByDatetimeAndStatus($fromDatetime, $toDatetime, $playerId);
			if (!empty($_referral_infos)) {
				$referral_infos = $_referral_infos[0];
				$countRef = (isset($referral_infos['total_referral']) ? $referral_infos['total_referral'] : 0);
			}

			if (array_key_exists('bindWithRoulette', $description) && is_array($description['bindWithRoulette'])) {

				foreach ($description['bindWithRoulette'] as $key => $target) {
					$rouletteName = array_key_exists('name', $target) ? $target['name'] : false;
					if ($rouletteName) {
						$api_name = 'roulette_api_' . $rouletteName;
						$classExists = file_exists(strtolower(APPPATH . 'libraries/roulette/' . $api_name . ".php"));
						if ($classExists) {
							$this->load->library('roulette/' . $api_name);
							$this->roulette_api = $this->$api_name;
							switch($key){
								case 'target1':
									$t1releasedSpin = $this->roulette_api->getSpinByGenerateBy($playerId, ucfirst($description['class']));
									$target1times = $countRef * $target['ratio'];
									$hasLeftTimes = $hasLeftTimes || ($target1times > $t1releasedSpin);
									break;

								case 'target2':
									$t2releasedSpin = $this->roulette_api->getSpinByGenerateBy($playerId, ucfirst($description['class']));
									$t2tierSetting = $description['t2tierSetting'];
									$firstlevel = $t2tierSetting[0];
									$lastlevel = end($t2tierSetting);
									if (!($countRef < $firstlevel['threshold'])) {
										$tierType = array_key_exists('tierType', $description) ? $description['tierType'] : 'default';
										if ($tierType == 'currentLevel') {

											for ($i = count($t2tierSetting); $i >= 1; $i--) {
												$level = [];
												$this->appendToDebugLog("generateFreespinTimes check tier", ['level' => $t2tierSetting[$i - 1]]);

												$level = $t2tierSetting[$i - 1];
												$threshold = $level["threshold"];
												$award = $level["award"];
												if ($countRef >= $threshold) {
													$target2times = $award;
													break;
												}
											}
										} else {

											$countArr = count($t2tierSetting);
											for ($i = 0; $i < $countArr; $i++) {
												$level = $t2tierSetting[$i];
												$threshold = $level["threshold"];
												$award = $level["award"];
												if ($countRef >= $threshold) {
													$target2times += $award;
												}
											}
											$hasLeftTimes = $hasLeftTimes || ($target2times > $t2releasedSpin);
										}
									}
									break;
							}
						}
					}
				}
			}
			$this->CI->load->model(['player_additional_roulette']);
			$bonus = $this->CI->player_additional_roulette->getBonusByGenerateBy($playerId, ucfirst($description['class']));
		}


		$result = [
			"countRef" => $countRef,
			"target1times" => $target1times,
			"target2times" => $target2times,
			"bonus" => $bonus,
			"hasLeftTimes" => $hasLeftTimes,
			"description" => $description
		];
		if ($returnJson) {

			return $this->returnJsonResult(array('success' => true, 'result' => $result));
		}

		return $this->returnJsonpResult(array('success' => true, 'result' => $result));
	}

	public function testAddTrackingEvent($trackingevent_source_type){
		$success = false;
		if ($this->CI->authentication->isLoggedIn()) {

			$playerId = $this->CI->authentication->getPlayerId();
			$params = [
				'amount' => 11,
				'msg' => 'myFirstEvent',
				'tire' => [
					0 => 1,
					1 => 1,
					2 => 1,
					3 => 1,
				]
			];
			$success = $this->CI->utils->playerTrackingEvent($playerId, $trackingevent_source_type, $params);
		}
		return $this->returnJsonpResult(array('success' => $success));
	}

	/// After login in player conter and visit the url,
	// http://player.og.local/async/get_event_url
	public function get_event_url($reveal_raw_token = false, $api_key = ''){

		$ret = [];
		if ($this->CI->authentication->isLoggedIn()) {
			$success = false;
			$event_url = '';
			$this->load->model(array('common_token'));
			$pub_key=$this->utils->getConfig('api_key_player_center_public_key_in_smash_promo_auth');
			$player_id = $this->CI->authentication->getPlayerId();
			$_player_token = $this->common_token->getPlayerToken($player_id);
			if( ! empty($pub_key)){
				$player_token = $this->utils->public_encrypt($_player_token, $pub_key);
			}
			$player_token = urlencode($player_token);

			$event_uri_in_api = $this->utils->getConfig('event_url_list_by_ogp_27441');
			if( ! empty($event_uri_in_api) ){
				$search = ['{TOKEN}'];
				$replace = [$player_token];
				$event_url = str_replace($search, $replace, $event_uri_in_api);
				$success = true;
			}
			$ret = ['success' => $success , 'event_url' => $event_url];

			if($reveal_raw_token == 'dbg'){
				$ret['raw_token'] = $_player_token;
				$ret['sign'] =  md5($_player_token. $api_key);
			}
		}
		return $this->returnJsonpResult($ret);
	} // EOF get_event_url

	public function ref28684($returnJson = false, $disableCache = false, $_playerId = null) {

		if (!$this->utils->getConfig('async_ref28684')) {
		// if (true) {
			return $this->returnErrorStatus(403, true);
		}

        if(!$this->authentication->isLoggedIn()){
			return $this->returnJsonpResult(array('success' => false, 'result' => lang('not login')));
        }

		if ($this->CI->authentication->isLoggedIn() || !empty($_playerId)) {
			$playerId = $_playerId ?: $this->CI->authentication->getPlayerId();

			$cachekey = "ref28684-referralInfos-{$playerId}";
			$referralInfosCache = $this->utils->getJsonFromCache($cachekey);
			// $referralInfosCache = false;

			if (!empty($referralInfosCache) && $disableCache != 1) {

				$result = $referralInfosCache;
			} else {

				$configSetting = config_item('async_ref28684');
				$this->CI->load->model(['player_model', 'player_friend_referral', 'player_promo', 'promorules']);
	
				$checkPlayerId = $this->CI->player_model->getPlayerArrayById($playerId) ?: false;
				if(empty($checkPlayerId)) {
					return $this->returnJsonpResult(array('success' => false, 'result' => lang('not login')));
				}

				$invitationCode = $checkPlayerId['invitationCode'];
				$invitation = 0;
				$yesterdayBonuses = 0;
				$accumulatedBonuses = 0;
				
				$_referral_infos = $this->CI->player_friend_referral->getPlayerReferralList($playerId);
				if($_referral_infos){
					$invitation = count($_referral_infos);
				}
				$promoCmsId = $this->CI->utils->safeGetArray($configSetting, 'promocmsId', false);
				if ($promoCmsId) {

					$promorule = $this->CI->promorules->getPromoruleByPromoCms($promoCmsId);
					if($promorule['promorulesId']){
		
						$yesterday_datetime = $this->CI->utils->getYesterdayForMysql();
						$y_start = $yesterday_datetime.' '.Utils::FIRST_TIME;
						$y_end = $yesterday_datetime.' '.Utils::LAST_TIME;
						$yesterdayBonuses = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $y_start, $y_end);
		
						$from_datetime = $this->CI->utils->safeGetArray($configSetting, 'fromDatetime', '2000-01-01 00:00:00');
						$to_datetime = $this->CI->utils->getNowForMysql();
						$accumulatedBonuses = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $from_datetime, $to_datetime);
					}
				}
	
				
				$result = [
					"invitationCode" => $invitationCode,
					"invitation"  => $invitation ?: 0,
					"yesterdayBonuses" => floatval($yesterdayBonuses) ?: 0,
					"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
				];
	
				$ttl = 30 * 60;
				$this->utils->saveJsonToCache($cachekey, $result, $ttl);
			}
		}
		
		if ($returnJson) {	
			return $this->returnJsonResult(array('success' => true, 'result' => $result));
		}
		return $this->returnJsonpResult(array('success' => true, 'result' => $result));
	}
}
