<?php
/**
 * will run generatePlayerQuestState
 * config cronjob
 * active SBE quest manager
 * player claim success will check the next quest
 */
trait quest_command_module
{
    public function generatePlayerQuestState($questCategoryId = _COMMAND_LINE_NULL, $questManagerId = _COMMAND_LINE_NULL, $playerId = _COMMAND_LINE_NULL, $suffix4mdb = '')
    {
        $this->load->model(['quest_manager']);
        $this->load->library(['quest_library']);

        $resultData = [
            'success' => false,
            'result' => [],
            'totalProcessTime' => 0,
        ];

        try{
            $startTime = microtime(true);
            $enabledQuest = $this->utils->getConfig('enabled_quest');
            $this->utils->info_log('start generatePlayerQuestState enabledQuest', $enabledQuest, 'playerId', $playerId, 'questManagerId', $questManagerId, 'questCategoryId', $questCategoryId);

            $bpquOptions = $this->config->item('batch_player_quest_update');
            $this->utils->debug_log('bpquOptions', $bpquOptions);

            $enabledCacheVerfiy = isset($bpquOptions['enabledCacheVerfiy']) ? $bpquOptions['enabledCacheVerfiy'] : true;
            $overRunningTime = isset($bpquOptions['overRunningTime']) ? $bpquOptions['overRunningTime'] : 3600;
            $idleSec = isset($bpquOptions['idleSec']) ? $bpquOptions['idleSec'] : 0;
            $cacheKeyTtl = isset($bpquOptions['cacheKeyTtl']) ? $bpquOptions['cacheKeyTtl'] : 1;
            $notifyUser = isset($bpquOptions['notifyUser']) ? $bpquOptions['notifyUser'] : $this->db->database;
            $ttl = $cacheKeyTtl * 60 * 60;
            $allowedExecutionTime = isset($bpquOptions['allowedQuestManagerExecutionTime']) ? $bpquOptions['allowedQuestManagerExecutionTime'] : ['3' => ['start' => '12:30', 'end' => '13:00']];

            $getCommandQuestCacheKey = "commandGeneratePlayerQuestStateCacheKey";
            $getQuestCacheResult = $this->utils->getJsonFromCache($getCommandQuestCacheKey);
            $this->utils->debug_log(__METHOD__, 'getCommandQuestCacheKey', $getCommandQuestCacheKey, 'getQuestCacheResult', $getQuestCacheResult);

            if (!$enabledQuest) {
                throw new Exception("quest is not enabled");
            }

            if ($enabledCacheVerfiy && $getQuestCacheResult === 1) {
                throw new Exception("command is already running");
            }

            $this->utils->saveJsonToCache($getCommandQuestCacheKey, 1, $ttl);

            // Check if the command is already running
            $hasSelf = null;
            $currPS = null;
            $match = null;
            $funcName = __FUNCTION__;
            $funcList = [$funcName];
            $isEnabledMDB=$this->utils->isEnabledMDB();
            $scriptType = 'php';
            $isManual = !empty($questManagerId) && $questManagerId != _COMMAND_LINE_NULL;

            $_this = $this;
            $_isExecingCB = function($match) use ($funcName, &$hasSelf, &$_this) { // isExecingCB
                $hasSelf = false;
                $isExecing = null;

                if(!empty($match)){
                    foreach($match as $ps) {
                        if(empty($ps[0])){
                            continue;
                        }
                        if(strpos($ps[0], $funcName) !== false){
                            $hasSelf = true;
                            break;
                        }
                    }
                }

                if($hasSelf){
                    /// will call $this->isExecingCB4Self($match);
                    $isExecing = call_user_func_array([$_this,'isExecingCB4Self'], [$match]);
                }else{
                    /// will call $this->isExecingCB4Related($match);
                    $isExecing = call_user_func_array([$_this,'isExecingCB4Related'], [$match]);
                }

                return $isExecing;
            }; // EOF $_isExecingCB

            if( ! $isEnabledMDB ){
                // Disabled MDB
                $is_execing = $this->isExecingListWithPS($funcList, $this->oghome, $_isExecingCB, $currPS, $match);
            }else{
                // Enabled MDB
                $is_execing = $this->isExecingListWithPSWithMDB($funcList, $this->oghome, $_isExecingCB, $currPS, $match, $funcName, $suffix4mdb, $scriptType);
            }

            $this->utils->debug_log($funcName, '------------------is_execing:', $is_execing, 'hasSelf:', $hasSelf, 'currPS:', $currPS, 'match:', $match);

            $questManagers = [];
            $isOverWaitingTime = false;
            $this->utils->debug_log($funcName, ' is_execing:', $is_execing,'isOverWaitingTime:', $isOverWaitingTime);
            if (!$is_execing && !$isOverWaitingTime ) {
                $questManagers = $this->quest_manager->getAllQuestManager($questCategoryId, $questManagerId);
                $this->utils->info_log(__METHOD__, 'questManagers', $questManagers);
                if (!empty($questManagers)) {

                    $this->idleSec($idleSec);
                    $time_exec_begin = date('c', time());
                    $this->utils->debug_log(__METHOD__, 'Begin execution', [ 'time_exec_begin' => $time_exec_begin ]);
                    set_time_limit(0);

                    $managerData = [];
                    foreach ($questManagers as $questManager) {
                        $this->utils->debug_log(__METHOD__, ['questManager' => $questManager]);

                        if(!$this->isExecutionAllowed($questManager['questManagerType'], $allowedExecutionTime, $isManual)){
                            $this->utils->debug_log(__METHOD__, 'isExecutionAllowed is false', $questManager['questManagerId']);
                            continue;
                        }

                        $is_blocked = false;
                        $db = $this->CI->db;
                        $res = null;
                        $command = 'oneworkSyncQuestManager';
                        $this->utils->info_log(__METHOD__, 'funcName', $command);
                        $dbName = !empty($db) ? $db->getOgTargetDB() : null;

                        $file_list = [];
                        $command_params = [
                            $questManager['questManagerId'],
                            $playerId,
                            $isManual
                        ];

                        $cmd = $this->utils->generateCommandLine($command, $command_params, $is_blocked, $file_list, $dbName);

                        $this->utils->info_log(__METHOD__, 'cmd' . (empty($db) ? ' empty db' : ' db'), $cmd, $dbName);

                        if (!empty($cmd)) {
                            $res = $this->utils->runCmd($cmd);
                            $managerData[] = [
                                'questManagerId' => $questManager['questManagerId'],
                                'res' => $res,
                            ];
                            $this->utils->info_log(__METHOD__, 'res', $res);
                        }
                    }

                    $resultData = [
                        'success' => true,
                        'result' => $managerData,
                    ];
                }
            }else {
                if($isOverWaitingTime){
                    $this->utils->debug_log($funcName. ' is over waiting times.');
                    throw new Exception($funcName. ' is over waiting times.');
                }
                if($is_execing){
                    if($hasSelf){
                        /// $hasSelf Not exactly correct!
                        // Because "batch_player_level_upgrade" is the part of "batch_player_level_upgrade_check_hourly".
                        $msg = $funcName. ' is already running.';
                    }else{
                        $msg = 'The related task is already running.';
                    }
                    $this->utils->debug_log($msg, '$currPS:', $currPS);
                    throw new Exception($msg);
                }
            }
        }catch(\Throwable $th){
            $message = $th->getMessage();
            $this->utils->error_log('generatePlayerQuestState Failed', ['questCategoryId' => $questCategoryId, 'questManagerId' => $questManagerId, 'message' => $message]);
            $this->notificationMMWhenSyncQuestFailed($message, $questManagerId, $notifyUser);
        }finally{
            $this->utils->saveJsonToCache($getCommandQuestCacheKey, 0, 0);
            $getQuestCacheResult = $this->utils->getJsonFromCache($getCommandQuestCacheKey);
            $this->utils->info_log('finally process clear cache', 'getCommandQuestCacheKey', $getCommandQuestCacheKey, 'getQuestCacheResult', $getQuestCacheResult);

            $endTime = microtime(true);
            $processTime = $endTime - $startTime;

            if ($processTime > $overRunningTime) {
                $title = $isManual ? "Manual sync is over running. ($notifyUser)" : "Auto sync is over running. ($notifyUser)";
                $resultData['totalProcessTime'] = $processTime;
                $this->notificationMMWhenResultFailed($title, $resultData);
            }

            $this->utils->info_log('finally process generatePlayerQuestState', 'resultData', $resultData);

            return $resultData;
        }
    }

