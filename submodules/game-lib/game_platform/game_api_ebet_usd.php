<?php

require_once dirname(__FILE__).'/game_api_ebet.php';

class Game_api_ebet_usd extends Game_api_ebet
{
    const ORIGINAL_LOGS_TABLE_NAME = 'ebet_usd_game_logs';
    const MD5_FIELDS_FOR_ORIGINAL=['gameType', 'roundNo', 'payout', 'createTime', 'payoutTime', 'validBet', 'userId', 'username','realBet','niuniuWithHoldingTotal','niuniuWithHoldingDetail','niuniuResult'];
    const MD5_FLOAT_AMOUNT_FIELDS=['payout','validBet','realBet','niuniuWithHoldingTotal'];

    public function getPlatformCode(){
        return EBET_USD_API;
    }

    public function __construct(){
        parent::__construct();

        $this->CI->load->model('ebet_usd_game_logs');
        $this->ebet_usd_game_logs=$this->CI->ebet_usd_game_logs;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function processResultForSyncOriginalGameLogs($params) {

        $this->CI->load->model(array('external_system','original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJson);
        $result = array(
            'data_count'=> 0
        );
        if ($success) {
            $gameRecords = $this->processGameRecords($resultJson['betHistories'], $responseResultId);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'uniqueid',
                'uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            ); 
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
            $result['count'] = $resultJson['count'];
        }

        return array($success, $result);
    }

    public function syncMergeToGameLogs($token) {

        $this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

        $this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

        $cnt 	= 0;
        $rlt 	= array('success' => true);
        $result = $this->ebet_usd_game_logs->getebetGameLogStatistics($dateTimeFrom, $dateTimeTo);

        foreach ($result as $ebet_data) {
            if ($player_id = $this->getPlayerIdInGameProviderAuth($ebet_data->username)) {

                $cnt++;

                $player 		= $this->CI->player_model->getPlayerById($player_id);
                $bet_amount 	= $this->gameAmountToDB($this->getBetAmount($ebet_data));
                $real_bet_amount= $this->gameAmountToDB($this->getRealBetAmount($ebet_data));
                $result_amount 	= $this->gameAmountToDB($this->getResultAmount($ebet_data));
                $has_both_side 	= $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

                if(!empty($ebet_data->niuniuWithHoldingTotal)){
                    $result_amount = $result_amount - $ebet_data->niuniuWithHoldingTotal;
                }

                $extra = [
                    'table'=>$ebet_data->roundNo,
                    'trans_amount'=>$real_bet_amount,
                    'bet_type'=> lang("Bet Detail Link"),
                    'bet_details' => $this->processGameBetDetail($ebet_data,$bet_amount,$result_amount),
                    'sync_index' => $ebet_data->id,
                ];

                $this->syncGameLogs(
                    $ebet_data->game_type_id,  			# game_type_id
                    $ebet_data->game_description_id,	# game_description_id
                    $ebet_data->gameshortcode, 			# game_code
                    $ebet_data->game_type_id, 			# game_type
                    $ebet_data->game, 					# game
                    $player_id, 						# player_id
                    $ebet_data->username, 				# player_username
                    $bet_amount, 						# bet_amount
                    $result_amount, 					# result_amount
                    null,								# win_amount
                    null,								# loss_amount
                    null,								# after_balance
                    $has_both_side, 					# has_both_side
                    $ebet_data->external_uniqueid, 		# external_uniqueid
                    $ebet_data->start_at,				# start_at
                    $ebet_data->end_at,					# end_at
                    $ebet_data->response_result_id,		# response_result_id
                    Game_logs::FLAG_GAME,
                    $extra
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

        return array('success' => true);
    }

    private function isInvalidRow($row) {
        return FALSE;
    }

    private function getBetAmount($row) {
        $bet = $row->bet;
        return $bet;
    }

    private function getResultAmount($row) {
        $bet = $row->realBet;
        $result = $row->result;
        return $result - $bet;
    }

    private function getRealBetAmount($row) {
        $bet = $row->realBet;
        return $bet;
    }

}

/*end of file*/
