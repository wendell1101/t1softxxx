<?php

trait ag_seamless_game_syncing_utils
{

    public function getGameRecordPath()
    {
        return $this->getSystemInfo('ag_game_records_path');
    }

    public function syncOriginalGameLogs($token)
    {

        $gameLogDirectoryAG = $this->getGameRecordPath();

        if(!is_array($gameLogDirectoryAG)){
          $gameLogDirectoryAG = (array)$gameLogDirectoryAG;
        }

        $playerName = $this->getValueFromSyncInfo($token, 'playerName');
        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');

        $dateTimeFrom=new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo=new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('[syncOriginalGameLogs] after adjust ag dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

        foreach ($gameLogDirectoryAG as $logDirectoryAG) {

            $startDate = new DateTime($dateTimeFrom->format('Y-m-d H:i:s'));
            $endDate = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));
            $day_diff = $endDate->diff($startDate)->format("%a");

            if ($day_diff > 0) {
                for ($i = 0; $i < $day_diff; $i++) {
                    $this->utils->debug_log('########  AG GAME DATES INPUT #################', $startDate , $endDate);
                    if ($i == 0) {
                        $directory = $logDirectoryAG . $startDate->format('Ymd');
                        $this->retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId);
                    }
                    $startDate->modify('+1 day');
                    $directory = $logDirectoryAG . $startDate->format('Ymd');

                    $this->retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId);

                }
            } else {
                $directory = $logDirectoryAG . $startDate->format('Ymd');
                $this->retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId);

                $startDate->modify('+1 day');
                $directory = $logDirectoryAG . $startDate->format('Ymd');

                $this->retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId);
            }
        }

        return array('success' => true);
    }

    public function extractResultXMLRecord($xml){
        $this->CI->load->model(array('agin_seamless_game_logs_result'));
        $source = $xml;

        $xmlData = '<rows>'.file_get_contents($source, true).'</rows>';
        $reportData = simplexml_load_string($xmlData);
        if(!empty($reportData)){
            foreach ($reportData as $key => $value) {
                $result = array();
                $result['data_type']    = isset($value['dataType']) ? (string)$value['dataType'] : NULL;
                $result['game_code']    = isset($value['gmcode']) ? (string)$value['gmcode'] : NULL;
                $result['table_code']   = isset($value['tablecode']) ? (string)$value['tablecode'] : NULL;
                $result['begin_time']   = isset($value['begintime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['begintime']))) : NULL;
                $result['close_time']   = isset($value['closetime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['closetime']))) : NULL;
                $result['dealer']       = isset($value['dealer']) ? (string)$value['dealer'] : NULL;
                $result['shoe_code']    = isset($value['shoecode']) ? (int)$value['shoecode'] : NULL;
                $result['flag']         = isset($value['flag']) ? (int)$value['flag'] : NULL;
                $result['banker_point'] = isset($value['bankerPoint']) ? (int)$value['bankerPoint'] : NULL;
                $result['player_point'] = isset($value['playerPoint']) ? (int)$value['playerPoint'] : NULL;
                $result['card_num']     = isset($value['cardnum']) ? (int)$value['cardnum'] : NULL;
                $result['pair']         = isset($value['pair']) ? (int)$value['pair'] : NULL;
                $result['game_type']    = isset($value['gametype']) ? (string)$value['gametype'] : NULL;
                $result['dragon_point'] = isset($value['dragonpoint']) ? (int)$value['dragonpoint'] : NULL;
                $result['tiger_point']  = isset($value['tigerpoint']) ? (int)$value['tigerpoint'] : NULL;
                $result['card_list']    = isset($value['cardlist']) ? (string)$value['cardlist'] : NULL;
                $result['vid']          = isset($value['vid']) ? (string)$value['vid'] : NULL;
                $result['platform_type'] = isset($value['platformtype']) ? (string)$value['platformtype'] : NULL;
                $isExists = $this->CI->agin_seamless_game_logs_result->isRowIdAlreadyExists($result['game_code']);
                if ($isExists) {
                    $result['updated_at'] = date('Y-m-d H:i:s');
                    $this->CI->agin_seamless_game_logs_result->updateGameResultLogs($result);
                } else {
                    $result['created_at'] = date('Y-m-d H:i:s');
                    $this->CI->agin_seamless_game_logs_result->insertGameResultLogs($result);
                }
            }
        }
        return array("success" => true);
    }

    public function retrieveXMLFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId)
    {
         $this->CI->utils->debug_log('XML CURRENT DIRECTORY------',$directory);

        if (is_dir($directory)) {
            $agGameLogsXml = array_diff(scandir($directory), array('..', '.'));

            //from filename , to filename
            $fromFile = $dateTimeFrom->format('YmdH');
            $toFile = $dateTimeTo->format('YmdH');

            //from filename , to filename
            $fromFileYmd = $dateTimeFrom->format('Ymd');
            $toFileYmd = $dateTimeTo->format('Ymd');

            # GET DIRECTORY NAME
            $directoryArr = explode("/",$directory);
            $dirName = $directoryArr[count($directoryArr)-2]; // directory type name
            # if lostAndfound directory
            if(in_array("lostAndfound",$directoryArr)){
                $dirName = $directoryArr[count($directoryArr)-3];
            }

            foreach ($agGameLogsXml as $xml) {
                //should ignore by time
                $xmlname = substr($xml, 0, 10); //YmdH
                $xmlYmd = substr($xml, 0, 8); //Ymd

                if (($xmlname >= $fromFile && $xmlname <= $toFile)||($xmlYmd!=$fromFileYmd && $xmlYmd!=$toFileYmd)) {
                    $filepath = $directory.'/'.$xml;
                    if(file_exists($filepath)){
                        $responseResultId = $this->saveResponseResultForFile(true,'syncGameRecords', $this->getPlatformCode(), $filepath, array('sync_id' => $syncId));

                        $this->CI->utils->debug_log($this->getPlatformCode().' ag process', $filepath);

                        $this->extractXMLRecord($filepath, $playerName, $responseResultId);
                    }else{
                        $this->CI->utils->debug_log('not found '.$filepath);
                    }
                } else {
                    //ignore
                }
            }
        }
    }

    public function extractXMLRecord($xml, $playerName = null, $responseResultId = null)
    {

        $source = $xml;

        $xmlData = '<rows>'.file_get_contents($source, true).'</rows>';
        $reportData = simplexml_load_string($xmlData);
        $cnt = 0;
        $dataResult = array();
        $uniqueIds = array();
        $ingorePlatformTypes = $this->getIngorePlatformTypes();

        foreach ($reportData as $key => $value) {
            $result = array();

            $dataType=(string)$value['dataType'];
            $transferType=(string)$value['transferType'];
            $platformType=(string) $value['platformType'];
            $rowPlayerName=(string) $value['rowPlayerName'];

            if (in_array($dataType, $this->ignore_type_array)) {
                if($dataType == 'TR' && in_array($transferType, $this->allowed_transfer_type)){
                    //allowed transfer type
                }else{
                    continue; //ignore
                }
            }
            if (!empty($playerName) && $rowPlayerName) {
                continue; //ignore
            }
            if (!empty($ingorePlatformTypes) && in_array($platformType, $ingorePlatformTypes)) {
                continue;
            }

            ++$cnt;

                $result['datatype'] = (string) $value['dataType'];
                $result['playername'] = (string) $value['playerName'];
                $result['agentcode'] = (string) $value['agentCode'];
                $result['billno'] = (string) $value['billNo'];
                $result['uniqueid'] = (string) $value['billNo'];
                $result['bettime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['betTime'])));
                $result['recalcutime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['recalcuTime'])));
                $result['creationtime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['betTime'])));
                $result['betamount'] = (float) $value['betAmount'];
                $result['beforecredit'] = (string) $value['beforeCredit'];
                $result['netamount'] = (float) $value['netAmount'];
                $result['gametype'] = (string) $value['gameType'];
                $result['gamecode'] = (string) $value['gameCode'];
                $result['playtype'] = (string) $value['playType'];
                $transferType=(string)$value['transferType'];

                if($transferType == 'RED_POCKET'){
                    $result['billno'] = (string) $value['ID'];
                    $result['uniqueid'] = (string) $value['ID'];
                    $result['bettime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['creationTime'])));
                    $result['recalcutime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['creationTime'])));
                    $result['creationtime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($value['creationTime'])));
                    $result['transferAmount'] = (float) $value['transferAmount'];
                    $result['previousAmount'] = (float) $value['previousAmount'];
                    $result['currentAmount'] = (float) $value['currentAmount'];
                    $result['beforecredit'] = (float) $value['previousAmount'];
                    $result['netamount'] = (float) $value['transferAmount'];
                    $result['betamount'] =  0;
                    $result['gametype'] = $transferType;
                    $result['gamecode'] = $transferType;
                }

                $result['subbillno'] = (string) $value['subbillno'];
                $result['validbetamount'] = (float) $value['validBetAmount'];
                $result['flag'] = (string) $value['flag'];
                $result['currency'] = (string) $value['currency'];
                $result['tablecode'] = $this->getStringValueFromXml($value, 'tableCode');
                $result['loginip'] = (string) $value['loginIP'];
                $result['platformtype'] = $platformType;
                $result['remark'] = $this->getStringValueFromXml($value, 'remark');
                $result['round'] = (string) $value['round'];
                $result['result'] = (string) $value['result'];
                $result['response_result_id'] = $responseResultId;
                $result['external_uniqueid'] = $result['uniqueid'];
                $result['updated_at'] = $this->CI->utils->getNowForMysql();

                //for AGSHABA
                $remarkJsonString = isset($value['remark']) ? (string) $value['remark'] : null;
                if (!empty($remarkJsonString)) {
                    //overwrite sport type to game type
                    $remark = $this->CI->utils->decodeJson($remarkJsonString);
                    if (isset($remark['after_amount'])) {
                        $result['after_amount'] = (float) $remark['after_amount'];
                    }
                    if (isset($remark['sport_type'])) {
                        $result['gametype'] = (string) $remark['sport_type'];
                    }
                }

                if ($value['flag'] != '0' ||  ((string)$value['transferType']  && (string)$value['transferType'] == 'RED_POCKET')) {
                    //FILTER DUPLICATE ROWS IN XML
                    if (!in_array($result['uniqueid'], $uniqueIds)) {
                        array_push($dataResult, $result);
                        array_push($uniqueIds, $result['uniqueid']);
                    } else {
                        if ($this->isUpdateOriginalRow()) {
                            //override netamount incase of duplicate unique
                            array_walk($dataResult, function($value,$key) use ($result,&$dataResult){
                                if($value['uniqueid'] == $result['uniqueid']){
                                    $dataResult[$key]['netamount'] = $result['netamount'];
                                }
                            });
                        }
                    }
                }
        }

        $this->CI->utils->debug_log('dataResults', count($dataResult), 'isUpdateOriginalRow', $this->isUpdateOriginalRow());

        if (count($dataResult) > 0) {
            if ($this->isUpdateOriginalRow()) {
                # get data array for data merging
                if ($platformType == 'AGIN') {
                    $this->syncGameLogsToDB($dataResult);
                } else {
                    foreach ($dataResult as $dataRow) {
                       $this->syncGameLogsToDB($dataRow);
                    }
                }
            } else {
                $availableResult = $this->getAvailableRows($dataResult);
                if (count($availableResult) > 0) {
                    $this->CI->utils->debug_log('insert ag game logs', count($availableResult));

                    $ids = $this->insertBatchToGameLogs($availableResult);
                    $this->syncMergeToGameLogsByIds($ids);
                }
            }
        }

        $this->CI->utils->debug_log('count game record', $cnt, 'filepath', $xml);

        return $cnt;
    }

    public function getIngorePlatformTypes()
    {
        return $this->ignore_platformtypes;
    }

    public function getStringValueFromXml($xml, $key)
    {
        $value = (string) $xml[$key];
        if (empty($value) || $value == 'null') {
            $value = '';
        }

        return $value;
    }

    public function isUpdateOriginalRow()
    {
        return $this->is_update_original_row;
    }

    public function syncGameLogsToDB($availableResult){
        $this->CI->load->model('agin_seamless_game_logs_thb');
        $records = array();

        # check if multi array
        if (!isset($availableResult[0]) || !is_array($availableResult[0])) {
            //if only one array
            if(is_array($availableResult)){
                $availableResult=[$availableResult];
            }else{
                $this->CI->utils->debug_log('================== sync game logs error availableResult', $availableResult);
                return;
            }
        }

        # merge combo bets
        if ($this->merge_game_logs) {
            $records = $this->syncRecords($availableResult);
        } else {
            # Create bet details before saving data
            $records = $this->createBetDetailsAndCheckIfComboBets($availableResult);
        }

        # dump data to db
        if (!empty($records)) {
            foreach ($records as $record) {
                $this->CI->agin_seamless_game_logs_thb->syncGameLogs($record);
            }
            return;
        }
    }

    public function syncRecords($gameRecords) {
        $this->CI->load->model('agin_seamless_game_logs_thb');
        $round_ids = array();
        $externalUniqueIds = array();
        $map_external_to_round_id = array();
        $map = array();
        $count = 0;
        $mergeResult = array();

        if (!empty($gameRecords)) {
            foreach ($gameRecords as $row) {
                if (empty($row['gamecode'])) {
                    array_push($mergeResult, $row);
                    continue;
                }

                $externalId=isset($map_external_to_round_id[$row['gamecode'].$row['playername']]) ?
                    $map_external_to_round_id[$row['gamecode'].$row['playername']] : null;

                # check multiple bets or same round id but diffent player on the same round
                if (!in_array($row['gamecode'], $round_ids) ||
                    (in_array($row['gamecode'], $round_ids) &&
                        !isset($map[$externalId])
                    ))
                {
                    array_push($round_ids, $row['gamecode']);
                    array_push($externalUniqueIds, $row['billno']);

                    $data = $row;
                    $data['extra'] = $this->createGameBetDetialsJson($data);

                    if (!isset($map[$row['billno']])) {
                        $map[$row['billno']] = $data;
                        $map_external_to_round_id[$row['gamecode'].$row['playername']]  = $row['billno'];
                    }
                } else {
                    # merge amount, valid bet, win lose, and valid bet then add it extra info if multiple bets
                    $tmp_data = $map[$map_external_to_round_id[$row['gamecode'].$row['playername']]];
                    $extra = array();
                    $extra = json_decode($tmp_data['extra'], true);
                    $newExtra = $this->createGameBetDetialsJson($row, $extra);

                    $map[$map_external_to_round_id[$row['gamecode'].$row['playername']]]['betamount'] += $row['betamount'];
                    $map[$map_external_to_round_id[$row['gamecode'].$row['playername']]]['validbetamount'] += $row['validbetamount'];
                    $map[$map_external_to_round_id[$row['gamecode'].$row['playername']]]['netamount'] += $row['netamount'];
                    $map[$map_external_to_round_id[$row['gamecode'].$row['playername']]]['extra'] = $newExtra;
                }
            }

            $existingGameCode = $this->CI->agin_seamless_game_logs_thb->getExistingGameCode($round_ids);

            if (!empty($map)) {
                foreach ($map as $key => $row) {
                    # checkout if gamecode is exist in DB
                    if (!empty($existingGameCode) && !$this->is_update_original_row) {
                        if (in_array($row['gamecode'], array_column($existingGameCode, 'gamecode'))) {
                            # Get array index of existing game code
                            // $arrKey = array_search($row['gamecode'], array_column($existingGameCode, 'gamecode'));
                            $existingGameCodeArr = array_column($existingGameCode, 'gamecode');
                            $counts = array_count_values($existingGameCodeArr);
                            $gameCode = $row['gamecode'];

                            $filtered = array_filter($existingGameCodeArr, function ($value) use ($counts, $gameCode) {
                                return $counts[$value] = $gameCode;
                            });

                            if (!empty($filtered)) {
                                foreach ($filtered as $key => $value) {
                                    # Check if this round ID belongs to the same player
                                    if ($existingGameCode[$key]['playername'] == $row['playername']) {
                                        continue 2; # continue outer loop
                                    }
                                }
                            }

                        }
                    }

                    array_push($mergeResult, $row);
                    $count++;
                }
            }
        }

        return $mergeResult;
    }

    public function getAvailableRows($dataResult)
    {
        $this->CI->load->model('agin_seamless_game_logs_thb');
        return $this->CI->agin_seamless_game_logs_thb->getAvailableRows($dataResult);
    }

    public function insertBatchToGameLogs($availableResult)
    {
        $this->CI->load->model('agin_seamless_game_logs_thb');
        if ($this->merge_game_logs) {
            $syncRecords = $this->syncRecords($availableResult);
            return $this->CI->agin_seamless_game_logs_thb->insertBatchGameLogsReturnIds($syncRecords);
        } else {
            # add betdetails first before saving the data
            $records = $this->createBetDetailsAndCheckIfComboBets($availableResult);

            return $this->CI->agin_seamless_game_logs_thb->insertBatchGameLogsReturnIds($records);
        }

    }

    public function syncMergeToGameLogsByIds($ids)
    {
        $this->CI->load->model(array('game_logs'));

        $result = $this->getOriginalGameLogsByIds($ids);
        if ($result) {
            $this->mergeResultGameLogs($result);
        }
    } 
}
