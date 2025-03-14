<?php

/**
 * Api_common core module: player reports
 * Separated 8/18/2021
 * @see		api_common.php
 */
trait comapi_core_player_reports {

    /**
     * Returns player's reports as in player center
     * Furbished in OGP-9569, OGP-9815
     * Ported back from xcyl
     *
     * @uses    POST:api_key    string      The api_key, as md5 sum. Required.
     * @uses    POST:token      string      Effective token for player.
     * @uses    POST:username   string      Player username.  Required.
     * @uses    POST:time_from  datetime    Start time of query
     * @uses    POST:time_to    datetime    End time of query
     * @uses    POST:trans_type string      Type of transaction.  Use any one of
     *      following: cashback, deposit, game, point, promo, transfer, withdrawal
     * @uses    POST:limit      int         Paging.  Count of records to return.  Defaults to 10.
     * @uses    POST:offset     int         Paging.  Starting point of records to return.
     *
     * @see     models/comapi_reports
     *
     * @return  JSON    Standard return object of [ success, code, mesg, result ]
     *
     */
    public function getPlayerReports() {
        $api_key     = $this->input->post('api_key'     , true);
        $username    = $this->input->post('username'    , true);
        $token       = $this->input->post('token'       , true);
        $time_start  = $this->input->post('time_from'   , true);
        $time_end    = $this->input->post('time_to'     , true);
        $trans_type  = $this->input->post('trans_type'  , true);
        $limit       = intval($this->input->post('limit'        , true));
        $offset      = intval($this->input->post('offset'       , true));
        $include_declined_promos = intval($this->input->post('include_declined_promos')) != -1;
        $game_platform_id = intval($this->input->post('game_platform_id' , true));
        $return_type      = intval($this->input->post('return_type' , true));
        $reveal_totals     = $this->input->post('reveal_totals', true);
        if( !isset($_POST['reveal_totals']) ){
            $reveal_totals = true;// default is true
        }
        $reveal_totals     = !!$reveal_totals;  // convert to boolean type


        if (!$this->__checkKey($api_key)) { return; }

        $this->load->model(['player_model', 'comapi_reports', 'game_description_model']);
        $player = $this->player_model->getPlayerByUsername($username);

        if (!empty($token) && !empty($player) && $this->__isLoggedIn($player->playerId, $token)){

            $limit_max = $this->utils->getConfig('comapi_get_player_reports_query_limit');

            if (empty($limit) || $limit < 0 || $limit > $limit_max) {
                $limit = $limit_max;
            }

            $result = [];

            switch ($trans_type){
                case 'referral': // friend referral
                    $result = $this->comapi_reports->playerReferralFriend($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
                case 'referralblur':
                    $result = $this->comapi_reports->playerReferralFriend($player->playerId, $time_start, $time_end, $limit, $offset, true);
                    break;
                case 'promo':
                    $result = $this->comapi_reports->playerActivePromoDetails($player->playerId, $time_start, $time_end, $limit, $offset, $include_declined_promos);
                    break;
                case 'transfer':
                    $result = $this->comapi_reports->playerTransferRequests($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
                case 'cashback':
                    $result = $this->comapi_reports->playerCashbackRequestRecords($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
                // case 'point':
                //  $result = $this->comapi_reports->api_pointsHistory($player->playerId, $time_start, $time_end, $limit, $offset);
                //  break;
                case 'game':
                    $game_res_count = $this->comapi_reports->playerGameHistory($player->playerId, $time_start, $time_end, $limit, $offset, $game_platform_id, 'return_count');
                    $game_res = $this->comapi_reports->playerGameHistory($player->playerId, $time_start, $time_end, $limit, $offset, $game_platform_id);
                    $ret_rows = [];
                    $game_history_just_game=$this->utils->getConfig('game_history_just_game');
                    if (!empty($game_res)) {
                        foreach ($game_res as $grow) {
                            // OGP-16577: fix for game name
                            $game_desc = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($grow['game_platform_id'], $grow['game_code']);
                            // $game_desc = $this->game_description_model->getGameDescriptionByCode($grow['game_code']);
                            $game_locale_name = lang(empty($game_desc) ? null : $game_desc->game_name);
                            $row = [
                                'date'              => $grow['end_at'] ,
                                'game_platform'     => $grow['game'] ,
                                'game_platform_id'  => $grow['game_platform_id'] ,
                                'game_type'         => lang($grow['game_type']) ,
                                'game_name'         => $game_locale_name ,
                                'real_bet_amount'   => $grow['real_bet_amount'] ,
                                'bet_amount'        => $grow['bet_amount'] ,
                                'result_amount'     => $grow['result_amount'] ,
                                'bet_plus_result'   => $grow['bet_plus_result_amount'] ,
                                'win_amount'        => $grow['win_amount'] ,
                                'loss_amount'       => $grow['loss_amount'] ,
                                'round_no'          => $grow['round_no'] ,
                                'bet_details'       => $grow['bet_details'] ,
                                'flag'              => $grow['flag'] ,

                            ];
                            if(!empty($game_history_just_game)&&$grow['flag'] != Game_logs::FLAG_GAME){
                                continue;
                            }
                            $ret_rows[] = $row;
                        }
                    }

                    $result = [ 'row_count_total' => $game_res_count, 'rows' => $ret_rows ];

                    if($reveal_totals){
                        $result['totals'] = $this->comapi_reports->playerGameHistory($player->playerId, $time_start, $time_end, $limit, $offset, $game_platform_id, false, 'reveal_totals');
                    }
                    break;
                case 'withdrawal':
                    $result = $this->comapi_reports->player_withdrawals($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
                case 'transaction':
                    $result = $this->comapi_reports->player_transactions($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
                // case 'deposit2':
                //  $result = $this->comapi_reports->player_deposits2($player->playerId, $time_start, $time_end, $limit, $offset);
                //  break;
                case 'deposit':
                default:
                    $result = $this->comapi_reports->player_deposits($player->playerId, $time_start, $time_end, $limit, $offset);
                    break;
            }

            $ret = ($return_type == 0) ? $result['rows'] : $result;

            $this->__returnApiResponse(true, self::CODE_SUCCESS, lang('Player report generated successfully'), $ret);

            return;
        }
        else{
            // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
            $this->__returnApiResponse(false, self::CODE_COMMON_INVALID_TOKEN, lang('Invalid token or user not logged in'));
            return;
        }
    } // End function getPlayerReports()


} // End of trait comapi_core_player_reports
