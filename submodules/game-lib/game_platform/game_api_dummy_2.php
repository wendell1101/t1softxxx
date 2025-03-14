<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/*
 * Dummy game API
 */
class Game_api_dummy_2 extends Abstract_game_api {
	public function getPlatformCode() {
		return DUMMY_GAME_API;
	}

	public function __construct() {
		parent::__construct();
		$this->generate_to_dashboard = $this->getSystemInfo('generate_to_dashboard');
	}

	# -- Implementation of API functions --
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $playerId, $password, $email, $extra);
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $infos);
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);
		return $this->returnUnimplemented();
	}

	/**
	 * query player balance
	 *
	 * @author Elvis_Chen
	 * @since 1.0.0 Elvis_Chen: Implement function
	 *
	 * @param string $playerName
	 * @return array
	 */
	public function queryPlayerBalance($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);

		$this->CI->load->model('player_model');
		$player = $this->CI->player_model->getPlayerByUsername($playerName);

		$subwallets = $this->CI->utils->get_sub_wallet($player->playerId);

		$result = $this->returnUnimplemented();

		foreach($subwallets as $subwallet){
			if($subwallet['game'] === $this->getSystemInfo('_system_code')){
				$result['success'] = true;
				$result['balance'] = $subwallet['totalBalanceAmount'];
			}
		}

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $amount);
		return $this->returnUnimplemented();
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $amount);
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $password);
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra = array()) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $extra);
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $password);
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token = null) {
		$this->utils->debug_log("Invoked in dummy game API", $token);
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model('game_logs');

        $response_result_id = parent::getValueFromSyncInfo($token, 'response_result_id');

        $dateTimeFromInput = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeToInput = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFromInput));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeToInput));
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $dateTimeFromString = $dateTimeFrom->format('Y-m-d H:i:s');
        $dateTimeToString = $dateTimeTo->format('Y-m-d H:i:s');
        $this->CI->utils->debug_log("DUMMY merge from [$dateTimeFromString] to [$dateTimeToString]");


        if ($response_result_id) {
        	$this->CI->db->where('response_result_id', $response_result_id);
        } else {
	        $this->CI->db->where('bet_time >=', $dateTimeFromString);
	        $this->CI->db->where('bet_time <=', $dateTimeToString);
        }

        $query = $this->CI->db->get('import_game_logs');
        $result = $query->result_array();

        $successCount = 0;
        $errorCount = array();

        if ( ! empty($result)) {

			$this->CI->load->model(['game_type_model','game_description_model','total_player_game_day']);
			
			$count = 0;
			$total = count($result);

            foreach ($result as $row) {

            	$count++;

                $this->utils->debug_log("DUMMY merge: {$count}/{$total}");


                $player_id = $this->getPlayerIdFromUsername($row['player_username']);

                if ( ! $player_id) {
                    $this->utils->debug_log("DUMMY merge: Player ID not found for username", $row['player_username']);
                    @$errorCount['Player not found: ' . $row['player_username']]++;
                    continue;
                }

				$game_type_id = $this->CI->game_type_model->getActiveGameTypeId($row['game_type'], $row['game_provider_id']);

	            if (empty($game_type_id)) {
                    $this->utils->debug_log("DUMMY merge: Game type not found: ", $row['game_type']);
                    @$errorCount['Game type not found: ' . $row['game_type']]++;
                    continue;
	            }

	            $this->CI->game_description_model->db->where('game_type_id', $game_type_id);
				$game_description = $this->CI->game_description_model->getGameDescriptionByGamePlatformIdAndGameName($row['game_provider_id'], $row['game_name']);

	            if (empty($game_description)) {
                    $this->utils->debug_log("DUMMY merge: Game not found: ", $row['game_name']);
                    @$errorCount['Game not found: ' . $row['game_name']]++;
                    continue;
	            }

                $game_description_id = $game_description[0]['id'];
                $game_code = $game_description[0]['game_code'];

                $available_bet 	= floatval(str_replace(',', '', $row['available_bet']));
				$result_amount 	= floatval(str_replace(',', '', $row['result_amount']));
				$win_amount 	= floatval(str_replace(',', '', $row['win_amount']));
				$loss_amount 	= floatval(str_replace(',', '', $row['loss_amount']));
				$after_balance 	= floatval(str_replace(',', '', $row['after_balance']));

				$has_both_side 	= $available_bet >= $result_amount && $result_amount > 0 ? 1 : 0;

                # TODO:
                $extra = array(
                	'game_platform_id' => $row['game_provider_id'],
                	'table' => $row['round_no'] == 'N/A' || empty($row['round_no']) ? NULL : $row['round_no'],
                	'trans_amount' => $row['real_bet'] == 'N/A' || empty($row['real_bet']) ? NULL : $row['real_bet'],
                	'bet_type' 				=> $row['bet_type'] == 'N/A' || empty($row['bet_type']) ? NULL : $row['bet_type'],
                	// 'real_betting_amount' 	=> $row['real_bet'] == 'N/A' || empty($row['real_bet']) ? NULL : $row['real_bet'],
                	// 'odds' 					=> $row['odds'] == 'N/A' || empty($row['odds']) ? NULL : $row['odds'],
                	// 'handicap' 				=> $row['handicap'] == 'N/A' || empty($row['handicap']) ? NULL : $row['handicap'],
                	// 'match_details' 			=> $row['match_details'] == 'N/A' || empty($row['match_details']) ? NULL : $row['match_details'],
                	// 'match_type' 			=> $row['match_type'] == 'N/A' || empty($row['match_type']) ? NULL : $row['match_type'],
                	// 'bet_details' 			=> $row['bet_detail'] == 'N/A' || empty($row['bet_detail']) ? NULL : $row['bet_detail'],
                	// 'note' 					=> $row['note'] == 'N/A' || empty($row['note']) ? NULL : $row['note'],
                );

                $this->syncGameLogs(
                    $game_type_id,					# game_type_id
                    $game_description_id,			# game_description_id
                    $game_code,						# game_code
                    $row['game_type'],				# game_type
                    $row['game_name'],				# game
                    $player_id,						# player_id
                    $row['player_username'],		# player_username
                    $available_bet, 				# bet_amount
                    $result_amount, 				# result_amount
                    $win_amount, 					# win_amount
                    $loss_amount, 					# loss_amount
                    $after_balance, 				# after_balance
                    $has_both_side, 				# has_both_side
                    $row['external_uniqueid'],		# external_uniqueid
                    $row['bet_time'], 				# start_at
                    $row['date'], 					# end_at
                    $row['response_result_id'], 	# response_result_id
                    Game_logs::FLAG_GAME, 			# flag
                    $extra							# extra
                );

                if ($this->generate_to_dashboard) {
                	$dataPlayerGameDay = array(
	                	"player_id" => $player_id,
	                	"betting_amount" => $available_bet,
	                	"date" => $row['date'],
	                	"updated_at" => $row['date'],
	                	"game_description_id" => $game_description_id,
	                	"game_platform_id" => $row['game_provider_id'],
	                	"game_type_id" => $game_type_id,
	                	"uniqueid" => $row['external_uniqueid'],
	                	"result_amount" => $result_amount,
	                );

                	$this->CI->total_player_game_day->syncToTotalPlayerGameDay($dataPlayerGameDay);
                }
                

                $successCount++;

            }
        }

        $this->CI->utils->debug_log("Done DUMMY merge from [$dateTimeFromString] to [$dateTimeToString], merge count: [$successCount]");
        return array('success' => true, 'success' => $successCount, 'error' => $errorCount);
	}

	public function isPlayerExist($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);
		return $this->returnUnimplemented();
	}

	public function blockPlayer($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);
		return $this->returnUnimplemented();
	}

	public function unblockPlayer($playerName) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName);
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		$this->utils->debug_log("Invoked in dummy game API", $apiName, $params);
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		$this->utils->debug_log("Invoked in dummy game API", $apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $oldPassword, $newPassword);
		return $this->returnUnimplemented();
	}
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $playerId, $dateFrom, $dateTo);
		return $this->returnUnimplemented();
	}
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$this->utils->debug_log("Invoked in dummy game API", $dateFrom, $dateTo, $playerName);
		return $this->returnUnimplemented();
	}
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		$this->utils->debug_log("Invoked in dummy game API", $playerName, $dateFrom, $dateTo);
		return $this->returnUnimplemented();
	}
	public function queryTransaction($transactionId, $extra) {
		$this->utils->debug_log("Invoked in dummy game API", $transactionId, $extra);
		return $this->returnUnimplemented();
	}
}