    public function oneworkSyncQuestManager($questManagerId, $playerId = _COMMAND_LINE_NULL, $isManual = false)
    {
        $this->utils->info_log("start oneworkSyncQuestManager-$questManagerId", 'playerId', $playerId, 'isManual', $isManual);
        $this->load->model(['quest_manager']);
        $this->load->library(['quest_library']);
        $resultData = [
            'success' => false,
            'result' => [],
            'totalProcessTime' => 0,
        ];
        $startTime = microtime(true);
        try {

            $bpquOptions = $this->config->item('batch_player_quest_update');
            $this->utils->debug_log('bpquOptions', $bpquOptions);
            $notifyUser = isset($bpquOptions['notifyUser']) ? $bpquOptions['notifyUser'] : $this->db->database;
            $overRunningTime = isset($bpquOptions['overRunningTime']) ? $bpquOptions['overRunningTime'] : 3600;
            $onlyRunOneRecord = isset($bpquOptions['onlyRunOneRecord']) ? $bpquOptions['onlyRunOneRecord'] : true;
            $lastLoginTimeStart = isset($bpquOptions['lastLoginTimeStart']) ? $bpquOptions['lastLoginTimeStart'] : '-2 days';
            $lastLoginTimeEnd = isset($bpquOptions['lastLoginTimeEnd']) ? $bpquOptions['lastLoginTimeEnd'] : 'now';
            $idleSec = isset($bpquOptions['idleSec']) ? $bpquOptions['idleSec'] : 0;
            $dryRun = isset($bpquOptions['dryRun']) ? $bpquOptions['dryRun'] : false;
            $testException = isset($bpquOptions['testException']) ? $bpquOptions['testException'] : false;

            $questManager = $this->quest_manager->getQuestManagerDetailsById($questManagerId);
            $questManagerId = $questManager['questManagerId'];
            $levelType = $questManager['levelType'];
            $questRuleId = $questManager['questRuleId'];
            $isHierarchy = $levelType == Quest_manager::QUEST_LEVEL_TYPE_HIERARCHY;
            $categoryId = $questManager['questCategoryId'];
            $categoryDetails = $this->quest_manager->getQuestCategoryDetails($categoryId);

            $this->idleSec($idleSec);
            $time_exec_begin = date('c', time());
            $this->utils->debug_log(__METHOD__, 'Begin execution', [ 'time_exec_begin' => $time_exec_begin ]);
            set_time_limit(0);

            if ($testException){
                throw new Exception("test exception");
            }

            $verify = $this->quest_library->verifyQuestCountdownExpired($questManagerId);
            if (!$verify['passed']) {
                throw new Exception("Manager is expired");
            }

            list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($categoryDetails);
            $conditions = ['rewardStatus' => [Quest_manager::QUEST_REWARD_STATUS_NOT_ACHIEVED, Quest_manager::QUEST_REWARD_STATUS_ACHIEVED_NOT_RECEIVED]];

            if ($isHierarchy) {
                $activePlayerIds = $this->getActivePlayerIds($lastLoginTimeStart, $lastLoginTimeEnd);
                $playerIdsToQuery = $playerId != _COMMAND_LINE_NULL ? $playerId : (!empty($activePlayerIds) ? $activePlayerIds : _COMMAND_LINE_NULL);
            } else {
                $playerIdsToQuery = $playerId;
            }

            $questStatePlayerList = $this->quest_manager->getQuestProgressByPlayer($playerIdsToQuery, $questManagerId, $fromDatetime, $toDatetime, $isHierarchy, null, $conditions);

            if ($onlyRunOneRecord) {
                if ($isHierarchy) {
                    $questStatePlayerList = $this->filterFirstQuestStateRecordPerPlayer($questStatePlayerList, $dryRun);
                }else{
                    $questStatePlayerList = $this->_mappingQuestStatePlayerList2($questStatePlayerList, $lastLoginTimeStart, $lastLoginTimeEnd, $dryRun);
                }
            }

            $this->utils->info_log(__METHOD__, "questStatePlayerList-$questManagerId",
                'fromDatetime', $fromDatetime,
                'toDatetime', $toDatetime,
                'playerId', $playerId,
                'isHierarchy', $isHierarchy,
                'onlyRunOneRecord', $onlyRunOneRecord,
                'count players', count($questStatePlayerList)
            );

            if (empty($questStatePlayerList)) {
                $this->utils->info_log(__METHOD__, "questStatePlayerList-$questManagerId", 'empty player list');
                $resultData = [
                    'success' => true,
                    'result' => [
                        'questManagerId' => $questManagerId,
                        'processCount' => 0,
                    ],
                ];
                return $resultData;
            }

            $successPlayer = [];
            $failPlayer = [];
            foreach ($questStatePlayerList as $questStatePlayer) {
                $questStateId = $questStatePlayer['id'];
                $questStatePlayerId = $questStatePlayer['playerId'];
                $rewardStatus = $questStatePlayer['rewardStatus'];
                $questJobId = $questStatePlayer['questJobId'];
                $managerId = $questStatePlayer['questManagerId'];

                $this->utils->debug_log(__METHOD__, "questStatePlayer-$questStatePlayerId", $questStatePlayer);

                if ($rewardStatus == Quest_manager::QUEST_REWARD_STATUS_RECEIVED || $rewardStatus == Quest_manager::QUEST_REWARD_STATUS_EXPIRED) {
                    $this->utils->debug_log(__METHOD__, 'rewardStatus is done or expired', $rewardStatus);
                    continue;
                }

                if ($isHierarchy && !empty($questJobId)){//階梯
                    $questRule = $this->quest_manager->getQuestRuleByJobId($questJobId);
                }else{
                    $questRule = $this->quest_manager->getQuestRuleByQuestRuleId($questRuleId);
                }

                $this->utils->debug_log(__METHOD__, "questRulePlayerList-$questStatePlayerId", $questRule);

                $controller = $this;
                $success = $this->lockAndTransForPlayerQuest($questStatePlayerId, function () use ($controller, $questStatePlayerId, $questStateId, $managerId, $questRule, &$statsData, $fromDatetime, $toDatetime, $categoryId) {

                    $isInteract = false;
                    if ($questRule['questConditionType'] == 9) {
                        $isInteract = true;
                    }

                    $questConditionResult = $controller->quest_library->getPlayerQuestProgressStatus($questStatePlayerId, $managerId, $questRule, $fromDatetime, $toDatetime, $isInteract, $categoryId);
                    $this->utils->debug_log(__METHOD__, "questConditionResult-$questStatePlayerId", $questConditionResult);

                    $statsData = [
                        'jobStats' => $questConditionResult['jobStats'],
                        'rewardStatus' => $questConditionResult['rewardStatus'],
                        'updatedAt' => $this->utils->getNowForMysql(),
                    ];

                    $succ =  $controller->quest_manager->updatePlayerQuestJobState($questStatePlayerId, $managerId, $questStateId, $statsData);
                    $this->utils->debug_log(__METHOD__, "updatePlayerQuestJobState-$questStatePlayerId", $statsData, 'succ', $succ);

                    return $succ;
                });

                if ($success) {
                    $successPlayer[$questStatePlayerId] = $statsData;
                    $this->quest_library->deletePlayerQuestProgressCache($questStatePlayerId, $managerId);
                }else{
                    $failPlayer[$questStatePlayerId] = $statsData;
                }
            }

            $this->utils->debug_log("end foreach players-managerId-$questManagerId",
                'count successPlayer', count($successPlayer),
                'count failPlayer', count($failPlayer)
            );

            $resultData = [
                'success' => true,
                'result' => [
                    'questManagerId' => $questManagerId,
                    'processCount' => count($successPlayer) + count($failPlayer),
                    'successCount' => count($successPlayer),
                    'failCount' => count($failPlayer),
                ],
            ];

        }catch(\Throwable $th){
            $message = $th->getMessage();
            $this->utils->error_log('generatePlayerQuestState Failed', ['questManagerId' => $questManagerId, 'message' => $message]);
            $this->notificationMMWhenSyncQuestFailed($message, $questManagerId, $notifyUser);
        }finally {
            $endTime = microtime(true);
            $processTime = $endTime - $startTime;

            if ($processTime > $overRunningTime) {
                $title = $isManual == 'true' ? "Manual sync is over running. ($notifyUser)" : "Auto sync is over running. ($notifyUser)";
                $resultData['totalProcessTime'] = $processTime;
                $this->notificationMMWhenResultFailed($title, $resultData);
            }

            $this->utils->info_log("end syncQuestManager-$questManagerId", 'resultData', $resultData);

            return $resultData;
        }
    }


