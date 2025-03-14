<?php

trait baison_betdetails_module {

	private function getGameRoundCacheKey($game_id, $round_id){
    	return 'game-api-'.$this->getPlatformCode().'-gameid-'.$game_id.'-'.$round_id;
    }

	public function generateBetDetails($row){
		if(!$this->allowGenerateBetDetails){
			return null;
		}

		$timestamp = $this->microtime_int();
    	$url = $this->betDetailUrl;
    	$game_id = $row['game_id'];
    	$round_id = $row['round_number'];
    	$user_id = $this->agentId .'_'. $row['player_username'];
    	$room_id = $row['room_id'];

		$game_round_key=$this->getGameRoundCacheKey($game_id, $round_id);
		//try get from cache
    	$rlt=$this->CI->utils->getJsonFromCache($game_round_key);
    	if(!empty($rlt)){
    		return $rlt;
    	}

    	$url .= '?' . http_build_query(array(
            'timestamp'=>$timestamp,
            'channel_id' => $this->agentId,
            'game_id' => $game_id,
            'round_id' => $round_id,
            'user_id' => $user_id,
            'room_id' => $room_id,
            'key' => md5($this->agentId.$game_id.$round_id.$timestamp.$user_id.$room_id.$this->md5Key)
        ));
        $rlt['url'] = $url;

		$this->CI->utils->debug_log('-----------------------Baison GenerateBetDetails Logs ----------------------------',$url);

        $this->CI->utils->saveJsonToCache($game_round_key, $rlt);
        return $rlt;
	}

	public function generateBetDetails2($row){
		if(!$this->allowGenerateBetDetails){
			return null;
		}

		$card_value = $row['card_value'];
		$game_id = $row['game_id'];

		$bet_place = array(
			0 => "<b>Seat NO.1</b>",
			1 => "<b>Seat NO.2</b>",
			2 => "<b>Seat NO.3</b>",
			3 => "<b>Seat NO.4</b>",
			4 => "<b>Seat NO.5</b>",
			5 => "<b>Seat NO.6</b>",
			6 => "<b>Seat NO.7</b>",
			7 => "<b>Seat NO.8</b>",
			8 => "<b>Seat NO.9</b>",
		);

		switch ($game_id) {
			case self::Baccarat:
				return $this->convertCardGameValue($card_value);
				break;
			case self::Golden_Flower:
			case self::Speed_Flower:
				return $this->convertCardGameValue($card_value,$bet_place,true);
				break;
			case self::Circle_Tiles:
				return $this->convertMahjongValue($card_value);
				break;
			case self::Banker_niu_niu:
				//5 handcards per player occupy 10 bytes
				$bytes_occupy = 10;
				return $this->convertCardGameValue($card_value,$bet_place,true,$bytes_occupy);
				break;
			case self::Thirteen_Poker:
				return $this->converOtherPokerGameCardValue($card_value);
				break;

			default:
				return null;
				break;
		}
	}

	function converOtherPokerGameCardValue($card_value, $bet_place = null, $poker = false, $bytes_occupy = self::DEFAULT_BYTES_OCCUPY){
		$seats = explode(';', strtoupper($card_value));
		$str = "";
		$data = array();
		if(!empty($seats)){
			foreach ($seats as $key => $seat) {
				if(!empty($seat)){
					$parts = explode(',', $seat);
					$sNo = end($parts);
					array_pop($parts);
					$str .= "<b>No.".$sNo."</b> ";
					if(!empty($parts)){
						foreach ($parts as $key => $part) {
							$cards = str_split($part,self::BYTES_PER_CARD);
							$cardMode = end($cards);
							$cardMode = @self::card_mode[$cardMode];
							array_pop($cards);
							if(!empty($cards)){
								foreach ($cards as $key => $card) {
									$card = str_split($card);
									$c_value =  @self::cards_expression['value'][$card[0]];
									$c_suit = @self::cards_expression['suit'][$card[1]];
									$str .= $c_value.$c_suit." ";
								}
							}
							// print_r(($cardMode));exit();
							$str .= $cardMode.",";
						}
					}
					$data["s".$sNo] = $str;
					$str = "";
					// print_r(($data));exit();
				}
			}
		}
		return $data;
		// print_r($data);exit();
	}


	function convertCardGameValue($card_value, $bet_place = null, $poker = false, $bytes_occupy = self::DEFAULT_BYTES_OCCUPY){
		$card_value = str_split(strtoupper($card_value),$bytes_occupy);
		if(empty($bet_place)){
			$bet_place = array(
				0 => lang('Banker').": ",
				1 => lang('Player').": ",
			);
		}

		$str = "";
		$bet_place_count = 0;
		$data = array();
		$details = "";
		if(!empty($card_value)){
			// echo "<pre>";
			// print_r($card_value);exit();
			foreach ($card_value as $key => $positions) {
				$outputs = str_split($positions,self::BYTES_PER_CARD);
				$str.= (count($outputs) > 1) ? @$bet_place[$bet_place_count] : "";
				if($poker){
					$str.= ($positions == self::NO_PLAYER) ? " no player" : " player hands:";
					if(count($outputs) ==  1){
						$str = "";
					}
				}
				// echo "<pre>";
				// print_r($outputs);exit();
				foreach ($outputs as $output) {
					$result = str_split($output);
					// echo "<pre>";
					// print_r($result);exit();
					if(count($result) > 1){
						$c_value =  @self::cards_expression['value'][$result[0]];
						$c_suit = @self::cards_expression['suit'][$result[1]];
						$str .= $c_value.$c_suit." ";
					} else{
						$str .= "the player of seat No.".$output." is the winner";
					}
				}
				$data["s".$bet_place_count] = $str;
				$str = "";
				$bet_place_count++;
			}
		}
		return $data;
	}

	function convertMahjongValue($card_value){
		$m_expression = array(
			'1' => 'dot',
			'2' => 'bamboo',
			'3' => 'character',
			'4' => 'wind',
			'5' => 'dragon',
			'6' => 'flower',
		);

		$bet_place = array(
			0 => "<b>Banker's position</b> is ",
			1 => "<b>Sky's position</b> is ",
			2 => "<b>Ground position</b> is ",
			3 => "<b>Smoothly position</b> is ",
		);

		$card_value = str_split($card_value,4);
		$bet_place_count = 0;
		$str = "";
		$data = array();
		if(!empty($card_value)){
			foreach ($card_value as $key => $positions) {
				$outputs = str_split($positions,2);
				if(!empty($outputs)){
					$str.=$bet_place[$bet_place_count];
					$cnt = 0;
					foreach ($outputs as $output) {
						$result = str_split($output);
						$str2 = ($cnt&1) ? "" : " and ";
						$str.= @$result[0].$m_expression[$result[1]].$str2;
						$cnt++;
					}
				}
				$data["info".$bet_place_count] = $str;
				$str = "";
				$bet_place_count++;
			}
		}
		return $data;
	}
}