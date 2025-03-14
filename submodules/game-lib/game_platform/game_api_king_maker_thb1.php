<?php
require_once dirname(__FILE__) . '/game_api_common_nttech_v2.php';

class Game_api_king_maker_thb1 extends Game_api_common_nttech_v2 {
	const CURRENCY_TYPE = "THB";
	const DEDAULT_LANG = "th";
    const KING_MAKER_THB1_ORIG_GAMELOGS_TABLE = "king_maker_thb1_game_logs";
    
    # Fields in king_maker_thb1_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        "gameType",
        //"comm", we remove this because sexy gaming api already removed this
        "txTime",
        // "bizDate",
        "winAmt",
        "gameInfo",
        "betAmt",
        "updateTime",
        "jackpotWinAmt",
        "turnOver",
        "userId",
        //"betType", we remove this because KINGMAKER don't have this at this moment. OGP-15601
        "platform",
        "txStatus",
        "jackpotBetAmt",
        //"createTime",we remove this because sexy gaming api already removed this
        "platformTxId",#unique
        "realBetAmt",
        "gameCode",
        "currency",
        "ID",#transid
        "realWinAmt",
        "roundId"
    ];

	public function getPlatformCode(){
		return KING_MAKER_GAMING_THB_B1_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
        $this->player_lang = self::DEDAULT_LANG;
        $this->original_gamelogs_table = self::KING_MAKER_THB1_ORIG_GAMELOGS_TABLE;
    }

    /*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  player_center/goto_kingmaker_game/2136/<game_code>/<language>/<is_mobile>/<is_redirect>/<game_type>
	 *  Desktop: player_center/goto_kingmaker_game/2136/KM-TABLE-007
	 *  Mobile: player_center/goto_kingmaker_game/2136/KM-TABLE-007/en/true/true
	 *
	 */
	public function queryForwardGame($playerName, $extra = null) 
	{
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $language = isset($extra["language"]) ?  
            $this->getLauncherLanguage($extra['language']) :
            $this->getLauncherLanguage($this->CI->language_function->getCurrentLanguage());

		$params = array(
			'cert' => $this->cert,
			'agentId' => $this->agent_id,
			'userId' => $gameUsername,
			'gameType' => $extra['game_type'],
			'platform' => $this->platform,
			'externalURL' => $this->external_url,
			'gameForbidden' => $this->game_forbidden,
			'language' =>  $language // we use player center language here as a default
        );

		if(isset($extra['game_code'])){
			$params['gameCode'] = $extra['game_code'];
		}

		#IDENTIFY MOBILE GAME
		if(isset($extra['is_mobile'])){
			$ismobile = $extra['is_mobile'] ? true : false;	
			if($ismobile){
				$params['isMobileLogin'] = true;
			}
		}

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
        );

        $result = $this->callApi(self::API_login, $params, $context);
		if($result["success"])
		{
			$this->CI->utils->debug_log('KING_MAKER_THB_B1_API queryForwardgame URL:', $result['url']);
			return ['success'=>true, 'url'=> $result['url']];
		}
		return ['success'=>false, 'url'=> null];
    }

    public function syncOriginalGameLogsFromExcel($isUpdate = false){
        set_time_limit(0);
        $this->CI->load->model(array('external_system','original_game_logs_model'));
        require_once dirname(__FILE__) . '/../../../admin/application/libraries/phpexcel/PHPExcel.php';

        $game_logs_path = $this->getSystemInfo('km_game_records_path');
        $directory = $game_logs_path;
        $km_game_logs_excel = array_diff(scandir($directory), array('..', '.'));

        $header = [
            'A'=>'id',
            'B'=>'gametype',
            'C'=>'comm',
            'D'=>'txtime',
            'E'=>'bizdate',
            'F'=>'winamt',
            'G'=>'gameinfo',
            'H'=>'betamt',
            'I'=>'updatetime',
            'J'=>'jackpotwinamt',
            'K'=>'turnover',
            'L'=>'userid',
            'M'=>'bettype',
            'N'=>'platform',
            'O'=>'txstatus',
            'P'=>'jackpotbetamt',
            'Q'=>'createtime',
            'R'=>'platformtxid',
            'S'=>'realbetamt',
            'T'=>'gamecode',
            'U'=>'currency',
            'V'=>'transid',
            'W'=>'realwinamt',
            'X'=>'roundid',
            'Y'=>'result_amount',
            'Z'=>'response_result_id',
            'AA'=>'external_uniqueid',
            'AB'=>'created_at',
            'AC'=>'updated_at',
            'AD'=>'md5_sum'
        ];

        $count = 0;
        $excel_data = [];
        if(!empty($km_game_logs_excel)){
            foreach ($km_game_logs_excel as $file_name) {

                $file = explode(".", $file_name);
                $obj_php_excel = PHPExcel_IOFactory::load($directory . "/" . $file_name);
                $cell_collection = $obj_php_excel->getActiveSheet()->getCellCollection();

                foreach ($cell_collection as $cell) {
                    ini_set('memory_limit', '-1');
                    $column = $obj_php_excel->getActiveSheet()->getCell($cell)->getColumn();
                    $row = $obj_php_excel->getActiveSheet()->getCell($cell)->getRow();
                    $data_value = $obj_php_excel->getActiveSheet()->getCell($cell)->getValue();

                    if ($row == 1) continue;

                    $excel_data[$row][$header[$column]] = $data_value;
                }

            }
            if(!empty($excel_data)){
                foreach ($excel_data as $record) {
                    // print_r($excel_data);
                    $count++;
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
            }
            $result = array('data_count'=>$count);
            return array("success" => true,$result);
        }

    }
}
/*end of file*/