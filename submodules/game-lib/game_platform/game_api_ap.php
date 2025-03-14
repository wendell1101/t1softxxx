<?php

require_once dirname(__FILE__) . '/game_api_pinnacle.php';

class Game_api_ap extends Game_api_pinnacle {

	const ORIGINAL_TABLE = "ap_game_logs";

	public function __construct() {
		parent::__construct();
	}

	public function getOriginalTable()
	{
		return self::ORIGINAL_TABLE;
	}

	public function getPlatformCode() {
		return AP_GAME_API;
	}

	public function createPlayer($userName, $playerId, $password, $email = null, $extra = null) {

		$extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

		parent::createPlayer($userName, $playerId, $password, $email, $extra);
		
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
			'playerId' => $playerId
        );

        $params = array(
			"agentCode" => $this->agentCode,
			"loginId" => $playerName,
			"method" => "POST"
        );

        $this->utils->debug_log("CreatePlayer params ============================>", $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $sbe_playerName, $statusCode);
		$this->CI->utils->debug_log("AP_GAME_API @createPlayer processResultBoolean value", $success);
		
		if($success){
			//update external AccountID
			$externalAccountId = $playerName.'-'.$resultJsonArr['userCode'];
			$this->CI->utils->debug_log("AP_GAME_API @createPlayer playerName before updateExternalAccountIdForPlayer", $playerName);
			$this->CI->utils->debug_log("AP_GAME_API @createPlayer externalAccountId before updateExternalAccountIdForPlayer", $externalAccountId);
			$this->updateExternalAccountIdForPlayer($playerId, $externalAccountId);
		}

