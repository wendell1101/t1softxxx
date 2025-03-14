<?php

/**
 * Class Async
 *
 */
trait player_custom_utils_module{

	public function getCustInfoByReferralType($playerId, $referralType, $request_body=null)
	{
		$referralType = !empty($referralType)? strtolower($referralType) : $referralType;
		$currency = !empty($request_body['currency'])? $request_body['currency'] : $this->currency;

		$custInfo = [
			'code' => Playerapi::CODE_OK,
			'data' => []
		];
		switch ($referralType) {
			case 'sssbet':
				$custInfo['data'] =  $this->custReferralInfoForSssbet($playerId);
				break;
			case 'hugebet':
				$custInfo['data'] =  $this->custReferralInfoForHugebet($playerId);
				break;
			case 'king':
				$custInfo['data'] =  $this->custReferralInfoForKing($playerId, $currency);
				break;
			case 'alpha':
				$custInfo['data'] =  $this->custReferralInfoForAlpha($playerId, $currency);
				break;
			case 'smash':
				$custInfo['data'] =  $this->custReferralInfoForSmash($playerId);
				break;
			default:
				$custInfo['data'] =  $this->defaultReferralInfo($playerId);
				break;
		}
		return $custInfo;
	}

	public function defaultReferralInfo($playerId, $disableCache = false){
		$result = [];
		$cachekey = "default-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$this->load->model(['player_model']);

			$referralCode = [];
			$player = $this->player_model->getPlayerArrayById($playerId) ?: false;
			if(!empty($player)){
				$referralCode = $player['invitationCode'];
			}

			$result = [
				"referralCode" => $referralCode
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}

	public function custReferralInfoForHugebet($playerId, $disableCache = false)
	{
		$result = [];
		$cachekey = "ogp31225-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$configSetting = config_item('async_ogp31225');
			$this->load->model(['player_model', 'player_friend_referral', 'player_promo', 'promorules']);

			$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
			$referralCode = $checkPlayerId['invitationCode'];
			$invitedCount = 0;
			$invitationBonus = 0;
			$yesterdayTotalBonus = 0;
			$accumulatedBonuses = 0;

			$request_from_api = true;
			$_referral_infos = $this->player_friend_referral->getPlayerReferralLevelList(null, null, null, null, null, null, $playerId, $request_from_api);
			if(!empty($_referral_infos)){
				$invitedCount = count($_referral_infos);
			}
			$totalReferralBonuses = $this->player_friend_referral->getTotalReferralBonusByPlayerId($playerId);
			if(!empty($totalReferralBonuses)){
				$invitationBonus = $totalReferralBonuses;
			}

			$promoCmsId = $this->utils->safeGetArray($configSetting, 'promocmsId', false);
			if ($promoCmsId) {

				$promorule = $this->promorules->getPromoruleByPromoCms($promoCmsId);
				if($promorule['promorulesId']){

					$yesterday_datetime = $this->utils->getYesterdayForMysql();
					$y_start = $yesterday_datetime.' '.Utils::FIRST_TIME;
					$y_end = $yesterday_datetime.' '.Utils::LAST_TIME;
					$yesterdayTotalBonus = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $y_start, $y_end);

					$from_datetime = $this->utils->safeGetArray($configSetting, 'fromDatetime', '2000-01-01 00:00:00');
					$to_datetime = $this->utils->getNowForMysql();
					$accumulatedBonuses = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $from_datetime, $to_datetime);
				}
			}