    private function getActivePlayerIds($lastLoginTimeStart, $lastLoginTimeEnd)
    {
        $startTime = microtime(true);
        $lastLoginTimeBeginDateTime = new DateTime($lastLoginTimeStart);
        $lastLoginTimeEndDateTime = new DateTime($lastLoginTimeEnd);
        $start = $this->utils->formatDateTimeForMysql($lastLoginTimeBeginDateTime);
        $end = $this->utils->formatDateTimeForMysql($lastLoginTimeEndDateTime);
        $activePlayers = $this->player_model->getAllEnabledPlayersByActivityTime($start, $end);

        $activePlayerIds = array_column($activePlayers, 'playerId');

        $this->utils->info_log(__METHOD__, 'count activePlayerIds', count($activePlayerIds), 'time', microtime(true) - $startTime);
        return $activePlayerIds;
    }

    private function filterFirstQuestStateRecordPerPlayer($questStatePlayerList, $dryRun = false)
    {
        $startTime = microtime(true);
        $filteredData = [];
        $seenPlayerIds = [];

        foreach ($questStatePlayerList as $item) {
            if (!isset($seenPlayerIds[$item['playerId']])) {
                $filteredData[] = $item;
                $seenPlayerIds[$item['playerId']] = true;
            }
        }

        $this->utils->info_log(__METHOD__, 'total', count($questStatePlayerList), 'filtered', count($filteredData), 'time', microtime(true) - $startTime);

        if ($dryRun) {
            return [];
        }

        return $filteredData;
    }

