<?php

/**
 * Api_common core module: player reports
 * Separated 8/18/2021
 * @see		api_common.php
 */
trait comapi_core_player_score
{
    public function getPlayerRankList($playerId = false)
    {
        $this->load->model(['player_score_model', 'comapi_reports']);

        // $api_key     = $this->input->post('api_key', true);
        $token       = $this->input->post('token', true);
        $username    = $this->input->post('username', true);

        // if (!$this->__checkKey($api_key)) { return; }
        $player = $this->player_model->getPlayerByUsername($username);


        if (true) {

            // $ret = ($return_type == 0) ? $result['rows'] : $result;
            $ret['rankList'] = $this->player_score_model->getPlayersRanklist();
            $ret['rankCurrentPlayer'] = false;
            if (!empty($token) && !empty($player) && $this->__isLoggedIn($player->playerId, $token)) {

                $ret['rankCurrentPlayer'] = $this->player_score_model->getPlayersRanklist($player->playerId);
            }

            $this->__returnApiResponse(true, parent::CODE_SUCCESS, lang('Success'), $ret);

            return;
        } else {
            // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
            $this->__returnApiResponse(false, parent::CODE_COMMON_INVALID_TOKEN, lang('Invalid token or user not logged in'));
            return;
        }
    }
    public function getPlayerNewbetRanklist($playerId = false)
    {
        $api_key = $this->input->post('api_key');
        $api_key = strtolower($api_key);
        if (!$this->isValidApiKey($api_key)) {
            $this->__returnApiResponse(false, self::CODE_COMMON_INVALID_TOKEN, lang('Invalid token'));
            return;
        }

        if(!$this->utils->getConfig('enabled_player_score')){
            $this->__returnApiResponse(false, self::CODE_SUCCESS, lang('Not Enabled'));
            return;
        }
        $this->load->model(['player_score_model', 'comapi_reports']);
        $newbet_setting = $this->player_score_model->checkCustomRank('newbet');
        if (!$newbet_setting) {
            $this->__returnApiResponse(false, self::CODE_SUCCESS, lang('Not Enabled'));
            return;
        }

        // $api_key     = $this->input->post('api_key', true);
        $token       = $this->input->post('token', true);
        $username    = $this->input->post('username', true);
        $limit       = $this->input->post('limit', true) ?: null;
        $offset       = $this->input->post('offset', true) ?: null;
        $sync_date       = $this->input->post('syncDate', true);

        // $turn_over = 0.07; //bonus_turnover
        // $newbet_bonus_trie = [
        //     1 => 50,
        //     2 => 18.75,
        //     3 => 11.25,
        //     4 => 5.63,
        //     5 => 4.38,
        //     6 => 3.75,
        //     7 => 2.5,
        //     8 => 1.88,
        //     9 => 1.25,
        //     10 => 0.61,
        // ]; //rank_bonus_rate

        $turn_over = empty($newbet_setting['bonus_turnover']) ? 0 : $newbet_setting['bonus_turnover'];
        $newbet_bonus_trie =  empty($newbet_setting['rank_bonus_rate']) ? [] : $newbet_setting['rank_bonus_rate'];

        // if (!$this->__checkKey($api_key)) { return; }
        $player = $this->player_model->getPlayerByUsername($username);


        if (true) {
            $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();

            //for sync_original_game_logs_anytime
            if (is_object($syncDate) && $syncDate instanceof DateTime) {
                $syncDate = $syncDate->format('Y-m-d');
            }

            //for syncPlayerRankWithScore
            if (is_string($syncDate)) {
                $syncDate = $this->utils->formatDateForMysql(new DateTime($syncDate));
            }

            // $ret = ($return_type == 0) ? $result['rows'] : $result;
            // $ret['total_score'] = 
            $total_score = $this->player_score_model->getPlayerTotalScore(false, $syncDate, 'newbet', null, player_score_model::ID_FOR_TOTAL_SCORE);
            $newbet_bonus = (!empty($total_score[0]) && isset($total_score[0]['game_score'])) ? (float)$total_score[0]['game_score'] : 0;
            $newbet_bonus_turnover = $newbet_bonus * ($turn_over / 100);
            $ret['newbet_bonus'] = round($newbet_bonus_turnover, 2);
            
            // $ret['playerNewbetDetail'] = [];
            // $ret['lastRankCurrentPlayer'] = [];
            $lastRankCurrentPlayer = [
                'rank' => 0,
                'score' => 0
            ];
            
            $rankList = $this->player_score_model->getPlayerNewbetRanklist(false, $limit, $offset, $syncDate);
            foreach ($rankList as $index => $item) {
                unset($rankList[$index]['player_id']);
                unset($rankList[$index]['playerpromoId']);
                
                // $rankList[$index]['username'] = $this->utils->maskMiddleString($item['username'],3);
                $rankList[$index]['username'] = substr_replace($item['username'], '*****', 3);
                $rankList[$index]['score'] = round($item['score'], 2, PHP_ROUND_HALF_DOWN);
                $rankList[$index]['rank_bonus'] = round($newbet_bonus_turnover * ($newbet_bonus_trie[$item['rank']]/100),2);
                $rankList[$index]['rank_bonus_rate'] = $newbet_bonus_trie[$item['rank']];

                if(empty($lastRankCurrentPlayer) || ($lastRankCurrentPlayer['rank'] < $item['rank'])){

                    $lastRankCurrentPlayer = $item;
                }
            }
            $ret['rankList'] = $rankList;
            // $ret['lastRankCurrentPlayer'] = $lastRankCurrentPlayer;
            $ret['rankCurrentPlayer'] = [];
            if (!empty($token) && !empty($player) && $this->__isLoggedIn($player->playerId, $token)) {

                // $lastRankCurrentPlayer = $this->player_score_model->getPlayerNewbetRanklist(false, 1, 9, $syncDate);
                $_rankCurrentPlayer = $this->player_score_model->getPlayerNewbetRanklist($player->playerId, 1, 0, $syncDate);
                $rankCurrentPlayer = !empty($_rankCurrentPlayer[0]) ? $_rankCurrentPlayer[0] : [
                    "rank" => false,
                    "username" => $username,
                    "score" => 0
                ];
                // $rankCurrentPlayer['username'] = $this->utils->maskMiddleString($username,3);
                $rankCurrentPlayer['username'] = substr_replace($username, '*****', 3);
                unset($rankCurrentPlayer['player_id']);
                unset($rankCurrentPlayer['rank_key']);
                unset($rankCurrentPlayer['playerpromoId']);

                // $ret['lastRankCurrentPlayer'] = !empty($lastRankCurrentPlayer[0]) ? $lastRankCurrentPlayer[0] : [];
                $playerNewbetDetail = $this->player_score_model->getPlayerRealBetByDateForNewbet($syncDate, $player->playerId);
                $playerNewbetDetail =  !empty($playerNewbetDetail[0])? (array)$playerNewbetDetail[0] : [
                    'betamount' => 0,
                    'bet_date' => $syncDate,
                ];
                // unset($ret['playerNewbetDetail']['player_id']);
                $rankCurrentPlayer['realtimeBet'] = !empty($playerNewbetDetail['betamount']) ? round($playerNewbetDetail['betamount'],2) : round($rankCurrentPlayer['score'],2);

                $currentRank = $rankCurrentPlayer['rank'];
                $wagScore = (empty($currentRank) || $currentRank>$lastRankCurrentPlayer['rank']) ? $lastRankCurrentPlayer['score'] - $rankCurrentPlayer['realtimeBet'] : 0;
                $rankCurrentPlayer['wagScore'] = round($wagScore,2, PHP_ROUND_HALF_DOWN);
                $ret['rankCurrentPlayer'] = $rankCurrentPlayer;
            } else {

                $ret['rankCurrentPlayer'] = 'not_login';
            }

            $ret['rankDate'] = $this->utils->formatDatetimeForDisplay(new DateTime($syncDate), 'd/m/Y');
            $this->__returnApiResponse(true, self::CODE_SUCCESS, lang('Success'), $ret);

            return;
        } else {
            // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
            $this->__returnApiResponse(false, self::CODE_COMMON_INVALID_TOKEN, lang('Invalid token or user not logged in'));
            return;
        }
    }
} // End of trait comapi_core_player_reports
