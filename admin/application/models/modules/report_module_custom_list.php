<?php

/**
 * General behaviors include
 * * get custom report
 *
 * @category report_module_custom_list
 * @version 1.0.0
 * @copyright 2013-2022 tot
 */
trait report_module_custom_list
{

    /**
     * detail: get player reports with additional roulette
     * for C043-smash
     * @param array $request
     * @param Boolean $viewPlayerInfoPerm
     * @param Boolean $is_export
     *
     * @return array
     */
    public function player_additionl_roulette_report($request, $viewPlayerInfoPerm, $is_export = false)
    {
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('risk_score_model', 'player_kyc', 'kyc_status_model', 'agency_model', 'transactions', 'affiliatemodel', 'affiliate', 'player_promo', 'promorules'));
        $this->load->helper(['player_helper']);
        $game_apis_map = $this->utils->getAllSystemMap();

        $sum_add_bonus_as_manual_bonus = $this->utils->getConfig('sum_add_bonus_as_manual_bonus');
        $sum_deposit_promo_bonus_as_total_deposit_bonus = $this->utils->getConfig('sum_deposit_promo_bonus_as_total_deposit_bonus');
        $player_kyc = $this->player_kyc;
        $kyc_status_model = $this->kyc_status_model;
        $risk_score_model = $this->risk_score_model;

        $this->data_tables->is_export = $is_export;

        // $table = 'player_report_hourly';
        $joins = array();
        $where = array();
        $values = array();
        $group_by = array();
        $having = array();

        $joins['player'] = "player_additional_roulette.player_id = player.playerId";
        $joins['player_report_hourly'] = "player.playerId = player_report_hourly.player_id";
        $joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player_report_hourly.level_id';
        $joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';
        // $joins['player_last_transactions'] = "player_last_transactions.player_id = player.playerId";
        $i = 0;
        $input = $this->data_tables->extra_search($request);