    public function _mappingQuestStatePlayerList2($questStatePlayerList, $lastLoginTimeStart, $lastLoginTimeEnd, $dryRun = false)
    {
        $filteredData = [];

        if (empty($questStatePlayerList)) {
            return $filteredData;
        }

        $startTime = microtime(true);
        $lastLoginTimeBeginDateTime = new DateTime( $lastLoginTimeStart ); // -1 days
        $lastLoginTimeEndDateTime = new DateTime( $lastLoginTimeEnd ); // now
        $start = $this->utils->formatDateTimeForMysql($lastLoginTimeBeginDateTime);
        $end = $this->utils->formatDateTimeForMysql($lastLoginTimeEndDateTime);
        $activePlayers = $this->player_model->getAllEnabledPlayersByActivityTime($start, $end);

        $activePlayerIds = [];
        foreach ($activePlayers as $activePlayer) {
           $activePlayerIds[$activePlayer->playerId] = true;
        }

        $this->utils->info_log(" start _mappingQuestStatePlayerList2", 'activePlayerIds', $activePlayerIds);

        foreach ($questStatePlayerList as $item) {
            if (!isset($activePlayerIds[$item['playerId']])) {
                continue;
            }

            if (!isset($filteredData[$item['playerId']])) {
                $filteredData[$item['playerId']] = $item;
            }
        }

        $this->utils->info_log("end _mappingQuestStatePlayerList2", 'total', count($questStatePlayerList), 'filtered', count($filteredData), 'time', microtime(true) - $startTime);

        if ($dryRun) {
            return [];
        }

        return $filteredData;
    }

