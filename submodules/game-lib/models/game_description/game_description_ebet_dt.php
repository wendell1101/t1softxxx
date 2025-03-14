<?php
trait game_description_ebet_dt {

	public function sync_game_description_ebet_dt(&$cnt=0){

		// $cnt=0;

		$success=true;

		$api_id=EBET_DT_API;
		$db_true=1;
		$db_false=0;
		$now=$this->utils->getNowForMysql();

		$game_type_code_classic_slots='dt_classic';
		$game_type_code_unknown='unknown';

		//sync game type first
		//use game_type_code as key
		$game_types = [
			$game_type_code_classic_slots => [
				'game_platform_id' => $api_id,
				'game_type' => '_json:{"1":"Classic Slots","2":"经典老虎机","3":"Classic Slots","4":"Classic Slots","5":"Classic Slots"}',
				'game_type_lang' => '_json:{"1":"Classic Slots","2":"经典老虎机","3":"Classic Slots","4":"Classic Slots","5":"Classic Slots"}',
				"game_type_code" => $game_type_code_classic_slots,
				'flag_show_in_site' => $db_true,
				'auto_add_new_game' => $db_true,
				'auto_add_to_cashback' => $db_true,
				'status'=>$db_true,
				'updated_at'=>$now,
				'game_tag_code' => sync::TAG_CODE_SLOT
			],
			$game_type_code_unknown => [
				'game_platform_id' => $api_id,
				'game_type' => '_json:{"1":"Unknown","2":"不明","3":"Unknown","4":"Unknown","5":"Unknown"}',
				'game_type_lang' => '_json:{"1":"Unknown","2":"不明","3":"Unknown","4":"Unknown","5":"Unknown"}',
				"game_type_code" => $game_type_code_unknown,
				'flag_show_in_site' => $db_false,
				'auto_add_new_game' => $db_true,
				'auto_add_to_cashback' => $db_true,
				'status'=>$db_true,
				'updated_at'=>$now,
				'game_tag_code' => sync::TAG_CODE_UNKNOWN_GAME
			]
		];

		$this->load->model(['game_type_model']);
		$gameTypeCodeMaps=$this->game_type_model->syncGameTypes($game_types);

		$this->utils->debug_log('gameTypeCodeMaps', $gameTypeCodeMaps);

		if(empty($gameTypeCodeMaps)){
			return false;
		}

		$game_descriptions = [

			#=========================================== Game List ========================================
			//===Classic Slots===================================
            [
                'game_name' => '_json:{"1":"Dragonball","2":"七龙珠","3":"Dragonball", "4":"Dragonball","5":"Dragonball"}',
                'game_code' => 'dragonball',
                'external_game_id' => 'dragonball',
                'english_name' => 'Dragonball',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Slamdunk","2":"灌篮高手","3":"Slamdunk", "4":"Slamdunk","5":"Slamdunk"}',
                'game_code' => 'sd',
                'external_game_id' => 'sd',
                'english_name' => 'Slamdunk',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"The King of Fighters","2":"拳皇","3":"The King of Fighters", "4":"The King of Fighters","5":"The King of Fighters"}',
                'game_code' => 'kof',
                'external_game_id' => 'kof',
                'english_name' => 'The King of Fighters',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Three Kingdoms","2":"赤壁之战","3":"Three Kingdoms", "4":"Three Kingdoms","5":"Three Kingdoms"}',
                'game_code' => 'san',
                'external_game_id' => 'san',
                'english_name' => 'Three Kingdoms',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Naruto","2":"火影忍者","3":"Naruto", "4":"Naruto","5":"Naruto"}',
                'game_code' => 'naruto',
                'external_game_id' => 'naruto',
                'english_name' => 'Naruto',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Onepiece","2":"海贼王pro","3":"Onepiece", "4":"Onepiece","5":"Onepiece"}',
                'game_code' => 'onepiece3x1',
                'external_game_id' => 'onepiece3x1',
                'english_name' => 'Onepiece',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"The King of Fighters 98","2":"拳皇98","3":"The King of Fighters 98", "4":"The King of Fighters 98","5":"The King of Fighters 98"}',
                'game_code' => 'kof5x9',
                'external_game_id' => 'kof5x9',
                'english_name' => 'The King of Fighters 98',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Slamdunk Pro","2":"灌篮高手pro","3":"Slamdunk Pro", "4":"Slamdunk Pro","5":"Slamdunk Pro"}',
                'game_code' => 'sd5',
                'external_game_id' => 'sd5',
                'english_name' => 'Slamdunk Pro',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Five elements","2":"五行世界","3":"Five elements", "4":"Five elements","5":"Five elements"}',
                'game_code' => 'fiveelements5x9',
                'external_game_id' => 'fiveelements5x9',
                'english_name' => 'Five elements',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Doraemon","2":"哆啦A梦","3":"Doraemon", "4":"Doraemon","5":"Doraemon"}',
                'game_code' => 'doraemon3x5',
                'external_game_id' => 'doraemon3x5',
                'english_name' => 'Doraemon',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Onepiece","2":"海贼王","3":"Onepiece", "4":"Onepiece","5":"Onepiece"}',
                'game_code' => 'onepiece',
                'external_game_id' => 'onepiece',
                'english_name' => 'Onepiece',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Fantasy forest","2":"梦幻森林","3":"Fantasy forest", "4":"Fantasy forest","5":"Fantasy forest"}',
                'game_code' => 'fantasyforest3x1',
                'external_game_id' => 'fantasyforest3x1',
                'english_name' => 'Fantasy forest',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Gundam","2":"高达","3":"Gundam", "4":"Gundam","5":"Gundam"}',
                'game_code' => 'gundam3x5',
                'external_game_id' => 'gundam3x5',
                'english_name' => 'Gundam',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Seiya","2":"圣斗士星矢","3":"Seiya", "4":"Seiya","5":"Seiya"}',
                'game_code' => 'seiya',
                'external_game_id' => 'seiya',
                'english_name' => 'Seiya',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Streetfihter","2":"街霸","3":"Streetfihter", "4":"Streetfihter","5":"Streetfihter"}',
                'game_code' => 'streetfighter3x1',
                'external_game_id' => 'streetfighter3x1',
                'english_name' => 'Streetfihter',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"God of Wealth","2":"财神到","3":"God of Wealth", "4":"God of Wealth","5":"God of Wealth"}',
                'game_code' => 'tgow',
                'external_game_id' => 'tgow',
                'english_name' => 'God of Wealth',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Newyear","2":"新年到","3":"Newyear", "4":"Newyear","5":"Newyear"}',
                'game_code' => 'newyear',
                'external_game_id' => 'newyear',
                'english_name' => 'Newyear',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Wusong","2":"水浒-武松传","3":"Wusong", "4":"Wusong","5":"Wusong"}',
                'game_code' => 'watermargin',
                'external_game_id' => 'watermargin',
                'english_name' => 'Wusong',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Aladdin","2":"阿拉丁神灯","3":"Aladdin", "4":"Aladdin","5":"Aladdin"}',
                'game_code' => 'aladdin5x243',
                'external_game_id' => 'aladdin5x243',
                'english_name' => 'Aladdin',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Xiyouji","2":"降妖传奇","3":"Xiyouji", "4":"Xiyouji","5":"Xiyouji"}',
                'game_code' => 'xiyouji5x9',
                'external_game_id' => 'xiyouji5x9',
                'english_name' => 'Xiyouji',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Cosmicgirl","2":"宇宙少女战士","3":"Cosmicgirl", "4":"Cosmicgirl","5":"Cosmicgirl"}',
                'game_code' => 'cosmicgirl3x1',
                'external_game_id' => 'cosmicgirl3x1',
                'english_name' => 'Cosmicgirl',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Watermargin","2":"水浒传","3":"Watermargin", "4":"Watermargin","5":"Watermargin"}',
                'game_code' => 'watermargin5x25',
                'external_game_id' => 'watermargin5x25',
                'english_name' => 'Watermargin',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Xiyouji","2":"西游记","3":"Xiyouji", "4":"Xiyouji","5":"Xiyouji"}',
                'game_code' => 'xiyouji5x25',
                'external_game_id' => 'xiyouji5x25',
                'english_name' => 'Xiyouji',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"League of Gods","2":"封神榜","3":"League of Gods", "4":"League of Gods","5":"League of Gods"}',
                'game_code' => 'tlod',
                'external_game_id' => 'tlod',
                'english_name' => 'League of Gods',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Journey to the West demon","2":"西游降妖","3":"Journey to the West demon", "4":"Journey to the West demon","5":"Journey to the West demon"}',
                'game_code' => 'jtw',
                'external_game_id' => 'jtw',
                'english_name' => 'Journey to the West demon',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"whitesnake","2":"白蛇传","3":"whitesnake", "4":"whitesnake","5":"whitesnake"}',
                'game_code' => 'whitesnake',
                'external_game_id' => 'whitesnake',
                'english_name' => 'whitesnake',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Crazy Basketball","2":"劲爆篮球","3":"Crazy Basketball", "4":"Crazy Basketball","5":"Crazy Basketball"}',
                'game_code' => 'btball5x20',
                'external_game_id' => 'btball5x20',
                'english_name' => 'Crazy Basketball',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"DragonPhoenix Prosper","2":"龙凤呈祥","3":"DragonPhoenix Prosper", "4":"DragonPhoenix Prosper","5":"DragonPhoenix Prosper"}',
                'game_code' => 'dnp',
                'external_game_id' => 'dnp',
                'english_name' => 'DragonPhoenix Prosper',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Glory of Heroes","2":"英雄荣耀","3":"Glory of Heroes", "4":"Glory of Heroes","5":"Glory of Heroes"}',
                'game_code' => 'crystal',
                'external_game_id' => 'Glory of Heroes',
                'english_name' => '',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"FULUSHOU","2":"福禄寿","3":"FULUSHOU", "4":"FULUSHOU","5":"FULUSHOU"}',
                'game_code' => 'fls',
                'external_game_id' => 'fls',
                'english_name' => 'FULUSHOU',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Chinese Paladin","2":"仙剑奇缘","3":"Chinese Paladin", "4":"Chinese Paladin","5":"Chinese Paladin"}',
                'game_code' => 'xjqy5x9',
                'external_game_id' => 'xjqy5x9',
                'english_name' => 'Chinese Paladin',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"FULUSHOU","2":"福禄寿","3":"FULUSHOU", "4":"FULUSHOU","5":"FULUSHOU"}',
                'game_code' => 'fls',
                'external_game_id' => 'fls',
                'english_name' => 'FULUSHOU',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Four Holy Beasts","2":"四圣兽","3":"Four Holy Beasts", "4":"Four Holy Beasts","5":"Four Holy Beasts"}',
                'game_code' => 'fourss',
                'external_game_id' => 'fourss',
                'english_name' => 'Four Holy Beasts',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"nightclub","2":"夜店之王","3":"nightclub", "4":"nightclub","5":"nightclub"}',
                'game_code' => 'nightclub5x20',
                'external_game_id' => 'nightclub5x20',
                'english_name' => 'nightclub',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"3D Slot","2":"3D老虎机","3":"3D Slot", "4":"3D Slot","5":"3D Slot"}',
                'game_code' => 'casino',
                'external_game_id' => 'casino',
                'english_name' => '3D Slot',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Crazy GO GO GO","2":"疯狂转转转","3":"Crazy GO GO GO", "4":"Crazy GO GO GO","5":"Crazy GO GO GO"}',
                'game_code' => 'crazy5x243',
                'external_game_id' => 'crazy5x243',
                'english_name' => 'Crazy GO GO GO',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
            [
                'game_name' => '_json:{"1":"Football All Star","2":"传奇之路","3":"Football All Star", "4":"Football All Star","5":"Football All Star"}',
                'game_code' => 'football',
                'external_game_id' => 'football',
                'english_name' => 'Football All Star',
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_classic_slots],
                'updated_at' => $now,
                'flag_show_in_site' => $db_true,
                'status' => $db_true,
            ],
			//===unknown======================================================================
			[
				'game_platform_id' => $api_id,
				'game_code' => 'unknown',
				'game_type_id' => $gameTypeCodeMaps[$game_type_code_unknown],
				'game_name' => '_json:{"1":"Unknown","2":"不明","3":"Unknown","4":"Unknown","5":"Unknown"}',
				"english_name" => "Unknown",
				'external_game_id' => 'unknown',
				'status'=> $db_true,
				'flag_show_in_site'=>$db_false,
				'updated_at'=>$now,
			],

		];

		$this->load->model(['game_description_model']);

		$success=$this->game_description_model->syncGameDescription($game_descriptions);

		return $success;
	}

}