        if (empty($input['timezone'])) {
            $input['timezone'] = 0; // for undefined issue
        }
        $date_from = null;
        $date_to = null;
        $playerlvl = null;
        $playerlvls = null;
        if (isset($input['date_from'], $input['date_to'])) {
            // $date_from = $input['date_from'];
            // $date_to = $input['date_to'];

            $date_from = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_from']);
            $date_to = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_to']);
        }
        $subWhere = '';
        $dateTimeFrom = null;
        $dateTimeTo = null;
        if (isset($input['date_from'], $input['date_to'])) {
            $dateTimeFrom = $input['date_from'];
            $dateTimeTo   = $input['date_to'];

            /// for apply the timezone,
            // override the inputs, deposit_date_from and deposit_date_to.
            $dateTimeFrom = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeFrom);
            $dateTimeTo = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeTo);

            $_dateTimeFrom = $this->utils->formatDateTimeForMysql(new DateTime($dateTimeFrom));
            $_dateTimeTo = $this->utils->formatDateTimeForMysql(new DateTime($dateTimeTo));
            $subWhere = sprintf("where player_additional_roulette.created_at >= '%s' AND player_additional_roulette.created_at <= '%s'", $_dateTimeFrom, $_dateTimeTo);
        }

        $subTable = "SELECT
        player_additional_roulette.player_id player_id,
        player_additional_roulette.created_at,
        sum(playerpromo.bonusAmount) applied_bonus
        FROM
        player_additional_roulette
            LEFT JOIN
        playerpromo ON playerpromo.playerpromoId = player_additional_roulette.player_promo_id
        $subWhere
        group by player_additional_roulette.player_id";

        $table = "($subTable) AS player_additional_roulette";

        $group_by[] = 'player_additional_roulette.player_id';

        # FILTER ######################################################################################################################################################################################

        $show_username            = true;
        $show_realname            = true;
        $show_tag                 = true;
        $show_risk_level          = true;
        $show_kyc_level           = true;
        $show_player_level        = true;
        $show_affiliate           = true;
        $show_agent               = true;
        $show_register_date       = true;
        $show_deposit_bonus       = true;
        $show_cashback_bonus      = true;
        $show_referral_bonus      = true;
        $show_manual_bonus        = true;
        $show_subtract_bonus      = true;
        $show_total_bonus         = true;
        $show_first_deposit       = true;
        $show_first_deposit_date  = true;
        $show_total_deposit       = true;
        $show_total_deposit_times = true;
        $show_total_withdrawal    = true;
        $show_total_dw            = true;
        $show_total_bets          = true;
        $show_total_win           = true;
        $show_total_loss          = true;
        $show_total_payout        = true;
        $show_payout_rate         = true;
        $show_total_revenue       = true;
        $show_last_login_date     = true;
        $show_last_deposit_date   = true;
        $show_net_loss            = true;

        if (isset($input['username'])) {
            $where[] = "player_username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

        if (isset($input['referrer'])) {

            $refereePlayerId = $this->player_model->getPlayerIdByUsername($input['referrer']) ?: -1;
            // $joins['player'] = "player.playerId = player_report_hourly.player_id";
            $where[] = "player.refereePlayerId = ?";
            $values[] = $refereePlayerId;
        }


        if (isset($input['playerlevel'])) {
            $where[] = "level_id = ?";
            $values[] = $input['playerlevel'];
        }

        if (isset($input['depamt1'])) {
            $having['total_deposit <='] = $input['depamt1'];
        }

        if (isset($input['depamt2'])) {
            $having['total_deposit >='] = $input['depamt2'];
        }

        if (isset($input['widamt1'])) {
            $having['total_withdrawal <='] = $input['widamt1'];
        }

        if (isset($input['widamt2'])) {
            $having['total_withdrawal >='] = $input['widamt2'];
        }
        if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
            $where[] = "player_report_hourly.agent_id IS NOT NULL";
            if (!isset($input['agent_name'])) {
                if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
                    $input['agent_name'] = $input['current_agent_name'];
                }
            }
        }
        if (isset($input['agent_name'])) {
            $agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

            if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
                $joins['agency_agents'] = 'player_report_hourly.agent_id = agency_agents.agent_id';
                $parent_ids = array($agent_detail['agent_id']);
                $all_ids = $parent_ids;
                // $sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids);
                while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
                    $this->utils->debug_log('sub_ids', $sub_ids);
                    $all_ids = array_merge($all_ids, $sub_ids);
                    $parent_ids = $sub_ids;
                    $sub_ids = array();
                }
                $w = '';
                foreach ($all_ids as $i => $id) {
                    if ($i == 0) {
                        $w = "(player_report_hourly.agent_id = ?";
                    } else {
                        $w .= " OR player_report_hourly.agent_id = ?";
                    }
                    $values[] = $id;
                }
                $w .= ")";
                $where[] = $w;
            } else {
                $where[] = "player_report_hourly.agent_id = ?";
                $values[] = $agent_detail['agent_id'];
            }
        }

        if (isset($input['affiliate_name'])) {

            $affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_name']);
            @$this->utils->debug_log('affiliateId', $$affiliateId);
            $affiliateIds = null;

            if (
                isset($input['aff_include_all_downlines']) && $input['aff_include_all_downlines']
                && !empty($affiliateId)
            ) {
                $affiliateIds = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliateId);
            }

            if (!empty($affiliateIds)) {
                $where[] = 'affiliate_id IN(' . implode(',', $affiliateIds) . ')';
            } else {
                $where[] = "affiliate_username = ?";
                $values[] = $input['affiliate_name'];
            }
        }

        $affiliatesTagMap = [];

        if (isset($input['affiliate_tags'])) {

            $affiliatesTagMap = $this->affiliate->getAffTagsMap();

            $this->utils->debug_log('affiliatesTagMap1', $affiliatesTagMap);

            $joins['affiliatetag'] = 'affiliatetag.affiliateId=affiliate_id';
            $joins['affiliatetaglist'] = 'affiliatetaglist.tagId=affiliatetag.tagId';
            $where[] = "affiliatetag.tagId = ?";
            $values[] = $input['affiliate_tags'];
        }

        if (isset($input['player_tag'])) {
            $joins['playertag'] = 'playertag.playerId = player_id';
            $where[] = "playertag.tagId = ?";
            $values[] = $input['player_tag'];
        }

        if (isset($input['tag_list'])) {
            $tagList = $input['tag_list'];

            if (is_array($tagList)) {
                $notag = array_search('notag', $tagList);
                if ($notag !== false) {
                    $where[] = 'player_report_hourly.player_id IN (SELECT DISTINCT playerId FROM playertag)';
                    unset($tagList[$notag]);
                }
            } elseif ($tagList == 'notag') {
                $where[] = 'player_report_hourly.player_id IN (SELECT DISTINCT playerId FROM playertag)';
                $tagList = null;
            }
            if (!empty($tagList)) {
                $tagList = is_array($tagList) ? implode(',', $tagList) : $tagList;
                $where[] = 'player_report_hourly.player_id NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN (' . $tagList . '))';
            }
        }

        if (isset($input['tag_list_included'])) {
            $tagListIncluded = $input['tag_list_included'];

            if (is_array($tagListIncluded)) {
                $notag = array_search('notag', $tagListIncluded);
                if ($notag !== false) {
                    $where[] = 'player_report_hourly.player_id IN (SELECT DISTINCT playerId FROM playertag)';
                    unset($tagListIncluded[$notag]);
                }
            } elseif ($tagListIncluded == 'notag') {
                $where[] = 'player_report_hourly.player_id NOT IN (SELECT DISTINCT playerId FROM playertag)';
                $tagListIncluded = null;
            }
            if (!empty($tagListIncluded)) {
                $tagListIncluded = is_array($tagListIncluded) ? implode(',', $tagListIncluded) : $tagListIncluded;
                $where[] = 'player_report_hourly.player_id IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN (' . $tagListIncluded . '))';
            }
        }

        if (isset($input['affiliate_agent'])) {
            switch ($input['affiliate_agent']) {

                case '1': # Not under any Affiliate or Agent
                    $where[] = '(player_report_hourly.agent_id IS NULL OR player_report_hourly.agent_id = 0) AND (affiliate_id IS NULL OR affiliate_id = 0)';
                    break;

                case '2': # Under Affiliate Only
                    $where[] = '(player_report_hourly.agent_id IS NULL OR player_report_hourly.agent_id = 0) AND affiliate_id > 0';
                    break;

                case '3': # Under Agent Only
                    $where[] = 'player_report_hourly.agent_id > 0 AND (affiliate_id IS NULL OR affiliate_id = 0)';
                    break;

                case '4': # Under Affiliate or Agent
                    $where[] = '(player_report_hourly.agent_id > 0 OR affiliate_id > 0)';
                    break;
            }
        }

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player_username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player_username = ?";
                $values[] = $input['username'];
            }
        }

        $subtotals = [
            'subtotals_deposit_bonus' => 0, 'subtotals_cashback_bonus' => 0, 'subtotals_referral_bonus' => 0,
            'subtotals_manual_bonus' => 0, 'subtotals_subtract_bonus' => 0, 'subtotals_total_bonus' => 0, 'subtotals_first_deposit' => 0,
            'subtotals_second_deposit' => 0, 'subtotals_total_deposit' => 0, 'subtotals_total_deposit_times' => 0, 'subtotals_total_withdrawal' => 0,
            'subtotals_total_bets' => 0, 'subtotals_total_payout' => 0, 'subtotals_total_win' => 0, 'subtotals_total_loss' => 0,
            'subtotals_total_dw' => 0, 'subtotals_payout_rate' => 0,
            'subtotals_dnb' => 0, 'subtotals_bod' => 0, 'subtotals_wod' => 0, 'subtotals_tat' => 0, 'subtotals_win' => 0, 'subtotals_loss' => 0
        ];
        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array(
            array(
                'select' => 'affiliate_id'
            ),
            array(
                'select' => 'player.levelId',
                'alias' => 'level_id'
            ),
            array(
                'select' => 'player_report_hourly.agent_id',
                'alias' => 'prh_agent_id'
            ),
            array(
                'select' => 'player.levelName',
                'alias' => 'level_name'
            ),
            array(
                'select' => 'vipLevelName'
            ),
            array(
                'select' => 'player_additional_roulette.player_id',
                'alias' => 'player_id'
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'name' => lang('report.pr01'),
                'select' => 'player.username',
                'formatter' => function ($d, $row) use ($is_export, $date_from, $date_to, $show_username) {

                    if ($show_username) {
                        if ($is_export) {
                            return $d;
                        } else {
                            $date_qry = '';
                            if (!empty($date_from) && !empty($date_to)) {
                                $date = new DateTime($date_from);
                                $date_qry = '&date_from=' . $date->format('Y-m-d') . '&hour_from=' . $date->format('H');

                                $date = new DateTime($date_to);
                                $date_qry .= '&date_to=' . $date->format('Y-m-d') . '&hour_to=' . $date->format('H');
                            }
                            return '<a href="/player_management/userInformation/' . $row['player_id'] . '">' . $d . '</a>';
                        }
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                // 'dt' => $i++,
                'name' => lang('report.pr03'),
                'alias' => 'member_level',
                'select' => 'player.groupName',
                'formatter' => function ($d, $row) use ($show_player_level, $is_export) {
                    if (($show_player_level)) {
                        return lang($d) . " - " . lang($row['level_name']);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'affiliates_name',
            //     'name' => lang('Affiliate'),
            //     'select' => 'affiliate_username',
            //     'formatter' => function ($d, $row) use ($is_export, $show_affiliate, $input, $affiliatesTagMap) {
            //         if ($show_affiliate) {
            //             $url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
            //             $name = '';
            //             if ($is_export) {
            //                 $name = !empty($d) ? $d :  lang('lang.norecyet');
            //             } else {
            //                 if (!empty($d)) {
            //                     if (isset($input['affiliate_tags'])) {
            //                         if (isset($affiliatesTagMap[$input['affiliate_tags']])) {
            //                             $name = '<span class="badge badge-info" style="float:right;">' . $affiliatesTagMap[$input['affiliate_tags']] . '</span>';
            //                         } else {
            //                             $name = '';
            //                         }
            //                     }
            //                     $name .= '<a href="' . $url . '">' . $d . '</a> ';
            //                 } else {
            //                     $name = '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //                 }
            //             }
            //             return $name;
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'agent',
            //     'select' => 'agent_username',
            //     'name' => lang("Agent"),
            //     'formatter' => function ($d, $row) use ($is_export, $show_agent) {
            //         if ($show_agent) {
            //             if ($d != null) {
            //                 $url = site_url('/agency_management/agent_information/' . $row['prh_agent_id']);
            //                 if ($is_export) {
            //                     return $d;
            //                 } else {
            //                     return '<a href="' . $url . '">' . $d . '</a>';
            //                 }
            //             } else {
            //                 if ($is_export) {
            //                     return trim(trim($d), ',') ?: lang('lang.norecyet');
            //                 } else {
            //                     return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //                 }
            //             }
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            array(
                'dt' => $i++,
                'alias' => 'createdOn',
                'name' => lang('report.pr10'),
                'select' => 'player.createdOn',
                'formatter' =>  function ($d, $row) use ($show_register_date, $is_export) {
                    if ($show_register_date) {
                        return $d;
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'first_deposit_datetime',
            //     'select' => 'MAX(first_deposit_datetime)',
            //     'name' => lang('aff.ap06'),
            //     'formatter' => function ($d, $row) use ($show_first_deposit_date, $is_export, &$subtotals) {
            //         if ($show_first_deposit_date) {
            //             return $d;
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            // array(
            //     'dt' => $this->utils->getConfig('display_last_deposit_col') ? $i++ : null,
            //     'alias' => 'lastDepositDateTime',
            //     'select' => 'player_last_transactions.last_deposit_date',
            //     'name' => lang('player.105'),
            //     'formatter' => function ($d, $row) use ($show_last_deposit_date, $is_export) {
            //         if ($show_last_deposit_date) {
            //             return $d;
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'total_bonus',
            //     'name' => lang('report.pr18'),
            //     'select' => 'SUM(player_report_hourly.total_bonus)',
            //     'formatter' =>  function ($d, $row) use ($show_total_bonus, $is_export, &$subtotals) {
            //         if ($show_total_bonus) {
            //             $subtotals['subtotals_total_bonus'] += $d;
            //             // return $this->data_tables->currencyFormatter($d - $row['subtract_bonus']);
            //             return $this->data_tables->currencyFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     }
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_bonus',
                'name' => lang('report.pr18'),
                'select' => 'player_additional_roulette.applied_bonus',
                'formatter' =>  function ($d, $row) use ($show_total_bonus, $is_export, &$subtotals) {
                    if ($show_total_bonus) {
                        $subtotals['subtotals_total_bonus'] += $d;
                        // return $this->data_tables->currencyFormatter($d - $row['subtract_bonus']);
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'first_deposit',
            //     'select' => 'MAX(first_deposit_amount)',
            //     'name' => lang('player.75'),
            //     'formatter' => function ($d, $row) use ($show_first_deposit, $is_export, &$subtotals) {
            //         if ($show_first_deposit) {
            //             $subtotals['subtotals_first_deposit'] += $d;
            //             return $this->data_tables->currencyFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit',
                'name' => lang('report.pr21'),
                'select' => 'SUM(player_report_hourly.total_deposit)',
                'formatter' =>  function ($d) use ($show_total_deposit, $is_export, &$subtotals) {
                    if ($show_total_deposit) {
                        $subtotals['subtotals_total_deposit'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'total_deposit_times',
            //     'name' => lang('yuanbao.deposit.times'),
            //     'select' => 'SUM(deposit_times)',
            //     'formatter' =>  function ($d) use ($show_total_deposit_times, $is_export, &$subtotals) {
            //         if ($show_total_deposit_times) {
            //             $subtotals['subtotals_total_deposit_times'] += $d;
            //             return $d;
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     }
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_withdrawal',
                'name' => lang('report.pr22'),
                'select' => 'SUM(total_withdrawal)',
                'formatter' =>  function ($d) use ($show_total_withdrawal, $is_export, &$subtotals) {
                    if ($show_total_withdrawal) {
                        $subtotals['subtotals_total_withdrawal'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'total_dw',
            //     'name' => lang('Net Deposit'),
            //     'select' => 'SUM(total_gross)',
            //     'formatter' =>  function ($d) use ($show_total_dw, $is_export, &$subtotals) {
            //         if ($show_total_dw) {
            //             $subtotals['subtotals_total_dw'] += $d;
            //             return $this->data_tables->currencyFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     }
            // ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'total_withdrawal_over_deposit',
            //     'name' => lang('WOD%'),
            //     'select' => 'SUM(total_withdrawal)/SUM(total_deposit)',
            //     'formatter' =>  function ($d) use ($show_total_dw, $is_export, &$subtotals) {
            //         if ($show_total_dw) {
            //             return $this->data_tables->percentageFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     }
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_bets',
                'name' => lang('cms.totalbets'),
                'select' => 'SUM(total_bet)',
                'formatter' => function ($d) use ($show_total_bets, $is_export, &$subtotals, $input) {
                    if ($show_total_bets) {
                        $subtotals['subtotals_total_bets'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'turn_around_time',
            //     'name' => lang('TAT'),
            //     'select' => 'SUM(total_bet)/ (SUM(total_deposit)+SUM(player_report_hourly.total_bonus))',
            //     'formatter' => function ($d) use ($show_total_bets, $is_export, &$subtotals, $input) {
            //         if ($show_total_bets) {
            //             $subtotals['subtotals_tat'] += $d;
            //             return $this->data_tables->currencyFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_win',
                'name' => lang('Win'),
                'select' => 'SUM(total_win)',
                'formatter' => function ($d) use ($show_total_win, $is_export, &$subtotals, $input) {
                    if ($show_total_win) {
                        $subtotals['subtotals_total_win'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_loss',
                'name' => lang('Loss'),
                'select' => 'SUM(total_loss)',
                'formatter' => function ($d) use ($show_total_loss, $is_export, &$subtotals, $input) {
                    if ($show_total_loss) {
                        $subtotals['subtotals_total_loss'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'total_payout',
            //     'name' => lang('Payout'),
            //     'select' => 'SUM(total_bet) - ( SUM(total_loss) - SUM(total_win) )',
            //     'formatter' => function ($d) use ($show_total_payout, $is_export, &$subtotals, $input) {
            //         if ($show_total_payout) {
            //             $subtotals['subtotals_total_payout'] += $d;
            //             return $this->data_tables->currencyFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            // array(
            //     'dt' => $i++,
            //     'alias' => 'payout_rate',
            //     'name' => lang('sys.payoutrate'),
            //     'select' => '(SUM(total_bet) - (SUM(total_loss)-SUM(total_win))) / SUM(total_bet)',
            //     'formatter' => function ($d) use ($show_payout_rate, $is_export, $input) {
            //         if ($show_payout_rate) {
            //             return $this->data_tables->percentageFormatter($d);
            //         } else {
            //             if ($is_export) {
            //                 return lang('lang.norecyet');
            //             }
            //             return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
            //         }
            //     },
            // ),
            array(
                'dt' => $i++,
                'alias' => 'total_revenue',
                'name' => lang('Game Revenue'),
                'select' => 'SUM(total_loss)-SUM(total_win)',
                'formatter' =>  function ($d) use ($show_total_revenue, $is_export, $input) {
                    if ($show_total_revenue) {
                        return $this->data_tables->currencyFormatter($d);
                    } else {
                        if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }

            ),
        );

        if ($is_export) {
            $this->data_tables->options['is_export'] = true;
            // $this->data_tables->options['only_sql']=true;
            if (empty($csv_filename)) {
                $csv_filename = $this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
        // $sql = $this->data_tables->last_query;
        // $result['last_query'] = $sql;
        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        /*
        #EXPORT - FOR VIEWING FIELDS TO EXPORT ON VIEW
        $result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);

        $subtotals['subtotals_payout_rate']  = empty($subtotals['subtotals_total_bets']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($subtotals['subtotals_total_payout'] / $subtotals['subtotals_total_bets']);
        $subtotals['subtotals_bod']  = empty($subtotals['subtotals_total_deposit']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($subtotals['subtotals_total_bonus'] / $subtotals['subtotals_total_deposit']);
        $subtotals['subtotals_wod']  = empty($subtotals['subtotals_total_deposit']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($subtotals['subtotals_total_withdrawal'] / $subtotals['subtotals_total_deposit']);
        $subtotals['subtotals_tat']  = ($subtotals['subtotals_total_bets'] && $subtotals['subtotals_dnb']) ? ($subtotals['subtotals_total_bets'] / $subtotals['subtotals_dnb']) : 0;
        $subtotals['subtotals_game_revenue']  = $subtotals['subtotals_total_loss'] - $subtotals['subtotals_total_win'];

        $result['subtotals'] = $subtotals;

        // $query = $this->db->select('MAX(first_deposit_amount) first_deposit_amount, MAX(second_deposit_amount) second_deposit_amount, SUM(subtract_bonus) subtract_bonus')->from('player_report_hourly');
        $query = $this->db->select('MAX(first_deposit_amount) first_deposit_amount, MAX(second_deposit_amount) second_deposit_amount, SUM(subtract_bonus) subtract_bonus')->from('player_report_hourly');
        if (count($where) > 0) {
            if (count($joins) > 0) {
                $joins = array_unique($joins);
                foreach ($joins as $key => $value) {
                    $joinMode = 'left';
                    if (!empty($this->innerJoins)) {
                        if (in_array($key, $this->innerJoins)) {
                            $joinMode = 'innder';
                        }
                    }
                    $query->join($key, $value, $joinMode);
                }
            }
            if ($search_string_1 = $this->_flatten($where)) {
                $search_string_1 = $this->db->compile_binds($search_string_1, $values);
                $query->where($search_string_1);
            }
        }

        $query->group_by($group_by);

        $rows = $this->runMultipleRowArray();

        $this->utils->printLastSQL();
        $deposit = ['first' => 0, 'second' => 0];
        $subtract_bonus = 0;
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $deposit['first'] += $row['first_deposit_amount'];
                $deposit['second'] += $row['second_deposit_amount'];
                $subtract_bonus -= $row['subtract_bonus'];
            }
        }

        $summary = $this->data_tables->summary(
            $request,
            $table,
            $joins,
            'SUM(player_report_hourly.total_cashback) cashback,
			 SUM(deposit_bonus) deposit_bonus,
			 SUM(referral_bonus) referral_bonus,
			 SUM(manual_bonus) total_manual,
			 SUM(player_report_hourly.total_bonus) total_bonus,
			 SUM(total_deposit) total_deposit,
			 SUM(deposit_times) deposit_times,
			 SUM(total_withdrawal) total_withdrawal,
			 SUM(total_gross) total_deposit_withdraw,
			 SUM(total_bet) total_bet,
			 SUM(payout) payout,
			 SUM(total_win) total_win,
			 SUM(total_loss) total_loss,',
            null,
            $columns,
            $where,
            $values
        );

        $total['total_cashback']       = $this->utils->formatCurrencyNoSym($summary[0]['cashback']);
        $total['total_deposit_bonus']  = $this->utils->formatCurrencyNoSym($summary[0]['deposit_bonus']);
        $total['total_referral_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['referral_bonus']);
        $total['total_add_bonus']      = $this->utils->formatCurrencyNoSym($summary[0]['total_manual']);
        $total['total_subtract_bonus'] = $this->utils->formatCurrencyNoSym($subtract_bonus);
        $total['total_total_bonus']    = $this->utils->formatCurrencyNoSym($summary[0]['total_bonus']);
        $total['total_first_deposit']  = $this->utils->formatCurrencyNoSym($deposit['first']);
        $total['total_second_deposit'] = $this->utils->formatCurrencyNoSym($deposit['second']);
        $total['total_deposit']        = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit']);
        $total['total_deposit_times']  = $summary[0]['deposit_times'] ?: 0;
        $total['total_withdrawal']     = $this->utils->formatCurrencyNoSym($summary[0]['total_withdrawal']);
        $total['total_dw']             = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit_withdraw']);
        $total['total_bets']           = $this->utils->formatCurrencyNoSym($summary[0]['total_bet']);
        $total_payout = $summary[0]['total_bet'] - ($summary[0]['total_loss'] - $summary[0]['total_win']);
        $total['total_payout']         = $this->utils->formatCurrencyNoSym($total_payout);
        $total['total_payout_rate']    = empty($summary[0]['total_bet']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($total_payout / $summary[0]['total_bet']);
        $dnb = $summary[0]['total_deposit'] + $summary[0]['total_bonus'];
        $total['total_dnb']            = $this->utils->formatCurrencyNoSym($dnb);
        $total['total_bod']            = empty($summary[0]['total_deposit']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($summary[0]['total_bonus'] / $summary[0]['total_deposit']);
        $total['total_wod']            = empty($summary[0]['total_deposit']) ? $this->data_tables->percentageFormatter(0) : $this->data_tables->percentageFormatter($summary[0]['total_withdrawal'] / $summary[0]['total_deposit']);
        $total['total_tat']            = empty($dnb) ? $this->utils->formatCurrencyNoSym(0) : $this->utils->formatCurrencyNoSym($summary[0]['total_bet'] / $dnb);
        $total['total_win']            = $this->utils->formatCurrencyNoSym($summary[0]['total_win']);
        $total['total_loss']           = $this->utils->formatCurrencyNoSym($summary[0]['total_loss']);
        $total['total_game_revenue']   = $this->utils->formatCurrencyNoSym($summary[0]['total_loss'] - $summary[0]['total_win']);
        $result['total'] = $total;
        */

        return $result;
    }

    public function player_additionl_report($request, $is_export = false){
        $readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('risk_score_model', 'player_kyc', 'kyc_status_model', 'agency_model', 'transactions', 'affiliatemodel', 'affiliate', 'player_promo', 'promorules'));
        $this->load->helper(['player_helper']);

        $i = 0;
        $this->data_tables->is_export = $is_export;
        $input = $this->data_tables->extra_search($request);

        $table = 'player_report_hourly';
        $joins = array();
        $where = array();
        $values = array();
        $group_by = array();
        $joins['player'] = "player.playerId = player_report_hourly.player_id";
        $joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player_report_hourly.level_id';
        $joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';
        $joins['player_last_transactions'] = "player_last_transactions.player_id = player.playerId";
        $group_by[] = 'player_report_hourly.player_id';

        # FILTER ######################################################################################################################################################################################

        if (empty($input['timezone'])) {
            $input['timezone'] = 0; // for undefined issue
        }

        if (isset($input['date_from'], $input['date_to'])) {
            $dateTimeFrom = $input['date_from'];
            $dateTimeTo   = $input['date_to'];

            /// for apply the timezone,
            // override the inputs, deposit_date_from and deposit_date_to.
            $dateTimeFrom = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeFrom);
            $dateTimeTo = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeTo);

            $where[]  = "player_report_hourly.date_hour >= ? AND player_report_hourly.date_hour <= ?";
            $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
            $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
        }

        if (isset($input['username'])) {
            $where[] = "player_username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player_username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player_username = ?";
                $values[] = $input['username'];
            }
        }

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array(
            array(
                'select' => 'affiliate_id'
            ),
            array(
                'select' => 'player.levelId',
                'alias' => 'level_id'
            ),
            array(
                'select' => 'player_report_hourly.agent_id',
                'alias' => 'prh_agent_id'
            ),
            array(
                'select' => 'player.levelName',
                'alias' => 'level_name'
            ),
            array(
                'select' => 'vipLevelName'
            ),
            array(
                'select' => 'player_report_hourly.player_id',
                'alias' => 'player_id'
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'name' => lang('report.pr01'),
                'select' => 'player.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return $d;
                    } else {
                        return '<a href="/player_management/userInformation/' . $row['player_id'] . '">' . $d . '</a>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'name' => lang("player.41"),
                'alias' => 'tagName',
                'select' => 'player.playerId',
                'formatter' => function ($d, $row) use ($is_export) {
                    return player_tagged_list($row['player_id'], $is_export);
                },
            ),
            array(
                'dt' => $i++,
                'name' => lang('report.pr03'),
                'alias' => 'member_level',
                'select' => 'player.groupName',
                'formatter' => function ($d, $row) use ($is_export) {
                    return lang($d) . " - " . lang($row['level_name']);
                },
            ),
            array(
                'dt' => !($this->utils->isEnabledFeature('close_aff_and_agent')) ? $i++ : null,
                'alias' => 'affiliates_name',
                'name' => lang('Affiliate'),
                'select' => 'affiliate_username',
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return trim(trim($d), ',') ?: lang('lang.norecyet');
                    } else {
                        return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => !($this->utils->isEnabledFeature('close_aff_and_agent')) ? $i++ : null,
                'alias' => 'agent',
                'select' => 'agent_username',
                'name' => lang("Agent"),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return trim(trim($d), ',') ?: lang('lang.norecyet');
                    } else {
                        return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $this->utils->getConfig('display_last_deposit_col') ? $i++ : null,
                'alias' => 'lastDepositDateTime',
                'select' => 'player_last_transactions.last_deposit_date',
                'name' => lang('player.105'),
                'formatter' => function ($d) use ($is_export) {
                    return $d;
                },
            ),

            array(
                'dt' => $i++,
                'alias' => 'total_cashback',
                'name' => lang('report.sum15'),
                'select' => 'SUM(player_report_hourly.total_cashback)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_bonus',
                'name' => lang('report.pr18'),
                'select' => 'SUM(player_report_hourly.total_bonus)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'first_deposit',
                'select' => 'MAX(first_deposit_amount)',
                'name' => lang('player.75'),
                'formatter' => function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit',
                'name' => lang('report.pr21'),
                'select' => 'SUM(player_report_hourly.total_deposit)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit_times',
                'name' => lang('yuanbao.deposit.times'),
                'select' => 'SUM(deposit_times)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $d;
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_withdrawal',
                'name' => lang('report.pr22'),
                'select' => 'SUM(total_withdrawal)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_dw',
                'name' => lang('Net Deposit'),
                'select' => 'SUM(total_gross)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_bets',
                'name' => lang('cms.totalbets'),
                'select' => 'SUM(total_bet)',
                'formatter' => function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_revenue',
                'name' => lang('Game Revenue'),
                'select' => 'SUM(total_loss)-SUM(total_win)',
                'formatter' =>  function ($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $this->utils->getConfig('display_net_loss_col') ? $i++ : NULL,
                'alias' => 'net_loss',
                'name' => lang('report.pr33'),
                'select' => '(SUM(total_gross) - SUM(player_report_hourly.total_bonus) - SUM(player_report_hourly.total_cashback) /* - player.total_total_nofrozen */ )',
                'formatter' =>  function($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_total_nofrozen',
                'select' => "player.total_total_nofrozen",
                'name' => lang('Total Balance'),
                'formatter' => function($d) use ($is_export) {
                    return $this->data_tables->currencyFormatter($d);
                }
            ),
        );

        if ($is_export) {
            $this->data_tables->options['is_export'] = true;
            // $this->data_tables->options['only_sql']=true;
            if (empty($csv_filename)) {
                $csv_filename = $this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
        // $sql = $this->data_tables->last_query;
        // $result['last_query'] = $sql;
        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        return $result;
    }
}
////END OF FILE/////////
