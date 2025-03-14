<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Player_score_model extends BaseModel {
	protected $tableName = 'total_score';
    protected $table_for_score_history = 'score_history';
    protected $table_for_score_rank = 'score_rank';

	const MANUAL_ADD_SCORE = 1;
	const MANUAL_SUBTRACT_SCORE  = 2;

	const SCORE_TYPE_MANUAL  = 1;
	const SCORE_TYPE_AUTO = 2;

    const ID_FOR_TOTAL_SCORE = 0;

	public function __construct() {
		parent::__construct();
	}

	public function sumPlayerGameScore($player_id, $date = null){
		$this->utils->debug_log(__METHOD__, 'get player game_score', $player_id, $date);
		$this->db->select_sum('game_score');
		$this->db->where('player_id', $player_id);
		if (!empty($date)) {
			$this->db->where('generate_date', $date);
		}
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'game_score');
	}

	public function sumPlayerManualScore($player_id, $date = null){
		$this->utils->debug_log(__METHOD__, 'get player manual_score', $player_id, $date);
		$this->db->select_sum('manual_score');
		$this->db->where('player_id', $player_id);
		if (!empty($date)) {
			$this->db->where('generate_date', $date);
		}
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'manual_score');
	}

    public function getScoreHistoryList(){
        $this->db->select('player_id, SUM(score) total_manual_score');
        $this->db->from($this->table_for_score_history);
        $this->db->group_by('player_id');
        return $this->runMultipleRowArray();
    }

	/**
	 * overview : get Player score history Details
	 * @param int $player_id
	 * @return array
	 */
	public function getPlayerScoreHistoryDetails($player_id, $from_date = null, $to_date = null) {
		$this->utils->debug_log(__METHOD__, $player_id, $from_date, $to_date);
		$this->db->select('*');
		$this->db->from($this->table_for_score_history);
		$this->db->where('player_id', $player_id);
		if (!empty($from_date) && !empty($to_date)) {
			$this->db->where('created_at >=', $from_date);
			$this->db->where('created_at <=', $to_date);
		}
		$this->db->order_by('created_at', 'DESC');
		return $this->runOneRowArray();
	}

	public function insertUpdatePlayerManualScore($player_id, $score, $userId, $date = null){
		$this->utils->debug_log(__METHOD__, 'update player score', $player_id, $score, $userId);

		$update_data = array('manual_score' => $score, 'updated_at' => $this->utils->getNowForMysql(), 'processed_by' => $userId);

//		$this->db->where('generate_date', $date);
		$this->db->where('player_id', $player_id);
		$this->db->update($this->tableName, $update_data);
		if ($this->db->affected_rows() == '1') {
			#update
			return true;
		}else{
			#insert
			$insert_data = array(
				'player_id' => $player_id,
				'manual_score' => $score,
				'generate_date' => $date,
				'updated_at' => $this->utils->getNowForMysql(),
				'processed_by' => $userId,
			);
			$this->db->insert($this->tableName, $insert_data);
			return ($this->db->affected_rows() != 1) ? false : true;
		}
	}

	public function createAdjustmentRecord($userId, $player_id, $score_type, $score, $reason, $action_log, $before_score, $after_score){
		$this->utils->debug_log(__METHOD__, 'params', $userId, $player_id, $score_type, $score, $reason, $action_log, $before_score, $after_score);

		$success = false;

		$this->utils->debug_log(__METHOD__, 'score process', $before_score, $after_score, $score);

		$score_data = array(
			'player_id' => $player_id,
			'type' => $score_type,
			'score' => $score,
			'created_at' => $this->utils->getNowForMysql(),
			'created_by' => $userId,
			'before_score' => $before_score,
			'after_score' => $after_score,
			'note' => $reason,
			'action_log' => $action_log
		);

		if (!empty($score_data)) {
			$success = $this->insertData($this->table_for_score_history, $score_data);
		}

		return $success;
	}

	public function getPlayersRanklist($playerId = false, $limit = 50, $offset = 0){
        if($this->utils->getConfig('enabled_player_score')){

            $this->db->select('rank.player_id, CAST(rank.rank as UNSIGNED) rank', false);
            // $this->db->select('sum(score.game_score) + sum(score.manual_score) score', false);
            $this->db->select('rank.current_score score', false);
            $this->db->from('score_rank rank');
            $this->db->join('player p', 'rank.player_id = p.playerId', 'LEFT');
            $this->db->join('total_score score', 'rank.player_id = score.player_id');
            $this->db->group_by('rank.player_id');
    
            if(!empty($playerId)){
                $this->db->select('p.username username');
                $this->db->where('rank.player_id', $playerId);
            }
    
            $this->db->order_by('rank','asc');
            if($limit) {

                $this->db->limit($limit, $offset);
            }
            $qry = $this->db->get();
            return $this->getMultipleRowArray($qry);
        }
        return false;

	}

    public function getPlayerNewbetRanklist($playerId = false, $limit = null, $offset = 0, $sync_date = false){
        if($this->utils->getConfig('enabled_player_score')){

            $rank_name = 'newbet';
            $newbet_setting = $this->checkCustomRank('newbet');
            if (!$newbet_setting) {
                return false;
            }
            $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();
            $newbet_rank_limit = empty($newbet_setting['rank_limit']) ? 10 : $newbet_setting['rank_limit'];
            //for sync_original_game_logs_anytime
            if (is_object($syncDate) && $syncDate instanceof DateTime) {
                $syncDate = $syncDate->format('Y-m-d');
            }
    
            //for syncPlayerRankWithScore
            if(is_string($syncDate)){
                $syncDate=$this->utils->formatDateForMysql(new DateTime($syncDate));
            }
            $rank_key = $rank_name.'_'.$syncDate;
            $this->db->select('rank.player_id, CAST(rank.rank as UNSIGNED) rank, rank.rank_key, p.username', false);
            // $this->db->select('sum(score.game_score) + sum(score.manual_score) score', false);
            if($this->db->field_exists('playerpromoId', $this->table_for_score_rank)){
                $this->db->select('rank.playerpromoId', false);
            }
            $this->db->select('rank.current_score score', false);
            $this->db->from('score_rank rank');
            $this->db->join('player p', 'rank.player_id = p.playerId', 'LEFT');
            $this->db->where('rank.rank_key', $rank_key );
            if(!empty($playerId)){
                $this->db->select('p.username username');
                $this->db->where('rank.player_id', $playerId);
            } else {
                if($newbet_rank_limit) {
                    $this->db->where("rank.rank <=", 10);
                }
            }
    
            $this->db->order_by('rank','asc');
            if($limit) {
                $this->db->limit($limit, $offset);
            }
            $qry = $this->db->get();
            return $this->getMultipleRowArray($qry);
        }
        return false;
    }

    public function createPlayerTotalScore($player_id, $normal_score, $action_log, $processed_by, $generate_date = null, $rank_name = null){
        $this->utils->debug_log(__METHOD__, 'create player total score', $player_id, $normal_score, $action_log, $processed_by);
        $data['player_id'] = $player_id;
        $data['game_score'] = $normal_score;
        $data['updated_at'] = $this->utils->getNowForMysql();
        $data['action_log'] = json_encode($action_log);
        $data['processed_by'] = $processed_by;
        if(!empty($generate_date)) {
            $data['generate_date'] = $generate_date;
        }
        if(!empty($rank_name)){
            $data['rank_name'] = $rank_name;
        }

        return $this->insertData($this->tableName, $data);
    }

    public function updatePlayerTotalScore($player_id, $normal_score, $action_log, $processed_by, $generate_date = null, $rank_name = null){
        $this->utils->debug_log(__METHOD__, 'update player total score', $player_id, $normal_score, $action_log, $processed_by);
        $data['game_score'] = $normal_score;
        $data['updated_at'] = $this->utils->getNowForMysql();
        $data['action_log'] = json_encode($action_log);
        $data['processed_by'] = $processed_by;
        if(!empty($generate_date)) {
            $data['generate_date'] = $generate_date;
            $this->db->where("generate_date", $generate_date);
        }
        if(!empty($rank_name)){
            $data['rank_name'] = $rank_name;
            $this->db->where("rank_name", $rank_name);
        }

        $this->db->where("player_id", $player_id);
        $this->db->set($data);

        return $this->runAnyUpdate($this->tableName);
    }

    public function getPlayerTotalScore($for_ranking = false, $generate_date = null, $rank_name = null, $rank_limit = null, $player_id = null){
        $playerTotalScore = [];

        if($for_ranking){
            switch ($rank_name) {
                case 'newbet':
                    $newbet_setting = $this->checkCustomRank('newbet');
                    if (!$newbet_setting) {
                        return false;
                    } else {
                        $ignore_player_tags = !empty($newbet_setting['ignore_tag_id']) && is_array($newbet_setting['ignore_tag_id']) ? $newbet_setting['ignore_tag_id'] : false;
                        if($ignore_player_tags) {
                            $query_tag_arr = implode(",", $ignore_player_tags);
                            // $this->db->select('playertag.tagId');
                            $this->db->select("sum(case when playertag.tagId in ($query_tag_arr) then 1 else 0 end) as is_ignore");
                            // $this->db->join("player",  "player.playerId = total_score.player_id");
                            $this->db->join("playertag",  "total_score.player_id = playertag.playerId", "left");
                            // $this->db->having("(playertag.tagId is null OR (playertag.tagId not in ($query_tag_arr)))");
                            $this->db->having("is_ignore", 0);
                            $this->db->group_by("total_score.player_id");
                        }
                    }
                    break;
            }
            $this->db->select('total_score.player_id, total_score.game_score, total_score.manual_score');
            $this->db->from($this->tableName);
            $this->db->join("player",  "player.playerId = total_score.player_id");
            $this->db->where('total_score.player_id <>', self::ID_FOR_TOTAL_SCORE);
            if(!empty($generate_date)) {
                $this->db->where('total_score.generate_date', $generate_date);
            }
            if(!empty($rank_name)) {
                $this->db->where('total_score.rank_name', $rank_name);
            }
            if(!empty($rank_limit)){
                $this->db->order_by("(game_score+manual_score)", "desc");
                $this->db->limit($rank_limit);
            }
            // $this->db->group_by('player_id');
        }else{
            if(!empty($generate_date)) {
                $this->db->where('generate_date', $generate_date);
            }
            if(!empty($rank_name)) {
                $this->db->where('rank_name', $rank_name);
            }
            if($player_id === self::ID_FOR_TOTAL_SCORE || !empty($player_id)){
                $this->db->where('player_id', $player_id);
            }

            $this->db->from($this->tableName);
        }

        $result = $this->runMultipleRowArray();

        $this->utils->printLastSQL();

        if(!empty($result)){
            foreach($result as $row){
                if($for_ranking){
                    $total_score = $row['game_score'] + $row['manual_score'];
                    if(!empty($total_score)){
                        $playerTotalScore[$row['player_id']] = $total_score;
                    }
                }else{
                    $playerTotalScore[$row['player_id']] = $row;
                }
            }
            if($for_ranking){
                arsort($playerTotalScore);
            }
        }

        return $playerTotalScore;
    }

    public function syncNewbetPlayerScoreRank($generate_date = null, $rank_name = null, $rank_limit = null){
        $success = false;
        $sync_rank = true;
        $countReleasedPlayer = $this->countReleasedPlayer($rank_name.'_'.$generate_date);
        if(!empty($countReleasedPlayer) && $countReleasedPlayer> 0) {
            $this->utils->info_log(__METHOD__.' countReleasedPlayer is not empty, ignore sync rank. ', ['countReleasedPlayer' => $countReleasedPlayer, 'rank_key' => $rank_name.'_'.$generate_date]);
            return true;
        }
        $playerTotalScore = $this->getPlayerRealBetByDateForNewbet($generate_date);
        $rank_key = $rank_name.'_'.$generate_date;

        if(!empty($playerTotalScore)){

            $rank_list = [];
            $now = $this->utils->getNowForMysql();
            foreach ($playerTotalScore as $index => $record) {
                    $rank_list[] = [
                        'player_id' => $record->player_id,
                        'rank' => $index+1,
                        'current_score' => $record->betamount,
                        'updated_at' => $now,
                        'rank_key' => $rank_key,
                    ];
            }
            // END ranking with player_id and score , order by score desc

            // Delete all content and re-ranking
            if(!empty($rank_list)){
                $cnt = 0;
                $success = false;
                $delete_success = true;
                $suc_ids = [];
                $fail_ids =[];
                $controller = $this;
                $anyid = random_string('numeric', 5);

                $success = $this->lockAndTrans(Utils::LOCK_ACTION_AUTOMATICALLY_ADJUST_PLAYER_SCORE, $anyid, function ()
                    use ($controller, $rank_list, $cnt, $success, $delete_success, $suc_ids, $fail_ids, $rank_key) {

                        // check exist data
                        $controller->db->select('count(*)')->from($this->table_for_score_rank)->where('rank_key', $rank_key);
                        $exist_data = $controller->runExistsResult();

                        // if exist then delete
                        if($exist_data){
                            $controller->db->where('rank_key', $rank_key);
                            $delete_success = $controller->runRealDelete($this->table_for_score_rank);
                            $controller->utils->debug_log('run real delete result', $delete_success);
                        }

                        if($delete_success){
                            foreach($rank_list as $rank_data){
                                $success = $controller->insertData('score_rank', $rank_data);
                                if($success){
                                    $cnt += 1;
                                    $suc_ids[] = $rank_data['player_id'];
                                }else{
                                    $fail_ids[] = $rank_data['player_id'];
                                    return false;
                                }
                            }
                        }
                    return $success;
                });

                $controller->utils->debug_log(__METHOD__, 'multiple player insert score rank result key: '.$rank_key, $delete_success, $success, $cnt, $suc_ids, $fail_ids);
            }
        } else {
            $cnt = 0;
            $success = false;
            $delete_success = true;
            $suc_ids = [];
            $fail_ids =[];
            $controller = $this;
            $anyid = random_string('numeric', 5);
            $success = $this->lockAndTrans(Utils::LOCK_ACTION_AUTOMATICALLY_ADJUST_PLAYER_SCORE, $anyid, function ()
            use ($controller, $cnt, $success, $delete_success, $suc_ids, $fail_ids, $rank_key) {

                // check exist data
                $controller->db->select('count(*)')->from($this->table_for_score_rank)->where('rank_key', $rank_key);
                $exist_data = $controller->runExistsResult();

                // if exist then delete
                if($exist_data){
                    $controller->db->where('rank_key', $rank_key);
                    $delete_success = $controller->runRealDelete($this->table_for_score_rank);
                    $controller->utils->debug_log('run real delete result', $delete_success);
                }
                $success = $delete_success;
            return $success;
        });
        }

        return $success;
    }
    public function syncPlayerScoreRank($generate_date = null, $rank_name = null, $rank_limit = null){
        $success = false;
        $sync_rank = true;
        $playerTotalScore = $this->getPlayerTotalScore($sync_rank, $generate_date, $rank_name, $rank_limit);
        $countReleasedPlayer = $this->countReleasedPlayer($rank_name.'_'.$generate_date);
        if(!empty($countReleasedPlayer) && $countReleasedPlayer> 0) {
            $this->utils->debug_log(__METHOD__.' countReleasedPlayer is not empty, ignore sync rank. ', ['countReleasedPlayer' => $countReleasedPlayer, 'rank_key' => $rank_name.'_'.$generate_date]);
            return true;
        }

        if(!empty($playerTotalScore)){
            // START ranking with player_id and score , order by score desc
            $pleyer_id_arr = array_keys($playerTotalScore); // all player id
            $current_socre_arr = array_values($playerTotalScore); // all values

            // in order to array_count_value, need to convert double to string
            foreach($current_socre_arr as &$current_score){
                $current_score = (string) $current_score;
            }
            $occurrences = array_count_values($current_socre_arr); // count each score repeat times

            $uniqueScore = array_unique($playerTotalScore); //exclude duplicate score

            $i = 0;
            $rank_str = null;
            foreach($uniqueScore as $score) {
                $score = (string)$score;
                // $rank_str .= str_repeat($score .'-'.($i+1).',',$occurrences[$score]);
                $rank_str .= str_repeat(($i+1).',',$occurrences[$score]);
                $i += $occurrences[$score];
            }

            $rank_str = rtrim($rank_str, ',');
            $rank_arr = explode(',', $rank_str);

            $rank_arr = array_values($rank_arr); // final rank
            $rankInfo = array_combine($pleyer_id_arr, $rank_arr); // combine player id and score

            $rank_key = $rank_name.'_'.$generate_date;
            $rank_list = [];
            if(!empty($rankInfo)){
                $now = $this->utils->getNowForMysql();
                $i = 0;
                foreach ($rankInfo as $player_id => $player_rank) {
                    $rank_list[] = [
                        'player_id' => $player_id,
                        'rank' => $player_rank,
                        'current_score' => $current_socre_arr[$i],
                        'updated_at' => $now,
                        'rank_key' => $rank_key,
                    ];
                    $i++;
                }
            }
            // END ranking with player_id and score , order by score desc

            // Delete all content and re-ranking
            if(!empty($rank_list)){
                $cnt = 0;
                $success = false;
                $delete_success = true;
                $suc_ids = [];
                $fail_ids =[];
                $controller = $this;
                $anyid = random_string('numeric', 5);

                $success = $this->lockAndTrans(Utils::LOCK_ACTION_AUTOMATICALLY_ADJUST_PLAYER_SCORE, $anyid, function ()
                    use ($controller, $rank_list, $cnt, $success, $delete_success, $suc_ids, $fail_ids, $rank_key) {

                        // check exist data
                        $controller->db->select('count(*)')->from($this->table_for_score_rank)->where('rank_key', $rank_key);
                        $exist_data = $controller->runExistsResult();

                        // if exist then delete
                        if($exist_data){
                            $controller->db->where('rank_key', $rank_key);
                            $delete_success = $controller->runRealDelete($this->table_for_score_rank);
                            $controller->utils->debug_log('run real delete result', $delete_success);
                        }

                        if($delete_success){
                            foreach($rank_list as $rank_data){
                                $success = $controller->insertData('score_rank', $rank_data);
                                if($success){
                                    $cnt += 1;
                                    $suc_ids[] = $rank_data['player_id'];
                                }else{
                                    $fail_ids[] = $rank_data['player_id'];
                                    return false;
                                }
                            }
                        }
                    return $success;
                });

                $controller->utils->debug_log(__METHOD__, 'multiple player insert score rank result key: '.$rank_key, $delete_success, $success, $cnt, $suc_ids, $fail_ids);
            }
        }

        return $success;
    }

    public function updateDetailScore($result, &$detail_score){
        $deposit_score_base = 1.5;
        $bet_score_base = 1.5;
        $win_score_base = 30;
        $referral_score_base = 10;
        if(!empty($result)){
            foreach ($result as $row){
                #only for loop deposit
                if(!empty($row['total_deposit'])){
                    $deposit_score = $deposit_score_base * floor($row['total_deposit']/50);
                    $detail_score[$row['player_id']]['total_deposit'] = $row['total_deposit'];
                    $detail_score[$row['player_id']]['deposit_score'] = $deposit_score;
                }

                #only for loop bet
                if(!empty($row['total_bet'])){
                    $bet_score = $bet_score_base * floor($row['total_bet']/1000);
                    $detail_score[$row['player_id']]['total_bet'] = $row['total_bet'];
                    $detail_score[$row['player_id']]['bet_score'] = $bet_score;
                }

                #only for loop win
                if(!empty($row['total_win'])){
                    $win_score = $win_score_base * floor($row['total_win']/1000);
                    $detail_score[$row['player_id']]['total_win'] = $row['total_win'];
                    $detail_score[$row['player_id']]['win_score'] = $win_score;
                }

                #only for loop referral
                if(!empty($row['total_referral'])){
                    $referral_score = $referral_score_base * $row['total_referral'];
                    $detail_score[$row['player_id']]['total_referral'] = $row['total_referral'];
                    $detail_score[$row['player_id']]['referral_score'] = $referral_score;
                }
            }
        }
    }

    public function syncPlayerTotalScore($sync_date_from = null, $sync_date_to = null, $sync_rank = false) {
        $this->load->model(['total_player_game_minute', 'users', 'transactions', 'player_friend_referral', 'total_player_game_minute']);
        $success = false;

        $this->utils->debug_log("<===== SYNC PLAYER GAME SCORE PARAMS =======>", $sync_date_from, $sync_date_to, $sync_rank);

        // only for cronjob
        if($sync_rank){
            $success = $this->syncPlayerScoreRank();
            if(!$success){
                $this->utils->debug_log("<===== SYNC PLAYER TOTAL SCORE FAILED =======>");
            }
        }else{
            // sync game logs with update player score
            $detail_score = [];
            $fromDatetime = !empty($sync_date_from) ? $sync_date_from : '2022-01-01 00:00:00';
            $toDatetime = !empty($sync_date_to) ? $sync_date_to : '2022-01-03 23:59:59';

            //for sync_original_game_logs_anytime
            if (is_object($fromDatetime) && $fromDatetime instanceof DateTime) {
                $fromDatetime = $fromDatetime->format('Y-m-d H:i:s');
                $toDatetime = $toDatetime->format('Y-m-d H:i:s');
                $maxDate='2022-01-03 23:59:59';
            }

            //for syncPlayerRankWithScore
            if(is_string($fromDatetime)){
                $maxDate=$this->utils->formatDateTimeForMysql(new DateTime('2022-01-03 23:59:59'));
            }

            $this->utils->debug_log("<===== SYNC PLAYER TOTAL SCORE FROM ".$fromDatetime." TO ".$toDatetime.", maxDate: [".$maxDate."]=====>");

            if($toDatetime <= $maxDate){
                #deposit
                $deposit_info = $this->transactions->sumDepositAmountByDate($fromDatetime, $toDatetime);
                $this->utils->debug_log("<===== Deposit Infos =======>", $deposit_info);
                $this->updateDetailScore($deposit_info, $detail_score);

                #bet amount and win amount
                $game_infos = $this->total_player_game_minute->getTotalBetsWinsLossByAllPlayers($fromDatetime, $toDatetime);
                $this->utils->debug_log("<===== Game Infos =======>", $game_infos);
                $this->updateDetailScore($game_infos, $detail_score);

                #referral
                $referral_infos = $this->player_friend_referral->getPlayerTotalFriendRefferalCountByDatetimeAndStatus($fromDatetime, $toDatetime);
                $this->utils->debug_log("<===== Referral Infos =======>", $referral_infos);
                $this->updateDetailScore($referral_infos, $detail_score);

                $playerTotalScore = $this->getPlayerTotalScore();

                if(!empty($detail_score)){
                    foreach ($detail_score as $player_id => $category){
                        $total_score = 0;
                        if(!empty($category['deposit_score'])){
                            $total_score+=$category['deposit_score'];
                        }
                        if(!empty($category['bet_score'])){
                            $total_score+=$category['bet_score'];
                        }
                        if(!empty($category['win_score'])){
                            $total_score+=$category['win_score'];
                        }
                        if(!empty($category['referral_score'])){
                            $total_score+=$category['referral_score'];
                        }
                        if(!empty($detail_score[$player_id])){
                            $action_log = $detail_score[$player_id];
                        }

                        if(!empty($playerTotalScore[$player_id])){
                            $success = $this->updatePlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID);
                        }else{
                            $success = $this->createPlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID);
                        }

                        if(!$success){
                            $this->utils->debug_log("<===== INSERT UPDATE PLAYER SCORE FAILED =======>", $player_id, $detail_score);
                        }
                    }
                }else{
                    $this->utils->debug_log("<===== INSERT UPDATE PLAYER SCORE FAILED, BECAUSE EMPTY DETAIL SCORE DATA =======>");
                }
            }else{
                $this->utils->debug_log("<===== INSERT UPDATE PLAYER SCORE FAILED, BECAUSE IN NOT RIGHT DATA PERIOD =======>", $fromDatetime, $toDatetime, $maxDate);
            }

        }
        return $success;
    }

    public function syncNewbetScore($sync_date = null, $sync_rank = false)
    {
        $this->load->model(['total_player_game_minute', 'users', 'total_player_game_minute']);
        $success = false;

        $this->utils->debug_log("<===== SYNC NEW BET SCORE PARAMS =======>", $sync_date, $sync_rank);
        $rank_name = 'newbet';

        $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();

        //for sync_original_game_logs_anytime
        if (is_object($syncDate) && $syncDate instanceof DateTime) {
            $syncDate = $syncDate->format('Y-m-d');
        }

        //for syncPlayerRankWithScore
        if(is_string($syncDate)){
            $syncDate=$this->utils->formatDateForMysql(new DateTime($syncDate));
        }

        $this->utils->debug_log("<===== SYNC NEW BET PLAYER TOTAL SCORE DATE =====>", $syncDate);

        // only for cronjob
        if($sync_rank){
            $rank_limit = 100;
            $success = $this->syncNewbetPlayerScoreRank($syncDate, $rank_name, $rank_limit);
            if(!$success){
                $this->utils->debug_log("<===== SYNC PLAYER TOTAL SCORE FAILED =======>");
            }
        }else{
            $players_newbet_record = $this->getPlayerRealBetByDateForNewbet($syncDate);
            if(empty($players_newbet_record)) return false;
            $player_total_score_list = $this->getPlayerTotalScore(false, $syncDate, $rank_name);
            foreach($players_newbet_record as $record){
                $player_id = $record->player_id;
                $betamount = (int)$record->betamount;
                $total_score = floor($betamount);
                $action_log = [
                    "total_bet" => $total_score,
                    "bet_score" => $total_score,
                ];
                $this->utils->debug_log('========================GET',  $player_id, $betamount, $total_score);

                if(!empty($player_total_score_list[$player_id])){
                    // $this->utils->debug_log('========================UPDATE',  $player_id, $total_score);
                    $success = $this->updatePlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID, $syncDate, $rank_name);
                }else{
                    // $this->utils->debug_log('========================INSERT',  $player_id, $total_score);
                    $success = $this->createPlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID, $syncDate, $rank_name);
                }
            }

            $this->utils->debug_log("<===== SYNC NEW BET PLAYER TOTAL SCORE COUNT RECORD =====>", count($players_newbet_record));
        }

        return $success;
    }

    public function syncNewbetTotalScore($sync_date = null) {
        $this->load->model(['total_player_game_minute', 'users', 'total_player_game_minute']);
        $success = false;

        $this->utils->debug_log("<===== SYNC NEW BET SCORE PARAMS =======>", $sync_date);
        $rank_name = 'newbet';

        $syncDate = !empty($sync_date) ? $sync_date : $this->utils->getTodayForMysql();

        $countReleasedPlayer = $this->countReleasedPlayer($rank_name.'_'.$sync_date);
        if(!empty($countReleasedPlayer) && $countReleasedPlayer> 0) {
            $this->utils->info_log(__METHOD__.' countReleasedPlayer is not empty, ignore sync rank. ', ['countReleasedPlayer' => $countReleasedPlayer, 'rank_key' => $rank_name.'_'.$sync_date]);
            return true;
        }

        //for sync_original_game_logs_anytime
        if (is_object($syncDate) && $syncDate instanceof DateTime) {
            $syncDate = $syncDate->format('Y-m-d');
        }

        //for syncPlayerRankWithScore
        if(is_string($syncDate)){
            $syncDate=$this->utils->formatDateForMysql(new DateTime($syncDate));
        }

        $this->utils->debug_log("<===== SYNC NEW BET PLAYER TOTAL SCORE DATE =====>", $syncDate);

        // only for cronjob
        $players_newbet_record = $this->getPlayerRealBetByDateForNewbet($syncDate, null, true);
        
        if(empty($players_newbet_record)) return false;

        $check_total_score = $this->getPlayerTotalScore(false, $syncDate, $rank_name, null, self::ID_FOR_TOTAL_SCORE);
        $record = $players_newbet_record[0];
        // foreach($players_newbet_record as $record){
            $player_id = self::ID_FOR_TOTAL_SCORE;
            $betamount = (float)$record->betamount;
            $total_score = round($betamount,2);
            $action_log = [
                "total_bet" => $total_score,
                "bet_score" => $total_score,
            ];
            $this->utils->debug_log('========================GET',  $player_id, $betamount, $total_score);

            if(!empty($check_total_score)){
                // $this->utils->debug_log('========================UPDATE',  $player_id, $total_score);
                $success = $this->updatePlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID, $syncDate, $rank_name);
            }else{
                // $this->utils->debug_log('========================INSERT',  $player_id, $total_score);
                $success = $this->createPlayerTotalScore($player_id, $total_score, $action_log, Users::SUPER_ADMIN_ID, $syncDate, $rank_name);
            }
        // }
        $this->utils->debug_log("<===== SYNC NEW BET PLAYER TOTAL SCORE COUNT RECORD =====>", count($players_newbet_record));
        return $success;
    }

    public function getPlayerRankDetails($playerId) {
        if(empty($playerId)){
            return false;
        }
        
        $this->utils->debug_log(__METHOD__, 'get player manual_score', $playerId);
        $this->db->select('player_id, manual_score, action_log');
        $this->db->where('player_id', $playerId);
        $qry = $this->db->get($this->tableName);
        return $this->getOneRowArray($qry);

        // return false;
    }

    public function getPlayerRealBetByDateForNewbet($dateFrom, $player_id = null, $get_total = false){

        $newbet_setting = $this->checkCustomRank('newbet');
        if (!$newbet_setting) {
            return false;
        }
        $all_game_description_id = [];
        if(isset($newbet_setting['game']) && is_array($newbet_setting['game'])){
            foreach ($newbet_setting['game'] as $game_platfrom => $game_code) {
                // $game_description_id = $this->db->query("SELECT id FROM game_description WHERE game_platform_id IN ('".T1LOTTERY_SEAMLESS_API."' , '".BISTRO_SEAMLESS_API."') AND game_code IN ('crash' , 'dice', '60')")->result_array();
                $this->db->select('id');
                $this->db->WHERE('game_platform_id', $game_platfrom);
                $this->db->where_in('game_code', $game_code); 
                $game_description_id = $this->db->get('game_description')->result_array();
                $game_description_id = array_column($game_description_id, 'id');
                $this->utils->debug_log('===========game_description_id=============', $game_description_id);
                $all_game_description_id = array_merge($all_game_description_id, $game_description_id);
            }
        }
        $this->utils->debug_log('===========all_game_description_id============= ', $all_game_description_id);

        $ignore_player_tags_players = [];
        $ignore_player_tags = !empty($newbet_setting['ignore_tag_id']) && is_array($newbet_setting['ignore_tag_id']) ? $newbet_setting['ignore_tag_id'] : false;
        if($ignore_player_tags) {
            // select playerId from playertag where tagId IN ($ignore_player_tags) and isDeleted = 0 group by playerI
            $this->db->select("playerId", false);
            $this->db->where_in("tagId", $ignore_player_tags);
            $this->db->where("isDeleted", 0);
            $this->db->group_by("playerId");
            $ignore_player_tags_players = $this->db->get('playertag')->result_array();
            $ignore_player_tags_players = array_column($ignore_player_tags_players, 'playerId');
            $this->utils->debug_log('===========ignore_player_tags_players=============', $ignore_player_tags_players);
        }

		$this->db->select('SUM(real_betting_amount) betamount, player_id, total_player_game_minute.date, max(total_player_game_minute.date_minute) lastbet');
        $this->db->where("date", $dateFrom);
        if($all_game_description_id) {
            $this->db->where_in("game_description_id", $all_game_description_id);
        } 
        else {

            // $this->db->where_in("game_description_id", ['12742', '10748', '11480']);
        }
        if(!empty($ignore_player_tags_players)) {
            $this->db->where_not_in('player_id', $ignore_player_tags_players);
        }

        if($player_id){
            $this->db->where("player_id", $player_id);
        }
        if(!$get_total){
            $this->db->group_by(array("player_id"));
            $this->db->order_by( 'betamount desc, lastbet asc', null, false);

            $this->db->limit(100);
        }
        
        $qry = $this->db->get('total_player_game_minute');
		$result = $this->getMultipleRow($qry);
        $this->utils->printLastSQL();
		return $result;

	}

    public function getAllRankKey($get_last = false){
        $this->db->select('rank_key, updated_at');
        $this->db->distinct();
        // $this->db->order_by('updated_at', 'DESC');
        if($get_last == true){
            $this->db->limit('1');
        }
        $this->db->where('rank_key <>', '');
        $this->db->order_by('rank_key', 'desc');
		$qry = $this->db->get($this->table_for_score_rank);
		return $this->getMultipleRowOneFieldArray('rank_key', $qry);
    }

    public function checkCustomRank($rank_name) {
        $custom_rank_setting = $this->utils->getConfig('custom_player_rank_list');
        if(empty($custom_rank_setting)) {
            return false;
        }
        $rank_setting = isset($custom_rank_setting[$rank_name]) ? $custom_rank_setting[$rank_name] : false;
        if (!$rank_setting || (isset($rank_setting['enable']) && $rank_setting['enable'] != true)) {
            return false;
        }

        return $rank_setting;
    }

    public function updateRankListPlayerPromoId($player_id, $playerPromoId, $rank_key) {
        $update_data['playerpromoId'] = $playerPromoId;
        $this->db->where('player_id', $player_id);
        $this->db->where('rank_key', $rank_key);
		$this->db->update($this->table_for_score_rank, $update_data);
        if ($this->db->affected_rows() != 0) {
			#update
			return true;
		}else {
            return false;
        }
    }

    public function countReleasedPlayer($rank_key){
        $this->db->where('rank_key', $rank_key);
        $this->db->where('playerpromoId is not null', null, false);
        $query = $this->db->get($this->table_for_score_rank);
        return $query->num_rows();
    }

}