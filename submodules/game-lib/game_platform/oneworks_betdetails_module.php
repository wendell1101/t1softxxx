<?php

trait oneworks_betdetails_module {

    public function getMatchDetailWithCache($match_id){
    	return $this->CI->oneworks_game_logs->getMatchDetailWithCache($match_id);
    }

    public function generateBetDetails($gameRecord){
    	$this->CI->load->model(array('oneworks_game_logs'));
        $bet_detail = array();

        $game_name=!empty($gameRecord['game_description_name']) ? lang($gameRecord['game_description_name']) : $gameRecord['game'];

        if(!empty($gameRecord['parlay_data'])){
            $parlay_data = $this->CI->utils->decodeJson($gameRecord['parlay_data']);
        	$bet_detail[]=[
        		"refNo" => 'Ref No:' . $gameRecord['trans_id'],
        		"betTime" => $gameRecord['bet_at'],
        		"parlayType" => $gameRecord['parlay_type'],
        		"comboType"  => $gameRecord['combo_type'] . $gameRecord['bet_amount'],
        		"odds"  => "@".$gameRecord['odds'],
        	];
        	foreach ($parlay_data as $key => $data) {
        		$homeTeam = isset($this->getTeamName($data['home_id'])['teamName']) ? $this->getTeamName($data['home_id'])['teamName'] : null;
		        $awayTeam = $this->getTeamName($data['away_id'])['teamName'];
        		$league_name = isset($this->getLeagueName($data['league_id'])['leagueName']) ? $this->getLeagueName($data['league_id'])['leagueName'] : null;
        		if(empty($data['match_id'])){
        			$bet_team = $this->getTeamName($data['bet_team']);
        			$bet = isset($bet_team['teamName'][$this->bet_detail_default_lang])? $bet_team['teamName'][$this->bet_detail_default_lang] : null;
        		} else if($data['bet_type'] == 5) { // FT.1X2
        			$bet = "FT." . strtoupper($data['bet_team']);
        		} else {
        			// $bet = ($data['bet_team'] == self::IS_HOME ) ? @$homeTeam : @$awayTeam;
                    $data['bet_type_oneworks'] = $data['bet_type'];
                    $bet = $this->checkAndGetBetPlace($data,$homeTeam,$awayTeam);
        		}
                $score = ($data['islive'] == self::IS_LIVE) ? "[" . $data['home_score'] . "-" . $data['away_score'] . "]" : "";
        		$hdp = $data['hdp'];
        		$odds = "@".$data['odds'];
                $bet_detail[] = array(
	            	'bet' => $bet . " " . $hdp . $odds . $score,
	            	'betType'=> $this->getBetType($data['bet_type']),
	            	'vs' => $homeTeam.' -vs- '.$awayTeam,
	            	'League' => 'Parlay' . "/" . $league_name,
	            );
            }
            $bet_detail[]=[
                "is_parlay"  => !empty($gameRecord['parlay_data']) ? true : false,
            ];
            return $bet_detail;
        }else{
        	$homeTeam = null;
        	$awayTeam = null;
        	$league_name = null;
        	$bet_detail['refNo']   = 'Ref No:' . $gameRecord['trans_id'];
        	$bet_detail['betTime'] = $gameRecord['bet_at'];
        	if($gameRecord['sport_type'] == self::NUMBER_GAME) {
        		$number_details = $this->getBetDetailByTransId($gameRecord['trans_id'],$gameRecord['sport_type']);
        		$last_ball = isset($number_details['last_ball_no']) ? "[". $number_details['last_ball_no'] ."]": null;
        		$result = $this->getMatchDetailWithCache($gameRecord['match_id']);
        		$bet = $this->generateBetPlace($gameRecord);
	            $bet_detail['betTeam']   = $bet . $last_ball;
        		$bet_detail['betType'] = $this->getBetType($gameRecord['bet_type_oneworks']);
        		if(!empty($result)){
	        		$bet_detail['no'] = "No. " . $result['game_no'];
        		}
        		$bet_detail['game'] = $game_name;
        	} else if(in_array($gameRecord['sport_type'], self::VIRTUAL_SPORTS)) {
        		if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
        			$homeTeam = $this->getTeamName($gameRecord['home_id'])['teamName'];
		        	$awayTeam = $this->getTeamName($gameRecord['away_id'])['teamName'];
        		}
        		if(!empty($gameRecord['league_id'])){
        			$league_name = $this->getLeagueName($gameRecord['league_id'])['leagueName'];
        		}
        		$virtual_info = $this->getBetDetailByTransId($gameRecord['trans_id'],$gameRecord['sport_type']);
        		$race_no = isset($virtual_info['race_number']) ? $virtual_info['race_number']." - " : null;
        		$bet = $this->checkAndGetBetPlace($gameRecord,$homeTeam,$awayTeam);
        		$hdp = !empty($gameRecord['hdp']) ? $gameRecord['hdp'] : null;
        		$score = (isset($gameRecord['home_score']) && $gameRecord['isLive']) ? "[" . $gameRecord['home_score'] . "-" . $gameRecord['away_score'] . "]" : "";
				$bet_detail['bet'] = $race_no . $bet . " " . $hdp .$score;
				$bet_detail['betType'] = $this->getBetType($gameRecord['bet_type_oneworks']);
				if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
					$bet_detail['vs'] = $homeTeam.' vs '.$awayTeam;
				}
				$bet_detail['League'] = $game_name . " " . $league_name;
        	} else if(in_array($gameRecord['sport_type'], self::VIRTUAL_SPORTS2)) {
        		if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
        			$homeTeam = $this->getTeamName($gameRecord['home_id'])['teamName'];
		        	$awayTeam = $this->getTeamName($gameRecord['away_id'])['teamName'];
        		}
        		if(!empty($gameRecord['league_id'])){
        			$league_name = $this->getLeagueName($gameRecord['league_id'])['leagueName'];
        		}
        		$bet = $this->checkAndGetBetPlace($gameRecord,$homeTeam,$awayTeam);
        		$hdp = !empty($gameRecord['hdp']) ? $gameRecord['hdp'] : null;
        		if(in_array($gameRecord['bet_type_oneworks'], self::OVERUNDER_BET)){
        			$hdp = !empty($gameRecord['hdp']) ? $gameRecord['hdp'] : (!empty($gameRecord['home_hdp']) ? $gameRecord['home_hdp'] : $gameRecord['away_hdp']);
        		}
        		$score = (isset($gameRecord['home_score']) && $gameRecord['isLive']) ? "[" . $gameRecord['home_score'] . "-" . $gameRecord['away_score'] . "]" : "";
				$bet_detail['bet'] = $bet . " " . $hdp .$score;
				$bet_detail['betType'] = $this->getBetType($gameRecord['bet_type_oneworks']);
				if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
					$bet_detail['vs'] = $homeTeam.' vs '.$awayTeam;
				}
				$virtual_info = $this->getMatchDetailWithCache($gameRecord['match_id']);

				$bet_detail['virtualinfo'] = $virtual_info->VirtualSport_info;
				$bet_detail['League'] = $game_name . " " . $league_name;
			} else if($gameRecord['sport_type'] == self::LG_VIRTUAL_SPORTS) {
				$bet_choices = explode(';', $gameRecord['bet_choice']);
				if(!empty($bet_choices)){
					foreach ($bet_choices as $key => $value) {
						$bet_choice = substr($value, 0, 2);
						if(strtolower($bet_choice) == "m="){
							$bet_detail['Match Team'] = ltrim($value, $bet_choice);
						} else if(strtolower($bet_choice) == "b="){
							$bet_detail['Bet Option'] = ltrim($value, $bet_choice);
						} else if(strtolower($bet_choice) == "s="){
							$bet_detail['Bet Type'] = ltrim($value, $bet_choice);
						} else if(strtolower($bet_choice) == "o="){
							$bet_detail['Odds'] = ltrim($value, $bet_choice);
						} else if(strtolower($bet_choice) == "ht="){
							$bet_detail['Half Time Score'] = ltrim($value, $bet_choice);
						} else if(strtolower($bet_choice) == "ht="){
							$bet_detail['Full Time Score'] = ltrim($value, $bet_choice);
						}
					}
				}
        	} else {
        		if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
        			$homeTeam = isset($this->getTeamName($gameRecord['home_id'])['teamName']) ? $this->getTeamName($gameRecord['home_id'])['teamName'] : null;
		        	$awayTeam = isset($this->getTeamName($gameRecord['away_id'])['teamName']) ? $this->getTeamName($gameRecord['away_id'])['teamName'] : null;
        		}
        		if(!empty($gameRecord['league_id'])){
        			$league_name = isset($this->getLeagueName($gameRecord['league_id'])['leagueName']) ? $this->getLeagueName($gameRecord['league_id'])['leagueName'] : null;
        		}
        		$bet = $this->checkAndGetBetPlace($gameRecord,$homeTeam,$awayTeam);
        		$hdp = !empty($gameRecord['hdp']) ? $gameRecord['hdp'] : (!empty($gameRecord['home_hdp']) ? $gameRecord['home_hdp'] : $gameRecord['away_hdp']);
        		if(in_array($gameRecord['bet_type_oneworks'], self::NO_HDP_DISPLAY)){
        			$hdp = null;
        		}
        		$score = (isset($gameRecord['home_score']) && $gameRecord['isLive']) ? "[" . $gameRecord['home_score'] . "-" . $gameRecord['away_score'] . "]" : "";
				$bet_detail['bet'] = $bet . " " . (!empty($hdp) ? $hdp : null) .$score;
				$bet_detail['betType'] = $this->getBetType($gameRecord['bet_type_oneworks'],$gameRecord['trans_id']);
				if(!empty($gameRecord['home_id']) && !empty($gameRecord['away_id'])){
					$bet_detail['vs'] = $homeTeam.' vs '.$awayTeam;
				}
				$bet_detail['League'] = $game_name . " " . $league_name;
        	}
        	if(!empty($gameRecord['cash_out_data'])){
        		$cash_out_data = $this->CI->utils->decodeJson($gameRecord['cash_out_data']);
        		$count = 1;
        		$bet_detail['cashout']= "---Cash Out Data---";
        		foreach ($cash_out_data as $key => $cashout) {
        			$bet_detail['cashout_id-'.$count] = isset($cashout['cashout_id']) ? 'Ref No:' .$cashout['cashout_id'] : null;
        			$bet_detail['transdate-'.$count]  = isset($cashout['transdate']) ? $cashout['transdate'] : null;
        			$bet_detail['stake-'.$count] 	  = isset($cashout['stake']) ? "Stake: " . $cashout['stake'] : null;
        			$bet_detail['cashout-'.$count] 	  = isset($cashout['buyback_amount']) ? "Cashout: " . $cashout['buyback_amount'] : null;
        			$bet_detail['winlost-'.$count] 	  = isset($cashout['buyback_amount']) ? "Win/Lose: " . ((float)$cashout['buyback_amount'] - $cashout['stake']) : null;
        			$bet_detail['-'.$count]    = "";
		            $count++;
        		}
        	}
        }

        return $bet_detail;
    }

    public function generateMatchDetails($gameRecord){
    	$this->CI->load->model(array('oneworks_game_logs'));
        $result = $this->getMatchDetailWithCache($gameRecord['match_id']);
        $bet_detail = array();
        if(!empty($result)){
        	if($gameRecord['sport_type'] == self::NUMBER_GAME)
	        {
	        	$bet_detail = array(
	            	'Match' => $gameRecord['game'] . " No " . $result['game_no'],
	            	'1st Ball' => $result['first_ball'],
	            	'2nd Ball' => $result['second_ball'],
	            	'3rd Ball' => $result['third_ball'],
	            	'Status'   => ($result['game_status'] == "Close") ? "Completed" : $result['game_status'],
	            );
	        } else if(in_array($gameRecord['bet_type_oneworks'], self::VIRTUAL_WINPLACE_BET)) {
	        	//remove by OGP-31178
	        	// $virtual_result =  $this->getVirtualResult($gameRecord['sport_type'],$gameRecord['match_id'])['Data'];
	        	// if(!empty($virtual_result)){
	        	// 	foreach ($virtual_result as $key => $value) {
	        	// 		if(!empty($value)){
	        	// 			$race_name = $this->getTeamName(@$value['RacerID'],$gameRecord['bet_type_oneworks'])['teamName'];
	        	// 			// $race_name = $home = $this->getTeamName(@$value['RacerID'],$gameRecord->bet_type);
				//         	$bet_detail[@$value['Placing']] = (!empty($race_name)) ? $race_name : "N/A";
				//         	$bet_detail[@$value['Placing'] .' Win'] = (!empty($value['WinOdds'])) ? $value['WinOdds'] : "N/A";
				//         	$bet_detail[@$value['Placing'] .' Status'] = (!empty($value['IsFavor'])) ? $value['IsFavor'] : "N/A";
	        	// 		}
	        	// 	}
	        	// }
	        } else {
	        	$homeTeam = $this->getTeamName($gameRecord['home_id'])['teamName'];
		        $awayTeam = $this->getTeamName($gameRecord['away_id'])['teamName'];
		        $bet_detail = array(
		        	'Home Team' => $homeTeam,
		        	'Away Team' => $awayTeam,
		        );
		        if(!empty($result)) {
		        	$bet_detail['Home Score'] = $result['home_score'];
		        	$bet_detail['Away Score'] = $result['away_score'];
		        	$bet_detail['HT Home Score'] = $result['ht_home_score'];
		        	$bet_detail['HT Away Score'] = $result['ht_away_score'];
		        	$bet_detail['Status'] = ($result['game_status'] == "Close") ? "Completed" : $result['game_status'];
		        }
	        }
        }
        return $bet_detail;
    }

    public function checkAndGetBetPlace($gameRecord = null,$homeTeam = null,$awayTeam = null){
    	$bet = null;
    	if(in_array($gameRecord['bet_type_oneworks'], self::VIRTUAL_WINPLACE_BET) || $gameRecord['bet_type_oneworks'] == self::OUTRIGHT) {
			$bet = $this->getTeamName($gameRecord['bet_team'],$gameRecord['bet_type_oneworks'])['teamName'];
		} else if(in_array($gameRecord['bet_type_oneworks'], self::HANDICAP_BET)) {
			if($gameRecord['bet_team'] == self::IS_HOME || $gameRecord['bet_team'] == "1"){
				$bet = @$homeTeam;
			} else if($gameRecord['bet_team'] == "a" || $gameRecord['bet_team'] == "2"){
				$bet = @$awayTeam;
			} else {
				$bet = "Draw";
			}
		} else {
			$bet = $this->generateBetPlace($gameRecord);
		}
		return $bet;
    }

	public function generateBetPlace($gameRecord = null)
    {
        $trans_id = $gameRecord['trans_id'];
    	$bet_type = $gameRecord['bet_type_oneworks'];
    	$place_bet = $gameRecord['bet_team'];
    	$bet_name = $this->getBetType($gameRecord['bet_type_oneworks']);
    	$bet_type_info = array(
    		"ODDEVEN_BET" => array(
    			"h" => "Odd",
    			"a" => "even",
    			"o" => "Odd",
    			"e" => "even",
    		),
    		"OVERUNDER_BET" => array(
    			"h" => "Over",
    			"a" => "Under",
    			"o" => "Over",
    			"u" => "Under",
    		),
    		"ONEXTWO_BET" => array(
    			"1" => "1",
    			"x" => "x",
    			"2" => "2",
    		),
    		"YES_NO_BET" => array(
    			"y" => "Yes",
    			"n" => "No",
    		),
    		"HALF_SCORING_BET" => array(
    			"1h" => "First Half",
    			"2h" => "Second Half",
    			"tie" => "Tie",
    		),
    		"DOUBLE_CHANCE_BET" => array(
    			"1x" => "Home or Draw",
    			"12" => "Home or Away",
    			"2x" => "Away or Draw",
    			"hd" => "Home or Draw",
    			"ha" => "Home or Away",
    			"da" => "Away or Draw",
    		),
    		"FIRST_LAST_CORNER_BET" => array(
    			"h" => "Home",
    			"a" => "Away",
    			"n" => "None",
    		),
    		//Total Goal
    		"6" => array(
    			"3-6" => "0-1",
    			"2-3" => "2-3",
    			"4-6" => "4-6",
    			"7-over" => "7-over"
    		),
    		//Clean Sheet
    		"13" => array(
    			"hy" => 'Home Yes',
    			"hn" => 'Home No',
    			"ay" => 'Away Yes',
    			"an" => 'Away No',
    		),
    		//First Goal/Last Goal
    		"14" => array(
    			"1:1" => 'Home FG',
    			"1:2" => 'Home LG',
    			"2:1" => 'Away FG',
    			"2:2" => 'Away LG',
    			"0:0" => 'No Goals',
    		),
    		//HT/FT
    		"16" => array(
    			"0:0" => "DD",
    			"0:1" => "DH",
    			"0:2" => "DA",
    			"1:0" => "HD",
    			"1:1" => "HH",
    			"1:2" => "HA",
    			"2:0" => "AD",
    			"2:1" => "AH",
    			"2:2" => "AA",
    		),
    		//Next Goal
    		"22" => array(
    			"1" => "Home",
    			"x" => "No Goals",
    			"2" => "Away",
    		),
    		// Both/one/neither team to score
    		"26" => array(
    			"o" => "One",
    			"n" => "No Goal",
    			"b" => "Both",
    		),
    		// Nexthigh/low(number game)
    		"87" => array(
    			"h" => "Next High",
    			"a" => "Next Low",
    		),
    		//wairior(number game)
    		"88" => array(
    			"h" => "2nd",
    			"a" => "3rd",
    		),
    		//Next combo(number game)
    		"89" => array(
    			"1:1" => "Over:1-Odd",
    			"1:2" => "Over:1-Even",
    			"2:1" => "Under:2-Odd",
    			"2:2" => "Under:2-Even",
    		),
    		//Number wheel
    		"90" => array(
    			"1-1" => "1~5",
    			"1-2" => "6~10",
    			"1-3" => "11~15",
    			"1-4" => "16~20",
    			"1-5" => "21~25",
    			"1-6" => "26~30",
    			"1-7" => "31~35",
    			"1-8" => "36~40",
    			"1-9" => "41~45",
    			"1-10" => "46~50",
    			"1-11" => "51~55",
    			"1-12" => "56~60",
    			"1-13" => "61~65",
    			"1-14" => "66~70",
    			"1-15" => "71~75",
    			///---------///
    			"2-1" => "1~15",
    			"2-2" => "16~30",
    			"2-3" => "31~45",
    			"2-4" => "46~60",
    			"2-5" => "61~75",
    			"3-1" => "1~25",
    			"3-2" => "26~50",
    			"3-3" => "51~75",
    			///---------///
    			"4-1" => "1,6,11,16,21,26,31,36,41,46,51,56,61,66,71",
    			"4-2" => "2,7,12,17,22,27,32,37,42,47,52,57,62,67,72",
    			"4-3" => "3,8,13,18,23,28,33,38,43,48,53,58,63,68,73",
    			"4-4" => "4,9,14,19,24,29,34,39,44,49,54,59,64,69,74",
    			"4-5" => "5,10,15,20,25,30,35,40,45,50,55,60,65,70,75",
    			///---------///
    			"5-1" => "1",
    			"5-2" => "2",
    			"5-75" => "75",
    		),
            //Next R/B"
            "91" => array(
                "r" => lang("red"),
                "b" => lang("blue"),
            ),
    		//Home no bet
    		"121" => array(
    			"x" => "Draw",
    			"a" => "Away",
    		),
    		// Away no bet
    		"122" => array(
    			"h" => "Home",
    			"x" => "Draw",
    		),
    		// Draw / no draw
    		"123" => array(
    			"h" => "Draw",
    			"a" => "No Draw",
    		),
    		// 1h total goal
    		"126" => array(
    			"3-6" => "0-1",
    			"2-3" => "2-3",
    			"4-over" => "4&Over",
    		),
    		// 1h first goal/last goal
    		"127" => array(
    			"1:1" => 'Home FG',
    			"1:2" => 'Home LG',
    			"2:1" => 'Away FG',
    			"2:2" => 'Away LG',
    			"0:0" => 'No Goals',
    		),
    		// HT/FT ODD/EVEN
    		"128" => array(
    			"oo" => "Odd/Odd",
    			"oe" => "Odd/Even",
    			"eo" => "Even/Odd",
    			"ee" => "Even/Even",
    		),
    		// Match correct score
    		"1302" => array(
    			// 3 set
    			"20" => "Home 2-0 Win",
    			"21" => "Home 2-1 Win",
    			"02" => "Away 0-2 Win",
    			"12" => "Away 1-2 Win",
    			// 4 set
    			"30" => "Home 3-0 Win",
    			"31" => "Home 3-1 Win",
    			"32" => "Away 3-2 Win",
    			"03" => "Away 0-2 Win",
    			"13" => "Away 1-3 Win",
    			"23" => "Away 2-3 Win",
    		),
    		// Exact Total Goals
    		"159" => array(
    			"g0" => "0 Goals",
    			"g1" => "1 Goals",
    			"g2" => "2 Goals",
    			"g3" => "3 Goals",
    			"g4" => "4 Goals",
    			"g5" => "5 Goals",
    			"g6" => "6 &Over",
    		),
    		"406" => array(
    			"g0" => "0 Goals",
    			"g1" => "1 Goals",
    			"g2" => "2 Goals",
    			"g3" => "3 Goals",
    			"g4" => "4 Goals",
    			"g5" => "5 Goals",
    			"g6" => "6 &Over",
    		),
    		//Exact Home team goasls
    		"161" => array(
    			"g0" => lang("0 Goals"),
    			"g1" => lang("1 Goals"),
    			"g2" => lang("2 Goals"),
    			"g3" => lang("3 &Over"),
    		),
    		"407" => array(
    			"g0" => lang("0 Goals"),
    			"g1" => lang("1 Goals"),
    			"g2" => lang("2 Goals"),
    			"g3" => lang("3 &Over"),
    		),
    		//Exact Away team goasls
    		"162" => array(
    			"g0" => lang("0 Goals"),
    			"g1" => lang("1 Goals"),
    			"g2" => lang("2 Goals"),
    			"g3" => lang("3 &Over"),
    		),
    		"409" => array(
    			"g0" => lang("0 Goals"),
    			"g1" => lang("1 Goals"),
    			"g2" => lang("2 Goals"),
    			"g3" => lang("3 &Over"),
    		),
    		//Result / total goals
    		"163" => array(
    			"hu" => lang("Home/Under"),
    			"ho" => lang("Home/Over"),
    			"du" => lang("Draw/Under"),
    			"do" => lang("Draw/Over"),
    			"au" => lang("Away/Under"),
    			"ao" => lang("Away/Over"),
    		),
    		"144" => array(
    			"hu" => lang("Home/Under"),
    			"ho" => lang("Home/Over"),
    			"du" => lang("Draw/Under"),
    			"do" => lang("Draw/Over"),
    			"au" => lang("Away/Under"),
    			"ao" => lang("Away/Over"),
    		),
    		//Extra time 1h 1x2
    		"167" => array(
    			"1" => lang("Extra Time HT.1"),
    			"x" => lang("Extra Time HT.X"),
    			"2" => lang("Extra Time HT.2"),
    		),
    		//Extra time 1h 1x2
    		"169" => array(
    			"1-15"  => lang("1-15 Min"),
    			"6-30"  => lang("6-30 Min"),
    			"31-45" => lang("31-45 Min"),
    			"46-60" => lang("46-60 Min"),
    			"61-75" => lang("61-75 Min"),
    			"76-90" => lang("76-90 Min"),
    			"none"  => lang("none"),
    		),
    		// Team to score
    		"170" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    			"b" => lang("Both"),
    			"n" => lang("None"),
    		),
    		// Winning margin
    		"171" => array(
    			"h1"  => lang("Home 1"),
    			"h2"  => lang("Home 2"),
    			"h3" => lang("Home 3 up"),
    			"a1" => lang("Away 1"),
    			"a2" => lang("Away 2"),
    			"a3" => lang("Away 3up"),
    			"d" => lang("Draw"),
    			"ng" => lang("No Goal"),
    		),
    		"408" => array(
    			"h1"  => lang("Home 1"),
    			"h2"  => lang("Home 2"),
    			"h3" => lang("Home 3 up"),
    			"a1" => lang("Away 1"),
    			"a2" => lang("Away 2"),
    			"a3" => lang("Away 3up"),
    			"d" => lang("Draw"),
    			"ng" => lang("No Goal"),
    		),
    		// Result and first team to score
    		"172" => array(
    			"hh"  => lang("Home/Home"),
    			"hd"  => lang("Home/Draw"),
    			"ha" => lang("Home/Away"),
    			"ah" => lang("Away/Home"),
    			"ad" => lang("Away/Draw"),
    			"aa" => lang("Away/Away"),
    			"no" => lang("None"),
    		),
    		"415" => array(
    			"hh"  => lang("Home/Home"),
    			"hd"  => lang("Home/Draw"),
    			"ha" => lang("Home/Away"),
    			"ah" => lang("Away/Home"),
    			"ad" => lang("Away/Draw"),
    			"aa" => lang("Away/Away"),
    			"no" => lang("None"),
    		),
    		// Match Decided Method
    		"175" => array(
    			"hr"  => lang("Home/Regular Time"),
    			"he"  => lang("Home/Extra Time"),
    			"hp" => lang("Home/Penalty"),
    			"ar" => lang("Away/Regular Time"),
    			"ae" => lang("Away/Extra Time"),
    			"ap" => lang("Away/Penalty"),
    		),
    		//Exact 2h Goals
    		"187" => array(
    			"g0"  => lang("0 Goals"),
    			"g1"  => lang("1 Goals"),
    			"g2"  => lang("2 &Over"),
    		),
    		//First Goal Time (10 min)
    		"192" => array(
    			"1-10"  => lang("1-10 Min"),
    			"11-20"  => lang("11-20 Min"),
    			"21-30"  => lang("21-30 Min"),
    			"31-40"  => lang("31-40 Min"),
    			"41-50"  => lang("41-50 Min"),
    			"51-60"  => lang("51-60 Min"),
    			"61-70"  => lang("61-70 Min"),
    			"71-80"  => lang("71-80 Min"),
    			"81-90"  => lang("81-90 Min"),
    			"none"  => lang("None"),
    		),
    		//Home team exact corner
    		"195" => array(
    			"0-2"  => lang("0-2 Corners"),
    			"3-4"  => lang("3-4 Corners"),
    			"5-6"  => lang("5-6 Corners"),
    			"7-over"  => lang("7 &over"),
    		),
    		//Away team exact corner
    		"196" => array(
    			"0-2"  => lang("0-2 Corners"),
    			"3-4"  => lang("3-4 Corners"),
    			"5-6"  => lang("5-6 Corners"),
    			"7-over"  => lang("7 &over"),
    		),
    		//Total corners
    		"199" => array(
    			"0-8"  => lang("0-8 Corners"),
    			"9-11"  => lang("9-11 Corners"),
    			"12-over"  => lang("12 &over"),
    		),
    		// 1h home team exact corners
    		"200" => array(
    			"5-6"  => lang("0-1 Corners"),
    			"2"  => lang("2 Corners"),
    			"3"  => lang("3 Corners"),
    			"4-over"  => lang("4 &over"),
    		),
    		// 1h away team exact corners
    		"201" => array(
    			"5-6"  => lang("0-1 Corners"),
    			"2"  => lang("2 Corners"),
    			"3"  => lang("3 Corners"),
    			"4-over"  => lang("4 &over"),
    		),
    		// 1h total corners
    		"202" => array(
    			"0-4"  => lang("0-4 Corners"),
    			"5-6"  => lang("5-6 Corners"),
    			"7-over"  => lang("7 &over"),
    		),
    		//next 1 minute
    		"221" => array(
    			"2"  => lang("Goal Yes"),
    			"-2"  => lang("Goal No"),
    			"4"  => lang("Corner Yes"),
    			"-4"  => lang("Corner No"),
    			"8"  => lang("Free-Kick Yes"),
    			"-8"  => lang("Free-Kick No"),
    			"16"  => lang("Goal-Kick Yes"),
    			"-16"  => lang("Goal-Kick No"),
    			"32"  => lang("Throw-In Yes"),
    			"-32"  => lang("Throw-In No"),
    		),
    		//next 5 minute
    		"222" => array(
    			"2"  => lang("Goal Yes"),
    			"-2"  => lang("Goal No"),
    			"4"  => lang("Corner Yes"),
    			"-4"  => lang("Corner No"),
    			"8"  => lang("Free-Kick Yes"),
    			"-8"  => lang("Free-Kick No"),
    			"16"  => lang("Goal-Kick Yes"),
    			"-16"  => lang("Goal-Kick No"),
    			"32"  => lang("Throw-In Yes"),
    			"-32"  => lang("Throw-In No"),
    			"128"  => lang("Penalty-Yes"),
    		),
    		// what will happen first in next 1 minute
    		"223" => array(
    			"1"  => lang("None"),
    			"2"  => lang("Goal Yes"),
    			"5"  => lang("Corner Yes"),
    			"8"  => lang("Free-Kick"),
    			"16"  => lang("Goal Kick"),
    			"32"  => lang("Throw-In"),
    		),
    		// what will happen first in next 5 minute
    		"224" => array(
    			"1"  => lang("None"),
    			"2"  => lang("Goal Yes"),
    			"5"  => lang("Corner Yes"),
    			"64"  => lang("Booking"),
    			"128"  => lang("Penalty"),
    		),
    		// Next 1 minute set piece
    		"225" => array(
    			"1"  => lang("No"),
    			"44"  => lang("Yes"),
    		),
    		// which combination will happen first in next 1 min
    		"226" => array(
    			"26"  => lang("Goal/Free Kick/Goal Kick"),
    			"36"  => lang("Corner/Throw In"),
    			"1"  => lang("None"),
    		),
    		// which combination will happen first in next 5 min
    		"227" => array(
    			"194"  => lang("Goal/Booking/Penalty"),
    			"4"  => lang("Corner"),
    			"1"  => lang("None"),
    		),
    		//Exact 1st goal
    		"412" => array(
    			"0"  => lang("No Goal"),
    			"1"  => lang("1 Goal"),
    			"2"  => lang("2 Goals"),
    			"3"  => lang("3 Over"),
    		),
    		//Both Teams To Socre/Result
    		"417" => array(
    			"yh"  => lang("Yes/Home"),
    			"ya"  => lang("Yes/Away"),
    			"yd"  => lang("Yes/Draw"),
    			"nh"  => lang("No/Home"),
    			"na"  => lang("No/Away"),
    			"nd"  => lang("No/Draw"),
    		),
    		//Both Teams To Socre/Total goal
    		"418" => array(
    			"yo"  => lang("Yes/Over"),
    			"yu"  => lang("Yes/Under"),
    			"no"  => lang("No/Over"),
    			"nu"  => lang("No/Under"),
    		),
    		//which half first goal
    		"419" => array(
    			"1h"  => lang("1st Half"),
    			"2h"  => lang("2nd Half"),
    			"n"  => lang("Neither"),
    		),
    		//home team which falf first goal
    		"420" => array(
    			"1h"  => lang("1st Half"),
    			"2h"  => lang("2nd Half"),
    			"n"  => lang("Neither"),
    		),
    		//away team which falf first goal
    		"421" => array(
    			"1h"  => lang("1st Half"),
    			"2h"  => lang("2nd Half"),
    			"n"  => lang("Neither"),
    		),
    		//First Team 2 Goals
    		"422" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    			"n"  => lang("Neither"),
    		),
    		//First Team 3 Goals
    		"423" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    			"n"  => lang("Neither"),
    		),
    		//First Goal Method
    		"424" => array(
    			"s"  => lang("Shot"),
    			"h"  => lang("Header"),
    			"p"  => lang("Penalty"),
    			"fk"  => lang("Free Kick"),
    			"og"  => lang("Own Goal"),
    			"ng"  => lang("No Goal"),
    		),
    		//To win from behind
    		"425" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//1h winning margin
    		"426" => array(
    			"h1"  => lang("Home to win by 1 goal"),
    			"h2+"  => lang("Home to win by 2up goals"),
    			"d"  => lang("Score Draw"),
    			"a1"  => lang("Away to win by 1 goal"),
    			"a2+"  => lang("Away to win by 2up goal"),
    			"ng"  => lang("No Goal"),
    		),
    		//exact 2nd half goals
    		"429" => array(
    			"0"  => lang("No Goal"),
    			"1+"  => lang("1 Goal"),
    			"2"  => lang("2 Goals"),
    			"3"  => lang("3 Over"),
    		),
    		//both team to score in 1h/2h
    		"445" => array(
    			"yy"  => lang("Yes/Yes"),
    			"yn"  => lang("Yes/No"),
    			"ny"  => lang("No/Yes"),
    			"nn"  => lang("No/No"),
    		),
    		//home 1h to score/2h to score
    		"446" => array(
    			"yy"  => lang("Yes/Yes"),
    			"yn"  => lang("Yes/No"),
    			"ny"  => lang("No/Yes"),
    			"nn"  => lang("No/No"),
    		),
    		//away 1h to score/2h to score
    		"447" => array(
    			"yy"  => lang("Yes/Yes"),
    			"yn"  => lang("Yes/No"),
    			"ny"  => lang("No/Yes"),
    			"nn"  => lang("No/No"),
    		),
    		//FT winning margin 14 way
    		"601" => array(
    			"h1-2"  => lang("HomeTeam to Win by 1 to 2 points"),
    			"h3-6"  => lang("HomeTeam to Win by 3 to 6 points"),
    			"h7-9"  => lang("HomeTeam to Win by 7 to 9 points"),
    			"h10-13"  => lang("HomeTeam to Win by 10 to 13 points"),
    			"h14-16"  => lang("HomeTeam to Win by 14 to 16 points"),
    			"h17-20"  => lang("HomeTeam to Win by 17 to 20 points"),
    			"h21+"  => lang("HomeTeam to Win by 21+ points"),
    			"a1-2"  => lang("AwayTeam to Win by 1 to 2 points"),
    			"a3-6"  => lang("AwayTeam to Win by 3 to 6 points"),
    			"a7-9"  => lang("AwayTeam to Win by 7 to 9 points"),
    			"a10-13"  => lang("AwayTeam to Win by 10 to 13 points"),
    			"a14-16"  => lang("AwayTeam to Win by 14 to 16 points"),
    			"a17-20"  => lang("AwayTeam to Win by 17 to 20 points"),
    			"a21+"  => lang("AwayTeam to Win by 21+ points"),
    		),
    		//FT winning margin 12 way
    		"602" => array(
    			"h1-5"  => lang("HomeTeam to Win by 1 to 5 points"),
    			"h6-10"  => lang("HomeTeam to Win by 6 to 10 points"),
    			"h11-15"  => lang("HomeTeam to Win by 11 to 15 points"),
    			"h16-20"  => lang("HomeTeam to Win by 16 to 20 points"),
    			"h21-25"  => lang("HomeTeam to Win by 21 to 25 points"),
    			"h26+"  => lang("HomeTeam to Win by 26+ points"),
    			"a1-5"  => lang("AwayTeam to Win by 1 to 5 points"),
    			"a6-10"  => lang("AwayTeam to Win by 6 to 10 points"),
    			"a11-15"  => lang("AwayTeam to Win by 11 to 15 points"),
    			"a16-20"  => lang("AwayTeam to Win by 16 to 20 points"),
    			"a21-25"  => lang("AwayTeam to Win by 21 to 25 points"),
    			"a26+"  => lang("AwayTeam to Win by 26+ points"),
    		),
    		//FT which team to score the highest quarter
    		"603" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//FT which team to score the first basket
    		"604" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//FT which team to score the last basket
    		"605" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//1h race to x
    		"606" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//2h race to x
    		"607" => array(
    			"h"  => lang("Home"),
    			"a"  => lang("Away"),
    		),
    		//1h winning Margin 12 Way
    		"608" => array(
    			"h1-5"  => lang("HomeTeam to Win by 1 to 5 points"),
    			"h6-10"  => lang("HomeTeam to Win by 6 to 10 points"),
    			"h11-15"  => lang("HomeTeam to Win by 11 to 15 points"),
    			"h16-20"  => lang("HomeTeam to Win by 16 to 20 points"),
    			"h21-25"  => lang("HomeTeam to Win by 21 to 25 points"),
    			"h26+"  => lang("HomeTeam to Win by 26+ points"),
    			"d"  => lang("Draw"),
    			"a1-5"  => lang("AwayTeam to Win by 1 to 5 points"),
    			"a6-10"  => lang("AwayTeam to Win by 6 to 10 points"),
    			"a11-15"  => lang("AwayTeam to Win by 11 to 15 points"),
    			"a16-20"  => lang("AwayTeam to Win by 16 to 20 points"),
    			"a21-25"  => lang("AwayTeam to Win by 21 to 25 points"),
    			"a26+"  => lang("AwayTeam to Win by 26+ points"),
    		),
    		//Double chance & total goals
    		"449" => array(
    			"1xo"  => lang("Home/Draw & Over"),
    			"1xu"  => lang("Home/Draw & Under"),
    			"120"  => lang("Home/Away & Over"),
    			"12u"  => lang("Home/Away & Under"),
    			"2xo"  => lang("Away/Draw & Over"),
    			"2xu"  => lang("Away/Draw & Under"),
    		),
    		//Odd/even & total goals
    		"450" => array(
    			"oo"  => lang("Odd & Over"),
    			"ou"  => lang("Odd & Under"),
    			"eo"  => lang("Even & Over"),
    			"eu"  => lang("Even & Under"),
    		),
    		//Both Teams to score / double chance
    		"451" => array(
    			"y1x"  => lang("Yes & Home/Draw"),
    			"y12"  => lang("Yes & Home/Away"),
    			"y2x"  => lang("Yes & Away/Draw"),
    			"n1x"  => lang("No & Home/Draw"),
    			"n12"  => lang("No & Home/Away"),
    			"n2x"  => lang("No & Away/Draw"),
    		),
    		//Highest Scoreing half(2 way)
    		"452" => array(
    			"1h"  => lang("First Half"),
    			"2h"  => lang("Second Half"),
    		),
    		//Double chance & first team to score
    		"454" => array(
    			"1xh"  => lang("Home/Draw & Home"),
    			"12h"  => lang("Home/Away & Home"),
    			"2xh"  => lang("Away/Draw & Home"),
    			"1xa"  => lang("Home/Draw & Away"),
    			"12a"  => lang("Home/Away & Away"),
    			"2xa"  => lang("Away/Draw & Away"),
    			"ng"  => lang("No Goal"),
    		),
    		//Time of first goal
    		"455" => array(
    			"1-10"  => lang("00:01-10:00"),
    			"11-20"  => lang("10:01-20:00"),
    			"21-10"  => lang("20:01-30:00"),
    			"31-10"  => lang("30:01-40:00"),
    			"41-10"  => lang("40:01-50:00"),
    			"51-10"  => lang("50:01-60:00"),
    			"61-10"  => lang("60:01-70:00"),
    			"71-10"  => lang("70:01-80:00"),
    			"81-10"  => lang("80:01-90:00"),
    			"ng"  => lang("No Goal"),
    		),
    		//1h both teams to score /rsult
    		"456" => array(
    			"yh"  => lang("Yes/Home"),
    			"ya"  => lang("Yes/Away"),
    			"yd"  => lang("Yes/Draw"),
    			"nh"  => lang("No/Home"),
    			"na"  => lang("No/Away"),
    			"nd"  => lang("No/Draw"),
    		),
    		//1h both teams to score /Total goals
    		"457" => array(
    			"yo"  => lang("Yes & Over"),
    			"yu"  => lang("Yes & Under"),
    			"no"  => lang("No & Over"),
    			"nu"  => lang("No & Under"),
    		),
    		//Set x correct score
    		"1317" => array(
    			"60"  => lang("Home 6-0"),
    			"61"  => lang("Home 6-1"),
    			"62"  => lang("Home 6-2"),
    			"63"  => lang("Home 6-3"),
    			"64"  => lang("Home 6-4"),
    			"75"  => lang("Home 7-5"),
    			"76"  => lang("Home 7-6"),
    			"06"  => lang("Home 0-6"),
    			"16"  => lang("Home 1-6"),
    			"26"  => lang("Home 2-6"),
    			"36"  => lang("Home 3-6"),
    			"46"  => lang("Home 4-6"),
    			"57"  => lang("Home 5-7"),
    			"67"  => lang("Home 6-7"),
    		),
    		//winning margin 6 way
    		"2807" => array(
    			"h1-5"  => lang("Home to win by 1-5 points"),
    			"h6-10"  => lang("Home to win by 6-10 points"),
    			"h11+"  => lang("Home to win by 11+ points"),
    			"a1-5"  => lang("Away to win by 1-5 points"),
    			"a6-10"  => lang("Away to win by 6-10 points"),
    			"a11+"  => lang("Away to win by 11+ points"),
    		),
    		//1h winning margin 7 way
    		"2808" => array(
    			"h1-5"  => lang("Home to win by 1-5 points"),
    			"h6-10"  => lang("Home to win by 6-10 points"),
    			"h11+"  => lang("Home to win by 11+ points"),
    			"d"  => lang("Draw"),
    			"a1-5"  => lang("Away to win by 1-5 points"),
    			"a6-10"  => lang("Away to win by 6-10 points"),
    			"a11+"  => lang("Away to win by 11+ points"),
    		),
            //Big/Small
            "8101" => array(
                "b" => lang("Big"),
                "s" => lang("Small"),
            ),
            //Odd/Even
            "8102" => array(
                "o" => lang("Odd"),
                "e" => lang("Even"),
            ),
            //4 Seasons
            "8103" => array(
                "sp" => lang("Spring"),
                "su" => lang("Summer"),
                "au" => lang("Autumn"),
                "wi" => lang("Winter"),
            ),
            //More Odd/More Even
            "8104" => array(
                "mo" => lang("More Odd"),
                "me" => lang("More Even"),
            ),
            //Combo
            "8105" => array(
                "bo" => lang("Big/Odd"),
                "be" => lang("Big/Even"),
                "so" => lang("Small/Odd"),
                "se" => lang("Small/Even"),
            ),
            //Half Time/Full Time Exact Total Goals
            "467" => arraY(
                "g0/g0" =>lang("Half Time 0 / Full Time 0"),
                "g0/g1" =>lang("Half Time 0 / Full Time 1"),
                "g0/g2" =>lang("Half Time 0 / Full Time 2"),
                "g0/g3" =>lang("Half Time 0 / Full Time 3"),
                "g0/g4" =>lang("Half Time 0 / Full Time 4"),
                "g0/g5" =>lang("Half Time 0 / Full Time 5"),
                "g0/g6" =>lang("Half Time 0 / Full Time 6"),
                "g0/g7+" =>lang("Half Time 0 / Full Time 7+"),
                "g1/g1" =>lang("Half Time 1 / Full Time 1"),
                "g1/g2" =>lang("Half Time 1 / Full Time 2"),
                "g1/g3" =>lang("Half Time 1 / Full Time 3"),
                "g1/g4" =>lang("Half Time 1 / Full Time 4"),
                "g1/g5" =>lang("Half Time 1 / Full Time 5"),
                "g1/g6" =>lang("Half Time 1 / Full Time 6"),
                "g1/g7+" =>lang("Half Time 1 / Full Time 7+"),
                "g2/g2" =>lang("Half Time 2 / Full Time 2"),
                "g2/g3" =>lang("Half Time 2 / Full Time 3"),
                "g2/g4" =>lang("Half Time 2 / Full Time 4"),
                "g2/g5" =>lang("Half Time 2 / Full Time 5"),
                "g2/g6" =>lang("Half Time 2 / Full Time 6"),
                "g2/g7+" =>lang("Half Time 2 / Full Time 7+"),
                "g3/g3" =>lang("Half Time 3 / Full Time 3"),
                "g3/g4" =>lang("Half Time 3 / Full Time 4"),
                "g3/g5" =>lang("Half Time 3 / Full Time 5"),
                "g3/g6" =>lang("Half Time 3 / Full Time 6"),
                "g3/g7+" =>lang("Half Time 3 / Full Time 7+"),
                "g4/g4" =>lang("Half Time 4 / Full Time 4"),
                "g4/g5" =>lang("Half Time 4 / Full Time 5"),
                "g4/g6" =>lang("Half Time 4 / Full Time 6"),
                "g4/g7" =>lang("Half Time 4 / Full Time 7"),
                "g4/g8+" =>lang("Half Time 4 / Full Time 8+"),
                "g5/g5" =>lang("Half Time 5 / Full Time 5"),
                "g5/g6" =>lang("Half Time 5 / Full Time 6"),
                "g5/g7" =>lang("Half Time 5 / Full Time 7"),
                "g5/g8" =>lang("Half Time 5 / Full Time 8"),
                "g5/g9+" =>lang("Half Time 5 / Full Time 9+"),
                "g6/g6" =>lang("Half Time 6 / Full Time 6"),
                "g6/g7" =>lang("Half Time 6 / Full Time 7"),
                "g6/g8" =>lang("Half Time 6 / Full Time 8"),
                "g6/g9" =>lang("Half Time 6 / Full Time 9"),
                "g6/g10+" =>lang("Half Time 6 / Full Time 10+"),
                "g7+/g7+" =>lang("Half Time 7+ / Full Time 7+"),
            ),
            //Score Box Handicap
            "468" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //Score Box Over/Under
            "469" => array(
                "o" => lang("Equal/Over"),
                "a" => lang("Equal/Under"),
            ),
            //Corners Odd/Even (own)
            "470" => array(
                "o" => lang("Odd"),
                "a" => lang("Even"),
            ),
            //1H Corners Odd/Even (own)
            "471" => array(
                "o" => lang("Odd"),
                "a" => lang("Even"),
            ),
            //2H Corners Odd/Even
            "472" => array(
                "o" => lang("Odd"),
                "a" => lang("Even"),
            ),
            //Total Corners
            "473" => array(
                "0-5" => "0-5",
                "6-8" => "6-8",
                "9-11" => "9-11",
                "12-14" => "12-14",
                "15+" => "15+",
            ),
            //1H Total Corners (own)
            "474" => array(
                "0-2" => "0-2",
                "3-4" => "3-4",
                "5-6" => "5-6",
                "7+" => "7+",
            ),
            //Alternative Corners
            "475" => array(
                "o" => lang("Over"),
                "e" => lang("Exact"),
                "u" => lang("under"),
            ),
            //1H Alternative Corners
            "476" => array(
                "o" => lang("Over"),
                "e" => lang("Exact"),
                "u" => lang("under"),
            ),
            //Corner 3-Way Handicap
            "477" => array(
                "1" => lang("Home"),
                "x" => lang("Draw"),
                "2" => lang("Away"),
            ),
            //1H Corner 3-Way Handicap
            "478" => array(
                "1" => lang("Home"),
                "x" => lang("Draw"),
                "2" => lang("Away"),
            ),
            //Time Of First Corner
            "479" => array(
                "y" => lang("Yes"),
                "n" => lang("No"),
            ),
            //Time Of 2H First Corner
            "481" => array(
                "y" => lang("Yes"),
                "n" => lang("No"),
            ),
            //Home Team Over/Under Corners
            "482" => array(
                "o" => lang("Over"),
                "u" => lang("Under"),
            ),
            //Away Team Over/Under Corners
            "483" => array(
                "o" => lang("Over"),
                "u" => lang("Under"),
            ),
            //1H Home Team Over/Under Corners
            "484" => array(
                "o" => lang("Over"),
                "u" => lang("Under"),
            ),
            //1H Away Team Over/Under Corners
            "485" => array(
                "o" => lang("Over"),
                "u" => lang("Under"),
            ),
            //Corners Race
            "486" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
                "n" => lang('Neither'),
            ),
            //1H Corners Race
            "487" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
                "n" => lang('Neither'),
            ),
            //First Corner (2-Way)
            "488" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //1H First Corner (2-Way)
            "489" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //2H First Corner (2-Way)
            "490" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //Last Corner (2-Way)
            "491" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //1H Last Corner (2-Way)
            "492" => array(
                "h" => lang("Home"),
                "a" => lang("Away"),
            ),
            //Half Time/Full Time Total Corners
            "493" => array(
                "0/0" => "Half Time 0 / Full Time 0",
                "0/1" => "Half Time 0 / Full Time 1",
                "0/2" => "Half Time 0 / Full Time 2",
                "0/3" => "Half Time 0 / Full Time 3",
                "0/4" => "Half Time 0 / Full Time 4",
                "0/5" => "Half Time 0 / Full Time 5",
                "0/6" => "Half Time 0 / Full Time 6",
                "0/7" => "Half Time 0 / Full Time 7",
                "0/8+" => "Half Time 0 / Full Time 8+",
                "1/1" => "Half Time 1 / Full Time 1",
                "1/2" => "Half Time 1 / Full Time 2",
                "1/3" => "Half Time 1 / Full Time 3",
                "1/4" => "Half Time 1 / Full Time 4",
                "1/5" => "Half Time 1 / Full Time 5",
                "1/6" => "Half Time 1 / Full Time 6",
                "1/7" => "Half Time 1 / Full Time 7",
                "1/8" => "Half Time 1 / Full Time 8",
                "1/9+" => "Half Time 1 / Full Time 9+",
                "2/2" => "Half Time 2 / Full Time 2",
                "2/3" => "Half Time 2 / Full Time 3",
                "2/4" => "Half Time 2 / Full Time 4",
                "2/5" => "Half Time 2 / Full Time 5",
                "2/6" => "Half Time 2 / Full Time 6",
                "2/7" => "Half Time 2 / Full Time 7",
                "2/8" => "Half Time 2 / Full Time 8",
                "2/9" => "Half Time 2 / Full Time 9",
                "2/10+" => "Half Time 2 / Full Time 10+",
                "3/3" => "Half Time 3 / Full Time 3",
                "3/4" => "Half Time 3 / Full Time 4",
                "3/5" => "Half Time 3 / Full Time 5",
                "3/6" => "Half Time 3 / Full Time 6",
                "3/7" => "Half Time 3 / Full Time 7",
                "3/8" => "Half Time 3 / Full Time 8",
                "3/9" => "Half Time 3 / Full Time 9",
                "3/10" => "Half Time 3 / Full Time 10",
                "3/11+" => "Half Time 3 / Full Time 11+",
                "4/4" => "Half Time 4 / Full Time 4",
                "4/5" => "Half Time 4 / Full Time 5",
                "4/6" => "Half Time 4 / Full Time 6",
                "4/7" => "Half Time 4 / Full Time 7",
                "4/8" => "Half Time 4 / Full Time 8",
                "4/9" => "Half Time 4 / Full Time 9",
                "4/10" => "Half Time 4 / Full Time 10",
                "4/11" => "Half Time 4 / Full Time 11",
                "4/12+" => "Half Time 4 / Full Time 12+",
                "5/5" => "Half Time 5 / Full Time 5",
                "5/6" => "Half Time 5 / Full Time 6",
                "5/7" => "Half Time 5 / Full Time 7",
                "5/8" => "Half Time 5 / Full Time 8",
                "5/9" => "Half Time 5 / Full Time 9",
                "5/10" => "Half Time 5 / Full Time 10",
                "5/11" => "Half Time 5 / Full Time 11",
                "5/12" => "Half Time 5 / Full Time 12",
                "5/13+" => "Half Time 5 / Full Time 13+",
                "6/6" => "Half Time 6 / Full Time 6",
                "6/7" => "Half Time 6 / Full Time 7",
                "6/8" => "Half Time 6 / Full Time 8",
                "6/9" => "Half Time 6 / Full Time 9",
                "6/10" => "Half Time 6 / Full Time 10",
                "6/11" => "Half Time 6 / Full Time 11",
                "6/12" => "Half Time 6 / Full Time 12",
                "6/13" => "Half Time 6 / Full Time 13",
                "6/14+" => "Half Time 6 / Full Time 14+",
                "7/7" => "Half Time 7 / Full Time 7",
                "7/8" => "Half Time 7 / Full Time 8",
                "7/9" => "Half Time 7 / Full Time 9",
                "7/10" => "Half Time 7 / Full Time 10",
                "7/11" => "Half Time 7 / Full Time 11",
                "7/12" => "Half Time 7 / Full Time 12",
                "7/13" => "Half Time 7 / Full Time 13",
                "7/14" => "Half Time 7 / Full Time 14",
                "7/15+" => "Half Time 7 / Full Time 15+",
                "8/8" => "Half Time 8 / Full Time 8",
                "8/9" => "Half Time 8 / Full Time 9",
                "8/10" => "Half Time 8 / Full Time 10",
                "8/11" => "Half Time 8 / Full Time 11",
                "8/12" => "Half Time 8 / Full Time 12",
                "8/13" => "Half Time 8 / Full Time 13",
                "8/14" => "Half Time 8 / Full Time 14",
                "8/15+" => "Half Time 8 / Full Time 15+",
                "9/9" => "Half Time 9 / Full Time 9",
                "9/10" => "Half Time 9 / Full Time 10",
                "9/11" => "Half Time 9 / Full Time 11",
                "9/12" => "Half Time 9 / Full Time 12",
                "9/13" => "Half Time 9 / Full Time 13",
                "9/14" => "Half Time 9 / Full Time 14",
                "9/15+" => "Half Time 9 / Full Time 15+",
                "AOS" => "AOS",
            ),
            //1H Correct Corners
            "494" => array(
                "1-0" => "1-0",
                "2-0" => "2-0",
                "2-1" => "2-1",
                "3-0" => "3-0",
                "3-1" => "3-1",
                "3-2" => "3-2",
                "4-0" => "4-0",
                "4-1" => "4-1",
                "4-2" => "4-2",
                "4-3" => "4-3",
                "5-0" => "5-0",
                "5-1" => "5-1",
                "5-2" => "5-2",
                "5-3" => "5-3",
                "5-4" => "5-4",
                "0-1" => "0-1",
                "0-2" => "0-2",
                "1-2" => "1-2",
                "0-3" => "0-3",
                "1-3" => "1-3",
                "2-3" => "2-3",
                "0-4" => "0-4",
                "1-4" => "1-4",
                "2-4" => "2-4",
                "3-4" => "3-4",
                "0-5" => "0-5",
                "1-5" => "1-5",
                "2-5" => "2-5",
                "3-5" => "3-5",
                "4-5" => "4-5",
                "0-0" => "0-0",
                "1-1" => "1-1",
                "2-2" => "2-2",
                "3-3" => "3-3",
                "4-4" => "4-4",
                "AOS" => "AOS",
            ),
            //2H Correct Corners
            "495" => array(
                "1-0" => "1-0",
                "2-0" => "2-0",
                "2-1" => "2-1",
                "3-0" => "3-0",
                "3-1" => "3-1",
                "3-2" => "3-2",
                "4-0" => "4-0",
                "4-1" => "4-1",
                "4-2" => "4-2",
                "4-3" => "4-3",
                "5-0" => "5-0",
                "5-1" => "5-1",
                "5-2" => "5-2",
                "5-3" => "5-3",
                "5-4" => "5-4",
                "0-1" => "0-1",
                "0-2" => "0-2",
                "1-2" => "1-2",
                "0-3" => "0-3",
                "1-3" => "1-3",
                "2-3" => "2-3",
                "0-4" => "0-4",
                "1-4" => "1-4",
                "2-4" => "2-4",
                "3-4" => "3-4",
                "0-5" => "0-5",
                "1-5" => "1-5",
                "2-5" => "2-5",
                "3-5" => "3-5",
                "4-5" => "4-5",
                "0-0" => "0-0",
                "1-1" => "1-1",
                "2-2" => "2-2",
                "3-3" => "3-3",
                "4-4" => "4-4",
                "AOS" => "AOS",
            ),
            //Corner Highest Scoring Half
            "496" => array(
                "1h" => lang("First Half"),
                "2h" => lang("Second Half"),
                "tie" => lang("Tie"),
            ),
            //Corner Highest Scoring Half(2-Way)
            "497" => array(
                "1h" => lang("First Half"),
                "2h" => lang("Second Half"),
            ),
            "618" => array(
                "0" => lang("home + away full time score last digit is 0"),
                "1" => lang("home + away full time score last digit is 1"),
                "2" => lang("home + away full time score last digit is 2"),
                "3" => lang("home + away full time score last digit is 3"),
                "4" => lang("home + away full time score last digit is 4"),
                "5" => lang("home + away full time score last digit is 5"),
                "6" => lang("home + away full time score last digit is 6"),
                "7" => lang("home + away full time score last digit is 7"),
                "8" => lang("home + away full time score last digit is 8"),
                "9" => lang("home + away full time score last digit is 9"),
            ),
            "619" => array(
                "0" => lang("home team full time score last digit is 0"),
                "1" => lang("home team full time score last digit is 1"),
                "2" => lang("home team full time score last digit is 2"),
                "3" => lang("home team full time score last digit is 3"),
                "4" => lang("home team full time score last digit is 4"),
                "5" => lang("home team full time score last digit is 5"),
                "6" => lang("home team full time score last digit is 6"),
                "7" => lang("home team full time score last digit is 7"),
                "8" => lang("home team full time score last digit is 8"),
                "9" => lang("home team full time score last digit is 9"),
            ),
            "620" => array(
                "0" => lang("away team full time score last digit is 0"),
                "1" => lang("away team full time score last digit is 1"),
                "2" => lang("away team full time score last digit is 2"),
                "3" => lang("away team full time score last digit is 3"),
                "4" => lang("away team full time score last digit is 4"),
                "5" => lang("away team full time score last digit is 5"),
                "6" => lang("away team full time score last digit is 6"),
                "7" => lang("away team full time score last digit is 7"),
                "8" => lang("away team full time score last digit is 8"),
                "9" => lang("away team full time score last digit is 9"),
            ),
            "621" => array(
                "0" => lang("home + away half time score last digit is 0"),
                "1" => lang("home + away half time score last digit is 1"),
                "2" => lang("home + away half time score last digit is 2"),
                "3" => lang("home + away half time score last digit is 3"),
                "4" => lang("home + away half time score last digit is 4"),
                "5" => lang("home + away half time score last digit is 5"),
                "6" => lang("home + away half time score last digit is 6"),
                "7" => lang("home + away half time score last digit is 7"),
                "8" => lang("home + away half time score last digit is 8"),
                "9" => lang("home + away half time score last digit is 9"),
            ),
            "622" => array(
                "0" => lang("home team half time score last digit is 0"),
                "1" => lang("home team half time score last digit is 1"),
                "2" => lang("home team half time score last digit is 2"),
                "3" => lang("home team half time score last digit is 3"),
                "4" => lang("home team half time score last digit is 4"),
                "5" => lang("home team half time score last digit is 5"),
                "6" => lang("home team half time score last digit is 6"),
                "7" => lang("home team half time score last digit is 7"),
                "8" => lang("home team half time score last digit is 8"),
                "9" => lang("home team half time score last digit is 9"),
            ),
            "623" => array(
                "0" => lang("away team half time score last digit is 0"),
                "1" => lang("away team half time score last digit is 1"),
                "2" => lang("away team half time score last digit is 2"),
                "3" => lang("away team half time score last digit is 3"),
                "4" => lang("away team half time score last digit is 4"),
                "5" => lang("away team half time score last digit is 5"),
                "6" => lang("away team half time score last digit is 6"),
                "7" => lang("away team half time score last digit is 7"),
                "8" => lang("away team half time score last digit is 8"),
                "9" => lang("away team half time score last digit is 9"),
            ),
            "624" => array(
                "0" => lang("home + away 2nd half time score last digit is 0"),
                "1" => lang("home + away 2nd half time score last digit is 1"),
                "2" => lang("home + away 2nd half time score last digit is 2"),
                "3" => lang("home + away 2nd half time score last digit is 3"),
                "4" => lang("home + away 2nd half time score last digit is 4"),
                "5" => lang("home + away 2nd half time score last digit is 5"),
                "6" => lang("home + away 2nd half time score last digit is 6"),
                "7" => lang("home + away 2nd half time score last digit is 7"),
                "8" => lang("home + away 2nd half time score last digit is 8"),
                "9" => lang("home + away 2nd half time score last digit is 9"),
            ),
            "625" => array(
                "0" => lang("home team 2nd half time score last digit is 0"),
                "1" => lang("home team 2nd half time score last digit is 1"),
                "2" => lang("home team 2nd half time score last digit is 2"),
                "3" => lang("home team 2nd half time score last digit is 3"),
                "4" => lang("home team 2nd half time score last digit is 4"),
                "5" => lang("home team 2nd half time score last digit is 5"),
                "6" => lang("home team 2nd half time score last digit is 6"),
                "7" => lang("home team 2nd half time score last digit is 7"),
                "8" => lang("home team 2nd half time score last digit is 8"),
                "9" => lang("home team 2nd half time score last digit is 9"),
            ),
            "626" => array(
                "0" => lang("away team 2nd half time score last digit is 0"),
                "1" => lang("away team 2nd half time score last digit is 1"),
                "2" => lang("away team 2nd half time score last digit is 2"),
                "3" => lang("away team 2nd half time score last digit is 3"),
                "4" => lang("away team 2nd half time score last digit is 4"),
                "5" => lang("away team 2nd half time score last digit is 5"),
                "6" => lang("away team 2nd half time score last digit is 6"),
                "7" => lang("away team 2nd half time score last digit is 7"),
                "8" => lang("away team 2nd half time score last digit is 8"),
                "9" => lang("away team 2nd half time score last digit is 9"),
            ),
            "627" => array(
                "0" => lang("home + away Quarter X score last digit is 0"),
                "1" => lang("home + away Quarter X score last digit is 1"),
                "2" => lang("home + away Quarter X score last digit is 2"),
                "3" => lang("home + away Quarter X score last digit is 3"),
                "4" => lang("home + away Quarter X score last digit is 4"),
                "5" => lang("home + away Quarter X score last digit is 5"),
                "6" => lang("home + away Quarter X score last digit is 6"),
                "7" => lang("home + away Quarter X score last digit is 7"),
                "8" => lang("home + away Quarter X score last digit is 8"),
                "9" => lang("home + away Quarter X score last digit is 9"),
            ),
            "628" => array(
                "0" => lang("home team Quarter X score last digit is 0"),
                "1" => lang("home team Quarter X score last digit is 1"),
                "2" => lang("home team Quarter X score last digit is 2"),
                "3" => lang("home team Quarter X score last digit is 3"),
                "4" => lang("home team Quarter X score last digit is 4"),
                "5" => lang("home team Quarter X score last digit is 5"),
                "6" => lang("home team Quarter X score last digit is 6"),
                "7" => lang("home team Quarter X score last digit is 7"),
                "8" => lang("home team Quarter X score last digit is 8"),
                "9" => lang("home team Quarter X score last digit is 9"),
            ),
            "629" => array(
                "0" => lang("away team Quarter X score last digit is 0"),
                "1" => lang("away team Quarter X score last digit is 1"),
                "2" => lang("away team Quarter X score last digit is 2"),
                "3" => lang("away team Quarter X score last digit is 3"),
                "4" => lang("away team Quarter X score last digit is 4"),
                "5" => lang("away team Quarter X score last digit is 5"),
                "6" => lang("away team Quarter X score last digit is 6"),
                "7" => lang("away team Quarter X score last digit is 7"),
                "8" => lang("away team Quarter X score last digit is 8"),
                "9" => lang("away team Quarter X score last digit is 9"),
            ),
            "630" => array(
                "1" => lang("home team"),
                "x" => lang("draw"),
                "2" => lang("away team"),
            ),
            "631" => array(
                "hd" => lang("Home or Draw"),
                "da" => lang("Draw or Away"),
                "ha" => lang("Home or Away"),
            ),
            "632" => array(
                "oooo" => lang("odd/odd/odd/odd"),
                "oooe" => lang("odd/odd/odd/even"),
                "ooeo" => lang("odd/odd/even/odd"),
                "oeoo" => lang("odd/even/odd/odd"),
                "eooo" => lang("even/odd/odd/odd"),
                "eeee" => lang("even/even/even/even"),
                "eeeo" => lang("even/even/even/odd"),
                "eeoe" => lang("even/even/odd/even"),
                "eoee" => lang("even/odd/even/even"),
                "oeee" => lang("odd/even/even/even"),
                "ooee" => lang("odd/odd/even/even"),
                "oeoe" => lang("odd/even/odd/even"),
                "eoeo" => lang("even/odd/even/odd"),
                "eeoo" => lang("even/even/odd/odd"),
                "eooe" => lang("even/odd/odd/even"),
                "oeeo" => lang("odd/even/even/odd"),
            ),
            "633" => array(
                "hh" => lang("home/home"),
                "hd" => lang("home/draw"),
                "ha" => lang("home/away"),
                "dh" => lang("draw/home"),
                "dd" => lang("draw/draw"),
                "da" => lang("draw/away"),
                "ah" => lang("away/home"),
                "ad" => lang("away/draw"),
                "aa" => lang("away/away"),
            ),
            "634" => array(
                "hh" => lang("home/home"),
                "hd" => lang("home/draw"),
                "ha" => lang("home/away"),
                "dh" => lang("draw/home"),
                "dd" => lang("draw/draw"),
                "da" => lang("draw/away"),
                "ah" => lang("away/home"),
                "ad" => lang("away/draw"),
                "aa" => lang("away/away"),
            ),
            "635" => array(
                "h" => lang("home team"),
                "a" => lang("away team"),
            ),
            "636" => array(
                "h" => lang("home team"),
                "a" => lang("away team"),
            ),
            "637" => array(
                "h" => lang("home team"),
                "a" => lang("away team"),
            ),
            "638" => array(
                "o" => lang("over"),
                "u" => lang("under"),
            ),
            "639" => array(
                "o" => lang("over"),
                "u" => lang("under"),
            ),
            "640" => array(
                "0" => lang("no draw quarter"),
                "1" => lang("1 and above draw quarters"),
            ),
            "641" => array(
                "o" => lang("over"),
                "u" => lang("under"),
            ),
            "642" => array(
                "h12" => lang("home win 1st & 2nd quarter"),
                "h13" => lang("home win 1st & 3rd quarter"),
                "h14" => lang("home win 1st & 4th quarter"),
                "h23" => lang("home win 2nd & 3rd quarter"),
                "h24" => lang("home win 2nd & 4th quarter"),
                "h34" => lang("home win 3rd & 4th quarter"),
                "a12" => lang("away win 1st & 2nd quarter"),
                "a13" => lang("away win 1st & 3rd quarter"),
                "a14" => lang("away win 1st & 4th quarter"),
                "a23" => lang("away win 2nd & 3rd quarter"),
                "a24" => lang("away win 2nd & 4th quarter"),
                "a34" => lang("away win 3rd & 4th quarter"),
                "aos12" => lang("aos of 1st & 2nd quarter"),
                "aos13" => lang("aos of 1st & 3rd quarter"),
                "aos14" => lang("aos of 1st & 4th quarter"),
                "aos23" => lang("aos of 2nd & 3rd quarter"),
                "aos24" => lang("aos of 2nd & 4th quarter"),
                "aos34" => lang("aos of 3rd & 4th quarter"),
            ),
            "643" => array(
                "1q" => lang("1st quarter"),
                "2q" => lang("2nd quarter"),
                "3q" => lang("3rd quarter"),
                "4q" => lang("4th quarter"),
                "tie" => lang("tie"),
            ),
            "644" => array(
                "2:1" => lang("2-1"),
                "3:0" => lang("3-0"),
                "3:1" => lang("3-1"),
                "4:0" => lang("4-0"),
                "2:2" => lang("2-2"),
                "1:2" => lang("1-2"),
                "0:3" => lang("0-3"),
                "1:3" => lang("1-3"),
                "0:4" => lang("0-4"),
                "aos" => lang("aos"),
            ),
            "645" => array(
                "hh" => lang("home/home"),
                "hd" => lang("home/draw"),
                "ha" => lang("home/away"),
                "dh" => lang("draw/home"),
                "da" => lang("draw/away"),
                "ah" => lang("away/home"),
                "ad" => lang("away/draw"),
                "aa" => lang("away/away"),
            ),
            "646" => array(
               "ho" => lang("home team & over"),
               "hu" => lang("home team & under"),
               "ao" => lang("away team & over"),
               "au" => lang("away team & under"),
            ),
            "228" => array(
    			"o" => lang("Over"),
    			"u" => lang("Under"),
    		),
    		"229" => array(
    			"h" => lang("Home"),
    			"a" => lang("Away"),
    		),
    		"3613" => array(
    			"odd" => lang("Odd"),
    			"even" => lang("Even"),
    			"male" => lang("Male"),
    			"female" => lang("Female"),
    		),
    	);

        if( ($gameRecord['sport_type'] == self:: HAPPY_5_SPORTTYPE) &&  !empty($gameRecord['bet_choice']) ){
            $place_bet = $gameRecord['bet_choice'];
        }
        //check if bet penalty shoot out Awa
        if($bet_type == self::BETTYPE_PENALTYSHOOTOUTCOMBO){
            $btype_detail = array(
                "h" => lang('Home'),
                "a" => lang('Away'),
                "s" => lang('Goal'),
                "m" => lang('Miss'),
            );

            $bet_array = explode(":", $place_bet);
            $odds = isset($bet_array[1]) ? "@".@$bet_array[1] : "";
            $bet_str = isset($bet_array[0] ) ? @$bet_array[0] : "";
            $bet_array = str_split($bet_str);

            $home_or_away = isset($bet_array[0]) ? $btype_detail[$bet_array[0]] : "";
            $round = isset($bet_array[1]) ? lang('Round'). " " .$bet_array[1] : "";
            $goal_or_miss = isset($bet_array[2]) ? $btype_detail[$bet_array[2]] : "";

            return  $home_or_away . " " . $round . " ". $goal_or_miss . $odds;
        }
    	//check if over  under
    	if (in_array($bet_type, self::OVERUNDER_BET)) {
    		$bet_type = "OVERUNDER_BET";
		}
		//check if odd even
		if (in_array($bet_type, self::ODDEVEN_BET)) {
    		$bet_type = "ODDEVEN_BET";
		}
		//check if 1x2
		if (in_array($bet_type, self::ONEXTWO_BET)) {
    		$bet_type = "ONEXTWO_BET";
    		$bet = @$bet_type_info[$bet_type][$place_bet];
    		$str = str_replace("1X2",$bet,$bet_name);
    		$str = str_replace("1H","HT.",$str);//replace all first half to HT
    		return $str;
		}
		//check if correct score
		if (in_array($bet_type, self::CORRECT_SCORE_BET)) {
    		$str =str_replace(":",'-',$place_bet) ;
    		return $str;
		}
		//check if YES/NO
		if (in_array($bet_type, self::YES_NO_BET)) {
    		$bet_type = "YES_NO_BET";
		}
		//check if HALF_SCORING_BET
		if (in_array($bet_type, self::HALF_SCORING_BET)) {
    		$bet_type = "HALF_SCORING_BET";
		}
		//check if DOUBLE_CHANCE_BET
		if (in_array($bet_type, self::DOUBLE_CHANCE_BET)) {
    		$bet_type = "DOUBLE_CHANCE_BET";
		}
		//check if FIRST_LAST_CORNER_BET
		if (in_array($bet_type, self::FIRST_LAST_CORNER_BET)) {
    		$bet_type = "FIRST_LAST_CORNER_BET";
		}
    	if (array_key_exists($bet_type,$bet_type_info)){
    		if (array_key_exists($place_bet,$bet_type_info[$bet_type])){
    			$bet = @$bet_type_info[$bet_type][$place_bet];
                if (in_array($bet_type, self::QUARTERXY_BETTYPE)) {
                    if(!empty($trans_id)){
                        $data = $this->getBetDetailByTransId($trans_id);
                        if(isset($data['bet_tag']) && !empty($data['bet_tag'])){
                            if (strpos($data['bet_tag'], '-') !== false) {
                                $data = explode("-",$data['bet_tag']);
                                $x = isset($data['0']) ? $data['0'] : null;
                                $y = isset($data['1']) ? $data['1'] : null;
                                $str = str_replace("X",$x,$bet);
                                $str = str_replace("Y",$y,$str);
                                return $str;
                            }
                            $str = str_replace("X",+$data['bet_tag'],$bet);
                            return $str;
                        }
                    }
                }
    			return $bet;
    		}
			return $place_bet;
		}
		return $place_bet;
    }

	public function getOddsType($id = null){
		$array = array(
			"1" => lang("MY"),//Malay Odds
			"2" => lang("HK"),//Hongkong Odds
			"3" => lang("DEC"),//Decimal Odd
			"4" => lang("IN"),//INdo Odds
			"5" => lang("US"),//American Odds
            "0" => lang("Special"),//doesn't belong to any existing odds type (OGP-18540)
		);
		return $array[$id];
	}

	public function getBetType($id = null,$trans_id = null){
		$bet_type = array(
			"1" =>"Handicap",
			"2" =>"Odd/Even",
			"3" =>"Over/Under",
			"4" =>"Correct Score",
			"5" =>"FT.1X2",
			"6" =>"Total Goal",
			"7" =>"1H Handicap",
			"8" =>"1H Over/Under",
			"9" =>"Mix Parlay",
			"10" =>"Outright",
			"11" =>"Total Corners",
			"12" =>"1H Odd/Even",
			"13" =>"Clean Sheet",
			"14" =>"First Goal/Last Goal",
			"15" =>"1H 1X2",
			"16" =>"HT/FT",
			"17" =>"2H Handicap",
			"18" =>"2H Over/Under",
			"19" =>"Substitutes",
			"20" =>"Moneyline",
			"21" =>"1H Moneyline",
			"22" =>"Next Goal",
			"23" =>"Next Corner",
			"24" =>"Double Chance",
			"25" =>"Draw No Bet",
			"26" =>"Both/One/Neither Team To Score",
			"27" =>"To Win To Nil",
			"28" =>"3-Way Handicap",
			"29" =>"System Parlay",
			"30" =>"1H Correct Score",
			"31" =>"Win",
			"32" =>"Place",
			"33" =>"Win/Place",
			"38" =>"Single Match Parlay",
			"41" =>"Win.UK Tote",
			"42" =>"Place.UK Tote",
			"43" =>"Win/Place.UK Tote",
			"51" =>"5 min O/U",
			"52" =>"15 min O/U",
			"53" =>"30 min O/U",
			"54" =>"45 min O/U",
			"55" =>"60 min O/U",
			"56" =>"End of day O/U",
			"61" =>"5 min OE",
			"62" =>"15 min OE",
			"63" =>"30 min OE",
			"64" =>"45 min OE",
			"65" =>"60 min OE",
			"66" =>"End of day OE",
			"71" =>"Casino Games",
			"72" =>"Casino Progressive Bonus",
			"73" =>"Bingo",
			"81" =>"1st Ball O/U",
			"82" =>"Last Ball O/U",
			"83" =>"1st Ball O/E",
			"84" =>"Last Ball O/E",
			"85" =>"Over/Under",
			"86" =>"Odd/Even",
			"87" =>"Next High/Low",
			"88" =>"Warrior",
			"89" =>"Next Combo",
			"90" =>"Number Wheel",
            "91" =>"Next R/B",
			"121" =>"Home No Bet",
			"122" =>"Away No Bet",
			"123" =>"Draw/No Draw",
			"124" =>"FT.1X2 HDP",
			"125" =>"1H 1X2 HDP",
			"126" =>"1H Total Goal",
			"127" =>"1H First Goal/Last Goal",
			"128" =>"HT/FT Odd/Even",
			"129" =>"Exact 1H Goal",
			"130" =>"Exact Goal",
			"131" =>"Home Team Total Goal",
			"132" =>"Away Team Total Goal",
			"133" =>"Home To Win Both Halves",
			"134" =>"Away To Win Both Halves",
			"135" =>"Penalty Shootout",
			"136" =>"1H Home Team Odd/Even",
			"137" =>"Home Team Odd/Even",
			"138" =>"1H Away Team Odd/Even",
			"139" =>"Away Team Odd/Even",
			"140" =>"Highest Scoring Half",
			"141" =>"Highest Scoring Half Home Team",
			"142" =>"Highest Scoring Half Away Team",
			"143" =>"1H Result/Total Goals",
			"144" =>"Result/Total Goals",
			"145" =>"Both Teams To Score",
			"146" =>"2H Both Teams To Score",
			"147" =>"Home To Score In Both Halves",
			"148" =>"Away To Score In Both Halves",
			"149" =>"Home To Win Either Half",
			"150" =>"Away To Win Either Half",
			"151" =>"1H Double Chance",
			"152" =>"Half Time/Full Time Correct Score",
			"153" =>"FT. Game HDP",
			"154" =>"Set X Winner",
			"155" =>"Set X Game Handicap",
			"156" =>"Set X Total Game Over/Under",
			"157" =>"Odd/Even (3rd)",
			"158" =>"Correct Score",
			"159" =>"Exact Total Goals",
			"160" =>"Next Goal (3rd)",
			"161" =>"Exact Home Team Goals",
			"162" =>"Exact Away Team Goals",
			"163" =>"Result/Total Goals (3rd)",
			"164" =>"Extra Time Next Goal",
			"165" =>"Extra Time 1H Correct Score",
			"166" =>"Extra Time Correct Score",
			"167" =>"Extra Time 1H 1X2",
			"168" =>"Who Advances To Next Round",
			"169" =>"Next Goal Time",
			"170" =>"Teams To Score",
			"171" =>"Winning Margin",
			"172" =>"Result And First Team To Score",
			"173" =>"Extra Time Yes/No",
			"174" =>"Extra Time And Goal",
			"175" =>"Match Decided Method",
			"176" =>"First Ten Minutes 1X2",
			"177" =>"2H 1X2",
			"178" =>"2H Over/Under (3rd)",
			"179" =>"Exact 1H Goals (3rd)",
			"180" =>"1H Next Goal",
			"181" =>"1H Exact Home Team Goals",
			"182" =>"1H Exact Away Team Goals",
			"183" =>"2H Handicap (3rd)",
			"184" =>"2H Odd/Even",
			"185" =>"2H Draw No Bet",
			"186" =>"2H Double Chance",
			"187" =>"Exact 2H Goals (3rd)",
			"188" =>"1H Both Teams To Score",
			"189" =>"Both Halves Over 1.5 Yes/No",
			"190" =>"Both Halves Under 1.5 Yes/No",
			"191" =>"1H Draw No Bet",
			"192" =>"First Goal Specific Time (10 min)",
			"193" =>"First Goal Specific Time (15 min)",
			"194" =>"Corners Odd/Even",
			"195" =>"Home Team Exact Corners",
			"196" =>"Away Team Exact Corners",
			"197" =>"Home Team Total Corners Over/Under",
			"198" =>"Away Team Total Corners Over/Under",
			"199" =>"Total Corners (3rd)",
			"200" =>"1H Home Team Exact Corners",
			"201" =>"1H Away Team Exact Corners",
			"202" =>"1H Total Corners",
			"203" =>"1H Corners Odd/Even",
			"204" =>"1H Home Corner Over/Under",
			"205" =>"1H Away Corner Over/Under",
			"206" =>"First Corner",
			"207" =>"1H First Corner",
			"208" =>"Last Corner",
			"209" =>"1H Last Corner",
			"210" =>"Player Sent Off",
			"211" =>"1H Player Sent Off",
			"212" =>"Home Team Player Sent Off",
			"213" =>"1H Home Team Player Sent Off",
			"214" =>"Away Team Player Sent Off",
			"215" =>"1H Away Team Player Sent Off",
			"216" =>"Goal Scorer - Anytime",
			"217" =>"Goal Scorer - First",
			"218" =>"FT. Point HDP",
			"219" =>"Set X Point Handicap",
			"220" =>"Set X Total Point Over/Under",
			"221" =>"Next 1 Minute",
			"222" =>"Next 5 Minutes",
			"223" =>"What will happen first in next 1 minute",
			"224" =>"What will happen first in next 5 minutes",
			"225" =>"Next 1 Minute Set Piece",
			"226" =>"Which combination will happen first in next 1 min",
			"227" =>"Which combination will happen first in next 5 mins",
			"301" =>"% Handicap",
			"302" =>"% Over/Under",
			"303" =>"1H % Handicap",
			"304" =>"1H % Over/Under",
			"401" =>"Home Team Over/Under",
			"402" =>"Away Team Over/Under",
			"403" =>"1H Home Team Over/Under",
			"404" =>"1H Away Team Over/Under",
			"405" =>"2H Correct Score",
			"406" =>"Exact Total Goals (own)",
			"407" =>"Exact Home Team Goals (own)",
			"408" =>"Winning Margin (own)",
			"409" =>"Exact Away Team Goals (own)",
			"410" =>"1H Double Chance (own)",
			"411" =>"1H Draw No Bet (own)",
			"412" =>"Exact 1H Goals",
			"413" =>"Correct Score (AOS)",
			"414" =>"1H Correct Score (AOS)",
			"415" =>"Result and First Team To Score (own)",
			"416" =>"Half Time/Full Time Correct Score (AOS)",
			"417" =>"Both Teams To Score/Result",
			"418" =>"Both Teams To Score/Total Goals",
			"419" =>"Which Half First Goal",
			"420" =>"Home Team Which Half First Goal",
			"421" =>"Away Team Which Half First Goal",
			"422" =>"First Team 2 Goals",
			"423" =>"First Team 3 Goals",
			"424" =>"First Goal Method",
			"425" =>"To Win From Behind",
			"426" =>"1H Winning Margin",
			"427" =>"1H Both Teams To Score (own)",
			"428" =>"2H Odd/Even (own)",
			"429" =>"Exact 2H Goals",
			"430" =>"2H 1X2 (own)",
			"431" =>"2H Double Chance (own)",
			"432" =>"2H Draw No Bet (own)",
			"433" =>"2H Both Teams To Score (own)",
			"434" =>"Both Halves Over 1.5 Yes/No (own)",
			"435" =>"Both Halves Under 1.5 Yes/No (own)",
			"436" =>"Home To Score In Both Halves (own)",
			"437" =>"Away To Score In Both Halves (own)",
			"438" =>"Home To Win Both Halves (own)",
			"439" =>"Away To Win Both Halves (own)",
			"440" =>"Home To Win Either Half (own)",
			"441" =>"Away To Win Either Half (own)",
			"442" =>"Highest Scoring Half (own)",
			"443" =>"Highest Scoring Half Home Team (own)",
			"444" =>"Highest Scoring Half Away Team (own)",
			"445" =>"Both Teams To Score In 1H/2H",
			"446" =>"Home 1H To Score/2H To Score",
			"447" =>"Away 1H To Score/2H To Score",
			"448" =>"Last Team To Score",
			"449" =>"Double Chance/Total Goals",
			"450" =>"Odd Even/Total Goals",
			"451" =>"Both Teams to Score/Double Chance",
			"452" =>"Highest Scoring Half (2 Way)",
			"453" =>"1H 3-Way Handicap",
			"454" =>"Double Chance/First Team To Score",
			"455" =>"Time of First Goal",
			"456" =>"1H Both Teams To Score/Result",
			"457" =>"1H Both Teams To Score/Total Goals",
			"458" =>"Asian 1X2",
			"459" =>"1H Asian 1X2",
			"460" =>"Which Team Will Win By 5+ Goals",
			"461" =>"Home Team Over/Under (dec)",
			"462" =>"Away Team Over/Under (dec)",
			"463" =>"1H Home Team Over/Under (dec)",
			"464" =>"1H Away Team Over/Under (dec)",
			"500" =>"Charge Commission",
			"501" =>"Match Winner",
			"502" =>"Match Winner 1X2",
			"599" =>"Others",
			"601" =>"Winning Margin 14 Way",
			"602" =>"Winning Margin 12 Way",
			"603" =>"Which Team To Score The Highest Quarter",
			"604" =>"Which Team To Score The First Basket",
			"605" =>"Which Team To Score The Last Basket",
			"606" =>"1H Race To X Points",
			"607" =>"2H Race To X Points",
			"608" =>"1H Winning Margin 13 Way",
			"609" =>"Quarter X Handicap",
			"610" =>"Quarter X Over/Under",
			"611" =>"Quarter X Odd/Even",
			"612" =>"Quarter X Moneyline",
			"613" =>"Quarter X Race To Y Points",
			"614" =>"Quarter X Winning Margin 7 Way",
			"615" =>"Quarter X Home Team Over/Under",
			"616" =>"Quarter X Away Team Over/Under",
			"617" =>"Quarter X Which Team To Score The Last Basket",
			"2701" =>"FT.1X2",
			"2702" =>"1H 1X2",
			"2703" =>"Over/Under",
			"2704" =>"1H Over/Under",
			"2705" =>"Handicap",
			"2706" =>"1H Handicap",
			"2707" =>"Correct Score",
			"2799" =>"Mix Parlay",
			"2801" =>"Match Winner",
			"2802" =>"1H Winner",
			"2803" =>"Over/Under",
			"2804" =>"1H Over/Under",
			"2805" =>"Handicap",
			"2806" =>"1H Handicap",
			"2807" =>"Winning Margin 6 Way",
			"2808" =>"1H Winning Margin 7 Way",
			"2809" =>"FT race to",
			"2811" =>"Home Team Over/Under",
			"2812" =>"Away Team Over/Under",
			"1201" =>"Handicap",
			"1203" =>"Over/under 2.5 Goals",
			"1204" =>"Correct Score",
			"1205" =>"1X2",
			"1206" =>"Total Goal",
			"1220" =>"Player Win",
			"1231" =>"Win",
			"1232" =>"Place",
			"1233" =>"Win/Place",
			"1235" =>"Score Bet",
			"1236" =>"Total Points",
            "1501" =>"Keno 1",
            "1502" =>"Keno 2",
            "1503" =>"Keno 3",
            "1504" =>"Keno 4",
            "1505" =>"Keno 5",
            "1506" =>"Keno 6",
            "1507" =>"Keno 7",
            "1508" =>"Keno 8",
            "1509" =>"Keno 9",
            "1510" =>"Keno 10",
            "1511" =>"Keno 11",
            "1512" =>"Keno 12",
            "1513" =>"Keno 13",
            "1514" =>"Keno 14",
            "1515" =>"Max Keno",
            "1516" =>"Max Keno2",
            "1517" =>"Mini Keno",
            "1518" =>"Keno 18",
            "1519" =>"Spring",
            "1520" =>"Summer",
            "1521" =>"Autumn",
            "1522" =>"Winter",
            "1523" =>"Keno 23",
            "1524" =>"Mini Keno 2",
            "1525" =>"Keno War",
            "1526" =>"Keno War 2",
            "1031" =>"Max5D 60",
            "1032" =>"Max5D 90",
            "1033" =>"Max3D 60",
            "1034" =>"Max3D 90",
            "1035" =>"Max11x5 60",
            "1036" =>"Max11x5 90",
            "1037" =>"MaxDice 60",
            "1038" =>"MaxDice 90",
            "1039" =>"Max Racing",
            "1040" =>"Max Racing 2",
            "1041" =>"Penalty Shoot-out",
            "1042" =>"Penalty Shoot-out 2",
            "1043" =>"Se Die",
            "1044" =>"Se Die 2",
            "1045" =>"Lottery Bull",
            "1046" =>"Lottery Bull 2",
            "1047" =>"Max Se Die",
            "1048" =>"Max Se Die 2",
            "8101" =>"Big/Small",
            "8102" =>"Odd/Even",
            "8103" =>"4 Seasons",
            "8104" =>"More Odd/More Even",
            "8105" =>"Combo",
            //prod september first week
            "467"  =>"Half Time/Full Time Exact Total Goals",
            "468"  =>"Score Box Handicap",
            "469"  =>"Score Box Over/Under",
            //prod september 12
            "470"  =>"Corners Odd/Even (own)",
            "471"  =>"1H Corners Odd/Even (own)",
            "472"  =>"2H Corners Odd/Even",
            "473"  =>"Total Corners",
            "474"  =>"1H Total Corners (own)",
            "475"  =>"Alternative Corners",
            "476"  =>"1H Alternative Corners",
            "477"  =>"Corner 3-Way Handicap",
            "478"  =>"1H Corner 3-Way Handicap",
            "479"  =>"Time Of First Corner",
            "481"  =>"Time Of 2H First Corner",
            "482"  =>"Home Team Over/Under Corners",
            "483"  =>"Away Team Over/Under Corners",
            "484"  =>"1H Home Team Over/Under Corners",
            "485"  =>"1H Away Team Over/Under Corners",
            "486"  =>"Corners Race",
            "487"  =>"1H Corners Race",
            "488"  =>"First Corner (2-Way)",
            "489"  =>"1H First Corner (2-Way)",
            "490"  =>"2H First Corner (2-Way)",
            "491"  =>"Last Corner (2-Way)",
            "492"  =>"1H Last Corner (2-Way)",
            "493"  =>"Half Time/Full Time Total Corners",
            "494"  =>"1H Correct Corners",
            "495"  =>"2H Correct Corners",
            "496"  =>"Corner Highest Scoring Half",
            "497"  =>"Corner Highest Scoring Half(2-Way)",
            "1301"  =>"Match Winner (Tennis)",
            "1302"  =>"Match Correct Score (Tennis)",
            "1303"  =>"Set Handicap (Tennis)",
            "1305"  =>"Match Total Games Odd/Even (Tennis)",
            "1306"  =>"Match Total Games Over/under (Tennis)",
            "1308"  =>"Match Games Handicap (Tennis)",
            "1311"  =>"Set x Winner (Tennis)",
            "1312"  =>"Set x Total Games (Tennis)",
            "1316"  =>"Set x Game Handicap (Tennis)",
            "1317"  =>"Set x Correct Score (Tennis)",
            "1318"  =>"Set x Total Game Odd/Even (Tennis)",
            "1324"  =>"Set x Game y Winner (Tennis)",
            "9001"  =>"Map X Moneyline (eSports)",
            "9002" =>"Map X Total Kills Handicap (eSports)",
            "9003" =>"Map X Total Kills Over/Under (eSports)",
            "9004" =>"Map X Total Kills Moneyline (eSports)",
            "9005" =>"Map X Total Kills Odd/Even (eSports)",
            "9006" =>"Map X First Blood (eSports)",
            "9007" =>"Map X First to Y Kills (eSports)",
            "9008" =>"Map X Total Towers Handicap (eSports)",
            "9009" =>"Map X Total Towers Over/Under (eSports)",
            "9010" =>"Map X Total Towers Moneyline (eSports)",
            "9011" =>"Map X First Tier Y Tower (eSports)",
            "9012" =>"Map X Total Roshans Handicap (eSports)",
            "9013" =>"Map X Total Roshans Over/Under (eSports)",
            "9014" =>"Map X Total Roshans Moneyline (eSports)",
            "9015" =>"Map X 1st Roshan (eSports)",
            "9016" =>"Map X 2nd Roshan (eSports)",
            "9017" =>"Map X 3rd Roshan (eSports)",
            "9018" =>"Map X Total Barracks Handicap (eSports)",
            "9019" =>"Map X Total Barracks Over/Under (eSports)",
            "9020" =>"Map X Total Barracks Moneyline (eSports)",
            "9021" =>"Map X Barracks 1st Lane (eSports)",
            "9022" =>"Map X Barracks 2nd Lane (eSports)",
            "9023" =>"Map X Barracks 3rd Lane (eSports)",
            "9024" =>"Map X Total Turrets Handicap (eSports)",
            "9025" =>"Map X Total Turrets Over/Under (eSports)",
            "9026" =>"Map X Total Turrets Moneyline (eSports)",
            "9027" =>"Map X First Tier Y Turret (eSports)",
            "9028" =>"Map X Total Dragons Handicap (eSports)",
            "9029" =>"Map X Total Dragons Over/Under (eSports)",
            "9030" =>"Map X Total Dragons Moneyline (eSports)",
            "9031" =>"Map X 1st Dragon (eSports)",
            "9032" =>"Map X 2nd Dragon (eSports)",
            "9033" =>"Map X 3rd Dragon (eSports)",
            "9034" =>"Map X Total Barons Handicap (eSports)",
            "9035" =>"Map X Total Barons Over/Under (eSports)",
            "9036" =>"Map X Total Barons Moneyline (eSports)",
            "9037" =>"Map X 1st Baron (eSports)",
            "9038" =>"Map X 2nd Baron (eSports)",
            "9039" =>"Map X 3rd Baron (eSports)",
            "9040" =>"Map X Total Inhibitors Handicap (eSports)",
            "9041" =>"Map X Total Inhibitors Over/Under (eSports)",
            "9042" =>"Map X Total Inhibitors Moneyline (eSports)",
            "9043" =>"Map X 1st Inhibitor (eSports)",
            "9044" =>"Map X 2nd Inhibitor (eSports)",
            "9045" =>"Map X 3rd Inhibitor (eSports)",
            "9046" =>"Map X Total Tyrants Handicap (eSports)",
            "9047" =>"Map X Total Tyrants Over/Under (eSports)",
            "9048" =>"Map X Total Tyrants Moneyline (eSports)",
            "9049" =>"Map X 1st Tyrant (eSports)",
            "9050" =>"Map X 2nd Tyrant (eSports)",
            "9051" =>"Map X 3rd Tyrant (eSports)",
            "9052" =>"Map X Total Overlords Handicap (eSports)",
            "9053" =>"Map X Total Overlords Over/Under (eSports)",
            "9054" =>"Map X Total Overlords Moneyline (eSports)",
            "9055" =>"Map X 1st Overlord (eSports)",
            "9056" =>"Map X 2nd Overlord (eSports)",
            "9057" =>"Map X 3rd Overlord (eSports)",
            "9058" =>"Map X Duration Over/Under (Mins) (eSports)",
            "9059" =>"Map X Rounds Handicap (eSports)",
            "9060" =>"Map X Rounds Over/Under (eSports)",
            "9061" =>"Map X Rounds Odd/Even (eSports)",
            "9062" =>"Map X First to Y Rounds (eSports)",
            "9063" =>"Map X First Half (eSports)",
            "9064" =>"Map X Second Half (eSports)",
            "9065" =>"Map X Most First Kill (eSports)",
            "9066" =>"Map X Clutches (eSports)",
            "9067" =>"Map X 16th Round (eSports)",
            "9068" =>"Map X Round Y Moneyline (eSports)",
            "9069" =>"Map X Round Y Total Kills Moneyline (eSports)",
            "9070" =>"Map X Round Y Total Kills Over/Under (eSports)",
            "9071" =>"Map X Round Y Total Kills Odd/Even (eSports)",
            "9072" =>"Map X Round Y First Kill (eSports)",
            "9073" =>"Map X Round Y Bomb Plant (eSports)",
            "9074" =>"Map X Rounds Over/Under (Overtime) (eSports)",
            "9075" =>"Map X Final Round Bomb Plant (eSports)",
            "9076" =>"Map X Clutches Handicap (eSports)",
            "9077" =>"Map X Round Y Total Kills Handicap (eSports)",
            "9078" =>"Map X Total Towers Odd/Even (eSports)",
            "9079" =>"Map X Total Roshans Odd/Even (eSports)",
            "9080" =>"Map X Total Barracks Odd/Even (eSports)",
            "9081" =>"Map X Total Turrets Odd/Even (eSports)",
            "9082" =>"Map X Total Dragons Odd/Even (eSports)",
            "9083" =>"Map X Total Barons Odd/Even (eSports)",
            "9084" =>"Map X Total Inhibitors Odd/Even (eSports)",
            "9085" =>"Map X Total Tyrants Odd/Even (eSports)",
			"9086" =>"Map X Total Overlords Odd/Even (eSports)",
            //cricket games bet types
            "9400" => "Super Over Winner (Cricket)",
            "9401" => "Toss Winner (Cricket)",
            "9404" => "Home Inns Runs (Cricket)",
            "9405" => "Away Inns Runs (Cricket)",
            "9406" => "Home 1st Inns Runs (Cricket)",
            "9407" => "Away 1st Inns Runs (Cricket)",
            "9408" => "Home 2nd Inns Runs (Cricket)",
            "9409" => "Away 2nd Inns Runs (Cricket)",
            "9410" => "Home Group 1-X Runs (Cricket)",
            "9411" => "Away Group 1-X Runs (Cricket)",
            "9412" => "Home 1st Inns Group 1-X Runs (Cricket)",
            "9413" => "Away 1st Inns Group 1-X Runs (Cricket)",
            "9414" => "Home 2nd Inns Group 1-X Runs (Cricket)",
            "9415" => "Away 2nd Inns Group 1-X Runs (Cricket)",
            "9416" => "Home Score at End of Over X (Cricket)",
            "9417" => "Away Score at End of Over X (Cricket)",
            "9418" => "Home 1st Inns Score at End of Over X (Cricket)",
            "9419" => "Away 1st Inns Score at End of Over X (Cricket)",
            "9420" => "Home 2nd Inns Score at End of Over X (Cricket)",
            "9421" => "Away 2nd Inns Score at End of Over X (Cricket)",
            "9422" => "Home Score after Over X Ball Y (Cricket)",
            "9423" => "Away Score after Over X Ball Y (Cricket)",
            "9428" => "Home Fall of Wicket X (Cricket)",
            "9429" => "Away Fall of Wicket X (Cricket)",
            "9430" => "Home 1st Inns Fall of Wicket X (Cricket)",
            "9431" => "Away 1st Inns Fall of Wicket X (Cricket)",
            "9432" => "Home 2nd Inns Fall of Wicket X (Cricket)",
            "9433" => "Away 2nd Inns Fall of Wicket X (Cricket)",
            "9434" => "Home Wicket X Method of Dismissal (Cricket)",
            "9435" => "Away Wicket X Method of Dismissal (Cricket)",
            "9436" => "Home 1st Inns Wicket X Method of Dismissal (Cricket)",
            "9437" => "Away 1st Inns Wicket X Method of Dismissal (Cricket)",
            "9438" => "Home 2nd Inns Wicket X Method of Dismissal (Cricket)",
            "9439" => "Away 2nd Inns Wicket X Method of Dismissal (Cricket)",
            "9440" => "Most Fours (Cricket)",
            "9441" => "Most Sixes (Cricket)",
            "9442" => "Highest Opening Partnership (Cricket)",
            "9443" => "Highest Score in Over 1 (Cricket)",
            "9446" => "Match Fours (Cricket)",
            "9447" => "Match Sixes (Cricket)",
            "9448" => "Match Run Outs (Cricket)",
            "9449" => "Match Wickets (Cricket)",
            "9450" => "Match Extras (Cricket)",
            "9451" => "Match Dot Balls (Cricket)",
            "9452" => "Highest Individual Score (Cricket)",
            "9453" => "Highest Over Score (Cricket)",
            "9454" => "Home Inns Fours (Cricket)",
            "9455" => "Away Inns Fours (Cricket)",
            "9456" => "Home 1st Inns Fours (Cricket)",
            "9457" => "Away 1st Inns Fours (Cricket)",
            "9458" => "Home 2nd Inns Fours (Cricket)",
            "9459" => "Away 2nd Inns Fours (Cricket)",
            "9460" => "Home Inns Sixes (Cricket)",
            "9461" => "Away Inns Sixes (Cricket)",
            "9462" => "Home 1st Inns Sixes (Cricket)",
            "9463" => "Away 1st Inns Sixes (Cricket)",
            "9464" => "Home 2nd Inns Sixes (Cricket)",
            "9465" => "Away 2nd Inns Sixes (Cricket)",
            "9484" => "Home Over X Exact Runs (Cricket)",
            "9485" => "Away Over X Exact Runs (Cricket)",
            "9486" => "Home 1st Inns Over X Exact Runs (Cricket)",
            "9487" => "Away 1st Inns Over X Exact Runs (Cricket)",
            "9488" => "Home 2nd Inns Over X Exact Runs (Cricket)",
            "9489" => "Away 2nd Inns Over X Exact Runs (Cricket)",
            "9490" => "Home Over X Ball Y Exact Runs (Cricket)",
            "9491" => "Away Over X Ball Y Exact Runs (Cricket)",
            "9496" => "Home Inns Runs OE (Cricket)",
            "9497" => "Away Inns Runs OE (Cricket)",
            "9498" => "Home 1st Inns Runs OE (Cricket)",
            "9499" => "Away 1st Inns Runs OE (Cricket)",
            "9500" => "Home 2nd Inns Runs OE (Cricket)",
            "9501" => "Away 2nd Inns Runs OE (Cricket)",
            "9502" => "Home Group 1-X Runs OE (Cricket)",
            "9503" => "Away Group 1-X Runs OE (Cricket)",
            "9504" => "Home 1st Inns Group 1-X Runs OE (Cricket)",
            "9505" => "Away 1st Inns Group 1-X Runs OE (Cricket)",
            "9506" => "Home 2nd Inns Group 1-X Runs OE (Cricket)",
            "9507" => "Away 2nd Inns Group 1-X Runs OE (Cricket)",
            "9508" => "Home Score at End of Over X OE (Cricket)",
            "9509" => "Away Score at End of Over X OE (Cricket)",
            "9510" => "Home 1st Inns Score at End of Over X OE (Cricket)",
            "9511" => "Away 1st Inns Score at End of Over X OE (Cricket)",
            "9512" => "Home 2nd Inns Score at End of Over X OE (Cricket)",
            "9513" => "Away 2nd Inns Score at End of Over X OE (Cricket)",
            "9514" => "Home Fall of Wicket X OE (Cricket)",
            "9515" => "Away Fall of Wicket X OE (Cricket)",
            "9516" => "Home 1st Inns Fall of Wicket X OE (Cricket)",
            "9517" => "Away 1st Inns Fall of Wicket X OE (Cricket)",
            "9518" => "Home 2nd Inns Fall of Wicket X OE (Cricket)",
            "9519" => "Away 2nd Inns Fall of Wicket X OE (Cricket)",
            "9527" => "Match Fours OE (Cricket)",
            "9528" => "Match Sixes OE (Cricket)",
            "9529" => "Match Run Outs OE (Cricket)",
            "9530" => "Match Extras OE (Cricket)",
            "9531" => "Match Dot Balls OE (Cricket)",
            "9532" => "Home Inns Fours OE (Cricket)",
            "9533" => "Away Inns Fours OE (Cricket)",
            "9534" => "Home 1st Inns Fours OE (Cricket)",
            "9535" => "Away 1st Inns Fours OE (Cricket)",
            "9536" => "Home 2nd Inns Fours OE (Cricket)",
            "9537" => "Away 2nd Inns Fours OE (Cricket)",
            "9538" => "1X2 (Back & Lay) (Cricket)",
            "9539" => "Match Winner (Back & Lay) (Cricket)",
            "9540" => "Super Over Winner (Back & Lay) (Cricket)",
            "9541" => "Home Inns Runs Fancy (Cricket)",
            "9542" => "Away Inns Runs Fancy (Cricket)",
            "9543" => "Home 1st Inns Runs Fancy (Cricket)",
            "9544" => "Away 1st Inns Runs Fancy (Cricket)",
            "9545" => "Home 2nd Inns Runs Fancy (Cricket)",
            "9546" => "Away 2nd Inns Runs Fancy (Cricket)",
            "9547" => "Home Group 1-X Runs Fancy (Cricket)",
            "9548" => "Away Group 1-X Runs Fancy (Cricket)",
            "9549" => "Home 1st Inns Group 1-X Runs Fancy (Cricket)",
            "9550" => "Away 1st Inns Group 1-X Runs Fancy (Cricket)",
            "9551" => "Home 2nd Inns Group 1-X Runs Fancy (Cricket)",
            "9552" => "Away 2nd Inns Group 1-X Runs Fancy (Cricket)",
            "9559" => "Home Fall of Wicket X Fancy (Cricket)",
            "9560" => "Away Fall of Wicket X Fancy (Cricket)",
            "9561" => "Home 1st Inns Fall of Wicket X Fancy (Cricket)",
            "9562" => "Away 1st Inns Fall of Wicket X Fancy (Cricket)",
            "9563" => "Home 2nd Inns Fall of Wicket X Fancy (Cricket)",
            "9564" => "Away 2nd Inns Fall of Wicket X Fancy (Cricket)",
            "9566" => "Match Fours Fancy (Cricket)",
            "9567" => "Match Sixes Fancy (Cricket)",
            "9569" => "Match Wickets Fancy (Cricket)",
            "9570" => "Match Extras Fancy (Cricket)",
            "9572" => "Highest Individual Score Fancy (Cricket)",
            "9573" => "Highest Over Score Fancy (Cricket)",
            "9574" => "Home Inns Fours Fancy (Cricket)",
            "9575" => "Away Inns Fours Fancy (Cricket)",
            "9576" => "Home 1st Inns Fours Fancy (Cricket)",
            "9577" => "Away 1st Inns Fours Fancy (Cricket)",
            "9578" => "Home 2nd Inns Fours Fancy (Cricket)",
            "9579" => "Away 2nd Inns Fours Fancy (Cricket)",
            "9580" => "Home Inns Sixes Fancy (Cricket)",
            "9581" => "Away Inns Sixes Fancy (Cricket)",
            "9582" => "Home 1st Inns Sixes Fancy (Cricket)",
            "9583" => "Away 1st Inns Sixes Fancy (Cricket)",
            "9584" => "Home 2nd Inns Sixes Fancy (Cricket)",
            "9585" => "Away 2nd Inns Sixes Fancy (Cricket)",
            "9604" => "Over/Under (dec) (Cricket)",
            "9605" => "Odd/Even (dec) (Cricket)",
            "9607" => "Home Over X Runs (Cricket)",
            "9608" => "Away Over X Runs (Cricket)",
            "9609" => "Home 1st Inns Over X Runs (Cricket)",
            "9610" => "Away 1st Inns Over X Runs (Cricket)",
            "9611" => "Home 2nd Inns Over X Runs (Cricket)",
            "9612" => "Away 2nd Inns Over X Runs (Cricket)",
            "9613" => "Home Over X Ball Y Runs (Cricket)",
            "9614" => "Away Over X Ball Y Runs (Cricket)",
            "9615" => "Most Run Outs (Cricket)",
            "9616" => "Home Over X Delivery Y Runs (Cricket)",
            "9617" => "Away Over X Delivery Y Runs (Cricket)",
            "9618" => "Home Over X Runs OE (Cricket)",
            "9619" => "Away Over X Runs OE (Cricket)",
            "9620" => "Home 1st Inns Over X Runs OE (Cricket)",
            "9621" => "Away 1st Inns Over X Runs OE (Cricket)",
            "9622" => "Home 2nd Inns Over X Runs OE (Cricket)",
            "9623" => "Away 2nd Inns Over X Runs OE (Cricket)",
            "9624" => "Will there be a Boundary Fours in Home Over X? (Cricket)",
            "9625" => "Will there be a Boundary Fours in Away Over X? (Cricket)",
            "9626" => "Will there be a Boundary Fours in Home 1st Inns Over X? (Cricket)",
            "9627" => "Will there be a Boundary Fours in Away 1st Inns Over X? (Cricket)",
            "9628" => "Will there be a Boundary Fours in Home 2nd Inns Over X? (Cricket)",
            "9629" => "Will there be a Boundary Fours in Away 2nd Inns Over X? (Cricket)",
            "9630" => "Will there be a Boundary Sixes in Home Over X? (Cricket)",
            "9631" => "Will there be a Boundary Sixes in Away Over X? (Cricket)",
            "9632" => "Will there be a Boundary Sixes in Home 1st Inns Over X? (Cricket)",
            "9633" => "Will there be a Boundary Sixes in Away 1st Inns Over X? (Cricket)",
            "9634" => "Will there be a Boundary Sixes in Home 2nd Inns Over X? (Cricket)",
            "9635" => "Will there be a Boundary Sixes in Away 2nd Inns Over X? (Cricket)",
            "9636" => "Home Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9637" => "Away Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9638" => "Home 1st Inns Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9639" => "Away 1st Inns Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9640" => "Home 2nd Inns Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9641" => "Away 2nd Inns Wicket X Method of Dismissal (6 Way) (Cricket)",
            "9650" => "Home Top Batsman (Cricket)",
            "9651" => "Away Top Batsman (Cricket)",
            "9652" => "Home 1st Inns Top Batsman (Cricket)",
            "9653" => "Away 1st Inns Top Batsman (Cricket)",
            "9654" => "Home 2nd Inns Top Batsman (Cricket)",
            "9655" => "Away 2nd Inns Top Batsman (Cricket)",
            "9656" => "Home Top Bowler (Cricket)",
            "9657" => "Away Top Bowler (Cricket)",
            "9658" => "Home 1st Inns Top Bowler (Cricket)",
            "9659" => "Away 1st Inns Top Bowler (Cricket)",
            "9660" => "Home 2nd Inns Top Bowler (Cricket)",
            "9661" => "Away 2nd Inns Top Bowler (Cricket)",
            "9662" => "Home Next Man Out (Cricket)",
            "9663" => "Away Next Man Out (Cricket)",
            "9664" => "Home 1st Inns Next Man Out (Cricket)",
            "9665" => "Away 1st Inns Next Man Out (Cricket)",
            "9666" => "Home 2nd Inns Next Man Out (Cricket)",
            "9667" => "Away 2nd Inns Next Man Out (Cricket)",
            "9668" => "Home Prematch Top Batsman (Cricket)",
            "9669" => "Away Prematch Top Batsman (Cricket)",
            "9670" => "Home 1st Inns Prematch Top Batsman (Cricket)",
            "9671" => "Away 1st Inns Prematch Top Batsman (Cricket)",
            "9672" => "Home Prematch Top Bowler (Cricket)",
            "9673" => "Away Prematch Top Bowler (Cricket)",
            "9674" => "Home 1st Inns Prematch Top Bowler (Cricket)",
            "9675" => "Away 1st Inns Prematch Top Bowler (Cricket)",
            "9676" => "Home Player Runs (Cricket)",
            "9677" => "Home Player Runs OE (Cricket)",
            "9678" => "Home 1st Inns Player Runs (Cricket)",
            "9679" => "Home 1st Inns Player Runs OE (Cricket)",
            "9680" => "Home Batsman Runs (Cricket)",
            "9681" => "Home Batsman Runs OE (Cricket)",
            "9682" => "Home 1st Inns Batsman Runs (Cricket)",
            "9683" => "Home 1st Inns Batsman Runs OE (Cricket)",
            "9684" => "Home 2nd Inns Batsman Runs (Cricket)",
            "9685" => "Home 2nd Inns Batsman Runs OE (Cricket)",
            "9686" => "Away Player Runs (Cricket)",
            "9687" => "Away Player Runs OE (Cricket)",
            "9688" => "Away 1st Inns Player Runs (Cricket)",
            "9689" => "Away 1st Inns Player Runs OE (Cricket)",
            "9690" => "Away Batsman Runs (Cricket)",
            "9691" => "Away Batsman Runs OE (Cricket)",
            "9692" => "Away 1st Inns Batsman Runs (Cricket)",
            "9693" => "Away 1st Inns Batsman Runs OE (Cricket)",
            "9694" => "Away 2nd Inns Batsman Runs (Cricket)",
            "9695" => "Away 2nd Inns Batsman Runs OE (Cricket)",
            "9696" => "Home 2nd Inns Batsman Fours (Cricket)",
            "9697" => "Away 2nd Inns Batsman Fours (Cricket)",
            "9698" => "Home Batsman Milestones (Cricket)",
            "9699" => "Away Batsman Milestones (Cricket)",
            "9702" => "Home 1st Inns Batsman Milestones (Cricket)",
            "9703" => "Away 1st Inns Batsman Milestones (Cricket)",
            "9704" => "Home 2nd Inns Batsman Milestones (Cricket)",
            "9705" => "Away 2nd Inns Batsman Milestones (Cricket)",
            "9708" => "1st Inns Highest Individual Score (Cricket)",
            "9709" => "1st Inns Highest Over Score (Cricket)",
            "9710" => "1st Inns Highest Opening Partnership (Cricket)",
            "9711" => "1st Inns Highest Score in Over 1 (Cricket)",
            "9712" => "Home Player Runs Fancy (Cricket)",
            "9713" => "Home 1st Inns Player Runs Fancy (Cricket)",
            "9714" => "Home Batsman Runs Fancy (Cricket)",
            "9715" => "Home 1st Inns Batsman Runs Fancy (Cricket)",
            "9716" => "Home 2nd Inns Batsman Runs Fancy (Cricket)",
            "9717" => "Away Player Runs Fancy (Cricket)",
            "9718" => "Away 1st Inns Player Runs Fancy (Cricket)",
            "9719" => "Away Batsman Runs Fancy (Cricket)",
            "9720" => "Away 1st Inns Batsman Runs Fancy (Cricket)",
            "9721" => "Away 2nd Inns Batsman Runs Fancy (Cricket)",
            "9722" => "Home Over X Runs Fancy (Cricket)",
            "9723" => "Away Over X Runs Fancy (Cricket)",
            "9724" => "Home 1st Inns Over X Runs Fancy (Cricket)",
            "9725" => "Away 1st Inns Over X Runs Fancy (Cricket)",
            "9726" => "Home 2nd Inns Over X Runs Fancy (Cricket)",
            "9727" => "Away 2nd Inns Over X Runs Fancy (Cricket)",
            "9728" => "1st Inns Highest Over Score Fancy (Cricket)",
            "9729" => "1st Inns Highest Individual Score Fancy (Cricket)",
            "9730" => "Home Batsman Sixes (Cricket)",
            "9731" => "Away Batsman Sixes (Cricket)",
            "9732" => "Home 1st Inns Batsman Sixes (Cricket)",
            "9733" => "Away 1st Inns Batsman Sixes (Cricket)",
            "9734" => "Home 2nd Inns Batsman Sixes (Cricket)",
            "9735" => "Away 2nd Inns Batsman Sixes (Cricket)",
            "9736" => "Home Batsman Fours (Cricket)",
            "9737" => "Away Batsman Fours (Cricket)",
            "9738" => "Home 1st Inns Batsman Fours (Cricket)",
            "9739" => "Away 1st Inns Batsman Fours (Cricket)",
			"701" =>"Match Handicap",
			"702" =>"Match Number of Games",
			"703" =>"Match Correct Score",
			"704" =>"Match Point Handicap",
			"705" =>"Match Point Over/Under",
			"706" =>"Match Point Odd/Even",
			"707" =>"Game X Winner",
			"708" =>"Game X Point Handicap",
			"709" =>"Game X Point Over/Under",
			"710" =>"Game X Point Odd/Even",
			"711" =>"Game X Winning Margin",
			"712" =>"Game X Race to Y Point",
			"713" =>"Game X Point Y Winner",
			"714" =>"Game X Extra Points",
            "376" =>"Penalty Shootout Combo (First 10)",
            "1527" =>"Lucky28",
            "1528" =>"Max Keno (20+1)",
            "1529" =>"Max Keno (20+1) 2",
            "1065" =>"Max6",
            "4914" =>"Two-Eight Bar",
            "618" =>"Last Digit Score",
            "619" =>"Home Team Last Digit Score",
            "620" =>"Away Team Last Digit Score",
            "621" =>"1H Last Digit Score",
            "622" =>"1H Home Team Last Digit Score",
            "623" =>"1H Away Team Last Digit Score",
            "624" =>"2H Last Digit Score ",
            "625" =>"2H Home Team Last Digit Score",
            "626" =>"2H Away Team Last Digit Score",
            "627" =>"Quarter X Last Digit Score",
            "628" =>"Quarter X Home Team Last Digit Score",
            "629" =>"Quarter X Away Team Last Digit Score",
            "630" =>"Quarter X 1x2",
            "631" =>"Quarter X Double Chance",
            "632" =>"Correct Quarter Odd/Even",
            "633" =>"Quarter 1/Quarter 2 Result",
            "634" =>"Quarter 3/Quarter 4 Result",
            "635" =>"Quarter To Win To Nil",
            "636" =>"Quarter To Win From Behind",
            "637" =>"Quarters Winners Handicap",
            "638" =>"Home Team Quarters Win Over/Under",
            "639" =>"Away Team Quarters Win Over/Under",
            "640" =>"Exact Quarter Draw",
            "641" =>"Quarter Draw Over/Under",
            "642" =>"Double Quarter Winner",
            "643" =>"Highest Scoring Quarter",
            "644" =>"Quarter Correct Score",
            "645" =>"1st Half/2nd Half Result",
            "646" =>"Match Handicap & Total",
            "4917" =>"Baccarat T2",
            "4918" =>"Baccarat T3",
            "4919" =>"Baccarat T4",
            "4920" =>"Baccarat T5",
            "4921" =>"Baccarat NC T2",

            #Arcadia Gaming
            "1901" =>"Video Poker",
            "1902" =>"BigSmall",
            "1903" =>"OddEven",
            "1905" =>"11HiLo",
            "1906" =>"Bonus",
            "1912" =>"Dragon Tiger",
            #Happy 5 linked Games
            "3601" =>"Parlay 5- Over/Under",
            "3602" =>"Parlay 5- Odd/Even",
            "3603" =>"Parlay 5- Red/Blue",
            "3612" =>"Soccer Lottery",
            "3613" => "E-Sports Lottery",
            "3604" => "Soccer5",
            #VG GAMING
            "3802" =>"Big Small",
            "3803" =>"Se Die",
            "3804" =>"Fish Prawn Crab",
            "3805" =>"Turbo Big Small",
            "3806" =>"Odd Even",
            "3807" =>"Turbo Odd Even",
            "3808" =>"Dragon Tiger",
            "3809" =>"Keno Pro Max",
            "3810" =>"Baccarat",
            "3811" =>"Keno Max",
            "3812" =>"Keno Max 2",
            "3813" =>"Keno Mini",
            "3814" =>"Keno Mini 2",
            "3815" =>"Keno East",
            "3816" =>"Keno West",
            "3817" =>"Keno South",
            "3818" =>"Keno North",
            "3819" =>"Roulette",
            "3820" =>"Blackjack",
            "3821" =>"Sic Bo",
            "3822" =>"Fish Prawn Crab PRO",
            "228" =>"SABA OU Time Machine",
            "229" =>"SABA HDP Time Machine",
            "3832" => "Baccarat Diamond",
			"3833" => "Baccarat Sapphire",
			"3834" => "Baccarat Ruby",
            #saba club
            "1612" => "Big Small",
			"1613" => "Se Die",
			"1614" => "Fish Prawn Crab",
			"1615" => "Turbo Big Small",
			"1616" => "Odd Even",
			"1617" => "Turbo Odd Even",
			"1618" => "Dragon Tiger",
			"1619" => "Keno Pro Max",
			"1620" => "Mini Se Die",
			"1621" => "Baccarat",
			"1622" => "Keno Pro Viet",
			"1623" => "Keno Max",
			"1624" => "Keno Max 2",
			"1625" => "Keno Mini",
			"1626" => "Keno Mini 2",
			"1627" => "Keno East",
			"1628" => "Keno West",
			"1629" => "Keno South",
			"1630" => "Keno North",
			"1632" => "Roulette",
			"1633" => "VIP Se Die",
			"1634" => "Blackjack",
			"1635" => "Special Se Die",
			"1636" => "Sic Bo",
			"1637" => "VIP Fish Prawn Crab",
			"1638" => "VIP Baccarat",
			"1639" => "Se Die War",
			"1640" => "Four Guardians",
			"1642" => "RNG Lottery",
			"1643" => "Three Cards",
			"1699" => "Jackpot Prize",
			"3836" => "Casino Hold'em Poker",
			"3837" => "Texas Hold'em Poker",
			"3614" => "NBA Lottery",
			"1530" => "Max Keno 3",
			"1531" => "Max Keno 4",
			"1071" => "Jhandi Munda",
			"1072" => "Ladder Game",
			"1073" => "Fireball 10",
			"1074" => "MaxDice 3",
			"1075" => "MaxDice 4",
			"1076" => "Ladder Game 2",
			"4928" => "Dragon Tiger T2",
			"4929" => "Dragon TigerT3",
			"4930" => "Dragon TigerT4",
			"4902" => "Texas Poker",
			"4903" => "Win Three Card",
			"4904" => "Win Three Card 2",
			"4905" => "Dragon Tiger",
			"4906" => "Bull",
			"4907" => "Blackjack 16",
			"4908" => "Baccarat T1",
			"4909" => "Baccarat NC T1",
			"4910" => "Three Picture",
			"4911" => "Teen Patti",
			"4912" => "Andar Bahar",

			//OGP-25347 Map games
			"9089" => "Map X First to Y Kills Time Over/Under",
			"9090" => "Map X First Blood Time Over/Under",
			"9091" => "Map X Total Kills at 10 Mins Handicap",
			"9092"	=> "Map X Total Kills at 10 Mins Over/Under",
			"9093" => "Map X Total Kills at 20 Mins Handicap",
			"9094" => "Map X Total Kills at 20 Mins Over/Under",
			"9095" => "Map X Home Team Total Kills at 10 Mins Over/Under",
			"9096"	=> "Map X Away Team Total Kills at 10 Mins Over/Under",
			"9097" => "Map X Home Team Total Kills at 20 Mins Over/Under",
			"9098" => "Map X Away Team Total Kills at 20 Mins Over/Under",
			"9099" => "Map X Home Team Total Kills Over/Under",
			"9100" => "Map X Away Team Total Kills Over/Under",
			"9101" => "Map X 1st Kill",
			"9102" => "Map X 2nd Kill",
			"9103" => "Map X 3rd Kill",
			"9104" => "Map X 4th Kill",
			"9105" => "Map X 5th Kill",
			"9106" => "Map X 6th Kill",
			"9107" => "Map X 7th Kill",
			"9108" => "Map X 8th Kill",
			"9109" => "Map X 9th Kill",
			"9110" => "Map X 10th Kill",
			"9111" => "Map X 15th Kill",
			"9112" => "Map X 20th Kill",
			"9113" => "Map X Lane With Most Turrets Destroyed",
			"9114" => "Map X First Turret Location",
			"9115" => "Map X Total Gold Moneyline",
			"9116" => "Map X Total Gold Handicap",
			"9117" => "Map X Total Gold Over/Under",
			"9118" => "Map X Home Team Total Gold Over/Under",
			"9119" => "Map X Away Team Total Gold Over/Under",
			"9120" => "Map X Total Gold Odd/Even",
			"9121" => "Map X First Tyrant Time Over/Under",
			"9122" => "Map X First Dark Tyrant",
			"9123" => "Map X First Overlord Time Over/Under",
			"9124" => "Map X Will Storm Dragon Be Killed Yes/No",
			"9125" => "Map X First Herald",
			"9126" => "Map X First Herald Time Over/Under",
			"9127" => "Map X First Baron Time Over/Under",
			"9128" => "Map X First Baron No. Players Buff Over/Under",
			"9129" => "Map X First Inhibitor Time Over/Under",
			"9130" => "Map X First Inhibitor Location",
			"9131" => "Map X First Dragon Time Over/Under",
			"9132" => "Map X First Elder Dragon",
			"9133" => "Map X Will Any Aegis Be Snatched Yes/No",
			"9134" => "Map X First Tower Time Over/Under",
			"9135" => "Map X First Tower Location",
			"9136" => "Map X First Roshan Time Over/Under",
			"9137" => "Map X First Barrack Time Over/Under",
			"9138" => "Map X First Barrack Location",
			"9139" => "Map X Total Bomb Detonations Over/Under (Excl. OT)",
			"9140" => "Map X Total Bomb Defuses Over/Under (Excl. OT)",
			"9141" => "Map X Lane With Most Turrets Destroyed (KOG)",
			"9142" => "Map X First Turret Location (KOG)",
            "4606" => "Basketball Mines",
            "4609" => "Basketball Towers",
			"5301" => "Football Cup",
            "4605" => "Soccer Mines",
            "4608" => "Soccer Towers",
		);
		if (in_array($id, self::QUARTERXY_BETTYPE)) {
			if(!empty($trans_id)){
				$data = $this->getBetDetailByTransId($trans_id);
				if(isset($data['bet_tag']) && !empty($data['bet_tag'])){
					if (strpos($data['bet_tag'], '-') !== false) {
						$data = explode("-",$data['bet_tag']);
						$x = isset($data['0']) ? $data['0'] : null;
						$y = isset($data['1']) ? $data['1'] : null;
						$str = str_replace("X",$x,$bet_type[$id]);
						$str = str_replace("Y",$y,$str);
						return $str;
					}
					$str = str_replace("X",+$data['bet_tag'],$bet_type[$id]);

					unset($bet_type);
					return $str;
				}
			}
		}
		if (array_key_exists($id,$bet_type)){
				$str= $bet_type[$id];
				unset($bet_type);
				return $str;
		}
		return "Bet type not registered";
	}

    public function getSports($sport_id = null){
        $sports = array(
            //necessary fields
            "1" => lang("Soccer"),
            "2" => lang("Basketball"),
            "3" => lang("Football"),
            "5" => lang("Tennis"),
            "8" => lang("Baseball"),
            "10" => lang("Golf"),
            "11" => lang("Motorsports"),
            "99" => lang("Other Sports"),
            "99MP" => lang("Mix Parlay"),
            //others
            "154" => lang("HorseRacing FixedOdds"),
            "161" => lang("Number GameNumber"),
            "164" => lang("Happy 5"),
            "180" => lang("Virtual Sports"),
            "190" => lang("Virtual Sports 2"),
            "151" => lang("151"),
            "43" => lang("E Sports"),
        );

        if(empty($sport_id)){
            return $sports;
        } else {
            if(isset($sports[$sport_id])){
                return $sports[$sport_id];
            }
            return $sport_id;
        }
    }
}