    /**
     * notificationMM
     */
    public function notificationMMWhenSyncQuestFailed($message, $questManagerId, $notifyUser = null)
    {
        $channel = 'PSH004'; /// PSH004, PHP Personal Notification 004
        $title = "Command: Run Generate Player Quest State Failed ($notifyUser)";
        $message =
            '```'. PHP_EOL .
            "Message: " . $message . PHP_EOL.
            "ManagerId: " . $questManagerId. PHP_EOL.
            '```'. PHP_EOL;
        ;
        $this->utils->debug_log('=====notificationMMWhenSyncQuestFailed', $message);
        $this->sendNotificationToMattermost($title, $channel, $message, 'danger');
    }

    public function notificationMMWhenResultFailed($title, $resultData)
    {
        $channel = 'PSH004'; /// PSH004, PHP Personal Notification 004
        $message =
            '```'. PHP_EOL .
            "Result: " . json_encode($resultData) . PHP_EOL.
            '```'. PHP_EOL;
        ;
        $this->utils->debug_log('=====notificationMMWhenResultFailed', $message);
        $this->sendNotificationToMattermost($title, $channel, $message, 'danger');
    }

    public function notificationMMWhenUpdatePlayerQuestRewardStatusJob($token, $ret, $categoryId, $managerId, $notifyUser = null, $playerId = _COMMAND_LINE_NULL)
    {
        $channel = 'PSH004'; /// PSH004, PHP Personal Notification 004
        $title = 'Command: Update Player Quest Reward Status Job Failed.';
        $message =
            '```'. PHP_EOL .
            $title . PHP_EOL.
            "Result: " . json_encode($ret) . PHP_EOL.
            "Category Id: $categoryId" . PHP_EOL.
            "Manager Id: $managerId" . PHP_EOL.
            "Player Id: $playerId" . PHP_EOL.
            "Token: $token" . PHP_EOL.
            '```'. PHP_EOL;
        ;
        $this->utils->debug_log('=====notificationMMWhenUpdatePlayerQuestRewardStatusJob', $message);
        $this->sendNotificationToMattermost("Update Player Quest Reward Status And Manager Status Job ($notifyUser)", $channel, $message, 'warning');
    }

