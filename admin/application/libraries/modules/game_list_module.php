<?php

trait game_list_module {

	/**
     * [sync_gamelist_from_json description]
     * @param  [string] $game_providers [can sync multiple game provider]
     * @return [json]                   [result of game sync]
     */
    public function sync_gamelist_from_json($game_provider)
    {
        $this->CI->load->model(['game_type_model','game_description_model','vipsetting']);

        $gamelist_json_directory = dirname(__FILE__) . '/../../../../submodules/game-lib/models/game_description/json_data';
        $gamelist_json_files = array_diff(scandir($gamelist_json_directory), array('..', '.'));

        $is_exist = false;

        if (count($gamelist_json_files) == 0) {
            $result = ["Note" => "No available game list."];
            return $this->showErrorMessage($result);
        }
        $orig_game_provider_id = $game_provider;
        $game_provider = $this->filter_sub_game_api($game_provider);

        foreach ($gamelist_json_files as $file_name)
        {
            $file_name_arr = explode("-", str_replace(".json", "", $file_name));

            $game_platform_id = $file_name_arr[1];

            # records action
            $this->utils->recordAction('game_list', 'dev_manual_sync_gamelist_from_json', "Manual Sync Game List: ".$game_platform_id);

            if (in_array($game_provider, $file_name_arr))
            {
                $is_exist = true;
                $game_list_data = file_get_contents($gamelist_json_directory . "/". $file_name);

                if (empty($this->validateGameJsonFormat($game_list_data))) continue;
                $game_list = json_decode($game_list_data,true);

                #get game_type_id ids
                $game_types = array_unique(array_column($game_list, 'game_type_code'));

                $temp_game_types = $this->process_game_type_id($game_types, $orig_game_provider_id);

                $game_external_game_ids = [];
                foreach ($game_list as &$game)
                {
                    #redefined game platform_id
                    $game['game_platform_id'] = $orig_game_provider_id;

                    $game['game_type_id'] = @$temp_game_types[$game['game_type_code']]['game_type_id'];

                    if (empty($game['game_type_id']))
                    {
                        $this->utils->debug_log("ERROR game_type_code not valid =======>",$game['game_type_code']);
                        $game = null;
                        continue;
                    }
                    unset($game['game_type_code']);

                    if (!in_array($game['external_game_id'], $game_external_game_ids))
                    {
                        array_push($game_external_game_ids, $game['external_game_id']);

                    }else{
                        $this->utils->debug_log("ERROR duplicate game =======>",$game['external_game_id']);
                        $game=null;
                        continue;
                    }

                    $isFieldsAreValid = $this->checkIfFieldsAreValid($game);

                    if (empty($isFieldsAreValid))
                        $game = null;

                }
                unset($game_external_game_ids);

                $syncedResult = $this->CI->game_description_model->devSyncGameDescriptionInGameGateway($game_list, true);

                return json_encode($syncedResult,JSON_PRETTY_PRINT);
            }
        }

        if (empty($is_exist)) {
            $result = [
                "Note"=>"ERROR Game Provider: [".$game_provider."] not found on game list!",
                "List of available Game provider"=>$gamelist_json_files
            ];
            return $this->showErrorMessage($result);
        }
    }

    /**
     * [checkIfFieldsAreValid
     *  - check if all required fields exist
     *  - check if all fields are valid
     *  - check min and max input per field
     * ]
     * @param  [array] &$game            [game attributes]
     * @return [boolean]                   [description]
     */
    private function checkIfFieldsAreValid(&$game)
    {
    	$fields_datatypes = [
					            "int" => [
					            			"game_type_id",
					            		  	"game_platform_id",
					            		  	"related_game_desc_id",
					            		  	"game_order"
					            		 ],
					            "boolean" => [
					            				"flag_show_in_site",
						                        "enabled_on_ios",
						                        "enabled_on_android",
						                        "progressive",
						                        "html_five_enabled",
						                        "flash_enabled",
						                        "flag_new_game",
						                        "dlc_enabled",
						                        "mobile_enabled",
						                        "enabled_freespin",
						                        "status",
						                        "void_bet",
						                        "no_cash_back",
						                        "flag_hot_game",
						                        "desktop_enabled"
					                      	 ],
					            "text" => [
					            			"game_name",
					            			"english_name",
					            			"attributes",
					            			"demo_link",
					            			"game_code",
					            			"external_game_id",
					            			"client_id",
					            			"moduleid",
					            			"sub_game_provider",
					            			"note"
					            		  ],
					        ];

        $success = true;
        $game_fields = array_keys($game);
        $missing_required_fields = $min_max_result = [];

        foreach (GAME_DESCRIPTION_MODEL::GAME_LIST_REQUIRED_FIELDS as $required_field)
        {
            if (!in_array($required_field, $game_fields)) {
                array_push($missing_required_fields, $required_field);
            }
        }

        $game_list_valid_fields = array_keys(GAME_DESCRIPTION_MODEL::GAME_LIST_VALID_FIELDS_WITH_LIMIT);

        foreach ($game_fields as $field) {
            if ($field == "release_date") {
                //ignore
               continue;
            }
            if (!in_array($field,$game_list_valid_fields)){
                $success = false;
                $this->utils->debug_log("ERROR Invalid Field ======>[external_game_id=".$game['external_game_id']."]",$field);
            }

            if (in_array($field, $fields_datatypes['text'])) {
                if (strlen($game[$field]) > GAME_DESCRIPTION_MODEL::GAME_LIST_VALID_FIELDS_WITH_LIMIT[$field]['max']) {
                    array_push($min_max_result, [$field=>strlen($game[$field])]);
                    $success = false;
                }
            }

            if (in_array($field, $fields_datatypes['int']) || in_array($field, $fields_datatypes['boolean'])) {
                if ((int)$game[$field] > (int)GAME_DESCRIPTION_MODEL::GAME_LIST_VALID_FIELDS_WITH_LIMIT[$field]['max']) {
                    array_push($min_max_result, [$field=>$game[$field]]);
                    $success = false;
                }
                if ((int)$game[$field] < (int)GAME_DESCRIPTION_MODEL::GAME_LIST_VALID_FIELDS_WITH_LIMIT[$field]['min']) {
                    $game[$field]= false;
                }
            }

        }

        if (count($min_max_result) > 0)
            $this->utils->debug_log("ERROR Min/Max not met ======>[external_game_id=".$game['external_game_id']."]",$min_max_result);

        if (count($missing_required_fields) > 0)
            $this->utils->debug_log("ERROR Missing Required Field[s] ======>[external_game_id=".$game['external_game_id']."]",$missing_required_fields);

        unset($missing_required_fields,$min_max_result);
        return $success;
    }

    /**
     * [process_game_type_id description]
     * @param  [array] $game_types     [list of game types from game list]
     * @param  [int] $game_platform_id [defined system id]
     * @return [int]                   [returns game_type_id]
     */
    private function process_game_type_id($game_types,$game_platform_id)
    {
    	$game_tags = $this->CI->game_type_model->getAllGameTags();
        $temp_game_types = [];
        foreach ($game_types as $game_type)
        {
            foreach ($game_tags as $key => $game_tag)
            {
                if ($game_type == $game_tag['tag_code'])
                {
                    $extra =[
                        'game_type' => $game_tag['tag_name'],
                        'game_type_code' => $game_tag['tag_code'],
                    ];

                    $temp_game_types[$game_type]=[
                        'game_type_id' => $this->CI->game_type_model->checkGameType($game_platform_id,$game_tag['tag_name'],$extra),
                    ];
                }
            }

            if ($game_type =='yoplay' && in_array($game_platform_id, [AGIN_API,T1AGIN_API]))
            {
                $game_type_name = $extra['game_type'] = '_json:{"1":"Yoplay","2":"Yoplay","3":"Yoplay","4":"Yoplay","5":"Yoplay"}';
                $extra['game_type_code'] = "yoplay";

                $temp_game_types[$game_type] = [
                    'game_type_id' => $this->CI->game_type_model->checkGameType($game_platform_id,$game_type_name,$extra),
                ];
            }

            if ($game_type =='tip' && in_array($game_platform_id, [BBIN_API,GSBBIN_API,T1BBIN_API,AGBBIN_API]))
            {
                $game_type_name = $extra['game_type'] = '_json:{"1":"BB Tip","2":"BB 小费","3":"BB Tip","4":"BB Tip","5":"BB Tip"}';

                $temp_game_types[$game_type] = [
                    'game_type_id' => $this->CI->game_type_model->checkGameType($game_platform_id,$game_type_name,$extra),
                ];
            }
        }

        return $temp_game_types;
    }