        //success if player already exist
        if(isset($resultJsonArr['code'])&&$resultJsonArr['code']==self::ERROR_PLAYER_ALREADY_EXIST){
            $success = true;
        }

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		return array($success, $resultJsonArr);
	}


	public function processSyncOriginalGameLogs($params){
		$this->CI->load->model(array('pinnacle_game_logs', 'player_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$gameRecords = $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, null, $statusCode);
		$result = array('data_count'=>0);
		if ($success) {
			if ($gameRecords) {
				$dataCount = 0;
				if (!empty($gameRecords)) {
					foreach ($gameRecords as $record) {
						$insertRecord = array();
						$playerID = $this->getPlayerIdByExternalAccountId($record['loginId'].'-'.$record['userCode']);

						if(!$playerID){
							if ($this->enable_mm_channel_notifications) {
								$sql = $this->CI->db->last_query();
								$baseUrl =  $this->utils->getBaseUrlWithHost();
							
								$message = "@all Non-existing Player Alert"."\n";
								$message .= "Client: ".$baseUrl."\n";
								$message .= PHP_EOL;
								$message .= PHP_EOL;
								$message .= "Last Query:" . "\n" .
											"----------------------------" .  "\n".
											$sql;
								$currentFile = __FILE__;
								$currentLine = __LINE__;
								$currentMethod = __METHOD__;
								
								$message .= "\n\n Current file: $currentFile\n";
								$message .= "\n Current method: $currentMethod\n";
								$message .= "\n Current line: $currentLine\n";
							
								$this->SendNotificationToMattermost($message);
								
							}
							continue;
						}

						$playerUsername = $this->getGameUsernameByPlayerId($playerID);
						$insertRecord['wagerId'] = isset($record['wagerId'])?$record['wagerId']:null;
						$insertRecord['eventName'] = isset($record['eventName'])?$record['eventName']:null;
						$insertRecord['parentEventName'] = isset($record['parentEventName'])?$record['parentEventName']:null;
						$insertRecord['headToHead'] = isset($record['headToHead'])?$record['headToHead']:null;
						$insertRecord['wagerDateFm'] = isset($record['wagerDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['wagerDateFm']))):null;
						$insertRecord['eventDateFm'] = isset($record['eventDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['eventDateFm']))):null;
						$insertRecord['status'] = isset($record['status'])?$record['status']:null;
						$insertRecord['homeTeam'] = isset($record['homeTeam'])?$record['homeTeam']:null;
						$insertRecord['awayTeam'] = isset($record['awayTeam'])?$record['awayTeam']:null;
						$insertRecord['selection'] = isset($record['selection'])?$record['selection']:null;
						$insertRecord['handicap'] = isset($record['handicap'])?$record['handicap']:null;
						$insertRecord['odds'] = isset($record['odds'])?$record['odds']:null;
						$insertRecord['oddsFormat'] = isset($record['oddsFormat'])?$record['oddsFormat']:null;
						$insertRecord['betType'] = isset($record['betType'])?$record['betType']:null;
						$insertRecord['league'] = isset($record['league'])?$record['league']:null;
						$insertRecord['stake'] = isset($record['stake'])?$record['stake']:null;
						$insertRecord['sport'] = isset($record['sport'])?$record['sport']:null;
						if($record['betType']==self::MIX_PARLAY){
							$insertRecord['sport'] = "MIX_PARLAY";
						}
						$insertRecord['currencyCode'] = isset($record['currencyCode'])?$record['currencyCode']:null;
						$insertRecord['inplayScore'] = isset($record['inplayScore'])?$record['inplayScore']:null;
						$insertRecord['inPlay'] = isset($record['inPlay'])?$record['inPlay']:null;
						$insertRecord['homePitcher'] = isset($record['homePitcher'])?$record['homePitcher']:null;
						$insertRecord['awayPitcher'] = isset($record['awayPitcher'])?$record['awayPitcher']:null;
						$insertRecord['homePitcherName'] = isset($record['homePitcherName'])?$record['homePitcherName']:null;
						$insertRecord['awayPitcherName'] = isset($record['awayPitcherName'])?$record['awayPitcherName']:null;
						$insertRecord['period'] = isset($record['period'])?$record['period']:null;
						$insertRecord['parlaySelections'] = isset($record['parlaySelections'])?json_encode($record['parlaySelections']):null;
						$insertRecord['category'] = isset($record['category'])?$record['category']:null;
						$insertRecord['toWin'] = isset($record['toWin'])?$record['toWin']:null;
						$insertRecord['toRisk'] = isset($record['toRisk'])?$record['toRisk']:null;
						$insertRecord['product'] = isset($record['product'])?$record['product']:null;
						$insertRecord['parlayMixOdds'] = isset($record['parlayMixOdds'])?$record['parlayMixOdds']:null;
						$insertRecord['competitors'] = isset($record['competitors'])?json_encode($record['competitors']):null;
						$insertRecord['userCode'] = isset($record['userCode'])?$record['userCode']:null;
						$insertRecord['winLoss'] = isset($record['winLoss'])?$record['winLoss']:null;
						$insertRecord['winLoss'] = isset($record['winLoss'])?json_encode($record['winLoss']):null;
						$insertRecord['result'] = isset($record['result'])?json_encode($record['result']):null;

						//extra info from SBE
						$insertRecord['userName'] = $playerUsername;
						$insertRecord['playerId'] = $playerID;
						$insertRecord['uniqueid'] = $insertRecord['wagerId']; //add external_uniueid for og purposes
						$insertRecord['external_uniqueid'] = $insertRecord['wagerId']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;

						// only settled , cancelled and deleted  have settledDate if open and queue value must be null
						if($record['status']=="SETTLED"||$record['status']=="CANCELLED"||$record['status']=="DELETED"){
							$insertRecord['settledDate'] = isset($record['settleDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['settleDateFm']))):null;
						}
						//insert data to Pinnacle gamelogs table database
						$this->CI->pinnacle_game_logs->syncGameLogs($insertRecord, 'ap_game_logs');
						$dataCount++;
					}
					$result['data_count'] = $dataCount;
				}
			}
		}
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'pinnacle_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->getGameLogStatistics($startDate, $endDate, $this->getPlatformCode());
		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $pinnacle_data) {

				if (!$pinnacle_data['playerId']) {
					continue;
				}

				$note = null;

				$cnt++;

				$game_description_id = $pinnacle_data['game_description_id'];
				$game_type_id = $pinnacle_data['game_type_id'];

				if (empty($game_description_id)) {

					list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($pinnacle_data, $unknownGame);

					if (empty($game_description_id)) {
						$this->CI->utils->debug_log('empty game_description_id', $unknownGame);
						continue;
					}
				}

				$status = $this->getGameRecordsStatus($pinnacle_data['status']);

				$bet_amount_for_cashback = $bet_amount = $real_betting_amount = $pinnacle_data['bet_amount'];
                # IF Match is DRAW valid bet is 0, This is refunded to player.
                /*if (strpos(strtolower($pinnacle_data['result']),'draw') !== false){
					// $valid_bet = 0;
					$pinnacle_data['bet_amount'] = 0;
				}*/

				###### START PROCESS BET AMOUNT CONDITIONS
				# get bet conditions for status
				$betConditionsParams = [];
				$betConditionsParams['bet_status'] = strtolower( trim($pinnacle_data['result'], '"') );

				# get bet conditions for win/loss
				$betConditionsParams['win_loss_status'] = null;
				$betConditionsParams['odds_status'] = null;

				if($pinnacle_data['winLoss']<0){
					if(abs($pinnacle_data['winLoss']) / $pinnacle_data['bet_amount'] == .5 ){
						$betConditionsParams['win_loss_status'] = 'half_lose';
					}
				}else{
					if($pinnacle_data['winLoss'] / $pinnacle_data['bet_amount'] == .5 ){
						$betConditionsParams['win_loss_status'] = 'half_win';
					}
				}

				# get bet conditions for odds
				$oddsType = $this->getUnifiedOddsType($pinnacle_data['oddsFormat']);
				$betConditionsParams['valid_bet_amount'] = $bet_amount;
				$betConditionsParams['bet_amount_for_cashback'] = $bet_amount;
				$betConditionsParams['real_betting_amount'] = $real_betting_amount;
				$betConditionsParams['odds_type'] = $oddsType;
				$betConditionsParams['odds_amount'] = $pinnacle_data['odds'];
				
				list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);
				if(!empty($_appliedBetRules)){
					$bet_amount = $_validBetAmount;
					$bet_amount_for_cashback = $_betAmountForCashback;
					$real_betting_amount = $_realBettingAmount;
				}

				###### /END PROCESS BET AMOUNT CONDITIONS

                $this->utils->debug_log('==============> SelectionsDetails value', $pinnacle_data['parlaySelections']);
                $sectDetails = json_decode($pinnacle_data['parlaySelections'], true);
                //$sportsGameFields = array();
                //if ($status == Game_logs::STATUS_SETTLED) {
                $sportsGameFields = array(
                    'match_details' => !empty($sectDetails) ? $sectDetails[0]['eventName'] : $pinnacle_data['eventName'],
                    'match_type'    => !empty($sectDetails) ? $sectDetails[0]['inPlay'] : $pinnacle_data['inPlay'],
                    'handicap'      => !empty($sectDetails) ? $sectDetails[0]['handicap'] : $pinnacle_data['handicap'],
                    'bet_type'      => strtolower($pinnacle_data['game_code']) == 'mix_parlay' ? 'Mix Parlay' : 'Single Bet'
                );
                //}
                $this->utils->debug_log('==============> Pinnacle Sport Game Fields Value', $sportsGameFields);

				$bet_conditions_details = [];
				if(!empty($_betconditionsDetails)){
					$bet_conditions_details = $_betconditionsDetails;
				}

				$betDetails =  $this->utils->encodeJson(array_merge(
                        $this->processBetDetatails($pinnacle_data),
                        array('sports_bet' => $this->setBetDetails($pinnacle_data), 'Odds Type' => $oddsType
						)
                    )
                );

				$extra = array(
					'trans_amount'	=> 	$real_betting_amount,
					'status'		=> 	$status,
					'table' 		=>  $pinnacle_data['RoundID'],
					'odds' 			=>  $pinnacle_data['odds'],
					'odds_type'   =>  $oddsType,
					'note'			=>  $note,#json_encode($bet_conditions_details),
                    'bet_details'   =>  $betDetails,
                    'sync_index'  => $pinnacle_data['id'],
                    'real_betting_amount'  => $real_betting_amount,
				);


		
				$this->debug_external_uniqueid = $this->getSystemInfo('debug_external_uniqueid', false);
				if($pinnacle_data['external_uniqueid']==$this->debug_external_uniqueid){

					$this->utils->debug_log('==============> debug_external_uniqueid', 
					'pinnacle_data', $pinnacle_data,
					'betConditionsParams', $betConditionsParams);
				}
				
				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$pinnacle_data['game_code'],
					$pinnacle_data['game_type'],
					$pinnacle_data['game'],
					$pinnacle_data['playerId'],
					$pinnacle_data['userName'],
					$bet_amount,
					$pinnacle_data['result_amount'],
					null, # win_amount
					null, # loss_amount
					null,//$pinnacle_data['after_balance'], # after_balance
					0, # has_both_side
					$pinnacle_data['external_uniqueid'],
					$pinnacle_data['game_date'], //start
					$pinnacle_data['settled_date'], //end
					$pinnacle_data['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra,
                    $sportsGameFields
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	public function getGameLogStatistics($dateFrom, $dateTo, $game_platform_id = null) {
		$tableName = $this->getOriginalTable();
		$game_platform_id = $this->getPlatformCode();
	
		$sql = <<<EOD
SELECT
ap_game_logs.id as id,
ap_game_logs.playerId,
ap_game_logs.userName,
ap_game_logs.external_uniqueid,
ap_game_logs.wagerDateFm AS game_date,
IFNULL(ap_game_logs.settledDate, ap_game_logs.wagerDateFm) AS settled_date,
ap_game_logs.betType,
ap_game_logs.sport AS game_code,
ap_game_logs.sport AS original_game_code,
ap_game_logs.response_result_id,
ap_game_logs.stake AS bet_amount,
ap_game_logs.winLoss AS result_amount,
ap_game_logs.winLoss,
ap_game_logs.wagerId AS RoundID,
ap_game_logs.status,
ap_game_logs.odds,
ap_game_logs.eventName,
ap_game_logs.inPlay,
ap_game_logs.handicap,
ap_game_logs.selection,
ap_game_logs.inplayScore,
ap_game_logs.parlaySelections,
ap_game_logs.league,
ap_game_logs.oddsFormat,
ap_game_logs.result,
game_description.id AS game_description_id,
game_description.game_name AS game,
game_description.game_code,
game_description.game_type_id,
game_description.void_bet,
game_type.game_type
FROM {$tableName} as ap_game_logs
LEFT JOIN game_description 
ON ap_game_logs.sport = game_description.game_code 
AND game_description.game_platform_id = ? 
AND game_description.void_bet != 1
LEFT JOIN game_type 
ON game_description.game_type_id = game_type.id
WHERE 
IFNULL(ap_game_logs.settledDate, ap_game_logs.wagerDateFm) >= ? 
AND IFNULL(ap_game_logs.settledDate, ap_game_logs.wagerDateFm) <= ?
EOD;

		$params = [
			$game_platform_id,
			$dateFrom,
			$dateTo
		];
	
		$this->CI->utils->debug_log('getGameLogStatistics sql', $sql, $params);
		
		return $this->CI->game_provider_auth->runRawSelectSQLArray($sql, $params);
	}
}

/*end of file*/