    public function commandSetPlayerQuestState($questManagerId, $fromState = 2, $toState = 1, $playerId = _COMMAND_LINE_NULL, $questJobId = _COMMAND_LINE_NULL)
    {
        $this->utils->info_log("start setPlayerQuestState-$questManagerId", 'fromState', $fromState, 'toState', $toState, 'playerId', $playerId, 'questJobId', $questJobId);
        $this->load->model(['quest_manager']);
        $result = false;
        $startTime = microtime(true);
        try {
            $this->startTrans();

            $bpquOptions = $this->config->item('batch_player_quest_update');
            $this->utils->debug_log('bpquOptions', $bpquOptions);
            $notifyUser = isset($bpquOptions['notifyUser']) ? $bpquOptions['notifyUser'] : $this->db->database;

            $affected = $this->quest_manager->setPlayerQuestState($questManagerId, $fromState, $toState, $playerId, $questJobId);
            $this->utils->info_log("setPlayerQuestState-startTrans-$questManagerId", 'result', $result);

            $result = ($affected > 0) && $this->endTransWithSucc();

            $this->utils->info_log("setPlayerQuestState-endTrans-$questManagerId", 'result', $result);

            if (!$result) {
                throw new Exception("setPlayerQuestState failed");
            }

            if ($playerId != _COMMAND_LINE_NULL){
                $this->quest_library->deletePlayerQuestProgressCache($questManagerId, $playerId);
            }

            $this->utils->info_log("end setPlayerQuestState-$questManagerId", 'result', $result);
        }catch (Exception $e) {
            $this->utils->error_log('setPlayerQuestState fail', $e->getMessage());

            $title = "Edit quest manager and update player State ($notifyUser)";
            $resultData = [
                'success' => $result,
                'message' => $e->getMessage(),
                'affected' => $affected,
                'fromState' => $fromState,
                'toState' => $toState,
                'playerId' => $playerId,
                'questJobId' => $questJobId,
                'time' => microtime(true) - $startTime,
            ];
            $this->notificationMMWhenResultFailed($title, $resultData);
            $this->rollbackTrans();
        }
    }

    public function update_player_quest_reward_status_job($token)
    {
        $this->utils->info_log("start update_player_quest_reward_status_job-$token");
        $this->load->model(['quest_manager', 'queue_result']);
        $queue_result_model = $this->queue_result;
        $data = $this->initJobData($token);
        $this->utils->debug_log("data-$token", $data);

        $params = [];
		if (isset($data['params']) && !empty($data['params'])) {
			$params = $data['params'];
		}

        $categoryId = isset($params['categoryId']) ? $params['categoryId'] : _COMMAND_LINE_NULL;
        $managerId = isset($params['managerId']) ? $params['managerId'] : _COMMAND_LINE_NULL;
        $playerId = isset($params['playerId']) ? $params['playerId'] : _COMMAND_LINE_NULL;

        $bpquOptions = $this->config->item('batch_player_quest_update');
        $this->utils->debug_log('bpquOptions', $bpquOptions);
        $notifyUser = isset($bpquOptions['notifyUser']) ? $bpquOptions['notifyUser'] : $this->db->database;

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
            $dateTimeStr = $this->utils->getNowForMysql();
			$result = array('categoryId'=>$categoryId, 'managerId'=>$managerId, 'message'=>'Processing...', 'dateTimeStr'=>$dateTimeStr);
			$done=false;
			$queue_result_model->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
		}

