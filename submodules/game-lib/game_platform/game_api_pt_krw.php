<?php

require_once dirname(__FILE__).'/game_api_common_pt.php';

class Game_api_pt_krw extends Game_api_common_pt
{
    public function getPlatformCode(){
        return PT_KRW_API;
    }

    public function __construct(){
        parent::__construct();
    }

    public function getPTGameLogStatistics($dateTimeFrom, $dateTimeTo, $playerName) {
        $this->CI->load->model('pt_krw_game_logs');
        return $this->CI->pt_krw_game_logs->getPTGameLogStatistics($dateTimeFrom, $dateTimeTo, $playerName);
    }
    
    public function isUniqueIdAlreadyExists($uniqueId) {
        $this->CI->load->model('pt_krw_game_logs');
        return $this->CI->pt_krw_game_logs->isUniqueIdAlreadyExists($uniqueId);
    }

    public function processResultForSyncGameRecords($params) {
        $responseResultId = $params['responseResultId'];
        $resultJson = $this->convertResultJsonFromParams($params);
        // $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = $this->processResultBoolean($responseResultId, $resultJson);
        $result = array();
        if ($success) {
            $this->CI->load->model('pt_krw_game_logs');
            // $ptGameRecords = array();
            $gameRecords = $resultJson['result'];
            $sum = 0;
            if ($gameRecords) {
                $availableRows = $this->CI->pt_krw_game_logs->getAvailableRows($gameRecords);
                $this->CI->utils->debug_log('PT KRW resultJson ===> ', $resultJson,' Available Rows',$availableRows );
                foreach ($availableRows as $key) {

                    preg_match_all("/\(([^()]+)\)/", $key['GAMENAME'], $matches);
                    $gameshortcode = $matches[1];
                    $uniqueId = $key['GAMECODE'];
                    $sum += $key['BET'];
                    $external_uniqueid = $gameshortcode[0] . '-' . $key['SESSIONID'] . '-' . $key['GAMEDATE'] . '-' . $key['WINDOWCODE'] . '-' . $key['RNUM'];
                    // if (!$this->isUniqueIdAlreadyExists($uniqueId) &&
                    if (!$this->isInvalidRow($key)) {
                        // $ptGameRecords[] =
                        $row = array('playername' => $key['PLAYERNAME'],
                            'gamename' => $key['GAMENAME'],
                            'gameshortcode' => $gameshortcode[0],
                            'gamecode' => $key['GAMECODE'],
                            'bet' => $key['BET'],
                            'win' => $key['WIN'],
                            'gamedate' => $key['GAMEDATE'],
                            'sessionid' => $key['SESSIONID'],
                            'gametype' => $key['GAMETYPE'],
                            'windowcode' => $key['WINDOWCODE'],
                            'balance' => $key['BALANCE'],
                            'progressivebet' => $key['PROGRESSIVEBET'],
                            'progressivewin' => $key['PROGRESSIVEWIN'],
                            'currentbet' => $key['CURRENTBET'],
                            'livenetwork' => $key['LIVENETWORK'],
                            'gameid' => $key['GAMEID'],
                            'uniqueid' => $uniqueId,
                            'response_result_id' => $responseResultId,
                            'external_uniqueid' => $external_uniqueid);
                        $this->CI->pt_krw_game_logs->insertPTGameLogs($row);
                    }
                }
            }

            // foreach ($ptGameRecords as $key) {
            //  $this->CI->pt_krw_game_logs->syncToPTGameLogs($key);
            // }
            // $ptGameRecords = array();
            //
            $result['totalPages'] = $resultJson['pagination']['totalPages'];
            $result['currentPage'] = $resultJson['pagination']['currentPage'];
            $result['itemsPerPage'] = $resultJson['pagination']['itemsPerPage'];
            $result['totalCount'] = @$resultJson['pagination']['totalCount'];
            $result['sum'] = $sum;

        }

        // unset($resultJson);

        return array($success, $result);
    }

    public function syncMergeToGameLogs($token) {
        //merge ag_game_logs to game_logs, map fields
        //check duplicate record

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $playerName = parent::getValueFromSyncInfo($token, 'playerName');

        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        // if (!$dateTimeTo) {
        //  $dateTimeTo = new DateTime();
        // }
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $result = $this->getPTGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'), $playerName);

        $unknownGame = $this->getUnknownGame();
        $cnt = 0;
        if ($result) {
            //var_dump($result);
            $this->CI->load->model(array('game_description_model', 'game_logs'));
            $gameDescIdMap = $this->CI->game_description_model->getGameCodeMap(PT_API); // use original PT gametype and game desc

            foreach ($result as $key) {
                // $sum += $key->bet_amount;
                $username = strtolower($key->playername);
                $gameDate = new \DateTime($key->gamedate);
                $gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

                // $gameLogs['game_platform_id'] = $this->getPlatformCode();

                list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($key, $unknownGame, $gameDescIdMap);
                // $this->CI->utils->debug_log('game_description_id', $game_description_id, 'game_type_id', $game_type_id);

                if (empty($game_description_id)) {
                    $this->CI->utils->debug_log('empty game_description_id , pt_krw_game_logs.id=', $key->id);
                    continue;
                }
                $cnt++;
                $bet_amount=$this->gameAmountToDB($key->bet_amount);
                //gamecode is round number
                $extra_info=['table'=>$key->gamecode, 'trans_amount'=>$bet_amount];

                $this->syncGameLogs($game_type_id, $game_description_id, $key->gameshortcode,
                    $key->gametype, $key->gamename, null, $username,
                    $bet_amount, $this->gameAmountToDB($key->result_amount), null, null, $this->gameAmountToDB($this->processAfterBalance($key->after_balance)), $key->has_both_side,
                    $key->external_uniqueid, $gameDateStr, $gameDateStr, $key->response_result_id,
                    Game_logs::FLAG_GAME, $extra_info);

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

        return array('success' => true);
    }
}

/*end of file*/
