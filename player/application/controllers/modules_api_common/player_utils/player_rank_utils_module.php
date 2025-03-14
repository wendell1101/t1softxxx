<?php
/**
 * player_rank_utils_module
 * 
 * $config['enabled_player_score'] = true;
 * $config['custom_player_rank_list'] = [
 *     'newbet' => [
 *         'enable' => true,
 *         'fallback_currency' => 'cny',
 *         'game' => [
 *              T1LOTTERY_SEAMLESS_API => array('crash','double','dice'),
 *              BISTRO_SEAMLESS_API => array(60, 61),
 *              TRUCO_SEAMLESS_API => array('truco')
 *          ],
 *          'ignore_tag_id' => [5, 13, 17, 19, 37],
 *         'bonus_turnover' => 0.07,
 *         'rank_bonus_rate' => [
 *             1 => 50,
 *             2 => 18.75,
 *             3 => 11.25,
 *             4 => 5.63,
 *             5 => 4.38,
 *             6 => 3.75,
 *             7 => 2.5,
 *             8 => 1.88,
 *             9 => 1.25,
 *             10 => 0.61,
 *         ]
 *     ],
 * ];
 */
trait player_rank_utils_module {
    protected function getRankList($rank_key, $rank_setting){
        $this->load->model(['player_score_model']);
		switch($rank_key){
			case 'newbet':
			default:
				return $this->getNewbetRanklist($rank_setting);
				break;
		}
	}

