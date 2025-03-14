<?php
trait game_description_gameplay_sbtech {

	public function sync_game_description_gameplay_sbtech(){
		$cnt=0;
		$cntInsert = 0;
		$cntUpdate = 0;

		$db_true = 1;
		$db_false = 0;
		$api_id = GAMEPLAY_SBTECH_API;
		$now = $this->utils->getNowForMysql();

        $game_type_code_sports     = SYNC::TAG_CODE_SPORTS;
        $game_type_code_unknown    = SYNC::TAG_CODE_UNKNOWN_GAME;


		// sync game type first
		// use game_type_code as key
		  $game_types = [
            $game_type_code_sports => [
                'game_platform_id' => $api_id,
                'game_type' => '_json:{"1":"Sports"}',
                'game_type_lang' => '_json:{"1":"Sports"}',
                'game_type_code' => $game_type_code_sports,
                'flag_show_in_site' => $db_true,
                'auto_add_new_game' => $db_true,
                'auto_add_to_cashback' => $db_true,
                'status' => $db_true,
                'updated_at' => $now,
                'game_tag_code' => SYNC::TAG_CODE_SPORTS
            ],
            $game_type_code_unknown => [
                'game_platform_id' => $api_id,
                'game_type' => '_json:{"1":"Unknown","2":"不明类型","3":"Unknown","4":"Unknown"}',
                'game_type_lang' => '_json:{"1":"Unknown","2":"不明类型","3":"Unknown","4":"Unknown"}',
                "game_type_code" => $game_type_code_unknown,
                'flag_show_in_site' => $db_true,
                'auto_add_new_game' => $db_true,
                'auto_add_to_cashback' => $db_true,
                'status'=>$db_true,
                'updated_at'=>$now,
                'game_tag_code' => SYNC::TAG_CODE_UNKNOWN_GAME
            ],
        ];


		$this->load->model(['game_type_model']);
		//this code is checking the game type table with game
		//also insertion of new game type based on game_type_code
		// 'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
		$gameTypeCodeMaps=$this->game_type_model->syncGameTypes($game_types);
		// echo "<pre>";
		// print_r($gameTypeCodeMaps);
		// die();
		$this->utils->debug_log('gameTypeCodeMaps', $gameTypeCodeMaps);

		$game_descriptions = array(
            #================================================== Sports Game ========================================
            [
                'game_name' => '_json:{"1":"Soccer","2":"Soccer","3":"Soccer","4":"Soccer","5":"Soccer"}',
                'game_code' => 'Soccer',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Soccer',
                'english_name' => 'Soccer',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Basketball","2":"Basketball","3":"Basketball","4":"Basketball","5":"Basketball"}',
                'game_code' => 'Basketball',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Basketball',
                'english_name' => 'Basketball',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Football","2":"Football","3":"Football","4":"Football","5":"Football"}',
                'game_code' => 'Football',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Football',
                'english_name' => 'Football',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Tennis","2":"Tennis","3":"Tennis","4":"Tennis","5":"Tennis"}',
                'game_code' => 'Tennis',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Tennis',
                'english_name' => 'Tennis',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Baseball","2":"Baseball","3":"Baseball","4":"Baseball","5":"Baseball"}',
                'game_code' => 'Baseball',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Baseball',
                'english_name' => 'Baseball',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Ice Hockey","2":"Ice Hockey","3":"Ice Hockey","4":"Ice Hockey","5":"Ice Hockey"}',
                'game_code' => 'Ice Hockey',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Ice Hockey',
                'english_name' => 'Ice Hockey',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Specials","2":"Specials","3":"Specials","4":"Specials","5":"Specials"}',
                'game_code' => 'Specials',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Specials',
                'english_name' => 'Specials',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Handball","2":"Handball","3":"Handball","4":"Handball","5":"Handball"}',
                'game_code' => 'Handball',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Handball',
                'english_name' => 'Handball',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Rugby League","2":"Rugby League","3":"Rugby League","4":"Rugby League","5":"Rugby League"}',
                'game_code' => 'Rugby League',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Rugby League',
                'english_name' => 'Rugby League',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Golf","2":"Golf","3":"Golf","4":"Golf","5":"Golf"}',
                'game_code' => 'Golf',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Golf',
                'english_name' => 'Golf',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Snooker & Pool","2":"Snooker & Pool","3":"Snooker & Pool","4":"Snooker & Pool","5":"Snooker & Pool"}',
                'game_code' => 'Snooker & Pool',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Snooker & Pool',
                'english_name' => 'Snooker & Pool',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Motor Racing","2":"Motor Racing","3":"Motor Racing","4":"Motor Racing","5":"Motor Racing"}',
                'game_code' => 'Motor Racing',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Motor Racing',
                'english_name' => 'Motor Racing',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Darts","2":"Darts","3":"Darts","4":"Darts","5":"Darts"}',
                'game_code' => 'Darts',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Darts',
                'english_name' => 'Darts',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Cycling","2":"Cycling","3":"Cycling","4":"Cycling","5":"Cycling"}',
                'game_code' => 'Cycling',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Cycling',
                'english_name' => 'Cycling',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Speedway","2":"Speedway","3":"Speedway","4":"Speedway","5":"Speedway"}',
                'game_code' => 'Speedway',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Speedway',
                'english_name' => 'Speedway',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Volleyball","2":"Volleyball","3":"Volleyball","4":"Volleyball","5":"Volleyball"}',
                'game_code' => 'Volleyball',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Volleyball',
                'english_name' => 'Volleyball',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Boxing","2":"Boxing","3":"Boxing","4":"Boxing","5":"Boxing"}',
                'game_code' => 'Boxing',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Boxing',
                'english_name' => 'Boxing',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Hockey","2":"Hockey","3":"Hockey","4":"Hockey","5":"Hockey"}',
                'game_code' => 'Hockey',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Hockey',
                'english_name' => 'Hockey',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Table Tennis","2":"Table Tennis","3":"Table Tennis","4":"Table Tennis","5":"Table Tennis"}',
                'game_code' => 'Table Tennis',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Table Tennis',
                'english_name' => 'Table Tennis',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Winter sports","2":"Winter sports","3":"Winter sports","4":"Winter sports","5":"Winter sports"}',
                'game_code' => 'Winter sports',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Winter sports',
                'english_name' => 'Winter sports',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Beach Volleyball","2":"Beach Volleyball","3":"Beach Volleyball","4":"Beach Volleyball","5":"Beach Volleyball"}',
                'game_code' => 'Beach Volleyball',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Beach Volleyball',
                'english_name' => 'Beach Volleyball',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Badminton","2":"Badminton","3":"Badminton","4":"Badminton","5":"Badminton"}',
                'game_code' => 'Badminton',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Badminton',
                'english_name' => 'Badminton',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Rugby Union","2":"Rugby Union","3":"Rugby Union","4":"Rugby Union","5":"Rugby Union"}',
                'game_code' => 'Rugby Union',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Rugby Union',
                'english_name' => 'Rugby Union',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Aussie Rules","2":"Aussie Rules","3":"Aussie Rules","4":"Aussie Rules","5":"Aussie Rules"}',
                'game_code' => 'Aussie Rules',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Aussie Rules',
                'english_name' => 'Aussie Rules',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"MMA","2":"MMA","3":"MMA","4":"MMA","5":"MMA"}',
                'game_code' => 'MMA',
                'game_platform_id' => $api_id,
                'external_game_id' => 'MMA',
                'english_name' => 'MMA',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Cricket","2":"Cricket","3":"Cricket","4":"Cricket","5":"Cricket"}',
                'game_code' => 'Cricket',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Cricket',
                'english_name' => 'Cricket',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Surfing","2":"Surfing","3":"Surfing","4":"Surfing","5":"Surfing"}',
                'game_code' => 'Surfing',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Surfing',
                'english_name' => 'Surfing',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"E-Sports","2":"E-Sports","3":"E-Sports","4":"E-Sports","5":"E-Sports"}',
                'game_code' => 'E-Sports',
                'game_platform_id' => $api_id,
                'external_game_id' => 'E-Sports',
                'english_name' => 'E-Sports',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Chess","2":"Chess","3":"Chess","4":"Chess","5":"Chess"}',
                'game_code' => 'Chess',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Chess',
                'english_name' => 'Chess',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Gaelic Football","2":"Gaelic Football","3":"Gaelic Football","4":"Gaelic Football","5":"Gaelic Football"}',
                'game_code' => 'Gaelic Football',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Gaelic Football',
                'english_name' => 'Gaelic Football',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            [
                'game_name' => '_json:{"1":"Mix Parlay","2":"Mix Parlay","3":"Mix Parlay","4":"Mix Parlay","5":"Mix Parlay"}',
                'game_code' => 'Mix Parlay',
                'game_platform_id' => $api_id,
                'external_game_id' => 'Mix Parlay',
                'english_name' => 'Mix Parlay',
                'flash_enabled' => $db_false,
                'html_five_enabled' => $db_false,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_sports],
                'flag_show_in_site' => $db_true,
                'status' =>  $db_true,
                'mobile_enabled' => $db_false,
            ],
            #================================================== Unknown Game ========================================
            [
                'game_name' => '_json:{"1":"Unknown","2":"不明类型","3":"Unknown","4":"Unknown"}',
                'game_code' => 'unknown',
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_unknown],
                // 'game_type' => Table Game
                'external_game_id' => 'unknown',
                'english_name' => 'Unknown',
                'game_platform_id' => $api_id,
                'status'=> $db_true,
                'flag_show_in_site'=>$db_true,
                'updated_at'=>$now,
            ],

            #====================================End Game list=========================================
		);

		$this->load->model(['game_description_model']);

		$success=$this->game_description_model->syncGameDescription($game_descriptions);

		return $success;
	}

}
