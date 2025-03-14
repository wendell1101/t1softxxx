<?php

trait t1t_comapi_module_player_game_log {

	/**
	 * Returns player's detailed game logs.
	 * Note: This is a administration-level API method, requires no player token.
	 * Not intended for directly use of players.
	 *
	 * @uses	string		POST:username		Effective username
	 * @uses	datetime	POST:time_from		Datetime of query start.  Defaults to begin of today.
	 * @uses	datetime	POST:time_to		Datetime of query end.  Defaults to end of today.
	 * @uses	int			POST:timezone		Timezone.  Defaults to system timezone (+8 for CST)
	 * @uses	int			POST:limit			Limit, for paging use
	 * @uses	int			POST:offset			Offset, for paging use
	 * @uses	string		POST:game_provider	Game provider, like AG, PT.  Caseless
	 * @uses	string		POST:game_code		Game code, caseless
	 * @uses	string		POST:flag			1 for game logs, 2 for transactions (subwallet transfers)
	 *
	 * @return	array 		Array of game log rows
	 */
	public function getPlayerGameLogs() {
		$api_key = $this->input->post('api_key');
	    if (!$this->__checkKey($api_key)) { return; }

	    try {
	    	$username		= $this->input->post('username'		, true);
	    	$raw_time_from	= $this->input->post('time_from'	, true);
			$raw_time_to	= $this->input->post('time_to'		, true);
			$timezone		= intval($this->input->post('timezone'	, true));
			$limit 			= intval($this->input->post('limit'		, true));
			$offset 		= intval($this->input->post('offset'	, true));
			$game_provider	= $this->input->post('game_provider'	, true);
			$game_code		= $this->input->post('game_code'		, true);
			$flag			= intval($this->input->post('flag'		, true));

			$request = [ 'api_key' => $api_key, 'username' => $username, 'time_from' => $raw_time_from, 'time_to' => $raw_time_to, 'timezone' => $timezone, 'limit' => $limit, 'offset' => $offset, 'flag' => $flag ];

			$this->utils->debug_log(__FUNCTION__, 'Request', $request);

			$player_id = $this->player_model->getPlayerIdByUsername($username);

			if (empty($player_id)) {
				throw new Exception('Invalid username', self::CODE_INVALID_USER);
			}

			// time_from, time_to
			$dt_from	= strtotime(empty($raw_time_from) ? 'today 00:00' : $raw_time_from);
			$dt_to		= strtotime(empty($raw_time_to) ? 'today 23:59:59' : $raw_time_to);

			// timezone correction
			$tz_default = intval(date('Z')) / 3600;
			if (!empty($timezone)) {
				$tz_offset = ($timezone - $tz_default) * 3600;
				$dt_from += $tz_offset;
				$dt_to += $tz_offset;
			}

			$time_from	= date('c', $dt_from);
			$time_to	= date('c', $dt_to);

			// limit, offset
			if (empty($limit)) { $limit = 30; }

			// flag
			if (!in_array($flag, [ 1, 2 ])) { $flag = 0; }

			// game_provider, game_code
			$game_provider	= preg_replace('/\W/u', '', $game_provider);
			$game_code		= preg_replace('/\W/u', '', $game_code);

			$res = $this->game_logs->getGameLogs_api($player_id, $time_from, $time_to, $flag, $game_provider, $game_code, $limit, $offset);

			$ret = [
			    'success'   => true,
			    'code'      => self::CODE_SUCCESS,
			    'mesg'      => "Player game logs retrieved successfully",
			    'result'    => $res
			];

			$this->utils->debug_log(__METHOD__, 'response', $res, $request);
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $request);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	}

