<?php
trait game_description_legaming {

    public function sync_game_description_legaming(){
        $api_id = LE_GAMING_API;
        $this->legaming_game_list($api_id);
    }

    public function sync_game_description_legaming_t1(){
        $api_id = T1LE_GAMING_API;
        $this->legaming_game_list($api_id);
    }

    public function legaming_game_list($api_id){

        $db_true = 1;
        $db_false = 0;
        $now = $this->utils->getNowForMysql();

        $game_type_code_table_game = SYNC::TAG_CODE_TABLE_AND_CARDS;
        // $game_type_code_poker = SYNC::TAG_CODE_POKER;
        $game_type_code_unknown = SYNC::TAG_CODE_UNKNOWN_GAME;

        $game_types = [
            $game_type_code_table_game => [
                'game_platform_id' => $api_id,
                'game_type' => '_json:{"1":"Table and Cards","2":"牌桌&牌游戏","3":"Table and Cards","4":"Table and Cards","5":"테이블 & 카드"}',
                'game_type_lang' => '_json:{"1":"Table and Cards","2":"牌桌&牌游戏","3":"Table and Cards","4":"Table and Cards","5":"테이블 & 카드"}',
                "game_type_code" => $game_type_code_table_game,
                'updated_at'=>$now,
                'game_tag_code' => SYNC::TAG_CODE_TABLE_AND_CARDS,
            ],
            $game_type_code_unknown => [
                'game_platform_id' => $api_id,
                'game_type' => '_json:{"1":"Unknown","2":"不明","3":"Unknown","4":"Unknown"}',
                'game_type_lang' => '_json:{"1":"Unknown","2":"不明","3":"Unknown","4":"Unknown"}',
                "game_type_code" => $game_type_code_unknown,
                'updated_at'=>$now,
                'game_tag_code' => SYNC::TAG_CODE_UNKNOWN_GAME,
            ],
        ];

        $this->load->model(['game_type_model']);
        //this code is checking the game type table with game
        //also insertion of new game type based on game_type_code
        $gameTypeCodeMaps=$this->game_type_model->syncGameTypes($game_types);

        $this->utils->debug_log('gameTypeCodeMaps', $gameTypeCodeMaps);

        $cnt=0;
        $cntInsert = 0;
        $cntUpdate = 0;

        $game_descriptions = array(
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_unknown],
                'game_name' => '_json:{"1":"unknown","2":"不明","3":"unknown","4":"unknown","5":"unknown"}',
                'game_code' => 'unknown',
                'dlc_enabled' => $db_true,
                'mobile_enabled' => $db_true,
                'html_five_enabled' => $db_true,
                'english_name' => 'unknown',
                'external_game_id' => 'unknown',
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '620',
                'external_game_id' => '620',
                'game_name' => '_json:{"1":"Texas Hold’em Poker developed","2":"Texas Hold’em Poker developed","3":"Texas Hold’em Poker developed","4":"Texas Hold’em Poker developed","5":"Texas Hold’em Poker developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"620"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Texas Hold’em Poker developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '720',
                'external_game_id' => '720',
                'game_name' => '_json:{"1":"Two-Eight developed","2":"Two-Eight developed","3":"Two-Eight developed","4":"Two-Eight developed","5":"Two-Eight developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"720"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Two-Eight developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '830',
                'external_game_id' => '830',
                'game_name' => '_json:{"1":"Banker Niu-Niu developed","2":"Banker Niu-Niu developed","3":"Banker Niu-Niu developed","4":"Banker Niu-Niu developed","5":"Banker Niu-Niu developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"830"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Banker Niu-Niu developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '220',
                'external_game_id' => '220',
                'game_name' => '_json:{"1":"Golden Flower developed","2":"Golden Flower developed","3":"Golden Flower developed","4":"Golden Flower developed","5":"Golden Flower developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"220"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Golden Flower developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '860',
                'external_game_id' => '860',
                'game_name' => '_json:{"1":"San-Gong developed","2":"San-Gong developed","3":"San-Gong developed","4":"San-Gong developed","5":"San-Gong developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"860"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'San-Gong developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '870',
                'external_game_id' => '870',
                'game_name' => '_json:{"1":"Casino Niu-Niu developed","2":"Casino Niu-Niu developed","3":"Casino Niu-Niu developed","4":"Casino Niu-Niu developed","5":"Casino Niu-Niu developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"870"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Casino Niu-Niu developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '230',
                'external_game_id' => '230',
                'game_name' => '_json:{"1":"Speed Flower developed","2":"Speed Flower developed","3":"Speed Flower developed","4":"Speed Flower developed","5":"Speed Flower developed"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"230"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Speed Flower developed',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '610',
                'external_game_id' => '610',
                'game_name' => '_json:{"1":"Fight the landlord","2":"Fight the landlord","3":"Fight the landlord","4":"Fight the landlord","5":"Fight the landlord"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"610"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Fight the landlord',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '600',
                'external_game_id' => '600',
                'game_name' => '_json:{"1":"The 21 point(Black Jack)","2":"The 21 point(Black Jack)","3":"The 21 point(Black Jack)","4":"The 21 point(Black Jack)","5":"The 21 point(Black Jack)"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"600"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'The 21 point(Black Jack)',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '870',
                'external_game_id' => '870',
                'game_name' => '_json:{"1":"Casino Niu-Niu","2":"Casino Niu-Niu","3":"Casino Niu-Niu","4":"Casino Niu-Niu","5":"Casino Niu-Niu"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"870"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Casino Niu-Niu',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '630',
                'external_game_id' => '630',
                'game_name' => '_json:{"1":"Thirteen Water","2":"Thirteen Water","3":"Thirteen Water","4":"Thirteen Water","5":"Thirteen Water"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"630"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Thirteen Water',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '380',
                'external_game_id' => '380',
                'game_name' => '_json:{"1":"Five Lucky","2":"Five Lucky","3":"Five Lucky","4":"Five Lucky","5":"Five Lucky"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"380"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Five Lucky',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '390',
                'external_game_id' => '390',
                'game_name' => '_json:{"1":"Shooting Dragon Gate","2":"Shooting Dragon Gate","3":"Shooting Dragon Gate","4":"Shooting Dragon Gate","5":"Shooting Dragon Gate"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"390"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Shooting Dragon Gate',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '730',
                'external_game_id' => '730',
                'game_name' => '_json:{"1":"Banker Pai Gow","2":"Banker Pai Gow","3":"Banker Pai Gow","4":"Banker Pai Gow","5":"Banker Pai Gow"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"730"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Banker Pai Gow',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '900',
                'external_game_id' => '900',
                'game_name' => '_json:{"1":"Dragon-Tiger","2":"Dragon-Tiger","3":"Dragon-Tiger","4":"Dragon-Tiger","5":"Dragon-Tiger"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"900"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Dragon-Tiger',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '910',
                'external_game_id' => '910',
                'game_name' => '_json:{"1":"Baccarat","2":"Baccarat","3":"Baccarat","4":"Baccarat","5":"Baccarat"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"910"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Baccarat',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
            [
                'game_platform_id' => $api_id,
                'game_type_id' => $gameTypeCodeMaps[$game_type_code_table_game],
                'game_code' => '890',
                'external_game_id' => '890',
                'game_name' => '_json:{"1":"Firstly get three cards Niu-Niu","2":"Firstly get three cards Niu-Niu","3":"Firstly get three cards Niu-Niu","4":"Firstly get three cards Niu-Niu","5":"Firstly get three cards Niu-Niu"}',
                'dlc_enabled' => $db_false,
                'progressive' => $db_false,
                'offline_enabled' => $db_false,
                'mobile_enabled' => $db_true,
                'attributes' => '{"game_launch_code":"890"}',
                'html_five_enabled' => $db_true,
                'english_name' => 'Firstly get three cards Niu-Niu',
                'enabled_freespin' => $db_false,
                'enabled_on_android' => $db_true,
                'enabled_on_ios' => $db_true,
            ],
        );

        $this->load->model(['game_description_model']);

        $success=$this->game_description_model->syncGameDescription($game_descriptions);

        return $success;

    }

}
