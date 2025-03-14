<?php
require_once dirname(__FILE__) . '/base_model.php';

class Player_latest_game_logs extends BaseModel {

	protected $tableName = 'player_latest_game_logs';
    public $data = [];

	function __construct() {
		parent::__construct();
	}

	/**
	 * overview : sync
	 *
	 * @param DateTime 	$from
	 * @param DateTime 	$to
	 * @param int		$playerId
	 * @return array
	 */
	public function sync(\DateTime $from, \DateTime $to, $playerId = null, $gamePlatfomrId= null) {

		$fromStr = $from->format('Y-m-d H:i:') . '00';
		$toStr = $to->format('Y-m-d H:i:') . '59';

		$playerIdSql = null;
		if (!empty($playerId)) {
			$playerIdSql = ' and player_id=' . $this->db->escape($playerId);
		}
		$gamePlatformSQL=null;
		if(!empty($gamePlatformId)){
			//for insert
			$gamePlatformSQL=' and game_platform_id='.intval($gamePlatformId);
		}

		// delete data from current datetime range
		$this->db->where('bet_at >=', $fromStr)
			->where('bet_at <=', $toStr);
		if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
		}
		if(!empty($gamePlatformId)){
			$this->db->where('game_platform_id', $gamePlatformId);
		}
		$this->db->delete($this->tableName);

        $enable_limit_player_latest_game_logs = $this->utils->getConfig('enable_limit_player_latest_game_logs');
        if(!$enable_limit_player_latest_game_logs){
            // delete old data ? 1 week old ?
            $weekDate = new DateTime();
            $weekDateStr =  $weekDate->modify('-1 week');
            $this->db->where('bet_at <=', $weekDateStr->format('Y-m-d H:i:s'));
    		$this->db->delete($this->tableName);
        }

		$now=$this->utils->getNowForMysql();

		$params=[$fromStr, $toStr];
		$t=time();
		$sql = <<<EOD
select
gl.player_id, gl.bet_at, gl.end_at, gl.bet_amount,gl.win_amount,gl.loss_amount,ifnull(gl.odds, "") odds, gl.game_platform_id,
gl.game_type_id, CONCAT(gl.game_platform_id,gl.external_uniqueid) external_uniqueid,gl.game_description_id, gl.`table` as round_id
from game_logs as gl
where flag = 1 and bet_at >= ?
and bet_at <= ?
{$playerIdSql}
{$gamePlatformSQL}
order by bet_at asc
EOD;
		$rows=$this->runRawSelectSQLArray($sql, $params);
		$this->utils->info_log('get rows from game_logs', count($rows), $params, 'cost', (time()-$t));

		$t=time();
		$cnt=0;
		$limit=500;
		$success=$this->runBatchInsertWithLimit($this->db, $this->tableName, $rows, $limit, $cnt);
		unset($rows);
		$this->utils->info_log('insert into player_latest_game_logs', $cnt, $params, 'cost', (time()-$t));
        if($enable_limit_player_latest_game_logs){
            $this->deleteAndLimitTable();
        }

        $this->data['insert_count'] = $cnt;