	public function getLatestBets() {
		$res = "";
		$api_key = $this->input->post('api_key');
		$forceRefresh = $this->input->post('refresh');
	    if (!$this->__checkKey($api_key)) { return; }

		try {

			// $game_codes = array(
			// 	5923 => array(901,101)
			// ); // need to add to config

			$game_codes = array();

			if($this->utils->getConfig('latest_bets_game_code')) {
				$game_codes = $this->utils->getConfig('latest_bets_game_code');
			}
			$this->load->model(array('player_latest_game_logs'));
			$isCached = false;
			$cacheOnly=true;
			$res = $this->player_latest_game_logs->get_latest_bets($game_codes, $isCached, false, $cacheOnly);

			// replace the last 4 digits with asterisk

			$results = [];

			if(!empty($res)){
				shuffle($res);

				$res = array_slice($res, 0, 50);

				foreach($res as $key => $rs) {

					// $results[$key]["player_username"] = substr_replace($rs["player_username"], '****', -4);
					$results[$key]["player_username"] = substr_replace($rs["player_username"], '*****', 3);
					$results[$key]["game_name"] = $rs["game_name"];
					$results[$key]["bet_amount"] = $rs["bet_amount"];
				}
			}

			$ret = [
				'success'   => true,
				'code'      => self::CODE_SUCCESS,
				'mesg'      => "Latest bets retrieved successfully",
				'result'    => $results
			];

			if($isCached){
				$ret['mesg'] = "Latest bets retrieved from cache successfully";
			}

			$this->utils->debug_log(__METHOD__, 'response', $res);

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	}

	public function getLatestBetsByGameType() {

		$game_type = $this->input->post('game_type');
		$forceRefresh = (bool)$this->input->post('refresh');
		$res = "";
		$api_key = $this->input->post('api_key');
	    if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->model(array('player_latest_game_logs'));
			$isCached = false;
			$cacheOnly=true;
			$res = $this->player_latest_game_logs->get_latest_bets_by_game_type($game_type, $isCached, $forceRefresh, $cacheOnly);

			$results = [];

			if(!empty($res)){
				foreach($res as $key => $rs) {
					$results[$key]["player_username"] = substr_replace($rs["player_username"], '*****', 4);
					$results[$key]["game_name"] = $rs["game_name"];
					$results[$key]["bet_time"] = $rs["betting_time"];
					$results[$key]["bet_amount"] = "RS ".number_format($rs["bet_amount"], 2);
					$results[$key]["the_odds"] = ($rs["the_odds"] == 0 || $rs["the_odds"] == null) ? "-" : $rs["the_odds"];
					$results[$key]["bonus_amount"] = "RS ".number_format($rs["bonus_amount"], 2);
				}
			}

			$ret = [
				'success'   => true,
				'code'      => self::CODE_SUCCESS,
				'mesg'      => "Latest bets retrieved successfully",
				'result'    => $results
			];

			if($isCached){
				$ret['mesg'] = "Latest bets retrieved from cache successfully";
			}

			$this->utils->debug_log(__METHOD__, 'response', $res);

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	}

    public function getLatestBetsByPlayerAndGameType() {
        $api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	$debug = false;

    	try {
            $res = "";
		    $game_type = $this->input->post('game_type', 1);
		    $player_username = $this->input->post('player_username', '');
		    $refresh = $this->input->post('refresh', 1);

    		$limit			= intval($this->input->post('limit', 1));
    		$offset			= intval($this->input->post('offset', 1));
			$request 		= [ 'api_key' => $api_key, 'player_username' => $player_username, 'game_type' => $game_type, 'refresh' => $refresh, 'limit' => $limit, 'offset' => $offset];
    		
			// $this->comapi_log(__METHOD__, 'request', $request);

			$this->load->model(array('player_latest_game_logs'));
			
			$res = $this->player_latest_game_logs->get_latest_bets_by_player_and_game_type($game_type, $player_username, $refresh, $limit, $offset);

            
			if ($res['success'] != true) {
				throw new Exception($res['mesg'], $res['code']);
			}
            
	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> $res['mesg'] ,
	    		'result'	=> $res['result']
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
    }

    /**
     * Get players bet list by date
     * OGP-28526
     *
     * @return  JSON    Standard JSON return object
     */
    public function getPlayersBetListByDate() {
        $ret = [];
        $res = [];

        $api_key = $this->input->post('api_key');
        $off_api_key = $this->input->post('off_api_key');

        if (!$this->__checkKey($api_key) && !$off_api_key) {
            return; 
        }

        $ret = [];
        $res = [];
        $list = [];
        $msg = 'No Data!';
        $players_bet_list = null;

        try {
            $this->load->model(['player_latest_game_logs']);

            $date = $this->input->post('date');
            $order_by = $this->input->post('order_by');
            $order_type = $this->input->post('order_type');
            $limit = intval($this->input->post('limit'));
            $offset = intval($this->input->post('offset'));
            $show_player = intval($this->input->post('show_player'));
            $get_total_bet_amount = intval($this->input->post('get_total_bet_amount'));
            $delete_cache = intval($this->input->post('delete_cache'));

            if (!$date) {
                $date = date('Y-m-d', strtotime($this->utils->getNowForMysql()));
            }

            if (!$order_type) {
                $order_type = 'desc';
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $players_bet_list,
            ];

            $cache_key = __CLASS__ . "-" . __TRAIT__ . "-" .__FUNCTION__ . "-{$date}-{$order_by}-{$order_type}-{$limit}-{$offset}-{$show_player}-{$get_total_bet_amount}";
            $cached_result = $this->utils->getJsonFromCache($cache_key);

            if (!empty($cached_result)) {
                if ($delete_cache) {
                    $this->utils->deleteCache($cache_key);
                } else {
                    $ret = $cached_result;
                    $this->utils->debug_log(__FUNCTION__, '1', 'cache_key', $cache_key, 'ret', $ret);
                    return $ret;
                }
            } else {
                if ($get_total_bet_amount) {
                    switch ($order_by) {
                        case 'player_username':
                            $order_by = 'player_username';
                            break;
                        case 'number_of_bet':
                            $order_by = 'number_of_bet';
                            break;
                        case 'total_bet_amount':
                            $order_by = 'total_bet_amount';
                            break;
                        default:
                            $order_by = 'total_bet_amount';
                        break;
                    }
    
                    $players_bet_list = $this->player_latest_game_logs->getPlayersTotalBetByDate($date, $order_by, $order_type, $limit, $offset);
                } else {
                    $fields = [
                        'p.username AS player_username',
                        'plgl.game_platform_id',
                        'gd.english_name AS game_name',
                        'gd.game_code',
                        'plgl.bet_amount',
                        'plgl.bet_at',
                    ]; 
    
                    $where = "plgl.bet_at BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'";
    
                    switch ($order_by) {
                        case 'player_username':
                            $order_by = 'player_username';
                            break;
                        case 'game_platform_id':
                            $order_by = 'plgl.game_platform_id';
                            break;
                        case 'game_name':
                            $order_by = 'game_name';
                            break;
                        case 'game_code':
                            $order_by = 'gd.game_code';
                            break;
                        case 'bet_amount':
                            $order_by = 'plgl.bet_amount';
                            break;
                        case 'bet_at':
                            $order_by = 'plgl.bet_at';
                            break;
                        default:
                            $order_by = 'plgl.bet_amount';
                        break;
                    }
    
                    $players_bet_list = $this->player_latest_game_logs->getPlayerLatestGameLogsCustom($fields, $where, $order_by, $order_type, $limit, $offset);
                }
    
                if (!empty($players_bet_list)) {
                    foreach ($players_bet_list as $key => $player) {
                        if (isset($player['player_username'])) {
                            $list['player_username'] = $player['player_username'];
                        }
    
                        if (isset($player['game_platform_id'])) {
                            $list['game_platform_id'] = $player['game_platform_id'];
                        }
    
                        if (isset($player['game_name'])) {
                            $list['game_name'] = $player['game_name'];
                        }
    
                        if (isset($player['game_code'])) {
                            $list['game_code'] = $player['game_code'];
                        }
    
                        if (isset($player['number_of_bet'])) {
                            $list['number_of_bet'] = $player['number_of_bet'];
                        }
    
                        if (isset($player['total_bet_amount'])) {
                            $list['total_bet_amount'] = $player['total_bet_amount'];
                        }
    
                        if (isset($player['bet_amount'])) {
                            $list['bet_amount'] = $player['bet_amount'];
                        }
    
                        if (isset($player['bet_at'])) {
                            $list['bet_at'] = $player['bet_at'];
                        }
    
                        if (!$show_player) {
                            $str = $list['player_username'];
                            $username_partially_hidden = substr_replace($str, '***', 1, -2);
                            $list['player_username'] = $username_partially_hidden;
                        }
    
                        $players_bet_list[$key] = $list;
                    }
    
                    $msg = 'Get data successfully!';
                } else {
                    $players_bet_list = null;
                }
    
                $ret = [
                    'success' => true,
                    'code' => 0,
                    'mesg' => $msg,
                    'result' => $players_bet_list,
                ];
    
                $this->utils->debug_log(__FUNCTION__, '2', 'cache_key', $cache_key, 'ret', $ret);
    
                if (!empty($players_bet_list)) {
                    $ttl = $this->utils->getConfig('get_players_bet_list_by_date_cache_ttl');
                    $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
                }
            }
        } catch (Exception $ex) {
            $ex_log = [
                'code' => $ex->getCode(),
                'message' => isset($res['mesg_debug']) ? $res['mesg_debug'] : $ex->getMessage(),
            ];

            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

            $ret = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'result' => null,
            ];
        } finally {
            $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }

    public function getProviderLatestBets() {
    	$player_username = $this->input->post('player_username');
		$game_type = $this->input->post('game_type');
		$custom_game_type = (bool)$this->input->post('custom_game_type');
		$game_provider = $this->input->post('game_provider');
		// $game_code = $this->input->post('game_code');
		$currency_symbol = $this->input->post('currency_symbol');
		$event_start_date = $this->input->post('event_start_date');
		$event_end_date = $this->input->post('event_end_date');
		$lang_code = $this->input->post('lang_code');
		$refresh = (bool)$this->input->post('refresh');
		$res = "";
		$api_key = $this->input->post('api_key');
	    if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->model(array('game_logs'));

			if (empty($game_provider)) {
				throw new Exception('Provider ID is required', self::CODE_LG_PLATFORM_ID_INVALID);
			}

			if (empty($event_start_date) || empty($event_end_date) ) {
				throw new Exception('Event start and end date required', self::CODE_INVALID_REQUEST_DATE_PARAM);
			}

			$start_date = new DateTime($event_start_date);
			$end_date = new DateTime($event_end_date);
			$date_diff = date_diff($start_date,$end_date);
			$max_day = 7;
			if ($date_diff->days > $max_day) {
				throw new Exception('Allow 7 days range only', self::CODE_INVALID_REQUEST_DATE_PARAM);
			}

			if(empty($lang_code)){
				$lang_code = Language_function::INT_LANG_ENGLISH;
			}

        	$cache_key = "lb-by-provider-" . md5("{$game_provider}{$game_type}{$player_username}{$event_start_date}{$event_end_date}{$currency_symbol}{$lang_code}{$custom_game_type}");
			$cached_result = $this->utils->getJsonFromCache($cache_key);
            if (!empty($cached_result) && !$refresh) {
                $ret = $cached_result;
                $this->utils->debug_log(__FUNCTION__, '1', 'cache_key', $cache_key, 'ret', $ret);
                return $ret;
            }

			$res = $this->game_logs->getProviderLatestBets($game_provider, $game_type, $player_username, $event_start_date, $event_end_date, $custom_game_type);
			$results = [];
			if(!empty($res)){
				foreach($res as $key => $rs) {
					if(empty($player_username)){
						$results[$key]["player_username"] = substr_replace($rs["player_username"], '*****', 4);
					}
					$results[$key]["type"] = $this->utils->text_from_json($rs["game_type"], $lang_code);
					$results[$key]["bet_time"] = $rs["betting_time"];
					$results[$key]["bet_amount"] = (!empty($currency_symbol) ? "{$currency_symbol} " : "") .number_format($rs["bet_amount"], 2);
					$results[$key]["odds"] = ($rs["odds"] == 0 || $rs["odds"] == null) ? "-" : $rs["odds"];
					$results[$key]["win_amount"] = (!empty($currency_symbol) ? "{$currency_symbol} " : "") .number_format($rs["win_amount"], 2);
				}
			}

			$ret = [
				'success'   => true,
				'code'      => self::CODE_SUCCESS,
				'mesg'      => "Latest bets retrieved successfully",
				'result'    => $results
			];

			$this->utils->debug_log(__METHOD__, 'response', $res);
			if (!empty($results)) {
				$default_ttl = 300;
				$ttl_config = $this->utils->getConfig('get_provider_latest_bets_cache_ttl');
                $ttl = !empty($ttl_config) ? $ttl_config : $default_ttl;
                $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
            }
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	}

	public function getProviderBetRankings() {
		$game_type = $this->input->post('game_type');
		$custom_game_type = (bool)$this->input->post('custom_game_type');
		$game_provider = $this->input->post('game_provider');
		// $game_code = $this->input->post('game_code');
		$currency_symbol = $this->input->post('currency_symbol');
		$event_start_date = $this->input->post('event_start_date');
		$event_end_date = $this->input->post('event_end_date');
		$refresh = (bool)$this->input->post('refresh');
		$res = "";
		$api_key = $this->input->post('api_key');
	    if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->model(array('game_logs'));
			if (empty($game_provider)) {
				throw new Exception('Provider ID is required', self::CODE_LG_PLATFORM_ID_INVALID);
			}

			if (empty($event_start_date) || empty($event_end_date) ) {
				throw new Exception('Event start and end date required', self::CODE_INVALID_REQUEST_DATE_PARAM);
			}

			$start_date = new DateTime($event_start_date);
			$end_date = new DateTime($event_end_date);
			$date_diff = date_diff($start_date,$end_date);
			$max_day = 7;
			if ($date_diff->days > $max_day) {
				throw new Exception('Allow 7 days range only', self::CODE_INVALID_REQUEST_DATE_PARAM);
			}

        	$cache_key = "rank-by-provider-" . md5("{$game_provider}{$game_type}{$currency_symbol}{$event_start_date}{$event_end_date}{$custom_game_type}");
			$cached_result = $this->utils->getJsonFromCache($cache_key);
            if (!empty($cached_result) && !$refresh) {
                $ret = $cached_result;
                $this->utils->debug_log(__FUNCTION__, '1', 'cache_key', $cache_key, 'ret', $ret);
                return $ret;
            }

			$res = $this->game_logs->getProviderBetRankings($game_provider, $game_type, $event_start_date, $event_end_date, $custom_game_type);
			$results = [];
			if(!empty($res)){
				$rank = $count = 0;
				$prev_total_bet = null;
				foreach($res as $key => $rs) {
					++$count;
					$total_bet = $rs["total_bet"];
					$rank = $total_bet == $prev_total_bet ? $rank : $count; 
					$results[$key]['rank'] = $rank; 
					$results[$key]["player_username"] = substr_replace($rs["player_username"], '*****', 4);
					$results[$key]["total_bet"] = (!empty($currency_symbol) ? "{$currency_symbol} " : "") .number_format($rs["total_bet"], 2);
				}
			}

			$ret = [
				'success'   => true,
				'code'      => self::CODE_SUCCESS,
				'mesg'      => "Bet rank retrieved successfully",
				'result'    => $results
			];

			$this->utils->debug_log(__METHOD__, 'response', $res);
			if (!empty($results)) {
				$default_ttl = 300;
				$ttl_config = $this->utils->getConfig('get_provider_bet_rankings_cache_ttl');
                $ttl = !empty($ttl_config) ? $ttl_config : $default_ttl;
                $this->utils->saveJsonToCache($cache_key, $ret, $ttl);
            }
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	}

    /**
     * Get players game logs, default is latest
     * OGP-28514
     *
     * @uses    date    POST:date                   Date. sample 1996-04-24. Default date today
	 * @uses    string	POST:player_username        Player username. Default _null
	 * @uses    int	    POST:game_platform_id       Game Platform Id. Default 0
	 * @uses    string  POST:game_type              Game Type. Default _null
	 * @uses    string  POST:game_code              Game Code. Default _null
	 * @uses    string  POST:order_by               Order By. Default bet_at
	 * @uses    string  POST:order_type             Order Type. Default desc
	 * @uses    int     POST:limit                  Limit. Default 0
	 * @uses    int     POST:offset                 Offset. Default 0
	 * @uses    int     POST:show_player            Show Player name or Blur Player Name. Default 0
	 * @uses    int     POST:show_win_only          Show wins only, will hide lose records. Default 1
	 * @uses    int     POST:get_total              Get total results. Default 0
     * 
     * @return  JSON    Standard JSON return object
     */
    public function getPlayersGameLogs() {
        $api_key = $this->input->post('api_key');
        $off_api_key = $this->input->post('off_api_key');

        if (!$this->__checkKey($api_key) && !$off_api_key) {
            return; 
        }

        $ret = [];
        $res = [];
        $list = [];
        $msg = 'No Data!';
        $players_game_logs = null;

        try {
            $this->load->model(['game_logs', 'player_latest_game_logs']);

            $date = $this->input->post('date');
            $player_username = $this->input->post('player_username');
            $game_platform_id = intval($this->input->post('game_platform_id'));
            $game_type = $this->input->post('game_type');
            $game_code = $this->input->post('game_code');
            $order_by = $this->input->post('order_by');
            $order_type = $this->input->post('order_type');
            $limit = intval($this->input->post('limit'));
            $offset = intval($this->input->post('offset'));
            $show_player = intval($this->input->post('show_player'));
            $show_win_only = intval($this->input->post('show_win_only'));
            $get_total = intval($this->input->post('get_total'));
            $date_start = $this->input->post('date_start');
            $date_end = $this->input->post('date_end');
            $get_players_game_logs_default_by = $this->input->post('get_players_game_logs_default_by');
            $delete_cache = intval($this->input->post('delete_cache'));

            if (!$date) {
                $date = date('Y-m-d', strtotime($this->utils->getNowForMysql()));
            } else {
                $date = date('Y-m-d', strtotime($date));
            }

            if (!$order_by) {
                $order_by = 'bet_datetime';
            }

            if (!$order_type) {
                $order_type = 'desc';
            }

            if (!$get_players_game_logs_default_by) {
                $get_players_game_logs_default_by = $this->utils->getConfig('get_players_game_logs_default_by');
            }

            if (!$player_username || !$game_type || !$game_code || !$date_start || !$date_end) {
                $player_username = $game_type = $game_code = $date_start = $date_end = '_null';
            }

            $ret = [
                'success' => true,
                'code' => 0,
                'mesg' => $msg,
                'result' => $players_game_logs,
            ];

            $cache_key = __CLASS__ . "-" . __TRAIT__ . "-" .__FUNCTION__ . "-{$date}-{$player_username}-{$game_platform_id}-{$game_type}-{$game_code}-{$order_by}-{$order_type}-{$limit}-{$offset}-{$show_player}-{$show_win_only}-{$get_total}-{$date_start}-{$date_end}-{$get_players_game_logs_default_by}";
            $cache_key_encrypted = sha1($cache_key);
            $cached_result = $this->utils->getJsonFromCache($cache_key_encrypted);

            if (!empty($cached_result)) {
                if ($delete_cache) {
                    $this->utils->deleteCache($cache_key_encrypted);
                } else{
                    $ret = $cached_result;
                    $this->utils->debug_log(__FUNCTION__, '1', 'costMs', $this->utils->getCostMs(), 'cache_key', $cache_key, 'cache_key_encrypted', $cache_key_encrypted, 'ret', $ret);
                    return $ret;
                }
            } else {
                // will get cache key from the service
                if ($this->utils->getConfig('cache_players_game_logs_from_scheduler_cronjob')) {
                    $ret = [
                        'success' => true,
                        'code' => 0,
                        'mesg' => $msg,
                        'result' => $players_game_logs,
                    ];
                } else {
                    switch ($order_by) {
                        case 'username':
                            $order_by = 'p.username';
                            break;
                        case 'game_platform_id':
                            $order_by = 'plgl.game_platform_id';
                            break;
                        case 'game_name':
                            $order_by = 'gd.english_name';
                            break;
                        case 'game_code':
                            $order_by = 'gd.game_code';
                            break;
                        case 'bet_amount':
                            $order_by = 'plgl.bet_amount';
                            break;
                        case 'win_amount':
                            $order_by = 'plgl.win_amount';
                            break;
                        case 'bet_datetime':
                            $order_by = 'plgl.bet_at';
                            break;
                        case 'end_datetime':
                            $order_by = 'plgl.end_at';
                            break;
                        default:
                            $order_by = 'plgl.bet_at';
                        break;
                    }

                    if ($player_username == '_null' || $game_type == '_null' || $game_code == '_null' || $date_start == '_null' || $date_end == '_null') {
                        $player_username = $game_type = $game_code = $date_start = $date_end = '';
                    }

                    if ($this->utils->getConfig('use_player_latest_game_logs')) {
                        $players_game_logs = $this->player_latest_game_logs->getPlayersLatestGameLogs(
                            $date,
                            $player_username,
                            $game_platform_id,
                            $game_type,
                            $game_code,
                            $order_by,
                            $order_type,
                            $limit,
                            $offset,
                            $show_win_only,
                            $get_total,
                            $date_start,
                            $date_end,
                            $get_players_game_logs_default_by
                        );
                    } else {
                        $players_game_logs = $this->game_logs->getPlayersGameLogs(
                            $date,
                            $player_username,
                            $game_platform_id,
                            $game_type,
                            $game_code,
                            $order_by,
                            $order_type,
                            $limit,
                            $offset,
                            $show_win_only,
                            $get_total,
                            $date_start,
                            $date_end,
                            $get_players_game_logs_default_by
                        );
                    }

                    if (!empty($players_game_logs)) {
                        foreach ($players_game_logs as $key => $player) {
                            if (isset($player['player_username'])) {
                                $list['player_username'] = $player['player_username'];
                            }
        
                            if (isset($player['game_platform_id'])) {
                                $list['game_platform_id'] = $player['game_platform_id'];
                            }
        
                            if (isset($player['game_type'])) {
                                $list['game_type'] = $player['game_type'];
                            }
        
                            if (isset($player['game_name'])) {
                                $list['game_name'] = $player['game_name'];
                            }
        
                            if (isset($player['game_code'])) {
                                $list['game_code'] = $player['game_code'];
                            }
        
                            if (isset($player['bet_amount'])) {
                                $list['bet_amount'] = $player['bet_amount'];
                            }
        
                            if (isset($player['win_amount'])) {
                                $list['win_amount'] = $player['win_amount'];
                            }
        
                            if (!$get_total) {
                                if (!empty($player['multiplier'])) {
                                    $list['multiplier'] = $this->utils->truncateAmount($player['multiplier'], 4);
                                } else {
                                    $list['multiplier'] = 0;
                                }
                            }
        
                            if (isset($player['number_of_bet'])) {
                                $list['number_of_bet'] = $player['number_of_bet'];
                            }
        
                            if (isset($player['total_bet_amount'])) {
                                $list['total_bet_amount'] = $player['total_bet_amount'];
                            }
        
                            if (isset($player['total_win_amount'])) {
                                $list['total_win_amount'] = $player['total_win_amount'];
                            }
        
                            if (!empty($player['win_amount']) || !empty($player['total_win_amount'])) {
                                $list['result'] = 'win';
                            } else {
                                $list['result'] = 'lose';
                            }
        
                            if (isset($player['bet_datetime'])) {
                                $list['bet_datetime'] = $player['bet_datetime'];
                            }
        
                            if (isset($player['settle_datetime'])) {
                                // $list['settle_datetime'] = $player['settle_datetime'];
                            }
        
                            if (!$show_player) {
                                $str = $player['player_username'];
                                $username_partially_hidden = substr_replace($str, '***', 1, -2);
                                $list['player_username'] = $username_partially_hidden;
                            }
        
                            $players_game_logs[$key] = $list;
                        }
        
                        $msg = 'Get data successfully!';
                    } else {
                        $players_game_logs = null;
                    }
        
                    $ret = [
                        'success' => true,
                        'code' => 0,
                        'mesg' => $msg,
                        'result' => $players_game_logs,
                    ];
        
                    $this->utils->debug_log(__FUNCTION__, '2', 'costMs', $this->utils->getCostMs(), 'cache_key', $cache_key, 'cache_key_encrypted', $cache_key_encrypted, 'ret', $ret);
        
                    if (!empty($players_game_logs)) {
                        $ttl = $this->utils->getConfig('get_players_game_logs_cache_ttl');
                        $this->utils->saveJsonToCache($cache_key_encrypted, $ret, $ttl);
                    }
                }
            }
        } catch (Exception $ex) {
            $ex_log = [
                'code' => $ex->getCode(),
                'message' => isset($res['mesg_debug']) ? $res['mesg_debug'] : $ex->getMessage(),
            ];

            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

            $ret = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'result' => null,
            ];
        } finally {
            $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }

    public function getMonthlyTotalTurnOverByPlayer(){
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) return;
    
        $this->CI->load->model(['common_token', 'total_player_game_hour']);
    
        try {
            $token = $this->input->post('token');
            $player = $this->common_token->getPlayerInfoByToken($token);
    
            if (!$player) {
                throw new Exception('Invalid player token', self::CODE_INVALID_TOKEN);
            }
    
            $playerId = isset($player['player_id']) ? $player['player_id'] : null;

            $current_date = new DateTime();
            $start_month_day = $current_date->format('Y-m-01');
            $end_month_day = $current_date->format('Y-m-t');

            $request_body = $this->input->post();
            # determine if refresh
            $forceDB = false;
            if(isset($request_body['refresh'])&&!empty($request_body['refresh'])&&$request_body['refresh']==true){
                $forceDB = true;
            }
            # check cache
            $cache_key = 'getMonthlyTotalTurnOverByPlayer';
            $cache_string = '';
            
            foreach($request_body as $key => $val){
                if(is_array($val)){
                    $cache_string .= $key. implode($val);
                }else{
                    $cache_string .= $key.$val;
                }
            }

            if(!empty($cache_string)){
                $cache_key .= md5($cache_string);
            }

            $cached_result = $this->utils->getJsonFromCache($cache_key);

            if(!empty($cached_result)  && !$forceDB){
                $rlt= $cached_result;
                $rlt['mesg'] = "Player total monthly turnover retrieved successfully from cached";
                return $rlt;
            }
    
            $results = $this->total_player_game_hour->sumGameLogsByPlayerPerGameType($playerId, $start_month_day, $end_month_day);

            $formattedResult = ['total_bet' => 0];
            if(!empty($results)){
            	foreach ($results as $result) {
	                $formattedResult['total_bet'] += $result['total_betting_amount'];
	                $formattedResult[$result['game_type_code']] = floatval($result['total_betting_amount']);
	            }
            }
    
            $rlt = [
                'success' => true,
                'code' => self::CODE_SUCCESS,
                'mesg' => "Player total monthly turnover retrieved successfully",
                'res' => $formattedResult
            ];
            $this->utils->saveJsonToCache($cache_key, $rlt, 3600);
        } catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', ['code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
    
            $rlt = [
                'success' => false,
                'code' => $ex->getCode(),
                'mesg' => $ex->getMessage(),
                'res' => ['total_bet' => 0]
            ];
        } finally {
            $this->comapi_return_json($rlt);
        }
    }
    
} // End of trait t1t_comapi_module_player_game_log