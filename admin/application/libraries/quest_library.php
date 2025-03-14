<?php
/**
 * quest_library.php
 *
 * @author
 *
 * @property BaseController $CI
 */
class Quest_library {
    /* @var BaseController */
    public $CI;

    /* @var  Quest_manager */
    public $quest_manager;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(array('quest_manager', 'transactions', 'sale_order', 'total_player_game_hour', 'player_model', 'player_friend_referral', 'total_player_game_day', 'game_logs'));
        $this->quest_manager = $this->CI->quest_manager;
    }

    public function requestQuest($playerId, $questId, $questRule, $playerQuest, $isHierarchy, $fromDatetime, $toDatetime, $categoryDetails, $is_dry_run=false){
        $this->CI->utils->debug_log(__METHOD__, 'lock quest', $playerId, 'questId', $questId, $isHierarchy, $playerQuest, $fromDatetime, $toDatetime, $is_dry_run, $categoryDetails);

        try {
            $controller = $this;
            $message = '';

            $transSucc = $this->CI->player_model->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerId, $questId, $questRule, $playerQuest, $isHierarchy, $fromDatetime, $toDatetime, $categoryDetails, $is_dry_run, &$message){
                list($success, $message) = $this->quest_manager->triggerQuestFromManualPlayer($playerId, $questId, $questRule, $playerQuest, $isHierarchy, $fromDatetime, $toDatetime, $categoryDetails, $is_dry_run);
                $this->CI->utils->debug_log("QuesttransSucc-$playerId", 'questId', $questId, $success, $message);
                return $success;
            });

            $this->CI->utils->debug_log("requestQuest-$playerId", 'questId', $questId, $transSucc, $message);

            if(!$transSucc) {
                throw new \APIException($message);
            }
        }
        catch (Exception $e) {
            $this->CI->utils->debug_log('requestQuest Exception', $e->getMessage());
            $transSucc = false;
            $message = $e->getMessage();
        }

        return array($transSucc, $message);
    }

    public function getQuestPeriodTypeDatetime($questCategory)
    {
        $fromDatetime = null;
        $toDatetime = null;
        $showTimer = !empty($questCategory['showTimer']) ? $questCategory['showTimer'] : false;

        if($showTimer){
            $startAt = !empty($questCategory['startAt']) ? $questCategory['startAt'] : null;
            $endAt = !empty($questCategory['endAt']) ? $questCategory['endAt'] : null;
        }

        if (array_key_exists('period', $questCategory)) {
            $period = $questCategory['period'];

            list($fromDatetime, $toDatetime) = $this->CI->utils->getLimitDateRangeForPromo($period);

            if($period == Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE && $showTimer){
                $fromDatetime = $startAt;
                $toDatetime = $endAt;
            }

            if($showTimer && $fromDatetime < $startAt){
                $fromDatetime = $startAt;
            }
            $this->CI->utils->debug_log('getLimitDateRangeForPromo', 'fromDatetime', $fromDatetime, 'toDatetime', $toDatetime, 'period', $period);
        }

        return array($fromDatetime, $toDatetime);
    }

    public function createQuestProgress($playerId, $questId, $questRule, $isHierarchy, $fromDatetime, $toDatetime, $isInteract = false, $categoryId = null)
	{
		$questConditionResult = $this->getPlayerQuestProgressStatus($playerId, $questId, $questRule, $fromDatetime, $toDatetime, $isInteract, $categoryId);

		$statsData = [
			'playerId' => $playerId,
			'questManagerId' => $questId,
			'jobStats' => $questConditionResult['jobStats'],
			'rewardStatus' => $questConditionResult['rewardStatus'],
			'createdAt' => $this->CI->utils->getNowForMysql(),
			'updatedAt' => $this->CI->utils->getNowForMysql(),
		];

		if($isHierarchy){
			$statsData['questJobId'] = $questRule['questJobId'];
		}
		$questProgress = $this->quest_manager->createQuestProgress($statsData);

		$this->CI->utils->debug_log(__METHOD__, "createQuestProgressResult-$playerId", $questProgress);
		return $questProgress;
	}

    public function getPlayerQuestProgressStatus($playerId, $questId, $questRule, $fromDatetime = null, $toDatetime = null, $isInteract = false, $categoryId = null)
	{
		$questConditionResult = [
			'jobStats' => 0,
			'rewardStatus' => 1,// 1: not achieved , 2:achieved Not received, 3:achieved and received, 4:expired
		];

        if (empty($fromDatetime) && empty($toDatetime)) {
            $getPlayerInfoById = $this->CI->player_model->getPlayerInfoById($playerId);
            $startAt = $getPlayerInfoById['playerCreatedOn'];
		    $endAt = $this->CI->utils->getNowForMysql();
        }else{
            $startAt = $fromDatetime;
            $endAt = $toDatetime;
        }

        $this->CI->utils->debug_log(__METHOD__, "questRule-$playerId", $startAt, $endAt, $questRule);

		switch ($questRule['questConditionType']) {
			case 1:// signle deposit
				$depositList = $this->CI->sale_order->getDepositListBy($playerId, $startAt, $endAt, $questRule['questConditionValue']);
                $this->CI->utils->debug_log(__METHOD__, "depositList-$playerId", $depositList);
				$depositAmount = count($depositList) > 0 ? $depositList[0]->amount : 0 ;
				$questConditionResult['jobStats'] = $depositAmount;
				$questConditionResult['rewardStatus'] = $depositAmount >= $questRule['questConditionValue'] ? 2 : 1;
				break;
			case 2:// total deposit
                list($bets, $totalDepositAmount) = $this->CI->player_model->getBetsAndDepositByDate($playerId, $startAt, $endAt);
                $this->CI->utils->debug_log(__METHOD__, "totalDepositAmount-$playerId", $totalDepositAmount);
				$questConditionResult['jobStats'] = $totalDepositAmount > 0 ? $totalDepositAmount : 0;
				$questConditionResult['rewardStatus'] = $totalDepositAmount >= $questRule['questConditionValue'] ? 2 : 1;
				break;
			case 3:// signle bet
                $betList = $this->CI->game_logs->getPlayersGameLogsByDate($playerId, $startAt, $endAt, $questRule['questConditionValue']);
				$this->CI->utils->debug_log(__METHOD__, "betList-$playerId", $betList, 'startAt', $startAt, 'endAt', $endAt);
                $betAmount = !empty($betList) ? $betList['bet_amount'] : 0;
				$questConditionResult['jobStats'] = $betAmount;
				$questConditionResult['rewardStatus'] = $betAmount >= $questRule['questConditionValue'] ? 2 : 1;
				break;
			case 4:// total bet
                $playerTotalBetWinLoss = $this->CI->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $startAt, $endAt);
                $bets = $playerTotalBetWinLoss['total_bet'];
                $this->CI->utils->debug_log(__METHOD__, "bets-$playerId", $bets);
				$questConditionResult['jobStats'] = $bets > 0 ? $bets : 0;
				$questConditionResult['rewardStatus'] = $bets >= $questRule['questConditionValue'] ? 2 : 1;
				break;
			case 5:// friend referral
                if($this->CI->utils->getConfig('enable_player_invite_calculation')){
                    $friReferral = $this->CI->player_friend_referral->getPlayerInvitations($playerId, $categoryId, $startAt, $endAt);
                    $this->CI->utils->debug_log(__METHOD__, "friReferral-getPlayerInvitations-$playerId", $friReferral);
                    $questConditionResult['jobStats'] = !empty($friReferral) ? $friReferral->totalValidInvites : 0;
                }else{
                    $friReferral = $this->CI->player_friend_referral->getPlayerReferral($playerId, player_friend_referral::STATUS_PAID, $startAt, $endAt);
                    $this->CI->utils->debug_log(__METHOD__, "friReferral-$playerId", $friReferral);
                    $questConditionResult['jobStats'] = count($friReferral) > 0 ? count($friReferral) : 0;
                }
				$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] >= $questRule['questConditionValue'] ? 2 : 1;
				break;
			case 6:// register 0/1
				$questConditionResult['jobStats'] = 1;
				$questConditionResult['rewardStatus'] = 2;
				break;
			case 7:// profile 0/1
				$personalInfoType = isset($questRule['personalInfoType']) ? $questRule['personalInfoType'] : 0;
				if ($personalInfoType == 1) {// first name + last name
					$questConditionResult['jobStats'] = $this->isVerifiedRealName($playerId) ? 1 : 0;
					$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				}elseif ($personalInfoType == 2) {// contact number
					$questConditionResult['jobStats'] = $this->isVerifiedPhone($playerId) ? 1 : 0;
					$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				}elseif ($personalInfoType == 3) {// email
					$questConditionResult['jobStats'] = $this->isVerifiedEmail($playerId) ? 1 : 0;
					$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				}elseif ($personalInfoType == 4) {// cpf
					$questConditionResult['jobStats'] = $this->isVerifiedBindCPF($playerId)? 1 : 0;
					$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				}
				break;
			case 8:// download app 0/1
				$questConditionResult['jobStats'] = $this->isDownloadApp($playerId) ? 1 : 0;
				$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				break;
			case 9:// Follow channel 0/1
                $questConditionResult['jobStats'] = $this->isFollowingSocialPlatform($playerId, $startAt, $endAt, $isInteract) ? 1 : 0;
                $questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
				break;
			// case 10:// community Option 0/1
			// 	$questConditionResult['jobStats'] = $this->isFollowingSocialPlatform($playerId, $questRule['communityOptions']) ? 1 : 0;
			// 	$questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
			// 	break;
            case 11:// add bank account 0/1
                $questConditionResult['jobStats'] = $this->isFilledBankAccount($playerId) ? 1 : 0;
                $questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
                break;
            case 12:// share to social media 0/1
                $questConditionResult['jobStats'] = $this->isShareSocialMedia($playerId, $startAt, $endAt, $isInteract) ? 1 : 0;
                $questConditionResult['rewardStatus'] = $questConditionResult['jobStats'] == 1 ? 2 : 1;
                break;
			default:
				$questConditionResult['jobStats'] = 0;
				break;
		}

		$this->CI->utils->debug_log(__METHOD__, "ruleResult-$playerId", $questConditionResult);
		return $questConditionResult;
	}

	public function isVerifiedRealName($playerId, $usePlayerkyc = false)
	{
        $verified_real_name = false;
        if($usePlayerkyc){
            $this->CI->load->library('player_security_library');
            $player_verification = $this->CI->player_security_library->player_verification_info($playerId);
            $verified_real_name = $player_verification['verified'];
        } else {
            $playerInfo = $this->CI->player_model->getPlayerInfoById($playerId);
            $verified_real_name = !empty($playerInfo['firstName']) && !empty($playerInfo['lastName']);
        }

        return $verified_real_name;
    }

	public function isVerifiedPhone($playerId)
	{
        $verified_phone = $this->CI->player_model->isVerifiedPhone($playerId);
        if(!$verified_phone){
            $this->CI->utils->debug_log('not verified phone',['result' => $verified_phone]);
        }

        return $verified_phone;
    }

    public function isVerifiedEmail($playerId)
	{
        $verified_email = $this->CI->player_model->isVerifiedEmail($playerId);
        if(!$verified_email){
            $this->CI->utils->debug_log('not verified email',['result' => $verified_email]);
        }

        return $verified_email;
    }

	public function isVerifiedBindCPF($playerId, $verifyCpfFromKyc = false)
	{
        if($verifyCpfFromKyc){
            $verified_cpf_info = false;
            $this->CI->load->model('kyc_status_model');
            $verified_cpf_info = $this->CI->kyc_status_model->player_valid_documents($playerId);
        }else{
            $verified_cpf_info = $this->CI->player_model->isFilledCPFnumber($playerId);
        }

        return $verified_cpf_info;
    }

	public function isDownloadApp($playerId)
	{
		$isDownloadApp =  false;
        $by_login_report = true;
        if($by_login_report){
            $this->CI->load->model('player_login_report');
            $login_report_records = $this->CI->player_login_report->existsPlayerLoginByApp($playerId);
            $isDownloadApp = !!$login_report_records;
        } 
        if(!$isDownloadApp) {
            $this->CI->load->model('http_request');
            $by_domains = [];

			$getPlayerInfoById = $this->CI->player_model->getPlayerInfoById($playerId);
			$playerRegisterDate = $getPlayerInfoById['playerCreatedOn'];

            $historyFrom=new DateTime($playerRegisterDate);
            $historyTo=new DateTime($this->CI->utils->getNowForMysql());
            $login_list = $this->CI->http_request->getPlayerLoginList($historyFrom, $historyTo, $playerId);
            $this->CI->utils->debug_log('login_list_'.$playerId, $login_list);
            foreach ($login_list as $login) {
                $filter_referrer = array_filter($by_domains, function($domain) use ($login) {
                    return strpos($login['referrer'], $domain) !== false;
                });
                $this->CI->utils->debug_log('filter_referrer_'.$playerId, $filter_referrer);
                $isDownloadApp = !empty($filter_referrer);
                if($isDownloadApp) {
                    break;
                }
            }
        }

		$this->CI->utils->debug_log('download app',['result' => $isDownloadApp]);

		return $isDownloadApp;
	}

	public function isFollowingSocialPlatform($playerId, $startAt, $endAt, $isInteract, $options = 1)
	{
        $this->CI->load->model('player_trackingevent');

		// player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_MISSION_FOLLOW_TELEGRAM = 100
		$sourceType = $options == 1 ? 100 : $options;
        $hasRecord = $this->CI->player_trackingevent->getNotifyBySourceAndDate($playerId, $sourceType, $startAt, $endAt);

        if(!$hasRecord && $isInteract) {
            $result = $this->CI->player_trackingevent->createSettledNotify($playerId, $sourceType, array());
            $this->CI->utils->debug_log(__METHOD__ .'_'.$playerId , ["isInteract"=>$isInteract, "hasRecord" => $hasRecord, "createSettledNotify"=>$result]);

            $hasRecord = $result ? true : false;
        }
        $this->CI->utils->debug_log(__METHOD__ .'_'.$playerId , ["isInteract"=>$isInteract, "hasRecord" => $hasRecord]);

		return $hasRecord;
    }

    public function isShareSocialMedia($playerId, $startAt, $endAt, $isInteract, $options = 1)
    {
        $this->CI->load->model('player_trackingevent');
        //TRACKINGEVENT_SOURCE_TYPE_QUEST_SHARE_SOCIAL_MEDIA = 101
        $sourceType = $options == 1 ? 101 : $options;
        $hasRecord = $this->CI->player_trackingevent->getNotifyBySourceAndDate($playerId, $sourceType, $startAt, $endAt);

        if(!$hasRecord && $isInteract) {
            $result = $this->CI->player_trackingevent->createSettledNotify($playerId, $sourceType, array());
            $this->CI->utils->debug_log(__METHOD__ .'_'.$playerId , ["isInteract"=>$isInteract, "hasRecord" => $hasRecord, "createSettledNotify"=>$result]);

            $hasRecord = $result ? true : false;
        }
        $this->CI->utils->debug_log(__METHOD__ .'_'.$playerId , ["isInteract"=>$isInteract, "hasRecord" => $hasRecord]);

        return $hasRecord;
    }

    public function isFilledBankAccount($playerId){
        $this->CI->load->model(['playerbankdetails']);
        $bankdetails = $this->CI->playerbankdetails->getNotDeletedBankInfoList($playerId);
        $hasBankAccount = !empty((!empty($bankdetails['deposit']) || !empty($bankdetails['withdrawal']))) ? true : false;
        if(!$hasBankAccount){
            $this->CI->utils->debug_log('not add bank account',['result' => $hasBankAccount]);
        }

        return $hasBankAccount;
    }

    public function verifyQuestCountdownExpired($questId)
	{
        $result = ['passed' => true, 'errorMessage' => ''];

        $managerDetail = $this->getQuestManagerDetails($questId);

        if (empty($managerDetail)) {
            $result['errorMessage'] = lang('Quest manager is not found.');
            $result['passed'] = false;
            $this->CI->utils->debug_log(__METHOD__, $result, $questId);
            return $result;
        }

        $categoryId = $managerDetail['questCategoryId'];
        $categoryDetails = $this->getQuestCategoryDetails($categoryId);

        if (empty($categoryDetails)) {
            $result['errorMessage'] = lang('Quest category is not found.');
            $result['passed'] = false;
            $this->CI->utils->debug_log(__METHOD__, $result, $categoryId, $managerDetail);
            return $result;
        }

        $showTimer = isset($categoryDetails['showTimer']) ? $categoryDetails['showTimer'] : false;

		if ($showTimer) {
            if ($categoryDetails['coverQuestTime'] == 1){
                $endAt = $categoryDetails['endAt'];
            }else{
                $endAt = $managerDetail['endAt'];
            }

            $currTime = $this->CI->utils->getNowForMysql();
            if ($currTime > $endAt) {
                $result['passed'] = false;
                $result['errorMessage'] = lang('Quest countdown is expired.');
                $this->CI->utils->debug_log(__METHOD__, $result, $currTime, $endAt, $questId, $categoryId, $categoryDetails, $managerDetail);
            }
        }
        return $result;
	}

    public function deleteQuestCategoryCache($categoryId, $currency)
    {
		$getLoopQuestCategoryKey = "getQuestCategory-loop-$currency";
		$getSwitchQuestCategoryKey = "getQuestCategory-switch-$currency-$categoryId";
        $getQuestCategoryDetailsCacheKey = "getQuestCategoryDetailsCacheKey-$categoryId";
		$this->CI->utils->debug_log('=========getLoopQuestCategoryKey', $getLoopQuestCategoryKey, 'getSwitchQuestCategoryKey', $getSwitchQuestCategoryKey, 'getQuestCategoryDetailsCacheKey', $getQuestCategoryDetailsCacheKey);
        $this->CI->utils->deleteCache($getLoopQuestCategoryKey);
		$this->CI->utils->deleteCache($getSwitchQuestCategoryKey);
        $this->CI->utils->deleteCache($getQuestCategoryDetailsCacheKey);
	}

	public function deleteQuestManagerCache($categoryId, $currency)
    {
		$getQuestManagerCacheKey = "getQuestManagerCacheKey-$currency-$categoryId";
		$this->CI->utils->debug_log('=========getQuestManagerCacheKey', $getQuestManagerCacheKey);
		$this->CI->utils->deleteCache($getQuestManagerCacheKey);

        $managerList = $this->quest_manager->getQuestManagerByCategoryId($categoryId);
        if (!empty($managerList)) {
            foreach ($managerList as $manager) {
                $managerId = $manager['questManagerId'];
                $getQuestManagerDetailsCacheKey = "getQuestManagerDetailsCacheKey-$managerId";
                $this->CI->utils->debug_log('=========deleteQuestManagerCache getQuestManagerDetailsCacheKey', $getQuestManagerDetailsCacheKey);
                $this->CI->utils->deleteCache($getQuestManagerDetailsCacheKey);
            }
        }
	}

    public function getQuestManagerDetailsWithCategory($managerId)
    {
        $managerDetail = !empty($this->getQuestManagerDetails($managerId)) ? $this->getQuestManagerDetails($managerId) : [];
		$categoryId = isset($managerDetail['questCategoryId']) ? $managerDetail['questCategoryId'] : null;
		$categoryDetails = !empty($categoryId) ? $this->getQuestCategoryDetails($categoryId) : [];
        return array($managerDetail, $categoryDetails);
    }

    public function getQuestManagerDetails($managerId)
	{
		$getQuestManagerDetailsCacheKey = "getQuestManagerDetailsCacheKey-$managerId";
		// $this->CI->utils->debug_log(__METHOD__, '======getQuestManagerDetailsCacheKey', $getQuestManagerDetailsCacheKey);
		$getQuestManagerDetailsCacheResult = $this->CI->utils->getJsonFromCache($getQuestManagerDetailsCacheKey);
		if (!empty($getQuestManagerDetailsCacheResult)) {
			// $this->CI->utils->debug_log(__METHOD__, ['cached_result' => $getQuestManagerDetailsCacheResult]);
			$managerDetails = $getQuestManagerDetailsCacheResult;
		} else {
			$managerDetails = $this->quest_manager->getQuestManagerDetailsById($managerId);
			$ttl = 4 * 60 * 60;
			$this->CI->utils->saveJsonToCache($getQuestManagerDetailsCacheKey, $managerDetails, $ttl);
		}
		return $managerDetails;
	}

	public function getQuestCategoryDetails($categoryId)
	{
		$getQuestCategoryDetailsCacheKey = "getQuestCategoryDetailsCacheKey-$categoryId";
		// $this->CI->utils->debug_log(__METHOD__, '======getQuestCategoryDetailsCacheKey', $getQuestCategoryDetailsCacheKey);
		$getQuestCategoryDetailsCacheResult = $this->CI->utils->getJsonFromCache($getQuestCategoryDetailsCacheKey);
		if (!empty($getQuestCategoryDetailsCacheResult)) {
			// $this->CI->utils->debug_log(__METHOD__, ['cached_result' => $getQuestCategoryDetailsCacheResult]);
			$categoryDetails = $getQuestCategoryDetailsCacheResult;
		} else {
			$categoryDetails = $this->quest_manager->getQuestCategoryDetails($categoryId);
			$ttl = 4 * 60 * 60;
			$this->CI->utils->saveJsonToCache($getQuestCategoryDetailsCacheKey, $categoryDetails, $ttl);
		}
		return $categoryDetails;
	}

    public function mappingQuestPanel($panel)
    {
        switch ($panel) {
            case 0:
                return 1;
            case 1:
                return 3;
            case 2:
                return 2;
            default:
                return 0;
        }
    }

    public function deletePlayerQuestProgressCache($playerId, $managerId, $currency = null)
    {
        if (empty($currency)) {
            $currency = $this->CI->utils->getCurrentCurrency()['currency_code'];
            $currency = strtoupper($currency);
        }
        $getQuestProgressByPlayerCacheKey = "getQuestProgressByPlayerCacheKey-$currency-$playerId-$managerId";
        $this->CI->utils->debug_log(__METHOD__, 'deleteCache', $getQuestProgressByPlayerCacheKey);
        $this->CI->utils->deleteCache($getQuestProgressByPlayerCacheKey);
    }
}