		return $success;

	}

    private function generateInsertForPlayerLatestGamelogs($originalRows){
        $uniqueidValues=array_column($originalRows, 'external_uniqueid');
        $afterUniqueArr=array_unique($uniqueidValues);
        $limit=500;
        $arr = array_chunk($uniqueidValues, $limit);
        $existsRows = [];
        $insertRows = [];
        foreach ($arr as $data) {
            $data= $this->utils->convertArrayItemsToString($data);
            $this->db->select('external_uniqueid')
                ->from('player_latest_game_logs')->where_in('external_uniqueid', $data);
            $tmpRows = $this->runMultipleRowArray();
            $tmpRows=array_column($tmpRows, 'external_uniqueid');
            if(!empty($tmpRows)){
                $existsRows=array_merge($existsRows, $tmpRows);
            }
            unset($tmpRows);
        }

        if(!empty($originalRows)){
            foreach ($originalRows as $key => $originalRow) {
                if(!in_array($originalRow['external_uniqueid'], $existsRows)){
                    $insertRows[] = $originalRow;
                }
            }
        }
        return $insertRows;
    }

    private function deleteAndLimitTable(){
        $defaul_limit = 1000;
        $limit_config = $this->utils->getConfig('limit_player_latest_game_logs');
        $limit = !empty($limit_config) ? $limit_config : $defaul_limit;
        // $query = $this->db->get('player_latest_game_logs');
        // $total_count = $query->num_rows();
        $total_count = $this->db->count_all('player_latest_game_logs');
        if($total_count > $limit){
            $limit_delete = $total_count - $limit;

            $this->db->order_by('id');
            $this->db->limit($limit_delete);
            $this->db->where("player_id IS NOT NULL");
            $this->db->delete($this->tableName);
            $this->utils->info_log('running deleteAndLimitTable', $limit_delete, 'qry', $this->db->last_query());
        }
    }

    public function get_latest_bets( $games = array() // #1
                                    , &$isCached // #2
                                    , $forceRefresh = false // #3
                                    , $cacheOnly=false // #4
                                    , $player_username = null // #5
                                    , $use_start_date_today = null // #6
                                    , $use_limit = 20 // #7
                                    , $ttl = null // #8
    ) { // OGP-24778

        $cache_key='latest-bets';
        $hash_in_cache_key = '';

        if(!empty($games)) {
            $game_code_cache_keys = implode("-",array_map(function($a) {return implode("-",$a);},$games));

            // $cache_key .= "-" . md5($game_code_cache_keys);
            $hash_in_cache_key .= $game_code_cache_keys;
        }
        if( ! is_null($player_username) ){
            // $cache_key .= "-";
            // $cache_key .= $player_username;
            $hash_in_cache_key .= $player_username;
        }
        if( ! is_null($use_start_date_today) ){
            // $cache_key .= "-";
            // $cache_key .= $use_start_date_today_username;
            $hash_in_cache_key .= $use_start_date_today;
        }
        if(!empty($hash_in_cache_key)) {
            $cache_key .= "-" . md5($hash_in_cache_key);
        }

        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if($forceRefresh){
			$cachedResult = null;
		}
        if($cacheOnly || (!empty($cachedResult) && !$forceRefresh)) {
			$isCached = true;
            return $cachedResult;
        }


        $res = [];
        $start_date_today = date('Y-m-d 00:00:00');
        $end_date_today = date('Y-m-d 23:59:59');

        // $use_start_date_today = $this->utils->getConfig('get_latest_game_records_use_start_date_today');
        if( ! empty($use_start_date_today) ){
            $start_date_today = $use_start_date_today;
        }

        if(!empty($games)) {
            foreach($games as $game_platform_id => $game_codes) {
                foreach($game_codes as $game_code) {
                        $this->db->from("{$this->tableName} AS gl")
                        ->join('player as p', 'gl.player_id = p.playerId', 'left')
                        ->join('game_description as gd', 'gl.game_platform_id = gd.game_platform_id and gl.game_description_id = gd.id', 'left')
                        ->where("gl.bet_amount !=",0)
                        ->where("gl.bet_at >=",$start_date_today)
                        ->where("gl.bet_at <=",$end_date_today)
                        ->where("gl.game_platform_id =", $game_platform_id)
                        ->where("gd.game_code =", $game_code)
                        ->select([
                            'p.username as player_username',
                            'gd.english_name game_name' ,
                            'gd.id game_description_id',
                            'gl.bet_at as betting_datetime',
                            'gl.bet_amount'
                        ])
                        ->limit($use_limit)
                        ->order_by('gl.bet_at DESC')
                    ;
                    if( ! is_null($player_username) ){
                        $this->db->where("p.username =", $player_username);
                    }


                    $query = $this->runMultipleRowArray();
                    if(!empty($query)){
                        $res = array_merge($res, $query);
                    }

                }
            }
        }

        $this->utils->debug_log(__METHOD__, 'OGP-27441.143.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'OGP-27441.143.res', $res);
        if( is_null($ttl) ){
            $ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
        }
        $this->utils->saveJsonToCache($cache_key, $res, $ttl);

        return $res;
    }

    public function get_latest_bets_by_game_type($game_type = NULL, &$isCached, $forceRefresh = false, $cacheOnly=false) { // OGP-26415
        $cache_key ='latest-bets-by-game-type';
        $cache_key .= "-" . md5($game_type);

        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if($forceRefresh){
			$cachedResult = null;
		}
        if($cacheOnly || (!empty($cachedResult) && !$forceRefresh)) {
			$isCached = true;
            return $cachedResult;
        }

        $res = [];
        $table = "player_latest_game_logs";
        $start_date_today = date('Y-m-d 00:00:00');
        $end_date_today = date('Y-m-d 23:59:59');

        if($game_type!=NULL){
            $game_types_arr = $this->get_game_types($game_type);
            $game_types = "";

            foreach ($game_types_arr as $key => $val) {
                $game_types .= $val['id'].",";
            }

            $game_types =  rtrim($game_types,',');

            $this->db->from("{$table} AS gl")
            ->join('player as p', 'gl.player_id = p.playerId', 'left')
            ->join('game_description as gd', 'gl.game_description_id = gd.id', 'left')
            ->where("gl.bet_amount !=",0)
            ->where("gl.win_amount !=",0)
            ->where("gl.bet_at >=",$start_date_today)
            ->where("gl.bet_at <=",$end_date_today)
            ->where("gd.game_type_id IN ({$game_types})")
            ->select([
                'p.username as player_username',
                'gd.english_name game_name',
                'TIME_FORMAT(gl.bet_at, "%H:%i") as betting_time',
                '(gl.win_amount/gl.bet_amount) as the_odds',
                'gl.bet_amount as bet_amount',
                'gl.win_amount as bonus_amount'
            ])
            ->limit(10)
            ->order_by('gl.bet_at DESC');
        }else{

            $this->db->from("{$table} AS gl")
            ->join('player as p', 'gl.player_id = p.playerId', 'left')
            ->join('game_description as gd', 'gl.game_description_id = gd.id', 'left')
            ->where("gl.bet_amount !=",0)
            ->where("gl.win_amount !=",0)
            ->where("gl.bet_at >=",$start_date_today)
            ->where("gl.bet_at <=",$end_date_today)
            ->select([
                'p.username as player_username',
                'gd.english_name game_name',
                'TIME_FORMAT(gl.bet_at, "%H:%i") as betting_time',
                '(gl.win_amount/gl.bet_amount) as the_odds',
                'gl.bet_amount as bet_amount',
                'gl.win_amount as bonus_amount'
            ])
            ->limit(10)
            ->order_by('gl.bet_at DESC');
        }

		$res = $this->runMultipleRowArray();

        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'res', $res);

        $ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
        $this->utils->saveJsonToCache($cache_key, $res, $ttl);

        return $res;
    }

    public function get_latest_bets_by_player_and_game_type($game_type = null, #1
                                                            $player_username = null, #2
                                                            $refresh = false,  #3
                                                            $limit = 10, #4
                                                            $offset = 0, #5
                                                            $use_start_date_today = null, #6
                                                            $ttl = null //7 default as sync_latest_game_records_cache_ttl of Config
                                                            ) {
		try {
			$cache_key='games-by-pgt';
        	$hash_in_cache_key = '';

			if($game_type!=NULL){
                $game_type_in_cache_key = '';
                if( is_array($game_type) ){
                    $game_type_in_cache_key = implode(',', $game_type);
                }else if( is_string($game_type) ){
                    $game_type_in_cache_key = $game_type;
                }

                $hash_in_cache_key .= $game_type_in_cache_key;
            }

			if( ! is_null($player_username) ){
				$hash_in_cache_key .= $player_username;
			}

            if( ! is_null($limit) ){
				$hash_in_cache_key .= $limit;
			}

			if( ! is_null($offset) ){
				$hash_in_cache_key .= $offset;
			}

            if( ! is_null($use_start_date_today) ){
				$hash_in_cache_key .= $use_start_date_today;
			}

			if(!empty($hash_in_cache_key)) {
				$cache_key .= "-" . md5($hash_in_cache_key);
			}

        	$cachedResult = $this->utils->getJsonFromCache($cache_key);

			$this->utils->debug_log(__METHOD__, 'cachedResult', $cachedResult);

			if($refresh){
				$cachedResult = null;
			}

			if((!$refresh)) {
				if(!empty($cachedResult['result'])){
					$cachedResult['mesg'] = "Latest bets of player ".$player_username." retrieved successfully from cached.";
					$retval = $cachedResult;
					return $retval;
				}
			}

            $table = "player_latest_game_logs";
            $start_date_today = date('Y-m-d 00:00:00');
            $end_date_today = date('Y-m-d 23:59:59');

            if( ! empty($use_start_date_today) ){
                $start_date_today = $use_start_date_today;
            }

            if (empty($limit)) { $limit = 10; }
            if (empty($offset)) { $offset = 0; }

            if($game_type!=NULL){
                $game_types_arr = $this->get_game_types($game_type);
                $game_types = "";

                foreach ($game_types_arr as $key => $val) {
                    $game_types .= $val['id'].",";
                }

                $game_types =  rtrim($game_types,',');

                if( ! empty($game_types) ){ // for not found
                    $this->db->where("gd.game_type_id IN ({$game_types})");
                }

                $this->db->from("{$table} AS gl")
                    ->join('player as p', 'gl.player_id = p.playerId', 'left')
                    ->join('game_description as gd', 'gl.game_description_id = gd.id', 'left')
                    ->where("gl.bet_amount != 0 AND p.username = '{$player_username}' ")
                    ->where("gl.bet_at >=",$start_date_today)
                    ->where("gl.bet_at <=",$end_date_today)
                    ->select([
                        'gd.english_name game_name',
                        'gd.id game_description_id',
                        'TIME_FORMAT(gl.bet_at, "%H:%i") as betting_time',
                        'gl.bet_at as betting_datetime',
                        '(gl.win_amount/gl.bet_amount) as the_odds',
                        'gl.bet_amount as bet_amount',
                        'gl.win_amount as bonus_amount'
                    ])
                    ->limit($limit)
                    ->offset($offset)
                    ->order_by('gl.bet_at DESC');
            }else{

                $this->db->from("{$table} AS gl")
                    ->join('player as p', 'gl.player_id = p.playerId', 'left')
                    ->join('game_description as gd', 'gl.game_description_id = gd.id', 'left')
                    ->where("gl.bet_amount != 0 AND p.username = '{$player_username}' ")
                    ->where("gl.bet_at >=",$start_date_today)
                    ->where("gl.bet_at <=",$end_date_today)
                    ->select([
                        'gd.english_name game_name',
                        'gd.id game_description_id',
                        'TIME_FORMAT(gl.bet_at, "%H:%i") as betting_time',
                        'gl.bet_at as betting_datetime',
                        '(gl.win_amount/gl.bet_amount) as the_odds',
                        'gl.bet_amount as bet_amount',
                        'gl.win_amount as bonus_amount'
                    ])
                    ->limit($limit)
                    ->offset($offset)
                    ->order_by('gl.bet_at DESC');
            }

            $res = $this->runMultipleRowArray();

            $this->utils->debug_log(__METHOD__, '309.sql', $this->db->last_query());
            $this->utils->debug_log(__METHOD__, '310.res', $res);

            $results = [];
            if(!empty($res)){
                foreach($res as $key => $rs) {
                    $results[$key]["game_name"] = $rs["game_name"];
                    $results[$key]["bet_time"] = $rs["betting_time"];
                    $results[$key]["bet_amount"] = "RS ".number_format($rs["bet_amount"], 2);
                    $results[$key]["the_odds"] = ($rs["the_odds"] == 0 || $rs["the_odds"] == null) ? "-" : $rs["the_odds"];
                    $results[$key]["bonus_amount"] = "RS ".number_format($rs["bonus_amount"], 2);
                }
            }

			$retval = [
				'success'	=> true ,
				'code'		=> 0 ,
				'mesg'		=> "Latest bets of player ".$player_username." retrieved successfully." ,
				'result'	=> $results
			];

			$ttl = $this->utils->getConfig('player_center_list_games_cache_ttl');
        	$this->utils->saveJsonToCache($cache_key, $retval, $ttl);
		}
		catch (Exception $ex) {
			$retval = [
				'success'	=> false ,
				'code'		=> $ex->getCode() ,
				'mesg'		=> $ex->getMessage() ,
				'result'	=> null
			];
		}
		finally {
			return $retval;
		}
	} // End function new_fgapi_list_games_by_platform_gametype()

    /**
     * Get the game_type.id and "game_type.game_platform_id list
     *
     *
     * @param string|array $type The game_type.game_type_code list OT single value of "game_type.game_type_code".
     *
     * @return array The rows thats includes the fields, "game_type.id" and "game_type.game_platform_id".
     */
    public function get_game_types( $type=NULL){

        $res = [];
        if( empty($type) ){
        }else if( is_array($type) ){
            $_type_in = '"'. implode('","', $type). '"';
            $this->db->where("gt.game_type_code IN ( {$_type_in} ) ");
        }else if( is_string($type) ){
            $this->db->where("gt.game_type_code =  '{$type}'");
        }

        $this->db->from("game_type as gt")
        ->select([
            'gt.id',
            'gt.game_platform_id'
        ])
        ->order_by('gt.id DESC');

        $res = $this->runMultipleRowArray();

        $this->utils->debug_log(__METHOD__, '338.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, '339.res', $res);

        return $res;
    }


    public function get_latest_bets_with_sqls( $games = array() // #1
                                                , &$isCached // #2
                                                , $forceRefresh = false // #3
                                                , $cacheOnly=false // #4
                                                , $player_username = null // #5
                                                , $use_start_date_today = null // #6
                                                , $ttl = null // #7
    ) {
        $cache_key='lb-player-game-with-sql';
        $hash_in_cache_key = '';

        if(!empty($games)) {
            $game_code_cache_keys = implode("-",array_map(function($a) {return implode("-",$a);},$games));

            // $cache_key .= "-" . md5($game_code_cache_keys);
            $hash_in_cache_key .= $game_code_cache_keys;
        }
        if( ! is_null($player_username) ){
            // $cache_key .= "-";
            // $cache_key .= $player_username;
            $hash_in_cache_key .= $player_username;
        }
        if( ! is_null($use_start_date_today) ){
            // $cache_key .= "-";
            // $cache_key .= $use_start_date_today_username;
            $hash_in_cache_key .= $use_start_date_today;
        }
        if(!empty($hash_in_cache_key)) {
            $cache_key .= "-" . md5($hash_in_cache_key);
        }

        $cachedResult = $this->utils->getJsonFromCache($cache_key);
		if($forceRefresh){
			$cachedResult = null;
		}
        if($cacheOnly || (!empty($cachedResult) && !$forceRefresh)) {
			$isCached = true;
            return $cachedResult;
        }


        $res = [];
        $start_date_today = date('Y-m-d 00:00:00');
        $end_date_today = date('Y-m-d 23:59:59');

        // $use_start_date_today = $this->utils->getConfig('get_latest_game_records_use_start_date_today');
        if( ! empty($use_start_date_today) ){
            $start_date_today = $use_start_date_today;
        }


        $rows = $this->get_latest_bet_at_list(  $player_username // #1
                                                , $isCached // #2
                                                , $forceRefresh // #3
                                                , $cacheOnly // #4
                                                , $use_start_date_today // #5
                                                , $ttl ); // #6

        $_game_description_id_list = ' 0 ';
        $_bet_at_list = ' 0 ';
        if(!empty($rows)) {
            $_game_description_id_list = ' ';
            $_game_description_id_list .= implode(', ', array_column($rows, 'game_description_id') );
            $_game_description_id_list.= ' ';

            $_bet_at_list = ' "';
            $_bet_at_list .= implode('", "', array_column($rows, 'latest_bet_at') );
            $_bet_at_list.= '" ';
        }

        if(!empty($games)) {



            $sql = <<<SQL
SELECT p.username as player_username
, gd.english_name game_name
, gd.id game_description_id
, gl.bet_at as betting_datetime
, gl.bet_amount
FROM player_latest_game_logs as gl
LEFT JOIN player as p ON gl.player_id = p.playerId
LEFT JOIN game_description as gd ON gl.game_description_id = gd.id
WHERE p.username = "%s"
    AND gl.game_platform_id = %s
    AND gd.game_code = "%s"
    AND gl.game_description_id IN ( %s )
    AND gl.bet_at IN ( %s )
    AND gl.bet_amount != 0
;
SQL;
// $player_username
// $game_platform_id
// $game_code
// $_game_description_id_list
// $_bet_at_list

            foreach($games as $game_platform_id => $game_codes) {
                foreach($game_codes as $game_code) {
                    $_sql = sprintf($sql, $player_username
                                        , $game_platform_id
                                        , $game_code
                                        , $_game_description_id_list
                                        , $_bet_at_list );
                    $query = $this->db->query($_sql,[ ]);
                    $_rows = $this->getMultipleRowArray($query);

                    if(!empty($_rows)){
                        $res = array_merge($res, $_rows);
                    }

                    //// TODO: SQL with get_latest_bet_at_list()
                } // EOF foreach($game_codes as $game_code) {
            } // EOF foreach($games as $game_platform_id => $game_codes) {
        }

        $this->utils->debug_log(__METHOD__, 'OGP-27441.527.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'OGP-27441.528.res', $res);
        if( is_null($ttl) ){
            $ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
        }
        $this->utils->saveJsonToCache($cache_key, $res, $ttl);

        return $res;
    } // EOF get_latest_bets_with_sqls


    public function get_latest_bet_at_list( $player_username // #1
                                            , &$isCached // #2
                                            , $forceRefresh = false // #3
                                            , $cacheOnly=false // #4
                                            , $use_start_date_today = null // #5
                                            , $ttl = null // #6, default as sync_latest_game_records_cache_ttl of Config
    ) {
        $cache_key ='lb-get_latest_bet_at_list';

        $hash_in_cache_key = '';
        $hash_in_cache_key .= $player_username;
        //
        // if($game_type!=NULL){
        //     $game_type_in_cache_key = '';
        //     if( is_array($game_type) ){
        //         $game_type_in_cache_key = implode(',', $game_type);
        //     }else if( is_string($game_type) ){
        //         $game_type_in_cache_key = $game_type;
        //     }
        //     $hash_in_cache_key .= $game_type_in_cache_key;
        // }
        //
        if($use_start_date_today!=NULL){
            $hash_in_cache_key .= $use_start_date_today;
        }
        //
        $cache_key .= "-" . md5($hash_in_cache_key);

        $cachedResult = $this->utils->getJsonFromCache($cache_key);

        if($forceRefresh){
			$cachedResult = null;
		}

        if($cacheOnly || (!empty($cachedResult) && !$forceRefresh)) {
			$isCached= true;
            return $cachedResult;
        }



        $res = [];

        $start_date_today = date('Y-m-d 00:00:00');
        $end_date_today = date('Y-m-d 23:59:59');

        if( ! empty($use_start_date_today) ){
            $start_date_today = $use_start_date_today;
        }



        $sql = <<<SQL
SELECT max(gl.bet_at) as latest_bet_at
, game_description_id
, player_id
FROM player_latest_game_logs as gl
LEFT JOIN player as p ON gl.player_id = p.playerId
WHERE p.username = ?
    AND gl.bet_at BETWEEN ? AND ?
    AND gl.bet_amount !=0
GROUP BY game_description_id
;
SQL;
// $player_username
// $start_date_today
// $end_date_today
        $query = $this->db->query($sql,[ $player_username
                                        , $start_date_today
                                        , $end_date_today
                                    ]);

        $rows = $this->getMultipleRowArray($query);
        $this->utils->debug_log(__METHOD__, '511.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, '512.rows', $rows);

        return $rows;
    } // EOF get_latest_bet_at_list

    public function get_latest_bets_by_player_and_game_type_with_sqls( $game_type // #1
                                                            , $player_username // #2
                                                            , &$isCached // #3
                                                            , $forceRefresh = false // #4
                                                            , $cacheOnly=false // #5
                                                            , $use_start_date_today = null // #6
                                                            , $ttl = null // #7, default as sync_latest_game_records_cache_ttl of Config
    ) {

        $cache_key ='lb-player-game-type-with-sql';

        $hash_in_cache_key = '';
        $hash_in_cache_key .= $player_username;
        //
        if($game_type!=NULL){
            $game_type_in_cache_key = '';
            if( is_array($game_type) ){
                $game_type_in_cache_key = implode(',', $game_type);
            }else if( is_string($game_type) ){
                $game_type_in_cache_key = $game_type;
            }
            $hash_in_cache_key .= $game_type_in_cache_key;
        }
        //
        if($use_start_date_today!=NULL){
            $hash_in_cache_key .= $use_start_date_today;
        }
        //
        $cache_key .= "-" . md5($hash_in_cache_key);

        $cachedResult = $this->utils->getJsonFromCache($cache_key);

		if($forceRefresh){
			$cachedResult = null;
		}

        if($cacheOnly || (!empty($cachedResult) && !$forceRefresh)) {
			$isCached= true;
            return $cachedResult;
        }

        $res = [];

        $table = "player_latest_game_logs";
        $start_date_today = date('Y-m-d 00:00:00');
        $end_date_today = date('Y-m-d 23:59:59');

        if( ! empty($use_start_date_today) ){
            $start_date_today = $use_start_date_today;
        }

        $game_types = "";
        if($game_type!=NULL){
            $game_types_arr = $this->get_game_types($game_type);
            foreach ($game_types_arr as $key => $val) {
                $game_types .= $val['id'].",";
            }

            $game_types =  rtrim($game_types,',');
        }

        $rows = $this->get_latest_bet_at_list($player_username // #1
        , $isCached // #2
        , $forceRefresh // #3
        , $cacheOnly // #4
        , $use_start_date_today // #5
        , $ttl );


        if( ! empty($rows) ){
            $sql = <<<SQL
SELECT gl.id gl_id
    , gd.english_name game_name
    , gd.id game_description_id
    , TIME_FORMAT(gl.bet_at, "%s") as betting_time
    , gl.bet_at as betting_datetime
    , (gl.win_amount/gl.bet_amount) as the_odds
    , gl.bet_amount as bet_amount
    , gl.win_amount as bonus_amount
FROM player_latest_game_logs as gl
LEFT JOIN player as p ON gl.player_id = p.playerId
LEFT JOIN game_description as gd ON gl.game_description_id = gd.id
WHERE p.username = "%s"
    AND gd.game_type_id IN ({$game_types})
    AND gl.game_description_id IN ( %s )
    AND gl.bet_at IN ( %s );
SQL;
            // "%H:%i" via sprintf
            // $player_username via sprintf
            // game_description_id_list via sprintf
            // bet_at_list via sprintf

            $_game_description_id_list = ' 0 ';
            $_bet_at_list = ' 0 ';
            if( ! empty($rows) ){
                $_game_description_id_list = ' ';
                $_game_description_id_list .= implode(', ', array_column($rows, 'game_description_id') );
                $_game_description_id_list.= ' ';

                $_bet_at_list = ' "';
                $_bet_at_list .= implode('", "', array_column($rows, 'latest_bet_at') );
                $_bet_at_list.= '" ';
            }


            $_sql = sprintf($sql, '%H:%i', $player_username, $_game_description_id_list, $_bet_at_list);
            $query = $this->db->query($_sql,[ ]);
            $res = $this->getMultipleRowArray($query);
        } // EOF if( ! empty($rows) ){

        $this->utils->debug_log(__METHOD__, '770.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, '771.res', $res);

        if( is_null($ttl) ){
            $ttl = $this->utils->getConfig('sync_latest_game_records_cache_ttl');
        }

        $this->utils->saveJsonToCache($cache_key, $res, $ttl);

        return $res;
    } // EOF get_latest_bets_by_player_and_game_type_with_sqls

    public function get_player_latest_game_logs($limit = 10, $sort_key = 'payoutTime', $sort_type = 'desc'){
        $is_enabled_fake_data = $this->utils->getConfig('enable_latest_bets_fake_data');
        $fake_data_config = $this->utils->getConfig('latest_bets_fake_data');
        $number_of_fake_data = isset($fake_data_config['number_of_fake_data']) ? $fake_data_config['number_of_fake_data'] : 10;
        $fake_username_prefix = isset($fake_data_config['fake_username_prefix']) ? $fake_data_config['fake_username_prefix'] : '';
        $random_game_tag_code = isset($fake_data_config['random_game_tag_code']) ? $fake_data_config['random_game_tag_code'] : 'top';
        $min_bet_amount = isset($fake_data_config['range']['min_bet_amount']) ? $fake_data_config['range']['min_bet_amount'] : 0;

        $this->db->from("player_latest_game_logs as gl");
        $this->db->join('player as p', 'gl.player_id = p.playerId', 'left');
        $this->db->join('game_description as gd', 'gl.game_description_id = gd.id', 'left');
        $this->db->join('game_type as gt', 'gl.game_type_id = gt.id', 'left');
        $this->db->select([
            'p.username as playerUsername',
            'CONCAT(gl.game_platform_id,"-",gl.external_uniqueid) as uniqueId',
            'CONCAT(gl.game_platform_id,"-", gd.external_game_id) as virtualGameId',
            'gd.english_name as gameName',
            'gl.bet_at as betTime',
            'gl.end_at as payoutTime',
            'SUM(gl.bet_amount) as realBetAmount',
            'SUM(gl.win_amount) as payoutAmount',
            'SUM(gl.win_amount) - SUM(gl.bet_amount) as resultAmount',
            '(gl.win_amount/gl.bet_amount) as multiplier'
        ]);

        $this->db->limit($limit);
        $this->db->group_by(['gl.player_id', 'gl.round_id']);

        if ($is_enabled_fake_data && !empty($min_bet_amount)) {
            $this->db->having("SUM(gl.bet_amount) >= {$min_bet_amount}");
        }

        $column_names = [
            'realBetAmount',
            'payoutAmount',
            'betTime',
            'payoutTime'
        ];

        if (empty($sort_key) || !in_array($sort_key, $column_names)) {
            $sort_key = 'payoutTime';
        }

        $this->db->order_by("{$sort_key} {$sort_type}");
        $res = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'glbv2.sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'glbv2.res', $res);

        if ($is_enabled_fake_data) {
            if (!empty($res)) {
                $number_of_fake_data -= count($res);
            }

            if ($number_of_fake_data > 0) {
                $res = array_merge($res, $this->generateFakeData($number_of_fake_data, $fake_username_prefix, $random_game_tag_code));
                // override order by
                if ($sort_type == 'desc') {
                    $this->utils->usortDescByArraykeyValues($res, $sort_key);
                } else {
                    $this->utils->usortAscByArraykeyValues($res, $sort_key);
                }
            }
        }

        return $res;
    }

    public function getPlayerLatestGameLogsCustom($fields, $where, $order_by = 'bet_at', $order_type = 'desc', $limit = 10, $offset = 0) {
        $selected_fields = implode(',', $fields);

        $this->db->select($selected_fields)
            ->from('player_latest_game_logs AS plgl')
            ->join("player AS p", "p.playerId = plgl.player_id", 'left')
            ->join('game_type AS gt', 'plgl.game_type_id = gt.id', 'left')
            ->join("game_description AS gd", "plgl.game_description_id = gd.id", 'left')
            ->where($where);

        if (!empty($order_by)) {
            $this->db->order_by($order_by, $order_type);
        }

        if (!empty($limit)) {
            $this->db->limit($limit, $offset);
        }

        return $this->runMultipleRowArray();
    }

    public function getPlayersTotalBetByDate($date, $order_by = 'total_bet_amount', $order_type = 'desc', $limit = 10, $offset = 0) {
        $this->db->select('p.username AS player_username')
            ->select('count(plgl.player_id) AS number_of_bet')
            ->select_sum('plgl.bet_amount', 'total_bet_amount')
            ->from('player_latest_game_logs AS plgl')
            ->join("player AS p", "plgl.player_id = p.playerId", 'left')
			->where("plgl.bet_at BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'")
            ->where('plgl.bet_amount !=', 0)
            ->group_by('plgl.player_id');

        if (!empty($order_by)) {
            $this->db->order_by($order_by, $order_type);
        }

        if (!empty($limit)) {
            $this->db->limit($limit, $offset);
        }

        return $this->runMultipleRowArray();
    }

    /**
     * Get players latest game logs
     * OGP-28514
     *
     * @return  JSON    Standard JSON return object
     */
    public function getPlayersLatestGameLogs(
        $date,
        $player_username = null,
        $game_platform_id = null,
        $game_type = null,
        $game_code = null,
        $order_by = 'plgl.bet_at',
        $order_type = 'desc',
        $limit = 50,
        $offset = 0,
        $show_win_only = 0,
        $get_total = 0,
        $date_start = null,
        $date_end = null,
        $get_players_game_logs_default_by = 'latest'
    ) {

        $fields = [
            'p.username AS player_username',
            'plgl.game_platform_id',
            'sum(plgl.bet_amount) AS bet_amount',
            'sum(plgl.win_amount) AS win_amount',
            '(sum(plgl.win_amount) / sum(plgl.bet_amount)) AS multiplier',
            'plgl.bet_at AS bet_datetime',
            'plgl.end_at AS settle_datetime',
            'gt.game_type_code AS game_type',
            'gd.english_name AS game_name',
            'gd.game_code',
        ];

        if ($get_total) {
            $fields = [
                'p.username AS player_username',
                'sum(plgl.bet_amount) AS total_bet_amount',
                'sum(plgl.win_amount) AS total_win_amount',
            ];
        }

        $selected_fields = implode(',', $fields);

        $this->db->select($selected_fields)
            ->from('player_latest_game_logs AS plgl')
            ->join('player AS p', 'p.playerId = plgl.player_id', 'left')
            ->join('game_type AS gt', 'plgl.game_type_id = gt.id', 'left')
            ->join("game_description AS gd", "plgl.game_description_id = gd.id", 'left');

        /* if ($date) {
            $date = date('Y-m-d', strtotime($this->utils->getNowForMysql()));
        } else {
            $date = date('Y-m-d', strtotime($date));
        } */

        // $get_players_game_logs_default_by = $this->utils->getConfig('get_players_game_logs_default_by');

        switch ($get_players_game_logs_default_by) {
            case 'latest':
                # latest
                break;
            case 'today':
                $date = date('Y-m-d', strtotime($this->utils->getNowForMysql()));
                $date_start = "{$date} 00:00:00";
                $date_end = "{$date} 23:59:59";

                if (!empty($date)) {
                    $this->db->where("plgl.bet_at BETWEEN '{$date_start}' AND '{$date_end}'");
                }
                break;
            case 'date':
                $date = date('Y-m-d', strtotime($date));

                if (!empty($date)) {
                    $date_start = "{$date} 00:00:00";
                    $date_end = "{$date} 23:59:59";

                    $this->db->where("plgl.bet_at BETWEEN '{$date_start}' AND '{$date_end}'");
                }
                break;
            case 'date_range':
                if (!empty($date_start) && !empty($date_end)) {
                    $this->db->where("plgl.bet_at BETWEEN '{$date_start}' AND '{$date_end}'");
                } else {
                    # latest
                }
                break;
            default:
                # latest
                break;
        }

        if (!empty($player_username)) {
            $this->db->where('p.username', $player_username);
        }

        if (!empty($game_platform_id)) {
            $this->db->where('plgl.game_platform_id', $game_platform_id);
        }

        if (!empty($game_type)) {
            $this->db->where('gt.game_type_code', $game_type);
        }

        if (!empty($game_code)) {
            $this->db->where('gd.game_code', $game_code);
        }

        /* if (!$get_total) {
            $this->db->from('game_logs AS plgl');
            $this->db->group_by('plgl.`table`');
        } else {
            $this->db->from('player_latest_game_logs AS plgl');
            $this->db->group_by('plgl.player_id');
        } */

        if ($get_total) {
            $this->db->group_by('plgl.player_id');
        } else {
            $this->db->group_by('plgl.round_id');
        }

        if ($show_win_only) {
            $having = !$get_total ? 'win_amount > 0' : 'total_win_amount > 0';
            $this->db->having($having);
        }

        if (!empty($order_by)) {
            $this->db->order_by($order_by, $order_type);
        }

        if (!empty($limit)) {
            $this->db->limit($limit, $offset);
        }

        return $this->runMultipleRowArray();
    }

    public function randomGame($tag_code = null) {
        $this->db->select('gd.game_platform_id, gd.external_game_id, gd.english_name');
        $this->db->from('game_description as gd');
        $this->db->join('game_tag_list as gtl', 'gd.id = gtl.game_description_id', 'left');
        $this->db->join('game_tags as gt', 'gtl.tag_id = gt.id', 'left');
        $this->db->where("gd.external_game_id != 'unknown'");

        if (!empty($tag_code)) {
            $this->db->where(['gt.tag_code' => $tag_code]);
        }

        $this->db->order_by('rand()');

        return $this->runOneRowArray();
    }

    public function generateFakeData($number_of_fake_data = 10, $fake_username_prefix = '', $game_tag_code = 'top', $multiplier = 2, $show_player = true) {
        $fake_data_config = $this->utils->getConfig('latest_bets_fake_data');
        $this->utils->debug_log(__METHOD__, 'config', 'latest_bets_fake_data', $fake_data_config);

        $fake_usernames = $fake_data_config['fake_usernames'];
        $currency_info = $this->utils->getCurrentCurrency();
        $fake_data = [];

        for ($i = 0; $i < $number_of_fake_data; $i++) {
            $random_fake_name = $this->utils->randomUsername($fake_username_prefix, $show_player, false, $fake_usernames);
            $random_game = $this->randomGame($game_tag_code);

            if (empty($random_game)) {
                // will get random game without game tag code
                $this->utils->debug_log(__METHOD__, "empty {$game_tag_code} random game, will get random game without game tag code");
                $random_game = $this->randomGame(null);
            }

            $bet_amount = rand($fake_data_config['range']['min_bet_amount'], $fake_data_config['range']['max_bet_amount']);
            $payout_amount = $bet_amount * $multiplier;
            $result_amount = $payout_amount - $bet_amount;

            $interval = rand(1, 60);
            $payout_interval = $interval + 1;

            $fake_data[$i] = [
                'playerUsername' => $random_fake_name,
                'uniqueId' => $this->getSecureId($this->tableName, 'external_uniqueid', true, 'fake-'),
                'virtualGameId' => $random_game['game_platform_id'] . '-' . $random_game['external_game_id'],
                'gameName' => $random_game['english_name'],
                'betTime' => $this->utils->modifyDateTime($this->utils->getNowForMysql(), "+{$interval} SECOND"),
                'payoutTime' => $this->utils->modifyDateTime($this->utils->getNowForMysql(), "+{$payout_interval} SECOND"),
                'realBetAmount' => $bet_amount,
                'payoutAmount' => $payout_amount,
                'resultAmount' => $result_amount,
                'multiplier' => $multiplier,
            ];

            if (!empty($currency_info['currency_code'])) {
                $fake_data[$i]['currency'] = $currency_info['currency_code'];
            }
        }

        return $fake_data;
    }
}

/////end of file///////