    /**
     * [validateGameJsonFormat validate if game list json is valid]
     * @param  [string] $game_list [path of the file]
     */
    private function validateGameJsonFormat($game_list)
    {
        $game_list = json_decode($game_list);

        $success = false;
        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                $success = true;
            break;
            case JSON_ERROR_DEPTH:
                $message = ' - Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = ' - Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                $message = ' - Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                $message = ' - Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                $message = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                $message = ' - Unknown error';
            break;
        }
        if (!empty($message))
            $this->utils->debug_log("ERROR validateGameJsonFormat =======>",$message);

        return $success;
    }

    /*
     * This function filters sub game api if sub not found will return original game_platform_id
     * @param int $game_platform_id
     */
    public function filter_sub_game_api($game_platform_id)
    {
    	switch ($game_platform_id) {
            case T1ONEWORKS_API:
                return ONEWORKS_API;
                break;

            case T1PT_API:
                return PT_API;
                break;

            case T1OGPLUS_API:
                return OGPLUS_API;
                break;

    		case GSPT_API:
    			return IMPT_API;
    			break;

    		case T1JUMB_API:
    			return JUMB_GAMING_API;
    			break;

    		case EBET_MG_API:
    			return LAPIS_API;
    			break;

            case T1GG_API:
                return FISHINGGAME_API;
                break;

            case T1VR_API:
                return VR_API;
                break;

            case T1FG_ENTAPLAY_API:
    		case FG_ENTAPLAY_API:
    			return FG_API;
    			break;

    		case FADA_LD_LOTTERY_API:
    			return LD_LOTTERY_API;
    			break;

            case T1_JUMBO_SEAMLESS_GAME_API:
                return JUMBO_SEAMLESS_GAME_API;
                break;
            case T1_WAZDAN_SEAMLESS_GAME_API:
                return WAZDAN_SEAMLESS_GAME_API;
                break;
            
            case EBET_SEAMLESS_GAME_API:
    		case EBET_THB_API:
            case EBET_USD_API:
            case T1EBET_API:
    			return EBET_API;
    			break;

    		case T1SBTECH_BTI_API:
            case SBTECH_BTI_API:
    			return SBTECH_API;
    			break;

            case T1EVOLUTION_API:
    			return EVOLUTION_GAMING_API;
                break;

            case T1_EVOLUTION_SEAMLESS_GAME_API:
            case EVOLUTION_SEAMLESS_THB1_API:
                return EVOLUTION_SEAMLESS_GAMING_API;
                 break;

            case T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
                return EVOLUTION_NETENT_SEAMLESS_GAMING_API;
                    break;

            case T1_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
                return EVOLUTION_NLC_SEAMLESS_GAMING_API;
                    break;
            
            case T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
                return EVOLUTION_REDTIGER_SEAMLESS_GAMING_API;
                    break;

            case T1_EVOLUTION_BTG_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                return EVOLUTION_BTG_SEAMLESS_GAMING_API;
                    break;

    		case MTECH_OG_API:
    			return OG_API;
                break;

            case HABANERO_SEAMLESS_GAMING_CNY1_API:
            case HABANERO_SEAMLESS_GAMING_THB1_API:
            case HABANERO_SEAMLESS_GAMING_MYR1_API:
            case HABANERO_SEAMLESS_GAMING_VND1_API:
            case HABANERO_SEAMLESS_GAMING_USD1_API:
            case HABANERO_SEAMLESS_GAMING_IDR2_API:
            case HABANERO_SEAMLESS_GAMING_IDR3_API:
            case HABANERO_SEAMLESS_GAMING_IDR4_API:
            case HABANERO_SEAMLESS_GAMING_IDR5_API:
            case HABANERO_SEAMLESS_GAMING_IDR6_API:
            case HABANERO_SEAMLESS_GAMING_IDR7_API:
            case HABANERO_SEAMLESS_GAMING_CNY2_API:
            case HABANERO_SEAMLESS_GAMING_CNY3_API:
            case HABANERO_SEAMLESS_GAMING_CNY4_API:
            case HABANERO_SEAMLESS_GAMING_CNY5_API:
            case HABANERO_SEAMLESS_GAMING_CNY6_API:
            case HABANERO_SEAMLESS_GAMING_CNY7_API:
            case HABANERO_SEAMLESS_GAMING_THB2_API:
            case HABANERO_SEAMLESS_GAMING_THB3_API:
            case HABANERO_SEAMLESS_GAMING_THB4_API:
            case HABANERO_SEAMLESS_GAMING_THB5_API:
            case HABANERO_SEAMLESS_GAMING_THB6_API:
            case HABANERO_SEAMLESS_GAMING_THB7_API:
            case HABANERO_SEAMLESS_GAMING_MYR2_API:
            case HABANERO_SEAMLESS_GAMING_MYR3_API:
            case HABANERO_SEAMLESS_GAMING_MYR4_API:
            case HABANERO_SEAMLESS_GAMING_MYR5_API:
            case HABANERO_SEAMLESS_GAMING_MYR6_API:
            case HABANERO_SEAMLESS_GAMING_MYR7_API:
            case HABANERO_SEAMLESS_GAMING_VND2_API:
            case HABANERO_SEAMLESS_GAMING_VND3_API:
            case HABANERO_SEAMLESS_GAMING_VND4_API:
            case HABANERO_SEAMLESS_GAMING_VND5_API:
            case HABANERO_SEAMLESS_GAMING_VND6_API:
            case HABANERO_SEAMLESS_GAMING_VND7_API:
            case HABANERO_SEAMLESS_GAMING_USD2_API:
            case HABANERO_SEAMLESS_GAMING_USD3_API:
            case HABANERO_SEAMLESS_GAMING_USD4_API:
            case HABANERO_SEAMLESS_GAMING_USD5_API:
            case HABANERO_SEAMLESS_GAMING_USD6_API:
            case HABANERO_SEAMLESS_GAMING_USD7_API:
            case HABANERO_SEAMLESS_GAMING_IDR1_API:
            case HABANERO_SEAMLESS_GAMING_API:
            case IDN_HABANERO_SEAMLESS_GAMING_API:
            case T1_IDN_HABANERO_SEAMLESS_GAMING_API:
    		case MTECH_HB_API:
    		case HB_IDR1_API:
    		case HB_IDR2_API:
    		case HB_IDR3_API:
    		case HB_IDR4_API:
            case HB_IDR5_API:
            case HB_IDR6_API:
            case HB_IDR7_API:
    		case HB_THB1_API:
    		case HB_THB2_API:
    		case HB_VND1_API:
            case HB_VND2_API:
            case HB_VND3_API:
    		case HB_CNY1_API:
    		case HB_CNY2_API:
    		case HB_MYR1_API:
    		case HB_MYR2_API:
            case T1HB_API:
    			return HB_API;
    			break;
            case T1_HABANERO_SEAMLESS_GAME_API:
                return HABANERO_SEAMLESS_GAMING_API;
                break;
            case T1PRAGMATICPLAY_API:
    		case PRAGMATICPLAY_IDR1_API:
    		case PRAGMATICPLAY_IDR2_API:
    		case PRAGMATICPLAY_IDR3_API:
    		case PRAGMATICPLAY_IDR4_API:
            case PRAGMATICPLAY_IDR5_API:
            case PRAGMATICPLAY_IDR6_API:
            case PRAGMATICPLAY_IDR7_API:
            case PRAGMATICPLAY_IDR8_API:
            case PRAGMATICPLAY_IDR9_API:
    		case PRAGMATICPLAY_THB1_API:
    		case PRAGMATICPLAY_THB2_API:
    		case PRAGMATICPLAY_CNY1_API:
    		case PRAGMATICPLAY_CNY2_API:
    		case PRAGMATICPLAY_VND1_API:
            case PRAGMATICPLAY_VND2_API:
    		case PRAGMATICPLAY_VND3_API:
    		case PRAGMATICPLAY_MYR1_API:
            case PRAGMATICPLAY_MYR2_API:
            case PRAGMATICPLAY_SEAMLESS_STREAMER_API:
            case PRAGMATICPLAY_SEAMLESS_API:
            case PRAGMATICPLAY_SEAMLESS_IDR1_API:
            case PRAGMATICPLAY_SEAMLESS_CNY1_API:
            case PRAGMATICPLAY_SEAMLESS_THB1_API:
            case PRAGMATICPLAY_SEAMLESS_USD1_API:
            case PRAGMATICPLAY_SEAMLESS_VND1_API:
            case PRAGMATICPLAY_SEAMLESS_MYR1_API:
            case PRAGMATICPLAY_SEAMLESS_IDR2_API:
            case PRAGMATICPLAY_SEAMLESS_CNY2_API:
            case PRAGMATICPLAY_SEAMLESS_THB2_API:
            case PRAGMATICPLAY_SEAMLESS_USD2_API:
            case PRAGMATICPLAY_SEAMLESS_VND2_API:
            case PRAGMATICPLAY_SEAMLESS_MYR2_API:
            case PRAGMATICPLAY_SEAMLESS_IDR3_API:
            case PRAGMATICPLAY_SEAMLESS_CNY3_API:
            case PRAGMATICPLAY_SEAMLESS_THB3_API:
            case PRAGMATICPLAY_SEAMLESS_USD3_API:
            case PRAGMATICPLAY_SEAMLESS_VND3_API:
            case PRAGMATICPLAY_SEAMLESS_MYR3_API:
            case PRAGMATICPLAY_SEAMLESS_IDR4_API:
            case PRAGMATICPLAY_SEAMLESS_CNY4_API:
            case PRAGMATICPLAY_SEAMLESS_THB4_API:
            case PRAGMATICPLAY_SEAMLESS_USD4_API:
            case PRAGMATICPLAY_SEAMLESS_VND4_API:
            case PRAGMATICPLAY_SEAMLESS_MYR4_API:
            case PRAGMATICPLAY_SEAMLESS_IDR5_API:
            case PRAGMATICPLAY_SEAMLESS_CNY5_API:
            case PRAGMATICPLAY_SEAMLESS_THB5_API:
            case PRAGMATICPLAY_SEAMLESS_USD5_API:
            case PRAGMATICPLAY_SEAMLESS_VND5_API:
            case PRAGMATICPLAY_SEAMLESS_MYR5_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_IDR1_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_CNY1_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_THB1_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_USD1_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_VND1_API:
            case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_MYR1_API:
            case PRAGMATICPLAY_LIVEDEALER_IDR1_API:
            case PRAGMATICPLAY_LIVEDEALER_CNY1_API:
            case PRAGMATICPLAY_LIVEDEALER_THB1_API:
            case PRAGMATICPLAY_LIVEDEALER_MYR1_API:
            case PRAGMATICPLAY_LIVEDEALER_VND1_API:
            case PRAGMATICPLAY_LIVEDEALER_USD1_API:
            case PRAGMATIC_PLAY_FISHING_API:
            case T1_IDN_PRAGMATICPLAY_SEAMLESS_API:
            case IDN_PRAGMATICPLAY_SEAMLESS_API:
    			return PRAGMATICPLAY_API;
    			break;
            case T1_PRAGMATICPLAY_SEAMLESS_API:
                return PRAGMATICPLAY_SEAMLESS_API;
                break;
            case T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API:
                return IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API;
                break;
            case T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API:
                return IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API;
                break;
            case T1_MGPLUS_SEAMLESS_GAME_API:
            case T1MGPLUS_API:
            case MGPLUS2_API:
    		case MGPLUS_IDR1_API:
    		case MGPLUS_IDR2_API:
    		case MGPLUS_IDR3_API:
    		case MGPLUS_IDR4_API:
            case MGPLUS_IDR5_API:
            case MGPLUS_IDR6_API:
            case MGPLUS_IDR7_API:
    		case MGPLUS_THB1_API:
    		case MGPLUS_THB2_API:
    		case MGPLUS_CNY1_API:
    		case MGPLUS_CNY2_API:
    		case MGPLUS_VND1_API:
    		case MGPLUS_VND2_API:
            case MGPLUS_VND3_API:
    		case MGPLUS_MYR1_API:
    		case MGPLUS_MYR2_API:
            case MGPLUS_SEAMLESS_IDR1_API:
            case MGPLUS_SEAMLESS_IDR2_API:
            case MGPLUS_SEAMLESS_API:
    			return MGPLUS_API;
    			break;
            case ISB_IDR1_API:
            case ISB_IDR2_API:
            case ISB_IDR3_API:
            case ISB_IDR4_API:
            case ISB_IDR5_API:
            case ISB_IDR6_API:
            case ISB_IDR7_API:
            case ISB_THB1_API:
            case ISB_THB2_API:
            case ISB_THB3_API:
            case ISB_THB4_API:
            case ISB_VND1_API:
            case ISB_VND2_API:
            case ISB_VND3_API:
            case ISB_VND4_API:
            case ISB_VND5_API:
            case ISB_CNY1_API:
            case ISB_CNY2_API:
            case ISB_CNY3_API:
            case ISB_CNY4_API:
            case ISB_MYR1_API:
            case ISB_MYR2_API:
            case ISB_MYR3_API:
            case ISB_MYR4_API:
            case ISB_INR1_API:
            case ISB_SEAMLESS_IDR1_API:
            case ISB_SEAMLESS_THB1_API:
            case ISB_SEAMLESS_CNY1_API:
            case ISB_SEAMLESS_USD1_API:
            case ISB_SEAMLESS_VND1_API:
            case ISB_SEAMLESS_MYR1_API:
            case ISB_SEAMLESS_IDR2_API:
            case ISB_SEAMLESS_THB2_API:
            case ISB_SEAMLESS_CNY2_API:
            case ISB_SEAMLESS_USD2_API:
            case ISB_SEAMLESS_VND2_API:
            case ISB_SEAMLESS_MYR2_API:
            case ISB_SEAMLESS_IDR3_API:
            case ISB_SEAMLESS_THB3_API:
            case ISB_SEAMLESS_CNY3_API:
            case ISB_SEAMLESS_USD3_API:
            case ISB_SEAMLESS_VND3_API:
            case ISB_SEAMLESS_MYR3_API:
            case ISB_SEAMLESS_IDR4_API:
            case ISB_SEAMLESS_THB4_API:
            case ISB_SEAMLESS_CNY4_API:
            case ISB_SEAMLESS_USD4_API:
            case ISB_SEAMLESS_VND4_API:
            case ISB_SEAMLESS_MYR4_API:
            case ISB_SEAMLESS_IDR5_API:
            case ISB_SEAMLESS_THB5_API:
            case ISB_SEAMLESS_CNY5_API:
            case ISB_SEAMLESS_USD5_API:
            case ISB_SEAMLESS_VND5_API:
            case ISB_SEAMLESS_MYR5_API:
            case ISB_SEAMLESS_IDR6_API:
            case ISB_SEAMLESS_THB6_API:
            case ISB_SEAMLESS_CNY6_API:
            case ISB_SEAMLESS_USD6_API:
            case ISB_SEAMLESS_VND6_API:
            case ISB_SEAMLESS_MYR6_API:
            case T1ISB_API:
                return ISB_API;
                break;

            case MTECH_BBIN_API:
            case AGBBIN_API:
            case GSBBIN_API:
            case T1BBIN_API:
            case T1MTECHBBIN_API:
                return BBIN_API;
                break;

            case AB_V2_GAME_API:
            case T1AB_V2_API:
            case T1AB_API:
                return AB_API;
                break;
            case T1AE_SLOTS_API:
                return AE_SLOTS_GAMING_API;
                break;
            case T1DG_API:
                return DG_API;
                break;
            case T1DT_API:
                return DT_API;
                break;
            case T1_BOOMING_SEAMLESS_API:
                return BOOMING_SEAMLESS_API;
                break;
            case T1_CHERRY_GAMING_SEAMLESS_GAME_API:
                return CHERRY_GAMING_SEAMLESS_GAME_API;
                break;
            case T1_BETER_SEAMLESS_GAME_API:
                return BETER_SEAMLESS_GAME_API;
                break;
            case T1_BETER_SPORTS_SEAMLESS_GAME_API:
                return BETER_SPORTS_SEAMLESS_GAME_API;
                break;
                
            case T1PNG_API:
            case T1_PNG_SEAMLESS_API:
            case PNG_SEAMLESS_GAME_API:
                return PNG_API;
                break;
            case T1_AMEBA_SEAMLESS_API:
                return AMEBA_SEAMLESS_GAME_API;
                break;

            case T1GAMES_SEAMLESS_GAME_API:
            case T1_BGSOFT_SEAMLESS_GAME_API:
                return BGSOFT_SEAMLESS_GAME_API;
                break;

            // case T1_MGPLUS_SEAMLESS_GAME_API:
            //     return MGPLUS_SEAMLESS_API;
            //     break;
                
            case T1_SKYWIND_SEAMLESS_GAME_API:
                return SKYWIND_SEAMLESS_GAME_API;
                break;

            case T1_YL_SEAMLESS_GAME_API:
                return YL_NTTECH_SEAMLESS_GAME_API;
                break;

            case T1TTG_API:
            case T1_TTG_SEAMLESS_GAME_API:
            case TTG_SEAMLESS_GAME_API;
                return TTG_API;

            case T1AGIN_API:
            case T1YOPLAY_API:
            case YOPLAY_API:
                return AGIN_API;
                break;

            case T1GD_API:
            case GD_SEAMLESS_API:
                return GD_API;
                break;

            case T1QT_API:
                return QT_API;
                break;

            case T1IPM_V2_SPORTS_API:
                return IPM_V2_SPORTS_API;
                break;

            case T1_SA_GAMING_SEAMLESS_GAME_API:
            case SA_GAMING_SEAMLESS_THB1_API:
            case T1SA_GAMING_API:
            case SA_GAMING_SEAMLESS_API:
                return SA_GAMING_API;
            case T1MWG_API:
                return MWG_API;
                break;

            case T1RTG_MASTER_API:
                return RTG_MASTER_API;
                break;

            case T1LE_GAMING_API:
                return LE_GAMING_API;
                break;

            case T1TIANHAO_API:
                return TIANHAO_API;
                break;

            case T1N2LIVE_API:
                return N2LIVE_API;
                break;

            case T1HG_API:
                return HG_API;
                break;

            case T1_EVOLUTION_SEAMLESS_GAME_API:            
            case T1_IDN_EVOLUTION_SEAMLESS_GAMING_API:            
            case IDN_EVOLUTION_SEAMLESS_GAMING_API:            
                return EVOLUTION_SEAMLESS_GAMING_API;
                break;

            case T1EVOLUTION_API:
                return EVOLUTION_GAMING_API;
                break;

            case T1LOTUS_API:
                return LOTUS_API;
                break;

            case T1SBTECH_BTI_API:
                return SBTECH_BTI_API;
                break;

            case T1CQ9_API:
                return CQ9_API;
                break;
            case T1_CQ9_SEAMLESS_API:
                return CQ9_SEAMLESS_GAME_API;
                break;

            case YGG_SEAMLESS_GAME_API:
            case T1YGGDRASIL_API:
                return YGGDRASIL_API;
                break;

            // case PINNACLE_SEAMLESS_GAME_API:
            //     return PINNACLE_API;
            //     break;
            case T1_PINNACLE_SEAMLESS_GAME_API:
                return PINNACLE_SEAMLESS_GAME_API;
                break;
            
            case AP_GAME_API:
                return PINNACLE_API;
                break;

            case T1YL_NTTECH_GAME_API:
                return YL_NTTECH_GAME_API;
                break;

            case T1OG_API:
            case T1MG_API:
                return MG_API;
                break;

            case T1SUNCITY_API:
                return SUNCITY_API;
                break;

            case T1KYCARD_API:
                return KYCARD_API;
                break;
            case T1EZUGI_API:
                return EZUGI_API;
                break;

            case TANGKAS1_IDR_API:
            case TANGKAS1_CNY_API:
            case TANGKAS1_USD_API:
            case TANGKAS1_VND_API:
            case TANGKAS1_MYR_API:
                return TANGKAS1_API;
                break;

            case T1SPADE_GAMING_API:
                return SPADE_GAMING_API;
                break;

            case HOGAMING_SEAMLESS_API:
            case HOGAMING_IDR_B1_API:
            case HOGAMING_CNY_B1_API:
            case HOGAMING_THB_B1_API:
            case HOGAMING_USD_B1_API:
            case HOGAMING_VND_B1_API:
            case HOGAMING_MYR_B1_API:
            case HOGAMING_SEAMLESS_IDR1_GAME_API:
            case HOGAMING_SEAMLESS_IDR2_GAME_API:
            case HOGAMING_SEAMLESS_IDR3_GAME_API:
            case HOGAMING_SEAMLESS_IDR4_GAME_API:
            case HOGAMING_SEAMLESS_IDR5_GAME_API:
            case HOGAMING_SEAMLESS_IDR6_GAME_API:
            case HOGAMING_SEAMLESS_IDR7_GAME_API:
            case T1HOGAMING_API:
                return HOGAMING_API;
                break;

            // case VIVOGAMING_SEAMLESS_API:
            case VIVOGAMING_SEAMLESS_IDR1_API:
            case VIVOGAMING_SEAMLESS_CNY1_API:
            case VIVOGAMING_SEAMLESS_THB1_API:
            case VIVOGAMING_SEAMLESS_USD1_API:
            case VIVOGAMING_SEAMLESS_VND1_API:
            case VIVOGAMING_SEAMLESS_MYR1_API:
            case VIVOGAMING_IDR_B1_API:
            case VIVOGAMING_CNY_B1_API:
            case VIVOGAMING_THB_B1_API:
            case VIVOGAMING_USD_B1_API:
            case VIVOGAMING_VND_B1_API:
            case VIVOGAMING_MYR_B1_API:
            case VIVOGAMING_IDR_B1_ALADIN_API:
            case T1VIVOGAMING_API:
                return VIVOGAMING_API;
                break;

            case ISIN4D_IDR_B1_API:
            case ISIN4D_CNY_B1_API:
            case ISIN4D_THB_B1_API:
            case ISIN4D_USD_B1_API:
            case ISIN4D_VND_B1_API:
            case ISIN4D_MYR_B1_API:
                return ISIN4D_API;
                break;

            case ION_GAMING_IDR1_API:
                return ION_GAMING_API;

            case QQKENO_QQLOTTERY_IDR_B1_API:
            case QQKENO_QQLOTTERY_CNY_B1_API:
            case QQKENO_QQLOTTERY_THB_B1_API:
            case QQKENO_QQLOTTERY_USD_B1_API:
            case QQKENO_QQLOTTERY_VND_B1_API:
                return QQKENO_QQLOTTERY_API;
                break;

            case NTTECH_IDR_B1_API:
            case NTTECH_CNY_B1_API:
            case NTTECH_THB_B1_API:
            case NTTECH_USD_B1_API:
            case NTTECH_VND_B1_API:
                return NTTECH_API;
                break;

            case NTTECH_V2_IDR_B1_API:
            case NTTECH_V2_CNY_B1_API:
            case NTTECH_V2_THB_B1_API:
            case NTTECH_V2_USD_B1_API:
            case NTTECH_V2_VND_B1_API:
            case NTTECH_V2_INR_B1_API:
            case T1NTTECH_V2_API:
            case T1NTTECH_V2_CNY_B1_API:
            case NTTECH_V3_API:
                return NTTECH_V2_API;
                break;

            case IBC_24TECH_IDR_B1_API:
            case IBC_24TECH_CNY_B1_API:
            case IBC_24TECH_THB_B1_API:
            case IBC_24TECH_USD_B1_API:
            case IBC_24TECH_VND_B1_API:
                return IBC_24TECH_API;
                break;

            case ONEBOOK_IDR_B1_API:
            case ONEBOOK_CNY_B1_API:
            case ONEBOOK_THB_B1_API:
            case ONEBOOK_USD_B1_API:
            case ONEBOOK_VND_B1_API:
            case IBC_ONEBOOK_API:
            case IBC_ONEBOOK_SEAMLESS_API:
            // case T1_IBC_ONEBOOK_SEAMLESS_API:
                return ONEBOOK_API;
                break;
            case T1_IBC_ONEBOOK_SEAMLESS_API:
                return IBC_ONEBOOK_SEAMLESS_API;
                break;

            case SBOBETGAME_IDR_B1_API:
            case SBOBETGAME_CNY_B1_API:
            case SBOBETGAME_THB_B1_API:
            case SBOBETGAME_USD_B1_API:
            case SBOBETGAME_VND_B1_API:
            case SBOBETV2_GAME_API:
            // case SBOBET_SEAMLESS_GAME_API:
                return SBOBETGAME_API;
                break;

            case FLOW_GAMING_SEAMLESS_THB1_API:
            case T1_FLOW_GAMING_SEAMLESS_API:
                return FLOW_GAMING_SEAMLESS_API;
                break;

            case FLOW_GAMING_NETENT_SEAMLESS_THB1_API:
                return FLOW_GAMING_NETENT_SEAMLESS_API;
                break;

            case FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API:
                return FLOW_GAMING_YGGDRASIL_SEAMLESS_API;
                break;

            case FLOW_GAMING_MAVERICK_SEAMLESS_THB1_API:
                return FLOW_GAMING_MAVERICK_SEAMLESS_API;
                break;

            case FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API:
            case T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API:
                return FLOW_GAMING_QUICKSPIN_SEAMLESS_API;
                break;

            case FLOW_GAMING_PNG_SEAMLESS_THB1_API:
                return FLOW_GAMING_PNG_SEAMLESS_API;
                break;

            case FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API:
                return FLOW_GAMING_4THPLAYER_SEAMLESS_API;
                break;

            case FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API:
                return FLOW_GAMING_RELAXGAMING_SEAMLESS_API;
                break;

            case T1RG_API:
                return RG_API;
                break;

            case T1SLOTFACTORY_API:
            case SLOT_FACTORY_GAME_API:
                return SLOT_FACTORY_SEAMLESS_API;
                break;

            case GENESIS_SEAMLESS_THB1_API:
                return GENESIS_SEAMLESS_API;
                break;

            case KING_MAKER_GAMING_THB_B1_API:
            case KING_MAKER_GAMING_THB_B2_API:
                return KING_MAKER_GAMING_API;
                break;

            case T1_SV388_SEAMLESS_GAME_API:
                return SV388_SEAMLESS_GAME_API;
                break;
                
            case SV388_SEAMLESS_GAME_API:
            case SV388_GAMING_THB_B1_API:
                return SV388_GAME_API;
                break;

            case AG_SEAMLESS_THB1_API:
                return AG_SEAMLESS_GAME_API;
                break;

            case LUCKY_STREAK_SEAMLESS_THB1_API:
                return LUCKY_STREAK_SEAMLESS_GAME_API;
                break;

            case ICONIC_SEAMLESS_API:
                return ICONIC_SEAMLESS_API;
                break;

            case HYDAKO_IDR1_API:
            case HYDAKO_CNY1_API:
            case HYDAKO_THB1_API:
            case HYDAKO_MYR1_API:
            case HYDAKO_VND1_API:
            case HYDAKO_USD1_API:
                return HYDAKO_GAME_API;
                break;

            case T1TFGAMING_ESPORTS_API:
                return TFGAMING_ESPORTS_API;
                break;

            case EA_GAME_API_THB1_API:
                return EA_GAME_API;
                break;

            case T1_EVOPLAY_SEAMLESS_GAME_API:
                return EVOPLAY_SEAMLESS_GAME_API;
                break;

            case KINGPOKER_GAME_API_IDR1_API:
            case KINGPOKER_GAME_API_CNY1_API:
            case KINGPOKER_GAME_API_THB1_API:
            case KINGPOKER_GAME_API_MYR1_API:
            case KINGPOKER_GAME_API_VND1_API:
            case KINGPOKER_GAME_API_USD1_API:
                return KINGPOKER_GAME_API;
                break;

            case EVOPLAY_GAME_API_IDR1_API:
            case EVOPLAY_GAME_API_CNY1_API:
            case EVOPLAY_GAME_API_THB1_API:
            case EVOPLAY_GAME_API_MYR1_API:
            case EVOPLAY_GAME_API_VND1_API:
            case EVOPLAY_GAME_API_USD1_API:
                return EVOPLAY_GAME_API;
                break;

            case RUBYPLAY_SEAMLESS_IDR1_API:
            case RUBYPLAY_SEAMLESS_CNY1_API:
            case RUBYPLAY_SEAMLESS_THB1_API:
            case RUBYPLAY_SEAMLESS_MYR1_API:
            case RUBYPLAY_SEAMLESS_VND1_API:
            case RUBYPLAY_SEAMLESS_USD1_API:
                return RUBYPLAY_SEAMLESS_API;
                break;

            case SPORTSBOOK_FLASH_TECH_GAME_IDR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_CNY1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_THB1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_MYR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_VND1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_USD1_API:
            case T1SPORTSBOOK_FLASH_TECH_GAME_API:
                return SPORTSBOOK_FLASH_TECH_GAME_API;
            break;

            case BETGAMES_SEAMLESS_IDR1_GAME_API:
            case BETGAMES_SEAMLESS_CNY1_GAME_API:
            case BETGAMES_SEAMLESS_THB1_GAME_API:
            case BETGAMES_SEAMLESS_MYR1_GAME_API:
            case BETGAMES_SEAMLESS_VND1_GAME_API:
            case BETGAMES_SEAMLESS_USD1_GAME_API:
                return BETGAMES_SEAMLESS_GAME_API;
                break;

            case PRETTY_GAMING_SEAMLESS_API_IDR1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API_CNY1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API_THB1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API_MYR1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API_VND1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API_USD1_GAME_API:
            case PRETTY_GAMING_API_IDR1_GAME_API:
            case PRETTY_GAMING_API_CNY1_GAME_API:
            case PRETTY_GAMING_API_THB1_GAME_API:
            case PRETTY_GAMING_API_MYR1_GAME_API:
            case PRETTY_GAMING_API_VND1_GAME_API:
            case PRETTY_GAMING_API_USD1_GAME_API:
            case PRETTY_GAMING_SEAMLESS_API:
                return PRETTY_GAMING_API;
                break;

            // case QUEEN_MAKER_REDTIGER_GAME_API:
            //     return QUEEN_MAKER_GAME_API;
            //     break;

            case NETENT_SEAMLESS_GAME_API:
            case NETENT_SEAMLESS_GAME_IDR1_API:
            case NETENT_SEAMLESS_GAME_CNY1_API:
            case NETENT_SEAMLESS_GAME_THB1_API:
            case NETENT_SEAMLESS_GAME_MYR1_API:
            case NETENT_SEAMLESS_GAME_VND1_API:
            case NETENT_SEAMLESS_GAME_USD1_API:
                return NETENT_GAME_API;
            break;

            case BG_SEAMLESS_GAME_IDR1_API:
            case BG_SEAMLESS_GAME_CNY1_API:
            case BG_SEAMLESS_GAME_THB1_API:
            case BG_SEAMLESS_GAME_MYR1_API:
            case BG_SEAMLESS_GAME_VND1_API:
            case BG_SEAMLESS_GAME_USD1_API:
                return BG_SEAMLESS_GAME_API;
            break;

            case DONGSEN_ESPORTS_IDR1_API:
            case DONGSEN_ESPORTS_CNY1_API:
            case DONGSEN_ESPORTS_THB1_API:
            case DONGSEN_ESPORTS_USD1_API:
            case DONGSEN_ESPORTS_VND1_API:
            case DONGSEN_ESPORTS_MYR1_API:
                return DONGSEN_ESPORTS_API;

            case IONGAMING_SEAMLESS_IDR1_GAME_API:
            case IONGAMING_SEAMLESS_IDR2_GAME_API:
            case IONGAMING_SEAMLESS_IDR3_GAME_API:
            case IONGAMING_SEAMLESS_IDR4_GAME_API:
            case IONGAMING_SEAMLESS_IDR5_GAME_API:
            case IONGAMING_SEAMLESS_IDR6_GAME_API:
            case IONGAMING_SEAMLESS_IDR7_GAME_API:
            #ION Gaming Seamless CNY
            case IONGAMING_SEAMLESS_CNY1_GAME_API:
            case IONGAMING_SEAMLESS_CNY2_GAME_API:
            case IONGAMING_SEAMLESS_CNY3_GAME_API:
            case IONGAMING_SEAMLESS_CNY4_GAME_API:
            case IONGAMING_SEAMLESS_CNY5_GAME_API:
            case IONGAMING_SEAMLESS_CNY6_GAME_API:
            case IONGAMING_SEAMLESS_CNY7_GAME_API:
            #ION Gaming Seamless THB
            case IONGAMING_SEAMLESS_THB1_GAME_API:
            case IONGAMING_SEAMLESS_THB2_GAME_API:
            case IONGAMING_SEAMLESS_THB3_GAME_API:
            case IONGAMING_SEAMLESS_THB4_GAME_API:
            case IONGAMING_SEAMLESS_THB5_GAME_API:
            case IONGAMING_SEAMLESS_THB6_GAME_API:
            case IONGAMING_SEAMLESS_THB7_GAME_API:
            #ION Gaming Seamless MYR
            case IONGAMING_SEAMLESS_MYR1_GAME_API:
            case IONGAMING_SEAMLESS_MYR2_GAME_API:
            case IONGAMING_SEAMLESS_MYR3_GAME_API:
            case IONGAMING_SEAMLESS_MYR4_GAME_API:
            case IONGAMING_SEAMLESS_MYR5_GAME_API:
            case IONGAMING_SEAMLESS_MYR6_GAME_API:
            case IONGAMING_SEAMLESS_MYR7_GAME_API:
                return IONGAMING_SEAMLESS_GAME_API;
            case BG_GAME_API:
                return BG_SEAMLESS_GAME_API;
            case T1_WM2_SEAMLESS_GAME_API:
            case WM2_SEAMLESS_GAME_API:
            case T1_WM_SEAMLESS_GAME_API:
            case WM_SEAMLESS_GAME_API:
            case T1WM_API:
                return WM_API;
                break;
            case BDM_SEAMLESS_API:
                return JOKER_API;
                break;

            case T1_PGSOFT3_SEAMLESS_API:
            case T1_PGSOFT2_SEAMLESS_API:
            case T1_PGSOFT_SEAMLESS_API:
            case T1_IDN_PGSOFT_SEAMLESS_API:
                return PGSOFT_SEAMLESS_API;
                break;
            case T1_SEXY_BACCARAT_SEAMLESS_API:
                return SEXY_BACCARAT_SEAMLESS_API;
                break;
            case T1_BIGPOT_SEAMLESS_GAME_API:            
                return BIGPOT_SEAMLESS_GAME_API;
                break;
            case T1_EZUGI_EVO_SEAMLESS_GAME_API:
                return EZUGI_EVO_SEAMLESS_API;
                break;
            case T1_FC_SEAMLESS_GAME_API:
                return FC_SEAMLESS_GAME_API;
                break;  
            case T1_JILI_SEAMLESS_API:
                return JILI_SEAMLESS_API;
                break;   
            case T1_TADA_SEAMLESS_GAME_API:
                return TADA_SEAMLESS_GAME_API;
                break;
            case T1_EZUGI_NETENT_SEAMLESS_GAME_API:
                return EZUGI_NETENT_SEAMLESS_API;
                break;
            case T1_EZUGI_SEAMLESS_GAME_API:
                return EZUGI_SEAMLESS_API;
                break;
            case T1_EZUGI_REDTIGER_SEAMLESS_GAME_API:
                return EZUGI_REDTIGER_SEAMLESS_API;
                break;
            case T1_JOKER_SEAMLESS_GAME_API:
                return JOKER_API;
            // case T1_PNG_SEAMLESS_API:
            //     return PNG_SEAMLESS_GAME_API;
            //     break;

            case T1_EBET_SEAMLESS_GAME_API:
                return EBET_SEAMLESS_GAME_API;
    			break;
            case T1_GFG_SEAMLESS_GAME_API:
                return GFG_SEAMLESS_GAME_API;
                break;
            case T1_BTI_SEAMLESS_GAME_API:
                return BTI_SEAMLESS_GAME_API;
                break;
            case T1_SPADEGAMING_SEAMLESS_GAME_API:
            case T1_IDN_SPADEGAMING_SEAMLESS_GAME_API:
            case IDN_SPADEGAMING_SEAMLESS_GAME_API:
                return SPADEGAMING_SEAMLESS_GAME_API;
                break;
            case T1_BGAMING_SEAMLESS_GAME_API:
                return BGAMING_SEAMLESS_GAME_API;
            case T1_BOOMING_SEAMLESS_GAME_API:
                return BOOMING_SEAMLESS_GAME_API;
            case T1_SBOBET_SEAMLESS_API:
                return SBOBET_SEAMLESS_GAME_API;
                break;
            case T1_CALETA_SEAMLESS_API:
                return CALETA_SEAMLESS_API;
                break;

            
            case T1_PGSOFT3_SEAMLESS_API:
            case T1_PGSOFT2_SEAMLESS_API:
            case PGSOFT3_SEAMLESS_API:
            case PGSOFT2_SEAMLESS_API:
            case PGSOFT_SEAMLESS_API:
            case PGSOFT3_API:
            case IDN_PGSOFT_SEAMLESS_API:
            case T1_IDN_PGSOFT_SEAMLESS_API:
                return PGSOFT_API;
                break;
                
            case T1_CMD_SEAMLESS_GAME_API:
            case T1_CMD2_SEAMLESS_GAME_API:
            case CMD2_SEAMLESS_GAME_API:
                return CMD_SEAMLESS_GAME_API;
            case T1_SV388_AWC_SEAMLESS_GAME_API:
                return SV388_AWC_SEAMLESS_GAME_API;
            case T1_AFB_SBOBET_SEAMLESS_GAME_API:
                return AFB_SBOBET_SEAMLESS_GAME_API;
            case T1_SPRIBE_JUMBO_SEAMLESS_GAME_API:
                return SPRIBE_JUMBO_SEAMLESS_GAME_API;
            case T1_YGG_DCS_SEAMLESS_GAME_API:
                return YGG_DCS_SEAMLESS_GAME_API;
            case T1_WE_SEAMLESS_GAME_API:
                return WE_SEAMLESS_GAME_API;
            case T1_HACKSAW_DCS_SEAMLESS_GAME_API:
                return HACKSAW_DCS_SEAMLESS_GAME_API;
            case T1_QT_HACKSAW_SEAMLESS_API:
                return QT_HACKSAW_SEAMLESS_API;
            case T1_QT_NOLIMITCITY_SEAMLESS_API:
                return QT_NOLIMITCITY_SEAMLESS_API;
            case T1_VIVOGAMING_SEAMLESS_API:
                return VIVOGAMING_SEAMLESS_API;
            case T1_KING_MAKER_SEAMLESS_GAME_API:
                return KING_MAKER_SEAMLESS_GAME_API;
            case T1_BETIXON_SEAMLESS_GAME_API:
                return BETIXON_SEAMLESS_GAME_API;
            case T1_MGW_SEAMLESS_GAME_API:
                return MGW_SEAMLESS_GAME_API;
            // case HACKSAW_PARIPLAY_SEAMLESS_API:
            // case AMATIC_PARIPLAY_SEAMLESS_API:
            // case BEFEE_PARIPLAY_SEAMLESS_API:
            // case OTG_GAMING_PARIPLAY_SEAMLESS_API:
            // case HIGH5_PARIPLAY_SEAMLESS_API:
            // case PLAYSON_PARIPLAY_SEAMLESS_API:
            // case ORYX_PARIPLAY_SEAMLESS_API:
            //     return PARIPLAY_SEAMLESS_API;
            case T1_YEEBET_SEAMLESS_GAME_API:
                return YEEBET_SEAMLESS_GAME_API;
            case T1_ULTRAPLAY_SEAMLESS_GAME_API:
            case ULTRAPLAY_SEAMLESS_GAME_API:
                return ULTRAPLAY_API;
            case T1_SPINOMENAL_SEAMLESS_GAME_API:
                return SPINOMENAL_SEAMLESS_GAME_API;
            case T1_SMARTSOFT_SEAMLESS_GAME_API:
                return SMARTSOFT_SEAMLESS_GAME_API;
            case T1_WON_CASINO_SEAMLESS_GAME_API:
                return WON_CASINO_SEAMLESS_GAME_API;
            case T1_ASTAR_SEAMLESS_GAME_API:
                return ASTAR_SEAMLESS_GAME_API;
            case T1_BETGAMES_SEAMLESS_GAME_API:
                return BETGAMES_SEAMLESS_GAME_API;
            case T1_TWAIN_SEAMLESS_GAME_API:
                return TWAIN_SEAMLESS_GAME_API;
            case T1_IM_SEAMLESS_GAME_API:
                return IM_SEAMLESS_GAME_API;
            case HP_2D3D_GAME_API:
                return OM_LOTTO_GAME_API;
            case T1_AVATAR_UX_DCS_SEAMLESS_GAME_API:
                return AVATAR_UX_DCS_SEAMLESS_GAME_API;
            case T1_HACKSAW_SEAMLESS_GAME_API:
                return HACKSAW_SEAMLESS_GAME_API;
            case T1_RELAX_DCS_SEAMLESS_GAME_API:
                return RELAX_DCS_SEAMLESS_GAME_API;
            case KING_MIDAS_GAME_API:
                return QUEEN_MAKER_GAME_API; #looks like QUEEN_MAKER_GAME_API is up to date than KING_MAKER_GAMING_API
            case T1_ENDORPHINA_SEAMLESS_GAME_API:
                return ENDORPHINA_SEAMLESS_GAME_API;
            case T1_BELATRA_SEAMLESS_GAME_API:
                return BELATRA_SEAMLESS_GAME_API;
            case T1_NEXTSPIN_SEAMLESS_GAME_API:
                return NEXTSPIN_SEAMLESS_GAME_API;
            case T1_PEGASUS_SEAMLESS_GAME_API:
                return PEGASUS_SEAMLESS_GAME_API;
            case T1_BNG_SEAMLESS_GAME_API:
                return BNG_SEAMLESS_GAME_API;
            case T1_SPINIX_SEAMLESS_GAME_API:
                return SPINIX_SEAMLESS_GAME_API;
            case T1_FASTSPIN_SEAMLESS_GAME_API:
                return FASTSPIN_SEAMLESS_GAME_API;
            case RTG2_SEAMLESS_GAME_API:
            case T1_RTG2_SEAMLESS_GAME_API:
            case T1_RTG_SEAMLESS_GAME_API:
                return RTG_SEAMLESS_GAME_API;
            case T1_DRAGOONSOFT_SEAMLESS_GAME_API:
                return DRAGOONSOFT_SEAMLESS_GAME_API;
            case T1_MASCOT_SEAMLESS_GAME_API:
                return MASCOT_SEAMLESS_GAME_API;
            case T1_POPOK_GAMING_SEAMLESS_GAME_API:
                return POPOK_GAMING_SEAMLESS_GAME_API;
            case T1_MPOKER_SEAMLESS_GAME_API:
                return MPOKER_SEAMLESS_GAME_API;
            case T1_REDGENN_PLAYSON_SEAMLESS_GAME_API:
            case REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API:
                return REDGENN_PLAYSON_SEAMLESS_GAME_API;
            case T1_ONE_TOUCH_SEAMLESS_GAME_API:
                return ONE_TOUCH_SEAMLESS_GAME_API;
            case T1_AB_SEAMLESS_GAME_API:
                return AB_SEAMLESS_GAME_API;
            case T1_CREEDROOMZ_SEAMLESS_GAME_API:
                return CREEDROOMZ_SEAMLESS_GAME_API;
            case SIMPLEPLAY_SEAMLESS_GAME_API:
                return SIMPLEPLAY_GAME_API;
            case T1_PASCAL_SEAMLESS_GAME_API:
                return PASCAL_SEAMLESS_GAME_API;
            case T1_LIGHTNING_SEAMLESS_GAME_API:
                return LIGHTNING_SEAMLESS_GAME_API;
            case T1_AVIATRIX_SEAMLESS_GAME_API:
                return AVIATRIX_SEAMLESS_GAME_API;
            case T1_HOLI_SEAMLESS_GAME_API:
                return HOLI_SEAMLESS_GAME_API;
            case T1_WORLDMATCH_CASINO_SEAMLESS_API:
                return WORLDMATCH_CASINO_SEAMLESS_API;
            case T1_BFGAMES_SEAMLESS_GAME_API:
                return BFGAMES_SEAMLESS_GAME_API;
                break;
            case T1_TOM_HORN_SEAMLESS_GAME_API:
            case T1_TOM_HORN2_SEAMLESS_GAME_API:
            case TOM_HORN2_SEAMLESS_GAME_API:
                return TOM_HORN_SEAMLESS_GAME_API;
                break;
            case IDN_PT_SEAMLESS_GAME_API:
            case T1_IDN_PT_SEAMLESS_GAME_API:
            case T1_PT_SEAMLESS_GAME_API:
                return PT_SEAMLESS_GAME_API;
            case T1_IDN_SLOTS_PT_SEAMLESS_GAME_API:
                return IDN_SLOTS_PT_SEAMLESS_GAME_API;
            case T1_IDN_LIVE_PT_SEAMLESS_GAME_API:
                return IDN_LIVE_PT_SEAMLESS_GAME_API;
            case T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
                return IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API;
                break;
            case T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API:
                return IDN_LIVE_MGPLUS_SEAMLESS_GAME_API; 
                break;
            case T1_IDN_PLAYSTAR_SEAMLESS_GAME_API:
            case IDN_LIVE_MGPLUS_SEAMLESS_GAME_API:
                return PLAYSTAR_SEAMLESS_GAME_API; 
                break;
            case T1_FIVEG_GAMING_SEAMLESS_API:
                return FIVEG_GAMING_SEAMLESS_API;
            case T1_IDN_PLAY_SEAMLESS_GAME_API:
                return IDN_PLAY_SEAMLESS_GAME_API;
            case T1_FA_WS168_SEAMLESS_GAME_API:
                return FA_WS168_SEAMLESS_GAME_API;
    		default:
    			return $game_platform_id;
    			break;
    	}
    }

    /*
     * do_sync_game_list_from_gamegateway
     * - this feature will sync game list from gamegateway to client database
     * @param  [string] $game_platform_id - canbe null, means all games
     */
	public function do_sync_game_list_from_gamegateway($game_platform_id=null, $processActiveGames = false)
	{
        $this->CI->load->model(['game_description_model','game_type_model','external_system']);

        $active_game_apis = $this->CI->external_system->getAllActiveSytemGameApi();

        $game_api_to_sync_gamelist = $active_game_apis;
        if($game_platform_id)
        {
        	$game_api_to_sync_gamelist = $this->processValidGameApi($game_platform_id,$active_game_apis);
        	if($game_api_to_sync_gamelist['success'])
        	{
        		$game_api_to_sync_gamelist = $game_api_to_sync_gamelist['game_api'];
        	}
        	else
        	{
        		echo json_encode($game_api_to_sync_gamelist['message']); return;
        	}
        }
       return $this->processSyncGameList($game_api_to_sync_gamelist, $processActiveGames);
    }

    private function processSyncGameList($game_api_to_sync_gamelist, $processActiveGames = false)
    {
    	$exempted_game_type_codes_for_sync = $this->getExemptedGameTypeForSync();
    	$game_list_src_url = $this->CI->utils->getConfig("game_list_api_url")."/game_description/getAllGames";
        if ($processActiveGames === true) {
            $game_list_src_url = $this->CI->utils->getConfig("game_list_api_url")."/game_description/getAllActiveGames";
        }
    	$game_tags = $this->CI->game_type_model->getAllGameTags();
        $standard_game_type_codes = array_column($game_tags, 'tag_code');

        foreach ($game_api_to_sync_gamelist as $key => $row)
        {
            $game_map = [];
            $game_provider_id = $row['id'];
            $orig_game_provider_id = $this->filter_sub_game_api($game_provider_id);
            
            // if ($this->utils->isEnabledMDB()){
            //     $gamelist_api_url = $game_list_src_url ."/". $orig_game_provider_id . '?__OG_TARGET_DB=' . $this->utils->getActiveCurrencyKeyOnMDB();
            // } else {
                $gamelist_api_url = $game_list_src_url ."/". $orig_game_provider_id;
            // }

            $game_gateway_games = file_get_contents($gamelist_api_url);
            $this->utils->debug_log("gamelist_api_url ==========>" . $gamelist_api_url);

            # Get game list from gamegateway
            $game_gateway_games_list = json_decode($game_gateway_games,true);

            // if ($processActiveGames === true) {
            //     $aGameCodes = array_column($game_gateway_games_list, 'game_code');
            //     $this->utils->debug_log("gamelist_api_url ==========>" . $game_provider_id);
            //     $aResult = $this->CI->game_description_model->updateActiveGameListOnClient($game_api_to_sync_gamelist['game_platform']['id'], $aGameCodes);
            //     $iCountResult = count($aResult);
            //     $response = [
            //                  "Success" => true,
            //                  "Updated games count"   => $iCountResult,
            //                  "Games"  => $aResult
            //                 ];
            //     return json_encode($response);
            // }


          	# check if game api is active
            $api = $this->utils->loadExternalSystemLibObject($game_provider_id);
            if (empty($api)) continue;

            # records action
            $this->utils->recordAction('game_list', 'dev_manual_sync_gamelist_from_gamegateway', "Manual Sync Game List: ".$row['system_name']);

            # Get current game list from client db
            $local_games = $api->getGameList(null,null,null,null,true);

            # check if game list from gamegateway is not empty
            if (empty($game_gateway_games_list)) continue;

            $this->utils->debug_log("sync_current_games ==========>" . $game_provider_id);

            # loop game list data from gamegateway
            foreach ($game_gateway_games_list as $key => $game)
            {
            	# check if game provide id is not empty
            	if (empty($game['game_platform_id'])) continue;

                $game['game_platform_id'] = $game_provider_id;

                # per game preparation
                $game['game_type_id'] = $this->CI->game_type_model->getGameTypeId($game['game_type_code'],$game['game_platform_id']);

                #don't sync if game type is not standard
                if (!in_array($game['game_type_code'], $exempted_game_type_codes_for_sync))
                {
                	if (!in_array($game['game_type_code'], $standard_game_type_codes)) continue;
                }

                if(empty($game['game_type_id']))
                {
                    $game['game_type_id'] = $this->CI->game_type_model->checkGameType($game['game_platform_id'],$game['game_type'],$game);
                }

                foreach (self::DEFAULT_GAMETYPE_KEYS_FOR_UNSET as $key) unset($game[$key]);

                # end, ready for game synchronizing
                array_push($game_map,$game);
            }

			unset($game_gateway_games_list,$game_gateway_games);

            $response = [
            			 "success"=>true,
            			 "result"=>$this->CI->game_description_model->devSyncGameDescriptionInGameGateway($game_map,$processActiveGames)
            			];
            return json_encode($response);
        }
    }

    private function processValidGameApi($game_platform_id,$active_game_apis)
    {
    	$game_apis = [];
    	if($game_platform_id && !in_array($game_platform_id, ['null','false'])) {
        	$available_game_providers = array_column($active_game_apis, 'id');
        	$game_api_name = null;

        	// gets the game_api_name here
        	array_walk($active_game_apis,function($k,$v)use($game_platform_id,&$game_api_name){
        		if($k['id'] == $game_platform_id) $game_api_name = $k['system_name'];
        	});

        	if(!in_array($game_platform_id, $available_game_providers)) {
	        	return [
	        			"success"=>false,
	        			"message"=>"Error: Game platform ID Not found!"
	        		   ];
        	}else{
        		$game_apis = ['game_platform'=>['id'=>$game_platform_id,"system_name"=>$game_api_name]];
        	}

        }
        return ["success"=>true,"game_api"=>$game_apis];
    }

    private function getExemptedGameTypeForSync()
    {
    	$exempted_game_type_codes_for_sync = self::DEFAULT_EXEMPTED_GAME_TYPES_CODES_FORSYNC;
    	if (!empty($this->utils->getConfig('exempted_game_type_codes_for_sync')))
    		$exempted_game_type_codes_for_sync = $this->CI->utils->getConfig('exempted_game_type_codes_for_sync');

    	return $exempted_game_type_codes_for_sync;
    }

    private function showErrorMessage($result)
    {
        $this->utils->debug_log("ERROR",$result);
        $result['success'] = true;
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