        $ret = $this->oneworkSyncQuestManager($managerId, $playerId, true);

        $success = $ret['success'];
        $message = 'completed';

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
			$result = array('success'=>$success, 'message'=>$message, 'dataResult'=>$ret);
			$done=true;
			if ($success) {
				$queue_result_model->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
			} else {
                // if($playerId == _COMMAND_LINE_NULL) { is SBE active/deactive manager，if player is not null is update player quest reward status when player is apply quest
                $this->notificationMMWhenUpdatePlayerQuestRewardStatusJob($token, $ret, $categoryId, $managerId, $notifyUser, $playerId);

                if($playerId == _COMMAND_LINE_NULL) {
                    $this->updateManagerStatus($managerId);
                    $this->quest_library->deleteQuestManagerCache($managerId, strtoupper($this->utils->getActiveTargetDB()));
                    $queue_result_model->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
                }
			}
		}

        $this->utils->info_log('end update_player_quest_reward_status_job ', $result);

        return true;
    }

    public function updateManagerStatus($managerId)
    {
        $this->utils->info_log("start update_manager_status-$managerId");
        $questData = [
			'questManagerId' => $managerId,
			'status' 		  => 0,//0:inactive, 1:active, 2:pending
			'updatedBy'       => Users::SUPER_ADMIN_ID,
		];

        $this->startTrans();
        $this->utils->debug_log('=========questData', $questData);
		$result = $this->quest_manager->editQuestManager($questData, $managerId);
        $successManager = $this->endTransWithSucc() && $result;
        $this->utils->debug_log(__METHOD__, 'successManager', $successManager, $result);

        return $successManager;
    }

    /**
	 * overview : SyncPlayerInvitations
	 */
	public function syncPlayerInvitations() {
        $this->load->model(['player_friend_referral', 'quest_manager']);
		$this->load->library(['quest_library']);

        $this->utils->info_log("start syncPlayerInvitations");

        $resultData = [
            'success' => false,
            'result' => [],
            'totalProcessTime' => 0,
        ];

		try{
			$this->startTrans();
			$startTime = microtime(true);

            $executedQuestCategoryIds = [];
			$syncResult = [];

            if(!$this->utils->getConfig('enable_player_invite_calculation')){
                throw new Exception("enable_player_invite_calculation is false");
            }

            $now = $this->utils->getNowForMysql();
			$conditions = ['questManagerType' => 3];//friend referral
			$questManagers = $this->quest_manager->getAllQuestManager(_COMMAND_LINE_NULL, _COMMAND_LINE_NULL, $conditions);

			if(empty($questManagers)){
				throw new Exception("syncPlayerInvitations questManagers is empty");
			}

			foreach ($questManagers as $questManager) {
				$questManagerId = $questManager['questManagerId'];
				$categoryId = $questManager['questCategoryId'];

                if(in_array($categoryId, $executedQuestCategoryIds)){
                    $this->utils->debug_log('syncPlayerInvitations categoryId is already executed', $categoryId);
                    continue;
                }

                if (!$this->quest_library->verifyQuestCountdownExpired($questManagerId)['passed']) {
                    $this->utils->debug_log('syncPlayerInvitations verifyQuestCountdownExpired questManagerId', $questManagerId);
                    continue;
                }

                $executedQuestCategoryIds[] = $categoryId;
				$categoryDetails = $this->quest_manager->getQuestCategoryDetails($categoryId);
				list($fromDatetime, $toDatetime) = $this->quest_library->getQuestPeriodTypeDatetime($categoryDetails);
                $countPlayerInvitations = $this->player_friend_referral->countPlayerInvitations($fromDatetime, $toDatetime);

                $this->utils->debug_log('syncPlayerInvitations start end time', $fromDatetime, $toDatetime);

                if(empty($countPlayerInvitations)){
                    $this->utils->debug_log("syncPlayerInvitations-$questManagerId", 'countPlayerInvitations is empty', $countPlayerInvitations, 'categoryDetails', $categoryDetails);
                    continue;
                }

                $successCount = 0;
                $failedCount = 0;
                $blockedCount = 0;

                foreach ($countPlayerInvitations as $invite) {
                    $playerId = $invite->playerId;
                    $totalValidInvites = $invite->totalValidInvites;

                    if ($this->player_model->isBlocked($playerId)){
                        $this->utils->debug_log('syncPlayerInvitations player is blocked', $playerId);
                        $blockedCount++;
                        continue;
                    }

                    $playerInvitations = $this->player_friend_referral->getPlayerInvitations($playerId, $categoryId);

                    if (!empty($playerInvitations)) {
                        if($playerInvitations->totalValidInvites != $totalValidInvites){
                            $data = [
                                'totalValidInvites' => $totalValidInvites,
                                'questStartAt' => $fromDatetime,
                                'questEndAt' => $toDatetime,
                                'updatedAt' => $now,
                                'lastSyncAt' => $now
                            ];
                        }else{
                            $data = [
                                'lastSyncAt' => $now
                            ];
                        }
                        $success = $this->player_friend_referral->updatePlayerInvitations($playerId, $categoryId, $data);
                    } else {
                        $data = [
                            'playerId' => $playerId,
                            'questCategoryId' => $categoryId,
                            'totalValidInvites' => $totalValidInvites,
                            'questStartAt' => $fromDatetime,
                            'questEndAt' => $toDatetime,
                            'lastSyncAt' => $now
                        ];
                        $success = $this->player_friend_referral->insertPlayerInvitations($data);
                    }

                    if($success){
                        $successCount++;
                    }else{
                        $failedCount++;
                    }
                }

                $syncResult[] = [
                    'questManagerId' => $questManagerId,
                    'processCount' => $successCount + $failedCount + $blockedCount,
                    'successCount' => $successCount,
                    'failedCount' => $failedCount,
                    'blockedCount' => $blockedCount,
                ];

                $this->utils->info_log("syncPlayerInvitations-$questManagerId", 'count player invitations', count($countPlayerInvitations), 'success count', $successCount, 'failed count', $failedCount, 'blocked count', $blockedCount);
			}

			$result = $this->endTransWithSucc();

			if(!$result){
				throw new Exception("result false");
			}

            $resultData = [
                'success' => true,
                'result' => $syncResult,
            ];

		}catch(Exception $e){
			$this->utils->debug_log('syncPlayerInvitations', $e->getMessage());
            $resultData['error'] = $e->getMessage();
			$this->rollbackTrans();
		}finally{
            $endTime = microtime(true);
            $resultData['totalProcessTime'] = $endTime - $startTime;
            $resultData['executedQuestCategoryIds'] = $executedQuestCategoryIds;

            $this->utils->info_log('syncPlayerInvitations', 'resultData', $resultData);
            $notifyUser = $this->db->database;

            $this->notificationMMWhenResultFailed("Sync Player Invitations. ($notifyUser)", $resultData);

            return $resultData;
        }
	}

    private function isExecutionAllowed($questManagerType, $allowedExecutionTime, $isManual)
    {
        if ($isManual) {
            return true;
        }

        if (!isset($allowedExecutionTime[$questManagerType])) {
            return true;
        }

        $allowedTime = $allowedExecutionTime[$questManagerType];
        $currentTime =  new DateTime($this->utils->getNowForMysql());
        $startTimeWindow = new DateTime($allowedTime['start']);
        $endTimeWindow = new DateTime($allowedTime['end']);

        $this->utils->info_log(__METHOD__, 'currentTime', $currentTime, 'startTimeWindow', $startTimeWindow, 'endTimeWindow', $endTimeWindow);
        return $currentTime >= $startTimeWindow && $currentTime <= $endTimeWindow;
    }

    public function oneTimeCheckAndUpdateDisplayPanel()
    {
        $this->utils->info_log("start oneTimeCheckAndUpdateDisplayPanel");
        $this->load->model(['quest_manager']);

        $result = $this->quest_manager->checkAndUpdateDisplayPanel();
        $this->utils->info_log("end oneTimeCheckAndUpdateDisplayPanel", 'result', $result);
    }
}