			$result = [
				"referralCode" => $referralCode,
				"invitedCount"  => $invitedCount ?: 0,
				"inviteBonus"  => floatval($invitationBonus) ?: 0,
				"yesterdayTotalBonus" => floatval($yesterdayTotalBonus) ?: 0,
				"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}

	public function custReferralInfoForAlpha($playerId, $currency, $disableCache = false)
	{
		$result = [];
		$cachekey = "ogp33149-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$this->load->model(['player_model', 'player_friend_referral']);
			$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
			$referralCode = $checkPlayerId['invitationCode'];

			$invitedCount = 0;
			$invitationBonus = 0;
			$yesterdayTotalBonus = 0;
			$accumulatedBonuses = 0;

			$referral = $this->player_friend_referral->getPlayerReferralList($playerId);
			if(!empty($referral)){
				$invitedCount = count($referral);
			}
			$totalReferralBonuses = $this->player_friend_referral->getTotalReferralBonusByPlayerId($playerId);
			if(!empty($totalReferralBonuses)){
				$invitationBonus = $totalReferralBonuses;
			}

			$result = [
				"referralCode" => $referralCode,
				"invitedCount"  => $invitedCount ?: 0,
				"inviteBonus"  => floatval($invitationBonus) ?: 0,
				"yesterdayTotalBonus" => floatval($yesterdayTotalBonus) ?: 0,
				"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}

	public function custReferralInfoForKing($playerId, $currency, $disableCache = false)
	{
		$result = [];
		$cachekey = "ogp32271-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$configSetting = config_item('async_ogp32271');
			$this->load->model(['player_model', 'player_friend_referral', 'player_promo', 'promorules', 'transactions']);
			$promoCmsId = $this->utils->safeGetArray($configSetting, 'promocmsId', false);
			if ($promoCmsId) {
				$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $playerId, $promoCmsId, $configSetting){
					$today = $this->utils->getTodayForMysql();
					$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
					list($from, $to) = $this->utils->getTodayDateTimeRange();
					$today_from = $from->format('Y-m-d H:i:s');
					$today_to = $to->format('Y-m-d H:i:s');

					$referralCode = $checkPlayerId['invitationCode'];
					$invitedCount = 0;
					$invitedCountToday = 0;
					$todayTotalBonus = 0;
					$referredDepositToday = 0;
					$referredDepositTotal = 0;

					$_referral_infos = $this->player_friend_referral->getPlayerReferralList($playerId);
					$invitedCount = count($_referral_infos);

					$invitedToday = $this->player_friend_referral->getPlayerReferralList($playerId, null, $today_from, $today_to);
					$invitedCountToday = count($invitedToday);

					$promoCmsId = $this->utils->safeGetArray($configSetting, 'promocmsId', false);
					$start = $this->utils->safeGetArray($configSetting, 'fromDatetime', '2000-01-01 00:00:00');
					$now = $this->utils->getNowForMysql();

					$promorule = $this->promorules->getPromoruleByPromoCms($promoCmsId);
					$promorulesId = $promorule['promorulesId'];
					if($promorulesId){
						if(!empty($_referral_infos)){
							foreach($_referral_infos as $info){
								$invitedUserId = $info->invitedUserId;
								$registerDate = $this->utils->formatDateForMysql(new DateTime($info->registerTime));
								$total_deposit = $this->utils->roundCurrencyForShow($this->transactions->sumDepositAmount($invitedUserId, $start, $now, 0));
								$referredDepositTotal += $total_deposit;
								if($today == $registerDate){
									$today_deposit = $this->utils->roundCurrencyForShow($this->transactions->sumDepositAmount($invitedUserId, $today_from, $today_to, 0));
									$referredDepositToday += $today_deposit;
								}
							}
						}
						$todayTotalBonus = $this->player_promo->sumBonusAmount($playerId, $promorulesId, $today_from, $today_to);
					}

					$rlt = [
						'referralCode'=> $referralCode,
						'invitedCount'=> $invitedCount?:0,
						'invitedCountToday'=> $invitedCountToday?:0,
						'referredDepositTotal'=> floatval($referredDepositTotal)?:0,
						'referredDepositToday'=> floatval($referredDepositToday)?:0,
						'todayTotalBonus'=> floatval($todayTotalBonus)?:0,
					];

					return $rlt;
				});

				$referralCode = $output['referralCode'];
				$invitedCount = $output['invitedCount'];
				$invitedCountToday = $output['invitedCountToday'];
				$referredDepositTotal = $output['referredDepositTotal'];
				$referredDepositToday = $output['referredDepositToday'];
				$todayTotalBonus = $output['todayTotalBonus'];
			}

			$result = [
				"referralCode" => $referralCode,
				"invitedCount" => $invitedCount ?: 0,
				"invitedCountToday" => $invitedCountToday ?: 0,
				"invitedDepositTotal" => floatval($referredDepositTotal) ?: 0,
				"invitedDepositToday" => floatval($referredDepositToday) ?: 0,
				"todayTotalBonus" => floatval($todayTotalBonus) ?: 0
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}

	public function custReferralInfoForSmash($playerId, $disableCache = false)
	{
		$result = [];
		$cachekey = "ogp31810-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$this->load->model(['player_model', 'player_friend_referral', 'player_promo', 'promorules']);

			$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
			$referralCode = $checkPlayerId['invitationCode'];
			
			$invitedCount = 0;			
			$availableReferralCount = 0;
			$accumulatedBonuses = 0;

			$referral = $this->player_friend_referral->getPlayerReferralList($playerId);
			if(!empty($referral)){
				$invitedCount = count($referral);
			}

			$availableReferral = $this->player_friend_referral->getPlayerReferralList($playerId, Player_friend_referral::STATUS_PAID);
			if(!empty($availableReferral)){
				$availableReferralCount = count($availableReferral);
			}

			$accumulatedBonuses = $this->player_friend_referral->getTotalReferralBonusByPlayerId($playerId);

			$result = [
				"referralCode" => $referralCode,
				"invitedCount"  => $invitedCount ?: 0,
				"availableReferralCount" => $availableReferralCount ?: 0,
				"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}

	public function custReferralInfoForSssbet($playerId, $disableCache = false)
	{
		$result = [];
		$cachekey = "ref28684-referralInfos-{$playerId}";
		$referralInfosCache = $this->utils->getJsonFromCache($cachekey);

		if (!empty($referralInfosCache) && $disableCache != 1) {
			$result = $referralInfosCache;
		} else {
			$configSetting = config_item('async_ref28684');
			$this->load->model(['player_model', 'player_friend_referral', 'player_promo', 'promorules', 'transactions']);

			$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
			$referralCode = $checkPlayerId['invitationCode'];
			$invitedCount = 0;
			$yesterdayTotalBonus = 0;
			$accumulatedBonuses = 0;

			$promoCmsId = $this->utils->safeGetArray($configSetting, 'promocmsId', false);
			$from_datetime = $this->utils->safeGetArray($configSetting, 'fromDatetime', '2000-01-01 00:00:00');
			$to_datetime = $this->utils->getNowForMysql();

			list($from, $to) = $this->utils->getTodayDateTimeRange();
			$today = $from->format('Y-m-d');
			$today_from = $from->format('Y-m-d H:i:s');
			$today_to = $to->format('Y-m-d H:i:s');

			if ($promoCmsId) {
				$promorule = $this->promorules->getPromoruleByPromoCms($promoCmsId);
				if($promorule['promorulesId']){
					$yesterday_datetime = $this->utils->getYesterdayForMysql();
					$y_start = $yesterday_datetime.' '.Utils::FIRST_TIME;
					$y_end = $yesterday_datetime.' '.Utils::LAST_TIME;
					$yesterdayTotalBonus = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $y_start, $y_end);
					$accumulatedBonuses = $this->player_promo->sumBonusAmount($playerId, $promorule['promorulesId'], $from_datetime, $to_datetime);
				}
			}

			// get depositors & actualDepositors
			$record = $this->player_friend_referral->getPlayerReferralDepositors($playerId);
			$depositors = 0;
			$actualDepositors = 0;
			if(!empty($record['referred_depositors_count'])){
				$depositors = $record['referred_depositors_count'];
			}
			if(!empty($record['referred_actual_depositors_count'])){
				$actualDepositors = $record['referred_actual_depositors_count'];
			}

			$inviteBonus = $this->player_friend_referral->getReferralQuestBonusByPlayerId($playerId);

			$inviteDepositTotal = 0; //被推荐人总存款量
			$inviteDepositToday = 0; //被推荐人今日总存款量
			$invitedInfoTodayCount = [];
			$_referral_infos = $this->player_friend_referral->getPlayerReferralList($playerId);
			if(!empty($_referral_infos)){
				foreach($_referral_infos as $info){
					$invitedUserId = $info->invitedUserId;
					$registerDate = $this->utils->formatDateForMysql(new DateTime($info->registerTime));
					$total_deposit = $this->utils->roundCurrencyForShow($this->transactions->sumDepositAmount($invitedUserId, $from_datetime, $to_datetime, 0));
					$inviteDepositTotal += $total_deposit;
					if($today == $registerDate){
						$today_deposit = $this->utils->roundCurrencyForShow($this->transactions->sumDepositAmount($invitedUserId, $today_from, $today_to, 0));
						$inviteDepositToday += $today_deposit;
						$invitedInfoTodayCount[] = $info;
					}
				}
			}
			$invitedCount = count($_referral_infos);
			$inviteCountToday = count($invitedInfoTodayCount);

			$result = [
				"referralCode" => $referralCode,
				"invitedCount"  => $invitedCount ?: 0,
				"yesterdayTotalBonus" => floatval($yesterdayTotalBonus) ?: 0,
				"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
				"depositors" => $depositors ?: 0,
				"actualDepositors" => $actualDepositors ?: 0,
				"inviteBonus" => $inviteBonus ?: 0,
				"inviteCountToday" => $inviteCountToday ?: 0,
				"inviteDepositTotal" => $inviteDepositTotal ?: 0,
				"inviteDepositToday" => $inviteDepositToday ?: 0,
			];

			$ttl = 30 * 60;
			$this->utils->saveJsonToCache($cachekey, $result, $ttl);
		}

		return $result;
	}
}