    /**
     * getNewbetRanklist function
     *
     * @param array $newbet_setting from $config['custom_player_rank_list']['newbet']
     * @return array
     */
	private function getNewbetRanklist($newbet_setting){
        $request_body = $this->playerapi_lib->getRequestPramas();
        $turn_over = empty($newbet_setting['bonus_turnover']) ? 0 : $newbet_setting['bonus_turnover'];
        $newbet_bonus_trie =  empty($newbet_setting['rank_bonus_rate']) ? [] : $newbet_setting['rank_bonus_rate'];
        $sync_date = empty($request_body['syncDate']) ? '' : $request_body['syncDate'];
        $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();
        try {

            //for sync_original_game_logs_anytime
            if (is_object($syncDate) && $syncDate instanceof DateTime) {
                $syncDate = $syncDate->format('Y-m-d');
            }
            //for syncPlayerRankWithScore
            if (is_string($syncDate)) {
                $syncDate = $this->utils->formatDateForMysql(new DateTime($syncDate));
            }

            $total_score = $this->player_score_model->getPlayerTotalScore(false, $syncDate, 'newbet', null, player_score_model::ID_FOR_TOTAL_SCORE);
            $newbet_bonus = (!empty($total_score[0]) && isset($total_score[0]['game_score'])) ? (float)$total_score[0]['game_score'] : 0;
            $newbet_bonus_turnover = $newbet_bonus * ($turn_over / 100);
            $ret['jackpotTotalbonus'] = round($newbet_bonus_turnover, 2);
            
            $rankList = $this->_getPlayerNewbetRanklist($syncDate);
            foreach ($rankList as $index => $item) {
                $rankList[$index]['username'] = substr_replace($item['username'], '*****', 3);
                $rankList[$index]['score'] = round($item['score'], 2, PHP_ROUND_HALF_DOWN);
                $rankList[$index]['rankBonus'] = round($newbet_bonus_turnover * ($newbet_bonus_trie[$item['rank']]/100),2);
                $rankList[$index]['rankRate'] = $newbet_bonus_trie[$item['rank']];
                $rankList[$index]['rankKey'] = $item['rank_key'];
                unset($rankList[$index]['player_id']);
                unset($rankList[$index]['playerpromoId']);
                unset($rankList[$index]['rank_key']);
            }
            $ret['list'] = $rankList;
            $ret['syncDate'] = $this->utils->formatDatetimeForDisplay(new DateTime($syncDate), 'd/m/Y');

        } catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
        }
        return $ret;
	}
        //getRankRecords
    protected function getRankRecords($rank_key, $rank_setting){
        $this->load->model(['player_score_model']);
        switch($rank_key){
            case 'newbet':
            default:
                return $this->getNewbetRankRecords($rank_setting);
                break;
        }
    }

    private function getNewbetRankRecords($newbet_setting){
        $request_body = $this->playerapi_lib->getRequestPramas();
        $sync_date = empty($request_body['syncDate']) ? '' : $request_body['syncDate'];
        $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();
        try {

            //for sync_original_game_logs_anytime
            if (is_object($syncDate) && $syncDate instanceof DateTime) {
                $syncDate = $syncDate->format('Y-m-d');
            }
            //for syncPlayerRankWithScore
            if (is_string($syncDate)) {
                $syncDate = $this->utils->formatDateForMysql(new DateTime($syncDate));
            }

            $cache_key = "player-rankinfo-newbet-". $syncDate;
            $cached_result = $this->utils->getJsonFromCache($cache_key);
            if(!empty($cached_result)){
                $this->comapi_log(__METHOD__, ['cached_result' => $cached_result]);
                $ret = $cached_result;
                return $ret;
            }

            $ret['syncDate'] = $this->utils->formatDatetimeForDisplay(new DateTime($syncDate), 'd/m/Y');
            $rankList = $this->_getPlayerNewbetRanklist($syncDate);
            foreach ($rankList as $index => $item) {
                $rankList[$index]['username'] = substr_replace($item['username'], '*****', 3);
                $rankList[$index]['score'] = round($item['score'], 2, PHP_ROUND_HALF_DOWN);
                unset($rankList[$index]['player_id']);
                unset($rankList[$index]['playerpromoId']);
            }
            $ret['list'] = $rankList;

        } catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
        }
        $ttl = 2 * 60;
        $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
        return $ret;
    }

    protected function getPlayerRanking($player_id, $rank_key, $rank_setting){
        $this->load->model(['player_score_model']);
		switch($rank_key){
			case 'newbet':
			default:
				return $this->getNewbetRankingPlayer($player_id, $rank_setting);
				break;
		}
    }
    private function getNewbetRankingPlayer($player_id, $newbet_setting){
        $request_body = $this->playerapi_lib->getRequestPramas();
        $sync_date = empty($request_body['syncDate']) ? '' : $request_body['syncDate'];
        $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();
        try {

            //for sync_original_game_logs_anytime
            if (is_object($syncDate) && $syncDate instanceof DateTime) {
                $syncDate = $syncDate->format('Y-m-d');
            }
            //for syncPlayerRankWithScore
            if (is_string($syncDate)) {
                $syncDate = $this->utils->formatDateForMysql(new DateTime($syncDate));
            }

            $cache_key = "player-rankinfo-newbet-". $syncDate. "-". $player_id;
            $cached_result = $this->utils->getJsonFromCache($cache_key);
            if(!empty($cached_result)){
                $this->comapi_log(__METHOD__, ['cached_result' => $cached_result]);
                $ret = $cached_result;
                return $ret;
            }

            $ret['syncDate'] = $this->utils->formatDatetimeForDisplay(new DateTime($syncDate), 'd/m/Y');
            $lastRankCurrentPlayer = [
                'rank' => 0,
                'score' => 0
            ];
            $rankList = $this->_getPlayerNewbetRanklist($syncDate);
            foreach ($rankList as $index => $item) {
                if(empty($lastRankCurrentPlayer) || ($lastRankCurrentPlayer['rank'] < $item['rank'])){
                    $lastRankCurrentPlayer = $item;
                }
            }
            $player = $this->player_model->getPlayerArrayById($player_id);
            if (!empty($player)) {
                $username = $player['username'];
                $_rankCurrentPlayer = $this->player_score_model->getPlayerNewbetRanklist($player_id, 1, 0, $syncDate);
                $rankCurrentPlayer = !empty($_rankCurrentPlayer[0]) ? $_rankCurrentPlayer[0] : [
                    "rank" => 0,
                    "username" => $username,
                    "score" => 0
                ];
                // $rankCurrentPlayer['username'] = substr_replace($username, '*****', 3);
                $rankCurrentPlayer['username'] = $username;
                $playerNewbetDetail = $this->player_score_model->getPlayerRealBetByDateForNewbet($syncDate, $player_id);
                $playerNewbetDetail =  !empty($playerNewbetDetail[0])? (array)$playerNewbetDetail[0] : [
                    'betamount' => 0,
                    'bet_date' => $syncDate,
                ];
                $rankCurrentPlayer['realtimeBet'] = !empty($playerNewbetDetail['betamount']) ? round($playerNewbetDetail['betamount'],2) : round($rankCurrentPlayer['score'],2);
                $currentRank = $rankCurrentPlayer['rank'];
                $wagScore = (empty($currentRank) || $currentRank>$lastRankCurrentPlayer['rank']) ? $lastRankCurrentPlayer['score'] - $rankCurrentPlayer['realtimeBet'] : 0;
                $ret['wagScore'] = round($wagScore,2, PHP_ROUND_HALF_DOWN);

                $ret['username'] = $username;
                $ret['ranking'] = $currentRank;
                $ret['score'] = round($rankCurrentPlayer['score'],2, PHP_ROUND_HALF_DOWN);
                $ret['realtimeBet'] = $rankCurrentPlayer['realtimeBet'];
            } else {
				throw new APIException(null, Playerapi::CODE_UNAUTHORIZED);
            }

        } catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
        }
        $ttl = 2 * 60;
        $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
        return $ret;
    }

    private function _getPlayerNewbetRanklist($syncDate){
        $cache_key = "rank-list-newbet-". $syncDate;
        $cached_result = $this->utils->getJsonFromCache($cache_key);
        if (!empty($cached_result)) {
            $this->comapi_log(__METHOD__, ['cached_result' => $cached_result]);
            $rankList = $cached_result;
        } else {
            $rankList = $this->player_score_model->getPlayerNewbetRanklist(false, null, 0, $syncDate);
            $ttl = 20 * 60;
            $this->utils->saveJsonToCache($cache_key, $rankList, $ttl);
        }
        return $rankList;
    }
}

