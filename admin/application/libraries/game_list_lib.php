<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/modules/game_list_module.php';

class Game_list_lib {

    use game_list_module;
    const DEFAULT_EXEMPTED_GAME_TYPES_CODES_FORSYNC = [
                                                        'yoplay',
                                                        'tip',
                                                      ];
    const DEFAULT_GAMETYPE_KEYS_FOR_UNSET = [
                                             'id',
                                             'game_tag_id',
                                             'game_type_code',
                                             'game_type',
                                             'created_at',
                                             'updated_at',
                                             'md5_fields'
                                            ];
    public $_app_prefix;

    function __construct() {
        $this->CI = &get_instance();

        $this->_app_prefix=try_get_prefix();

        //get db name , if it's not og, use it, if it's og, use hostname
        // $default_db=config_item('db.default.database');
        // if($default_db!='og'){
        //     $this->_app_prefix=$default_db;
        // }else{
        //     static $_log;
        //     $_log = &load_class('Log');

        //     $this->_app_prefix=$_log->getHostname();
        // }

        // $is_staging=config_item('RUNTIME_ENVIRONMENT')=='staging';
        // if($is_staging && strpos($this->_app_prefix, 'staging')===false){
        //     //try append staging
        //     $this->_app_prefix.='_staging';
        // }

        $this->utils = $this->CI->utils;
    }

    const TAG_CODE_FISHING_GAME     = 'fishing_game';
    const TAG_CODE_SLOT             = 'slots';
    const TAG_CODE_LOTTERY          = 'lottery';
    const TAG_CODE_LIVE_DEALER      = 'live_dealer';
    const TAG_CODE_CASINO           = 'casino';
    const TAG_CODE_GAMBLE           = 'gamble';
    const TAG_CODE_TABLE_GAMES      = 'table_games';
    const TAG_CODE_TABLE_AND_CARDS  = 'table_and_cards';
    const TAG_CODE_CARD_GAMES       = 'card_games';
    const TAG_CODE_E_SPORTS         = 'e_sports';
    const TAG_CODE_FIXED_ODDS       = 'fixed_odds';
    const TAG_CODE_ARCADE           = 'arcade';
    const TAG_CODE_HORCE_RACING     = 'horce_racing';
    const TAG_CODE_PROGRESSIVES     = 'progressives';
    const TAG_CODE_SPORTS           = 'sports';
    const TAG_CODE_UNKNOWN_GAME     = 'unknown';
    const TAG_CODE_VIDEO_POKER      = 'video_poker';
    const TAG_CODE_POKER            = 'poker';
    const TAG_CODE_MINI_GAMES       = 'mini_games';
    const TAG_CODE_OTHER            = 'others';
    const TAG_CODE_SOFT_GAMES       = 'soft_games';
    const TAG_CODE_SCRATCH_CARD     = 'scratch_card';
    const TAG_CODE_BONUS_BUY        = 'bonuy_buy';

    const TAG_CODE_LOTTERY_KENO     = 'lottery_keno';
    const TAG_CODE_LOTTERY_THAI     = 'lottery_thailottery';
    const TAG_CODE_LOTTERY_SODE     = 'lottery_sode';
    const TAG_CODE_LOTTERY_SABAIDEE = 'lottery_sabaidee';

    const GAME_PLATFORM_HTML5 = "html5";
    const GAME_PLATFORM_IOS = "ios";
    const GAME_PLATFORM_FLASH = "flash";
    const GAME_PLATFORM_MOBILE = "mobile";
    const GAME_PLATFORM_ANDROID = "android";

    const ENGLISH_LANG_CODE = 1;
    const CHINESE_LANG_CODE = 2;
    const INDONESIAN_LANG_CODE = 3;
    const VIETNAMESE_LANG_CODE = 4;
    const KOREAN_LANG_CODE = 5;
    const THAI_LANG_CODE = 6;

    const FILTER_FLAG_SHOW_IN_SITE = true;

    private $game_apis_on_maintenance = [];

    /**
     * [getFrontEndGames generate available games game link
     *  How to use this function:
     *      - player.<client_site>/pub/get_front_end_games/<game_platform_id>/<game_type_code>
     *      - sample url: http://player.gamegateway.t1t.games/pub/get_frontend_games/1
     *      - when game_platform_id is empty, this function will show the available game provider with its details and you can use any game_platform_id value as parameter
     *      - as for the game type code, if you put a random text it will show the possible game type codes and you can use it as parameter
     *    System Feature: allow_generate_inactive_game_api_game_lists
     *      - this function have feature that only able the user to view the available game links with current active game api only
     *
     *  Fields description:
     *  'game_type_code' - can be use to determine which game type should be displayed
     *  'game_name' - full game name of the game
     *  'game_name_en' - english game name
     *  'game_name_cn' - chinese game name
     *  'game_name_indo' - indonesian game name
     *  'game_name_vn' - vietnamese game name
     *  'game_name_kr' - korean game name
     *  'provider_name' - Game provider full name
     *  'game_id_desktop' - game launch code for desktop
     *  'game_id_mobile' - game launch code for mobile
     *  'in_flash' - distinction if game is launchable on web only
     *  'in_html5' - distinction if game is launchable on mobile and web
     *  'mobile_enabled' - distinction if game is launchable on mobile only
     *  'downloadable' - distinction if game is downloadable
     *  'available_on_android' - distinction if game is launchable on android web version
     *  'available_on_ios' - distinction if game is launchable on ios web version
     *  'note' - note about the game
     *  'status' - status if game is enabled or not
     *  'top_game_order' - game order of the game most likely for top games
     *  'enabled_freespin' - distinction if game have free spin or not
     *  'sub_game_provider' - name of the game provider
     *  'flag_new_game' - status of the game if new or not
     *  'flag_show_in_site' - status of the game if can be viewable in site
     *  'progressive' - status if the game is/have jackpot or not
     *  'game_launch_url' - this is the available game links for the game
     *  'game_launch_code_other_settings' - this fields contains the other details for game link
     *  ]
     * @param  [int] $game_platform_id [defined game provider id]
     * @param  [string] $game_type_code   [show specific game type games only]
     * @return [type]                   [description]
     */
    public function getFrontEndGames($game_platform_id = null, $game_type_code = null, $game_platform = null, $extra = null){
        $this->CI->load->model(['game_description_model','game_type_model','external_system']);

        $game_apis = $this->CI->external_system->getActivedGameApiList();
        foreach($game_apis as $key => $value){
            if(in_array($value,$this->CI->utils->getConfig('except_game_api_list'))){
                unset($game_apis[$key]);
            }
        }

        if (empty($game_platform_id)) {
            $data = $this->getGameProviderDetails();
            if (!$this->CI->utils->isEnabledFeature('allow_generate_inactive_game_api_game_lists')) {
                $temp_data = [];
                foreach ($data['available_game_providers'] as $key => $game_provider) {
                    if(in_array($game_provider['game_platform_id'], $game_apis)){
                        $temp_data[$key] = $game_provider;
                    }
                }
                $data['available_game_providers'] = $temp_data;
                unset($temp_data);
            }

        }elseif(!empty($game_platform_id)){
            if (!$this->CI->utils->isEnabledFeature('allow_generate_inactive_game_api_game_lists')) {
                if (!in_array($game_platform_id, $game_apis))
                    return false;
            }
            
            if($this->CI->utils->getConfig('set_game_list_default_order_by_to_game_order')){
                if(empty($extra['order_by']) ){
                    $extra['order_by'] = "game_order";
                } 
            }
 
            $this->checkGameProviderGamelist($game_platform_id, $game_type_code, $data, $game_platform, $extra);
        }

        if (isset($data['game_list'])) {
            $game_list = [];
            foreach ($data['game_list'] as $key => $game) {
                $game_list[$key] = $this->prepareFrontendGames($game);
            }
            $data['game_list'] = $game_list;
            unset($game_list);
        }
        #remove sorting on array, added on query
        //sorting of gamelist by game order and game unique id
        // if(isset($data['game_list']))
        // {
        //     $game_order_top =  array_column($data['game_list'], 'top_game_order');
        //     $game_unique_id = array_column($data['game_list'], 'game_unique_id');
        //     array_multisort($game_order_top, SORT_DESC, $game_unique_id, SORT_ASC, $data['game_list']);

        //     $top_games = array_filter($data['game_list'], function($game) {
        //         return $game['top_game_order'] > 0;
        //     });


        //     $normal_games = array_filter($data['game_list'], function($game) {
        //         return $game['top_game_order'] == 0;
        //     });


        //     $order = ['slots'];

        //     usort($normal_games, function ($a, $b) use (&$order) {
        //         if(!array_key_exists($a['game_type_code'], $order)) {
        //             $order[] = $a['game_type_code'];
        //         }
        //         if(!array_key_exists($b['game_type_code'], $order)) {
        //             $order[] = $b['game_type_code'];
        //         }
        //         return array_search($a['game_type_code'], $order) - array_search($b['game_type_code'], $order);
        //     });

        //     $data['game_list'] = array_merge($top_games, $normal_games);
        //     unset($order);
        //     unset($top_games);
        //     unset($normal_games);
        // }
        return $data;

        # OUTPUT
        // $this->output->set_header('Access-Control-Allow-Origin: *');
        // $this->output->set_content_type('application/json');
        // $this->output->set_output(json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getAllFrontEndGames($game_platform_id, $game_type_code = null, $game_platform = null, $extra = null){
        $this->CI->load->model(['game_description_model','game_type_model','external_system']);

        $game_apis = $this->CI->external_system->getActivedGameApiList();

        $this->checkGameProviderGamelist($game_platform_id, $game_type_code, $data, $game_platform, $extra);

        if (isset($data['game_list'])) {
            $game_list = [];

            foreach ($data['game_list'] as $key => $game) {
                $game_list[$key] = $this->prepareFrontendGames($game);
            }
            $data['game_list'] = $game_list;
            unset($game_list);
        }

        return $data;
    }

    public function findGameByPlatformAndExtGameId($game_platform_id, $external_game_id) {
        $this->CI->load->model([ 'game_description_model' ]);
        try {
            $game_platforms_active = $this->CI->external_system->getActivedGameApiList();

            if (!in_array($game_platform_id, $game_platforms_active)) {
                throw new Exception('game_platform_id invalid', 1);
            }

            if (in_array($game_platform_id, Game_description_model::GAME_API_WITH_LOBBYS)) {
                throw new Exception('game platform has lobby', 2);
            }

            $extra = [ 'external_game_id' => $external_game_id ];
            $gp_res = $this->getFrontEndGames($game_platform_id, null, 'all', $extra);

            $ret = [
                'code'      => 0 ,
                'mesg'      => null,
                'result'    => $gp_res
            ];
        }
        catch (Exception $ex) {
            $ret = [
                'code'      => $ex->getCode() ,
                'mesg'      => $ex->getMessage() ,
                'result'    => null
            ];
        }
        finally {
            return $ret;
        }
    }

    public function findGameOverPlatforms($search_str) {
        $timing = [];
        $timing_all = microtime(1);
        $this->CI->load->model([ 'game_description_model' ]);
        $game_platforms_active = $this->CI->external_system->getActivedGameApiList();

        $game_platforms_matched = $this->CI->game_description_model->findGamePlatformsByGameName($search_str);

        $game_platforms = array_merge([], array_intersect($game_platforms_matched, $game_platforms_active));

        $res_all = [];
        $count_all = 0;
        foreach ($game_platforms as $game_platform_id) {
            $timing_gp = microtime(1);
            // Skip malformed platforms
            if (in_array($game_platform_id, [2, 9998])) {
                $timing[$game_platform_id] = [ 'R', sprintf('%.3f', microtime(1) - $timing_gp) ];
                continue;
            }

            // Skip platforms with their own lobbies
            if (in_array($game_platform_id, Game_description_model::GAME_API_WITH_LOBBYS)) {
                $timing[$game_platform_id] = [ 'L', sprintf('%.3f', microtime(1) - $timing_gp) ];
                continue;
            }

            // Acquire search result for each game platform
            $extra = [ 'match_name' => $search_str ];
            $gp_res = $this->getFrontEndGames($game_platform_id, null, 'all', $extra);

            // Skip unsupported platforms by the result
            if (isset($gp_res['Error!!!'])) {
                $timing[$game_platform_id] = [ 'U' , sprintf('%.3f', microtime(1) - $timing_gp) ];
                continue;
            }

            // $this->CI->utils->debug_log(__METHOD__, 'gp_res', $gp_res);

            // Skip platforms with 0 result
            if (!isset($gp_res['total_games']) || $gp_res['total_games'] == 0) {
                $timing[$game_platform_id] = [ (isset($gp_res['total_games']) ? 'N' : 0), sprintf('%.3f', microtime(1) - $timing_gp) ];
                continue;
            }

            // Squash game platform results to the all-result
            $count_all += $gp_res['total_games'];
            foreach ($gp_res['game_list'] as $game) {
                // Output only selected fields
                $game = $this->utils->array_select_fields($game, [ 'game_type_code', 'game_name', 'game_name_en', 'in_flash', 'in_html5', 'in_mobile', 'game_launch_url', 'image_path', 'provider_name' ]);
                $game['game_platform_id'] = (int) $game_platform_id;
                $res_all[] = $game;
            }
            $timing[$game_platform_id] = [ (int) $gp_res['total_games'], sprintf('%.3f', microtime(1) - $timing_gp) ];
        }

        $timing['all'] = [ $count_all, sprintf('%.3f', microtime(1) - $timing_all) ];


        $this->CI->utils->debug_log(__METHOD__, [ 'game_platforms_active' => $game_platforms_active, 'game_platforms_matched' => $game_platforms_matched, 'game_platforms' => $game_platforms ]);
        $this->CI->utils->debug_log(__METHOD__, 'timing', $timing);

        $results = [
            'total_games'   => $count_all ,
            'game_list'     => $res_all
        ];

        return $results;

    } // End function findGameOverPlatforms()

    private function checkGameProviderGamelist($game_platform_id, $game_type_code, &$data, $game_platform, $extra = null){
        $this->CI->load->model(['external_system']);

        $order_by_field = null;
        $order_by = null;
        $limit = null;
        $offset = null;
        $sub_game_provider = null;
        $show_new_games = null;
        $show_top_games = null;
        $show_hot_games = null;
        $order_by_direction = null;
        $tagCode = null;
        $game_type_code_query  = [];
        $isUsingTag = false;

        $this->CI->utils->debug_log(__METHOD__, 'extra', $extra);
        if(!empty($extra)){
            $order_by_field = isset($extra['top_game_code']) ? $extra['top_game_code'] : null;
            $available_order_by_field = array("game_code","game_name", 'game_order', 'release_date', 'created_at');

            if(isset($extra['order_by']) && (in_array($extra['order_by'], $available_order_by_field))){//optional
                $order_by = $extra['order_by'];
                if($extra['order_by'] == 'release_date')
                {
                    $order_by_direction = 'desc'; //from latest to oldest
                }
                if($extra['order_by'] == 'created_at')
                {
                    $order_by = 'game_description.created_on';
                }
                if($extra['order_by'] == 'game_order')
                {
                    $order_by = 'game_description.game_order';
                    $order_by_direction = 'asc';
                }
            }

            $order_by_direction = isset($extra['order_by_direction']) ? $extra['order_by_direction'] : 'asc';

            if(isset($extra['order_by_direction']) && !in_array($extra['order_by_direction'], ['asc', 'desc'])) {
                $extra['order_by_direction'] = 'asc';
            }
            else {
                if(isset($extra['order_by_direction'])) {
                    $order_by_direction = $extra['order_by_direction'];
                }
            }

            $limit = isset($extra['limit']) ? $extra['limit'] : null;
            $offset = isset($extra['offset']) ? $extra['offset'] : null;

            $sub_game_provider = isset($extra['sub_game_provider'])? $extra['sub_game_provider'] : null;
            $match_name = isset($extra['match_name']) ? $extra['match_name'] : null;

            $match_external_game_id = isset($extra['external_game_id']) ? $extra['external_game_id'] : null;
            $this->CI->utils->debug_log(__METHOD__, 'match_external_game_id', $match_external_game_id);

            $show_new_games = isset($extra['new_games'])? $extra['new_games'] : null;
            $show_top_games = isset($extra['top_games'])? $extra['top_games'] : null;
            $show_hot_games = isset($extra['hot_games'])? $extra['hot_games'] : null;
            $game_type_code_query = isset($extra['game_type_code'])? $extra['game_type_code'] : null;
            if(isset($extra['game_type_code'])){
                if(is_array($extra['game_type_code'])){
                    $game_type_code_query = $extra['game_type_code'];
                }else{
                    $game_type_code_query = explode(',', $extra['game_type_code']);
                }
            }

            if(isset($extra['tag']) && !empty($extra['tag'])){
                $isUsingTag = true;
            }

            if($isUsingTag&&isset($extra['order_by'])&&$extra['order_by']=='game_order'){
                $order_by = 'IF(game_tag_list.game_order!=0,game_tag_list.game_order,100000)';
            }

        }
        $select = "*, game_description.id as game_description_id";
        $join = [];
        $where = "game_description.flag_show_in_site = 1 and game_description.status = 1 and game_description.external_game_id != 'unknown' and game_description.game_code != 'unknown' and game_description.game_platform_id = " . $game_platform_id;
        if($game_platform_id=='all'){
            $where = "game_description.flag_show_in_site = 1 and game_description.status = 1 and game_description.external_game_id != 'unknown' and game_description.game_code != 'unknown' ";
        }

        if($isUsingTag){
            $tagCode = (string)trim($extra['tag']);
            $where .= " AND game_tags.tag_code='".$tagCode."'";
        }

        if (!in_array($game_platform, [self::GAME_PLATFORM_FLASH,self::GAME_PLATFORM_HTML5,self::GAME_PLATFORM_IOS,self::GAME_PLATFORM_ANDROID,self::GAME_PLATFORM_MOBILE,"all"])) {
            return $data = ["note"=>lang("Game platform not found!"),"avaialable_version"=>[self::GAME_PLATFORM_FLASH,self::GAME_PLATFORM_HTML5,self::GAME_PLATFORM_IOS,self::GAME_PLATFORM_ANDROID,self::GAME_PLATFORM_MOBILE]];
        }

        if($sub_game_provider != null) {
            $where .= " and sub_game_provider = '$sub_game_provider'";
        }

        if($show_new_games != null) {
            $where .= " and flag_new_game = '$show_new_games'";
        }

        if($show_hot_games != null) {
            $where .= " and flag_hot_game = '$show_hot_games'";
        }

        if($show_top_games != null&&$show_top_games!=0) {
            if($isUsingTag){
                $where .= " and game_tag_list.game_order > 0";
            }else{
                $where .= " and game_description.game_order > 0";
            }
        }

        if($show_top_games != null && !$isUsingTag) {
            //$where .= " and game_order > 0";
        }

        if (!empty($match_name)) {
            $where .= " AND (game_name LIKE '%{$match_name}%' OR english_name LIKE '%{$match_name}%') ";
        }

        if (!empty($match_external_game_id)) {
            $where .= " AND external_game_id = '{$match_external_game_id}' ";
        }

        if(!empty($game_type_code_query)) {
            $game_type_code_query_implode = implode("','", $game_type_code_query);
            $where .= " and game_type.game_type_code in ('$game_type_code_query_implode')";
            $join[] = ["table"=>"game_type","condition"=>"game_type.id = game_description.game_type_id"];
        }

        if($isUsingTag){
            $join[] = ["table"=>"game_tag_list","condition"=>"game_tag_list.game_description_id = game_description.id"];
            $join[] = ["table"=>"game_tags","condition"=>"game_tags.id = game_tag_list.tag_id"];
        }

        switch ($game_platform) {
            case self::GAME_PLATFORM_FLASH:
                $where.=" and flash_enabled = " . GAME_DESCRIPTION_MODEL::DB_TRUE;
                break;
            case self::GAME_PLATFORM_HTML5:
                $where.=" and html_five_enabled = " . GAME_DESCRIPTION_MODEL::DB_TRUE;
                break;
            case self::GAME_PLATFORM_IOS:
                $where.=" and enabled_on_ios = " . GAME_DESCRIPTION_MODEL::DB_TRUE;
                break;
            case self::GAME_PLATFORM_ANDROID:
                $where.=" and enabled_on_android = " . GAME_DESCRIPTION_MODEL::DB_TRUE;
                break;
            case self::GAME_PLATFORM_MOBILE:
                $where.=" and mobile_enabled = " . GAME_DESCRIPTION_MODEL::DB_TRUE;
                break;
            default:
                break;
        }

        # if client is ole, kycard will have lobby instead of url for individual games
        $providers_have_lobby = $this->utils->getConfig('allow_lobby_in_provider');
        if ( ! in_array($game_platform_id,GAME_DESCRIPTION_MODEL::GAME_API_WITH_LOBBYS) &&  ! in_array($game_platform_id,$providers_have_lobby)) {

            if (isset($this->getGameProviderDetails()['available_game_providers'][$game_platform_id]) || $game_platform_id=='all') {
                $show_maintenance_status_on_get_frontend_games = $this->utils->getConfig('show_maintenance_status_on_get_frontend_games');
                if($show_maintenance_status_on_get_frontend_games &&  $game_platform_id!='all') {
                    $data['maintenance_mode'] = $this->getGameProviderDetails()['available_game_providers'][$game_platform_id]['maintenance_mode'] == External_system::MAINTENANCE_OR_PAUSE_SYNCING_ON_PROGRESS;
                }

                if ($game_type_code && !in_array($game_type_code, ['null','false'])) {
                    $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,$game_type_code);
                    
                    if (empty($game_type_id)) {

                        #get all game types with active games only
                        $query = "game_type.game_type_code, game_type.game_type_lang, count(game_type_id) as game_type_count";
                        $where = "game_type_code != 'unknown' and game_type.game_platform_id = " . $game_platform_id . " and game_description.status != " . GAME_DESCRIPTION_MODEL::DB_FALSE
                                . " and game_description.flag_show_in_site != " . GAME_DESCRIPTION_MODEL::DB_FALSE
                                . " and game_type.flag_show_in_site != " . GAME_DESCRIPTION_MODEL::DB_FALSE;

                        if($sub_game_provider != null) {
                            $where .= " and sub_game_provider = '${sub_game_provider}'";
                        }

                        $group_by = "game_type_id";
                        $having = "game_type_code > 0";
                        $join = ["table"=>"game_type","condition"=>"game_type.id = game_description.game_type_id"];
                        $game_type_list = $this->CI->game_description_model->getGameByQuery($query,$where,$group_by,$join,null, $order_by_field,$order_by, $limit, $offset, $order_by_direction);
                        #end

                        $data['note'] = lang('Game Type Code not found!');

                        $game_type_list_with_lang = array();

                        foreach($game_type_list as $lang){
                            $game_type_lang = json_decode(str_replace("_json:", "", $lang['game_type_lang']),true);

                            foreach(Language_function::ISO2_LANG as $language_key => $language_code) {
                                $game_type_list_with_lang[$lang['game_type_code']][$language_code] =  isset($game_type_lang[$language_key]) ? $game_type_lang[$language_key] : $game_type_lang[LANGUAGE_FUNCTION::INT_LANG_ENGLISH];
                            }

                        }

                        $data['available_game_type_codes_lang'] = !empty($game_type_list) ? $game_type_list_with_lang :[];
                        $data['available_game_type_codes'] = !empty($game_type_list) ? array_column($game_type_list, 'game_type_code'):[];

                    }else{
                        $where .= " and game_type_id = " . $game_type_id;

                        if(isset($extra['tag']) && !empty($extra['tag'])){
                            $data['total_games'] = $this->CI->game_description_model->getGameByQuery("count(DISTINCT game_description.id) as count",$where,null,$join,null, $order_by_field, $order_by)[0]['count'];
                            $data['game_list'] = $this->CI->game_description_model->getGameByQuery($select,$where,null,$join,null, $order_by_field, $order_by, $limit, $offset, $order_by_direction);
                        }else{
                            $data['total_games'] = $this->CI->game_description_model->getGameByQuery("count(DISTINCT game_description.id) as count",$where,null,$join,null, $order_by_field, $order_by)[0]['count'];
                            $data['game_list'] = $this->CI->game_description_model->getGameByQuery($select,$where,null,$join,null, $order_by_field, $order_by, $limit, $offset, $order_by_direction);
                        }

                    }

                    if (!$this->CI->external_system->isFlagShowInSite($game_platform_id)) {
                        $data['total_games'] = "0";
                        $data['game_list'] = [];
                    }

                    return false;
                }

                if($isUsingTag){
                    $data['order_by'] = $order_by;
                    $data['tag_code'] = $tagCode;
                    $data['order_by_field'] = $order_by_field;
                    $data['order_by_direction'] = $order_by_direction;
                    $data['where'] = $where;
                    $data['join'] = $join;
                }
                
                $data['total_games'] = $this->CI->game_description_model->getGameByQuery("count(DISTINCT game_description.id) as count",$where,null,$join,null, $order_by_field, null)[0]['count'];
                if(!empty($extra)){
                    if(isset($extra['order_by']) && $extra['order_by']=="game_type.order_id"){
                        $order_by = $extra['order_by'];
                        $order_by_direction = $extra['order_by_direction'];
                    }
                    $select = "game_description.*, game_type.*, game_description.id game_description_id";
                    if($isUsingTag){
                        $select .= ",game_tag_list.game_order tag_game_order, game_tags.tag_code as tag_code";
                    }

                    $join = [];
                    $group_by = "game_description.id";
                    $join[] = ["table"=>"game_type","condition"=>"game_type.id = game_description.game_type_id"];
                    if($isUsingTag){
                        $join[] = ["table"=>"game_tag_list","condition"=>"game_tag_list.game_description_id = game_description.id"];
                        $join[] = ["table"=>"game_tags","condition"=>"game_tags.id = game_tag_list.tag_id"];
                    }
                    $data['game_list'] = $this->CI->game_description_model->getGameByQuery($select,$where,null,$join,null, null, $order_by, $limit, $offset, $order_by_direction);
                }else{
                    $data['game_list'] = $this->CI->game_description_model->getGameByQuery($select,$where,null,null,null, $order_by_field, $order_by, $limit, $offset, $order_by_direction);
                }

                if (!$this->CI->external_system->isFlagShowInSite($game_platform_id) && $game_platform_id!=='all') {
                    $data['total_games'] = "0";
                    $data['game_list'] = [];
                }

                // $data['game_list'] = $this->CI->game_description_model->getGameByQuery($select,$where);
            }else{
                $data['Error!!!'] = "Your Game platform id is not in the list. Please select the available game provider below:";
                $data['List'] = $this->getGameProviderDetails();
            }
            return false;
        }

        #Game provider belows have their own game lobby

        $game_api_details = $this->getGameProviderDetails();
        $show_maintenance_status_on_get_frontend_games = $this->utils->getConfig('show_maintenance_status_on_get_frontend_games');
        if($show_maintenance_status_on_get_frontend_games) {
            $data['maintenance_mode'] = $game_api_details['available_game_providers'][$game_platform_id]['maintenance_mode'] == External_system::MAINTENANCE_OR_PAUSE_SYNCING_ON_PROGRESS;
        }
        $data["Note"]= "This Game provider have their own game lobby: " . $game_api_details['available_game_providers'][$game_platform_id]['complete_name'];
        $game_launch_url = $game_api_details['available_game_providers'][$game_platform_id]['game_launch_url'];
        $game = [];

        switch ($game_platform_id) {
            case AGIN_YOPLAY_API:
                $game_types = ['slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['web'=>'YP800', 'mobile'=>'YP800', 'type_lang' => $game_type_lang['slots']],
                ];
                break;
            case AGIN_API:
            case T1AGIN_API:
                $game_types = ['slots', 'fishing_game', 'live_dealer', 'yoplay', 'sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['web'=>8, 'mobile'=>8, 'type_lang' => $game_type_lang['slots'], 'game_type' => 'slots'],
                    'fishing_game' => ['web'=>6, 'mobile'=>6, 'type_lang' => $game_type_lang['fishing_game'], 'game_type' => 'fishing_game'],
                    'live_dealer' => ['web'=>11, 'mobile'=>11, 'type_lang' => $game_type_lang['live_dealer'], 'game_type' => 'live_dealer'],
                    'html_live_games' => ['web'=>18, 'mobile'=>18, 'game_type' => 'html_live_games'],
                    'yoplay' => ['web'=>'YP800', 'mobile'=>'YP800', 'type_lang' => @$game_type_lang['yoplay'], 'game_type' => 'yoplay'],
                    'sports' => ['web'=>'TASSPTA', 'mobile'=>'TASSPTA', 'type_lang' => $game_type_lang['sports'], 'game_type' => 'sports'],
                    'main_lobby' => ['web'=>'0', 'mobile'=>'0', 'game_type' => 'main_lobby'],
                ];
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
            case PRETTY_GAMING_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;

            case AGBBIN_API:
            case GSBBIN_API:
                $game_types = ['slots', 'fishing_game', 'live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['web'=>8, 'mobile'=>8, 'type_lang' => $game_type_lang['slots']],
                    'fishing_game' => ['web'=>6, 'mobile'=>6, 'type_lang' => $game_type_lang['fishing_game']],
                    'live_dealer' => ['web'=>6, 'mobile'=>18, 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case BBIN_API:
                $game_types = ['sports', 'lottery', 'live_dealer', 'slots', 'fishing_game'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $gamekind_sports = 'sports'; // previously its 1
                $gamekind_live = 'live_dealer'; // previously its 3
                $gamekind_slots = 'slots'; // previously its 5
                $gamekind_lottery = 'lottery'; // previously its 12
                $gamekind_fishing = 'fishing_game'; // previously its 30
                $game = [
                    'sports' => ['web'=>$gamekind_sports, 'mobile'=>$gamekind_sports, 'type_lang' => $game_type_lang['sports']],
                    'lottery' => ['web'=>$gamekind_lottery, 'mobile'=>$gamekind_lottery, 'type_lang' => $game_type_lang['lottery']],
                    'live_dealer' => ['web'=>$gamekind_live, 'mobile'=>$gamekind_live, 'type_lang' => $game_type_lang['live_dealer']],
                    'slots' => ['web'=>$gamekind_slots, 'mobile'=>$gamekind_slots, 'type_lang' => $game_type_lang['slots']],
                    'fishing_game' => ['web'=>$gamekind_fishing, 'mobile'=>$gamekind_fishing, 'type_lang' => $game_type_lang['fishing_game']]
                ];
                break;
            case T1BBIN_API:
                $game_types = ['sports', 'lottery', 'live_dealer', 'slots', 'fishing_game'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['web'=>1, 'mobile'=>1, 'type_lang' => $game_type_lang['sports']],
                    'lottery' => ['web'=>34, 'mobile'=>34, 'type_lang' => $game_type_lang['lottery']],
                    'live_dealer' => ['web'=>36, 'mobile'=>36, 'type_lang' => $game_type_lang['live_dealer']],
                    'slots' => ['web'=>37, 'mobile'=>37, 'type_lang' => $game_type_lang['slots']],
                    'fishing_game' => ['web'=>'fish', 'mobile'=>'fish', 'type_lang' => $game_type_lang['fishing_game']]
                ];
                break;
            case EZUGI_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['type_lang' => $game_type_lang['live_dealer']],
                ];
                break;
            case KPLAY_EVO_SEAMLESS_GAME_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['type_lang' => $game_type_lang['live_dealer']],
                ];
                break;
            case T1_EBET_SEAMLESS_GAME_API:
            case EBET_SEAMLESS_GAME_API:
            case ENTWINE_API:
            case OG_API:
            case T1OG_API:
            case LD_CASINO_API:
            case EBET_AG_API:
            case EBET_OPUS_API:
            // case EBET_API:
                $game_types = ['live_dealer','slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['type_lang' => $game_type_lang['live_dealer']],
                    'slots' => ['type_lang' => $game_type_lang['slots']]
                ];
                break;
            case T1AB_V2_API:
            case AB_V2_GAME_API:
            case AB_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['type_lang' => $game_type_lang['live_dealer']],
                ];
                break;
            case T1VR_API:
            case VR_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['lottery' => ["1", 'type_lang' => $game_type_lang['lottery']],];
                break;
            case IDN_API:
            case BAISON_GAME_API:
                $game_types = ['poker', 'fishing_game'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                /* $game = [
                    'poker' => ["1", 'type_lang' => $game_type_lang['poker']],
                    'fishing_game' => ["1", 'type_lang' => $game_type_lang['fishing_game']]
                ]; */

                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ["1", "type_lang" => $game_type_lang[$game_type]];
                    }
                }
                break;
            case KYCARD_API:
            case T1KYCARD_API:
                $game_types = ['table_and_cards', 'poker'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ["1","type_lang" => $game_type_lang[$game_type]];
                    }

                }
                break;
            case LE_GAMING_API:
                $game_types = ['table_and_cards', 'poker'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ["1","type_lang" => $game_type_lang[$game_type]];
                    }
                }
                break;
            case T1LOTTERY_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['lottery' => ["","type_lang" => $game_type_lang['lottery']],];
                break;
            case ONEWORKS_API:
            case T1ONEWORKS_API:
                $game_types = ['sports', 'e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['lobby_code'=>"1", 'game_type'=>'sports', 'type_lang' => $game_type_lang['sports']],
                    'e_sports' =>  ['lobby_code'=>"esports", 'game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case SPORTSBOOK_API:
            case IBC_API:
            case SBTECH_API:
            case RWB_API:
            case WICKETS9_API:
            case BETF_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['sports' => ["1", 'type_lang' => $game_type_lang['sports']],];
                break;
            case HG_API:
                $game_types = ['live', 'table', 'slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live' => ['game_type'=>'live', 'type_lang' => $game_type_lang['live']],
                    'table' => ['game_type'=>'table', 'type_lang' => $game_type_lang['table']],
                    'slots' => ['game_type'=>'slots', 'type_lang' => $game_type_lang['slots']]
                ];
                break;
            case EXTREME_LIVE_GAMING_API:
                $game_types = ['baccarat', 'roulette', 'blackjack'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'baccarat' => ['game_type'=>'baccarat', 'type_lang' => $game_type_lang['baccarat']],
                    'roulette' => ['game_type'=>'roulette', 'type_lang' => $game_type_lang['roulette']],
                    'blackjack' => ['game_type'=>'blackjack', 'type_lang' => $game_type_lang['blackjack']],
                ];
                break;
            case OM_LOTTO_GAME_API:
            case TCG_API:
            case LD_LOTTERY_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['lottery' => ["", 'type_lang' => $game_type_lang['lottery']]];
                break;
            case TGP_AG_API:
                $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,"live_dealer");

                if ($game_type_id) {
                    $where .= " and game_type_id != " . $game_type_id;
                }

                $games = $this->CI->game_description_model->getGameByQuery($select,$where);
                $game_types = ['live_dealer', 'slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ["sub_game_provider" => "lobby","game_code" => "real", 'type_lang' => $game_type_lang['live_dealer'],
                    'games_dont_have_lobby' => $games],
                ];
                break;
            case PRAGMATICPLAY_API:
                //OGP-28594
                $games = $this->CI->game_description_model->getGameByQuery($select,$where,null,$join,null, null, $order_by, $limit, $offset, $order_by_direction);
                break;
            case T1SUNCITY_API:
            case SUNCITY_API:

                // $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,"live_dealer");

                // if ($game_type_id) {
                //     $where .= " and game_type_id != " . $game_type_id;
                // }

                // $games = $this->CI->game_description_model->getGameByQuery($select,$where);
                $game_types = ['live_dealer', 'slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ["sub_game_provider" => "SB","game_code" => "Sunbet_Lobby", 'type_lang' => $game_type_lang['live_dealer'],
                    //'games_dont_have_lobby' => $games
                    ],
                    'slots' => ["sub_game_provider" => "SBG","game_code" => "Sunbet_Lobby", 'type_lang' => $game_type_lang['slots'],
                    //'games_dont_have_lobby' => $games
                    ]
                ];
                break;
            case MWG_API:

                $games = $this->CI->game_description_model->getGameByQuery($select,$where);
                $game = [
                    'main_lobby' => ["main_lobby" => "lobby",],
                    'games_can_launch_without_lobby' => $games
                ];
                break;
            // case MG_API:

            //     $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,"live_dealer");
            //     $where .= $game_platform_id . " and game_type_id != " . $game_type_id;
            //     $games = $this->CI->game_description_model->getGameByQuery($select,$where);
            //     $game = [
            //         'live_dealer' => ['game_code' => '1'],
            //         'games_dont_have_lobby' => $games
            //     ];
            //     break;
            case SBOBET_API:
            case SBOBET_SEAMLESS_GAME_API:
            case SBOBETV2_GAME_API:
                $game_types = ['sports', 'live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' =>  ['game_type'=>'sports', 'type_lang' => $game_type_lang['sports']],
                    'live_dealer' =>  ['game_type'=>'live_dealer', 'type_lang' => $game_type_lang['live_dealer']],
                ];
                break;
            case T1_SBOBET_SEAMLESS_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' =>  ['game_type'=>'sports', 'type_lang' => $game_type_lang['sports']],
                ];
                break;
            case GAMEPLAY_API:
                $game_types = ['live_dealer','lottery_keno', 'lottery_pk10', 'lottery_ladder', 'slots', 'lottery_sode',];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,"slots");

                if ($game_type_id) {
                    $where .= " and game_type_id = " . $game_type_id;
                }

                $games = $this->CI->game_description_model->getGameByQuery($select,$where);

                $game = [
                    'live_dealer' => ["game_code" => "table", 'type_lang' => $game_type_lang['live_dealer']],
                    'lottery_keno' => ["game_code" => "keno", 'type_lang' => isset($game_type_lang['lottery_keno']) ? $game_type_lang['lottery_keno'] : null],
                    'lottery_pk10' => ["game_code" => "pk10", 'type_lang' => isset($game_type_lang['lottery_pk10']) ? $game_type_lang['lottery_pk10'] : null],
                    'lottery_pk10' => ["game_code" => "pk10", 'type_lang' => isset($game_type_lang['lottery_pk10']) ? $game_type_lang['lottery_pk10'] : null],
                    'lottery_thailottery' => ["game_code" => "thailottery", 'type_lang' => isset($game_type_lang['lottery_thailottery']) ? $game_type_lang['lottery_ladder'] : null],
                    'lottery_sode' => ["game_code" => "sode", 'type_lang' => isset($game_type_lang['lottery_sode']) ? $game_type_lang['lottery_sode'] : null],
                    'slots' => ['game_code' => 'slots', 'type_lang' => $game_type_lang['slots']],
                    // 'games_dont_have_lobby' => $games
                ];
                break;
            case SOLID_GAMING_THB_API:
                $game_types = ['slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game_type_id = $this->CI->game_type_model->getGameTypeIdGametypeCode($game_platform_id,"slots");

                $game = [
                    'slots' => ["game_code" => "slots", 'type_lang' => $game_type_lang['slots']],
                ];
                break;
            case CQ9_API:
            case T1_CQ9_SEAMLESS_API:
                $game = [
                    'slots' =>  ['game_type'=>'slots']
                ];
                break;
            case T1_PNG_SEAMLESS_API:
                $game = [
                    'slots' =>  ['game_type'=>'slots']
                ];
                break;
            case YUXING_CQ9_GAME_API:
                $game = [
                    'live_dealer' =>  ['game_type'=>'live_dealer']
                ];
                break;
            case OG_V2_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case HOGAMING_SEAMLESS_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case AVIA_ESPORT_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' =>  ['game_type'=>'sports', 'type_lang' => $game_type_lang['sports']]
                ];
                break;
            case PINNACLE_SEAMLESS_GAME_API:
            case PINNACLE_API:
            case T1_PINNACLE_SEAMLESS_GAME_API:
            case AP_GAME_API:
                $game_types = ['sports', 'e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['type_lang' => $game_type_lang['sports']],
                    'e_sports' =>  ['game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case GOLDEN_RACE_GAMING_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['type_lang' => $game_type_lang['sports']]
                ];
                break;
            case IPM_V2_ESPORTS_API:
            case IMESB_API:
                $game_types = ['e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'e_sports' =>  ['game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case IPM_V2_SPORTS_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' =>  ['game_type'=>'sports', 'type_lang' => $game_type_lang['sports']]
                ];
                break;
            case RG_API:
                $game_types = ['e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'e_sports' =>  ['game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case NTTECH_API:
            case NTTECH_IDR_B1_API:
            case NTTECH_CNY_B1_API:
            case NTTECH_THB_B1_API:
            case NTTECH_USD_B1_API:
            case NTTECH_VND_B1_API:
            case NTTECH_MYR_B1_API:
            case T1NTTECH_V2_CNY_B1_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case LUCKY_GAME_CHESS_POKER_API:
                $game_types = ['poker'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'poker' => ['game_type' => 'poker', 'type_lang' => $game_type_lang['poker']]
                ];
                break;
            case NTTECH_V2_API:
            case T1NTTECH_V2_API:
            case NTTECH_V3_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;

            case NTTECH_V2_IDR_B1_API:
            case NTTECH_V2_CNY_B1_API:
            case NTTECH_V2_THB_B1_API:
            case NTTECH_V2_USD_B1_API:
            case NTTECH_V2_VND_B1_API:
            case NTTECH_V2_MYR_B1_API:
            case NTTECH_V2_INR_B1_API:

            /* gamecode
                MX-LIVE-001 Baccarat Classic
                MX-LIVE-002 Baccarat
                MX-LIVE-003 Baccarat Insurance
                MX-LIVE-006 DragonTiger
                MX-LIVE-007 SicBo
                MX-LIVE-009 Roulette
                MX-LIVE-010 Red Blue Duel
                MX-LIVE-014 Thai Hi Lo
                MX-LIVE-015 Thai Fish Prawn Crab

             */
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'BaccaratClassic' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-001'],
                    'Baccarat' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-002'],
                    'BaccaratInsurance' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-003'],
                    'DragonTiger' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-006'],
                    'SicBo' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-007'],
                    'Roulette' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-009'],
                    'RedBlueDuel' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-010', 'mobile_only' => true],
                    'ThaiHiLo' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-014', 'mobile_only' => true],
                    'ThaiFish Prawn Crab' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer'], 'game_code' => 'MX-LIVE-015', 'mobile_only' => true],
                ];
                break;
            case YEEBET_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case WON_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case DG_API:
            case DG_SEAMLESS_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case AFB88_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['game_type' => 'sports', 'type_lang' => $game_type_lang['sports']]
                ];
                break;
            case SA_GAMING_SEAMLESS_THB1_API:
            case SA_GAMING_SEAMLESS_API:
            case SA_GAMING_API:
            case T1SA_GAMING_API:
            case T1_SA_GAMING_SEAMLESS_GAME_API:
                $game_types = ["slots","mini_games","lottery","live_dealer","fishing_game"];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['type_lang' => $game_type_lang['slots']],
                    'mini_games' => ['type_lang' => $game_type_lang['mini_games']],
                    'lottery' => ['type_lang' => $game_type_lang['lottery']],
                    "live_dealer" => ['type_lang' => $game_type_lang['live_dealer']],
                    "fishing_game" => ['type_lang' => $game_type_lang['fishing_game']]
                ];
                break;
            case ASIASTAR_API:
                $game_types = ['table_and_cards'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['table_and_cards' => ["1","type_lang" => $game_type_lang['table_and_cards']],];

                break;
            case OGPLUS_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case S128_GAME_API:
                $game_types = ['cock_fight'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'cock_fight' => ['game_type' => 'cock_fight', 'type_lang' => $game_type_lang['cock_fight']]
                ];
                break;
            case TIANHONG_MINI_GAMES_API:
                $game_types = ['mini_games'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'mini_games' => ['game_type' => 'mini_games', 'type_lang' => $game_type_lang['mini_games']]
                ];
                break;
            case RGS_API:
                $game_types = ['sports', 'horse_racing'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['game_type' => 'sports', 'type_lang' => $game_type_lang['sports']],
                    'horse_racing' => ['game_type' => 'horse_racing', 'type_lang' => $game_type_lang['horse_racing']]
                ];
                break;
            case T1_WM2_SEAMLESS_GAME_API:
            case T1_WM_SEAMLESS_GAME_API:
            case WM2_SEAMLESS_GAME_API:
            case WM_SEAMLESS_GAME_API:
            case WM_API:
            case T1WM_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case T1SBTECH_BTI_API:
            case SBTECH_BTI_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['game_type' => 'sports', 'type_lang' => $game_type_lang['sports']]
                ];
                break;
            case AG_SEAMLESS_THB1_API:
                $game_types = ['slots','live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['game_type' => 'slots', 'type_lang' => $game_type_lang['slots']],
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case LUCKY_STREAK_SEAMLESS_GAME_API:
            case LUCKY_STREAK_SEAMLESS_THB1_API:
                    $game_types = ['live_dealer'];
                    $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                    $game = [
                        'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                    ];
                    break;

            case BG_SEAMLESS_GAME_IDR1_API:
                case BG_SEAMLESS_GAME_CNY1_API:
                case BG_SEAMLESS_GAME_THB1_API:
                case BG_SEAMLESS_GAME_MYR1_API:
                case BG_SEAMLESS_GAME_VND1_API:
                case BG_SEAMLESS_GAME_USD1_API:
                case BG_SEAMLESS_GAME_API;
                        $game_types = ['live_dealer','fishing_game'];
                        $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                        $game = [
                            'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']],
                            'fishing_game' => ['game_type' => 'fishing_game', 'type_lang' => $game_type_lang['fishing_game']]
                        ];
                        break;
            case EA_GAME_API:
            case EA_GAME_API_THB1_API:
                    $game_types = ['live_dealer'];
                    $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                    $game = [
                        'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                    ];
                    break;
            case SPORTSBOOK_FLASH_TECH_GAME_API:
            case SPORTSBOOK_FLASH_TECH_GAME_IDR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_CNY1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_THB1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_MYR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_VND1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_USD1_API:
            case T1SPORTSBOOK_FLASH_TECH_GAME_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'sports' => ['game_type' => 'sports', 'type_lang' => $game_type_lang['sports']]
                ];
                break;
            case VIVOGAMING_SEAMLESS_API:
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
            case VIVOGAMING_API:
            case T1_VIVOGAMING_SEAMLESS_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' => ['game_type' => 'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case SEXY_BACCARAT_SEAMLESS_API:
            case SEXY_BACCARAT_SEAMLESS_IDR1_API:
            case SEXY_BACCARAT_SEAMLESS_CNY1_API:
            case SEXY_BACCARAT_SEAMLESS_THB1_API:
            case SEXY_BACCARAT_SEAMLESS_USD1_API:
            case SEXY_BACCARAT_SEAMLESS_VND1_API:
            case SEXY_BACCARAT_SEAMLESS_MYR1_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' =>  ['game_type'=>'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case BETGAMES_SEAMLESS_IDR1_GAME_API:
            case BETGAMES_SEAMLESS_CNY1_GAME_API:
            case BETGAMES_SEAMLESS_THB1_GAME_API:
            case BETGAMES_SEAMLESS_MYR1_GAME_API:
            case BETGAMES_SEAMLESS_VND1_GAME_API:
            case BETGAMES_SEAMLESS_USD1_GAME_API:
            case BETGAMES_SEAMLESS_GAME_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' =>  ['game_type'=>'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case GMT_GAME_API:
                $game_types = ['slots', 'fishing_game'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' => ['web'=>8, 'mobile'=>8, 'type_lang' => $game_type_lang['slots']],
                    'fishing_game' => ['web'=>6, 'mobile'=>6, 'type_lang' => $game_type_lang['fishing_game']],
                ];
                break;
            case HA_GAME_API:
                $game_types = ['slots', 'fishing_game', 'card_games'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'slots' =>  ['game_type'=>'slots', 'type_lang' => $game_type_lang['slots']],
                    'fishing_game' =>  ['game_type'=>'fishing_game', 'type_lang' => $game_type_lang['fishing_game']],
                    'card_games' =>  ['game_type'=>'card_games', 'type_lang' => $game_type_lang['card_games']],
                ];
                break;
            case YABO_GAME_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'live_dealer' =>  ['game_type'=>'live_dealer', 'type_lang' => $game_type_lang['live_dealer']]
                ];
                break;
            case DONGSEN_ESPORTS_API:
                $game_types = ['e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'e_sports' =>  ['game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case DONGSEN_LOTTERY_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'lottery' =>  ['game_type'=>'lottery', 'type_lang' => $game_type_lang['lottery']]
                ];
                break;
            case IBC_ONEBOOK_API:
            case IBC_ONEBOOK_SEAMLESS_API:
            case T1_IBC_ONEBOOK_SEAMLESS_API:
                $game_types = ['sports', 'e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ['game_type' => $game_type, 'type_lang' => $game_type_lang[$game_type]];
                    }
                }
                break;
            case HKB_GAME_API:
                $game_types = ['card_games', 'lottery', 'dingdong'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $gameId_card_games = '101';
                $gameId_lottery = '201';
                $gameId_dingdong = '303';
                $game = [
                    'card_games' =>  ['web' => $gameId_card_games, 'mobile' => $gameId_card_games, 'game_type'=>'card_games', 'type_lang' => $game_type_lang['card_games']],
                    'lottery' =>  ['web' => $gameId_lottery, 'mobile' => $gameId_lottery, 'game_type'=>'lottery', 'type_lang' => $game_type_lang['lottery']],
                    'dingdong' =>  ['web' => $gameId_dingdong, 'mobile' => $gameId_dingdong, 'game_type'=>'dingdong', 'type_lang' => $game_type_lang['dingdong']],
                ];
                break;
            case IPM_V2_IMSB_ESPORTSBULL_API:
                $game_types = ['sports', 'e_sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $game_id_sports = 'IMSB';
                $game_id_e_sports = 'ESPORTSBULL';
                $game = [
                    'sports' =>  ['web' => $game_id_sports, 'mobile' => $game_id_sports, 'game_type'=>'sports', 'type_lang' => $game_type_lang['sports']],
                    'e_sports' =>  ['web' => $game_id_e_sports, 'mobile' => $game_id_e_sports, 'game_type'=>'e_sports', 'type_lang' => $game_type_lang['e_sports']]
                ];
                break;
            case YL_NTTECH_GAME_API:
                $game_types = ['fishing_game'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'fishing_game' => ['game_type' => 'fishing_game', 'type_lang' => $game_type_lang['fishing_game']]
                ];
                break;
            case SGWIN_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [
                    'lottery' => ['game_type' => 'lottery', 'type_lang' => $game_type_lang['lottery']]
                ];
                break;
            case LOTO_SEAMLESS_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['lottery' => ["1", 'type_lang' => $game_type_lang['lottery']],];
                break;
            case BISTRO_SEAMLESS_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = ['lottery' => ["1", 'type_lang' => $game_type_lang['lottery']],];
                break;
            case IDNLIVE_SEAMLESS_GAME_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $live_dealer_lobby_game_code = 'bolagila';

                $game = [
                    'live_dealer' =>  [
                        'game_code' => $live_dealer_lobby_game_code,
                        'game_type'=>'live_dealer',
                        'type_lang' => $game_type_lang['live_dealer'],
                    ],
                ];
                break;
            case AG_SEAMLESS_GAME_API:
                $game_types = ['live_dealer', 'slots'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $live_dealer_lobby_game_code = 0;
                $slots_lobby_game_code = 8;

                $game = [
                    'live_dealer' =>  [
                        'game_code' => $live_dealer_lobby_game_code,
                        'game_type'=>'live_dealer',
                        'type_lang' => $game_type_lang['live_dealer'],
                    ],
                    'slots' =>  [
                        'game_code' => $slots_lobby_game_code,
                        'game_type'=>'slots',
                        'type_lang' => $game_type_lang['slots'],
                    ],
                ];
                break;
            /* case WE_SEAMLESS_GAME_API:
                $game = [
                    'Lobby' =>  ['web' => 'lobby', 'mobile' => 'lobby', 'type_lang' => 'lobby'],
                    'Lottery' =>  ['web' => 'lo', 'mobile' => 'lo', 'type_lang' => 'Lottery'],
                    'Roulette' =>  ['web' => 'rol', 'mobile' => 'rol', 'type_lang' => 'Roulette'],
                    'Traditional Baccarat' =>  ['web' => 'baa', 'mobile' => 'baa', 'type_lang' => 'Traditional Baccarat'],
                    'Lucky Wheel' =>  ['web' => 'lw', 'mobile' => 'lw', 'type_lang' => 'Lucky Wheel'],
                    'Dragon Tiger' =>  ['web' => 'dt', 'mobile' => 'dt', 'type_lang' => 'Dragon Tiger'],
                ];
                break; */
            case CMD_SEAMLESS_GAME_API:
            case CMD2_SEAMLESS_GAME_API:
            case T1_CMD_SEAMLESS_GAME_API:
            case T1_CMD2_SEAMLESS_GAME_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);

                $game = [
                    'sports' => [
                        'type_lang' => isset($game_type_lang['sports']) ? $game_type_lang['sports'] : null,
                        'game_code' => 'cmd_sports',
                    ],
                ];

                break;
            case SV388_AWC_SEAMLESS_GAME_API:
            case T1_SV388_AWC_SEAMLESS_GAME_API:
                $game_types = ['cock_fight'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);

                $game = [
                    'cock_fight' => [
                        'type_lang' => $game_type_lang['cock_fight'],
                        'game_code' => 'lobby',
                    ],
                ];

                break;
            case ULTRAPLAY_SEAMLESS_GAME_API:
            case T1_ULTRAPLAY_SEAMLESS_GAME_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ['game_type' => $game_type, 'type_lang' => $game_type_lang[$game_type]];
                    }
                }
                break;
            case ASTAR_SEAMLESS_GAME_API:
            case T1_ASTAR_SEAMLESS_GAME_API:
                $game_types = ['live_dealer'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ['game_type' => $game_type ,"type_lang" => $game_type_lang[$game_type]];
                    }
                }
                break;
            case TWAIN_SEAMLESS_GAME_API:
            case T1_TWAIN_SEAMLESS_GAME_API:
                $game_types = ['sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);

                $game = [
                    'sports' =>  [
                        'game_type' => 'sports',
                        'type_lang' => $game_type_lang['sports'],
                    ]
                ];
                break;
            case HP_2D3D_GAME_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ['game_type' => $game_type ,"type_lang" => $game_type_lang[$game_type]];
                    }
                }
                break;
            case HP_LOTTERY_GAME_API:
                $game_types = ['lottery'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $game = [];

                foreach ($game_types as $game_type) {
                    if (array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = [
                            'game_type' => $game_type ,
                            'type_lang' => $game_type_lang[$game_type],
                        ];
                    }
                }
                break;
            case EBET_API:
            case WE_SEAMLESS_GAME_API:
                $game_types = ['live_dealer', 'slots', 'fishing_game', 'sports'];
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id, $game_types);
                $game = [];

                foreach ($game_types as $game_type) {
                    if (array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = [
                            'game_type' => $game_type ,
                            'type_lang' => $game_type_lang[$game_type],
                        ];
                    }
                }
                break;
            default:
                $game_types = array_column($this->CI->game_type_model->getGameTypeListByGamePlatformId($game_platform_id, true), 'game_type_code');
                $game_type_lang = $this->prepareGameTypeTranslation($game_platform_id,$game_types);
                $game = [];
                foreach($game_types as $game_type) {
                    if(array_key_exists($game_type, $game_type_lang)) {
                        $game[$game_type] = ['game_type' => $game_type ,"type_lang" => $game_type_lang[$game_type]];
                    }
                }
                break;
        }

        if ( ! empty($game_type_code )) {
            if (isset($game[$game_type_code])) {
                $game[$game_type_code]['game_platform_id'] = $game_platform_id;
                $data["game_launch_url"] = $this->processGameUrls($game_launch_url,$game[$game_type_code]);
            }else{
                $data = $game;
            }
        }else{
            foreach ($game as $key => $game_launch_code) {

                if (in_array($key, ['stand_alone_game_links','games_dont_have_lobby','games_can_launch_without_lobby'])) continue;
                $game_launch_code['game_platform_id'] = $game_platform_id;
                $data["game_launch_url"][$key] = $this->processGameUrls($game_launch_url,$game_launch_code,$key, null, $extra);
            }

            $games = null;
            if (isset($game['games_dont_have_lobby']) ) {
                $games = $game['games_dont_have_lobby'];
                $games_dont_have_lobby = true;
            }elseif (isset($game['games_can_launch_without_lobby'])) {
                $games = $game['games_can_launch_without_lobby'];
                $games_can_launch_without_lobby = true;
            }

            if ( ! empty($games)) {
                foreach ($games as $key => $game) {
                    $game['game_platform_id'] = $game_platform_id;
                    $game_list[$key] = $this->prepareFrontendGames($game);
                }
                if (isset($games_can_launch_without_lobby)) {
                    $data['games_can_launch_without_lobby'] = $game_list;
                }elseif($games_dont_have_lobby){
                    $data['games_dont_have_lobby'] = $game_list;
                }
            }
        }
    }

    // Get the game_type_lang depends on the game_type of lobbies to be shown
    private function prepareGameTypeTranslation($game_platform_id, $game_type_codes){
        $iCountedGameCodes = count($game_type_codes);
        $game_type_lang = [];
        $lang = $this->utils->getPlayerCenterLanguage();
        $game_type_data = $this->CI->game_type_model->getGameTypeListByGamePlatformId($game_platform_id);
        foreach ($game_type_data as $game_type) {
            for ($iCounter = 0; $iCounter < $iCountedGameCodes ; $iCounter++) {
                switch ($game_type['game_type_code']) {
                    case $game_type_codes[$iCounter]:
                        $aGameTypeLang = [$game_type_codes[$iCounter] => $game_type['game_type_lang']];
                        $game_type_lang = array_merge($game_type_lang, $aGameTypeLang);
                        break;
                    default:
                        break;
                }
            }
        }
        return $game_type_lang;
    }

    private function trimString($string){
        return trim(preg_replace("/\([^)]+\)/", "", $string));
    }

     private function trimFlashOnly($string){
        return rtrim(str_replace(" (Flash)","",$string));;
    }

    private function processGameName(&$game_name){
        if(!empty($game_name)){
            foreach ($game_name as $key => $name) {
                $game_name[$key] = $this->trimString($name);
            }
        }
    }

    private function prepareFrontendGames($game){
        $game_api_details = $this->getGameProviderDetails();
        $game_name = json_decode(str_replace("_json:", "", $game['game_name']),true);
        $game_name_json = $game['game_name'];


        $game_type_code = json_decode(json_encode($this->CI->game_type_model->getGameTypeById($game['game_type_id'])),true)['game_type_code'];
        $game_launch_url = $game_api_details['available_game_providers'][$game['game_platform_id']]['game_launch_url'];

        if ($this->utils->getConfig('trim_game_name_on_gamelist_api')) {
            $this->processGameName($game_name);
        }
        if ($this->utils->getConfig('trim_game_name_flash_only')) {
            $game_name_json = $this->trimFlashOnly($game['game_name']);
        }
        $attributes = null;
        if (isset($game['attributes']))
            $attributes = $game['game_launch_code_other_settings'] = $game['attributes'];

        $sub_category = null;
        if($attributes && is_string($attributes)){
            $temp_attributes = json_decode($attributes, true);
            if (isset($temp_attributes['sub_category']))
            $sub_category = $temp_attributes['sub_category'];
        }

        $game_list = [
            'game_type_code'    => $game_type_code,
            'game_name'         => $game_name_json,
            //'game_code'         => (isset($game['game_code'])?$game['game_code']:null),
            'game_name_en'      => $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_cn'      => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_CHINESE]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_CHINESE] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_indo'    => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_INDONESIAN]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_INDONESIAN] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_vn'      => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_kr'      => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_KOREAN]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_KOREAN] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_th'      => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_THAI]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_THAI] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_name_india'   => isset($game_name[LANGUAGE_FUNCTION::INT_LANG_INDIA]) ? $game_name[LANGUAGE_FUNCTION::INT_LANG_INDIA] : $game_name[LANGUAGE_FUNCTION::INT_LANG_ENGLISH],
            'game_platform_id'    => (isset($game['game_platform_id'])?$game['game_platform_id']:null),
            'game_description_id' => (isset($game['game_description_id'])?$game['game_description_id']:null),
            'provider_name'     => $game_api_details['available_game_providers'][$game['game_platform_id']]['complete_name'],
            'provider_code'     => $game_api_details['available_game_providers'][$game['game_platform_id']]['game_provider_code'],
            // 'game_id_desktop'   => $game['game_code'],
            // 'game_id_mobile'    => $game['game_code'],
            'in_flash'          => $game['flash_enabled'],
            'in_html5'          => $game['html_five_enabled'],
            'in_mobile'         => $game['mobile_enabled'],
            'in_desktop'           => (!is_null($game['desktop_enabled'])?$game['desktop_enabled']:1),
            // 'downloadable'      => $game['dlc_enabled'],
            'available_on_android'       => $game['enabled_on_android'],
            'available_on_ios'           => $game['enabled_on_ios'],
            'note'              => $game['note'],
            'status'            => $game['status'],
            'top_game_order'    => $game['game_order'],
            'tag_game_order'    => (isset($game['tag_game_order'])?$game['tag_game_order']:null),
            //'tag_codes'         => (isset($game['tag_codes'])?$game['tag_codes']:null),
            'enabled_freespin'  => $game['enabled_freespin'],
            'sub_game_provider' => $game['sub_game_provider'],
            'flag_new_game'     => $game['flag_new_game'],
            'flag_hot_game'     => $game['flag_hot_game'],
            // 'flag_show_in_site' => $game['flag_show_in_site'],
            'progressive'       => $game['progressive'],
            'demo_enable'       => !empty($game['demo_link']) ? true : false,
            'game_launch_url'   => $this->processGameUrls($game_launch_url,$game,$game_type_code,$attributes),
            'game_launch_code_other_settings'   => $attributes,
            'image_path' => $this->processGameImagePath($game),
            'sub_category' => $sub_category,
            'rtp'     => $game['rtp']
        ];

        $game_code = $game['game_code'];
        $attributes = json_decode($attributes,true);
        if (isset($attributes['game_launch_code']))
            $game_code = $attributes['game_launch_code'];

        if ($game['mobile_enabled'])
            $game_list['game_id_mobile'] = $game_code;

        if ($game['flash_enabled'] || !$game['mobile_enabled'])
            $game_list['game_id_desktop'] = $game_code;

        if ($game['html_five_enabled'] || !$game['mobile_enabled']) {
            // $game_list['game_id_mobile'] = $game_code;
            $game_list['game_id_desktop'] = $game_code;
        }

        $game_list['game_unique_id'] = $game['external_game_id'];
        $game_list['rtp'] = $game['rtp'];
        $game_list['release_date'] = isset($game['release_date']) ? date("Y-m-d", strtotime($game['release_date'])) : null;

        return $game_list;
    }

    public function processPlatformImagePath($game_platform_id){
        $url =  $this->utils->getSystemUrl('www') . $this->utils->getConfig('game_list_image_path_url');
        $extension = ".png";
        $path = "{$url}/images/platform/{$game_platform_id}{$extension}";
        return $path;
    }

    public function processGameTypeIcon($unique_code){
        $url =  $this->utils->getSystemUrl('www') . $this->utils->getConfig('game_list_image_path_url');
        $extension = ".png";
        switch (strtolower($unique_code)) {
            case 'all':
                $icon = "all";
                break;
            case 'slots':
                $icon = "slots";
                break;
            case 'lottery':
                $icon = "lottery";
                break;
            case 'fishing_game':
                $icon = "fishing";
                break;
            case 'live_dealer':
                $icon = "live_dealer";
                break;
            case 'casino':
                $icon = "casino";
                break;
            case 'gamble':
                $icon = "gamble";
                break;
            case 'table_games':
            case 'table_and_cards':
                $icon = "table_games";
                break;
            case 'card_games':
                $icon = "card_games";
                break;
            case 'e_sports':
                $icon = "e_sports";
                break;
            case 'fixed_odds':
                $icon = "fixed_odd";
                break;
            case 'arcade':
                $icon = "arcade";
                break;
            case 'horce_racing':
                $icon = "horse_racing";
                break;
            case 'progressives':
                $icon = "progressive";
                break;
            case 'sports':
                $icon = "sport";
                break;
            case 'unknown':
                $icon = "unknown";
                break;
            case 'video_poker':
                $icon = "video_poker";
                break;
            case 'poker':
                $icon = "poker";
                break;
            case 'mini_games':
                $icon = "min_games";
                break;
            case 'others':
                $icon = "other";
                break;
            case 'soft_games':
                $icon = "soft_game";
                break;
            case 'scratch_card':
                $icon = "scratch_card";
                break;

            default:
                $icon = "all";
                break;
        }

        $path = "{$url}/images/icons/{$icon}{$extension}";
        return $path;
    }

    public function processGameImagePath($game){
        $dir = $this->game_dir_name($game['game_platform_id']);

        #sample path
        #cn path http://www.gamegateway.t1t.games/includes/images/cn/microgaming/Galacticons.png
        #en path http://www.gamegateway.t1t.games/includes/images/microgaming/Galacticons.png
        $url =  $this->utils->getSystemUrl('www') . $this->utils->getConfig('game_list_image_path_url');
        if($this->utils->getConfig('return_game_image_url_no_domain')){
            $url = $this->utils->getConfig('game_list_image_path_url');
        }

        $extension = ".jpg";

        #GL-5833
        $custom_image_extension_by_provider = $this->utils->getConfig('custom_image_extension_by_provider');
        /*
            $config['custom_image_extension_by_provider'] = [
                <game_platform_id> => ".webp"
            ];
         */
        if (!empty($custom_image_extension_by_provider) 
            && isset($custom_image_extension_by_provider[$game['game_platform_id']])
        ){
                $extension = $custom_image_extension_by_provider[$game['game_platform_id']];
        }


        #OGP-34132 enabling image gif extension for specific games 
        $enable_image_gif_extension_game_list = $this->utils->getConfig('enable_image_gif_extension_game_list');
        if (!empty($enable_image_gif_extension_game_list) 
            && isset($enable_image_gif_extension_game_list[$game['game_platform_id']])
            && in_array($game['game_code'], $enable_image_gif_extension_game_list[$game['game_platform_id']])){
                $extension = ".gif";
        }

        $game_code = $game['game_code'];
        // $file = $game_code.$extension;

        // game image file
        switch ($game['game_platform_id']) {
            case PGSOFT_API:
            case PGSOFT3_API:
            case PGSOFT_SEAMLESS_API:
            case PGSOFT2_SEAMLESS_API:
            case T1_PGSOFT2_SEAMLESS_API:
            case T1_PGSOFT_SEAMLESS_API:
            case PGSOFT3_SEAMLESS_API:
            case T1_PGSOFT3_SEAMLESS_API:
            case IDN_PGSOFT_SEAMLESS_API:
            case T1_IDN_PGSOFT_SEAMLESS_API:
            case LIVE12_PGSOFT_SEAMLESS_API:
                $game_launch_code = $game["game_code"];
                if(!empty($game["attributes"])){
                    $json=json_decode($game["attributes"],true);
                    if(!empty($json) && !empty($json['game_launch_code'])){
                        $game_launch_code = $json['game_launch_code'];
                    }
                }
                // $game_launch_code = json_decode($game["attributes"],true)["game_launch_code"] ?: $game["game_code"];
                $file = $game_launch_code.$extension;
                break;
            case PNG_SEAMLESS_GAME_API:
            case PNG_API:
            case T1PNG_API:
                $game_launch_code = $game["game_code"];
                if(isset($game["attributes"])){
                    $game_launch_code = json_decode($game["attributes"],true)["game_launch_code"] ?: $game["game_code"];
                }
                if(isset($game["game_launch_code_other_settings"])){
                    $game_launch_code = json_decode($game["game_launch_code_other_settings"],true)["game_launch_code"] ?: $game["game_code"];
                }
                $file = $game_launch_code.$extension;
                /*$game_code = json_decode($game['attributes'], TRUE)['game_launch_code'];
                $file = $game_code.$extension;*/
                break;
            case BOOMING_SEAMLESS_API:
            case T1_BOOMING_SEAMLESS_API:
            case BOOMING_SEAMLESS_GAME_API:
            case T1_BOOMING_SEAMLESS_GAME_API:
            case T1_EZUGI_REDTIGER_SEAMLESS_GAME_API:
            case EZUGI_REDTIGER_SEAMLESS_API:
            case T1_EZUGI_NETENT_SEAMLESS_GAME_API:
            case EZUGI_NETENT_SEAMLESS_API:
            case T1_EZUGI_SEAMLESS_GAME_API:
            case EZUGI_SEAMLESS_API:
                $external_game_id = $game["external_game_id"];
                $file = $external_game_id.$extension;
                break;
            case T1_AFB_SBOBET_SEAMLESS_GAME_API:
            case AFB_SBOBET_SEAMLESS_GAME_API:
            case SPRIBE_JUMBO_SEAMLESS_GAME_API:
            case T1_SPRIBE_JUMBO_SEAMLESS_GAME_API:
                $game_code = isset($game['english_name']) ? preg_replace('/\s+/', '', $game['english_name']) : $game_code;
                $file = $game_code.$extension;
                break;
            default:
                $file = $game_code . $extension;
                break;
        }

        $custom_path = $this->utils->getConfig('custom_game_list_image_path_url');
        $game_platform_id = $game['game_platform_id'];
        if( !empty($custom_path) && isset($custom_path[$game_platform_id]) && !empty($custom_path[$game_platform_id]) ){
            $dir  = $custom_path[$game_platform_id];
        }

        $game_list_language =  $this->utils->getConfig('game_list_language');
        $default_language=  array(
            "en" => "{$url}/images/{$dir}/{$file}",
            "cn" => "{$url}/images/cn/{$dir}/{$file}"
        );

        if(!empty($game_list_language)){
            foreach ($game_list_language as $key => $language) {
                if(!isset($default_language[$language])){
                    $default_language[$language] = "{$url}/images/{$language}/{$dir}/{$file}";
                }
            }
            return $default_language;
        }

        return $default_language;
    }

    private function processGameUrls($game_launch_url, $game, $game_type_code = null, $attributes = null, $extra = null){
        $lang = $this->utils->getPlayerCenterLanguage();
        $providers_have_lobby = $this->utils->getConfig('allow_lobby_in_provider');
        $this->CI->load->model(['game_description_model']);
        switch ($lang) {
            case self::CHINESE_LANG_CODE:
                $lang = "zh-cn";
                break;
            default:
                $lang = "en-us";
                break;
        }

        switch ($game['game_platform_id']) {
            case PT_API:
                if ($game_type_code == self::TAG_CODE_LIVE_DEALER)
                    $game_launch_url_arr['remarks'] = "Demo/Trial is not avaialable";
                if($game['html_five_enabled'] || $game['flash_enabled']){
                    $game_launch_url_arr['web'] = $game_launch_url . "/default/" . $game['external_game_id'];
                }

                if($game['mobile_enabled']){
                    $game_launch_url_arr['mobile']= $game_launch_url . "/default/" . $game['external_game_id'];
                }
                if($game_type_code !== self::TAG_CODE_LIVE_DEALER) {
                    $game_launch_url_arr['trial'] = $game_launch_url . "/default/" . $game['external_game_id'] . "/trial";
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/<siteName>/<game_launch_code>/<mode>/<is_mobile>";
                break;
            case PT_V2_API:
                if ($game_type_code == self::TAG_CODE_LIVE_DEALER)
                    $game_launch_url_arr['remarks'] = "Demo/Trial is not avaialable";
                if($game['html_five_enabled'] || $game['flash_enabled']){
                    $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game['external_game_id'];
                }

                if($game['mobile_enabled']){
                    $game_launch_url_arr['mobile']= $game_launch_url . "/{$game['game_platform_id']}/" . $game['external_game_id'];
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}>/<game_launch_code>/<mode>>";
                break;
            case MG_API:
                $type = ($game_type_code == self::TAG_CODE_LIVE_DEALER) ? 1:2;
                $game_code = $game['game_code'];
                if(($game_type_code == self::TAG_CODE_LIVE_DEALER)){
                    $game_launch_url_arr['web']['real'] = $game_launch_url . "/".$type."/" . "_mglivecasino/false/real/". $lang;
                    $game_launch_url_arr['web']['demo'] = "N/A";
                } else {
                    if($game['mobile_enabled']){
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/".$type."/" . $game_code . "/true/real/". $lang;
                    } else {
                        $game['flash_enabled'] = true;
                        $game_launch_url_arr['web'] = $game_launch_url . "/".$type."/" . $game_code . "/false/real/". $lang;
                    }
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/<type>/<gamecode>/<lunchmobileorweb>/<mode><language>";
                break;
            case T1N2LIVE_API:
            case T1YL_NTTECH_GAME_API:
            case T1MGPLUS_API:
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real";
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real";
                break;
            case T1EVOLUTION_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : ['game_type' => 'unknown'];
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/". $game['game_code'] . '/real/'. $code["game_type"];
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/". $game['game_code'] . '/real/'. $code["game_type"];
                $game_launch_url_arr['sample'] = $game_launch_url ."/" . $game['game_platform_id'] . "/<game_type>/<game_code>/";
                break;
            case T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
            case T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API:
            case T1_MGPLUS_SEAMLESS_GAME_API:
            case IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
            case IDN_LIVE_MGPLUS_SEAMLESS_GAME_API:
            case MGPLUS_SEAMLESS_API:
            case MGPLUS2_API:
            case MGPLUS_API:
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real";
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real";
                if(!empty($game['demo_link']) && (strtolower($game['demo_link']) == "supported")){
                    $game_launch_url_arr['trial'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/trial";
                }
                $game_launch_url_arr['sample'] = $game_launch_url ."/". $game['game_platform_id'] . '/<game_code>/<mode>';
                break;
                // case NT_API:
                // $game_launch_url_arr['web'] = $game_launch_url . "/default/" . $game['external_game_id'];
                // break;
            case OPUS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/default/" . $game['external_game_id'];
                break;
            case WON_API:
                $game_launch_url_arr['mobile'] = $game_launch_url  ."/" . $game['game_code'] . "/real";
                $game_launch_url_arr['web'] = $game_launch_url  ."/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url  ."/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url  . '/<game_code>/<mode>';
                break;
             case CQ9_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game['game_code'];
                $game_launch_url_arr['mobile']  = $game_launch_url . "/{$game['game_platform_id']}/" . $game['game_code'];
                $game_launch_url_arr['trial']  = $game_launch_url . "/{$game['game_platform_id']}/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_launch_code>/<mode>";
                break;
            case YUXING_CQ9_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . '/real';
                $game_launch_url_arr['trial'] = $game_launch_url . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . '/<mode>';
                break;
            case WICKETS9_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/'.WICKETS9_API.'/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . '/'.WICKETS9_API.'/real';
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_platform_id>/<mode>';
                break;
            case EZUGI_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . EZUGI_API;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . EZUGI_API;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>";
                break;
            case ENTWINE_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . ENTWINE_API;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . ENTWINE_API . "/true/true";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<extra>/<mobile>";
                break;
            case DG_API:
            case DG_SEAMLESS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = "/{$game_launch_url}/{$game['game_platform_id']}";
                $game_launch_url_arr['mobile'] = "/{$game_launch_url}/{$game['game_platform_id']}";
                $game_launch_url_arr['trial'] = "/{$game_launch_url}/{$game['game_platform_id']}/_null/trial";
                $game_launch_url_arr['sample'] = "/{$game_launch_url}/{$game['game_platform_id']}/_null>/<game_mode>";
                break;
            case LD_LOTTERY_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['trial'] = $game_launch_url . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<mode>";
                break;
            case OPUS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/true/20";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/true/20";
                break;
            case ONESGAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_type>/<language>";
                break;
            case GD_API:
            case GENESISM4_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_type>/<game_mode>";
                break;
            case WFT_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<languge>/<mobile>";
                break;
            case IPM_V2_ESPORTS_API:
            case IPM_V2_SPORTS_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<languge>";
                break;
            case IMPT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/default/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/default/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/default/" . $game['game_code']. "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<siteName>/<game_code>/<mode>";
                break;
            case TTG_API:
                $game_id = json_decode($attributes,true)['gameId'];
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_type = isset($attributes['gameType']) ? json_decode($attributes,true)['gameType'] : 0;

                $game_launch_url_arr['web'] = $game_launch_url . "/".$game_id."/" . $game_launch_code ."/". $game_type . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/".$game_id."/" . $game_launch_code ."/". $game_type . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/".$game_id."/" . $game_launch_code ."/". $game_type . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_id>/<game_launch_code>/<game_type>/<mode>";
                break;
            case TTG_SEAMLESS_GAME_API:
            case T1_TTG_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] ."/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case ONESGAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_type>/<language>";
                break;
            // case DT_API:
            case GAMESOS_API:
            case LAPIS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real/web";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real/mobile";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>/<web_platform>";
                break;
            case DT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code'] }";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code'] }";
                $game_launch_url_arr['trial'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code'] }" . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code'] }";
                break;
            case ISB_INR1_API:
            case ISB_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code . "/real";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>/";
                break;
            case FISHINGGAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/";
                break;
            case HRCC_API:
                $game_launch_url_arr['web'] = "unknown";
                $game_launch_url_arr['mobile'] = "unknown";
                $game_launch_url_arr['sample'] = "unknown";
                break;
            case OPUS_SPORTSBOOK_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/OPUS_SPORTSBOOK_API";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/OPUS_SPORTSBOOK_API";
                break;
            case OPUS_KENO_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/keno";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/keno";
                break;
            case KUMA_API:
            case EBET_DT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real/true";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>/<language>";
                break;
            case EBET_IMPT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code']. "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code']. "/real";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>/<type>";
                break;
            case V8POKER_GAME_API:
            case MPOKER_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            // case YEEBET_API:
            /* case MPOKER_SEAMLESS_GAME_API:
            case T1_MPOKER_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break; */
            case EVOLUTION_GAMING_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : ['game_type' => 'unknown'];
                $gameType = isset($code['game_type']) ? $code['game_type'] : 'unknown';
                $game_launch_url_arr['web'] = $game_launch_url ."/". $game['game_code'] . "/real" . "/" .$gameType;
                $game_launch_url_arr['mobile'] = $game_launch_url ."/". $game['game_code'] . "/real" . "/" .$gameType;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>/";
                $game_launch_url_arr['trial'] = $game_launch_url ."/". $game['game_code'] . "/trial" . "/" .$gameType;
                break;
            case T1_EVOLUTION_SEAMLESS_GAME_API:
            case EVOLUTION_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_SEAMLESS_GAMING_API:
            case EVOLUTION_SEAMLESS_THB1_API:
                $game_launch_code_other_settings = @json_decode($game['game_launch_code_other_settings'],true)["game_type"];
                if(empty($game_launch_code_other_settings)){
                    $game_launch_code_other_settings = "_null";
                }
                $game_launch_url_arr['web'] = $game_launch_url."/<game_code>"."/<mode>"."/<game_type>"."/<language>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/".$game['game_code']."/real"."/".$game_launch_code_other_settings."/null";
                $game_launch_url_arr['mobile'] = $game_launch_url."/<game_code>"."/<game_type>"."/<language>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/".$game['game_code']."/real"."/".$game_launch_code_other_settings."/null";
                $game_launch_url_arr['trial'] = $game_launch_url."/".$game['game_code']."/trial"."/".$game_launch_code_other_settings."/null";
                break;
            case EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case T1_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case EVOLUTION_BTG_SEAMLESS_GAMING_API:
            case T1_EVOLUTION_BTG_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            case IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
            case T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url ."/". $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url ."/". $game['game_code'] . "/real";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>/<language>";
                $game_launch_url_arr['trial'] = $game_launch_url ."/". $game['game_code'] . "/trial";
                break;
            case KING_MAKER_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url."/<game_code>"."/<language>"."/<is_mobile>"."/<is_redirect>"."/<game_type>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/".$game["game_code"];
                $game_launch_url_arr['mobile'] = $game_launch_url."/<game_code>"."/<language>"."/<is_mobile>"."/<is_redirect>"."/<game_type>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/".$game["game_code"]."/en"."/true"."/true";
                break;
            case KING_MAKER_GAMING_THB_B2_API:
            case KING_MAKER_GAMING_THB_B1_API:
                $game_launch_url_arr['web'] = $game_launch_url."/<game_code>"."/<language>"."/<is_mobile>"."/<is_redirect>"."/<game_type>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/".$game["game_code"];
                $game_launch_url_arr['mobile'] = $game_launch_url."/<game_code>"."/<language>"."/<is_mobile>"."/<is_redirect>"."/<game_type>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/".$game["game_code"]."/en"."/true"."/true";
                break;
            case MG_QUICKFIRE_API:

                $attributes = json_decode($game['attributes'], true);
                $game_launch_code = isset($attributes['game_launch_code']) ? $attributes['game_launch_code'] : '';
                $game_code = $game['game_code'];
                $product_id = isset($attributes['product_id']) ? $attributes['product_id'] : '';
                $module_id = isset($game['moduleid']) ? $game['moduleid'] : '';
                $client_id = isset($game['clientid']) ? $game['clientid'] : '';

                #ETI (External Third Party Integration) GAMES
                #6 - LEAP, #12 - Ainsworth
                $eti_arr = array(6,12);
                if(in_array($product_id, $eti_arr)){
                    if($game['mobile_enabled']==1){
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/mobile/".$game_code."/".$module_id. "/" .$client_id;
                    }else{
                        $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/desktop/".$game_code."/".$module_id. "/" .$client_id;
                    }
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>/<device_type>/<game_code>/<module_id>/<client_id>";
                }else{
                    if($game['mobile_enabled']){
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/mobile/" . $game_code;
                    }else{
                        $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/desktop/" . $game_code;;
                    }
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>/<device_type>/<game_code>";
                }

                break;
            case EBET_QT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code']. "/desktop/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code']. "/mobile/real";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<device_type>/<mode>/<language>";
                break;
            case ULTRAPLAY_API:
            case ULTRAPLAY_SEAMLESS_GAME_API:
            case T1_ULTRAPLAY_SEAMLESS_GAME_API:
                if (isset($game['type_lang'])) {
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                }
                $game_launch_url_arr['web'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                break;
            case JUMB_GAMING_API:
                if (in_array($game['game_code'], [7001,7002,7003,7004])) {
                    $game_type_code = "fishing";
                }else{
                    if ($game_type_code == "card_games") {
                        $game_type_code = "table_and_cards";
                    } elseif ($game_type_code == "lottery") { //***
                        $game_type_code = "lottery";
                    } elseif ($game_type_code == "arcade") {
                        $game_type_code = "arcade";
                    } elseif ($game_type_code == "slots") {
                        $game_type_code = "slots";
                    }
                }

                // $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_type_code . "/" . $game['game_code']. "/real/desktop";
                // $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_type_code . "/" . $game['game_code']. "/real/mobile";
                // $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_type_code . "/" . $game['game_code']. "/trial";
                // $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type>/<game_code>/<mode>/";
                // <domain>/player_center/goto_common_game/187/8027/trial/slots
                $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game['game_code']}/real/{$game_type_code}";
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game['game_code']}/real/{$game_type_code}";
                $game_launch_url_arr['trial'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game['game_code']}/trial/{$game_type_code}";
                $game_launch_url_arr['sample'] = "{$game_launch_url}/{$game['game_platform_id']}/<game_code>/<game_mode>/<game_type>";
                break;
            case EBET_MG_API:
                if ($game_type_code == "live_dealer") {
                    $game_launch_url_arr['web'] = $game_launch_url . "/_mglivecasino/" . $game['game_code'] . "/real/flash";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/_mglivecasino/" . $game['game_code'] . "/real/html5";
                }else{
                    $game_launch_url_arr['web'] = $game_launch_url . "/_null/" . $game['game_code'] . "/real/flash";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/" . $game['game_code'] . "/real/html5";
                }

                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type>/<game_code>/<game_mode>/<category>";
                break;
            case QT_API:
            case FG_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/".$game['game_platform_id']."/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/".$game['game_platform_id']."/" . $game['game_code'] . "/real";
                if(!empty($game['demo_link']) && (strtolower($game['demo_link']) == "supported")){
                    $game_launch_url_arr['trial'] = $game_launch_url . "/".$game['game_platform_id']."/" . $game['game_code'] . "/trial";
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_paltform_id>/<game_code>/<mode>";
                break;
            case T1ONEWORKS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game['game_type'];
                break;
            case ONEWORKS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/" . $game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/" . $game['game_type'];
                if(!empty($extra) && isset($extra['launcher_language'])){
                    $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/" . $game['game_type']."/{$extra['launcher_language']}";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/" . $game['game_type']."/{$extra['launcher_language']}";
                }
                break;
            case IBC_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/1/";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/1/true";
                break;
            case BBIN_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang'])?$game['type_lang']:null;
                // $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['web'];
                // $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['mobile'];
                // $game_launch_url_arr['trial'] = $game_launch_url . "/trial";
                // $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type>/<game_type_code>/<game_code>/<language>/<mode>/<active_site>";
                if(isset($game['web'])){
                    $game_launch_url_arr['web'] = "{$game_launch_url}/" . BBIN_API . "/_null/real/{$game['web']}";
                }
                if(isset($game['mobile'])){
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . BBIN_API . "/_null/real/{$game['mobile']}";
                }
                $game_launch_url_arr['trial'] = "{$game_launch_url}/" . BBIN_API . "/_null/trial/<game_type>";
                $game_launch_url_arr['sample'] = "{$game_launch_url}/" . BBIN_API . "/<game_code>/<game_mode>/<game_type>";
                break;
            case GSBBIN_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['web'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['mobile'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type_code>/<language>/<mobile>/<mode>";
                break;
            case MTECH_BBIN_API:
                if ($game_type_code == "live_dealer") {
                    $game_type_code = "live";
                } elseif ($game_type_code == "slots") {
                    $game_type_code = "game";
                } elseif ($game_type_code == "lottery") {
                    $game_type_code = "lottery";
                } elseif ($game_type_code == "sports") {
                    $game_type_code = "ball";
                }elseif ($game_type_code == "fishing_game") {
                    $game_type_code = "fisharea";
                }
                $game_launch_url_arr['web'] = $game_launch_url. "/" .$game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url. "/" .$game_type_code;
                break;
            case GAMEPLAY_SBTECH_API:
            case SBTECH_API:
                $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_platform_id']}";
                $game_launch_url_arr['sample'] = "{$game_launch_url}/{$game['game_platform_id']}";
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_platform_id']}";
                break;
            case AGIN_YOPLAY_API:
            case AGIN_API:
            case AGBBIN_API:
            case AGSHABA_API:
                if(array_key_exists('type_lang', $game)) {
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                }
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['web']}/real/{$game['game_type']}";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['mobile']}/real/{$game['game_type']}";
                $game_launch_url_arr['trial'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['mobile']}/trial/{$game['game_type']}";
                $game_launch_url_arr['fun'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['mobile']}/fun/{$game['game_type']}";
                break;
            case T1_HABANERO_SEAMLESS_GAME_API:
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
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . "/fun/";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case SPADE_GAMING_API:
            case NEXTSPIN_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . "/fun/";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case ICONIC_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . "/real/" . $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . "/real/" . $game_type_code;
                $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . "/fun/" . $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case HB_API:
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . HB_API . "/{$game['game_code']}";
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . HB_API . "/{$game['game_code']}";
                $game_launch_url_arr['trial'] = "{$game_launch_url}/" . HB_API . "/{$game['game_code']}/trial";
                $game_launch_url_arr['sample'] = "{$game_launch_url}/" . HB_API . "/<game_code/game id>/<mode>";
                break;
            case IMSLOTS_API:
            case UC_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/real/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/real/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/trial/" . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<mode>/<game_code>";
                break;
            case SA_GAMING_API:
                if(! in_array(SA_GAMING_API,$providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['sample'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    break;
                }

            case T1_SA_GAMING_SEAMLESS_GAME_API:
            case SA_GAMING_SEAMLESS_THB1_API:
            case SA_GAMING_SEAMLESS_API:
            case T1_EZUGI_SEAMLESS_GAME_API:
            case EZUGI_SEAMLESS_API:
                $attributes = json_decode($attributes, true);
                $game['game_code'] = isset($attributes['game_launch_code']) ? $attributes['game_launch_code'] : $game['external_game_id'];

                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case T1SA_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;

            case YOPLAY_API:
            case T1YOPLAY_API:
            case EBET_KUMA_API:
            case EBET_GGFISHING_API:
            case KYCARD_API:
                if(! in_array(KYCARD_API,$providers_have_lobby)){
                    $game_code = $game['game_code'];

                    $attributes = json_decode($attributes, true);
                    $game_code = isset($attributes['game_launch_code']) ? $attributes['game_launch_code'] : $game_code;

                    $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game_code;
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game_code;
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_platform_id']}/_null/real/{$game['game_type']}";
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_platform_id']}/_null/real/{$game['game_type']}";
                    $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_code>/<game_mode>/<game_type>";
                    break;
                }
            case T1KYCARD_API:
            case T1LE_GAMING_API:
            case LE_GAMING_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>";
                break;


            // case LE_GAMING_API:
            //     $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
            //     $game_launch_url_arr['web'] = $game_launch_url;
            //     $game_launch_url_arr['mobile'] = $game_launch_url;
            //     $game_launch_url_arr['sample'] = "player_center/goto_<game_platform_name>";
            //     break;
            case SPORTSBOOK_API:
            case KENOGAME_API:
            case BETEAST_API:
            case IDN_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : '';
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/true";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<mobile>";
                break;
            case OM_LOTTO_GAME_API:
            case AB_API:
            case AB_V2_GAME_API:
            case OG_API:
            case VR_API:
            case LB_API:
            case XHTDLOTTERY_API:
            case EBET_OPUS_API:
            case EBET_BBIN_API:
            case RWB_API:
            // case TCG_API:
            case LOTO_SEAMLESS_API:
            case HP_2D3D_GAME_API:
                $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case TCG_API:
                $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_platform_id']}";
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_platform_id']}";
                break;
            case PINNACLE_SEAMLESS_GAME_API:
            case T1_PINNACLE_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . $game['game_platform_id'];
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . $game['game_platform_id'];
                if ($game_type_code == self::TAG_CODE_E_SPORTS) {
                    $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                    $game_launch_url_arr['web'] = "{$game_launch_url}/" . $game['game_platform_id'] . '/_null/real/e_sports';
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . $game['game_platform_id'] . '/_null/real/e_sports';
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_launch_code>/<mode>/<game_type>";
                break;
            case PINNACLE_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . PINNACLE_API . '/_null/real/sports';
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . PINNACLE_API . '/_null/real/sports';
                if ($game_type_code == self::TAG_CODE_E_SPORTS) {
                    $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                    $game_launch_url_arr['web'] = "{$game_launch_url}/" . PINNACLE_API . '/_null/real/e_sports';
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . PINNACLE_API . '/_null/real/e_sports';
                }
                break;
            case AP_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . AP_GAME_API . '/_null/real/sports';
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . AP_GAME_API . '/_null/real/sports';
                if ($game_type_code == self::TAG_CODE_E_SPORTS) {
                    $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                    $game_launch_url_arr['web'] = "{$game_launch_url}/" . AP_GAME_API . '/_null/real/e_sports';
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . AP_GAME_API . '/_null/real/e_sports';
                }
                break;
            case IM_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . IM_SEAMLESS_GAME_API;
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . IM_SEAMLESS_GAME_API;
                if ($game_type_code == self::TAG_CODE_E_SPORTS) {
                    $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                    $game_launch_url_arr['web'] = "{$game_launch_url}" . '/ESPORTSBULL';
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}" . '/ESPORTSBULL';
                }
                if ($game_type_code == self::TAG_CODE_SPORTS) {
                    $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                    $game_launch_url_arr['web'] = "{$game_launch_url}" . '/IMSB';
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}" . '/IMSB';
                }
                break;
            case EBET_API:
                $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = "{$game_launch_url}/" . EBET_API . "/_null/real/" . $game['game_type'];
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . EBET_API . "/_null/real/" . $game['game_type'];
                $game_launch_url_arr['trial'] = "{$game_launch_url}/" . EBET_API . "/_null/trial" . $game['game_type'];
                $game_launch_url_arr['sample'] = "{$game_launch_url}/" . EBET_API . "/<game_code>/<mode>/<game_type>";

                /* if ($game_type_code == "slots") {
                    $game_launch_url_arr['web'] = "{$game_launch_url}/" . EBET_API . "/_null/real/slots";
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . EBET_API . "/_null/real/slots";
                    $game_launch_url_arr['trial'] = "{$game_launch_url}/" . EBET_API . "/_null/trial/slots";
                    $game_launch_url_arr['sample'] = "{$game_launch_url}/" . EBET_API . "/_null/<mode>/<game_type>";
                } */
                break;
            case T1_PNG_SEAMLESS_API:
            case PNG_SEAMLESS_GAME_API:
            case PNG_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real";
                if($game['mobile_enabled']==1){
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real";
                }
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
                break;
            case PRAGMATICPLAY_API:
            case PRAGMATICPLAY_IDR1_API:
            case PRAGMATICPLAY_IDR2_API:
            case PRAGMATICPLAY_IDR3_API:
            case PRAGMATICPLAY_IDR4_API:
            case PRAGMATICPLAY_IDR5_API:
            case PRAGMATICPLAY_IDR6_API:
            case PRAGMATICPLAY_IDR7_API:
            case PRAGMATICPLAY_THB1_API:
            case PRAGMATICPLAY_THB2_API:
            case PRAGMATICPLAY_CNY1_API:
            case PRAGMATICPLAY_CNY2_API:
            case PRAGMATICPLAY_VND1_API:
            case PRAGMATICPLAY_VND2_API:
            case PRAGMATICPLAY_VND3_API:
            case PRAGMATICPLAY_MYR1_API:
            case PRAGMATICPLAY_MYR2_API:
            case PRAGMATIC_PLAY_FISHING_API:
            case PRAGMATICPLAY_SEAMLESS_THB1_API:
            case PRAGMATICPLAY_SEAMLESS_STREAMER_API:
            case PRAGMATICPLAY_SEAMLESS_API:
            case T1_PRAGMATICPLAY_SEAMLESS_API:
            case T1_IDN_PRAGMATICPLAY_SEAMLESS_API:
            case IDN_PRAGMATICPLAY_SEAMLESS_API:
            case IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API:
            case IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API:
            case T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API:
            case T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API:
                $game_tags_without_trial_link = [
                    self::TAG_CODE_LIVE_DEALER,
                    self::TAG_CODE_SPORTS,
                    self::TAG_CODE_FISHING_GAME,
                ];
                //domain/player_center/goto_common_game/232/vs20fruitsw/trial
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                // $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/".$game['game_platform_id'];
                // $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/".$game['game_platform_id'];
                // if($game_type_code !== self::TAG_CODE_LIVE_DEALER) {
                //     $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun/".$game['game_platform_id'];
                // }
                // $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>/<game platform id>";
                $game_launch_url_arr['web'] = "/{$game_launch_url}/{$game['game_platform_id']}/{$game_launch_code}/real/{$game_type_code}";
                $game_launch_url_arr['mobile'] = "/{$game_launch_url}/{$game['game_platform_id']}/{$game_launch_code}/real/{$game_type_code}";
                /* if($game_type_code !== self::TAG_CODE_LIVE_DEALER) {
                    $game_launch_url_arr['trial'] = "/{$game_launch_url}/{$game['game_platform_id']}/{$game_launch_code}/fun";
                } */
                if(!in_array($game_type_code, $game_tags_without_trial_link)) {
                    $game_launch_url_arr['trial'] = "/{$game_launch_url}/{$game['game_platform_id']}/{$game_launch_code}/trial/{$game_type_code}";
                    if (empty($game['demo_link'])) {
                        unset($game_launch_url_arr['trial']);
                    }
                }
                $game_launch_url_arr['sample'] = "/{$game_launch_url}/{$game['game_platform_id']}/<game_code>/<game_mode>";
                break;
            // case PRAGMATIC_PLAY_FISHING_API:
            //     $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
            //     $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
            //     $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATIC_PLAY_FISHING_API;
            //     $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATIC_PLAY_FISHING_API;
            //     $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun/".PRAGMATIC_PLAY_FISHING_API;
            //     $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
            //     break;
            // case PRAGMATICPLAY_SEAMLESS_THB1_API:
            //     $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
            //     $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
            //     $game_launch_url_arr['web'] = $game_launch_url . $game['game_platform_id'] . "/" . $game_launch_code. "/real";
            //     $game_launch_url_arr['mobile'] = $game_launch_url . $game['game_platform_id'] . "/" . $game_launch_code. "/real";
            //     $game_launch_url_arr['trial'] = $game_launch_url . $game['game_platform_id'] . "/" . $game_launch_code. "/fun";
            //     $game_launch_url_arr['sample'] = $game_launch_url . $game['game_platform_id'] . "/<game_launch_code>/<mode>";
            //     break;
            case EBET_SPADE_GAMING_API:
            case EBET_BBTECH_API:
            case YUNGU_GAME_API:
            case LEBO_GAME_API:
            case ISB_SEAMLESS_API:
            case GOLDENF_PGSOFT_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                // $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real";
                // $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real";
                // $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun";
                // $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game_launch_code;
                $game_launch_url_arr['mobile']  = $game_launch_url . "/{$game['game_platform_id']}/" . $game_launch_code;
                $game_launch_url_arr['trial']  = $game_launch_url . "/{$game['game_platform_id']}/" . $game_launch_code . "/fun";
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_launch_code>/<mode>";
                break;
            case PGSOFT_API:
            case PGSOFT3_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/" . $game_launch_code;
                $game_launch_url_arr['mobile']  = $game_launch_url . "/{$game['game_platform_id']}/" . $game_launch_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_launch_code>/<mode>";
                break;
            case HG_API:
            case EBET_AG_API:
            case IG_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" .$game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_type'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type>";
                break;
            case EXTREME_LIVE_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" .$game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_type'] . "/true";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_type'] . "/false/true";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_type>/<mobile>/<mode>";
                break;
            case TGP_AG_API:
                $code = json_decode(isset($game['game_launch_code_other_settings']),true);
                $game_launch_code = !empty($code) ? $code['game_launch_code']: $game['game_code'];
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url ;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case T1SUNCITY_API:
            case SUNCITY_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];

                if(!in_array(SUNCITY_API,$providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url . "/" .$game['sub_game_provider'] . "/" . $game_launch_code;
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" .$game['sub_game_provider'] . "/" . $game_launch_code;
                    $game_launch_url_arr['trial'] = $game_launch_url . "/" .$game['sub_game_provider'] . "/" . $game_launch_code . "/trial";
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_provider_code>/<game_code>/<mode>";
                    break;

                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url . "/{$game['sub_game_provider']}/{$game['game_code']}";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['sub_game_provider']}/{$game['game_code']}";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['sub_game_provider']}/{$game['game_code']}/trial";
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_provider_code>/<game_code>/<mode>";
                    break;
                }
            case MWG_API:
                $game_code = isset($game['game_code']) ? $game['game_code']: $game['main_lobby'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case RTG_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['sub_game_provider'] . "/" . $game['game_code'] . "/real" ;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['sub_game_provider'] . "/" . $game['game_code'] . "/real" ;
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['sub_game_provider'] . "/" . $game['game_code'] . "/trial" ;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_id>/<mach_id>/<mode>";
                break;
            case T1MG_API:
                $type = ($game_type_code == self::TAG_CODE_LIVE_DEALER) ? 1:2;
                $game_code = $game['game_code'];
                if(($game_type_code == self::TAG_CODE_LIVE_DEALER)){
                    $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/". $game_code;
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/". $game_code;

                } else {
                    if($game['mobile_enabled']){
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/". $game_code;
                    } else {
                        $game['flash_enabled'] = true;
                        $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/". $game_code;
                    }
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>";
                break;
            case T1PT_API:
                if ($game_type_code == self::TAG_CODE_LIVE_DEALER) {
                    $game_launch_url_arr['remarks'] = "Demo/Trial is not avaialable";
                }

                $game_launch_url_arr['web']= $game_launch_url . "/" . $game['game_platform_id'] . "/" .$game['external_game_id'] . '/real';
                $game_launch_url_arr['mobile']= $game_launch_url . "/" . $game['game_platform_id'] . "/" .$game['external_game_id'] . '/real';
                $game_launch_url_arr['trial']= $game_launch_url . "/" . $game['game_platform_id'] . "/" .$game['external_game_id'] . '/fun';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<external_game_id>/<mode>";
                break;

            case T1PRAGMATICPLAY_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/fun";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_launch_code>/<mode>";
                break;

            case T1YOPLAY_API:
            case T1AB_API:
            // case T1MG_API:
            case T1DG_API:
            case T1PNG_API:
            case T1HB_API:
            case T1AE_SLOTS_API:
            case T1MTECHBBIN_API:
            case T1SPADE_GAMING_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game_launch_code . "/fun";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>". "/<game_launch_code>" . "/<real or fun>";
                break;

            case T1JUMB_API:
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real" . "/" . $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/real" . "/" . $game_type_code;
                $game_launch_url_arr['trial'] = $game_launch_url ."/" . $game['game_platform_id'] ."/" . $game['game_code'] . "/fun" . "/" . $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<mode>/<game_type_code>";
                break;

            case T1TTG_API:
            case T1EZUGI_API:
            case T1VR_API:
            case T1AB_V2_API:
            case T1NTTECH_V2_API:
            case T1HOGAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/";
                break;
            case T1EBET_API:
                $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                if ($game_type_code == "slots") {
                    $game_launch_url_arr['web'] = $game_launch_url . "/real/false/6";
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/real/true/6";
                }
                break;
            case T1ISB_API:
            case T1YGGDRASIL_API:
            case T1LOTTERY_EXT_API:
            case T1UC_API:
            case T1DT_API:
            case T1IDN_API:
            case T1GD_API:
            case T1QT_API:
            case T1GGPOKER_GAME_API:
            case T1FG_ENTAPLAY_API:
            case T1ONEWORKS_API:
            case T1GG_API:
            case T1DG_API:
            case QT_HACKSAW_SEAMLESS_API:
            case FG_ENTAPLAY_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_id = (!empty($code) && is_array($code) && array_key_exists('game_id', $code)) ? $code['game_id'] : '';
                $game_launch_url_arr['web'] = rtrim($game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'] . "/real/" . $game_id . "/", '/');
                $game_launch_url_arr['mobile'] = rtrim($game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'] . "/real/" . $game_id . "/", '/');
                $game_launch_url_arr['trial'] = rtrim($game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'] . "/trial/" . $game_id . "/", '/');
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<mode>/<game_id>/<language>/<redirect>";
                break;
            case T1AGIN_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['web'] . "/real" . "/" . $game['web'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['mobile'] . "/real" . "/" . $game['mobile'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['web'] . "/trial" . "/" . $game['web'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<mode>/<game_id>/<language>/<redirect>";
                break;
            case MG_DASHUR_API:
                if ($game_type_code == "live_dealer")
                    $game_type_code = "live";

                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code']}/real/{$game_type_code}";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code']}/real/{$game_type_code}";
                $game_launch_url_arr['trial'] = $game_launch_url . "/{$game['game_platform_id']}/{$game['game_code']}/real/{$game_type_code}";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<game_mode>/<game_type>";
                break;
            case T1OG_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case T1BBIN_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['web'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['mobile'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['web'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<mode>/<game_id>/<language>/<redirect>";
                break;
            case BETSOFT_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/real/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/real/" . $game['game_code'];
                break;

            // goto_betsoft_game($game_mode = 'real', $game_id='191',  $target = "iframe")
            case GAMEPLAY_API:
                // $platform = $game_type_code == "slots" ? "rslot" : ($game_type_code == 'live_dealer' ? $game_type_code = 'table' : $game_type_code);
                // $platform = $game_type_code == "slots" ? "rslot" : ($game_type_code == 'live_dealer' ? $game_type_code = 'table' :  (strpos($game_type_code, 'lottery_') !== false) ? str_replace('lottery_', '', $game_type_code) : $game_type_code);

                /*$platform = $game_type_code == "slots" ? "rslot" : (
                    (
                        $game_type_code == 'live_dealer' ? $game_type_code = 'table' : (
                            (strpos($game_type_code, 'lottery_') !== false ? str_replace('lottery_', '', $game_type_code) : $game_type_code)
                        )
                    )
                );*/

                $platform = $game_type_code;
                if($game_type_code == "slots"){
                    $platform ="rslot";
                }else{
                    if($game_type_code == 'live_dealer'){
                        $platform = 'table';
                    }else{
                        if(strpos($game_type_code, 'lottery_') !== false){
                            $platform = str_replace('lottery_', '', $game_type_code);
                        }
                    }
                }

                $game_launch_code = $game_type_code == "slots" ? "d_lobby" : "null";

                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game_type_code . "/". $game_launch_code ."/". $platform . "/0";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game_type_code . "/". $game_launch_code ."/". $platform . "/0";
                if ($game_type_code != 'table') {
                    $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game_type_code . "/". $game_launch_code ."/". $platform ."/1/null";
                }
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_type>/<game_code>/<platform>/<game_mode>/<is_mobile>/<game_name>";
                break;
                // goto_gpgame($game_platform_id, $game_type = null, $game_code = null, $platform = null, $game_mode = 0, $is_mobile = null, $game_name = null)

            case SBOBET_API:
            case SBOBET_SEAMLESS_GAME_API:
                $game_type_code = $game_type_code == 'sports' ? 'sportsbook' : 'casino';
                // $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/". $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/{$game['game_platform_id']}/_null/real/". $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/{$game['game_platform_id']}/<game_code>/<game_mode>/<game_type";
                break;

            case REDTIGER_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/". $game['game_platform_id']."/<game_code>/<game_mode>/<sub_game_provider>";
                $game_launch_url_arr['sample_web'] = $game_launch_url . "/". $game['game_platform_id']."/".$game['game_code']."/real"."/".$game['sub_game_provider'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/". $game['game_platform_id']."/<game_code>/<game_mode>/<sub_game_provider>/<language>/<extra>/<is_redirect>/<is_mobile>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url . "/". $game['game_platform_id']."/".$game['game_code']."/real"."/".$game['sub_game_provider']."/null/en-US/null/false/true";

                $game_launch_url_arr['demo'] = $game_launch_url . "/". $game['game_platform_id'] . "/<game_code>/demo/<sub_game_provider>/<sub_game_api>/<language>";
                $game_launch_url_arr['sample_demo'] = $game_launch_url . "/". $game['game_platform_id'] . "/" . $game['game_code'] ."/demo"."/".$game['sub_game_provider']."/null/en-US";
                break;
            case HOGAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url ."/". $game['game_platform_id']."/<game_type_id>/<lang>/<table_id>/<game_unique_id>/<bet_limit>";
                $game_launch_url_arr['sample_web'] = $game_launch_url ."/". $game['game_platform_id']."/en/0000000000000019/m777FH/1";
                $game_launch_url_arr['mobile'] = $game_launch_url ."/". $game['game_platform_id']."/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url ."/". $game['game_platform_id']."/en/0000000000000001/null/real/1/true/V3";
                break;
            case HOGAMING_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url ."/". $game['game_platform_id']."/null/real/en";
                $game_launch_url_arr['mobile'] = $game_launch_url ."/". $game['game_platform_id']."/null/real/en";
                $game_launch_url_arr['trial'] = $game_launch_url ."/". $game['game_platform_id']."/null/trial/en";
                $game_launch_url_arr['sample'] = $game_launch_url ."/<game_platform_id>/<game_type>/<game_mode>/<language>";
                break;
            case REDTIGER_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/fun";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case VIVOGAMING_SEAMLESS_API:
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
            case VIVOGAMING_API:
            case T1_VIVOGAMING_SEAMLESS_API:
                # add game API if needed to be lobby
                if(in_array(VIVOGAMING_THB_B1_API,$providers_have_lobby) || in_array(VIVOGAMING_API,$providers_have_lobby) || in_array(VIVOGAMING_SEAMLESS_API, $providers_have_lobby) || in_array(T1_VIVOGAMING_SEAMLESS_API, $providers_have_lobby)){
                        $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                        $game_launch_url_arr['web'] = $game_launch_url;
                        $game_launch_url_arr['sample'] = $game_launch_url;
                        $game_launch_url_arr['mobile'] = $game_launch_url;
                        break;
                }else{
                    $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                    break;
                }
            case QQKENO_QQLOTTERY_API:
            case QQKENO_QQLOTTERY_CNY_B1_API:
            case QQKENO_QQLOTTERY_THB_B1_API:
            case QQKENO_QQLOTTERY_USD_B1_API:
            case QQKENO_QQLOTTERY_VND_B1_API:
            case QQKENO_QQLOTTERY_MYR_B1_API:
                $game_launch_url_arr['web'] =  $game_launch_url;
                break;
            case SOLID_GAMING_THB_API:
                $game_launch_url_arr['web'] =  $game_launch_url . $game['game_platform_id'] . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] =  $game_launch_url . $game['game_platform_id'] . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] =  $game_launch_url . $game['game_platform_id'] . '/' . $game['game_code'] . '/DEMO';
                break;

            case NTTECH_API:
            case NTTECH_IDR_B1_API:
            case NTTECH_CNY_B1_API:
            case NTTECH_THB_B1_API:
            case NTTECH_USD_B1_API:
            case NTTECH_VND_B1_API:
            case NTTECH_MYR_B1_API:
                $game_language = $game['game_platform_id'] == NTTECH_IDR_B1_API ? "id" :
                                    ($game['game_platform_id'] == NTTECH_CNY_B1_API ? "cn" :
                                        ($game['game_platform_id'] == NTTECH_THB_B1_API ? "th" :
                                            ($game['game_platform_id'] == NTTECH_VND_B1_API ? "vn" : "en")));
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] =  $game_launch_url."/" . $game_language;
                $game_launch_url_arr['mobile'] =  $game_launch_url."/" . $game_language . "/true";
                $game_launch_url_arr['sample'] = $game_launch_url."/<language>/<is_mobile>";
                break;

            case NTTECH_V2_API:
            case NTTECH_V3_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case NTTECH_V2_IDR_B1_API:
            case NTTECH_V2_CNY_B1_API:
            case NTTECH_V2_THB_B1_API:
            case NTTECH_V2_USD_B1_API:
            case NTTECH_V2_VND_B1_API:
            case NTTECH_V2_MYR_B1_API:
            case NTTECH_V2_INR_B1_API:
                // $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                // $game_launch_url_arr['web'] =  $game_launch_url."/". "LIVE";
                // $game_launch_url_arr['mobile'] =  $game_launch_url."/". "LIVE";
                // $game_launch_url_arr['sample'] = $game_launch_url."/<gameType>/<language>/<is_mobile>/<is_redirect>";
                //player_center/goto_common_game/2117/MX-LIVE-003/real/LIVE/th
                // $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] =  "{$game_launch_url}/{$game['game_code']}/real/LIVE";
                $game_launch_url_arr['mobile'] =  "{$game_launch_url}/{$game['game_code']}/real/LIVE";
                $game_launch_url_arr['sample'] = $game_launch_url."/goto_common_game/{$game['game_platform_id']}/<game_code>/<game_mode>/<game_type>/<language>";
                if(isset($game['mobile_only'])){
                    unset($game_launch_url_arr['web']);
                }
                break;

            case T1NTTECH_V2_CNY_B1_API:
                $game_language = "cn";
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;

            case ONEBOOK_API:
            case ONEBOOK_IDR_B1_API:
            case ONEBOOK_CNY_B1_API:
            case ONEBOOK_THB_B1_API:
            case ONEBOOK_USD_B1_API:
            case ONEBOOK_VND_B1_API:
            case ONEBOOK_MYR_B1_API:
                /*$game_launch_url_arr['web'] =  $game_launch_url."/<language>/<is_mobile>/<skin_id>/<oddstype>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/en/false/bl001/5";
                $game_launch_url_arr['mobile'] =  $game_launch_url."/<is_mobile>/<skin_id>/<oddstype>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/en/true/bl001/5";*/
                $gameCode = isset($game['game_code'])?$game['game_code']:'_null';
                $gameType = isset($game['game_type'])?$game['game_type']:'_null';

                $game_launch_url_arr['web'] = $game_launch_url.'/'.$gameCode.'/real/'.$gameType;
                $game_launch_url_arr['mobile'] = $game_launch_url.'/'.$gameCode.'/real/'.$gameType;
                $game_launch_url_arr['sample'] = $game_launch_url.'/<game_code>/<game_mode>/<game_type>/<language>';
                break;

            case SBOBETGAME_API:
            case SBOBETGAME_IDR_B1_API:
            case SBOBETGAME_CNY_B1_API:
            case SBOBETGAME_THB_B1_API:
            case SBOBETGAME_USD_B1_API:
            case SBOBETGAME_VND_B1_API:
            case SBOBETGAME_MYR_B1_API:
            // case SBOBETV2_GAME_API:
                $game_launch_url_arr['web'] =  $game_launch_url."/<language>/<is_mobile>/<theme>/<oddstyle>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/en/false/blue/EU";
                $game_launch_url_arr['mobile'] =  $game_launch_url."<language>/<is_mobile>/<theme>/<oddstyle>/<is_redirect>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/en/true/blue/EU/true";
                break;

            case AE_SLOTS_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code'];
                $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code'];
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                $game_launch_url_arr['trial'] = $game_launch_url."/".$game['game_code']."/request_demo_play";
                break;
            case REDRAKE_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url."/<game_code>"."/<game_mode>"."/<game_type>"."/<language>";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/".$game['game_code']."/real/null/en";
                $game_launch_url_arr['mobile'] = $game_launch_url."/<game_code>"."/<game_mode>"."/<game_type>"."/<language>";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/".$game['game_code']."/real/null/en";
                $game_launch_url_arr['trial'] = $game_launch_url."/".$game['game_code']."/demo/null/en";
                break;
            case BAISON_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case BOOMING_SEAMLESS_API:
            case T1_BOOMING_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['external_game_id'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['external_game_id'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_platform_id'] . "/" . $game['external_game_id'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<external_game_id>/<game_mode>";
                break;
            case AVIA_ESPORT_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['trial'] = $game_launch_url . "/trial";
                break;
            case OG_V2_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case AFB88_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case DONGSEN_ESPORTS_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case DONGSEN_LOTTERY_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case YGG_SEAMLESS_GAME_API:
            case YGGDRASIL_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . '/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . '/real';
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case RG_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] =  $game_launch_url;
                $game_launch_url_arr['mobile'] =  $game_launch_url;
                break;
            case TPG_API:
                $game_launch_url_arr['web'] =  $game_launch_url . $game['game_code'];
                $game_launch_url_arr['mobile'] =  $game_launch_url . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . $game['game_code'];
                break;
            case LUCKY_GAME_CHESS_POKER_API:
                if(! in_array(LUCKY_GAME_CHESS_POKER_API,$providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                    $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                    break;
                } else {
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] =  $game_launch_url;
                    $game_launch_url_arr['mobile'] =  $game_launch_url;
                    break;
                }

            case FLOW_GAMING_SEAMLESS_API:
            case FLOW_GAMING_SEAMLESS_THB1_API:

            case FLOW_GAMING_NETENT_SEAMLESS_THB1_API:
            case FLOW_GAMING_NETENT_SEAMLESS_API:

            case FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API:
            case FLOW_GAMING_YGGDRASIL_SEAMLESS_API:

            case FLOW_GAMING_MAVERICK_SEAMLESS_THB1_API:
            case FLOW_GAMING_MAVERICK_SEAMLESS_API:

            case FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API:
            case FLOW_GAMING_QUICKSPIN_SEAMLESS_API:

            case FLOW_GAMING_PNG_SEAMLESS_THB1_API:
            case FLOW_GAMING_PNG_SEAMLESS_API:

            case FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API:
            case FLOW_GAMING_4THPLAYER_SEAMLESS_API:

            case FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API:
            case FLOW_GAMING_RELAXGAMING_SEAMLESS_API:

            case FLOW_GAMING_MG_SEAMLESS_API:
            case T1_FLOW_GAMING_SEAMLESS_API:
            case T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;

            case HA_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;

            case GOLDEN_RACE_GAMING_API:
            case GD_SEAMLESS_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : null;
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_platform_id'];
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_platform_id'];
                $game_launch_url_arr['trial'] = $game_launch_url ."/" . $game['game_platform_id'] . "/null/trial/null/en";
                $game_launch_url_arr['sample'] = $game_launch_url ."/<game platform id>/<game code>/<mode>/<game type>/<language>";
                break;
            case SLOT_FACTORY_SEAMLESS_API:
            case SLOT_FACTORY_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_platform_id'] . "/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>/<game_code>/<game_mode>";
                break;
            case T1TFGAMING_ESPORTS_API:
            case TFGAMING_ESPORTS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>";
                break;
            case BETF_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . $game['game_platform_id'];
                $game_launch_url_arr['mobile'] = $game_launch_url . $game['game_platform_id'];
                $game_launch_url_arr['sample'] = $game_launch_url . "<game_platform_id>";
                break;
            case T1LOTTERY_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = "player_center/goto_<game_platform_name>";
                break;
            case JILI_GAME_API:
            case JILI_SEAMLESS_API:
            case T1_JILI_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BISTRO_SEAMLESS_API:
            case T1LOTTERY_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case BGSOFT_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case T1GAMES_SEAMLESS_GAME_API:
            case BGSOFT_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . '/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'] . '/real';
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/demo';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case GENESIS_SEAMLESS_API:
            case GENESIS_SEAMLESS_THB1_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . '/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'] . '/real';
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/play';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;

            case ASIASTAR_API:
                $game_launch_url_arr['game_type_lang'] = @$game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url ;
                break;

            case OGPLUS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;


            case HUB88_API:
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['external_game_id'] . '/real';
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['external_game_id'] . '/real';
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['external_game_id'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<external_game_id>/<game_mode>";
                break;
            case KG_POKER_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_platform_id'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_platform_id>";
                break;
            case TIANHONG_MINI_GAMES_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case GPK_API:
                $game_launch_type = $game_type_code === self::TAG_CODE_FISHING_GAME ? 'fishing' : 'slots';
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real/" . $game_launch_type;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real/" . $game_launch_type;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                break;
            case AG_SEAMLESS_THB1_API:
                if(! in_array(AG_SEAMLESS_THB1_API, $providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/zh-cn";
                    $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/zh-cn";
                    $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    //$game_launch_url_arr['trial'] = $game_launch_url."/".$game['game_code']."/false/null/zh-cn";
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    //$game_launch_url_arr['trial'] = $game_launch_url."/0/trial/null/zh-cn";
                    break;
                }
            case LUCKY_STREAK_SEAMLESS_GAME_API:
            case LUCKY_STREAK_SEAMLESS_THB1_API:
                if(! in_array(LUCKY_STREAK_SEAMLESS_THB1_API, $providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/zh-cn";
                    $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/th";
                    $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    break;
                }
            case EA_GAME_API:
            case EA_GAME_API_THB1_API:
                if(! in_array(EA_GAME_API_THB1_API, $providers_have_lobby)){
                    /* $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/zh-cn";
                    $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/th";
                    $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>"; */

                    $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . '/real';
                    $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . '/real';
                    $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . '/trial';
                    $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<game_mode>';
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    break;
                }

            case NETENT_SEAMLESS_GAME_API:
            case NETENT_SEAMLESS_GAME_IDR1_API:
            case NETENT_SEAMLESS_GAME_CNY1_API:
            case NETENT_SEAMLESS_GAME_THB1_API:
            case NETENT_SEAMLESS_GAME_MYR1_API:
            case NETENT_SEAMLESS_GAME_VND1_API:
            case NETENT_SEAMLESS_GAME_USD1_API:
             case NETENT_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/th";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/th";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                break;
            case BG_SEAMLESS_GAME_IDR1_API:
            case BG_SEAMLESS_GAME_CNY1_API:
            case BG_SEAMLESS_GAME_THB1_API:
            case BG_SEAMLESS_GAME_MYR1_API:
            case BG_SEAMLESS_GAME_VND1_API:
            case BG_SEAMLESS_GAME_USD1_API:
            case BG_SEAMLESS_GAME_API;
                $game_launch_url_arr['web'] = $game_launch_url."/null/real/".$game['game_type']."/th";
                $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_type>/<language>";
                $game_launch_url_arr['mobile'] = $game_launch_url."/null/real/".$game['game_type']."/th";
                $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                break;
            case SPORTSBOOK_FLASH_TECH_GAME_API:
            case T1SPORTSBOOK_FLASH_TECH_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
            case SPORTSBOOK_FLASH_TECH_GAME_IDR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_CNY1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_THB1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_MYR1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_VND1_API:
            case SPORTSBOOK_FLASH_TECH_GAME_USD1_API:
                if(! in_array(SPORTSBOOK_FLASH_TECH_GAME_THB1_API, $providers_have_lobby)){
                    $game_launch_url_arr['web'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/zh-cn";
                    $game_launch_url_arr['sample_web'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    $game_launch_url_arr['mobile'] = $game_launch_url."/".$game['game_code']."/true"."/null"."/th";
                    $game_launch_url_arr['sample_mobile'] = $game_launch_url."/<game_code>/<game_mode>/<game_type>/<language>";
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    break;
                }
            case JOKER_API:
            case BDM_SEAMLESS_API:
            case T1_JOKER_SEAMLESS_GAME_API:
                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game['game_code'] . '/real';
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game['game_code'] . '/real';

                if (!empty($game['demo_link'])) {
                    $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                }

                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case RGS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/_null/real/{$game['game_type']}";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/real/{$game['game_type']}";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                break;
            case T1SBTECH_BTI_API:
            case SBTECH_BTI_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
                break;
            case WM_API:
            case T1WM_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['trial'] = $game_launch_url . "/null" . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url ."/<mode>";
                break;
            case AMG_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code']. "/trial";
                break;
            case TIANHAO_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];

                $game_launch_url_arr['mobile'] = $game_launch_url ."/" . $game_launch_code;
                $game_launch_url_arr['web'] = $game_launch_url ."/" . $game_launch_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case IMESB_API:

                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] =  $game_launch_url;
                $game_launch_url_arr['mobile'] =  $game_launch_url;
                break;
            case S128_GAME_API:

                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] =  $game_launch_url;
                $game_launch_url_arr['mobile'] =  $game_launch_url;
                break;

            case ICONIC_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . "/real/";
                $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . "/fun/";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
                break;
            case T1_SEXY_BACCARAT_SEAMLESS_API:
            case SEXY_BACCARAT_SEAMLESS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                break;
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
                $params = "/{$game['game_code']}/en-us/real/_null/_null/_null/newtab/";
                $trial_params = "/{$game['game_code']}/en-us/trial/_null/_null/_null/newtab/";
                $game_launch_url_arr['web'] = $game_launch_url . $params ;
                $game_launch_url_arr['mobile'] = $game_launch_url . $params ;
                $game_launch_url_arr['trial'] = $game_launch_url . $trial_params ;
                // $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . '/' .;
                break;

            case KINGPOKER_GAME_API_IDR1_API:
            case KINGPOKER_GAME_API_CNY1_API:
            case KINGPOKER_GAME_API_THB1_API:
            case KINGPOKER_GAME_API_MYR1_API:
            case KINGPOKER_GAME_API_VND1_API:
            case KINGPOKER_GAME_API_USD1_API:
            case KINGPOKER_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                // $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;

            case EVOPLAY_GAME_API_IDR1_API:
            case EVOPLAY_GAME_API_CNY1_API:
            case EVOPLAY_GAME_API_THB1_API:
            case EVOPLAY_GAME_API_MYR1_API:
            case EVOPLAY_GAME_API_VND1_API:
            case EVOPLAY_GAME_API_USD1_API:
            case EVOPLAY_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
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
            case PRETTY_GAMING_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['trial'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case HYDAKO_IDR1_API:
            case HYDAKO_CNY1_API:
            case HYDAKO_THB1_API:
            case HYDAKO_MYR1_API:
            case HYDAKO_VND1_API:
            case HYDAKO_USD1_API:
            case HYDAKO_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url  . "/" . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>";
                break;
            case PRAGMATICPLAY_LIVEDEALER_CNY1_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATICPLAY_LIVEDEALER_CNY1_API;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATICPLAY_LIVEDEALER_CNY1_API;
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun/".PRAGMATICPLAY_LIVEDEALER_CNY1_API;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
                break;
            case PRAGMATICPLAY_LIVEDEALER_THB1_API:
                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game['game_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATICPLAY_LIVEDEALER_THB1_API;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_launch_code. "/real/".PRAGMATICPLAY_LIVEDEALER_THB1_API;
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_launch_code. "/fun/".PRAGMATICPLAY_LIVEDEALER_THB1_API;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
                break;
            case RUBYPLAY_SEAMLESS_API:
            case RUBYPLAY_SEAMLESS_THB1_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/".$game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .$game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" .$game['game_code']. "/trial";
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<game_code>";
                break;
            case PHOENIX_CHESS_CARD_POKER_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_launch_code>/<mode>";
                break;
            case BETGAMES_SEAMLESS_IDR1_GAME_API:
            case BETGAMES_SEAMLESS_CNY1_GAME_API:
            case BETGAMES_SEAMLESS_THB1_GAME_API:
            case BETGAMES_SEAMLESS_MYR1_GAME_API:
            case BETGAMES_SEAMLESS_VND1_GAME_API:
            case BETGAMES_SEAMLESS_USD1_GAME_API:
            // case BETGAMES_SEAMLESS_GAME_API:
            case CHAMPION_SPORTS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;

            case QUEEN_MAKER_REDTIGER_GAME_API:
            case QUEEN_MAKER_GAME_API:
            case KING_MIDAS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>/<game_mode>";
                break;
            case AMB_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case GMT_GAME_API:
            case ONEGAME_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . "/real";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/demo";
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>/<game_mode>";
                break;
            case LIVE12_SEAMLESS_GAME_API:
                $game_code = json_decode($attributes,true)['game_launch_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code . "/Slot";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code . "/Slot";
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>/<game_type>/<provider_id>";
                break;
            case LIVE12_SPADEGAMING_SEAMLESS_API:
                $game_code = json_decode($attributes,true)['game_launch_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code . "/Slot/14";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code . "/Slot/14";
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>/<game_type>/<provider_id>";
                break;
            case LIVE12_REDTIGER_SEAMLESS_API:
                $game_code = json_decode($attributes,true)['game_launch_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code . "/Slot/10";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code . "/Slot/10";
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>/<game_type>/<provider_id>";
                break;
            case LIVE12_EVOLUTION_SEAMLESS_API:
                $game_code = json_decode($attributes,true)['game_launch_code'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code . "/Slot/16";
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code . "/Slot/16";
                break;
            case YABO_GAME_API:
            case N2LIVE_API:
                $game_launch_url_arr['web'] = $game_launch_url ;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case IBC_ONEBOOK_API:
            case IBC_ONEBOOK_SEAMLESS_API:
            case T1_IBC_ONEBOOK_SEAMLESS_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                break;
            case YL_NTTECH_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url ;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case HKB_GAME_API;
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['web'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['mobile'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<external_game_id>";
                break;
            case SGWIN_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case HOTGRAPH_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case T1_EBET_SEAMLESS_GAME_API:
            case EBET_SEAMLESS_GAME_API:
                if(!empty($attributes)) {
                    $provider_id = json_decode($attributes, true);
                    if(array_key_exists('provider_id', $provider_id)) {
                        $provider = "/".$provider_id['provider_id'];
                    }
                    $provider_sample = "/<provider_id>";
                }else{
                    $provider = "";
                    $provider_sample = "";
                }

                $code = isset($game['game_launch_code_other_settings']) ? json_decode($game['game_launch_code_other_settings'], true) : [];
                $game_code = isset($game['game_code'])?$game['game_code']:'_null';
                $game_launch_code = (!empty($code) && is_array($code) && array_key_exists('game_launch_code', $code)) ? $code['game_launch_code'] : $game_code;
                $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game_code}/real/{$game_type_code}/_null";
                $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game_code}/real/{$game_type_code}/_null";
                $game_launch_url_arr['trial'] = "{$game_launch_url}/{$game['game_platform_id']}/{$game_code}/demo/{$game_type_code}/_null";
                $game_launch_url_arr['sample'] = "{$game_launch_url}/{$game['game_platform_id']}/<game_code>/<game_mode>/<game_type>/<language>";

                if($game_type_code !== self::TAG_CODE_LIVE_DEALER) {
                    $game_launch_url_arr['web'] = $game_launch_url . '/'. $game['game_platform_id'].'/' . $game_code."/real"."/".$game_type_code."/_null".$provider;
                    $game_launch_url_arr['mobile'] = $game_launch_url .'/'.$game['game_platform_id'].'/'. $game_code."/real"."/".$game_type_code."/_null".$provider;
                    $game_launch_url_arr['trial'] = "{$game_launch_url}/{$game['game_platform_id']}/_null/demo";
                    $game_launch_url_arr['sample'] = $game_launch_url .'/'.$game['game_platform_id']. "/<game_code>/<game_mode>/<game_type>/<language>".$provider_sample;
                }
                break;
            case KPLAY_EVO_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case JUMBO_SEAMLESS_GAME_API:
            case T1_JUMBO_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                break;
            case CHERRY_GAMING_SEAMLESS_GAME_API:
            case T1_CHERRY_GAMING_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case BETER_SEAMLESS_GAME_API:
            case T1_BETER_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/demo";
                break;
            case BETER_SPORTS_SEAMLESS_GAME_API:
            case T1_BETER_SPORTS_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case EVENBET_POKER_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case T1_FC_SEAMLESS_GAME_API:
            case FC_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . "/demo";
                break;
            case AMB_PGSOFT_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case BBGAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case KGAME_API:
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case IDNPOKER_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case IPM_V2_IMSB_ESPORTSBULL_API;
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['web'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['mobile'];
                //$game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['web'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case PT_V3_API:
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'] . '/real/' . $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'] . '/real/' . $game_type_code;
                $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . '/fun/' . $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<mode>/<game_type>';

                if ($game_type_code == self::TAG_CODE_LIVE_DEALER) {
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                }
                break;

            case T1_EZUGI_REDTIGER_SEAMLESS_GAME_API:
            case T1_EZUGI_EVO_SEAMLESS_GAME_API:
            case EZUGI_EVO_SEAMLESS_API:
            case EZUGI_NETENT_SEAMLESS_API:
            case EZUGI_REDTIGER_SEAMLESS_API:
                $game_code = $game['game_code'];
                if(!empty($attributes)) {
                    $game_launch_other = json_decode($attributes, true);
                    if(array_key_exists('game_launch_code', $game_launch_other)) {
                        $game_code = $game_launch_other['game_launch_code'];
                    }
                }
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_type>/<game_mode>";
                break;

            case PGSOFT2_SEAMLESS_API:
            case PGSOFT_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>/<game_type>/<language>";
                break;
            case LUCKY365_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url;
                $game_launch_url_arr['mobile'] = $game_launch_url;
                $game_launch_url_arr['sample'] = $game_launch_url;
                break;
            case LIONKING_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . '/_null/app';
                $game_launch_url_arr['mobile'] = $game_launch_url . '/_null/app';
                $game_launch_url_arr['sample'] = $game_launch_url . '<game_code>/<game_mode>';
                break;
            case T1_EVOPLAY_SEAMLESS_GAME_API:
            case EVOPLAY_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case IDNLIVE_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case ORYX_PARIPLAY_SEAMLESS_API:
            case BEFEE_PARIPLAY_SEAMLESS_API:
            case FBM_PARIPLAY_SEAMLESS_API:
            case TRIPLECHERRY_PARIPLAY_SEAMLESS_API:
            case DARWIN_PARIPLAY_SEAMLESS_API:
            case SPINOMENAL_PARIPLAY_SEAMLESS_API:
            case SMARTSOFT_PARIPLAY_SEAMLESS_API:
            case SPRIBE_PARIPLAY_SEAMLESS_API:
            case SPINMATIC_PARIPLAY_SEAMLESS_API:
            case WIZARD_PARIPLAY_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BOOMING_PARIPLAY_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case CQ9_SEAMLESS_GAME_API:
            case T1_CQ9_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . '/real/' . $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'] . '/real/' . $game_type_code;
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial/' . $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";

                if ($game_type_code == self::TAG_CODE_LIVE_DEALER) {
                    unset($game_launch_url_arr['trial']);
                }
                break;
            case T1_PNG_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case SOFTSWISS_SEAMLESS_GAME_API:
            case SOFTSWISS_BGAMING_SEAMLESS_GAME_API:
            case SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API:
            case SOFTSWISS_SPRIBE_SEAMLESS_GAME_API:
            case SOFTSWISS_BETSOFT_SEAMLESS_GAME_API:
            case SOFTSWISS_WAZDAN_SEAMLESS_GAME_API:
                $game_tags_without_trial_link = [
                    self::TAG_CODE_LIVE_DEALER,
                    self::TAG_CODE_SPORTS,
                    self::TAG_CODE_FISHING_GAME,
                ];

                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'].'/real';
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'].'/real';

                if(!in_array($game_type_code, $game_tags_without_trial_link)) {
                    $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                }

                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BGAMING_SEAMLESS_GAME_API:
            case T1_BGAMING_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case T1_WAZDAN_SEAMLESS_GAME_API:
            case WAZDAN_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case T1_YL_SEAMLESS_GAME_API:
            case YL_NTTECH_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                // $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case SBOBETV2_GAME_API:
                $game_type_code = $game_type_code == 'sports' ? 'sportsbook' : 'casino';
                // $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/_null/real/". $game_type_code;
                $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/real/". $game_type_code;
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                break;
            case WE_SEAMLESS_GAME_API:
                    if(in_array(WE_SEAMLESS_GAME_API, $this->utils->getConfig('allow_lobby_in_provider'))){
                        /* 
                            $game_launch_url_arr['web'] = "{$game_launch_url}/" . "{$game['web']}";
                            $game_launch_url_arr['mobile'] = "{$game_launch_url}/" . "{$game['mobile']}";
                        */

                        $game_launch_url_arr['game_type_lang'] =  isset($game['type_lang']) ? $game['type_lang'] : "";
                        $game_launch_url_arr['web'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/_null/real/" . $game['game_type'];
                        $game_launch_url_arr['trial'] = $game_launch_url . "/_null/trial" . $game['game_type'];
                        $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                    } else {
                        $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'] . '/real/' . $game_type_code;
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'] . '/real/' . $game_type_code;
                        $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial/' . $game_type_code;
                        $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                    }
                break;
            case AG_SEAMLESS_GAME_API:
                $game_tags_without_trial_link = [
                    self::TAG_CODE_LIVE_DEALER,
                    self::TAG_CODE_SPORTS,
                    self::TAG_CODE_FISHING_GAME,
                ];

                $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];

                /* if(!in_array($game_type_code, $game_tags_without_trial_link)) {
                    $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                } */

                $game_launch_url_arr['sample'] = $game_launch_url . "/" . "<game_code>";
                break;
            case SKYWIND_SEAMLESS_GAME_API:
            case T1_SKYWIND_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case CALETA_SEAMLESS_API:
            case T1_CALETA_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case QT_HACKSAW_SEAMLESS_API:
            case T1_QT_HACKSAW_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case TADA_SEAMLESS_GAME_API:
            case T1_TADA_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case SPADEGAMING_SEAMLESS_GAME_API:
            case T1_SPADEGAMING_SEAMLESS_GAME_API:
            case IDN_SPADEGAMING_SEAMLESS_GAME_API:
            case T1_IDN_SPADEGAMING_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BOOMING_SEAMLESS_GAME_API:
            case T1_BOOMING_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case CMD_SEAMLESS_GAME_API:
            case CMD2_SEAMLESS_GAME_API:
            case T1_CMD_SEAMLESS_GAME_API:
            case T1_CMD2_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : '';
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>';
                break;
            case SV388_AWC_SEAMLESS_GAME_API:
            case T1_SV388_AWC_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : '';
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>';
                break;
            case T1_SPRIBE_JUMBO_SEAMLESS_GAME_API:
            case SPRIBE_JUMBO_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : '';
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>';
                break;
            case YGG_DCS_SEAMLESS_GAME_API:
            case HACKSAW_DCS_SEAMLESS_GAME_API:
            case AVATAR_UX_DCS_SEAMLESS_GAME_API:
            case RELAX_DCS_SEAMLESS_GAME_API:
            case T1_YGG_DCS_SEAMLESS_GAME_API:
            case T1_HACKSAW_DCS_SEAMLESS_GAME_API:
            case T1_AVATAR_UX_DCS_SEAMLESS_GAME_API:
            case T1_RELAX_DCS_SEAMLESS_GAME_API:
                $game_launch_url_arr['game_type_lang'] = isset($game['type_lang']) ? $game['type_lang'] : '';
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>';
                break;
            case QT_NOLIMITCITY_SEAMLESS_API:
            case T1_QT_NOLIMITCITY_SEAMLESS_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/" .  $game['game_code'] . '/<mode>/<game_type>/<language>';
                break;
            case KING_MAKER_SEAMLESS_GAME_API:
            case T1_KING_MAKER_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BIGPOT_SEAMLESS_GAME_API:
            case T1_BIGPOT_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case WM_SEAMLESS_GAME_API:
            case WM2_SEAMLESS_GAME_API:
            case T1_WM_SEAMLESS_GAME_API:
            case T1_WM2_SEAMLESS_GAME_API:
                $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . "/" .  $game['game_code'];
                $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game['game_code'] . '/trial';
                $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<game_mode>";
                break;

            case T1_SMARTSOFT_SEAMLESS_GAME_API:
            case SMARTSOFT_SEAMLESS_GAME_API:
                $game_launch_url_arr['web']     = $game_launch_url . "/". $game['game_code']. "/";
                $game_launch_url_arr['mobile']  = $game_launch_url . "/". $game['game_code']. "/";
                $game_launch_url_arr['trial']   = $game_launch_url . "/". $game['game_code']. "/" . "trial";
                $game_launch_url_arr['sample']  = $game_launch_url . "/<game_code>/<game_mode>/<game_type>";
                break;
            case T1_ASTAR_SEAMLESS_GAME_API:
            case ASTAR_SEAMLESS_GAME_API:
                    $game_launch_url_arr['web']     = $game_launch_url;
                    $game_launch_url_arr['mobile']  = $game_launch_url;
                    $game_launch_url_arr['trial']   = $game_launch_url . "/_null/trial";
                    $game_launch_url_arr['sample']  = $game_launch_url . "/<game_mode>";
                    break;
            case NEX4D_GAME_API:
                $game_launch_url_arr['web']     = $game_launch_url;
                $game_launch_url_arr['mobile']  = $game_launch_url;
                $game_launch_url_arr['sample']  = $game_launch_url . "/<game_code>/<game_mode>";
                break;
            case BETGAMES_SEAMLESS_GAME_API:
            case T1_BETGAMES_SEAMLESS_GAME_API:
            case TWAIN_SEAMLESS_GAME_API:
            case T1_TWAIN_SEAMLESS_GAME_API:
                if (in_array($game['game_platform_id'], GAME_DESCRIPTION_MODEL::GAME_API_WITH_LOBBYS)) {
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;

                    if (in_array($game['game_platform_id'], Game_description_model::GAME_API_WITH_TRIAL) || !empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = $game_launch_url . '/null/trial';
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url;
                } else {
                    $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                    $game_launch_url_arr['mobile'] = $game_launch_url . '/' .  $game['game_code'];

                    if (!empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . '/trial';
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<game_mode>';
                }
                break;
            case HP_LOTTERY_GAME_API:
                if (in_array($game['game_platform_id'], GAME_DESCRIPTION_MODEL::GAME_API_WITH_LOBBYS)) {
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;

                    if (!empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = $game_launch_url . '/trial';
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url;
                } else {
                    $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                    $game_launch_url_arr['mobile'] = $game_launch_url . '/' .  $game['game_code'];

                    if (!empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . '/trial';
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<game_mode>';
                }
                break;
            case HACKSAW_SEAMLESS_GAME_API:
            case T1_HACKSAW_SEAMLESS_GAME_API:
            case BNG_SEAMLESS_GAME_API:
            case T1_BNG_SEAMLESS_GAME_API:
            case RTG_SEAMLESS_GAME_API:
            case RTG2_SEAMLESS_GAME_API:
            case T1_RTG_SEAMLESS_GAME_API:
            case T1_RTG2_SEAMLESS_GAME_API:
            case ONE_TOUCH_SEAMLESS_GAME_API:
            case T1_ONE_TOUCH_SEAMLESS_GAME_API:
            case AB_SEAMLESS_GAME_API:
            case T1_AB_SEAMLESS_GAME_API:
            case MPOKER_SEAMLESS_GAME_API:
            case T1_MPOKER_SEAMLESS_GAME_API:
            case PT_SEAMLESS_GAME_API:
            case T1_PT_SEAMLESS_GAME_API:
            case IDN_PT_SEAMLESS_GAME_API:
            case T1_IDN_PT_SEAMLESS_GAME_API:
            case IDN_SLOTS_PT_SEAMLESS_GAME_API:
            case T1_IDN_SLOTS_PT_SEAMLESS_GAME_API:
            case IDN_LIVE_PT_SEAMLESS_GAME_API:
            case T1_IDN_LIVE_PT_SEAMLESS_GAME_API:
            case FA_WS168_SEAMLESS_GAME_API:
            case T1_FA_WS168_SEAMLESS_GAME_API:
                if (in_array($game['game_platform_id'], GAME_DESCRIPTION_MODEL::GAME_API_WITH_LOBBYS)) {
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;

                    if (!empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = $game_launch_url . '/trial';
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url;
                } else {
                    $game_launch_url_arr['web'] = "{$game_launch_url}/{$game['game_code']}/real/{$game_type_code}";
                    $game_launch_url_arr['mobile'] = "{$game_launch_url}/{$game['game_code']}/real/{$game_type_code}";

                    if (!empty($game['demo_link'])) {
                        $game_launch_url_arr['trial'] = "{$game_launch_url}/{$game['game_code']}/trial/{$game_type_code}";
                    }

                    $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<game_mode>/<game_type>';
                }
                break;
            case MASCOT_SEAMLESS_GAME_API:
            case T1_MASCOT_SEAMLESS_GAME_API:
                
                $game_launch_url_arr['web'] = $game_launch_url . '/' . $game['game_code'];
                $game_launch_url_arr['mobile'] = $game_launch_url . '/' .  $game['game_code'];

                if (!empty($game['demo_link'])) {
                    $game_launch_url_arr['trial'] = $game_launch_url . '/' . $game['game_code'] . '/trial';
                }

                $game_launch_url_arr['sample'] = $game_launch_url . '/<game_code>/<game_mode>';
                
                break;
            default:

                $has_trial = in_array($game['game_platform_id'], Game_description_model::GAME_API_WITH_TRIAL) || !empty($game['demo_link']);
                if(! in_array($game['game_platform_id'],$providers_have_lobby) && !in_array($game['game_platform_id'], Game_description_model::GAME_API_WITH_LOBBYS)){
                    $attributes = $attributes != null ? json_decode($attributes, true) : null;
                    if($attributes != null && array_key_exists('game_launch_code', $attributes)) {
                        $game_code = $attributes['game_launch_code'];
                    }
                    else {
                        $game_code = $game['game_code'];
                    }
                    $game_launch_url_arr['web'] = $game_launch_url . "/" . $game_code;
                    $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game_code;
                    if($has_trial) {
                        $game_launch_url_arr['trial'] = $game_launch_url . "/" . $game_code . '/trial';
                    }
                    $game_launch_url_arr['sample'] = $game_launch_url . "/" . $game_code . '/<mode>/<game_type>/<language>';
                    break;
                }else{
                    $game_launch_url_arr['game_type_lang'] = $game['type_lang'];
                    $game_launch_url_arr['web'] = $game_launch_url;
                    $game_launch_url_arr['sample'] = $game_launch_url;
                    $game_launch_url_arr['mobile'] = $game_launch_url;
                    if($has_trial) {
                        $game_launch_url_arr['mobile'] = $game_launch_url . "/null/trial";
                    }
                    $game_launch_url_arr['sample'] = $game_launch_url . "/" . '<game_code>/<mode>/<game_type>/<language>';
                    break;
                }

            // case XYZBLUE_API:
            //     $game_launch_url_arr['web'] = $game_launch_url . "/" . $game['game_code']. "/real";
            //     $game_launch_url_arr['mobile'] = $game_launch_url . "/" . $game['game_code']. "/real";
            //     $game_launch_url_arr['sample'] = $game_launch_url . "/<game_code>/<mode>";
            //     break;
        }

        if( empty($game['demo_link'])){
            if(isset($game_launch_url_arr['trial']) && !empty($game_launch_url_arr['trial'])){
                unset($game_launch_url_arr['trial']);
            }
            if(isset($game_launch_url_arr['demo']) && !empty($game_launch_url_arr['demo'])){
                unset($game_launch_url_arr['demo']);
            }
        }

        if (!empty($game['html_five_enabled']))
            return $game_launch_url_arr;

        if (array_key_exists('mobile_enabled',$game) && $game['mobile_enabled'] == GAME_DESCRIPTION_MODEL::DB_FALSE)
           unset($game_launch_url_arr['mobile']);

        if (array_key_exists('flash_enabled',$game) && $game['flash_enabled'] == GAME_DESCRIPTION_MODEL::DB_FALSE)
           unset($game_launch_url_arr['web']);

        return $game_launch_url_arr;
    }

    public function getGameProviderDetails()
    {
        $game_platforms['available_game_providers'] = [
            PT_API => $this->getGameApiDetails(PT_API,"PlayTech","Playtech","goto_ptgame", "PT"),
            MG_API => $this->getGameApiDetails(MG_API,"MG","Microgaming","goto_mggame", "MG"),
            NT_API => $this->getGameApiDetails(NT_API,"NT","NetEnt","goto_ntgame", "NT"),
            BBIN_API => $this->getGameApiDetails(BBIN_API,"BBIN","BBIN","goto_common_game", "BBIN"),
            OPUS_API => $this->getGameApiDetails(OPUS_API,"OPUS","OPUS","goto_opusgame", "OPUS"),
            ONESGAME_API => $this->getGameApiDetails(ONESGAME_API,"ONESGAME","ONESGAME","goto_onesgame", "ONESGAME"),
            GSPT_API => $this->getGameApiDetails(GSPT_API,"GS PlayTech","GSPT","goto_gsptgame", "GSPT"),
            KENOGAME_API => $this->getGameApiDetails(KENOGAME_API,"KENOGAME","KENOGAME","goto_kenogamegame", "KENOGAME"),
            AB_API => $this->getGameApiDetails(AB_API,"AllBet","KENOGAME","goto_abgame", "AB"),
            AB_V2_GAME_API => $this->getGameApiDetails(AB_V2_GAME_API,"AllBet V2","AllBetV2","goto_common_game/".AB_V2_GAME_API, "ALLBETV2"),
            IBC_API => $this->getGameApiDetails(IBC_API,"IBC","IBC","goto_ibc", "IBC"),
            GD_API => $this->getGameApiDetails(GD_API,"Gold Deluxe","GD","goto_gdgame", "GD"),
            XHTDLOTTERY_API => $this->getGameApiDetails(XHTDLOTTERY_API,"XHTDLOTTERY","XHTDLOTTERY","goto_xhtdlottery", "XHTDLOTTERY"),
            WFT_API => $this->getGameApiDetails(WFT_API,"WFT","WFT","goto_wftgame", "WFT"),
            HB_API => $this->getGameApiDetails(HB_API,"Habanero","HB","goto_common_game", "HB"),
            IMPT_API => $this->getGameApiDetails(IMPT_API,"Inplay Matrix Playtech","IMPT","goto_imptgame", "IMPT"),
            QT_API => $this->getGameApiDetails(QT_API,"Qtech","QT","goto_qtgame", "QT"),
            TTG_API => $this->getGameApiDetails(TTG_API,"Top Trend Gaming","TTG","goto_ttggame", "TTG"),
            TTG_SEAMLESS_GAME_API => $this->getGameApiDetails(TTG_SEAMLESS_GAME_API,"TTG_SEAMLESS_GAME_API","TTG_SEAMLESS_GAME_API","goto_common_game/".TTG_SEAMLESS_GAME_API, "TTG_SEAMLESS_GAME_API"),
            T1_TTG_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_TTG_SEAMLESS_GAME_API,"T1_TTG_SEAMLESS_GAME_API","T1_TTG_SEAMLESS_GAME_API","goto_t1games/".T1_TTG_SEAMLESS_GAME_API, "T1_TTG_SEAMLESS_GAME_API"),
            ENTWINE_API => $this->getGameApiDetails(ENTWINE_API,"ENTWINE","ENTWINE","goto_entwine", "ENTWINE"),
            GAMESOS_API => $this->getGameApiDetails(GAMESOS_API,"GAMESOS","GAMESOS","goto_gamesosgame", "GAMESOS"),
            FG_API => $this->getGameApiDetails(FG_API,"Flow Gaming","FG","goto_fggame", "FG"),
            EBET_API => $this->getGameApiDetails(EBET_API,"EBET","EBET","goto_common_game", "EBET"),
            LAPIS_API => $this->getGameApiDetails(LAPIS_API,"Microgaming Lapis","LAPIS","goto_mglapis_game", "LAPIS"),
            ISB_API => $this->getGameApiDetails(ISB_API,"Isoftbet","ISB","goto_isb_game", "ISB"),
            ONEWORKS_API => $this->getGameApiDetails(ONEWORKS_API,"ONEWORKS","ONEWORKS","goto_common_game", "ONEWORKS"),
            SPORTSBOOK_API => $this->getGameApiDetails(SPORTSBOOK_API,"IPM","SPORTSBOOK","goto_ipm", "SPORTSBOOK"),
            FISHINGGAME_API => $this->getGameApiDetails(FISHINGGAME_API,"Global Gaming","FISHINGGAME","goto_common_game/".FISHINGGAME_API, "FISHINGGAME"),
            IMSLOTS_API => $this->getGameApiDetails(IMSLOTS_API,"Global Gaming","IMSLOTS","goto_imslots", "IMSLOTS"),
            AGBBIN_API => $this->getGameApiDetails(AGBBIN_API,"AG BBIN","AGBBIN","goto_agbbingame", "AGBBIN"),
            AGSHABA_API => $this->getGameApiDetails(AGSHABA_API,"AGSHABA","AGSHABA","goto_agshabagame", "AGSHABA"),
            AGIN_API => $this->getGameApiDetails(AGIN_API,"AGIN","AGIN","goto_common_game", "AGIN"),
            AGIN_YOPLAY_API => $this->getGameApiDetails(AGIN_YOPLAY_API,"AGIN_YOPLAY_API","AGIN_YOPLAY_API","goto_agingame", "AGIN_YOPLAY_API"),
            HRCC_API => $this->getGameApiDetails(HRCC_API,"HRCC","HRCC","goto_hrcc_game", "HRCC"),
            BETEAST_API => $this->getGameApiDetails(BETEAST_API,"BETEAST","BETEAST","goto_beteastgame", "BETEAST"),
            UC_API => $this->getGameApiDetails(UC_API,"UC","UC","goto_beteastgame", "UC"),
            OPUS_SPORTSBOOK_API => $this->getGameApiDetails(OPUS_SPORTSBOOK_API,"OPUS SPORTSBOOK","OPUS_SPORTSBOOK","goto_opus", "OPUS_SPORTSBOOK"),
            OPUS_KENO_API => $this->getGameApiDetails(OPUS_KENO_API,"OPUS KENO","OPUS_KENO","goto_opus", "OPUS_KENO"),
            KUMA_API => $this->getGameApiDetails(KUMA_API,"KUMA","KUMA","goto_kumagame", "KUMA"),
            EZUGI_API => $this->getGameApiDetails(EZUGI_API,"EZUGI","EZUGI","goto_common_game", "EZUGI"),
            DT_API => $this->getGameApiDetails(DT_API,"Dream Tech","DT","goto_common_game", "DT"),
            IDN_API => $this->getGameApiDetails(IDN_API,"IDN Play","IDN","goto_idngame", "IDN"),
            SA_GAMING_API => $this->getGameApiDetails(SA_GAMING_API,"SA Gaming","SA_GAMING","goto_sagaminggame", "SA_GAMING"),
            SA_GAMING_SEAMLESS_THB1_API => $this->getGameApiDetails(SA_GAMING_SEAMLESS_THB1_API,"SA Gaming","SA_GAMING_SEAMLESS_API","goto_common_game/".SA_GAMING_SEAMLESS_THB1_API, "SA_GAMING_SEAMLESS_API"),
            SA_GAMING_SEAMLESS_API => $this->getGameApiDetails(SA_GAMING_SEAMLESS_API,"SA_GAMING_SEAMLESS_API","SA_GAMING_SEAMLESS_API","goto_common_game/".SA_GAMING_SEAMLESS_API, "SA_GAMING_SEAMLESS_API"),
            T1_SA_GAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SA_GAMING_SEAMLESS_GAME_API,"SA Gaming","T1_SA_GAMING_SEAMLESS_GAME_API","goto_t1games/".T1_SA_GAMING_SEAMLESS_GAME_API, "T1_SA_GAMING_SEAMLESS_GAME_API"),
            EBET_BBIN_API => $this->getGameApiDetails(EBET_BBIN_API,"EBET BBIN","EBET_BBIN","goto_ebetbbingame", "EBET_BBIN"),
            MG_QUICKFIRE_API => $this->getGameApiDetails(MG_QUICKFIRE_API,"MG QUICKFIRE","MG_QUICKFIRE","goto_mgquickfire_game", "MG_QUICKFIRE"),
            OG_API => $this->getGameApiDetails(OG_API,"Oriental Gaming","OG","goto_oggame", "OG"),
            JUMB_GAMING_API => $this->getGameApiDetails(JUMB_GAMING_API,"JUMB 168","JUMB_GAMING","goto_common_game", "JUMB_GAMING"),
            SPADE_GAMING_API => $this->getGameApiDetails(SPADE_GAMING_API,"Spade Gaming","SPADE_GAMING","goto_common_game/".SPADE_GAMING_API, "SPADE_GAMING"),
            PRAGMATICPLAY_API => $this->getGameApiDetails(PRAGMATICPLAY_API,"PragmaticPlay","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY"),
            PRAGMATICPLAY_IDR1_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR1_API,"PragmaticPlay IDR1","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR1_API"),
            PRAGMATICPLAY_IDR2_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR2_API,"PragmaticPlay IDR2","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR2_API"),
            PRAGMATICPLAY_IDR3_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR3_API,"PragmaticPlay IDR3","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR3_API"),
            PRAGMATICPLAY_IDR4_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR4_API,"PragmaticPlay IDR4","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR4_API"),
            PRAGMATICPLAY_IDR5_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR5_API,"PragmaticPlay IDR5","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR5_API"),
            PRAGMATICPLAY_IDR6_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR6_API,"PragmaticPlay IDR6","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR6_API"),
            PRAGMATICPLAY_IDR7_API => $this->getGameApiDetails(PRAGMATICPLAY_IDR7_API,"PragmaticPlay IDR7","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_IDR7_API"),
            PRAGMATICPLAY_THB1_API => $this->getGameApiDetails(PRAGMATICPLAY_THB1_API,"PragmaticPlay THB1","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_THB1_API"),
            PRAGMATICPLAY_THB2_API => $this->getGameApiDetails(PRAGMATICPLAY_THB2_API,"PragmaticPlay THB2","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_THB2_API"),
            PRAGMATICPLAY_CNY1_API => $this->getGameApiDetails(PRAGMATICPLAY_CNY1_API,"PragmaticPlay CNY1","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_CNY1_API"),
            PRAGMATICPLAY_CNY2_API => $this->getGameApiDetails(PRAGMATICPLAY_CNY2_API,"PragmaticPlay CNY2","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_CNY2_API"),
            PRAGMATICPLAY_VND1_API => $this->getGameApiDetails(PRAGMATICPLAY_VND1_API,"PragmaticPlay VND1","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_VND1_API"),
            PRAGMATICPLAY_VND2_API => $this->getGameApiDetails(PRAGMATICPLAY_VND2_API,"PragmaticPlay VND2","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_VND2_API"),
            PRAGMATICPLAY_VND3_API => $this->getGameApiDetails(PRAGMATICPLAY_VND3_API,"PragmaticPlay VND3","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_VND3_API"),
            PRAGMATICPLAY_MYR1_API => $this->getGameApiDetails(PRAGMATICPLAY_MYR1_API,"PragmaticPlay MYR1","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_MYR1_API"),
            PRAGMATICPLAY_MYR2_API => $this->getGameApiDetails(PRAGMATICPLAY_MYR2_API,"PragmaticPlay MYR2","PRAGMATICPLAY","goto_common_game", "PRAGMATICPLAY_MYR2_API"),
            PRAGMATIC_PLAY_FISHING_API => $this->getGameApiDetails(PRAGMATIC_PLAY_FISHING_API,"PragmaticPlay Fishing","PRAGMATICPLAY Fishing","goto_common_game", "PRAGMATICPLAY Fishing"),
            SBOBET_API => $this->getGameApiDetails(SBOBET_API,"SBO Bet","SBOBET","goto_common_game", "SBOBET"),
            SBOBET_SEAMLESS_GAME_API => $this->getGameApiDetails(SBOBET_SEAMLESS_GAME_API,"SBOBET SEAMLESS","SBOBET_SEAMLESS_GAME_API","goto_common_game", "SBOBET_SEAMLESS_GAME_API"),
            PNG_API => $this->getGameApiDetails(PNG_API,"Play N Go","PNG","goto_pnggame", "PNG"),
            ULTRAPLAY_API => $this->getGameApiDetails(ULTRAPLAY_API,"ULTRAPLAY","ULTRAPLAY","goto_ultraplay_game", "ULTRAPLAY"),
            ULTRAPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(ULTRAPLAY_SEAMLESS_GAME_API,"ULTRAPLAY_SEAMLESS_GAME_API","ULTRAPLAY_SEAMLESS_GAME_API","goto_common_game/".ULTRAPLAY_SEAMLESS_GAME_API, "ULTRAPLAY_SEAMLESS_GAME_API"),
            T1_ULTRAPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_ULTRAPLAY_SEAMLESS_GAME_API,"T1_ULTRAPLAY_SEAMLESS_GAME_API","T1_ULTRAPLAY_SEAMLESS_GAME_API","goto_t1games/".T1_ULTRAPLAY_SEAMLESS_GAME_API, "T1_ULTRAPLAY_SEAMLESS_GAME_API"),
            PINNACLE_API => $this->getGameApiDetails(PINNACLE_API,"PINNACLE","PINNACLE","goto_common_game", "PINNACLE"),
            PINNACLE_SEAMLESS_GAME_API => $this->getGameApiDetails(PINNACLE_SEAMLESS_GAME_API,"PINNACLE_SEAMLESS_GAME_API","PINNACLE_SEAMLESS_GAME_API","goto_common_game", "PINNACLE_SEAMLESS_GAME_API"),
            T1_PINNACLE_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_PINNACLE_SEAMLESS_GAME_API, "T1_PINNACLE_SEAMLESS_GAME_API", "T1_PINNACLE_SEAMLESS_GAME_API", "goto_t1games", "T1_PINNACLE_SEAMLESS_GAME_API"),
            VR_API => $this->getGameApiDetails(VR_API,"Virtual Racing","VR","goto_vrgame", "VR"),
            EVOLUTION_GAMING_API => $this->getGameApiDetails(EVOLUTION_GAMING_API,"Evolution Gaming","EVOLUTION_GAMING","goto_common_game/".EVOLUTION_GAMING_API, "EVOLUTION_GAMING"),
            EBET_SPADE_GAMING_API => $this->getGameApiDetails(EBET_SPADE_GAMING_API,"Ebet Spade Gaming","EBET_SPADE_GAMING","goto_ebet_spadegame", "EBET_SPADE_GAMING"),
            EBET_BBTECH_API => $this->getGameApiDetails(EBET_BBTECH_API,"Ebet BBTECH","EBET_BBTECH","goto_ebetbbtech", "EBET_BBTECH"),
            EBET_IMPT_API => $this->getGameApiDetails(EBET_IMPT_API,"Ebet Inplay Matrix Playtech","EBET_IMPT","goto_ebetimpt", "EBET_IMPT"),
            EBET_MG_API => $this->getGameApiDetails(EBET_MG_API,"Ebet Microgaming","EBET_MG","goto_ebet_mg_game", "EBET_MG"),
            YOPLAY_API => $this->getGameApiDetails(YOPLAY_API,"YOPLAY","YOPLAY_API","goto_yoplaygame", "YOPLAY"),
            GAMEPLAY_SBTECH_API => $this->getGameApiDetails(GAMEPLAY_SBTECH_API,"GAMEPLAY SBTECH","GAMEPLAY_SBTECH","goto_gameplaySbtech", "GAMEPLAY_SBTECH"),
            EBET_KUMA_API => $this->getGameApiDetails(EBET_KUMA_API,"EBET KUMA","EBET_KUMA","goto_ebetkumagame", "EBET_KUMA"),
            EBET_QT_API => $this->getGameApiDetails(EBET_QT_API,"Ebet Qtech","EBET_QT","goto_ebet_qt_game", "EBET_QT"),
            YUNGU_GAME_API => $this->getGameApiDetails(YUNGU_GAME_API,"YUNGU GAME","YUNGU_GAME","goto_yungu_game", "YUNGU_GAME"),
            LEBO_GAME_API => $this->getGameApiDetails(LEBO_GAME_API,"LEBO GAME","LEBO_GAME","goto_lebo_game", "LEBO_GAME"),
            GSBBIN_API => $this->getGameApiDetails(GSBBIN_API,"GSBBIN","GSBBIN","goto_gsbbingame", "GSBBIN"),
            DG_API => $this->getGameApiDetails(DG_API,"Dream Game","DG","goto_common_game", "DG"),
            IPM_V2_SPORTS_API => $this->getGameApiDetails(IPM_V2_SPORTS_API,"IPM V2 SPORTS","IPM_V2_SPORTS","goto_ipm_v2_game", "IPM_V2_SPORTS"),
            XYZBLUE_API => $this->getGameApiDetails(XYZBLUE_API,"XYZBLUE","XYZBLUE","goto_xyzBlueMinigames", "XYZBLUE"),
            EBET_GGFISHING_API => $this->getGameApiDetails(EBET_GGFISHING_API,"EBET Global Gaming","EBET_GGFISHING","goto_ebetmwgfishing", "EBET_GGFISHING"),
            FINANCE_API => $this->getGameApiDetails(FINANCE_API,"FINANCE","FINANCE","goto_finance_game", "FINANCE"),
            LD_CASINO_API => $this->getGameApiDetails(LD_CASINO_API,"LD CASINO","LD_CASINO","goto_ld_casino_game", "LD_CASINO"),
            HG_API => $this->getGameApiDetails(HG_API,"HG","HG","goto_hg_game", "HG"),
            LD_LOTTERY_API => $this->getGameApiDetails(LD_LOTTERY_API,"LD LOTTERY","LD LOTTERY","goto_ct_lottery_game", "LD_LOTTERY"),
            EXTREME_LIVE_GAMING_API => $this->getGameApiDetails(EXTREME_LIVE_GAMING_API,"EXTREME LIVE GAMING","EXTREME_LIVE_GAMING","goto_extreme", "EXTREME_LIVE_GAMING"),
            EBET_AG_API => $this->getGameApiDetails(EBET_AG_API,"EBET AG","EBET_AG","goto_ebet_ag", "EBET_AG"),
            EBET_OPUS_API => $this->getGameApiDetails(EBET_OPUS_API,"EBET OPUS","EBET_OPUS","goto_ebet_opus", "EBET_OPUS"),
            EBET_DT_API => $this->getGameApiDetails(EBET_DT_API,"EBET DreamTech","EBET_DT","goto_ebet_dt_game", "EBET_DT"),
            IG_API => $this->getGameApiDetails(IG_API,"IG","IG","goto_ig_game", "IG"),
            GGPOKER_GAME_API => $this->getGameApiDetails(GGPOKER_GAME_API,"GGPOKER GAME","GGPOKER_GAME","goto_ggpoker", "GGPOKER_GAME"),
            GENESISM4_GAME_API => $this->getGameApiDetails(GENESISM4_GAME_API,"GENESISM4 GAME","GENESISM4_GAME","goto_genesism4", "GENESISM4_GAME"),
            SBTECH_API => $this->getGameApiDetails(SBTECH_API,"SBTECH","SBTECH","goto_common_game", "SBTECH"),
            ISB_SEAMLESS_API => $this->getGameApiDetails(ISB_SEAMLESS_API,"Isofbet SEAMLESS","ISB_SEAMLESS","goto_isbseamless_game", "ISB_SEAMLESS"),
            SUNCITY_API => $this->getGameApiDetails(SUNCITY_API,"Sun City","SUNCITY","goto_suncity", "SUNCITY"),
            T1SUNCITY_API => $this->getGameApiDetails(T1SUNCITY_API,"T1 Sun City","T1SUNCITY","goto_suncity", "T1SUNCITY"),
            MWG_API => $this->getGameApiDetails(MWG_API,"MWG","MWG","goto_mwg", "MWG"),
            RTG_API => $this->getGameApiDetails(RTG_API,"RTG","RTG","goto_rtg", "RTG"),
            RWB_API => $this->getGameApiDetails(RWB_API,"RWB","RWB","goto_rwb", "RWB"),
            FG_ENTAPLAY_API => $this->getGameApiDetails(FG_ENTAPLAY_API,"FG ENTAPLAY","FG_ENTAPLAY","goto_t1games", "FG_ENTAPLAY"),
            T1PT_API => $this->getGameApiDetails(T1PT_API,"T1 PlayTech","T1PT","goto_t1games", "T1PT"),
            T1YOPLAY_API => $this->getGameApiDetails(T1YOPLAY_API,"T1 YOPLAY","T1YOPLAY","goto_t1yoplaygame", "T1YOPLAY"),
            T1LOTTERY_API => $this->getGameApiDetails(T1LOTTERY_API,"T1 LOTTERY","T1LOTTERY","goto_t1lottery", "T1LOTTERY"),
            T1OG_API => $this->getGameApiDetails(T1OG_API,"T1 Oriental Gaming","T1OG","goto_t1games", "T1OG"),
            T1AB_API => $this->getGameApiDetails(T1AB_API,"T1 AllBet","T1AB","goto_t1games", "T1AB"),
            T1MG_API => $this->getGameApiDetails(T1MG_API,"T1 MicroGaming","T1MG","goto_t1games", "T1MG"),
            T1DG_API => $this->getGameApiDetails(T1DG_API,"T1 Dream Game","T1DG","goto_t1games", "T1DG"),
            T1VIVOGAMING_API => $this->getGameApiDetails(T1VIVOGAMING_API,"T1 VIVOGAMING","T1VIVOGAMING","goto_t1games/".T1VIVOGAMING_API, "T1VIVOGAMING"),
            T1OGPLUS_API => $this->getGameApiDetails(T1OGPLUS_API,"T1 OGPLUS","T1OGPLUS","goto_t1games/".T1OGPLUS_API, "T1OGPLUS_API"),
            T1PNG_API => $this->getGameApiDetails(T1PNG_API,"T1 Play N Go","T1PNG","goto_t1games", "T1PNG"),
            T1PRAGMATICPLAY_API => $this->getGameApiDetails(T1PRAGMATICPLAY_API,"T1 PragmayticPlay","T1PRAGMATICPLAY","goto_t1games", "T1PRAGMATICPLAY"),
            T1TTG_API => $this->getGameApiDetails(T1TTG_API,"T1 Top Trend Gaming","T1TTG","goto_t1games", "T1TTG"),
            T1AGIN_API => $this->getGameApiDetails(T1AGIN_API,"T1 AGIN","T1AGIN","goto_t1games", "T1AGIN"),
            T1EBET_API => $this->getGameApiDetails(T1EBET_API,"T1 EBET","T1EBET","goto_t1games", "T1EBET"),
            T1SPADE_GAMING_API => $this->getGameApiDetails(T1SPADE_GAMING_API,"T1 SPADE GAMING","T1SPADE_GAMING","goto_t1games", "T1SPADE_GAMING"),
            T1HB_API => $this->getGameApiDetails(T1HB_API,"T1 Habanero","T1HB","goto_t1games", "T1HB"),
            T1EZUGI_API => $this->getGameApiDetails(T1EZUGI_API,"T1 EZUGI","T1EZUGI","goto_t1games", "T1EZUGI"),
            T1JUMB_API => $this->getGameApiDetails(T1JUMB_API,"T1 JUMB","T1JUMB","goto_t1games", "T1JUMB"),
            T1VR_API => $this->getGameApiDetails(T1VR_API,"T1 Virtual Racing","T1VR","goto_t1games", "T1VR"),
            T1ISB_API => $this->getGameApiDetails(T1ISB_API,"T1 Isoftbet","T1ISB","goto_t1games", "T1ISB"),
            T1LOTTERY_EXT_API => $this->getGameApiDetails(T1LOTTERY_EXT_API,"T1 LOTTERY","T1LOTTERY_EXT","goto_t1games", "T1LOTTERY_EXT"),
            T1UC_API => $this->getGameApiDetails(T1UC_API,"T1 UC8","T1UC","goto_t1games", "T1UC"),
            T1DT_API => $this->getGameApiDetails(T1DT_API,"T1 DreamTech","T1DT","goto_t1games", "T1DT"),
            T1BBIN_API => $this->getGameApiDetails(T1BBIN_API,"T1BBIN","T1BBIN","goto_t1games", "T1BBIN"),
            T1IDN_API => $this->getGameApiDetails(T1IDN_API,"T1 IDN","T1IDN","goto_t1games", "T1IDN"),
            T1GD_API => $this->getGameApiDetails(T1GD_API,"T1 Gold Deluxe","T1GD","goto_t1games", "T1GD"),
            T1QT_API => $this->getGameApiDetails(T1QT_API,"T1 QTech","T1QT","goto_t1games", "T1QT"),
            T1GGPOKER_GAME_API => $this->getGameApiDetails(T1GGPOKER_GAME_API,"T1 GGPOKER GAME","T1GGPOKER_GAME","goto_t1games", "T1GGPOKER_GAME"),
            T1FG_ENTAPLAY_API => $this->getGameApiDetails(T1FG_ENTAPLAY_API,"T1 FG ENTAPLAY","T1FG_ENTAPLAY","goto_t1games", "T1FG_ENTAPLAY"),
            T1TFGAMING_ESPORTS_API => $this->getGameApiDetails(T1TFGAMING_ESPORTS_API,"T1 TFGAMING ESPORTS","T1TFGAMING_ESPORTS","goto_t1games", "T1TFGAMING_ESPORTS"),
            T1DG_API => $this->getGameApiDetails(T1DG_API,"T1 DG","T1DG","goto_t1games", "T1DG"),
            T1N2LIVE_API => $this->getGameApiDetails(T1N2LIVE_API,"T1 N2LIVE","T1N2LIVE","goto_t1games", "T1N2LIVE"),
            T1ONEWORKS_API => $this->getGameApiDetails(T1ONEWORKS_API,"T1 ONEWORKS","T1ONEWORKS","goto_t1games", "T1ONEWORKS"),
            T1GG_API => $this->getGameApiDetails(T1GG_API,"T1 Global Gaming","T1GG","goto_t1games", "T1GG"),
            T1EVOLUTION_API => $this->getGameApiDetails(T1EVOLUTION_API,"T1 Evolution Gaming","T1EVOLUTION","goto_t1games", "T1EVOLUTION"),
            T1KYCARD_API => $this->getGameApiDetails(T1KYCARD_API,"T1 KYCARD","T1KYCARD","goto_t1games" , "T1KYCARD"),
            T1SA_GAMING_API => $this->getGameApiDetails(T1SA_GAMING_API,"SA Gaming","T1SA_GAMING","goto_t1games/" . T1SA_GAMING_API, "T1SA_GAMING"),
            T1LE_GAMING_API => $this->getGameApiDetails(T1LE_GAMING_API,"T1 LE Gaming","T1LEGAMING","goto_t1games", "T1LEGAMING"),
            T1YL_NTTECH_GAME_API => $this->getGameApiDetails(T1YL_NTTECH_GAME_API,"T1 YL NTTECH","T1YLNTTECH","goto_t1games", "T1YLNTTECH"),
            T1AB_V2_API => $this->getGameApiDetails(T1AB_V2_API,"T1 AB V2","T1AB_V2","goto_t1games", "T1AB_V2"),
            T1AE_SLOTS_API => $this->getGameApiDetails(T1AE_SLOTS_API,"T1 AE SLOTS","T1AE_SLOTS_API","goto_t1games", "T1AE_SLOTS_API"),
            T1NTTECH_V2_API => $this->getGameApiDetails(T1NTTECH_V2_API,"T1 NTTECH V2","T1NTTECH_V2_API","goto_t1games", "T1NTTECH_V2_API"),
            T1MTECHBBIN_API => $this->getGameApiDetails(T1MTECHBBIN_API,"T1 MTECH BBIN","T1MTECHBBIN_API","goto_t1games", "T1MTECHBBIN_API"),
            T1SPORTSBOOK_FLASH_TECH_GAME_API => $this->getGameApiDetails(T1SPORTSBOOK_FLASH_TECH_GAME_API, "T1 SPORTSBOOK FLASH TECH", "T1SPORTSBOOK_FLASH_TECH_GAME_API", "goto_t1games/" . T1SPORTSBOOK_FLASH_TECH_GAME_API, "T1SPORTSBOOK_FLASH_TECH_GAME_API"),
            ONE88_API => $this->getGameApiDetails(ONE88_API,"ONE88","ONE88","goto_", "ONE88"),
            T1YGGDRASIL_API => $this->getGameApiDetails(T1YGGDRASIL_API,"T1 YGGDRASIL","T1YGGDRASIL","goto_t1games", "T1YGGDRASIL"),
            GAMEPLAY_API => $this->getGameApiDetails(GAMEPLAY_API,"GAMEPLAY","GAMEPLAY","goto_gpgame", "GAMEPLAY"),
            BBTECHGSPOT_API => $this->getGameApiDetails(BBTECHGSPOT_API,"BBTECHGSPOT","BBTECHGSPOT","goto_", "BBTECHGSPOT"),
            GSKENO_API => $this->getGameApiDetails(GSKENO_API,"GSKENO","GSKENO","goto_", "GSKENO"),
            CROWN_API => $this->getGameApiDetails(CROWN_API,"CROWN","CROWN","goto_", "CROWN"),
            VIVO_API => $this->getGameApiDetails(VIVO_API,"VIVO","VIVO","goto_", "VIVO"),
            AGHG_API => $this->getGameApiDetails(AGHG_API,"AGHG","AGHG","goto_", "AGHG"),
            AGPT_API => $this->getGameApiDetails(AGPT_API,"AGPT","AGPT","goto_", "AGPT"),
            SEVEN77_API => $this->getGameApiDetails(SEVEN77_API,"SEVEN77","SEVEN77","goto_", "SEVEN77"),
            GSMG_API => $this->getGameApiDetails(GSMG_API,"GSMG","GSMG","goto_", "GSMG"),
            AGENCY_API => $this->getGameApiDetails(AGENCY_API,"AGENCY","AGENCY","goto_", "AGENCY"),
            EBET2_API => $this->getGameApiDetails(EBET2_API,"EBET2","EBET2","goto_", "EBET2"),
            PLAYSTAR_API => $this->getGameApiDetails(PLAYSTAR_API,"PLAYSTAR","PLAYSTAR","goto_common_game/".PLAYSTAR_API, "PLAYSTAR"),
            SBTECH_GAMING_API => $this->getGameApiDetails(SBTECH_GAMING_API,"SBTECH Gaming","SBTECH_GAMING","goto_", "SBTECH_GAMING"),
            LADDER_GAMING_API => $this->getGameApiDetails(LADDER_GAMING_API,"LADDER Gaming","LADDER_GAMING","goto_", "LADDER_GAMING"),
            BETMASTER_API => $this->getGameApiDetails(BETMASTER_API,"BETMASTER","BETMASTER","goto_", "BETMASTER"),
            PT_KRW_API => $this->getGameApiDetails(PT_KRW_API,"PT KRW","PT_KRW","goto_", "PT_KRW"),
            TCG_API => $this->getGameApiDetails(TCG_API,"TCG","TCG","goto_common_game", "TCG"),
            GOLDENF_PGSOFT_API => $this->getGameApiDetails(GOLDENF_PGSOFT_API,"GOLDENF PGSOFT","GOLDENF_PGSOFT","goto_common_game", "GOLDENF_PGSOFT"),
            MG_DASHUR_API => $this->getGameApiDetails(MG_DASHUR_API,"MG DASHUR","MG_DASHUR","goto_common_game", "MG_DASHUR"),
            KYCARD_API => $this->getGameApiDetails(KYCARD_API,"KYCARD","KYCARD","goto_common_game", "KYCARD"),
            LE_GAMING_API => $this->getGameApiDetails(LE_GAMING_API,"LE GAMING","LE_GAMING","goto_common_game", "LE_GAMING"),
            BETSOFT_API => $this->getGameApiDetails(BETSOFT_API,"BETSOFT","BETSOFT","goto_betsoft_game", "BETSOFT"),
            CQ9_API => $this->getGameApiDetails(CQ9_API,"CQ9","CQ9","goto_common_game", "CQ9"),
            YUXING_CQ9_GAME_API => $this->getGameApiDetails(YUXING_CQ9_GAME_API,"YUXING_CQ9","YUXING_CQ9","goto_yuxing", "YUXING_CQ9"),
            MTECH_BBIN_API => $this->getGameApiDetails(MTECH_BBIN_API,"MTECH BBIN","MTECH_BBIN","goto_common_game/".MTECH_BBIN_API, "MTECH_BBIN"),
            T1CQ9_API => $this->getGameApiDetails(T1CQ9_API,"T1 CQ9","T1CQ9","goto_t1games", "T1CQ9"),
            REDTIGER_API => $this->getGameApiDetails(REDTIGER_API,"REDTIGER","REDTIGER","gotogame", "REDTIGER"),
            HOGAMING_API => $this->getGameApiDetails(HOGAMING_API,"HO GAMING","HOGAMING","goto_hggame", "HOGAMING"),
            T1HOGAMING_API => $this->getGameApiDetails(T1HOGAMING_API,"T1HOGAMING","T1HOGAMING","goto_t1games", "T1HOGAMING"),
            MGPLUS_API => $this->getGameApiDetails(MGPLUS_API,"MG PLUS","MGPLUS","goto_common_game", "MGPLUS"),
            PGSOFT_API => $this->getGameApiDetails(PGSOFT_API,"PG SOFT","PGSOFT","goto_common_game", "PGSOFT"),
            PGSOFT_SEAMLESS_API => $this->getGameApiDetails(PGSOFT_SEAMLESS_API,"PGSOFT_SEAMLESS_API","PGSOFT_SEAMLESS_API","goto_common_game/".PGSOFT_SEAMLESS_API, "PGSOFT_SEAMLESS_API"),
            PGSOFT2_SEAMLESS_API => $this->getGameApiDetails(PGSOFT2_SEAMLESS_API,"PGSOFT2_SEAMLESS_API","PGSOFT2_SEAMLESS_API","goto_common_game/".PGSOFT2_SEAMLESS_API, "PGSOFT2_SEAMLESS_API"),
            PGSOFT3_SEAMLESS_API => $this->getGameApiDetails(PGSOFT3_SEAMLESS_API,"PGSOFT3_SEAMLESS_API","PGSOFT3_SEAMLESS_API","goto_common_game/".PGSOFT3_SEAMLESS_API, "PGSOFT3_SEAMLESS_API"),
            T1MGPLUS_API => $this->getGameApiDetails(T1MGPLUS_API,"T1 MG PLUS","T1MGPLUS","goto_t1games", "T1MGPLUS"),
            ISIN4D_API => $this->getGameApiDetails(ISIN4D_API,"ISIN4D","ISIN4D","gotogame/".ISIN4D_API, "ISIN4D"),
            ISIN4D_IDR_B1_API => $this->getGameApiDetails(ISIN4D_IDR_B1_API,"ISIN4D","ISIN4D","goto_isin4d/".ISIN4D_IDR_B1_API, "ISIN4D_IDR_B1"),
            QQKENO_QQLOTTERY_API => $this->getGameApiDetails(QQKENO_QQLOTTERY_API,"QQKENOQQLOTTERY","QQKENO_QQLOTTERY","goto_qqkenolottery/".QQKENO_QQLOTTERY_API, "QQKENO_QQLOTTERY"),
            QQKENO_QQLOTTERY_THB_B1_API => $this->getGameApiDetails(QQKENO_QQLOTTERY_THB_B1_API,"QQKENOQQLOTTERY THB","QQKENO_QQLOTTERY_THB_B1","goto_qqkenolottery/".QQKENO_QQLOTTERY_THB_B1_API, "QQKENO_QQLOTTERY_THB_B1"),
            NTTECH_API => $this->getGameApiDetails(NTTECH_API,"NT Sexy Live","NTTECH","goto_nttech_game/".NTTECH_API, "NTTECH"),
            NTTECH_IDR_B1_API => $this->getGameApiDetails(NTTECH_IDR_B1_API,"NT Sexy Live","NTTECH","goto_nttech_game/".NTTECH_IDR_B1_API, "NTTECH_IDR_B1"),
            NTTECH_CNY_B1_API => $this->getGameApiDetails(NTTECH_CNY_B1_API,"NT Sexy Live","NTTECH","goto_nttech_game/".NTTECH_CNY_B1_API, "NTTECH_CNY_B1"),
            NTTECH_THB_B1_API => $this->getGameApiDetails(NTTECH_THB_B1_API,"NT Sexy Live","NTTECH","goto_nttech_game/".NTTECH_THB_B1_API, "NTTECH_THB_B1"),
            NTTECH_V2_API => $this->getGameApiDetails(NTTECH_V2_API,"NT Sexy Live","NTTECH","goto_common_game/".NTTECH_V2_API, "NTTECH"),
            NTTECH_V2_IDR_B1_API => $this->getGameApiDetails(NTTECH_V2_IDR_B1_API,"NT Sexy Live","NTTECH","goto_common_game/".NTTECH_V2_IDR_B1_API, "NTTECH_V2_IDR_B1_API"),
            NTTECH_V2_CNY_B1_API => $this->getGameApiDetails(NTTECH_V2_CNY_B1_API,"NT Sexy Live","NTTECH","goto_common_game/".NTTECH_V2_CNY_B1_API, "NTTECH_V2_CNY_B1_API"),
            T1NTTECH_V2_CNY_B1_API => $this->getGameApiDetails(T1NTTECH_V2_CNY_B1_API,"T1 Sexy Baccarat CNY","T1 NTTECHV2CNY","goto_t1games/".T1NTTECH_V2_CNY_B1_API, "T1 NTTECHV2CNY"),
            NTTECH_V2_INR_B1_API => $this->getGameApiDetails(NTTECH_V2_INR_B1_API,"NT Sexy Live","NTTECH","goto_common_game/".NTTECH_V2_INR_B1_API, "NTTECH_V2_INR_B1_API"),
            NTTECH_V2_THB_B1_API => $this->getGameApiDetails(NTTECH_V2_THB_B1_API,"NT Sexy Live","NTTECH","goto_common_game/".NTTECH_V2_THB_B1_API, "NTTECH_V2_THB_B1_API"),
            AE_SLOTS_GAMING_API => $this->getGameApiDetails(AE_SLOTS_GAMING_API,"AE SLOTS","AE_SLOTS_GAMING","goto_common_game/".AE_SLOTS_GAMING_API, "AE_SLOTS_GAMING"),
            REDRAKE_GAMING_API => $this->getGameApiDetails(REDRAKE_GAMING_API,"Red Rake","REDRAKE_GAMING_API","goto_common_game/".REDRAKE_GAMING_API, "REDRAKE_GAMING_API"),
            BAISON_GAME_API => $this->getGameApiDetails(BAISON_GAME_API,"Baison Game","BAISON","goto_common_game/".BAISON_GAME_API, "BAISON_GAME"),
            BOOMING_SEAMLESS_API => $this->getGameApiDetails(BOOMING_SEAMLESS_API,"BOOMING API","BOOMING API","goto_booming", "BOOMING_SEAMLESS"),
            AVIA_ESPORT_API => $this->getGameApiDetails(AVIA_ESPORT_API,"AVIA ESPORTS","AVIAESPORTS","goto_aviasport", "AVIA_ESPORT"),
            OG_V2_API => $this->getGameApiDetails(OG_V2_API,"OG V2","OGV2","goto_oggame_v2", "OG_V2"),
            IBC_24TECH_API => $this->getGameApiDetails(IBC_24TECH_API,"IBC 24TECH","IBC_24TECH","goto_24tech_game/".IBC_24TECH_API, "IBC_24TECH"),
            AFB88_API => $this->getGameApiDetails(AFB88_API,"AFB88","AFB88","goto_common_game/".AFB88_API, "AFB88"),
            DONGSEN_ESPORTS_API => $this->getGameApiDetails(DONGSEN_ESPORTS_API,"DONGSEN ESPORTS","DS ESPORTS","goto_common_game/".DONGSEN_ESPORTS_API, "DONGSEN_ESPORTS"),
            DONGSEN_LOTTERY_API => $this->getGameApiDetails(DONGSEN_LOTTERY_API,"DONGSEN LOTTERY","DS LOTTERY","goto_common_game/".DONGSEN_LOTTERY_API, "DONGSEN_LOTTERY"),
            YGGDRASIL_API => $this->getGameApiDetails(YGGDRASIL_API,"YGGDRASIL","YGGDRASIL","goto_common_game/".YGGDRASIL_API, "YGGDRASIL"),
            YGG_SEAMLESS_GAME_API => $this->getGameApiDetails(YGG_SEAMLESS_GAME_API,"YGG_SEAMLESS","YGG_SEAMLESS","goto_common_game/".YGG_SEAMLESS_GAME_API, "YGG_SEAMLESS"),
            PT_V2_API => $this->getGameApiDetails(PT_V2_API,"PlayTech","Playtech","goto_common_game", "PT V2"),
            SOLID_GAMING_THB_API => $this->getGameApiDetails(SOLID_GAMING_THB_API,"SOLID GAMING","SOLID GAMING","goto_common_game/", "SOLID_GAMING"),
            VIVOGAMING_API => $this->getGameApiDetails(VIVOGAMING_API,"VIVO GAMING","VIVOGAMING_API","goto_common_game/".VIVOGAMING_API, "VIVOGAMING_API"),
            VIVOGAMING_IDR_B1_API => $this->getGameApiDetails(VIVOGAMING_IDR_B1_API,"VIVOGAMING_IDR_B1_API","VIVOGAMING_IDR_B1_API","goto_common_game/".VIVOGAMING_IDR_B1_API, "VIVOGAMING_IDR_B1_API"),
            VIVOGAMING_CNY_B1_API => $this->getGameApiDetails(VIVOGAMING_CNY_B1_API,"VIVOGAMING_CNY_B1_API","VIVOGAMING_CNY_B1_API","goto_common_game/".VIVOGAMING_CNY_B1_API, "VIVOGAMING_CNY_B1_API"),
            VIVOGAMING_THB_B1_API => $this->getGameApiDetails(VIVOGAMING_THB_B1_API,"VIVOGAMING_THB_B1_API","VIVOGAMING_THB_B1_API","goto_common_game/".VIVOGAMING_THB_B1_API, "VIVOGAMING_THB_B1_API"),
            VIVOGAMING_USD_B1_API => $this->getGameApiDetails(VIVOGAMING_USD_B1_API,"VIVOGAMING_USD_B1_API","VIVOGAMING_USD_B1_API","goto_common_game/".VIVOGAMING_USD_B1_API, "VIVOGAMING_USD_B1_API"),
            VIVOGAMING_VND_B1_API => $this->getGameApiDetails(VIVOGAMING_VND_B1_API,"VIVOGAMING_VND_B1_API","VIVOGAMING_VND_B1_API","goto_common_game/".VIVOGAMING_VND_B1_API, "VIVOGAMING_VND_B1_API"),
            VIVOGAMING_MYR_B1_API => $this->getGameApiDetails(VIVOGAMING_MYR_B1_API,"VIVOGAMING_MYR_B1_API","VIVOGAMING_MYR_B1_API","goto_common_game/".VIVOGAMING_MYR_B1_API, "VIVOGAMING_MYR_B1_API"),
            VIVOGAMING_IDR_B1_ALADIN_API => $this->getGameApiDetails(VIVOGAMING_IDR_B1_ALADIN_API,"VIVOGAMING_IDR_B1_ALADIN_API","VIVOGAMING_IDR_B1_ALADIN_API","goto_common_game/".VIVOGAMING_IDR_B1_ALADIN_API, "VIVOGAMING_IDR_B1_ALADIN_API"),
            VIVOGAMING_SEAMLESS_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_API,"VIVOGAMING_SEAMLESS_API","VIVOGAMING_SEAMLESS_API","goto_common_game/".VIVOGAMING_SEAMLESS_API, "VIVOGAMING_SEAMLESS_API"),
            VIVOGAMING_SEAMLESS_IDR1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_IDR1_API,"VIVOGAMING_SEAMLESS_IDR1_API","VIVOGAMING_SEAMLESS_IDR1_API","goto_common_game/".VIVOGAMING_SEAMLESS_IDR1_API, "VIVOGAMING_SEAMLESS_IDR1_API"),
            VIVOGAMING_SEAMLESS_CNY1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_CNY1_API,"VIVOGAMING_SEAMLESS_CNY1_API","VIVOGAMING_SEAMLESS_CNY1_API","goto_common_game/".VIVOGAMING_SEAMLESS_CNY1_API, "VIVOGAMING_SEAMLESS_CNY1_API"),
            VIVOGAMING_SEAMLESS_THB1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_THB1_API,"VIVOGAMING_SEAMLESS_THB1_API","VIVOGAMING_SEAMLESS_THB1_API","goto_common_game/".VIVOGAMING_SEAMLESS_THB1_API, "VIVOGAMING_SEAMLESS_THB1_API"),
            VIVOGAMING_SEAMLESS_USD1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_USD1_API,"VIVOGAMING_SEAMLESS_USD1_API","VIVOGAMING_SEAMLESS_USD1_API","goto_common_game/".VIVOGAMING_SEAMLESS_USD1_API, "VIVOGAMING_SEAMLESS_USD1_API"),
            VIVOGAMING_SEAMLESS_VND1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_VND1_API,"VIVOGAMING_SEAMLESS_VND1_API","VIVOGAMING_SEAMLESS_VND1_API","goto_common_game/".VIVOGAMING_SEAMLESS_VND1_API, "VIVOGAMING_SEAMLESS_VND1_API"),
            VIVOGAMING_SEAMLESS_MYR1_API => $this->getGameApiDetails(VIVOGAMING_SEAMLESS_MYR1_API,"VIVOGAMING_SEAMLESS_MYR1_API","VIVOGAMING_SEAMLESS_MYR1_API","goto_common_game/".VIVOGAMING_SEAMLESS_MYR1_API, "VIVOGAMING_SEAMLESS_MYR1_API"),
            EVOLUTION_SEAMLESS_THB1_API => $this->getGameApiDetails(EVOLUTION_SEAMLESS_THB1_API,"EVOLUTION SEAMLESS GAMING","EVOLUTION_SEAMLESS_THB1_API","goto_common_game/".EVOLUTION_SEAMLESS_THB1_API, "EVOLUTION_SEAMLESS_THB1_API"),
            EVOLUTION_SEAMLESS_GAMING_API => $this->getGameApiDetails(EVOLUTION_SEAMLESS_GAMING_API,"EVOLUTION SEAMLESS GAMING","EVOLUTION_SEAMLESS_GAMING_API","goto_common_game/".EVOLUTION_SEAMLESS_GAMING_API, "EVOLUTION_SEAMLESS_GAMING_API"),
            
            EVOLUTION_NETENT_SEAMLESS_GAMING_API => $this->getGameApiDetails(EVOLUTION_NETENT_SEAMLESS_GAMING_API,"EVOLUTION NETENT SEAMLESS GAMING","EVOLUTION_NETENT_SEAMLESS_GAMING_API","goto_common_game/".EVOLUTION_NETENT_SEAMLESS_GAMING_API, "EVOLUTION_NETENT_SEAMLESS_GAMING_API"),
            EVOLUTION_NLC_SEAMLESS_GAMING_API => $this->getGameApiDetails(EVOLUTION_NLC_SEAMLESS_GAMING_API,"EVOLUTION NLC SEAMLESS GAMING","EVOLUTION_NLC_SEAMLESS_GAMING_API","goto_common_game/".EVOLUTION_NLC_SEAMLESS_GAMING_API, "EVOLUTION_NLC_SEAMLESS_GAMING_API"),
            EVOLUTION_REDTIGER_SEAMLESS_GAMING_API => $this->getGameApiDetails(EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,"EVOLUTION REDTIGER SEAMLESS GAMING","EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","goto_common_game/".EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, "EVOLUTION_REDTIGER_SEAMLESS_GAMING_API"),
            EVOLUTION_BTG_SEAMLESS_GAMING_API => $this->getGameApiDetails(EVOLUTION_BTG_SEAMLESS_GAMING_API,"EVOLUTION BTG SEAMLESS GAMING","EVOLUTION_BTG_SEAMLESS_GAMING_API","goto_common_game/".EVOLUTION_BTG_SEAMLESS_GAMING_API, "EVOLUTION_BTG_SEAMLESS_GAMING_API"),

            T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API,"T1 VOLUTION NETENT SEAMLESS GAMING","T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API","goto_t1games/".T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API, "T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API"),
            T1_EVOLUTION_NLC_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_EVOLUTION_NLC_SEAMLESS_GAMING_API,"T1 VOLUTION NLC SEAMLESS GAMING","T1_EVOLUTION_NLC_SEAMLESS_GAMING_API","goto_t1games/".T1_EVOLUTION_NLC_SEAMLESS_GAMING_API, "T1_EVOLUTION_NLC_SEAMLESS_GAMING_API"),
            T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,"T1 EVOLUTION REDTIGER SEAMLESS GAMING","T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","goto_t1games/".T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, "T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API"),
            T1_EVOLUTION_BTG_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_EVOLUTION_BTG_SEAMLESS_GAMING_API,"T1 VOLUTION BTG SEAMLESS GAMING","T1_EVOLUTION_BTG_SEAMLESS_GAMING_API","goto_t1games/".T1_EVOLUTION_BTG_SEAMLESS_GAMING_API, "T1_EVOLUTION_BTG_SEAMLESS_GAMING_API"),
       
            T1_EVOLUTION_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EVOLUTION_SEAMLESS_GAME_API,"T1 EVOLUTION SEAMLESS GAMING","T1_EVOLUTION_SEAMLESS_GAME_API","goto_t1games/".T1_EVOLUTION_SEAMLESS_GAME_API, "T1_EVOLUTION_SEAMLESS_GAME_API"),
            REDTIGER_SEAMLESS_API => $this->getGameApiDetails(REDTIGER_SEAMLESS_API,"REDTIGER SEAMLESS","REDTIGER SEAMLESS","goto_redtigerseamless_game", "REDTIGER_SEAMLESS"),
            RG_API => $this->getGameApiDetails(RG_API,"Ray Gaming","RG","goto_rg_game", "RG"),
            ONEBOOK_API => $this->getGameApiDetails(ONEBOOK_API,"ONEBOOK","ONEBOOK SPORTSBOOK","goto_common_game/".ONEBOOK_API, "ONEBOOK_API"),
            ONEBOOK_THB_B1_API => $this->getGameApiDetails(ONEBOOK_THB_B1_API,"ONEBOOK","ONEBOOK SPORTSBOOK","goto_onebook_game/".ONEBOOK_THB_B1_API, "ONEBOOK_THB_B1_API"),
            DG_SEAMLESS_API => $this->getGameApiDetails(DG_SEAMLESS_API,"Dream Game Seamless","DG","goto_dggame_seamless", "DG"),
            LUCKY_GAME_CHESS_POKER_API => $this->getGameApiDetails(LUCKY_GAME_CHESS_POKER_API,"Lucky Game Chess Poker","LUCKY GAME","goto_common_game/".LUCKY_GAME_CHESS_POKER_API, "LUCKY_GAME_CHESS_POKER_API"),
            FLOW_GAMING_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_SEAMLESS_API,"FLOW GAMING SEAMLESS API","FLOW GAMING SEAMLESS API","goto_common_game/".FLOW_GAMING_SEAMLESS_API, "FLOW_GAMING_SEAMLESS"),
            PRAGMATICPLAY_SEAMLESS_THB1_API => $this->getGameApiDetails(PRAGMATICPLAY_SEAMLESS_THB1_API,"PragmaticPlay","PRAGMATICPLAY","goto_common_game/", "PRAGMATICPLAY"),
            SBOBETGAME_API => $this->getGameApiDetails(SBOBETGAME_API,"SBOBETGAME","SBOBETGAME SPORTSBOOK","goto_sbobetgame/".SBOBETGAME_API, "SBOBETGAME_API"),
            SBOBETGAME_THB_B1_API => $this->getGameApiDetails(SBOBETGAME_THB_B1_API,"SBOBETGAME","SBOBETGAME SPORTSBOOK","goto_sbobetgame/".SBOBETGAME_THB_B1_API, "SBOBETGAME_THB_B1_API"),
            // SBOBETV2_GAME_API => $this->getGameApiDetails(SBOBETV2_GAME_API,"SBOBETGAME","SBOBETGAME SPORTSBOOK","goto_sbobetgame/".SBOBETV2_GAME_API, "SBOBETV2_GAME_API"),
            SBOBETV2_GAME_API => $this->getGameApiDetails(SBOBETV2_GAME_API, "SBOBETV2_GAME_API", "SBOBETV2_GAME_API", "goto_common_game/" . SBOBETV2_GAME_API, "SBOBETV2_GAME_API"),
            ASIASTAR_API => $this->getGameApiDetails(ASIASTAR_API,"ASIASTAR","ASIASTAR_API","goto_common_game/".ASIASTAR_API, "ASIASTAR_API"),
            GOLDEN_RACE_GAMING_API => $this->getGameApiDetails(GOLDEN_RACE_GAMING_API,"GOLDEN RACE GAMING","GOLDEN RACE GAMING","goto_common_game", "GOLDEN RACE GAMING"),
            HABANERO_SEAMLESS_GAMING_API => $this->getGameApiDetails(HABANERO_SEAMLESS_GAMING_API,"HABANERO","HABANERO","goto_common_game/".HABANERO_SEAMLESS_GAMING_API, "HABANERO"),
            T1_HABANERO_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_HABANERO_SEAMLESS_GAME_API,"HABANERO","HABANERO","goto_t1games/".T1_HABANERO_SEAMLESS_GAME_API, "T1_HABANERO_SEAMLESS_API"),
            HABANERO_SEAMLESS_GAMING_IDR1_API => $this->getGameApiDetails(HABANERO_SEAMLESS_GAMING_IDR1_API,"HABANERO_SEAMLESS_GAMING_IDR1_API","HABANERO_SEAMLESS_GAMING_IDR1_API","goto_common_game/".HABANERO_SEAMLESS_GAMING_IDR1_API, "HABANERO_SEAMLESS_GAMING_IDR1_API"),
            FLOW_GAMING_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_SEAMLESS_THB1_API,"FLOW GAMING SEAMLESS API","FLOW GAMING SEAMLESS API","goto_common_game/".FLOW_GAMING_SEAMLESS_THB1_API, "FLOW_GAMING_SEAMLESS"),
            TPG_API => $this->getGameApiDetails(TPG_API,"TPG","TPG","goto_common_game/".TPG_API."/", "TPG_API"),
            SLOT_FACTORY_SEAMLESS_API => $this->getGameApiDetails(SLOT_FACTORY_SEAMLESS_API,"Slot Factory Seamless API", "SLOT_FACTORY_SEAMLESS_API", "goto_common_game", "SLOT_FACTORY_SEAMLESS_API"),
            TFGAMING_ESPORTS_API => $this->getGameApiDetails(TFGAMING_ESPORTS_API,"TFGaming Esports", "TFGAMING_ESPORTS_API", "goto_common_game", "TFGAMING_ESPORTS_API"),
            GD_SEAMLESS_API => $this->getGameApiDetails(GD_SEAMLESS_API,"Gold Deluxe", "GD_SEAMLESS_API", "goto_common_game", "GD_SEAMLESS_API"),
            GENESIS_SEAMLESS_API => $this->getGameApiDetails(GENESIS_SEAMLESS_API,"Genesis Seamless","Genesis Seamless","goto_common_game/".GENESIS_SEAMLESS_API, "GENESIS_SEAMLESS_API"),
            GENESIS_SEAMLESS_THB1_API => $this->getGameApiDetails(GENESIS_SEAMLESS_THB1_API,"Genesis Seamless","Genesis Seamless","goto_common_game/".GENESIS_SEAMLESS_THB1_API, "GENESIS_SEAMLESS_THB1_API"),
            LB_API => $this->getGameApiDetails(LB_API,"LB Keno","LB Keno","goto_common_game/".LB_API, "LB_API"),
            HOGAMING_SEAMLESS_API => $this->getGameApiDetails(HOGAMING_SEAMLESS_API,"HOGAMING SEAMLESS API","HOGAMING SEAMLESS API","goto_hgseamless_game/".HOGAMING_SEAMLESS_API, "HOGAMING_SEAMLESS"),
            KING_MAKER_GAMING_API => $this->getGameApiDetails(KING_MAKER_GAMING_API,"King Maker","KING_MAKER_GAMING_API","goto_kingmaker_game/".KING_MAKER_GAMING_API, "KING_MAKER_GAMING_API"),
            KING_MAKER_GAMING_THB_B1_API => $this->getGameApiDetails(KING_MAKER_GAMING_THB_B1_API,"King Maker","KING_MAKER_GAMING_THB_B1_API","goto_kingmaker_game/".KING_MAKER_GAMING_THB_B1_API, "KING_MAKER_GAMING_THB_B1_API"),
            KING_MAKER_GAMING_THB_B2_API => $this->getGameApiDetails(KING_MAKER_GAMING_THB_B2_API,"King Maker","KING_MAKER_GAMING_THB_B2_API","goto_sv388_game/".KING_MAKER_GAMING_THB_B2_API, "KING_MAKER_GAMING_THB_B2_API"),
            TIANHONG_MINI_GAMES_API => $this->getGameApiDetails(TIANHONG_MINI_GAMES_API,"TianHong Mini Games","TIANHONG_MINI_GAMES_API","goto_common_game/".TIANHONG_MINI_GAMES_API, "TIANHONG_MINI_GAMES_API"),
            OGPLUS_API => $this->getGameApiDetails(OGPLUS_API,"OGPLUS","OGPLUS","goto_common_game/".OGPLUS_API, "OGPLUS_API"),
            HUB88_API => $this->getGameApiDetails(HUB88_API,"HUB88","HUB88","goto_hub88", "HUB88_API"),
            KG_POKER_API => $this->getGameApiDetails(KG_POKER_API, "KG Poker", "KG_POKER_API", "goto_common_game", "KG_POKER_API"),
            AG_SEAMLESS_GAME_API => $this->getGameApiDetails(AG_SEAMLESS_GAME_API, "AG_SEAMLESS_GAME_API", "AG_SEAMLESS_GAME_API", "goto_common_game/" . AG_SEAMLESS_GAME_API, "AG_SEAMLESS_GAME_API"),
            AG_SEAMLESS_THB1_API => $this->getGameApiDetails(AG_SEAMLESS_THB1_API, "AG SEAMLESS", "AG_SEAMLESS_THB1_API", "goto_common_game/".AG_SEAMLESS_THB1_API, "AG_SEAMLESS_THB1_API"),
            LUCKY_STREAK_SEAMLESS_GAME_API => $this->getGameApiDetails(LUCKY_STREAK_SEAMLESS_GAME_API, "LUCKY STREAK SEAMLESS", "LUCKY_STREAK_SEAMLESS_GAME_API", "goto_common_game/".LUCKY_STREAK_SEAMLESS_GAME_API, "LUCKY_STREAK_SEAMLESS_GAME_API"),
            LUCKY_STREAK_SEAMLESS_THB1_API => $this->getGameApiDetails(LUCKY_STREAK_SEAMLESS_THB1_API, "LUCKY STREAK SEAMLESS", "LUCKY_STREAK_SEAMLESS_THB1_API", "goto_common_game/".LUCKY_STREAK_SEAMLESS_THB1_API, "LUCKY_STREAK_SEAMLESS_THB1_API"),
            GPK_API => $this->getGameApiDetails(GPK_API, "TP", "TP_API", "goto_common_game", "TP_API"),
            JOKER_API => $this->getGameApiDetails(JOKER_API, "JOKER", "JOKER_API", "goto_common_game/".JOKER_API, "JOKER_API"),
            RGS_API => $this->getGameApiDetails(RGS_API, "RGS", "RGS_API", "goto_common_game/".RGS_API, "RGS_API"),
            TGP_AG_API => $this->getGameApiDetails(TGP_AG_API,"TGP AG API","TGP AG API","goto_common_game/".TGP_AG_API, "TGP_AG"),
            ICONIC_SEAMLESS_API => $this->getGameApiDetails(ICONIC_SEAMLESS_API,"ASTRO TECH_SEAMLESS_API","ASTRO TECH_SEAMLESS_API","goto_common_game/".ICONIC_SEAMLESS_API, "ASTRO TECH_SEAMLESS_API"),
            SBTECH_BTI_API => $this->getGameApiDetails(SBTECH_BTI_API,"SBTECH BTI","SBTECH BTI","goto_sbtech_bti_game", "SBTECH_BTI_API"),
            T1SBTECH_BTI_API => $this->getGameApiDetails(T1SBTECH_BTI_API,"SBTECH BTI","SBTECH BTI","goto_sbtech_bti_game", "T1SBTECH_BTI_API"),
            WM_API => $this->getGameApiDetails(WM_API,"WM","WM_API","goto_common_game/".WM_API, "WM_API"),
            WM2_SEAMLESS_GAME_API => $this->getGameApiDetails(WM2_SEAMLESS_GAME_API,"WM2_SEAMLESS_GAME_API","WM2_SEAMLESS_GAME_API","goto_common_game/".WM2_SEAMLESS_GAME_API, "WM2_SEAMLESS_GAME_API"),
            WM_SEAMLESS_GAME_API => $this->getGameApiDetails(WM_SEAMLESS_GAME_API,"WM_SEAMLESS_GAME_API","WM_SEAMLESS_GAME_API","goto_common_game/".WM_SEAMLESS_GAME_API, "WM_SEAMLESS_GAME_API"),
            T1_WM2_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_WM2_SEAMLESS_GAME_API,"T1_WM2_SEAMLESS_GAME_API","T1_WM2_SEAMLESS_GAME_API","goto_t1games/".T1_WM2_SEAMLESS_GAME_API, "T1_WM2_SEAMLESS_GAME_API"),
            T1_WM_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_WM_SEAMLESS_GAME_API,"T1_WM_SEAMLESS_GAME_API","T1_WM_SEAMLESS_GAME_API","goto_t1games/".T1_WM_SEAMLESS_GAME_API, "T1_WM_SEAMLESS_GAME_API"),

            T1WM_API => $this->getGameApiDetails(T1WM_API,"WM","T1WM_API","goto_t1games/".T1WM_API, "T1WM_API"),
            AMG_API => $this->getGameApiDetails(AMG_API, "AMG API", "AMG API", "goto_common_game/".AMG_API, "AMG"),
            TIANHAO_API => $this->getGameApiDetails(TIANHAO_API,"TIANHAO API","TIANHAO_API","goto_tianhao", "TIANHAO_API"),
            TIANHAO_API => $this->getGameApiDetails(TIANHAO_API,"TIANHAO API","TIANHAO_API","goto_tianhao", "TIANHAO_API"),
            IMESB_API => $this->getGameApiDetails(IMESB_API,"IMESB API","IMESB_API","goto_common_game/".IMESB_API, "IMESB_API"),
            ICONIC_SEAMLESS_API => $this->getGameApiDetails(ICONIC_SEAMLESS_API,"ASTRO TECH_SEAMLESS_API","ASTRO TECH_SEAMLESS_API","goto_common_game/".ICONIC_SEAMLESS_API, "ASTRO TECH_SEAMLESS_API"),
            N2LIVE_API => $this->getGameApiDetails(N2LIVE_API,"N2LIVE_API","N2LIVE_API","goto_common_game/".N2LIVE_API, "N2LIVE_API"),
            SEXY_BACCARAT_SEAMLESS_API => $this->getGameApiDetails(SEXY_BACCARAT_SEAMLESS_API,"SEXY_BACCARAT_SEAMLESS_API","SEXY_BACCARAT_SEAMLESS_API","goto_common_game/".SEXY_BACCARAT_SEAMLESS_API, "SEXY_BACCARAT_SEAMLESS_API"),
            T1_SEXY_BACCARAT_SEAMLESS_API => $this->getGameApiDetails(T1_SEXY_BACCARAT_SEAMLESS_API,"T1_SEXY_BACCARAT_SEAMLESS_API","T1_SEXY_BACCARAT_SEAMLESS_API","goto_t1games/".T1_SEXY_BACCARAT_SEAMLESS_API, "T1_SEXY_BACCARAT_SEAMLESS_API"),
            HB_IDR1_API => $this->getGameApiDetails(HB_IDR1_API,"HB_IDR1_API","HB_IDR1_API","launch_game_by_lobby/".HB_IDR1_API, "HB_IDR1_API"),
            HB_IDR2_API => $this->getGameApiDetails(HB_IDR2_API,"HB_IDR2_API","HB_IDR2_API","launch_game_by_lobby/".HB_IDR2_API, "HB_IDR2_API"),
            HB_IDR3_API => $this->getGameApiDetails(HB_IDR3_API,"HB_IDR3_API","HB_IDR3_API","launch_game_by_lobby/".HB_IDR3_API, "HB_IDR3_API"),
            HB_IDR4_API => $this->getGameApiDetails(HB_IDR4_API,"HB_IDR4_API","HB_IDR4_API","launch_game_by_lobby/".HB_IDR4_API, "HB_IDR4_API"),
            HB_IDR5_API => $this->getGameApiDetails(HB_IDR5_API,"HB_IDR5_API","HB_IDR5_API","launch_game_by_lobby/".HB_IDR5_API, "HB_IDR5_API"),
            HB_IDR6_API => $this->getGameApiDetails(HB_IDR6_API,"HB_IDR6_API","HB_IDR6_API","launch_game_by_lobby/".HB_IDR6_API, "HB_IDR6_API"),
            HB_IDR7_API => $this->getGameApiDetails(HB_IDR7_API,"HB_IDR7_API","HB_IDR7_API","launch_game_by_lobby/".HB_IDR7_API, "HB_IDR7_API"),
            HB_THB1_API => $this->getGameApiDetails(HB_THB1_API,"HB_THB1_API","HB_THB1_API","launch_game_by_lobby/".HB_THB1_API, "HB_THB1_API"),
            HB_THB2_API => $this->getGameApiDetails(HB_THB2_API,"HB_THB2_API","HB_THB2_API","launch_game_by_lobby/".HB_THB2_API, "HB_THB2_API"),
            HB_VND1_API => $this->getGameApiDetails(HB_VND1_API,"HB_VND1_API","HB_VND1_API","launch_game_by_lobby/".HB_VND1_API, "HB_VND1_API"),
            HB_VND2_API => $this->getGameApiDetails(HB_VND2_API,"HB_VND2_API","HB_VND2_API","launch_game_by_lobby/".HB_VND2_API, "HB_VND2_API"),
            HB_VND3_API => $this->getGameApiDetails(HB_VND3_API,"HB_VND3_API","HB_VND3_API","launch_game_by_lobby/".HB_VND3_API, "HB_VND3_API"),
            HB_CNY1_API => $this->getGameApiDetails(HB_CNY1_API,"HB_CNY1_API","HB_CNY1_API","launch_game_by_lobby/".HB_CNY1_API, "HB_CNY1_API"),
            HB_CNY2_API => $this->getGameApiDetails(HB_CNY2_API,"HB_CNY2_API","HB_CNY2_API","launch_game_by_lobby/".HB_CNY2_API, "HB_CNY2_API"),
            HB_MYR1_API => $this->getGameApiDetails(HB_MYR1_API,"HB_MYR1_API","HB_MYR1_API","launch_game_by_lobby/".HB_MYR1_API, "HB_MYR1_API"),
            HB_MYR2_API => $this->getGameApiDetails(HB_MYR2_API,"HB_MYR2_API","HB_MYR2_API","launch_game_by_lobby/".HB_MYR2_API, "HB_MYR2_API"),
            EA_GAME_API => $this->getGameApiDetails(EA_GAME_API, "EA GAME", "EA_GAME_API", "goto_common_game/".EA_GAME_API, "EA_GAME_API"),
            EA_GAME_API_THB1_API => $this->getGameApiDetails(EA_GAME_API_THB1_API, "EA GAME", "EA_GAME_API_THB1_API", "goto_common_game/".EA_GAME_API_THB1_API, "EA_GAME_API_THB1_API"),
            NETENT_GAME_API => $this->getGameApiDetails(NETENT_GAME_API, "NETENT GAME", "NETENT_GAME_API", "goto_common_game/".NETENT_GAME_API, "NETENT_GAME_API"),
            NETENT_SEAMLESS_GAME_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_API, "NETENT SEAMLESS GAME", "NETENT_SEAMLESS_GAME_API", "goto_common_game/".NETENT_SEAMLESS_GAME_API, "NETENT_SEAMLESS_GAME_API"),
            NETENT_SEAMLESS_GAME_IDR1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_IDR1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_IDR1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_IDR1_API, "NETENT_SEAMLESS_GAME_IDR1_API"),
            NETENT_SEAMLESS_GAME_CNY1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_CNY1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_CNY1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_CNY1_API, "NETENT_SEAMLESS_GAME_CNY1_API"),
            NETENT_SEAMLESS_GAME_THB1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_THB1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_THB1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_THB1_API, "NETENT_SEAMLESS_GAME_THB1_API"),
            NETENT_SEAMLESS_GAME_MYR1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_MYR1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_MYR1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_MYR1_API, "NETENT_SEAMLESS_GAME_MYR1_API"),
            NETENT_SEAMLESS_GAME_VND1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_VND1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_VND1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_VND1_API, "NETENT_SEAMLESS_GAME_VND1_API"),
            NETENT_SEAMLESS_GAME_USD1_API => $this->getGameApiDetails(NETENT_SEAMLESS_GAME_USD1_API, "NETENT GAME", "NETENT_SEAMLESS_GAME_USD1_API", "goto_common_game/".NETENT_SEAMLESS_GAME_USD1_API, "NETENT_SEAMLESS_GAME_USD1_API"),
            BG_SEAMLESS_GAME_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_API, "BG SEAMLESS GAME", "BG_SEAMLESS_GAME_API", "goto_common_game/".BG_SEAMLESS_GAME_API, "BG_SEAMLESS_GAME_API"),
            BG_SEAMLESS_GAME_IDR1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_IDR1_API, "BG GAME", "BG_SEAMLESS_GAME_IDR1_API", "goto_common_game/".BG_SEAMLESS_GAME_IDR1_API, "BG_SEAMLESS_GAME_IDR1_API"),
            BG_SEAMLESS_GAME_CNY1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_CNY1_API, "BG GAME", "BG_SEAMLESS_GAME_CNY1_API", "goto_common_game/".BG_SEAMLESS_GAME_CNY1_API, "BG_SEAMLESS_GAME_CNY1_API"),
            BG_SEAMLESS_GAME_THB1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_THB1_API, "BG GAME", "BG_SEAMLESS_GAME_THB1_API", "goto_common_game/".BG_SEAMLESS_GAME_THB1_API, "BG_SEAMLESS_GAME_THB1_API"),
            BG_SEAMLESS_GAME_MYR1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_MYR1_API, "BG GAME", "BG_SEAMLESS_GAME_MYR1_API", "goto_common_game/".BG_SEAMLESS_GAME_MYR1_API, "BG_SEAMLESS_GAME_MYR1_API"),
            BG_SEAMLESS_GAME_VND1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_VND1_API, "BG GAME", "BG_SEAMLESS_GAME_VND1_API", "goto_common_game/".BG_SEAMLESS_GAME_VND1_API, "BG_SEAMLESS_GAME_VND1_API"),
            BG_SEAMLESS_GAME_USD1_API => $this->getGameApiDetails(BG_SEAMLESS_GAME_USD1_API, "BG GAME", "BG_SEAMLESS_GAME_USD1_API", "goto_common_game/".BG_SEAMLESS_GAME_USD1_API, "BG_SEAMLESS_GAME_USD1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_API, "SPORTSBOOK_FLASH_TECH_GAME_API"),
            SPORTSBOOK_FLASH_TECH_GAME_IDR1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_IDR1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_IDR1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_IDR1_API, "SPORTSBOOK_FLASH_TECH_GAME_IDR1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_CNY1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_CNY1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_CNY1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_CNY1_API, "SPORTSBOOK_FLASH_TECH_GAME_CNY1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_THB1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_THB1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_THB1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_THB1_API, "SPORTSBOOK_FLASH_TECH_GAME_THB1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_MYR1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_MYR1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_MYR1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_MYR1_API, "SPORTSBOOK_FLASH_TECH_GAME_MYR1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_VND1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_VND1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_VND1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_VND1_API, "SPORTSBOOK_FLASH_TECH_GAME_VND1_API"),
            SPORTSBOOK_FLASH_TECH_GAME_USD1_API => $this->getGameApiDetails(SPORTSBOOK_FLASH_TECH_GAME_USD1_API, "Sportsbook Flash Tech", "SPORTSBOOK_FLASH_TECH_GAME_USD1_API", "goto_common_game/".SPORTSBOOK_FLASH_TECH_GAME_USD1_API, "SPORTSBOOK_FLASH_TECH_GAME_USD1_API"),
            KINGPOKER_GAME_API => $this->getGameApiDetails(KINGPOKER_GAME_API,"KING POKER GAME API","KING POKER GAME API","goto_common_game/".KINGPOKER_GAME_API, "KINGPOKER_GAME"),
            KINGPOKER_GAME_API_USD1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_USD1_API,"KINGPOKER GAME API USD1 API","KINGPOKER GAME API USD1 API","goto_common_game/".KINGPOKER_GAME_API_USD1_API, "KINGPOKER_GAME_API_USD1"),
            KINGPOKER_GAME_API_VND1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_VND1_API,"KINGPOKER GAME API VND1 API","KINGPOKER GAME API VND1 API","goto_common_game/".KINGPOKER_GAME_API_VND1_API, "KINGPOKER_GAME_API_VND1"),
            KINGPOKER_GAME_API_MYR1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_MYR1_API,"KINGPOKER GAME API MYR1 API","KINGPOKER GAME API MYR1 API","goto_common_game/".KINGPOKER_GAME_API_MYR1_API, "KINGPOKER_GAME_API_MYR1"),
            KINGPOKER_GAME_API_THB1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_THB1_API,"KINGPOKER GAME API THB1 API","KINGPOKER GAME API THB1 API","goto_common_game/".KINGPOKER_GAME_API_THB1_API, "KINGPOKER_GAME_API_THB1"),
            KINGPOKER_GAME_API_CNY1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_CNY1_API,"KINGPOKER GAME API CNY1 API","KINGPOKER GAME API CNY1 API","goto_common_game/".KINGPOKER_GAME_API_CNY1_API, "KINGPOKER_GAME_API_CNY1"),
            KINGPOKER_GAME_API_IDR1_API => $this->getGameApiDetails(KINGPOKER_GAME_API_IDR1_API,"KINGPOKER GAME API IDR1 API","KINGPOKER GAME API IDR1 API","goto_common_game/".KINGPOKER_GAME_API_IDR1_API, "KINGPOKER_GAME_API_IDR1"),
            EVOPLAY_GAME_API => $this->getGameApiDetails(EVOPLAY_GAME_API,"EVOPLAY","EVOPLAY","goto_common_game/".EVOPLAY_GAME_API, "EVOPLAY_GAME"),
            EVOPLAY_GAME_API_USD1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_USD1_API,"EVOPLAY GAME API USD1 API","EVOPLAY GAME API USD1 API","goto_common_game/".EVOPLAY_GAME_API_USD1_API, "EVOPLAY_GAME_API_USD1"),
            EVOPLAY_GAME_API_VND1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_VND1_API,"EVOPLAY GAME API VND1 API","EVOPLAY GAME API VND1 API","goto_common_game/".EVOPLAY_GAME_API_VND1_API, "EVOPLAY_GAME_API_VND1"),
            EVOPLAY_GAME_API_MYR1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_MYR1_API,"EVOPLAY GAME API MYR1 API","EVOPLAY GAME API MYR1 API","goto_common_game/".EVOPLAY_GAME_API_MYR1_API, "EVOPLAY_GAME_API_MYR1"),
            EVOPLAY_GAME_API_THB1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_THB1_API,"EVOPLAY GAME API THB1 API","EVOPLAY GAME API THB1 API","goto_common_game/".EVOPLAY_GAME_API_THB1_API, "EVOPLAY_GAME_API_THB1"),
            EVOPLAY_GAME_API_CNY1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_CNY1_API,"EVOPLAY GAME API CNY1 API","EVOPLAY GAME API CNY1 API","goto_common_game/".EVOPLAY_GAME_API_CNY1_API, "EVOPLAY_GAME_API_CNY1"),
            EVOPLAY_GAME_API_IDR1_API => $this->getGameApiDetails(EVOPLAY_GAME_API_IDR1_API,"EVOPLAY GAME API IDR1 API","EVOPLAY GAME API IDR1 API","goto_common_game/".EVOPLAY_GAME_API_IDR1_API, "EVOPLAY_GAME_API_IDR1"),
            HYDAKO_GAME_API => $this->getGameApiDetails(HYDAKO_GAME_API, "HYDAKO", "HYDAKO_GAME_API", "goto_common_game/".HYDAKO_GAME_API, "HYDAKO_GAME_API"),
            HYDAKO_THB1_API => $this->getGameApiDetails(HYDAKO_THB1_API, "HYDAKO", "HYDAKO_THB1_API", "goto_common_game/".HYDAKO_THB1_API, "HYDAKO_THB1_API"),
            BGSOFT_GAME_API => $this->getGameApiDetails(BGSOFT_GAME_API,"BGSOFT","BGSOFT","goto_common_game/".BGSOFT_GAME_API, "BGSOFT"),
            BGSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(BGSOFT_SEAMLESS_GAME_API,"T1GAMES SEAMLESS","BGSOFT SEAMLESS","goto_common_game/".BGSOFT_SEAMLESS_GAME_API, "T1GAMES SEAMLESS"),
            T1GAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(T1GAMES_SEAMLESS_GAME_API,"T1GAMES SEAMLESS","T1GAMES SEAMLESS","goto_common_game/".T1GAMES_SEAMLESS_GAME_API, "T1GAMES SEAMLESS"),
            PRAGMATICPLAY_LIVEDEALER_CNY1_API => $this->getGameApiDetails(PRAGMATICPLAY_LIVEDEALER_CNY1_API, "PRAGMATICPLAY LIVE DEALER", "PRAGMATICPLAY_LIVEDEALER_CNY1_API", "goto_common_game/", "PRAGMATICPLAY LIVE DEALER"),
            PRAGMATICPLAY_LIVEDEALER_THB1_API => $this->getGameApiDetails(PRAGMATICPLAY_LIVEDEALER_THB1_API, "PP LD THB", "PRAGMATICPLAY_LIVEDEALER_THB1_API", "goto_common_game/", "PP LD THB"),
            RUBYPLAY_SEAMLESS_THB1_API => $this->getGameApiDetails(RUBYPLAY_SEAMLESS_THB1_API, "RUBY PLAY", "RUBYPLAY_SEAMLESS_THB1_API", "goto_common_game/".RUBYPLAY_SEAMLESS_THB1_API, "ruby_play"),
            RUBYPLAY_SEAMLESS_API => $this->getGameApiDetails(RUBYPLAY_SEAMLESS_API, "RUBY PLAY", "RUBYPLAY_SEAMLESS_API", "goto_common_game/".RUBYPLAY_SEAMLESS_API, "ruby_play"),
            PHOENIX_CHESS_CARD_POKER_API => $this->getGameApiDetails(PHOENIX_CHESS_CARD_POKER_API, "PHOENIX CHESS CARD POKER", "PHOENIX_CHESS_CARD_POKER_API", "goto_common_game/".PHOENIX_CHESS_CARD_POKER_API, "PHOENIX CHESS CARD POKER"),
            PRETTY_GAMING_SEAMLESS_API_IDR1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_IDR1_GAME_API,"PRETTY GAMING SEAMLESS API IDR1","PRETTY GAMING SEAMLESS API IDR1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_IDR1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_IDR1_GAME"),
            PRETTY_GAMING_SEAMLESS_API_CNY1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_CNY1_GAME_API,"PRETTY GAMING SEAMLESS API CNY1","PRETTY GAMING SEAMLESS API CNY1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_CNY1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_CNY1_GAME"),
            PRETTY_GAMING_SEAMLESS_API_THB1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_THB1_GAME_API,"PRETTY GAMING SEAMLESS API THB1","PRETTY GAMING SEAMLESS API THB1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_THB1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_THB1_GAME"),
            PRETTY_GAMING_SEAMLESS_API_MYR1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_MYR1_GAME_API,"PRETTY GAMING SEAMLESS API MYR1","PRETTY GAMING SEAMLESS API MYR1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_MYR1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_MYR1_GAME"),
            PRETTY_GAMING_SEAMLESS_API_VND1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_VND1_GAME_API,"PRETTY GAMING SEAMLESS API VND1","PRETTY GAMING SEAMLESS API VND1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_VND1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_VND1_GAME"),
            PRETTY_GAMING_SEAMLESS_API_USD1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API_USD1_GAME_API,"PRETTY GAMING SEAMLESS API USD1","PRETTY GAMING SEAMLESS API USD1","goto_common_game/".PRETTY_GAMING_SEAMLESS_API_USD1_GAME_API, "PRETTY_GAMING_SEAMLESS_API_USD1_GAME"),
            PRETTY_GAMING_SEAMLESS_API => $this->getGameApiDetails(PRETTY_GAMING_SEAMLESS_API,"PRETTY GAMING SEAMLESS API","PRETTY GAMING SEAMLESS API","goto_common_game/".PRETTY_GAMING_SEAMLESS_API, "PRETTY_GAMING_SEAMLESS_API_GAME"),
            PRETTY_GAMING_API_IDR1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_IDR1_GAME_API,"PRETTY GAMING API IDR1","PRETTY GAMING API IDR1","goto_common_game/".PRETTY_GAMING_API_IDR1_GAME_API, "PRETTY_GAMING_API_IDR1_GAME"),
            PRETTY_GAMING_API_CNY1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_CNY1_GAME_API,"PRETTY GAMING API CNY1","PRETTY GAMING API CNY1","goto_common_game/".PRETTY_GAMING_API_CNY1_GAME_API, "PRETTY_GAMING_API_CNY1_GAME"),
            PRETTY_GAMING_API_THB1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_THB1_GAME_API,"PRETTY GAMING API THB1","PRETTY GAMING API THB1","goto_common_game/".PRETTY_GAMING_API_THB1_GAME_API, "PRETTY_GAMING_API_THB1_GAME"),
            PRETTY_GAMING_API_MYR1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_MYR1_GAME_API,"PRETTY GAMING API MYR1","PRETTY GAMING API MYR1","goto_common_game/".PRETTY_GAMING_API_MYR1_GAME_API, "PRETTY_GAMING_API_MYR1_GAME"),
            PRETTY_GAMING_API_VND1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_VND1_GAME_API,"PRETTY GAMING API VND1","PRETTY GAMING API VND1","goto_common_game/".PRETTY_GAMING_API_VND1_GAME_API, "PRETTY_GAMING_API_VND1_GAME"),
            PRETTY_GAMING_API_USD1_GAME_API => $this->getGameApiDetails(PRETTY_GAMING_API_USD1_GAME_API,"PRETTY GAMING API USD1","PRETTY GAMING API USD1","goto_common_game/".PRETTY_GAMING_API_USD1_GAME_API, "PRETTY_GAMING_API_USD1_GAME"),
            PRETTY_GAMING_API => $this->getGameApiDetails(PRETTY_GAMING_API,"PRETTY GAMING API","PRETTY GAMING API","goto_common_game/".PRETTY_GAMING_API, "PRETTY_GAMING_API_GAME"),
            BETGAMES_SEAMLESS_THB1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_THB1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_THB1_GAME_API"),
            BETGAMES_SEAMLESS_IDR1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_IDR1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_IDR1_GAME_API"),
            BETGAMES_SEAMLESS_CNY1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_CNY1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_CNY1_GAME_API"),
            BETGAMES_SEAMLESS_MYR1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_MYR1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_MYR1_GAME_API"),
            BETGAMES_SEAMLESS_VND1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_VND1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_VND1_GAME_API"),
            BETGAMES_SEAMLESS_USD1_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_USD1_GAME_API,"BETGAMES API","BETGAMES API","goto_betgames/", "BETGAMES_SEAMLESS_USD1_GAME_API"),
            QUEEN_MAKER_GAME_API => $this->getGameApiDetails(QUEEN_MAKER_GAME_API,"QUEEN_MAKER_GAME_API","QUEEN_MAKER_GAME_API","goto_common_game/".QUEEN_MAKER_GAME_API, "QUEEN_MAKER_GAME_API"),
            KING_MIDAS_GAME_API => $this->getGameApiDetails(KING_MIDAS_GAME_API,"KING_MIDAS_GAME_API","KING_MIDAS_GAME_API","goto_common_game/".KING_MIDAS_GAME_API, "KING_MIDAS_GAME_API"),
            ONEGAME_GAME_API => $this->getGameApiDetails(ONEGAME_GAME_API,"ONEGAME_GAME_API","ONEGAME_GAME_API","goto_common_game/".ONEGAME_GAME_API, "ONEGAME_GAME_API"),
            SV388_GAME_API => $this->getGameApiDetails(SV388_GAME_API,"SV388 GAME API","SV388 GAME API","goto_sv388_game/".SV388_GAME_API, "SV388_GAME_API"),
            SV388_GAMING_THB_B1_API => $this->getGameApiDetails(SV388_GAMING_THB_B1_API,"SV388 THB1 GAME API","SV388 THB1 GAME API","goto_sv388_game/".SV388_GAMING_THB_B1_API, "SV388_GAMING_THB_B1_API"),
            SV388_SEAMLESS_GAME_API => $this->getGameApiDetails(SV388_SEAMLESS_GAME_API,"SV388_SEAMLESS_GAME_API","SV388_SEAMLESS_GAME_API","goto_sv388_game/".SV388_SEAMLESS_GAME_API, "SV388_SEAMLESS_GAME_API"),
            CHAMPION_SPORTS_GAME_API => $this->getGameApiDetails(CHAMPION_SPORTS_GAME_API,"CHAMPION SPORTS GAME API","CHAMPION SPORTS GAME API","goto_common_game/".CHAMPION_SPORTS_GAME_API, "CHAMPION_SPORTS_GAME_API"),
            S128_GAME_API => $this->getGameApiDetails(S128_GAME_API,"S128 GAME API","S128 GAME API","goto_common_game/".S128_GAME_API, "S128_GAME_API"),
            ISB_INR1_API => $this->getGameApiDetails(ISB_INR1_API,"ISB_INR1_API","ISB_INR1_API","goto_common_game/".ISB_INR1_API, "ISB_INR1_API"),
            IPM_V2_ESPORTS_API => $this->getGameApiDetails(IPM_V2_ESPORTS_API,"IPM V2 ESPORTS","IPM_V2_ESPORTS","goto_common_game/".IPM_V2_ESPORTS_API, "IPM_V2_ESPORTS"),
            QUEEN_MAKER_REDTIGER_GAME_API => $this->getGameApiDetails(QUEEN_MAKER_REDTIGER_GAME_API,"QUEEN_MAKER_REDTIGER_GAME_API","QUEEN_MAKER_REDTIGER_GAME_API","goto_common_game/".QUEEN_MAKER_REDTIGER_GAME_API, "QUEEN_MAKER_REDTIGER_GAME_API"),
            GMT_GAME_API => $this->getGameApiDetails(GMT_GAME_API,"GMT_GAME_API","GMT_GAME_API","goto_common_game/".GMT_GAME_API, "GMT_GAME_API"),

            FLOW_GAMING_NETENT_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_NETENT_SEAMLESS_THB1_API,"FLOW GAMING NETENT SEAMLESS API","FLOW GAMING NETENT SEAMLESS API","goto_common_game/".FLOW_GAMING_NETENT_SEAMLESS_THB1_API, "FLOW_GAMING_NETENT_SEAMLESS_THB1_API"),
            FLOW_GAMING_NETENT_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_NETENT_SEAMLESS_API,"FLOW GAMING NETENT SEAMLESS API","FLOW GAMING NETENT SEAMLESS API","goto_common_game/".FLOW_GAMING_NETENT_SEAMLESS_API, "FLOW_GAMING_NETENT_SEAMLESS_API"),

            FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API,"FLOW GAMING YGGDRASIL SEAMLESS API","FLOW GAMING YGGDRASIL SEAMLESS API","goto_common_game/".FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API, "FLOW_GAMING_YGGDRASIL_SEAMLESS_THB1_API"),
            FLOW_GAMING_YGGDRASIL_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_YGGDRASIL_SEAMLESS_API,"FLOW GAMING YGGDRASIL SEAMLESS API","FLOW GAMING YGGDRASIL SEAMLESS API","goto_common_game/".FLOW_GAMING_YGGDRASIL_SEAMLESS_API, "FLOW_GAMING_YGGDRASIL_SEAMLESS_API"),

            FLOW_GAMING_MAVERICK_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_MAVERICK_SEAMLESS_THB1_API,"FLOW GAMING ELYSIUM SEAMLESS API","FLOW GAMING ELYSIUM SEAMLESS API","goto_common_game/".FLOW_GAMING_MAVERICK_SEAMLESS_THB1_API, "FLOW_GAMING_ELYSIUM_SEAMLESS_THB1_API"),
            FLOW_GAMING_MAVERICK_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_MAVERICK_SEAMLESS_API,"FLOW GAMING ELYSIUM SEAMLESS API","FLOW GAMING ELYSIUM SEAMLESS API","goto_common_game/".FLOW_GAMING_MAVERICK_SEAMLESS_API, "FLOW_GAMING_ELYSIUM_SEAMLESS_API"),

            FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API,"FLOW GAMING QUICKSPIN SEAMLESS API","FLOW GAMING QUICKSPIN SEAMLESS API","goto_common_game/".FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API, "FLOW_GAMING_QUICKSPIN_SEAMLESS_THB1_API"),
            FLOW_GAMING_QUICKSPIN_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_QUICKSPIN_SEAMLESS_API,"FLOW GAMING QUICKSPIN SEAMLESS API","FLOW GAMING QUICKSPIN SEAMLESS API","goto_common_game/".FLOW_GAMING_QUICKSPIN_SEAMLESS_API, "FLOW_GAMING_QUICKSPIN_SEAMLESS_API"),

            FLOW_GAMING_PNG_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_PNG_SEAMLESS_THB1_API,"FLOW GAMING PNG SEAMLESS API","FLOW GAMING PNG SEAMLESS API","goto_common_game/".FLOW_GAMING_PNG_SEAMLESS_THB1_API, "FLOW_GAMING_PNG_SEAMLESS_THB1_API"),
            FLOW_GAMING_PNG_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_PNG_SEAMLESS_API,"FLOW GAMING PNG SEAMLESS API","FLOW GAMING PNG SEAMLESS API","goto_common_game/".FLOW_GAMING_PNG_SEAMLESS_API, "FLOW_GAMING_PNG_SEAMLESS_API"),

            FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API,"FLOW GAMING 4THPLAYER SEAMLESS API","FLOW GAMING 4THPLAYER SEAMLESS API","goto_common_game/".FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API, "FLOW_GAMING_4THPLAYER_SEAMLESS_THB1_API"),
            FLOW_GAMING_4THPLAYER_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_4THPLAYER_SEAMLESS_API,"FLOW GAMING 4THPLAYER SEAMLESS API","FLOW GAMING 4THPLAYER SEAMLESS API","goto_common_game/".FLOW_GAMING_4THPLAYER_SEAMLESS_API, "FLOW_GAMING_4THPLAYER_SEAMLESS_API"),

            FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API => $this->getGameApiDetails(FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API,"FLOW GAMING RELAXGAMING SEAMLESS API","FLOW GAMING RELAXGAMING SEAMLESS API","goto_common_game/".FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API, "FLOW_GAMING_RELAXGAMING_SEAMLESS_THB1_API"),
            FLOW_GAMING_RELAXGAMING_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_RELAXGAMING_SEAMLESS_API,"FLOW GAMING RELAXGAMING SEAMLESS API","FLOW GAMING RELAXGAMING SEAMLESS API","goto_common_game/".FLOW_GAMING_RELAXGAMING_SEAMLESS_API, "FLOW_GAMING_RELAXGAMING_SEAMLESS_API"),

            LIVE12_SEAMLESS_GAME_API => $this->getGameApiDetails(LIVE12_SEAMLESS_GAME_API,"LIVE12_SEAMLESS_GAME_API","LIVE12_SEAMLESS_GAME_API","goto_12live_game/".LIVE12_SEAMLESS_GAME_API, "LIVE12_SEAMLESS_GAME_API"),
            LIVE12_PGSOFT_SEAMLESS_API => $this->getGameApiDetails(LIVE12_PGSOFT_SEAMLESS_API,"LIVE12_PGSOFT_SEAMLESS_API","LIVE12_PGSOFT_SEAMLESS_API","goto_12live_game/".LIVE12_PGSOFT_SEAMLESS_API, "LIVE12_PGSOFT_SEAMLESS_API"),
            LIVE12_SPADEGAMING_SEAMLESS_API => $this->getGameApiDetails(LIVE12_SPADEGAMING_SEAMLESS_API,"LIVE12_SPADEGAMING_SEAMLESS_API","LIVE12_SPADEGAMING_SEAMLESS_API","goto_12live_game/".LIVE12_SPADEGAMING_SEAMLESS_API, "LIVE12_SPADEGAMING_SEAMLESS_API"),
            LIVE12_REDTIGER_SEAMLESS_API => $this->getGameApiDetails(LIVE12_REDTIGER_SEAMLESS_API,"LIVE12_REDTIGER_SEAMLESS_API","LIVE12_REDTIGER_SEAMLESS_API","goto_12live_game/".LIVE12_REDTIGER_SEAMLESS_API, "LIVE12_REDTIGER_SEAMLESS_API"),
            LIVE12_EVOLUTION_SEAMLESS_API => $this->getGameApiDetails(LIVE12_EVOLUTION_SEAMLESS_API,"LIVE12_EVOPLAY_SEAMLESS_API","LIVE12_EVOPLAY_SEAMLESS_API","goto_12live_game/".LIVE12_EVOLUTION_SEAMLESS_API, "LIVE12_EVOPLAY_SEAMLESS_API"),

            AMB_SEAMLESS_GAME_API => $this->getGameApiDetails(AMB_SEAMLESS_GAME_API,"AMB_SEAMLESS_GAME_API","AMB_SEAMLESS_GAME_API","goto_common_game/".AMB_SEAMLESS_GAME_API, "AMB_SEAMLESS_GAME_API"),

            HA_GAME_API => $this->getGameApiDetails(HA_GAME_API,"HA GAME API","HA GAME API","goto_common_game/".HA_GAME_API, "HA_GAME_API"),
            YABO_GAME_API => $this->getGameApiDetails(YABO_GAME_API,"YABO_GAME_API","YABO_GAME_API","goto_common_game/".YABO_GAME_API, "YABO_GAME_API"),
            OM_LOTTO_GAME_API => $this->getGameApiDetails(OM_LOTTO_GAME_API,"OM_LOTTO_GAME_API","OM_LOTTO_GAME_API","goto_common_game/".OM_LOTTO_GAME_API, "OM_LOTTO_GAME_API"),
            HP_2D3D_GAME_API => $this->getGameApiDetails(HP_2D3D_GAME_API,"HP_2D3D_GAME_API","HP_2D3D_GAME_API","goto_common_game/".HP_2D3D_GAME_API, "HP_2D3D_GAME_API"),
            SLOT_FACTORY_GAME_API => $this->getGameApiDetails(SLOT_FACTORY_GAME_API,"Slot Factory API", "SLOT_FACTORY_GAME_API", "goto_common_game", "SLOT_FACTORY_GAME_API"),
            LOTTO97_SEAMLESS_GAME_API => $this->getGameApiDetails(LOTTO97_SEAMLESS_GAME_API,"Lotto 97", "LOTTO97_SEAMLESS_GAME_API", "goto_common_game", "LOTTO97_SEAMLESS_GAME_API"),
            IBC_ONEBOOK_SEAMLESS_API => $this->getGameApiDetails(IBC_ONEBOOK_SEAMLESS_API,"IBC_ONEBOOK_SEAMLESS_API", "IBC_ONEBOOK_SEAMLESS_API", "goto_common_game/".IBC_ONEBOOK_SEAMLESS_API, "IBC_ONEBOOK_SEAMLESS_API"),
            T1_IBC_ONEBOOK_SEAMLESS_API => $this->getGameApiDetails(T1_IBC_ONEBOOK_SEAMLESS_API, "T1_IBC_ONEBOOK_SEAMLESS_API", "T1_IBC_ONEBOOK_SEAMLESS_API", "goto_t1games/" . T1_IBC_ONEBOOK_SEAMLESS_API, "T1_IBC_ONEBOOK_SEAMLESS_API"),
            IBC_ONEBOOK_API => $this->getGameApiDetails(IBC_ONEBOOK_API,"IBC_ONEBOOK_API", "IBC_ONEBOOK_API", "goto_common_game/".IBC_ONEBOOK_API, "IBC_ONEBOOK_API"),
            YL_NTTECH_GAME_API => $this->getGameApiDetails(YL_NTTECH_GAME_API,"YL_NTTECH_GAME_API", "YL_NTTECH_GAME_API", "goto_nttech_game/", "YL_NTTECH_GAME_API"),
            TANGKAS1_API => $this->getGameApiDetails(TANGKAS1_API,"TANGKAS1_API", "TANGKAS1_API", "goto_common_game/".TANGKAS1_API, "TANGKAS1_API"),
            TANGKAS1_IDR_API => $this->getGameApiDetails(TANGKAS1_IDR_API,"TANGKAS1_IDR_API", "TANGKAS1_IDR_API", "goto_common_game/".TANGKAS1_IDR_API, "TANGKAS1_IDR_API"),
            TANGKAS1_CNY_API => $this->getGameApiDetails(TANGKAS1_CNY_API,"TANGKAS1_CNY_API", "TANGKAS1_CNY_API", "goto_common_game/".TANGKAS1_CNY_API, "TANGKAS1_CNY_API"),
            TANGKAS1_THB_API => $this->getGameApiDetails(TANGKAS1_THB_API,"TANGKAS1_THB_API", "TANGKAS1_THB_API", "goto_common_game/".TANGKAS1_THB_API, "TANGKAS1_THB_API"),
            TANGKAS1_USD_API => $this->getGameApiDetails(TANGKAS1_USD_API,"TANGKAS1_USD_API", "TANGKAS1_USD_API", "goto_common_game/".TANGKAS1_USD_API, "TANGKAS1_USD_API"),
            TANGKAS1_VND_API => $this->getGameApiDetails(TANGKAS1_VND_API,"TANGKAS1_VND_API", "TANGKAS1_VND_API", "goto_common_game/".TANGKAS1_VND_API, "TANGKAS1_VND_API"),
            TANGKAS1_MYR_API => $this->getGameApiDetails(TANGKAS1_MYR_API,"TANGKAS1_MYR_API", "TANGKAS1_MYR_API", "goto_common_game/".TANGKAS1_MYR_API, "TANGKAS1_MYR_API"),
            ISIN4D_API => $this->getGameApiDetails(ISIN4D_API,"ISIN4D_API", "ISIN4D_API", "goto_common_game/".ISIN4D_API, "ISIN4D_API"),
            ISIN4D_IDR_B1_API => $this->getGameApiDetails(ISIN4D_IDR_B1_API,"ISIN4D_IDR_B1_API", "ISIN4D_IDR_B1_API", "goto_common_game/".ISIN4D_IDR_B1_API, "ISIN4D_IDR_B1_API"),
            ISIN4D_CNY_B1_API => $this->getGameApiDetails(ISIN4D_CNY_B1_API,"ISIN4D_CNY_B1_API", "ISIN4D_CNY_B1_API", "goto_common_game/".ISIN4D_CNY_B1_API, "ISIN4D_CNY_B1_API"),
            ISIN4D_THB_B1_API => $this->getGameApiDetails(ISIN4D_THB_B1_API,"ISIN4D_THB_B1_API", "ISIN4D_THB_B1_API", "goto_common_game/".ISIN4D_THB_B1_API, "ISIN4D_THB_B1_API"),
            ISIN4D_USD_B1_API => $this->getGameApiDetails(ISIN4D_USD_B1_API,"ISIN4D_USD_B1_API", "ISIN4D_USD_B1_API", "goto_common_game/".ISIN4D_USD_B1_API, "ISIN4D_USD_B1_API"),
            ISIN4D_VND_B1_API => $this->getGameApiDetails(ISIN4D_VND_B1_API,"ISIN4D_VND_B1_API", "ISIN4D_VND_B1_API", "goto_common_game/".ISIN4D_VND_B1_API, "ISIN4D_VND_B1_API"),
            ISIN4D_MYR_B1_API => $this->getGameApiDetails(ISIN4D_MYR_B1_API,"ISIN4D_MYR_B1_API", "ISIN4D_MYR_B1_API", "goto_common_game/".ISIN4D_MYR_B1_API, "ISIN4D_MYR_B1_API"),
            KA_SEAMLESS_API => $this->getGameApiDetails(KA_SEAMLESS_API,"KA Gaming","KA Gaming","goto_common_game/".KA_SEAMLESS_API, "KAGAMING"),
            CALETA_SEAMLESS_API => $this->getGameApiDetails(CALETA_SEAMLESS_API,"CALETA_SEAMLESS_API", "CALETA_SEAMLESS_API", "goto_common_game/".CALETA_SEAMLESS_API, "CALETA_SEAMLESS_API"),
            T1LOTTERY_SEAMLESS_API => $this->getGameApiDetails(T1LOTTERY_SEAMLESS_API,"T1LOTTERY_SEAMLESS_API","T1LOTTERY_SEAMLESS_API","goto_common_game/".T1LOTTERY_SEAMLESS_API, "T1LOTTERY_SEAMLESS_API"),
            HKB_GAME_API => $this->getGameApiDetails(HKB_GAME_API,"HKB","HKB", "goto_common_game/".HKB_GAME_API, "HKB"),
            TG_GAME_API => $this->getGameApiDetails(TG_GAME_API,"TG GAME API","TG GAME API", "goto_common_game/".TG_GAME_API, "TG GAME API"),
            YEEBET_API => $this->getGameApiDetails(YEEBET_API,"YEEBET GAME API","YEEBET GAME API", "goto_common_game/".YEEBET_API, "YEEBET GAME API"),
            WON_API => $this->getGameApiDetails(WON_API,"WON GAME API","WON GAME API", "goto_common_game/".WON_API, "WON GAME API"),
            WICKETS9_API => $this->getGameApiDetails(WICKETS9_API,"9WICKETS","9WICKETS", "goto_common_game", "9WICKETS"),
            BETF_API => $this->getGameApiDetails(BETF_API,"BETF GAME API","BETF GAME API", "goto_common_game/", "BETF GAME API"),
            IPM_V2_IMSB_ESPORTSBULL_API => $this->getGameApiDetails(IPM_V2_IMSB_ESPORTSBULL_API, "IPM V2 SPORTS AND ESPORTS", "IPM V2 SPORTS AND ESPORTS", "goto_common_game/".IPM_V2_IMSB_ESPORTSBULL_API, "IPM V2 SPORTS AND ESPORTS"),
            BDM_SEAMLESS_API => $this->getGameApiDetails(BDM_SEAMLESS_API,"Joker", "Joker", "goto_common_game/".BDM_SEAMLESS_API, "Joker"),
            T1_JOKER_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_JOKER_SEAMLESS_GAME_API,"Joker", "Joker", "goto_t1games/".T1_JOKER_SEAMLESS_GAME_API, "Joker"),

            FLOW_GAMING_PLAYTECH_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_PLAYTECH_SEAMLESS_API,"FLOW GAMING PLAYTECH SEAMLESS API","FLOW GAMING PLAYTECH SEAMLESS API","goto_common_game/".FLOW_GAMING_PLAYTECH_SEAMLESS_API, "FLOW_GAMING_PLAYTECH_SEAMLESS_API"),
            EZUGI_SEAMLESS_API => $this->getGameApiDetails(EZUGI_SEAMLESS_API,"EZUGI_SEAMLESS_API","EZUGI_SEAMLESS_API","goto_common_game/".EZUGI_SEAMLESS_API, "EZUGI_SEAMLESS_API"),
            EZUGI_EVO_SEAMLESS_API => $this->getGameApiDetails(EZUGI_EVO_SEAMLESS_API,"EZUGI_EVO_SEAMLESS_API","EZUGI_EVO_SEAMLESS_API","goto_common_game/".EZUGI_EVO_SEAMLESS_API, "EZUGI_EVO_SEAMLESS_API"),
            EZUGI_NETENT_SEAMLESS_API => $this->getGameApiDetails(EZUGI_NETENT_SEAMLESS_API,"EZUGI_NETENT_SEAMLESS_API","EZUGI_NETENT_SEAMLESS_API","goto_common_game/".EZUGI_NETENT_SEAMLESS_API, "EZUGI_NETENT_SEAMLESS_API"),
            EZUGI_REDTIGER_SEAMLESS_API => $this->getGameApiDetails(EZUGI_REDTIGER_SEAMLESS_API,"EZUGI_REDTIGER_SEAMLESS_API","EZUGI_REDTIGER_SEAMLESS_API","goto_common_game/".EZUGI_REDTIGER_SEAMLESS_API, "EZUGI_REDTIGER_SEAMLESS_API"),
            T1_EZUGI_REDTIGER_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EZUGI_REDTIGER_SEAMLESS_GAME_API,"T1_EZUGI_REDTIGER_SEAMLESS_GAME_API","T1_EZUGI_REDTIGER_SEAMLESS_GAME_API","goto_t1games/".T1_EZUGI_REDTIGER_SEAMLESS_GAME_API, "T1_EZUGI_REDTIGER_SEAMLESS_GAME_API"),

            T1_EZUGI_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EZUGI_SEAMLESS_GAME_API,"T1_EZUGI_SEAMLESS_GAME_API","T1_EZUGI_SEAMLESS_GAME_API","goto_t1games/".T1_EZUGI_SEAMLESS_GAME_API, "T1_EZUGI_SEAMLESS_GAME_API"),
            SGWIN_API => $this->getGameApiDetails(SGWIN_API,"SGWIN","SGWIN", "goto_common_game/".SGWIN_API, "SGWIN"),
            BBGAME_API => $this->getGameApiDetails(BBGAME_API,"BBGAME","BBGAME", "goto_common_game/".BBGAME_API, "BBGAME"),
            KGAME_API => $this->getGameApiDetails(KGAME_API,"KGAME","KGAME", "goto_common_game/".KGAME_API, "KGAME"),
            IDNPOKER_API => $this->getGameApiDetails(IDNPOKER_API,"IDNPOKER","IDNPOKER", "goto_common_game/".IDNPOKER_API, "IDNPOKER"),
            HOTGRAPH_SEAMLESS_API => $this->getGameApiDetails(HOTGRAPH_SEAMLESS_API,"HOTGRAPH_SEAMLESS","HOTGRAPH_SEAMLESS", "goto_common_game/".HOTGRAPH_SEAMLESS_API, "HOTGRAPH_SEAMLESS"),
            AMB_PGSOFT_SEAMLESS_API => $this->getGameApiDetails(AMB_PGSOFT_SEAMLESS_API,"AMB_PGSOFT_SEAMLESS","AMB_PGSOFT_SEAMLESS", "goto_common_game/".AMB_PGSOFT_SEAMLESS_API, "AMB_PGSOFT_SEAMLESS"),
            JUMBO_SEAMLESS_GAME_API =>$this->getGameApiDetails(JUMBO_SEAMLESS_GAME_API,"JUMBO_SEAMLESS","JUMBO_SEAMLESS", "goto_common_game/".JUMBO_SEAMLESS_GAME_API, "JUMBO_SEAMLESS"),
            CHERRY_GAMING_SEAMLESS_GAME_API =>$this->getGameApiDetails(CHERRY_GAMING_SEAMLESS_GAME_API,"CHERRY_GAMING_SEAMLESS","CHERRY_GAMING_SEAMLESS", "goto_common_game/".CHERRY_GAMING_SEAMLESS_GAME_API, "CHERRY_GAMING_SEAMLESS"),
            BETER_SEAMLESS_GAME_API =>$this->getGameApiDetails(BETER_SEAMLESS_GAME_API,"BETER_SEAMLESS_GAME_API","BETER_SEAMLESS_GAME_API", "goto_common_game/".BETER_SEAMLESS_GAME_API, "BETER_SEAMLESS_GAME_API"),
            BETER_SPORTS_SEAMLESS_GAME_API =>$this->getGameApiDetails(BETER_SPORTS_SEAMLESS_GAME_API,"BETER_SPORTS_SEAMLESS_GAME_API","BETER_SPORTS_SEAMLESS_GAME_API", "goto_common_game/".BETER_SPORTS_SEAMLESS_GAME_API, "BETER_SPORTS_SEAMLESS_GAME_API"),
            EVENBET_POKER_SEAMLESS_GAME_API =>$this->getGameApiDetails(EVENBET_POKER_SEAMLESS_GAME_API,"EVENBET_POKER_SEAMLESS","EVENBET_POKER_SEAMLESS", "goto_common_game/".EVENBET_POKER_SEAMLESS_GAME_API, "EVENBET_POKER_SEAMLESS"),
            EBET_SEAMLESS_GAME_API => $this->getGameApiDetails(EBET_SEAMLESS_GAME_API,"EBET_SEAMLESS","EBET_SEAMLESS", "goto_common_game", "EBET_SEAMLESS"),
            T1_EBET_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EBET_SEAMLESS_GAME_API,"T1_EBET_SEAMLESS_GAME_API","T1_EBET_SEAMLESS_GAME_API", "goto_t1games", "T1_EBET_SEAMLESS_GAME_API"),

            FLOW_GAMING_MG_SEAMLESS_API => $this->getGameApiDetails(FLOW_GAMING_MG_SEAMLESS_API,"FLOW GAMING MG SEAMLESS API","FLOW GAMING MG SEAMLESS API","goto_common_game/".FLOW_GAMING_MG_SEAMLESS_API, "FLOW_GAMING_MG_SEAMLESS_API"),
            LOTO_SEAMLESS_API => $this->getGameApiDetails(LOTO_SEAMLESS_API,"LOTO","LOTO","goto_common_game/".LOTO_SEAMLESS_API, "LOTO"),
            MGPLUS_SEAMLESS_API => $this->getGameApiDetails(MGPLUS_SEAMLESS_API,"MG Plus","MG Plus","goto_common_game", "MGPLUS"),
            PT_V3_API => $this->getGameApiDetails(PT_V3_API, "PlayTech", "PT_V3_API", "goto_ptv3game/" . PT_V3_API, "PT_V3_API"),
            BISTRO_SEAMLESS_API => $this->getGameApiDetails(BISTRO_SEAMLESS_API,"BISTRO","BISTRO","goto_common_game/".BISTRO_SEAMLESS_API, "BISTRO"),
            JILI_SEAMLESS_API => $this->getGameApiDetails(JILI_SEAMLESS_API,"JILI_SEAMLESS_API","JILI_SEAMLESS_API","goto_common_game/".JILI_SEAMLESS_API, "JILI_SEAMLESS_API"),
            TRUCO_SEAMLESS_API => $this->getGameApiDetails(TRUCO_SEAMLESS_API,"TRUCO_SEAMLESS_API","TRUCO_SEAMLESS_API","goto_common_game/".TRUCO_SEAMLESS_API, "TRUCO_SEAMLESS_API"),
            JQ_GAME_API => $this->getGameApiDetails(JQ_GAME_API,"JQ_GAME_API","JQ_GAME_API","goto_common_game/".JQ_GAME_API, "JQ_GAME_API"),
            NEXTSPIN_GAME_API => $this->getGameApiDetails(NEXTSPIN_GAME_API,"NextSpin","NEXTSPIN_GAME","goto_common_game/".NEXTSPIN_GAME_API, "NEXTSPIN_GAME"),
            QT_HACKSAW_SEAMLESS_API => $this->getGameApiDetails(QT_HACKSAW_SEAMLESS_API,"QT Hacksaw","QT Hacksaw","goto_common_game", "QT Hacksaw"),
            PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(PARIPLAY_SEAMLESS_API,"PARIPLAY","PARIPLAY","goto_common_game/".PARIPLAY_SEAMLESS_API, "PARIPLAY"),
            LUCKY365_GAME_API => $this->getGameApiDetails(LUCKY365_GAME_API, "LUCKY365_GAME_API", "LUCKY365_GAME_API", "goto_common_game/" . LUCKY365_GAME_API, "LUCKY365_GAME_API"),
            LIONKING_GAME_API => $this->getGameApiDetails(LIONKING_GAME_API, "LIONKING_GAME_API", "LIONKING_GAME_API", "goto_common_game/" . LIONKING_GAME_API, "LIONKING_GAME_API"),
            EVOPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(EVOPLAY_SEAMLESS_GAME_API, "EVOPLAY_SEAMLESS_GAME_API", "EVOPLAY_SEAMLESS_GAME_API", "goto_common_game/" . EVOPLAY_SEAMLESS_GAME_API, "EVOPLAY_SEAMLESS_GAME_API"),
            IDNLIVE_SEAMLESS_GAME_API => $this->getGameApiDetails(IDNLIVE_SEAMLESS_GAME_API, "IDNLIVE_SEAMLESS_GAME_API", "IDNLIVE_SEAMLESS_GAME_API", "goto_common_game/" . IDNLIVE_SEAMLESS_GAME_API, "IDNLIVE_SEAMLESS_GAME_API"),
            HACKSAW_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(HACKSAW_PARIPLAY_SEAMLESS_API,"Hacksaw","Hacksaw","goto_common_game/".HACKSAW_PARIPLAY_SEAMLESS_API, "Hacksaw"),
            AMATIC_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(AMATIC_PARIPLAY_SEAMLESS_API,"Amatic","Amatic","goto_common_game/".AMATIC_PARIPLAY_SEAMLESS_API, "Amatic"),
            BEFEE_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(BEFEE_PARIPLAY_SEAMLESS_API,"BeeFee","BeeFee","goto_common_game/".BEFEE_PARIPLAY_SEAMLESS_API, "BeeFee"),
            OTG_GAMING_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(OTG_GAMING_PARIPLAY_SEAMLESS_API,"1X2","1X2","goto_common_game/".OTG_GAMING_PARIPLAY_SEAMLESS_API, "1X2"),
            HIGH5_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(HIGH5_PARIPLAY_SEAMLESS_API,"High5","High5","goto_common_game/".HIGH5_PARIPLAY_SEAMLESS_API, "High5"),
            PLAYSON_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(PLAYSON_PARIPLAY_SEAMLESS_API,"Playson","Playson","goto_common_game/".PLAYSON_PARIPLAY_SEAMLESS_API, "Playson"),
            ORYX_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(ORYX_PARIPLAY_SEAMLESS_API,"Oryx","Oryx","goto_common_game/".ORYX_PARIPLAY_SEAMLESS_API, "Oryx"),
            PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(PRAGMATICPLAY_SEAMLESS_API, "PragmaticPlay", "PRAGMATICPLAY", "goto_common_game", "PRAGMATICPLAY"),
            T1_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(T1_PRAGMATICPLAY_SEAMLESS_API, "T1_PRAGMATICPLAY_SEAMLESS_API", "T1_PRAGMATICPLAY_SEAMLESS_API", "goto_t1games", "T1_PRAGMATICPLAY_SEAMLESS_API"),
            // MPOKER_SEAMLESS_GAME_API => $this->getGameApiDetails(MPOKER_SEAMLESS_GAME_API,"MPoker Seamless","MPoker Seamless","goto_common_game/".MPOKER_SEAMLESS_GAME_API, "MPoker Seamless"),
            MPOKER_GAME_API => $this->getGameApiDetails(MPOKER_GAME_API,"MPoker","MPoker","goto_common_game/".MPOKER_GAME_API, "MPoker"),
            V8POKER_GAME_API => $this->getGameApiDetails(V8POKER_GAME_API,"V8Poker","V8Poker","goto_common_game/".V8POKER_GAME_API, "V8Poker"),
            PNG_SEAMLESS_GAME_API => $this->getGameApiDetails(PNG_SEAMLESS_GAME_API, "PNG", "PNG", "goto_common_game/" . PNG_SEAMLESS_GAME_API, "PNG"),
            CQ9_SEAMLESS_GAME_API => $this->getGameApiDetails(CQ9_SEAMLESS_GAME_API, "CQ9_SEAMLESS_GAME_API", "CQ9_SEAMLESS_GAME_API", "goto_common_game/" . CQ9_SEAMLESS_GAME_API, "CQ9_SEAMLESS_GAME_API"),
            T1_CQ9_SEAMLESS_API => $this->getGameApiDetails(T1_CQ9_SEAMLESS_API, "T1_CQ9_SEAMLESS_API", "T1_CQ9_SEAMLESS_API", "goto_t1games/" . T1_CQ9_SEAMLESS_API, "T1_CQ9_SEAMLESS_API"),
            T1_JUMBO_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_JUMBO_SEAMLESS_GAME_API, "T1_JUMBO_SEAMLESS_GAME_API", "T1_JUMBO_SEAMLESS_GAME_API", "goto_t1games/" . T1_JUMBO_SEAMLESS_GAME_API, "T1_JUMBO_SEAMLESS_GAME_API"),
            T1_BOOMING_SEAMLESS_API => $this->getGameApiDetails(T1_BOOMING_SEAMLESS_API,"T1_BOOMING_SEAMLESS_API","T1_BOOMING_SEAMLESS_API","goto_t1games", "T1_BOOMING_SEAMLESS_API"),
            T1_CHERRY_GAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_CHERRY_GAMING_SEAMLESS_GAME_API, "T1_CHERRY_GAMING_SEAMLESS_GAME_API", "T1_CHERRY_GAMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_CHERRY_GAMING_SEAMLESS_GAME_API, "T1_CHERRY_GAMING_SEAMLESS_GAME_API"),
            T1_BETER_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BETER_SEAMLESS_GAME_API, "T1_BETER_SEAMLESS_GAME_API", "T1_BETER_SEAMLESS_GAME_API", "goto_t1games/" . T1_BETER_SEAMLESS_GAME_API, "T1_BETER_SEAMLESS_GAME_API"),
            T1_BETER_SPORTS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BETER_SPORTS_SEAMLESS_GAME_API, "T1_BETER_SPORTS_SEAMLESS_GAME_API", "T1_BETER_SPORTS_SEAMLESS_GAME_API", "goto_t1games/" . T1_BETER_SPORTS_SEAMLESS_GAME_API, "T1_BETER_SPORTS_SEAMLESS_GAME_API"),
            T1_SV388_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SV388_SEAMLESS_GAME_API, "T1_SV388_SEAMLESS_GAME_API", "T1_SV388_SEAMLESS_GAME_API", "goto_t1games/" . T1_SV388_SEAMLESS_GAME_API, "T1_SV388_SEAMLESS_GAME_API"),
            BTI_SEAMLESS_GAME_API => $this->getGameApiDetails(BTI_SEAMLESS_GAME_API, "BTI_SEAMLESS_GAME_API", "BTI_SEAMLESS_GAME_API", "goto_common_game/" . BTI_SEAMLESS_GAME_API, "BTI_SEAMLESS_GAME_API"),
            DIGITAIN_SEAMLESS_API => $this->getGameApiDetails(DIGITAIN_SEAMLESS_API, "DIGITAIN_SEAMLESS_API", "DIGITAIN_SEAMLESS_API", "goto_common_game/" . DIGITAIN_SEAMLESS_API, "DIGITAIN_SEAMLESS_API"),
            SKYWIND_SEAMLESS_GAME_API => $this->getGameApiDetails(SKYWIND_SEAMLESS_GAME_API, "SKYWIND_SEAMLESS_GAME_API", "SKYWIND_SEAMLESS_GAME_API", "goto_common_game/" . SKYWIND_SEAMLESS_GAME_API, "SKYWIND_SEAMLESS_GAME_API"),
            KPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(KPLAY_SEAMLESS_GAME_API, "KPLAY_SEAMLESS_GAME_API", "KPLAY_SEAMLESS_GAME_API", "goto_common_game/" . KPLAY_SEAMLESS_GAME_API, "KPLAY_SEAMLESS_GAME_API"),
            KPLAY_EVO_SEAMLESS_GAME_API =>$this->getGameApiDetails(KPLAY_EVO_SEAMLESS_GAME_API,"KPLAY_EVO_SEAMLESS_GAME_API","KPLAY_EVO_SEAMLESS_GAME_API", "goto_common_game/".KPLAY_EVO_SEAMLESS_GAME_API, "KPLAY_EVO_SEAMLESS_GAME_API"),
            SOFTSWISS_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_SEAMLESS_GAME_API, "SOFTSWISS_SEAMLESS_GAME_API", "SOFTSWISS_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_SEAMLESS_GAME_API, "SOFTSWISS_SEAMLESS_GAME_API"),
            SOFTSWISS_BGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_BGAMING_SEAMLESS_GAME_API, "SOFTSWISS_BGAMING_SEAMLESS_GAME_API", "SOFTSWISS_BGAMING_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_BGAMING_SEAMLESS_GAME_API, "SOFTSWISS_BGAMING_SEAMLESS_GAME_API"),
            BGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(BGAMING_SEAMLESS_GAME_API, "BGAMING_SEAMLESS_GAME_API", "BGAMING_SEAMLESS_GAME_API", "goto_common_game/" . BGAMING_SEAMLESS_GAME_API, "BGAMING_SEAMLESS_GAME_API"),
            FBM_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(FBM_PARIPLAY_SEAMLESS_API, "FBM_PARIPLAY_SEAMLESS_API", "FBM_PARIPLAY_SEAMLESS_API", "goto_common_game/" . FBM_PARIPLAY_SEAMLESS_API, "FBM_PARIPLAY_SEAMLESS_API"),
            BOOMING_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(BOOMING_PARIPLAY_SEAMLESS_API, "BOOMING_PARIPLAY_SEAMLESS_API", "BOOMING_PARIPLAY_SEAMLESS_API", "goto_common_game/" . BOOMING_PARIPLAY_SEAMLESS_API, "BOOMING_PARIPLAY_SEAMLESS_API"),
            WAZDAN_SEAMLESS_GAME_API => $this->getGameApiDetails(WAZDAN_SEAMLESS_GAME_API, "WAZDAN_SEAMLESS_GAME_API", "WAZDAN_SEAMLESS_GAME_API", "goto_common_game/" . WAZDAN_SEAMLESS_GAME_API, "WAZDAN_SEAMLESS_GAME_API"),
            SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API, "SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API", "SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API, "SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API"),
            SOFTSWISS_SPRIBE_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_SPRIBE_SEAMLESS_GAME_API, "SOFTSWISS_SPRIBE_SEAMLESS_GAME_API", "SOFTSWISS_SPRIBE_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_SPRIBE_SEAMLESS_GAME_API, "SOFTSWISS_SPRIBE_SEAMLESS_GAME_API"),
            SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API, "SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API", "SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API, "SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API"),
            WE_SEAMLESS_GAME_API => $this->getGameApiDetails(WE_SEAMLESS_GAME_API, "WE_SEAMLESS_GAME_API", "WE_SEAMLESS_GAME_API", "goto_common_game/" . WE_SEAMLESS_GAME_API, "WE_SEAMLESS_GAME_API"),
            BIGPOT_SEAMLESS_GAME_API => $this->getGameApiDetails(BIGPOT_SEAMLESS_GAME_API, "BIGPOT_SEAMLESS_GAME_API", "BIGPOT_SEAMLESS_GAME_API", "goto_common_game/" . BIGPOT_SEAMLESS_GAME_API, "BIGPOT_SEAMLESS_GAME_API"),
            SOFTSWISS_BETSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_BETSOFT_SEAMLESS_GAME_API, "SOFTSWISS_BETSOFT_SEAMLESS_GAME_API", "SOFTSWISS_BETSOFT_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_BETSOFT_SEAMLESS_GAME_API, "SOFTSWISS_BETSOFT_SEAMLESS_GAME_API"),
            SOFTSWISS_WAZDAN_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_WAZDAN_SEAMLESS_GAME_API, "SOFTSWISS_WAZDAN_SEAMLESS_GAME_API", "SOFTSWISS_WAZDAN_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_WAZDAN_SEAMLESS_GAME_API, "SOFTSWISS_WAZDAN_SEAMLESS_GAME_API"),
            SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API, "SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API", "SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API, "SOFTSWISS_THUNDERKICK_SEAMLESS_GAME_API"),
            SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API, "SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API", "SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API", "goto_common_game/" . SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API, "SOFTSWISS_PUSHGAMING_SEAMLESS_GAME_API"),
            YL_NTTECH_SEAMLESS_GAME_API => $this->getGameApiDetails(YL_NTTECH_SEAMLESS_GAME_API, "YL_NTTECH_SEAMLESS_GAME_API", "YL_NTTECH_SEAMLESS_GAME_API", "goto_common_game/" . YL_NTTECH_SEAMLESS_GAME_API, "YL_NTTECH_SEAMLESS_GAME_API"),
            FC_SEAMLESS_GAME_API => $this->getGameApiDetails(FC_SEAMLESS_GAME_API, "FC_SEAMLESS_GAME_API", "FC_SEAMLESS_GAME_API", "goto_common_game/" . FC_SEAMLESS_GAME_API, "FC_SEAMLESS_GAME_API"),
            T1_BIGPOT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BIGPOT_SEAMLESS_GAME_API,"T1_BIGPOT_SEAMLESS_GAME_API","T1_BIGPOT_SEAMLESS_GAME_API","goto_t1games/".T1_BIGPOT_SEAMLESS_GAME_API, "T1_BIGPOT_SEAMLESS_GAME_API"),
            T1_PNG_SEAMLESS_API => $this->getGameApiDetails(T1_PNG_SEAMLESS_API,"T1_PNG_SEAMLESS_API","T1_PNG_SEAMLESS_API","goto_t1games/".T1_PNG_SEAMLESS_API, "T1_PNG_SEAMLESS_API"),
            AMEBA_SEAMLESS_GAME_API => $this->getGameApiDetails(AMEBA_SEAMLESS_GAME_API,"AMEBA_SEAMLESS_GAME_API","AMEBA_SEAMLESS_GAME_API","goto_common_game/".AMEBA_SEAMLESS_GAME_API, "AMEBA_SEAMLESS_GAME_API"),
            T1_AMEBA_SEAMLESS_API => $this->getGameApiDetails(T1_AMEBA_SEAMLESS_API,"T1_AMEBA_SEAMLESS_API","T1_AMEBA_SEAMLESS_API","goto_t1games/".T1_AMEBA_SEAMLESS_API, "T1_AMEBA_SEAMLESS_API"),
            T1_BGSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BGSOFT_SEAMLESS_GAME_API,"T1_BGSOFT_SEAMLESS_GAME_API","T1_BGSOFT_SEAMLESS_GAME_API","goto_t1games/".T1_BGSOFT_SEAMLESS_GAME_API, "T1_BGSOFT_SEAMLESS_GAME_API"),
            T1_MGPLUS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_MGPLUS_SEAMLESS_GAME_API,"T1_MGPLUS_SEAMLESS_GAME_API","T1_MGPLUS_SEAMLESS_GAME_API","goto_t1games", "T1_MGPLUS_SEAMLESS_GAME_API"),
            T1_YL_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_YL_SEAMLESS_GAME_API, "T1_YL_SEAMLESS_GAME_API", "T1_YL_SEAMLESS_GAME_API", "goto_t1games/" . T1_YL_SEAMLESS_GAME_API, "T1_YL_SEAMLESS_GAME_API"),
            T1_SKYWIND_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SKYWIND_SEAMLESS_GAME_API,"T1_SKYWIND_SEAMLESS_GAME_API","T1_SKYWIND_SEAMLESS_GAME_API","goto_t1games/".T1_SKYWIND_SEAMLESS_GAME_API, "T1_SKYWIND_SEAMLESS_GAME_API"),
            T1_PGSOFT_SEAMLESS_API => $this->getGameApiDetails(T1_PGSOFT_SEAMLESS_API,"T1_PGSOFT_SEAMLESS_API","T1_PGSOFT_SEAMLESS_API","goto_t1games/".T1_PGSOFT_SEAMLESS_API, "T1_PGSOFT_SEAMLESS_API"),
            T1_PGSOFT2_SEAMLESS_API => $this->getGameApiDetails(T1_PGSOFT2_SEAMLESS_API,"T1_PGSOFT2_SEAMLESS_API","T1_PGSOFT2_SEAMLESS_API","goto_t1games/".T1_PGSOFT2_SEAMLESS_API, "T1_PGSOFT2_SEAMLESS_API"),
            T1_PGSOFT3_SEAMLESS_API => $this->getGameApiDetails(T1_PGSOFT3_SEAMLESS_API,"T1_PGSOFT3_SEAMLESS_API","T1_PGSOFT3_SEAMLESS_API","goto_t1games/".T1_PGSOFT3_SEAMLESS_API, "T1_PGSOFT3_SEAMLESS_API"),
            TRIPLECHERRY_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(TRIPLECHERRY_PARIPLAY_SEAMLESS_API, "TRIPLECHERRY_PARIPLAY_SEAMLESS_API", "TRIPLECHERRY_PARIPLAY_SEAMLESS_API", "goto_common_game/" . TRIPLECHERRY_PARIPLAY_SEAMLESS_API, "TRIPLECHERRY_PARIPLAY_SEAMLESS_API"),
            TADA_SEAMLESS_GAME_API => $this->getGameApiDetails(TADA_SEAMLESS_GAME_API, "TADA_SEAMLESS_GAME_API", "TADA_SEAMLESS_GAME_API", "goto_common_game/" . TADA_SEAMLESS_GAME_API, "TADA_SEAMLESS_GAME_API"),
            T1_TADA_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_TADA_SEAMLESS_GAME_API, "T1_TADA_SEAMLESS_GAME_API", "T1_TADA_SEAMLESS_GAME_API", "goto_t1games/" . T1_TADA_SEAMLESS_GAME_API, "T1_TADA_SEAMLESS_GAME_API"),
            DARWIN_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(DARWIN_PARIPLAY_SEAMLESS_API, "DARWIN_PARIPLAY_SEAMLESS_API", "DARWIN_PARIPLAY_SEAMLESS_API", "goto_common_game/" . DARWIN_PARIPLAY_SEAMLESS_API, "DARWIN_PARIPLAY_SEAMLESS_API"),
            SPINOMENAL_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(SPINOMENAL_PARIPLAY_SEAMLESS_API, "SPINOMENAL_PARIPLAY_SEAMLESS_API", "SPINOMENAL_PARIPLAY_SEAMLESS_API", "goto_common_game/" . SPINOMENAL_PARIPLAY_SEAMLESS_API, "SPINOMENAL_PARIPLAY_SEAMLESS_API"),
            GFG_SEAMLESS_GAME_API => $this->getGameApiDetails(GFG_SEAMLESS_GAME_API, "GFG_SEAMLESS_GAME_API", "GFG_SEAMLESS_GAME_API", "goto_common_game/" . GFG_SEAMLESS_GAME_API, "GFG_SEAMLESS_GAME_API"),
            T1_EZUGI_EVO_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EZUGI_EVO_SEAMLESS_GAME_API,"T1_EZUGI_EVO_SEAMLESS_GAME_API","T1_EZUGI_EVO_SEAMLESS_GAME_API","goto_t1games/".T1_EZUGI_EVO_SEAMLESS_GAME_API, "T1_EZUGI_EVO_SEAMLESS_GAME_API"),

            T1_FC_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_FC_SEAMLESS_GAME_API,"T1_FC_SEAMLESS_GAME_API","T1_FC_SEAMLESS_GAME_API","goto_t1games/".T1_FC_SEAMLESS_GAME_API, "T1_FC_SEAMLESS_GAME_API"),
            T1_JILI_SEAMLESS_API => $this->getGameApiDetails(T1_JILI_SEAMLESS_API,"T1_JILI_SEAMLESS_API","T1_JILI_SEAMLESS_API","goto_t1games/".T1_JILI_SEAMLESS_API, "T1_JILI_SEAMLESS_API"),

            T1_EZUGI_NETENT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EZUGI_NETENT_SEAMLESS_GAME_API,"T1_EZUGI_NETENT_SEAMLESS_GAME_API","T1_EZUGI_NETENT_SEAMLESS_GAME_API","goto_t1games/" . T1_EZUGI_NETENT_SEAMLESS_GAME_API, "T1_EZUGI_NETENT_SEAMLESS_GAME_API"),
            T1_GFG_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_GFG_SEAMLESS_GAME_API,"T1_GFG_SEAMLESS_GAME_API","T1_GFG_SEAMLESS_GAME_API","goto_t1games/".T1_GFG_SEAMLESS_GAME_API, "T1_GFG_SEAMLESS_GAME_API"),
            SPADEGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(SPADEGAMING_SEAMLESS_GAME_API, "SPADEGAMING_SEAMLESS_GAME_API", "SPADEGAMING_SEAMLESS_GAME_API", "goto_common_game/" . SPADEGAMING_SEAMLESS_GAME_API, "SPADEGAMING_SEAMLESS_GAME_API"),
            T1_SPADEGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SPADEGAMING_SEAMLESS_GAME_API, "T1_SPADEGAMING_SEAMLESS_GAME_API", "T1_SPADEGAMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_SPADEGAMING_SEAMLESS_GAME_API, "T1_SPADEGAMING_SEAMLESS_GAME_API"),
            T1_EVOPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_EVOPLAY_SEAMLESS_GAME_API, "T1_EVOPLAY_SEAMLESS_GAME_API", "T1_EVOPLAY_SEAMLESS_GAME_API", "goto_t1games/" . T1_EVOPLAY_SEAMLESS_GAME_API, "T1_EVOPLAY_SEAMLESS_GAME_API"),
            T1_BTI_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BTI_SEAMLESS_GAME_API,"T1_BTI_SEAMLESS_GAME_API","T1_BTI_SEAMLESS_GAME_API","goto_t1games/".T1_BTI_SEAMLESS_GAME_API, "T1_BTI_SEAMLESS_GAME_API"),
            T1_BGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BGAMING_SEAMLESS_GAME_API, "T1_BGAMING_SEAMLESS_GAME_API", "T1_BGAMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_BGAMING_SEAMLESS_GAME_API, "T1_BGAMING_SEAMLESS_GAME_API"),
            BOOMING_SEAMLESS_GAME_API => $this->getGameApiDetails(BOOMING_SEAMLESS_GAME_API, "BOOMING_SEAMLESS_GAME_API", "BOOMING_SEAMLESS_GAME_API", "goto_common_game/" . BOOMING_SEAMLESS_GAME_API, "BOOMING_SEAMLESS_GAME_API"),
            T1_BOOMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BOOMING_SEAMLESS_GAME_API, "T1_BOOMING_SEAMLESS_GAME_API", "T1_BOOMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_BOOMING_SEAMLESS_GAME_API, "T1_BOOMING_SEAMLESS_GAME_API"),
            T1_SBOBET_SEAMLESS_API => $this->getGameApiDetails(T1_SBOBET_SEAMLESS_API, "T1_SBOBET_SEAMLESS_API", "T1_SBOBET_SEAMLESS_API", "goto_t1games/" . T1_SBOBET_SEAMLESS_API, "T1_SBOBET_SEAMLESS_API"),
            T1_CALETA_SEAMLESS_API => $this->getGameApiDetails(T1_CALETA_SEAMLESS_API, "T1_CALETA_SEAMLESS_API", "T1_CALETA_SEAMLESS_API", "goto_t1games/" . T1_CALETA_SEAMLESS_API, "T1_CALETA_SEAMLESS_API"),
            CMD_SEAMLESS_GAME_API => $this->getGameApiDetails(CMD_SEAMLESS_GAME_API, "CMD_SEAMLESS_GAME_API", "CMD_SEAMLESS_GAME_API", "goto_common_game/" . CMD_SEAMLESS_GAME_API, "CMD_SEAMLESS_GAME_API"),
            CMD2_SEAMLESS_GAME_API => $this->getGameApiDetails(CMD2_SEAMLESS_GAME_API, "CMD2_SEAMLESS_GAME_API", "CMD2_SEAMLESS_GAME_API", "goto_common_game/" . CMD2_SEAMLESS_GAME_API, "CMD2_SEAMLESS_GAME_API"),
            T1_CMD_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_CMD_SEAMLESS_GAME_API, "T1_CMD_SEAMLESS_GAME_API", "T1_CMD_SEAMLESS_GAME_API", "goto_t1games/" . T1_CMD_SEAMLESS_GAME_API, "T1_CMD_SEAMLESS_GAME_API"),
            T1_CMD2_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_CMD2_SEAMLESS_GAME_API, "T1_CMD2_SEAMLESS_GAME_API", "T1_CMD2_SEAMLESS_GAME_API", "goto_t1games/" . T1_CMD2_SEAMLESS_GAME_API, "T1_CMD2_SEAMLESS_GAME_API"),
            BETBY_SEAMLESS_GAME_API => $this->getGameApiDetails(BETBY_SEAMLESS_GAME_API, "BETBY_SEAMLESS_GAME_API", "BETBY_SEAMLESS_GAME_API", "goto_common_game/" . BETBY_SEAMLESS_GAME_API, "BETBY_SEAMLESS_GAME_API"),
            SMARTSOFT_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(SMARTSOFT_PARIPLAY_SEAMLESS_API, "SMARTSOFT_PARIPLAY_SEAMLESS_API", "SMARTSOFT_PARIPLAY_SEAMLESS_API", "goto_common_game/" . SMARTSOFT_PARIPLAY_SEAMLESS_API, "SMARTSOFT_PARIPLAY_SEAMLESS_API"),
            SV388_AWC_SEAMLESS_GAME_API => $this->getGameApiDetails(SV388_AWC_SEAMLESS_GAME_API, 'SV388_AWC_SEAMLESS_GAME_API', 'SV388_AWC_SEAMLESS_GAME_API', 'goto_common_game/' . SV388_AWC_SEAMLESS_GAME_API, 'SV388_AWC_SEAMLESS_GAME_API'),
            T1_SV388_AWC_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SV388_AWC_SEAMLESS_GAME_API, "T1_SV388_AWC_SEAMLESS_GAME_API", "T1_SV388_AWC_SEAMLESS_GAME_API", "goto_t1games/" . T1_SV388_AWC_SEAMLESS_GAME_API, "T1_SV388_AWC_SEAMLESS_GAME_API"),
            AFB_SBOBET_SEAMLESS_GAME_API => $this->getGameApiDetails(AFB_SBOBET_SEAMLESS_GAME_API, 'AFB_SBOBET_SEAMLESS_GAME_API', 'AFB_SBOBET_SEAMLESS_GAME_API', 'goto_common_game/' . AFB_SBOBET_SEAMLESS_GAME_API, 'AFB_SBOBET_SEAMLESS_GAME_API'),
            T1_AFB_SBOBET_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_AFB_SBOBET_SEAMLESS_GAME_API, "T1_AFB_SBOBET_SEAMLESS_GAME_API", "T1_AFB_SBOBET_SEAMLESS_GAME_API", "goto_t1games/" . T1_AFB_SBOBET_SEAMLESS_GAME_API, "T1_AFB_SBOBET_SEAMLESS_GAME_API"),
            SPRIBE_JUMBO_SEAMLESS_GAME_API => $this->getGameApiDetails(SPRIBE_JUMBO_SEAMLESS_GAME_API, 'SPRIBE_JUMBO_SEAMLESS_GAME_API', 'SPRIBE_JUMBO_SEAMLESS_GAME_API', 'goto_common_game/' . SPRIBE_JUMBO_SEAMLESS_GAME_API, 'SPRIBE_JUMBO_SEAMLESS_GAME_API'),
            T1_SPRIBE_JUMBO_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SPRIBE_JUMBO_SEAMLESS_GAME_API, "T1_SPRIBE_JUMBO_SEAMLESS_GAME_API", "T1_SPRIBE_JUMBO_SEAMLESS_GAME_API", "goto_t1games/" . T1_SPRIBE_JUMBO_SEAMLESS_GAME_API, "T1_SPRIBE_JUMBO_SEAMLESS_GAME_API"),
            YGG_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(YGG_DCS_SEAMLESS_GAME_API, 'YGG_DCS_SEAMLESS_GAME_API', 'YGG_DCS_SEAMLESS_GAME_API', 'goto_common_game/' . YGG_DCS_SEAMLESS_GAME_API, 'YGG_DCS_SEAMLESS_GAME_API'),
            T1_YGG_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_YGG_DCS_SEAMLESS_GAME_API, "T1_YGG_DCS_SEAMLESS_GAME_API", "T1_YGG_DCS_SEAMLESS_GAME_API", "goto_t1games/" . T1_YGG_DCS_SEAMLESS_GAME_API, "T1_YGG_DCS_SEAMLESS_GAME_API"),
            T1_WE_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_WE_SEAMLESS_GAME_API, "T1_WE_SEAMLESS_GAME_API", "T1_WE_SEAMLESS_GAME_API", "goto_t1games/" . T1_WE_SEAMLESS_GAME_API, "T1_WE_SEAMLESS_GAME_API"),
            HACKSAW_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(HACKSAW_DCS_SEAMLESS_GAME_API, 'HACKSAW_DCS_SEAMLESS_GAME_API', 'HACKSAW_DCS_SEAMLESS_GAME_API', 'goto_common_game/' . HACKSAW_DCS_SEAMLESS_GAME_API, 'HACKSAW_DCS_SEAMLESS_GAME_API'),
            T1_HACKSAW_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_HACKSAW_DCS_SEAMLESS_GAME_API, "T1_HACKSAW_DCS_SEAMLESS_GAME_API", "T1_HACKSAW_DCS_SEAMLESS_GAME_API", "goto_t1games/" . T1_HACKSAW_DCS_SEAMLESS_GAME_API, "T1_HACKSAW_DCS_SEAMLESS_GAME_API"),
            T1_QT_HACKSAW_SEAMLESS_API => $this->getGameApiDetails(T1_QT_HACKSAW_SEAMLESS_API, "T1_QT_HACKSAW_SEAMLESS_API", "T1_QT_HACKSAW_SEAMLESS_API", "goto_t1games/" . T1_QT_HACKSAW_SEAMLESS_API, "T1_QT_HACKSAW_SEAMLESS_API"),
            QT_NOLIMITCITY_SEAMLESS_API => $this->getGameApiDetails(QT_NOLIMITCITY_SEAMLESS_API, 'QT_NOLIMITCITY_SEAMLESS_API', 'QT_NOLIMITCITY_SEAMLESS_API', 'goto_common_game/' . QT_NOLIMITCITY_SEAMLESS_API, 'QT_NOLIMITCITY_SEAMLESS_API'),
            T1_QT_NOLIMITCITY_SEAMLESS_API => $this->getGameApiDetails(T1_QT_NOLIMITCITY_SEAMLESS_API, "T1_QT_NOLIMITCITY_SEAMLESS_API", "T1_QT_NOLIMITCITY_SEAMLESS_API", "goto_t1games/" . T1_QT_NOLIMITCITY_SEAMLESS_API, "T1_QT_NOLIMITCITY_SEAMLESS_API"),
            T1_VIVOGAMING_SEAMLESS_API => $this->getGameApiDetails(T1_VIVOGAMING_SEAMLESS_API, "T1_VIVOGAMING_SEAMLESS_API", "T1_VIVOGAMING_SEAMLESS_API", "goto_t1games/" . T1_VIVOGAMING_SEAMLESS_API, "T1_VIVOGAMING_SEAMLESS_API"),
            JILI_GAME_API => $this->getGameApiDetails(JILI_GAME_API,"JILI","JILI","goto_common_game/".JILI_GAME_API, "JILI_GAME_API"),
            MIKI_WORLDS_GAME_API => $this->getGameApiDetails(MIKI_WORLDS_GAME_API, "MIKI_WORLDS_GAME_API", "MIKI_WORLDS_GAME_API", "goto_common_game/" . MIKI_WORLDS_GAME_API, "MIKI_WORLDS_GAME_API"),
            BETIXON_SEAMLESS_GAME_API => $this->getGameApiDetails(BETIXON_SEAMLESS_GAME_API, "BETIXON_SEAMLESS_GAME_API", "BETIXON_SEAMLESS_GAME_API", "goto_common_game/" . BETIXON_SEAMLESS_GAME_API, "BETIXON_SEAMLESS_GAME_API"),
            T1_BETIXON_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BETIXON_SEAMLESS_GAME_API, "T1_BETIXON_SEAMLESS_GAME_API", "T1_BETIXON_SEAMLESS_GAME_API", "goto_t1games/" . T1_BETIXON_SEAMLESS_GAME_API, "T1_BETIXON_SEAMLESS_GAME_API"),
            KING_MAKER_SEAMLESS_GAME_API => $this->getGameApiDetails(KING_MAKER_SEAMLESS_GAME_API, "KING_MAKER_SEAMLESS_GAME_API", "KING_MAKER_SEAMLESS_GAME_API", "goto_common_game/" . KING_MAKER_SEAMLESS_GAME_API, "KING_MAKER_SEAMLESS_GAME_API"),
            T1_KING_MAKER_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_KING_MAKER_SEAMLESS_GAME_API, "T1_KING_MAKER_SEAMLESS_GAME_API", "T1_KING_MAKER_SEAMLESS_GAME_API", "goto_t1games/" . T1_KING_MAKER_SEAMLESS_GAME_API, "T1_KING_MAKER_SEAMLESS_GAME_API"),
            MGW_SEAMLESS_GAME_API => $this->getGameApiDetails(MGW_SEAMLESS_GAME_API, "MGW_SEAMLESS_GAME_API", "MGW_SEAMLESS_GAME_API", "goto_common_game/" . MGW_SEAMLESS_GAME_API, "MGW_SEAMLESS_GAME_API"),
            YEEBET_SEAMLESS_GAME_API => $this->getGameApiDetails(YEEBET_SEAMLESS_GAME_API,"Yeebet","Yeebet","goto_common_game/".YEEBET_SEAMLESS_GAME_API, "Yeebet"),
            T1_YEEBET_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_YEEBET_SEAMLESS_GAME_API,"Yeebet","Yeebet","goto_t1games/".T1_YEEBET_SEAMLESS_GAME_API, "Yeebet"),
            IM_SEAMLESS_GAME_API => $this->getGameApiDetails(IM_SEAMLESS_GAME_API, "IM_SEAMLESS_GAME_API", "IM_SEAMLESS_GAME_API", "goto_common_game/" . IM_SEAMLESS_GAME_API, "IM_SEAMLESS_GAME_API"),
            T1_IM_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IM_SEAMLESS_GAME_API, "T1_IM_SEAMLESS_GAME_API", "T1_IM_SEAMLESS_GAME_API", "goto_t1games/" . T1_IM_SEAMLESS_GAME_API, "T1_IM_SEAMLESS_GAME_API"),
            T1_MGW_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_MGW_SEAMLESS_GAME_API, "T1_MGW_SEAMLESS_GAME_API", "T1_MGW_SEAMLESS_GAME_API", "goto_t1games/" . T1_MGW_SEAMLESS_GAME_API, "T1_MGW_SEAMLESS_GAME_API"),
            SPINOMENAL_SEAMLESS_GAME_API => $this->getGameApiDetails(SPINOMENAL_SEAMLESS_GAME_API, "SPINOMENAL_SEAMLESS_GAME_API", "SPINOMENAL_SEAMLESS_GAME_API", "goto_common_game/" . SPINOMENAL_SEAMLESS_GAME_API, "SPINOMENAL_SEAMLESS_GAME_API"),
            T1_SPINOMENAL_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SPINOMENAL_SEAMLESS_GAME_API, "T1_SPINOMENAL_SEAMLESS_GAME_API", "T1_SPINOMENAL_SEAMLESS_GAME_API", "goto_t1games/" . T1_SPINOMENAL_SEAMLESS_GAME_API, "T1_SPINOMENAL_SEAMLESS_GAME_API"),
            SMARTSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(SMARTSOFT_SEAMLESS_GAME_API, "SMARTSOFT_SEAMLESS_GAME_API", "SMARTSOFT_SEAMLESS_GAME_API", "goto_common_game/" . SMARTSOFT_SEAMLESS_GAME_API, "SMARTSOFT_SEAMLESS_GAME_API"),
            T1_SMARTSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SMARTSOFT_SEAMLESS_GAME_API, "T1_SMARTSOFT_SEAMLESS_GAME_API", "T1_SMARTSOFT_SEAMLESS_GAME_API", "goto_t1games/" . T1_SMARTSOFT_SEAMLESS_GAME_API, "T1_SMARTSOFT_SEAMLESS_GAME_API"),
            WON_CASINO_SEAMLESS_GAME_API => $this->getGameApiDetails(WON_CASINO_SEAMLESS_GAME_API,"Won Casino","Won Casino","goto_common_game/".WON_CASINO_SEAMLESS_GAME_API, "Won Casino"),
            T1_WON_CASINO_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_WON_CASINO_SEAMLESS_GAME_API,"Won Casino","Won Casino","goto_t1games/".T1_WON_CASINO_SEAMLESS_GAME_API, "Won Casino"),
            ASTAR_SEAMLESS_GAME_API => $this->getGameApiDetails(ASTAR_SEAMLESS_GAME_API, "ASTAR_SEAMLESS_GAME_API", "ASTAR_SEAMLESS_GAME_API", "goto_common_game/" . ASTAR_SEAMLESS_GAME_API, "ASTAR_SEAMLESS_GAME_API"),
            T1_ASTAR_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_ASTAR_SEAMLESS_GAME_API, "T1_ASTAR_SEAMLESS_GAME_API", "T1_ASTAR_SEAMLESS_GAME_API", "goto_t1games/" . T1_ASTAR_SEAMLESS_GAME_API, "T1_ASTAR_SEAMLESS_GAME_API"),
            BETGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(BETGAMES_SEAMLESS_GAME_API, "BETGAMES_SEAMLESS_GAME_API", "BETGAMES_SEAMLESS_GAME_API", "goto_common_game/" . BETGAMES_SEAMLESS_GAME_API, "BETGAMES_SEAMLESS_GAME_API"),
            T1_BETGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BETGAMES_SEAMLESS_GAME_API, "T1_BETGAMES_SEAMLESS_GAME_API", "T1_BETGAMES_SEAMLESS_GAME_API", "goto_t1games/" . T1_BETGAMES_SEAMLESS_GAME_API, "T1_BETGAMES_SEAMLESS_GAME_API"),
            TWAIN_SEAMLESS_GAME_API => $this->getGameApiDetails(TWAIN_SEAMLESS_GAME_API, "TWAIN_SEAMLESS_GAME_API", "TWAIN_SEAMLESS_GAME_API", "goto_common_game/" . TWAIN_SEAMLESS_GAME_API, "TWAIN_SEAMLESS_GAME_API"),
            T1_TWAIN_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_TWAIN_SEAMLESS_GAME_API, "T1_TWAIN_SEAMLESS_GAME_API", "T1_TWAIN_SEAMLESS_GAME_API", "goto_t1games/" . T1_TWAIN_SEAMLESS_GAME_API, "T1_TWAIN_SEAMLESS_GAME_API"),
            HP_LOTTERY_GAME_API => $this->getGameApiDetails(HP_LOTTERY_GAME_API, "HP_LOTTERY_GAME_API", "HP_LOTTERY_GAME_API", "goto_common_game/" . HP_LOTTERY_GAME_API, "HP_LOTTERY_GAME_API"),
            NTTECH_V3_API => $this->getGameApiDetails(NTTECH_V3_API, "NTTECH_V3_API", "NTTECH_V3_API", "goto_common_game/" . NTTECH_V3_API, "NTTECH_V3_API"),
            NEX4D_GAME_API => $this->getGameApiDetails(NEX4D_GAME_API,"NEX4D_GAME_API","NEX4D_GAME_API","goto_common_game/".NEX4D_GAME_API, "NEX4D_GAME_API"),
            T1_FLOW_GAMING_SEAMLESS_API => $this->getGameApiDetails(T1_FLOW_GAMING_SEAMLESS_API, "T1_FLOW_GAMING_SEAMLESS_API", "T1_FLOW_GAMING_SEAMLESS_API", "goto_t1games/" . T1_FLOW_GAMING_SEAMLESS_API, "T1_FLOW_GAMING_SEAMLESS_API"),
            T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API => $this->getGameApiDetails(T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API, "T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API", "T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API", "goto_t1games/" . T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API, "T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API"),
            AVATAR_UX_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(AVATAR_UX_DCS_SEAMLESS_GAME_API, 'AVATAR_UX_DCS_SEAMLESS_GAME_API', 'AVATAR_UX_DCS_SEAMLESS_GAME_API', 'goto_common_game/' . AVATAR_UX_DCS_SEAMLESS_GAME_API, 'AVATAR_UX_DCS_SEAMLESS_GAME_API'),
            T1_AVATAR_UX_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_AVATAR_UX_DCS_SEAMLESS_GAME_API, "T1_AVATAR_UX_DCS_SEAMLESS_GAME_API", "T1_AVATAR_UX_DCS_SEAMLESS_GAME_API", "goto_t1games/" . T1_AVATAR_UX_DCS_SEAMLESS_GAME_API, "T1_AVATAR_UX_DCS_SEAMLESS_GAME_API"),
            WGB_GAME_API => $this->getGameApiDetails(WGB_GAME_API,"WGB_GAME_API","WGB_GAME_API","goto_common_game/".WGB_GAME_API, "WGB_GAME_API"),
            WCC_GAME_API => $this->getGameApiDetails(WCC_GAME_API, "WCC_GAME_API", "WCC_GAME_API", "goto_common_game/" . WCC_GAME_API, "WCC_GAME_API"),
            HACKSAW_SEAMLESS_GAME_API => $this->getGameApiDetails(HACKSAW_SEAMLESS_GAME_API, 'HACKSAW_SEAMLESS_GAME_API', 'HACKSAW_SEAMLESS_GAME_API', 'goto_common_game/' . HACKSAW_SEAMLESS_GAME_API, 'HACKSAW_SEAMLESS_GAME_API'),
            T1_HACKSAW_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_HACKSAW_SEAMLESS_GAME_API, "T1_HACKSAW_SEAMLESS_GAME_API", "T1_HACKSAW_SEAMLESS_GAME_API", "goto_t1games/" . T1_HACKSAW_SEAMLESS_GAME_API, "T1_HACKSAW_SEAMLESS_GAME_API"),
            RELAX_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(RELAX_DCS_SEAMLESS_GAME_API, 'RELAX_DCS_SEAMLESS_GAME_API', 'RELAX_DCS_SEAMLESS_GAME_API', 'goto_common_game/' . RELAX_DCS_SEAMLESS_GAME_API, 'RELAX_DCS_SEAMLESS_GAME_API'),
            T1_RELAX_DCS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_RELAX_DCS_SEAMLESS_GAME_API, "T1_RELAX_DCS_SEAMLESS_GAME_API", "T1_RELAX_DCS_SEAMLESS_GAME_API", "goto_t1games/" . T1_RELAX_DCS_SEAMLESS_GAME_API, "T1_RELAX_DCS_SEAMLESS_GAME_API"),
            T1_ENDORPHINA_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_ENDORPHINA_SEAMLESS_GAME_API, "T1_ENDORPHINA_SEAMLESS_GAME_API", "T1_ENDORPHINA_SEAMLESS_GAME_API", "goto_t1games/" . T1_ENDORPHINA_SEAMLESS_GAME_API, "T1_ENDORPHINA_SEAMLESS_GAME_API"),
            ENDORPHINA_SEAMLESS_GAME_API => $this->getGameApiDetails(ENDORPHINA_SEAMLESS_GAME_API, 'ENDORPHINA_SEAMLESS_GAME_API', 'ENDORPHINA_SEAMLESS_GAME_API', 'goto_common_game/' . ENDORPHINA_SEAMLESS_GAME_API, 'ENDORPHINA_SEAMLESS_GAME_API'),
            BELATRA_SEAMLESS_GAME_API => $this->getGameApiDetails(BELATRA_SEAMLESS_GAME_API,"Belatra","Belatra","goto_common_game/".BELATRA_SEAMLESS_GAME_API, "Belatra"),
            T1_BELATRA_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BELATRA_SEAMLESS_GAME_API, "T1_BELATRA_SEAMLESS_GAME_API", "T1_BELATRA_SEAMLESS_GAME_API", "goto_t1games/" . T1_BELATRA_SEAMLESS_GAME_API, "T1_BELATRA_SEAMLESS_GAME_API"),
            SPRIBE_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(SPRIBE_PARIPLAY_SEAMLESS_API, "SPRIBE_PARIPLAY_SEAMLESS_API", "SPRIBE_PARIPLAY_SEAMLESS_API", "goto_common_game/" . SPRIBE_PARIPLAY_SEAMLESS_API, "SPRIBE_PARIPLAY_SEAMLESS_API"),
            NEXTSPIN_SEAMLESS_GAME_API => $this->getGameApiDetails(NEXTSPIN_SEAMLESS_GAME_API, "NEXTSPIN_SEAMLESS_GAME_API", "NEXTSPIN_SEAMLESS_GAME_API", "goto_common_game/" . NEXTSPIN_SEAMLESS_GAME_API, "NEXTSPIN_SEAMLESS_GAME_API"),
            T1_NEXTSPIN_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_NEXTSPIN_SEAMLESS_GAME_API, "T1_NEXTSPIN_SEAMLESS_GAME_API", "T1_NEXTSPIN_SEAMLESS_GAME_API", "goto_t1games/" . T1_NEXTSPIN_SEAMLESS_GAME_API, "T1_NEXTSPIN_SEAMLESS_GAME_API"),
            T1_PEGASUS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_PEGASUS_SEAMLESS_GAME_API, "T1_PEGASUS_SEAMLESS_GAME_API", "T1_PEGASUS_SEAMLESS_GAME_API", "goto_t1games/" . T1_PEGASUS_SEAMLESS_GAME_API, "T1_PEGASUS_SEAMLESS_GAME_API"),
            PEGASUS_SEAMLESS_GAME_API => $this->getGameApiDetails(PEGASUS_SEAMLESS_GAME_API, 'PEGASUS_SEAMLESS_GAME_API', 'PEGASUS_SEAMLESS_GAME_API', 'goto_common_game/' . PEGASUS_SEAMLESS_GAME_API, 'PEGASUS_SEAMLESS_GAME_API'),
            BNG_SEAMLESS_GAME_API => $this->getGameApiDetails(BNG_SEAMLESS_GAME_API, 'BNG_SEAMLESS_GAME_API', 'BNG_SEAMLESS_GAME_API', 'goto_common_game/' . BNG_SEAMLESS_GAME_API, 'BNG_SEAMLESS_GAME_API'),
            T1_BNG_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BNG_SEAMLESS_GAME_API, "T1_BNG_SEAMLESS_GAME_API", "T1_BNG_SEAMLESS_GAME_API", "goto_t1games/" . T1_BNG_SEAMLESS_GAME_API, "T1_BNG_SEAMLESS_GAME_API"),
            T1_WAZDAN_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_WAZDAN_SEAMLESS_GAME_API, "T1_WAZDAN_SEAMLESS_GAME_API", "T1_WAZDAN_SEAMLESS_GAME_API", "goto_t1games/" . T1_WAZDAN_SEAMLESS_GAME_API, "T1_WAZDAN_SEAMLESS_GAME_API"),
            SPINIX_SEAMLESS_GAME_API => $this->getGameApiDetails(SPINIX_SEAMLESS_GAME_API, 'SPINIX_SEAMLESS_GAME_API', 'SPINIX_SEAMLESS_GAME_API', 'goto_common_game/' . SPINIX_SEAMLESS_GAME_API, 'SPINIX_SEAMLESS_GAME_API'),
            T1_SPINIX_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_SPINIX_SEAMLESS_GAME_API, "T1_SPINIX_SEAMLESS_GAME_API", "T1_SPINIX_SEAMLESS_GAME_API", "goto_t1games/" . T1_SPINIX_SEAMLESS_GAME_API, "T1_SPINIX_SEAMLESS_GAME_API"),
            T1_FASTSPIN_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_FASTSPIN_SEAMLESS_GAME_API, "T1_FASTSPIN_SEAMLESS_GAME_API", "T1_FASTSPIN_SEAMLESS_GAME_API", "goto_t1games/" . T1_FASTSPIN_SEAMLESS_GAME_API, "T1_FASTSPIN_SEAMLESS_GAME_API"),
            FASTSPIN_SEAMLESS_GAME_API => $this->getGameApiDetails(FASTSPIN_SEAMLESS_GAME_API, 'FASTSPIN_SEAMLESS_GAME_API', 'FASTSPIN_SEAMLESS_GAME_API', 'goto_common_game/' . FASTSPIN_SEAMLESS_GAME_API, 'FASTSPIN_SEAMLESS_GAME_API'),
            RTG_SEAMLESS_GAME_API => $this->getGameApiDetails(RTG_SEAMLESS_GAME_API, 'RTG_SEAMLESS_GAME_API', 'RTG_SEAMLESS_GAME_API', 'goto_common_game/' . RTG_SEAMLESS_GAME_API, 'RTG_SEAMLESS_GAME_API'),
            T1_RTG_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_RTG_SEAMLESS_GAME_API, "T1_RTG_SEAMLESS_GAME_API", "T1_RTG_SEAMLESS_GAME_API", "goto_t1games/" . T1_RTG_SEAMLESS_GAME_API, "T1_RTG_SEAMLESS_GAME_API"),
            SPINMATIC_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(SPINMATIC_PARIPLAY_SEAMLESS_API, "SPINMATIC_PARIPLAY_SEAMLESS_API", "SPINMATIC_PARIPLAY_SEAMLESS_API", "goto_common_game/" . SPINMATIC_PARIPLAY_SEAMLESS_API, "SPINMATIC_PARIPLAY_SEAMLESS_API"),
            DRAGOONSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(DRAGOONSOFT_SEAMLESS_GAME_API, 'DRAGOONSOFT_SEAMLESS_GAME_API', 'DRAGOONSOFT_SEAMLESS_GAME_API', 'goto_common_game/' . DRAGOONSOFT_SEAMLESS_GAME_API, 'DRAGOONSOFT_SEAMLESS_GAME_API'),
            T1_DRAGOONSOFT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_DRAGOONSOFT_SEAMLESS_GAME_API, "T1_DRAGOONSOFT_SEAMLESS_GAME_API", "T1_DRAGOONSOFT_SEAMLESS_GAME_API", "goto_t1games/" . T1_DRAGOONSOFT_SEAMLESS_GAME_API, "T1_DRAGOONSOFT_SEAMLESS_GAME_API"),
            MASCOT_SEAMLESS_GAME_API => $this->getGameApiDetails(MASCOT_SEAMLESS_GAME_API, 'MASCOT_SEAMLESS_GAME_API', 'MASCOT_SEAMLESS_GAME_API', 'goto_common_game/' . MASCOT_SEAMLESS_GAME_API, 'MASCOT_SEAMLESS_GAME_API'),
            T1_MASCOT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_MASCOT_SEAMLESS_GAME_API, "T1_MASCOT_SEAMLESS_GAME_API", "T1_MASCOT_SEAMLESS_GAME_API", "goto_t1games/" . T1_MASCOT_SEAMLESS_GAME_API, "T1_MASCOT_SEAMLESS_GAME_API"),
            POPOK_GAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(POPOK_GAMING_SEAMLESS_GAME_API, 'POPOK_GAMING_SEAMLESS_GAME_API', 'POPOK_GAMING_SEAMLESS_GAME_API', 'goto_common_game/' . POPOK_GAMING_SEAMLESS_GAME_API, 'POPOK_GAMING_SEAMLESS_GAME_API'),
            T1_POPOK_GAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_POPOK_GAMING_SEAMLESS_GAME_API, "T1_POPOK_GAMING_SEAMLESS_GAME_API", "T1_POPOK_GAMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_POPOK_GAMING_SEAMLESS_GAME_API, "T1_POPOK_GAMING_SEAMLESS_GAME_API"),
            MGPLUS2_API => $this->getGameApiDetails(MGPLUS2_API,"MG Streaming","MG Streaming","goto_common_game", "MG Streaming"),
            AOG_GAME_API => $this->getGameApiDetails(AOG_GAME_API, "AOG_GAME_API", "AOG_GAME_API", "goto_common_game/" . AOG_GAME_API, "AOG_GAME_API"),
            MPOKER_SEAMLESS_GAME_API => $this->getGameApiDetails(MPOKER_SEAMLESS_GAME_API, 'MPOKER_SEAMLESS_GAME_API', 'MPOKER_SEAMLESS_GAME_API', 'goto_common_game/' . MPOKER_SEAMLESS_GAME_API, 'MPOKER_SEAMLESS_GAME_API'),
            T1_MPOKER_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_MPOKER_SEAMLESS_GAME_API, "T1_MPOKER_SEAMLESS_GAME_API", "T1_MPOKER_SEAMLESS_GAME_API", "goto_t1games/" . T1_MPOKER_SEAMLESS_GAME_API, "T1_MPOKER_SEAMLESS_GAME_API"),
            REDGENN_PLAYSON_SEAMLESS_GAME_API => $this->getGameApiDetails(REDGENN_PLAYSON_SEAMLESS_GAME_API, 'REDGENN_PLAYSON_SEAMLESS_GAME_API', 'REDGENN_PLAYSON_SEAMLESS_GAME_API', 'goto_common_game/' . REDGENN_PLAYSON_SEAMLESS_GAME_API, 'REDGENN_PLAYSON_SEAMLESS_GAME_API'),
            T1_REDGENN_PLAYSON_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_REDGENN_PLAYSON_SEAMLESS_GAME_API, "T1_REDGENN_PLAYSON_SEAMLESS_GAME_API", "T1_REDGENN_PLAYSON_SEAMLESS_GAME_API", "goto_t1games/" . T1_REDGENN_PLAYSON_SEAMLESS_GAME_API, "T1_REDGENN_PLAYSON_SEAMLESS_GAME_API"),
            ONE_TOUCH_SEAMLESS_GAME_API => $this->getGameApiDetails(ONE_TOUCH_SEAMLESS_GAME_API, 'ONE_TOUCH_SEAMLESS_GAME_API', 'ONE_TOUCH_SEAMLESS_GAME_API', 'goto_common_game/' . ONE_TOUCH_SEAMLESS_GAME_API, 'ONE_TOUCH_SEAMLESS_GAME_API'),
            T1_ONE_TOUCH_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_ONE_TOUCH_SEAMLESS_GAME_API, "T1_ONE_TOUCH_SEAMLESS_GAME_API", "T1_ONE_TOUCH_SEAMLESS_GAME_API", "goto_t1games/" . T1_ONE_TOUCH_SEAMLESS_GAME_API, "T1_ONE_TOUCH_SEAMLESS_GAME_API"),
            AB_SEAMLESS_GAME_API => $this->getGameApiDetails(AB_SEAMLESS_GAME_API, 'AB_SEAMLESS_GAME_API', 'AB_SEAMLESS_GAME_API', 'goto_common_game/' . AB_SEAMLESS_GAME_API, 'AB_SEAMLESS_GAME_API'),
            T1_AB_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_AB_SEAMLESS_GAME_API, "T1_AB_SEAMLESS_GAME_API", "T1_AB_SEAMLESS_GAME_API", "goto_t1games/" . T1_AB_SEAMLESS_GAME_API, "T1_AB_SEAMLESS_GAME_API"),
            SIMPLEPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(SIMPLEPLAY_SEAMLESS_GAME_API, "SIMPLEPLAY_SEAMLESS_GAME_API", "SIMPLEPLAY_SEAMLESS_GAME_API", "goto_common_game/" . SIMPLEPLAY_SEAMLESS_GAME_API, "SIMPLEPLAY_SEAMLESS_GAME_API"),
            FBSPORTS_SEAMLESS_GAME_API => $this->getGameApiDetails(FBSPORTS_SEAMLESS_GAME_API, "FBSPORTS_SEAMLESS_GAME_API", "FBSPORTS_SEAMLESS_GAME_API", "goto_common_game/" . FBSPORTS_SEAMLESS_GAME_API, "FBSPORTS_SEAMLESS_GAME_API"),
            ON_CASINO_GAME_API => $this->getGameApiDetails(ON_CASINO_GAME_API, "ON_CASINO_GAME_API", "ON_CASINO_GAME_API", "goto_common_game/" . ON_CASINO_GAME_API, "ON_CASINO_GAME_API"),
            ONEAPI_PP_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_PP_SEAMLESS_GAME_API, "ONEAPI_PP_SEAMLESS_GAME_API", "ONEAPI_PP_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_PP_SEAMLESS_GAME_API, "ONEAPI_PP_SEAMLESS_GAME_API"),
            ONEAPI_BGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_BGAMING_SEAMLESS_GAME_API, "ONEAPI_BGAMING_SEAMLESS_GAME_API", "ONEAPI_BGAMING_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_BGAMING_SEAMLESS_GAME_API, "ONEAPI_BGAMING_SEAMLESS_GAME_API"),
            ONEAPI_HABANERO_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_HABANERO_SEAMLESS_GAME_API, "ONEAPI_HABANERO_SEAMLESS_GAME_API", "ONEAPI_HABANERO_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_HABANERO_SEAMLESS_GAME_API, "ONEAPI_HABANERO_SEAMLESS_GAME_API"),
            ONEAPI_EVOPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_EVOPLAY_SEAMLESS_GAME_API, "ONEAPI_EVOPLAY_SEAMLESS_GAME_API", "ONEAPI_EVOPLAY_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_EVOPLAY_SEAMLESS_GAME_API, "ONEAPI_EVOPLAY_SEAMLESS_GAME_API"),
            ONEAPI_NETENT_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_NETENT_SEAMLESS_GAME_API, "ONEAPI_NETENT_SEAMLESS_GAME_API", "ONEAPI_NETENT_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_NETENT_SEAMLESS_GAME_API, "ONEAPI_NETENT_SEAMLESS_GAME_API"),
            ONEAPI_REDTIGER_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_REDTIGER_SEAMLESS_GAME_API, "ONEAPI_REDTIGER_SEAMLESS_GAME_API", "ONEAPI_REDTIGER_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_REDTIGER_SEAMLESS_GAME_API, "ONEAPI_REDTIGER_SEAMLESS_GAME_API"),
            ONEAPI_EZUGI_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_EZUGI_SEAMLESS_GAME_API, "ONEAPI_EZUGI_SEAMLESS_GAME_API", "ONEAPI_EZUGI_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_EZUGI_SEAMLESS_GAME_API, "ONEAPI_EZUGI_SEAMLESS_GAME_API"),
            ONEAPI_JDB_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_JDB_SEAMLESS_GAME_API, "ONEAPI_JDB_SEAMLESS_GAME_API", "ONEAPI_JDB_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_JDB_SEAMLESS_GAME_API, "ONEAPI_JDB_SEAMLESS_GAME_API"),
            ONEAPI_HACKSAW_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_HACKSAW_SEAMLESS_GAME_API, "ONEAPI_HACKSAW_SEAMLESS_GAME_API", "ONEAPI_HACKSAW_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_HACKSAW_SEAMLESS_GAME_API, "ONEAPI_HACKSAW_SEAMLESS_GAME_API"),
            ONEAPI_CQ9_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_CQ9_SEAMLESS_GAME_API, "ONEAPI_CQ9_SEAMLESS_GAME_API", "ONEAPI_CQ9_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_CQ9_SEAMLESS_GAME_API, "ONEAPI_CQ9_SEAMLESS_GAME_API"),
            ONEAPI_FACHAI_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_FACHAI_SEAMLESS_GAME_API, "ONEAPI_FACHAI_SEAMLESS_GAME_API", "ONEAPI_FACHAI_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_FACHAI_SEAMLESS_GAME_API, "ONEAPI_FACHAI_SEAMLESS_GAME_API"),
            ONEAPI_JDBGTF_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_JDBGTF_SEAMLESS_GAME_API, "ONEAPI_JDBGTF_SEAMLESS_GAME_API", "ONEAPI_JDBGTF_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_JDBGTF_SEAMLESS_GAME_API, "ONEAPI_JDBGTF_SEAMLESS_GAME_API"),
            ONEAPI_SPINIX_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_SPINIX_SEAMLESS_GAME_API, "ONEAPI_SPINIX_SEAMLESS_GAME_API", "ONEAPI_SPINIX_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_SPINIX_SEAMLESS_GAME_API, "ONEAPI_SPINIX_SEAMLESS_GAME_API"),
            ONEAPI_SPADEGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_SPADEGAMING_SEAMLESS_GAME_API, "ONEAPI_SPADEGAMING_SEAMLESS_GAME_API", "ONEAPI_SPADEGAMING_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_SPADEGAMING_SEAMLESS_GAME_API, "ONEAPI_SPADEGAMING_SEAMLESS_GAME_API"),
            ONEAPI_YELLOWBAT_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_YELLOWBAT_SEAMLESS_GAME_API, "ONEAPI_YELLOWBAT_SEAMLESS_GAME_API", "ONEAPI_YELLOWBAT_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_YELLOWBAT_SEAMLESS_GAME_API, "ONEAPI_YELLOWBAT_SEAMLESS_GAME_API"),
            ONEAPI_RELAXGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_RELAXGAMING_SEAMLESS_GAME_API, "ONEAPI_RELAXGAMING_SEAMLESS_GAME_API", "ONEAPI_RELAXGAMING_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_RELAXGAMING_SEAMLESS_GAME_API, "ONEAPI_RELAXGAMING_SEAMLESS_GAME_API"),
            ONEAPI_PNG_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_PNG_SEAMLESS_GAME_API, "ONEAPI_PNG_SEAMLESS_GAME_API", "ONEAPI_PNG_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_PNG_SEAMLESS_GAME_API, "ONEAPI_PNG_SEAMLESS_GAME_API"),
            ONEAPI_BTG_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_BTG_SEAMLESS_GAME_API, "ONEAPI_BTG_SEAMLESS_GAME_API", "ONEAPI_BTG_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_BTG_SEAMLESS_GAME_API, "ONEAPI_BTG_SEAMLESS_GAME_API"),
            ONEAPI_NLC_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_NLC_SEAMLESS_GAME_API, "ONEAPI_NLC_SEAMLESS_GAME_API", "ONEAPI_NLC_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_NLC_SEAMLESS_GAME_API, "ONEAPI_NLC_SEAMLESS_GAME_API"),
            ONEAPI_BNG_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_BNG_SEAMLESS_GAME_API, "ONEAPI_BNG_SEAMLESS_GAME_API", "ONEAPI_BNG_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_BNG_SEAMLESS_GAME_API, "ONEAPI_BNG_SEAMLESS_GAME_API"),
            ONEAPI_ILOVEU_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_ILOVEU_SEAMLESS_GAME_API, "ONEAPI_ILOVEU_SEAMLESS_GAME_API", "ONEAPI_ILOVEU_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_ILOVEU_SEAMLESS_GAME_API, "ONEAPI_ILOVEU_SEAMLESS_GAME_API"),
            ONEAPI_WINFINITY_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_WINFINITY_SEAMLESS_GAME_API, "ONEAPI_WINFINITY_SEAMLESS_GAME_API", "ONEAPI_WINFINITY_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_WINFINITY_SEAMLESS_GAME_API, "ONEAPI_WINFINITY_SEAMLESS_GAME_API"),
            ONEAPI_YEEBET_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_YEEBET_SEAMLESS_GAME_API, "ONEAPI_YEEBET_SEAMLESS_GAME_API", "ONEAPI_YEEBET_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_YEEBET_SEAMLESS_GAME_API, "ONEAPI_YEEBET_SEAMLESS_GAME_API"),
            ONEAPI_QUEENMAKER_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_QUEENMAKER_SEAMLESS_GAME_API, "ONEAPI_QUEENMAKER_SEAMLESS_GAME_API", "ONEAPI_QUEENMAKER_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_QUEENMAKER_SEAMLESS_GAME_API, "ONEAPI_QUEENMAKER_SEAMLESS_GAME_API"),
            ONEAPI_SPRIBE_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_SPRIBE_SEAMLESS_GAME_API, "ONEAPI_SPRIBE_SEAMLESS_GAME_API", "ONEAPI_SPRIBE_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_SPRIBE_SEAMLESS_GAME_API, "ONEAPI_SPRIBE_SEAMLESS_GAME_API"),
            ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API, "ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API", "ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API, "ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API"),
            ONEAPI_3OAKS_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_3OAKS_SEAMLESS_GAME_API, "ONEAPI_3OAKS_SEAMLESS_GAME_API", "ONEAPI_3OAKS_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_3OAKS_SEAMLESS_GAME_API, "ONEAPI_3OAKS_SEAMLESS_GAME_API"),
            ONEAPI_BOOMING_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_BOOMING_SEAMLESS_GAME_API, "ONEAPI_BOOMING_SEAMLESS_GAME_API", "ONEAPI_BOOMING_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_BOOMING_SEAMLESS_GAME_API, "ONEAPI_BOOMING_SEAMLESS_GAME_API"),
            ONEAPI_SPINOMENAL_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_SPINOMENAL_SEAMLESS_GAME_API, "ONEAPI_SPINOMENAL_SEAMLESS_GAME_API", "ONEAPI_SPINOMENAL_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_SPINOMENAL_SEAMLESS_GAME_API, "ONEAPI_SPINOMENAL_SEAMLESS_GAME_API"),
            ONEAPI_EPICWIN_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_EPICWIN_SEAMLESS_GAME_API, "ONEAPI_EPICWIN_SEAMLESS_GAME_API", "ONEAPI_EPICWIN_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_EPICWIN_SEAMLESS_GAME_API, "ONEAPI_EPICWIN_SEAMLESS_GAME_API"),
            ONEAPI_CPGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_CPGAMES_SEAMLESS_GAME_API, "ONEAPI_CPGAMES_SEAMLESS_GAME_API", "ONEAPI_CPGAMES_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_CPGAMES_SEAMLESS_GAME_API, "ONEAPI_CPGAMES_SEAMLESS_GAME_API"),
            ONEAPI_LIVE22_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_LIVE22_SEAMLESS_GAME_API, "ONEAPI_LIVE22_SEAMLESS_GAME_API", "ONEAPI_LIVE22_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_LIVE22_SEAMLESS_GAME_API, "ONEAPI_LIVE22_SEAMLESS_GAME_API"),
            ONEAPI_CG_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_CG_SEAMLESS_GAME_API, "ONEAPI_CG_SEAMLESS_GAME_API", "ONEAPI_CG_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_CG_SEAMLESS_GAME_API, "ONEAPI_CG_SEAMLESS_GAME_API"),
            ONEAPI_DB_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_DB_SEAMLESS_GAME_API, "ONEAPI_DB_SEAMLESS_GAME_API", "ONEAPI_DB_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_DB_SEAMLESS_GAME_API, "ONEAPI_DB_SEAMLESS_GAME_API"),
            ONEAPI_ALIZE_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_ALIZE_SEAMLESS_GAME_API, "ONEAPI_ALIZE_SEAMLESS_GAME_API", "ONEAPI_ALIZE_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_ALIZE_SEAMLESS_GAME_API, "ONEAPI_ALIZE_SEAMLESS_GAME_API"),
            ONEAPI_TURBOGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_TURBOGAMES_SEAMLESS_GAME_API, "ONEAPI_TURBOGAMES_SEAMLESS_GAME_API", "ONEAPI_TURBOGAMES_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_TURBOGAMES_SEAMLESS_GAME_API, "ONEAPI_TURBOGAMES_SEAMLESS_GAME_API"),
            ONEAPI_LIVE88_SEAMLESS_GAME_API => $this->getGameApiDetails(ONEAPI_LIVE88_SEAMLESS_GAME_API, "ONEAPI_LIVE88_SEAMLESS_GAME_API", "ONEAPI_LIVE88_SEAMLESS_GAME_API", "goto_common_game/" . ONEAPI_LIVE88_SEAMLESS_GAME_API, "ONEAPI_LIVE88_SEAMLESS_GAME_API"),
            CREEDROOMZ_SEAMLESS_GAME_API => $this->getGameApiDetails(CREEDROOMZ_SEAMLESS_GAME_API, "CREEDROOMZ_SEAMLESS_GAME_API", "CREEDROOMZ_SEAMLESS_GAME_API", "goto_common_game/" . CREEDROOMZ_SEAMLESS_GAME_API, "CREEDROOMZ_SEAMLESS_GAME_API"),
            PASCAL_SEAMLESS_GAME_API => $this->getGameApiDetails(PASCAL_SEAMLESS_GAME_API, "PASCAL_SEAMLESS_GAME_API", "PASCAL_SEAMLESS_GAME_API", "goto_common_game/" . PASCAL_SEAMLESS_GAME_API, "PASCAL_SEAMLESS_GAME_API"),
            LIGHTNING_SEAMLESS_GAME_API => $this->getGameApiDetails(LIGHTNING_SEAMLESS_GAME_API, "LIGHTNING_SEAMLESS_GAME_API", "LIGHTNING_SEAMLESS_GAME_API", "goto_common_game/" . LIGHTNING_SEAMLESS_GAME_API, "LIGHTNING_SEAMLESS_GAME_API"),
            PRAGMATICPLAY_SEAMLESS_STREAMER_API => $this->getGameApiDetails(PRAGMATICPLAY_SEAMLESS_STREAMER_API, "PRAGMATICPLAY_SEAMLESS_STREAMER_API", "PRAGMATICPLAY_SEAMLESS_STREAMER_API", "goto_common_game", "PRAGMATICPLAY_SEAMLESS_STREAMER_API"),
            REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API => $this->getGameApiDetails(REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API, 'REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API', 'REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API', 'goto_common_game/' . REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API, 'REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API'),
            WIZARD_PARIPLAY_SEAMLESS_API => $this->getGameApiDetails(WIZARD_PARIPLAY_SEAMLESS_API,"Wizard","Wizard","goto_common_game/".WIZARD_PARIPLAY_SEAMLESS_API, "Wizard"),
            AVIATRIX_SEAMLESS_GAME_API => $this->getGameApiDetails(AVIATRIX_SEAMLESS_GAME_API, 'AVIATRIX_SEAMLESS_GAME_API', 'AVIATRIX_SEAMLESS_GAME_API', 'goto_common_game/' . AVIATRIX_SEAMLESS_GAME_API, 'AVIATRIX_SEAMLESS_GAME_API'),
            T1_AVIATRIX_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_AVIATRIX_SEAMLESS_GAME_API, "T1_AVIATRIX_SEAMLESS_GAME_API", "T1_AVIATRIX_SEAMLESS_GAME_API", "goto_t1games/" . T1_AVIATRIX_SEAMLESS_GAME_API, "T1_AVIATRIX_SEAMLESS_GAME_API"),
            HOLI_SEAMLESS_GAME_API => $this->getGameApiDetails(HOLI_SEAMLESS_GAME_API, "HOLI_SEAMLESS_GAME_API", "HOLI_SEAMLESS_GAME_API", "goto_common_game/" . HOLI_SEAMLESS_GAME_API, "HOLI_SEAMLESS_GAME_API"),
            T1_HOLI_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_HOLI_SEAMLESS_GAME_API, "T1_HOLI_SEAMLESS_GAME_API", "T1_HOLI_SEAMLESS_GAME_API", "goto_t1games/" . T1_HOLI_SEAMLESS_GAME_API, "T1_HOLI_SEAMLESS_GAME_API"),
            AP_GAME_API => $this->getGameApiDetails(AP_GAME_API,"AP","AP","goto_common_game", "AP"),
            RTG2_SEAMLESS_GAME_API => $this->getGameApiDetails(RTG2_SEAMLESS_GAME_API, 'RTG2_SEAMLESS_GAME_API', 'RTG2_SEAMLESS_GAME_API', 'goto_common_game/' . RTG2_SEAMLESS_GAME_API, 'RTG2_SEAMLESS_GAME_API'),
            T1_RTG2_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_RTG2_SEAMLESS_GAME_API, "T1_RTG2_SEAMLESS_GAME_API", "T1_RTG2_SEAMLESS_GAME_API", "goto_t1games/" . T1_RTG2_SEAMLESS_GAME_API, "T1_RTG2_SEAMLESS_GAME_API"),
            PGSOFT3_API => $this->getGameApiDetails(PGSOFT3_API,"PG SOFT","PG SOFT","goto_common_game", "PG SOFT"),
            WORLDMATCH_CASINO_SEAMLESS_API => $this->getGameApiDetails(WORLDMATCH_CASINO_SEAMLESS_API,"World match casino","World match casino","goto_common_game/". WORLDMATCH_CASINO_SEAMLESS_API, "World match casino"),
            T1_WORLDMATCH_CASINO_SEAMLESS_API => $this->getGameApiDetails(T1_WORLDMATCH_CASINO_SEAMLESS_API, "T1_WORLDMATCH_CASINO_SEAMLESS_API", "T1_WORLDMATCH_CASINO_SEAMLESS_API", "goto_t1games/" . T1_WORLDMATCH_CASINO_SEAMLESS_API, "T1_WORLDMATCH_CASINO_SEAMLESS_API"),
            TOM_HORN_SEAMLESS_GAME_API => $this->getGameApiDetails(TOM_HORN_SEAMLESS_GAME_API, 'TOM_HORN_SEAMLESS_GAME_API', 'TOM_HORN_SEAMLESS_GAME_API', 'goto_common_game/' . TOM_HORN_SEAMLESS_GAME_API, 'TOM_HORN_SEAMLESS_GAME_API'),
            T1_TOM_HORN_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_TOM_HORN_SEAMLESS_GAME_API, "T1_TOM_HORN_SEAMLESS_GAME_API", "T1_TOM_HORN_SEAMLESS_GAME_API", "goto_t1games/" . T1_TOM_HORN_SEAMLESS_GAME_API, "T1_TOM_HORN_SEAMLESS_GAME_API"),
            BFGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(BFGAMES_SEAMLESS_GAME_API, 'BFGAMES_SEAMLESS_GAME_API', 'BFGAMES_SEAMLESS_GAME_API', 'goto_common_game/' . BFGAMES_SEAMLESS_GAME_API, 'BFGAMES_SEAMLESS_GAME_API'),
            T1_BFGAMES_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_BFGAMES_SEAMLESS_GAME_API, "T1_BFGAMES_SEAMLESS_GAME_API", "T1_BFGAMES_SEAMLESS_GAME_API", "goto_t1games/" . T1_BFGAMES_SEAMLESS_GAME_API, "T1_BFGAMES_SEAMLESS_GAME_API"),
            TOM_HORN2_SEAMLESS_GAME_API => $this->getGameApiDetails(TOM_HORN2_SEAMLESS_GAME_API, 'TOM_HORN2_SEAMLESS_GAME_API', 'TOM_HORN2_SEAMLESS_GAME_API', 'goto_common_game/' . TOM_HORN2_SEAMLESS_GAME_API, 'TOM_HORN2_SEAMLESS_GAME_API'),
            T1_TOM_HORN2_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_TOM_HORN2_SEAMLESS_GAME_API, "T1_TOM_HORN2_SEAMLESS_GAME_API", "T1_TOM_HORN2_SEAMLESS_GAME_API", "goto_t1games/" . T1_TOM_HORN2_SEAMLESS_GAME_API, "T1_TOM_HORN2_SEAMLESS_GAME_API"),
            JGAMEWORKS_SEAMLESS_API => $this->getGameApiDetails(JGAMEWORKS_SEAMLESS_API,"Jgameworks","Jgameworks","goto_common_game/". JGAMEWORKS_SEAMLESS_API, "Jgameworks"),
            PG_JGAMEWORKS_SEAMLESS_API => $this->getGameApiDetails(PG_JGAMEWORKS_SEAMLESS_API,"PG_JGAMEWORKS_SEAMLESS_API","PG_JGAMEWORKS_SEAMLESS_API","goto_common_game/". PG_JGAMEWORKS_SEAMLESS_API, "PG_JGAMEWORKS_SEAMLESS_API"),
            JILI_JGAMEWORKS_SEAMLESS_API => $this->getGameApiDetails(JILI_JGAMEWORKS_SEAMLESS_API,"JILI_JGAMEWORKS_SEAMLESS_API","JILI_JGAMEWORKS_SEAMLESS_API","goto_common_game/". JILI_JGAMEWORKS_SEAMLESS_API, "JILI_JGAMEWORKS_SEAMLESS_API"),
            PP_JGAMEWORKS_SEAMLESS_API => $this->getGameApiDetails(PP_JGAMEWORKS_SEAMLESS_API,"PP_JGAMEWORKS_SEAMLESS_API","PP_JGAMEWORKS_SEAMLESS_API","goto_common_game/". PP_JGAMEWORKS_SEAMLESS_API, "PP_JGAMEWORKS_SEAMLESS_API"),
            IDN_SPADEGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_SPADEGAMING_SEAMLESS_GAME_API, "IDN_SPADEGAMING_SEAMLESS_GAME_API", "IDN_SPADEGAMING_SEAMLESS_GAME_API", "goto_common_game/" . IDN_SPADEGAMING_SEAMLESS_GAME_API, "IDN_SPADEGAMING_SEAMLESS_GAME_API"),
            T1_IDN_SPADEGAMING_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_SPADEGAMING_SEAMLESS_GAME_API, "T1_IDN_SPADEGAMING_SEAMLESS_GAME_API", "T1_IDN_SPADEGAMING_SEAMLESS_GAME_API", "goto_t1games/" . T1_IDN_SPADEGAMING_SEAMLESS_GAME_API, "T1_IDN_SPADEGAMING_SEAMLESS_GAME_API"),
            PT_SEAMLESS_GAME_API => $this->getGameApiDetails(PT_SEAMLESS_GAME_API, 'PT_SEAMLESS_GAME_API', 'PT_SEAMLESS_GAME_API', 'goto_common_game/' . PT_SEAMLESS_GAME_API, 'PT_SEAMLESS_GAME_API'),
            T1_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_PT_SEAMLESS_GAME_API, "T1_PT_SEAMLESS_GAME_API", "T1_PT_SEAMLESS_GAME_API", "goto_t1games/" . T1_PT_SEAMLESS_GAME_API, "T1_PT_SEAMLESS_GAME_API"),
            IDN_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_PT_SEAMLESS_GAME_API, 'IDN_PT_SEAMLESS_GAME_API', 'IDN_PT_SEAMLESS_GAME_API', 'goto_common_game/' . IDN_PT_SEAMLESS_GAME_API, 'IDN_PT_SEAMLESS_GAME_API'),
            T1_IDN_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_PT_SEAMLESS_GAME_API, "T1_IDN_PT_SEAMLESS_GAME_API", "T1_IDN_PT_SEAMLESS_GAME_API", "goto_t1games/" . T1_IDN_PT_SEAMLESS_GAME_API, "T1_IDN_PT_SEAMLESS_GAME_API"),
            IDN_SLOTS_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_SLOTS_PT_SEAMLESS_GAME_API, 'IDN_SLOTS_PT_SEAMLESS_GAME_API', 'IDN_SLOTS_PT_SEAMLESS_GAME_API', 'goto_common_game/' . IDN_SLOTS_PT_SEAMLESS_GAME_API, 'IDN_SLOTS_PT_SEAMLESS_GAME_API'),
            T1_IDN_SLOTS_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_SLOTS_PT_SEAMLESS_GAME_API, "T1_IDN_SLOTS_PT_SEAMLESS_GAME_API", "T1_IDN_SLOTS_PT_SEAMLESS_GAME_API", "goto_t1games/" . T1_IDN_SLOTS_PT_SEAMLESS_GAME_API, "T1_IDN_SLOTS_PT_SEAMLESS_GAME_API"),
            IDN_LIVE_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_LIVE_PT_SEAMLESS_GAME_API, 'IDN_LIVE_PT_SEAMLESS_GAME_API', 'IDN_LIVE_PT_SEAMLESS_GAME_API', 'goto_common_game/' . IDN_LIVE_PT_SEAMLESS_GAME_API, 'IDN_LIVE_PT_SEAMLESS_GAME_API'),
            T1_IDN_LIVE_PT_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_LIVE_PT_SEAMLESS_GAME_API, "T1_IDN_LIVE_PT_SEAMLESS_GAME_API", "T1_IDN_LIVE_PT_SEAMLESS_GAME_API", "goto_t1games/" . T1_IDN_LIVE_PT_SEAMLESS_GAME_API, "T1_IDN_LIVE_PT_SEAMLESS_GAME_API"),
            IDN_HABANERO_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_HABANERO_SEAMLESS_GAMING_API, 'IDN_HABANERO_SEAMLESS_GAMING_API', 'IDN_HABANERO_SEAMLESS_GAMING_API', 'goto_common_game/' . IDN_HABANERO_SEAMLESS_GAMING_API, 'IDN_HABANERO_SEAMLESS_GAMING_API'),
            T1_IDN_HABANERO_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_HABANERO_SEAMLESS_GAMING_API, "T1_IDN_HABANERO_SEAMLESS_GAMING_API", "T1_IDN_HABANERO_SEAMLESS_GAMING_API", "goto_t1games/" . T1_IDN_HABANERO_SEAMLESS_GAMING_API, "T1_IDN_HABANERO_SEAMLESS_GAMING_API"),
            IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API,"IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API","IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API","goto_common_game", "IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API"),
            IDN_LIVE_MGPLUS_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_LIVE_MGPLUS_SEAMLESS_GAME_API,"IDN_LIVE_MGPLUS_SEAMLESS_GAME_API","IDN_LIVE_MGPLUS_SEAMLESS_GAME_API","goto_common_game", "IDN_LIVE_MGPLUS_SEAMLESS_GAME_API"),
            T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API,"T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API","T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API","goto_t1games", "T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API"),
            T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API,"T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API","T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API","goto_t1games", "T1_IDN_LIVE_MGPLUS_SEAMLESS_GAME_API"),
            IDN_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(IDN_PRAGMATICPLAY_SEAMLESS_API, "IDN_PRAGMATICPLAY_SEAMLESS_API", "IDN_PRAGMATICPLAY_SEAMLESS_API", "goto_common_game", "IDN_PRAGMATICPLAY_SEAMLESS_API"),
            T1_IDN_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(T1_IDN_PRAGMATICPLAY_SEAMLESS_API, "T1_IDN_PRAGMATICPLAY_SEAMLESS_API", "T1_IDN_PRAGMATICPLAY_SEAMLESS_API", "goto_t1games", "T1_IDN_PRAGMATICPLAY_SEAMLESS_API"),
            IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API, "IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API", "IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API", "goto_common_game", "IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API"),
            T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API, "T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API", "T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API", "goto_t1games", "T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API"),
            IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API, "IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API", "IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API", "goto_common_game", "IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API"),
            T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API => $this->getGameApiDetails(T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API, "T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API", "T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API", "goto_t1games", "T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API"),
            PLAYSTAR_SEAMLESS_GAME_API => $this->getGameApiDetails(PLAYSTAR_SEAMLESS_GAME_API, "PLAYSTAR_SEAMLESS_GAME_API", "PLAYSTAR_SEAMLESS_GAME_API", "goto_common_game/" . PLAYSTAR_SEAMLESS_GAME_API, "PLAYSTAR_SEAMLESS_GAME_API"),
            IDN_PLAYSTAR_SEAMLESS_GAME_API => $this->getGameApiDetails(IDN_PLAYSTAR_SEAMLESS_GAME_API, "IDN_PLAYSTAR_SEAMLESS_GAME_API", "IDN_PLAYSTAR_SEAMLESS_GAME_API", "goto_t1games/" . IDN_PLAYSTAR_SEAMLESS_GAME_API, "IDN_PLAYSTAR_SEAMLESS_GAME_API"),
            T1_IDN_PLAYSTAR_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_IDN_PLAYSTAR_SEAMLESS_GAME_API, "T1_IDN_PLAYSTAR_SEAMLESS_GAME_API", "T1_IDN_PLAYSTAR_SEAMLESS_GAME_API", "goto_t1games/" . T1_IDN_PLAYSTAR_SEAMLESS_GAME_API, "T1_IDN_PLAYSTAR_SEAMLESS_GAME_API"),
            IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,"IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API","IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API","goto_common_game/".IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API, "EVOLUTION_NETENT_SEAMLESS_GAMING_API"),
            IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,"IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API","IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API","goto_common_game/".IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API, "EVOLUTION_NLC_SEAMLESS_GAMING_API"),
            IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,"IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","goto_common_game/".IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, "IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API"),
            IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,"IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API","IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API","goto_common_game/".IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API, "IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API"),
            T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,"T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API","T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API","goto_t1games/".T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API, "T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API"),
            T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,"T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API","T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API","goto_t1games/".T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API, "T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API"),
            T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,"T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API","goto_t1games/".T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, "T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API"),
            T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,"T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API","T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API","goto_t1games/".T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API, "T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API"),
            IDN_EVOLUTION_SEAMLESS_GAMING_API => $this->getGameApiDetails(IDN_EVOLUTION_SEAMLESS_GAMING_API,"IDN_EVOLUTION_SEAMLESS_GAMING_API","IDN_EVOLUTION_SEAMLESS_GAMING_API","goto_common_game/".IDN_EVOLUTION_SEAMLESS_GAMING_API, "IDN_EVOLUTION_SEAMLESS_GAMING_API"),
            T1_IDN_EVOLUTION_SEAMLESS_GAMING_API => $this->getGameApiDetails(T1_IDN_EVOLUTION_SEAMLESS_GAMING_API,"T1_IDN_EVOLUTION_SEAMLESS_GAMING_API","T1_IDN_EVOLUTION_SEAMLESS_GAMING_API","goto_t1games/".T1_IDN_EVOLUTION_SEAMLESS_GAMING_API, "T1_IDN_EVOLUTION_SEAMLESS_GAMING_API"),
            FIVEG_GAMING_SEAMLESS_API => $this->getGameApiDetails(FIVEG_GAMING_SEAMLESS_API,"5G","5G","goto_common_game/". FIVEG_GAMING_SEAMLESS_API, "5G"),
            IDN_PGSOFT_SEAMLESS_API => $this->getGameApiDetails(IDN_PGSOFT_SEAMLESS_API, 'IDN_PGSOFT_SEAMLESS_API', 'IDN_PGSOFT_SEAMLESS_API', 'goto_common_game/' . IDN_PGSOFT_SEAMLESS_API, 'IDN_PGSOFT_SEAMLESS_API'),
            T1_IDN_PGSOFT_SEAMLESS_API => $this->getGameApiDetails(T1_IDN_PGSOFT_SEAMLESS_API, "T1_IDN_PGSOFT_SEAMLESS_API", "T1_IDN_PGSOFT_SEAMLESS_API", "goto_t1games/" . T1_IDN_PGSOFT_SEAMLESS_API, "T1_IDN_PGSOFT_SEAMLESS_API"),
            T1_FIVEG_GAMING_SEAMLESS_API => $this->getGameApiDetails(T1_FIVEG_GAMING_SEAMLESS_API, "T1_FIVEG_GAMING_SEAMLESS_API", "T1_FIVEG_GAMING_SEAMLESS_API", "goto_t1games/" . T1_FIVEG_GAMING_SEAMLESS_API, "T1_FIVEG_GAMING_SEAMLESS_API"),
            IDN_PLAY_SEAMLESS_GAME_API  => $this->getGameApiDetails(IDN_PLAY_SEAMLESS_GAME_API , "IDN_PLAY_SEAMLESS_GAME_API ", "IDN_PLAY_SEAMLESS_GAME_API ", "goto_common_game/" . IDN_PLAY_SEAMLESS_GAME_API , "IDN_PLAY_SEAMLESS_GAME_API"),
            FA_WS168_SEAMLESS_GAME_API => $this->getGameApiDetails(FA_WS168_SEAMLESS_GAME_API, 'FA_WS168_SEAMLESS_GAME_API', 'FA_WS168_SEAMLESS_GAME_API', 'goto_common_game/' . FA_WS168_SEAMLESS_GAME_API, 'FA_WS168_SEAMLESS_GAME_API'),
            T1_FA_WS168_SEAMLESS_GAME_API => $this->getGameApiDetails(T1_FA_WS168_SEAMLESS_GAME_API, "T1_FA_WS168_SEAMLESS_GAME_API", "T1_FA_WS168_SEAMLESS_GAME_API", "goto_t1games/" . T1_FA_WS168_SEAMLESS_GAME_API, "T1_FA_WS168_SEAMLESS_GAME_API"),
        ];

        if ($this->utils->getConfig('jumb_change_api_name')) {
            $game_platforms['available_game_providers'][JUMB_GAMING_API] = $this->getGameApiDetails(JUMB_GAMING_API,"JDB","JDB","goto_jdbgame", "JDB");
        }

        return $game_platforms;

    }

    private function getGameApiDetails($game_platform_id,$game_provider_name,$game_platform_name,$goto_game_method, $game_provider_code){

        $show_maintenance_status_on_get_frontend_games = $this->utils->getConfig('show_maintenance_status_on_get_frontend_games');
        if($show_maintenance_status_on_get_frontend_games) {
            $this->CI->load->model(['external_system']);
            if(empty($this->game_apis_on_maintenance)) {
                $this->game_apis_on_maintenance = $this->CI->external_system->getActivedGameApiWithFields('id, maintenance_mode');
            }
            $maintenance_mode = false;
            if(array_key_exists($game_platform_id, $this->game_apis_on_maintenance)) {
                $maintenance_mode = $this->game_apis_on_maintenance[$game_platform_id]['maintenance_mode'] == External_system::MAINTENANCE_OR_PAUSE_SYNCING_ON_PROGRESS;
            }
        }
        $data = [
                    'game_provider' => $game_platform_name,
                    'complete_name' => $game_provider_name,
                    'game_platform_id'=> $game_platform_id,
                    'game_provider_code' => $game_provider_code,
                    'game_launch_url' =>  'player_center/'.$goto_game_method,
               ];

        if($show_maintenance_status_on_get_frontend_games) {
            $data['maintenance_mode'] = $maintenance_mode;
        }
        return $data;
    }

    public function customGameUrl($platformId, $url, $game_type, $path=null){

        switch ($game_type) {
            #Sports
            case self::TAG_CODE_SPORTS:
                switch ($platformId) {
                    case ONEWORKS_API:
                            return $url . "/1";
                        break;

                    default:
                            return $url;
                        break;
                }
                break;
            #GPI Lottery
            case self::TAG_CODE_LOTTERY_KENO:
                switch ($platformId) {
                    case GAMEPLAY_API:
                            return $url . $path;
                        break;

                    default:
                            return $url;
                        break;
                }
                break;
            case self::TAG_CODE_LOTTERY_THAI:
                switch ($platformId) {
                    case GAMEPLAY_API:
                            return $url . $path;
                        break;

                    default:
                            return $url;
                        break;
                }
                break;
            #E-sports
            case self::TAG_CODE_E_SPORTS:
                switch ($platformId) {
                    case ONEWORKS_API:
                        return $url . "/esports";
                        break;
                    case PINNACLE_SEAMLESS_GAME_API:
                    case PINNACLE_API:
                    case T1_PINNACLE_SEAMLESS_GAME_API:
                    case AP_GAME_API:
                            return $url . "/e-sports";
                        break;
                    default:
                            return $url;
                        break;
                }
                break;
            #Live Dealer/Casino
            case self::TAG_CODE_LIVE_DEALER:
                switch ($platformId) {
                    case AGIN_YOPLAY_API:
                    case AGIN_API:
                        return $url . "/default/11";
                        break;
                    case BBIN_API:
                            return $url . "/37";
                        break;
                    case PRAGMATICPLAY_API:
                            return $url . "/101";
                        break;
                    case PT_V2_API:
                            return $url . "/default/bal";
                        break;
                    default:
                            return $url;
                        break;
                }
                break;
            #Slots
            case self::TAG_CODE_SLOT:
                switch ($platformId) {
                    case JUMB_GAMING_API:
                    case MG_DASHUR_API:
                            return $url . "/slots/<game_code>/<mode>";
                        break;
                    case HB_API:
                            return $url . "/<mode>/<game_code>";
                        break;
                    case AGIN_YOPLAY_API:
                    case AGIN_API:
                            return $url . "/default/8";
                        break;
                    case BBIN_API:
                            return $url . "/37";
                        break;
                    case PT_V2_API:
                            return $url . "/default/<game_code>/<mode>";
                        break;
                    default:
                            return $url . "/<game_code>/<mode>";
                        break;
                }
                break;
            default:
                    return $url;
                break;
        }
    }

    /**
     *  This function returns an array containing the web URL of the game provided by the game_description rows.
     *
     *  @param array $game_descriptions This array contains data structured from the *game_description* table.
     *  @param string $prefix this string will assume the 1st position in the url that will be returned. defaulted to 'player_center'
     *
     *  @return array $games example:
     *  [
     *      [
    *           'url' => 'player_center/goto_kycard/712/190',
    *           'image' => 'kycard',
    *           'favorite' => true,
     *      ],
     *  ]
     */
    public function getGameUrl($game_descriptions, $options = null){
        $this->CI->load->model(['game_description_model','game_type_model','external_system']);

        $favorite_games = isset($options['favorite_games']) ? array_map('strtolower', $options['favorite_games']) : [];

        $provider_details = $this->getGameProviderDetails();
        $provider_details = $provider_details['available_game_providers'];

        $platform_ids = array_unique(array_column($game_descriptions, 'game_platform_id'));

        $provider_data  = [];
        foreach($platform_ids as $platform_id){
            $this->checkGameProviderGamelist(
                $platform_id,
                null,
                $provider_data[$platform_id],
                'all'
            );
        }

        $games = [];
        foreach($game_descriptions as $key => $game_description) {
            $game_provider_data = $provider_data[$game_description['game_platform_id']];

            if (isset($game_provider_data['game_list'])){
                //provider does not have lobby
                $game_provider_data_key = array_search($game_description['external_game_id'], array_column($game_provider_data['game_list'], 'external_game_id'));
                if (!isset($game_provider_data['game_list'][$game_provider_data_key])){
                    continue;
                }
                $game_provider_data = $game_provider_data['game_list'][$game_provider_data_key];

                $game_provider_data['game_platform_id'] = isset($game_provider_data['game_platform_id']) ? $game_provider_data['game_platform_id'] : $game_description['game_platform_id'];
                $game_provider_data['game_code'] = $game_description['external_game_id'];
                $game_provider_data['type_lang'] = isset($game_provider_data['type_lang']) ? $game_provider_data['type_lang'] : null;

                $games[$key] = $this->processGameUrls(
                    $provider_details[$game_description['game_platform_id']]['game_launch_url'],
                    $game_provider_data,
                    $game_description['game_type_code'],
                    $game_description['attributes']
                );

            } else if (isset($game_provider_data['game_launch_url'])) {
                $game_code = $game_description['game_type_code'];
                $games[$key] = isset($game_provider_data['game_launch_url'][$game_code]) ? $game_provider_data['game_launch_url'][$game_code] : $game_description;
            }

            if (!isset($games[$key])){
                continue;
            }
            $games[$key] = array_merge($games[$key], $game_description);


            $games[$key]['url'] = isset($games[$key]['web']) ? $games[$key]['web'] : '';
            $image_path = $this->processGameImagePath($games[$key]);
            $games[$key]['image'] = isset($image_path[$options['language']]) ? $image_path[$options['language']] : $image_path['en'];
            $games[$key]['favorite'] =  in_array(strtolower($games[$key]['url']), $favorite_games);
            if (!$games[$key]['favorite']) {
                $games[$key]['favorite'] =  (in_array(strtolower(str_replace('/iframe_module', '/player_center', $games[$key]['url'])), $favorite_games));
            }

        }

        return $games;

    }

    public function game_dir_name($game_platform_id) {
        $function = __FUNCTION__;
        $dir = $game_platform_id;
        $file = dirname(__FILE__) . "/../config/{$function}.php";

        if (file_exists($file)) {
            require_once $file;

            if (function_exists($function)) {
                $dir = $function($game_platform_id);
            }
        }

        return $dir;
    }
}
