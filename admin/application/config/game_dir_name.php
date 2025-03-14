<?php

function game_dir_name($game_platform_id) {
    switch ($game_platform_id) {
        case PT_API:
        case PT_V2_API:
        case PT_V3_API:
        case T1PT_API:
        case PT_SEAMLESS_GAME_API:
        case T1_PT_SEAMLESS_GAME_API:
        case IDN_PT_SEAMLESS_GAME_API:
        case T1_IDN_PT_SEAMLESS_GAME_API:
        case IDN_SLOTS_PT_SEAMLESS_GAME_API:
        case T1_IDN_SLOTS_PT_SEAMLESS_GAME_API:
        case IDN_LIVE_PT_SEAMLESS_GAME_API:
        case T1_IDN_LIVE_PT_SEAMLESS_GAME_API:
            $dir = "playtech";
            break;
        case UC_API:
        case T1UC_API:
            $dir = "uc8";
            break;
        case TTG_API:
        case T1TTG_API:
        case TTG_SEAMLESS_GAME_API:
        case T1_TTG_SEAMLESS_GAME_API:
            $dir = "ttg";
            break;
        case TGP_AG_API:
            $dir = "asia_gaming";
            break;
        case SUNCITY_API:
        case T1SUNCITY_API:
            $dir = "sunbet";
            break;
        case SPADE_GAMING_API:
        case T1SPADE_GAMING_API:
        case SPADEGAMING_SEAMLESS_GAME_API:
        case T1_SPADEGAMING_SEAMLESS_GAME_API:
        case IDN_SPADEGAMING_SEAMLESS_GAME_API:
        case T1_IDN_SPADEGAMING_SEAMLESS_GAME_API:
            $dir = "spadegaming";
            break;
        case SA_GAMING_SEAMLESS_THB1_API:
        case SA_GAMING_SEAMLESS_API:
        case SA_GAMING_API:
        case T1SA_GAMING_API:
        case T1_SA_GAMING_SEAMLESS_GAME_API:
            $dir = "sagaming";
            break;
        case RTG_API:
        case T1RTG_MASTER_API:
        case RTG2_SEAMLESS_GAME_API:
        case T1_RTG2_SEAMLESS_GAME_API:
        case RTG_SEAMLESS_GAME_API:
        case T1_RTG_SEAMLESS_GAME_API:
            $dir = "rtg";
            break;
        case QT_API:
        case T1QT_API:
            $dir = "qt";
            break;
        case T1PRAGMATICPLAY_API:
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
        case PRAGMATICPLAY_SEAMLESS_API:
        case PRAGMATICPLAY_SEAMLESS_IDR1_API:
        case PRAGMATICPLAY_SEAMLESS_CNY1_API:
        case PRAGMATICPLAY_SEAMLESS_THB1_API:
        case PRAGMATICPLAY_SEAMLESS_USD1_API:
        case PRAGMATICPLAY_SEAMLESS_VND1_API:
        case PRAGMATICPLAY_SEAMLESS_MYR1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_IDR1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_CNY1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_THB1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_USD1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_VND1_API:
        case PRAGMATICPLAY_LIVEDEALER_SEAMLESS_MYR1_API:
        case PRAGMATICPLAY_SEAMLESS_STREAMER_API:
        case T1_PRAGMATICPLAY_SEAMLESS_API:
        case T1_IDN_PRAGMATICPLAY_SEAMLESS_API:
        case IDN_PRAGMATICPLAY_SEAMLESS_API:
        case IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API:
        case IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API:
        case T1_IDN_SLOTS_PRAGMATICPLAY_SEAMLESS_API:
        case T1_IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API:
            $dir = "pragmatic";
            break;
        case PNG_SEAMLESS_GAME_API:
        case PNG_API:
        case T1PNG_API:
            $dir = "png";
            break;
        case PGSOFT_API:
        case PGSOFT3_API:
        case PGSOFT_SEAMLESS_API:
        case PGSOFT2_SEAMLESS_API:
        case T1_PGSOFT2_SEAMLESS_API:
        case T1_PGSOFT_SEAMLESS_API:
        case PGSOFT3_SEAMLESS_API:
        case T1_PGSOFT3_SEAMLESS_API:
        case LIVE12_PGSOFT_SEAMLESS_API:
        case IDN_PGSOFT_SEAMLESS_API:
        case T1_IDN_PGSOFT_SEAMLESS_API:
            $dir = "pg";
            break;
        case OPUS_API:
            $dir = "opus";
            break;
        case T1_MGPLUS_SEAMLESS_GAME_API:
        case MG_API:
        case T1MG_API:
        case MG_DASHUR_API:
        case T1MGPLUS_API:
        case MGPLUS_API:
        case MGPLUS2_API:
        case MGPLUS_SEAMLESS_API:
        case T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
        case T1_IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
        case IDN_SLOTS_MGPLUS_SEAMLESS_GAME_API:
        case IDN_LIVE_MGPLUS_SEAMLESS_GAME_API:
            $dir = "microgaming";
            break;
        case JUMB_GAMING_API:
        case T1JUMB_API:
            $dir = "jumbo";
            break;
        case ISB_INR1_API:
        case ISB_API:
        case T1ISB_API:
            $dir = "isoftbet";
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
        case T1_HABANERO_SEAMLESS_GAME_API:
        case HABANERO_SEAMLESS_GAMING_API:
        case IDN_HABANERO_SEAMLESS_GAMING_API:
        case T1_IDN_HABANERO_SEAMLESS_GAMING_API:
        case HB_API:
        case T1HB_API:
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
            $dir = "habanero";
            break;
        case GD_API:
        case T1GD_API:
        case GD_SEAMLESS_API:
            $dir = "gd";
            break;
        case GAMEPLAY_API:
            $dir = "gameplay";
            break;
        case DT_API:
        case T1DT_API:
            $dir = "dt";
            break;
        case CQ9_API:
        case T1CQ9_API:
        case CQ9_SEAMLESS_GAME_API:
        case T1_CQ9_SEAMLESS_API:
            $dir = "cq9";
            break;
        case T1_PNG_SEAMLESS_API:
            $dir = "png";
            break;
        case YUXING_CQ9_GAME_API:
            $dir = "yuxing_cq9";
            break;
        case BETSOFT_API:
            $dir = "betsoft";
            break;
        case GOLDENF_PGSOFT_API:
            $dir = "goldenf_pgsoft";
            break;
        case T1_WM2_SEAMLESS_GAME_API:
        case WM2_SEAMLESS_GAME_API:
        case T1_WM_SEAMLESS_GAME_API:
        case WM_SEAMLESS_GAME_API:
            $dir = "wm_casino";
            break;
        case BOOMING_SEAMLESS_API:
        case T1_BOOMING_SEAMLESS_API:
        case BOOMING_SEAMLESS_GAME_API:
        case T1_BOOMING_SEAMLESS_GAME_API:
            $dir = "booming";
            break;
        case AVIA_ESPORT_API:
            $dir = "avia_esport";
            break;
        case OG_V2_API:
            $dir = "og_v2";
            break;
        case T1AE_SLOTS_API:
        case AE_SLOTS_GAMING_API:
            $dir = "ae_slots_gaming";
            break;
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
        case FLOW_GAMING_PLAYTECH_SEAMLESS_API:
        case FLOW_GAMING_MG_SEAMLESS_API:
        case T1_FLOW_GAMING_SEAMLESS_API:
        case T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API:
            $dir = "flow_gaming";
            break;
        case REDRAKE_GAMING_API:
            $dir = "redrake_gaming";
            break;
        case TPG_API:
            $dir = "tpg";
            break;
        case ASIASTAR_API:
            $dir = "asiastar";
            break;
        case SOLID_GAMING_THB_API:
            $dir = "solid_gaming";
            break;
        case LAPIS_API:
            $dir = "microgaminglapis";
            break;
        case OGPLUS_API:
            $dir = "ogplus";
            break;
        case S128_GAME_API:
            $dir = "s128";
            break;
        case HUB88_API:
            $dir = "hub88";
            break;
        case GENESIS_SEAMLESS_API:
        case GENESIS_SEAMLESS_THB1_API:
            $dir = "genesis";
            break;
        case GPK_API:
            $dir = "gpk";
            break;
        case RGS_API:
            $dir = "rgs";
            break;
        case WM_API:
        case T1WM_API:
            $dir = "wm";
            break;
        case AMG_API:
            $dir = "amg";
            break;
        case TIANHAO_API:
            $dir = "tianhao";
            break;
        case N2LIVE_API:
            $dir = "n2live";
            break;
        case EVOPLAY_GAME_API_IDR1_API:
        case EVOPLAY_GAME_API_CNY1_API:
        case EVOPLAY_GAME_API_THB1_API:
        case EVOPLAY_GAME_API_MYR1_API:
        case EVOPLAY_GAME_API_VND1_API:
        case EVOPLAY_GAME_API_USD1_API:
        case EVOPLAY_GAME_API:
            $dir = "evoplay";
            break;
        case KINGPOKER_GAME_API_IDR1_API:
        case KINGPOKER_GAME_API_CNY1_API:
        case KINGPOKER_GAME_API_THB1_API:
        case KINGPOKER_GAME_API_MYR1_API:
        case KINGPOKER_GAME_API_VND1_API:
        case KINGPOKER_GAME_API_USD1_API:
        case KINGPOKER_GAME_API:
            $dir = "kingpoker";
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
            $dir = "pretty_gaming";
            break;
        case HA_GAME_API:
            $dir = "ha_gaming";
            break;
        case PLAYSTAR_SEAMLESS_GAME_API:
        case IDN_PLAYSTAR_SEAMLESS_GAME_API:
        case T1_IDN_PLAYSTAR_SEAMLESS_GAME_API:
        case PLAYSTAR_API:
            $dir = 'playstar';
            break;
        case HKB_GAME_API:
            $dir = 'hkb';
            break;
        case RUBYPLAY_SEAMLESS_API:
        case RUBYPLAY_SEAMLESS_IDR1_API:
        case RUBYPLAY_SEAMLESS_CNY1_API:
        case RUBYPLAY_SEAMLESS_THB1_API:
        case RUBYPLAY_SEAMLESS_MYR1_API:
        case RUBYPLAY_SEAMLESS_VND1_API:
        case RUBYPLAY_SEAMLESS_USD1_API:
            $dir = 'ruby';
            break;
        case KING_MAKER_GAMING_API:
        case KING_MAKER_GAMING_THB_B1_API:
        case KING_MAKER_GAMING_THB_B2_API:
        case QUEEN_MAKER_GAME_API:
        case KING_MIDAS_GAME_API:
            $dir = 'kingmaker';
            break;
        case NETENT_SEAMLESS_GAME_API:
        case NETENT_SEAMLESS_GAME_IDR1_API:
        case NETENT_SEAMLESS_GAME_CNY1_API:
        case NETENT_SEAMLESS_GAME_THB1_API:
        case NETENT_SEAMLESS_GAME_MYR1_API:
        case NETENT_SEAMLESS_GAME_VND1_API:
        case NETENT_SEAMLESS_GAME_USD1_API:
            $dir = 'netent';
            break;
        case T1YOPLAY_API:
            $dir = 'yoplay';
            break;
        case ONEGAME_GAME_API:
            $dir = 'onegame';
            break;
        case LIVE12_SPADEGAMING_SEAMLESS_API:
            $dir = 'spadegaming';
            break;
        case LIVE12_REDTIGER_SEAMLESS_API:
        case QUEEN_MAKER_REDTIGER_GAME_API:
            $dir = 'redtiger';
            break;
        case LIVE12_EVOLUTION_SEAMLESS_API:
            $dir = 'evoplay';
            break;
        case YGG_SEAMLESS_GAME_API:
        case YGGDRASIL_API:
        case YGG_DCS_SEAMLESS_GAME_API:
        case T1_YGG_DCS_SEAMLESS_GAME_API:
            $dir = 'ygg';
            break;
        case T1_CALETA_SEAMLESS_API:
        case CALETA_SEAMLESS_API:
            $dir = 'caleta';
            break;
        case KA_SEAMLESS_API:
            $dir = 'kagaming';
            break;
        case AMB_SEAMLESS_GAME_API:
            $dir = 'amb_poker';
            break;
        case SLOT_FACTORY_GAME_API:
        case SLOT_FACTORY_SEAMLESS_API:
            $dir = 'slotsfactory';
            break;
        case ICONIC_SEAMLESS_API:
            $dir = 'iconic_gaming';
            break;
        case JOKER_API;
        case JOKER_V2_API:
        case BDM_SEAMLESS_API:
            $dir = 'joker_gaming';
            break;
        case SGWIN_API:
            $dir = 'sgwin';
            break;
        case BBGAME_API:
            $dir = 'bbgame';
            break;
        case KGAME_API:
            $dir = 'kgame';
            break;
        case IDNPOKER_API:
            $dir = 'idnpoker';
            break;
        case AMB_PGSOFT_SEAMLESS_API:
            $dir = 'amb_pgsoft';
            break;
        case JUMBO_SEAMLESS_GAME_API:
        case T1_JUMBO_SEAMLESS_GAME_API:
            $dir = 'jumbo';
            break;
        case CHERRY_GAMING_SEAMLESS_GAME_API:
        case T1_CHERRY_GAMING_SEAMLESS_GAME_API:
            $dir = 'cherry_gaming';
            break;
        case SV388_SEAMLESS_GAME_API:
        case T1_SV388_SEAMLESS_GAME_API:
            $dir = 'sv388';
            break;
        case BETER_SEAMLESS_GAME_API:
        case T1_BETER_SEAMLESS_GAME_API:
            $dir = 'beter';
            break;
        case BETER_SPORTS_SEAMLESS_GAME_API:
        case T1_BETER_SPORTS_SEAMLESS_GAME_API:
            $dir = 'betsy';
            break;
        case T1_EBET_SEAMLESS_GAME_API:
        case EBET_SEAMLESS_GAME_API:
            $dir = 'ebet_seamless';
            break;
        case EVENBET_POKER_SEAMLESS_GAME_API:
            $dir = 'evenbet_seamless';
            break;
        case IPM_V2_IMSB_ESPORTSBULL_API:
            $dir = 'ipm';
            break;
        case JQ_GAME_API:
            $dir = 'jq';
            break;
        case LUCKY365_GAME_API:
            $dir = 'lucky365';
            break;
        case LIONKING_GAME_API:
            $dir = 'lionking';
            break;
        case T1_EVOPLAY_SEAMLESS_GAME_API:
        case EVOPLAY_SEAMLESS_GAME_API:
            $dir = 'evoplay';
            break;
        case IDNLIVE_SEAMLESS_GAME_API:
            $dir = 'idnlive';
            break;
        case PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay';
            break;
        case ORYX_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_oryx';
            break;
        case BEFEE_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_befee';
            break;
        case TRIPLECHERRY_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_triplecherry';
            break;
        case DARWIN_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_darwin';
            break;
        case SPINOMENAL_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_spinomenal';
            break;
        case SMARTSOFT_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_smartsoft';
            break;
        case AMATIC_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_amatic';
            break;
        case OTG_GAMING_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_otg';
            break;
        case FBM_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_fbm';
            break;
        case BOOMING_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_bmg';
            break;
        case HACKSAW_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_hacksaw';
            break;
        case HIGH5_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_high5';
            break;
        case PLAYSON_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_playson';
            break;
        case SPINMATIC_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_spinmatic';
            break;
        case BGAMING_SEAMLESS_GAME_API:
        case T1_BGAMING_SEAMLESS_GAME_API:
            $dir = 'bgaming';
            break;
        case WAZDAN_SEAMLESS_GAME_API:
        case T1_WAZDAN_SEAMLESS_GAME_API:
            $dir = "wazdan";
            break;
        case SOFTSWISS_SEAMLESS_GAME_API:
        case SOFTSWISS_BGAMING_SEAMLESS_GAME_API:
            $dir = "softswiss";
            break;
        case SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API:
            $dir = 'evolution';
            break;
        case SOFTSWISS_SPRIBE_SEAMLESS_GAME_API:
            $dir = 'spribe';
            break;
        case SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API:
            $dir = 'softswiss_evoplay';
            break;
        case SOFTSWISS_BETSOFT_SEAMLESS_GAME_API:
            $dir = 'betsoft';
            break;
        case T1_YL_SEAMLESS_GAME_API:
        case YL_NTTECH_SEAMLESS_GAME_API:
            $dir = 'yl_nttech';
            break;
        case SOFTSWISS_WAZDAN_SEAMLESS_GAME_API:
            $dir = 'wazdan';
            break;
        case AG_SEAMLESS_GAME_API:
            $dir = 'ag';
            break;
        case PINNACLE_SEAMLESS_GAME_API:
        case T1_PINNACLE_SEAMLESS_GAME_API:
            $dir = 'pinnacle';
            break;
        case TADA_SEAMLESS_GAME_API:
        case T1_TADA_SEAMLESS_GAME_API:
            $dir = 'tada';
            break;
        case CMD_SEAMLESS_GAME_API:
        case CMD2_SEAMLESS_GAME_API:
        case T1_CMD_SEAMLESS_GAME_API:
        case T1_CMD2_SEAMLESS_GAME_API:
            $dir = 'cmd';
            break;
        case T1_EVOLUTION_SEAMLESS_GAME_API:
        case EVOLUTION_SEAMLESS_GAMING_API:
        case IDN_EVOLUTION_SEAMLESS_GAMING_API:
        case EVOLUTION_GAMING_API:
            $dir = 'evolution';
            break;
        case EVOLUTION_NETENT_SEAMLESS_GAMING_API:
        case IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
            $dir = 'evolution_netent';
            break;
        case EVOLUTION_NLC_SEAMLESS_GAMING_API:
        case IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
            $dir = 'evolution_nlc';
            break;
        case EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
        case IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
            $dir = 'evolution_redtiger';
            break;
        case EVOLUTION_BTG_SEAMLESS_GAMING_API:
        case IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
            $dir = 'evolution_btg';
            break;
        case IBC_ONEBOOK_SEAMLESS_API:
        case T1_IBC_ONEBOOK_SEAMLESS_API:
            $dir = 'ibc_onebook';
            break;
        case BGSOFT_GAME_API:
        case BGSOFT_SEAMLESS_GAME_API:
        case T1_BGSOFT_SEAMLESS_GAME_API:
            $dir = 't1games';
            break;
        case T1GAMES_SEAMLESS_GAME_API:
            $dir = 't1games';
            break;
        case T1_EZUGI_EVO_SEAMLESS_GAME_API:
        case EZUGI_EVO_SEAMLESS_API:
            $dir = "ezugi_evo";
            break;
        case T1_EZUGI_REDTIGER_SEAMLESS_GAME_API:
        case EZUGI_REDTIGER_SEAMLESS_API:
            $dir = "ezugi_redtiger";
            break;
        case T1_EZUGI_NETENT_SEAMLESS_GAME_API:
        case EZUGI_NETENT_SEAMLESS_API:
            $dir = "ezugi_netent";
            break;
        case T1_EZUGI_SEAMLESS_GAME_API:
        case EZUGI_SEAMLESS_API:
            $dir = "ezugi";
            break;
        case SV388_AWC_SEAMLESS_GAME_API:
        case T1_SV388_AWC_SEAMLESS_GAME_API:
            $dir = 'sv388';
            break;
        case T1_FC_SEAMLESS_GAME_API:
        case FC_SEAMLESS_GAME_API:
            $dir = "fachai";
            break;
        case JILI_GAME_API:
        case JILI_SEAMLESS_API:
        case T1_JILI_SEAMLESS_API:
            $dir = "jili";
            break;
        case T1_AFB_SBOBET_SEAMLESS_GAME_API:
        case AFB_SBOBET_SEAMLESS_GAME_API:
            $dir = 'afb';
            break;
        case SPRIBE_JUMBO_SEAMLESS_GAME_API:
        case T1_SPRIBE_JUMBO_SEAMLESS_GAME_API:
            $dir = 'spribe';
            break;
        case HACKSAW_DCS_SEAMLESS_GAME_API:
        case T1_HACKSAW_DCS_SEAMLESS_GAME_API:
        case HACKSAW_SEAMLESS_GAME_API:
        case T1_HACKSAW_SEAMLESS_GAME_API:
            $dir = 'hacksaw';
            break;
        case WE_SEAMLESS_GAME_API:
        case T1_WE_SEAMLESS_GAME_API:
            $dir = 'worldentertainment';
            break;
        case T1_QT_HACKSAW_SEAMLESS_API:
            $dir = 'qt_hacksaw';
            break;
        case QT_HACKSAW_SEAMLESS_API:
        case QT_NOLIMITCITY_SEAMLESS_API:
        case T1_QT_NOLIMITCITY_SEAMLESS_API:
            $dir = 'qt';
            break;
        case KING_MAKER_SEAMLESS_GAME_API:
        case T1_KING_MAKER_SEAMLESS_GAME_API:
            $dir = 'kingmidas';
            break;
        case BETIXON_SEAMLESS_GAME_API:
        case T1_BETIXON_SEAMLESS_GAME_API:
            $dir = 'betixon';
            break;
        case SPINOMENAL_SEAMLESS_GAME_API:
        case T1_SPINOMENAL_SEAMLESS_GAME_API:
            $dir = 'spinomenal';
            break;
        case ULTRAPLAY_API:
        case ULTRAPLAY_SEAMLESS_GAME_API:
        case T1_ULTRAPLAY_SEAMLESS_GAME_API:
            $dir = 'ultraplay';
            break;
        case YEEBET_API:
        case YEEBET_SEAMLESS_GAME_API:
        case T1_YEEBET_SEAMLESS_GAME_API:
            $dir = 'yeebet';
            break;
        case MGW_SEAMLESS_GAME_API:
        case T1_MGW_SEAMLESS_GAME_API:
            $dir = 'mgw';
            break;
        case T1_SMARTSOFT_SEAMLESS_GAME_API:
        case SMARTSOFT_SEAMLESS_GAME_API:
            $dir = 'smartsoft';
            break;
        case WON_CASINO_SEAMLESS_GAME_API:
        case T1_WON_CASINO_SEAMLESS_GAME_API:
            $dir = 'woncasino';
            break;
        case BETGAMES_SEAMLESS_GAME_API:
        case T1_BETGAMES_SEAMLESS_GAME_API:
            $dir = 'betgames';
            break;
        case TWAIN_SEAMLESS_GAME_API:
        case T1_TWAIN_SEAMLESS_GAME_API:
            $dir = 'twain';
            break;
        case IM_SEAMLESS_GAME_API:
        case T1_IM_SEAMLESS_GAME_API:
            $dir = 'im';
            break;
        case HP_2D3D_GAME_API:
            $dir = 'hp_2d3d';
            break;
        case ASTAR_SEAMLESS_GAME_API:
        case T1_ASTAR_SEAMLESS_GAME_API:
            $dir = 'astar';
            break;
        case ENDORPHINA_SEAMLESS_GAME_API:
        case T1_ENDORPHINA_SEAMLESS_GAME_API:
            $dir = 'endorphina';
            break;
        case BIGPOT_SEAMLESS_GAME_API:
        case T1_BIGPOT_SEAMLESS_GAME_API:
            $dir = 'bigpot';
            break;
        case HP_LOTTERY_GAME_API:
            $dir = 'hp_lottery';
            break;
        case T1LOTTERY_SEAMLESS_API:
            $dir = 't1games';
            break;
        case BISTRO_SEAMLESS_API:
            $dir = 'bistro';
            break;
        case AVATAR_UX_DCS_SEAMLESS_GAME_API:
        case T1_AVATAR_UX_DCS_SEAMLESS_GAME_API:
            $dir = 'avatar_ux';
            break;
        case RELAX_DCS_SEAMLESS_GAME_API:
        case T1_RELAX_DCS_SEAMLESS_GAME_API:
            $dir = 'relax';
            break;
        case BELATRA_SEAMLESS_GAME_API:
        case T1_BELATRA_SEAMLESS_GAME_API:
            $dir = 'belatra';
            break;
        case SPRIBE_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_spribe';
            break;

        case NEXTSPIN_GAME_API:
        case NEXTSPIN_SEAMLESS_GAME_API:
        case T1_NEXTSPIN_SEAMLESS_GAME_API:
            $dir = 'nextspin';
            break;
        case PEGASUS_SEAMLESS_GAME_API:
        case T1_PEGASUS_SEAMLESS_GAME_API:
            $dir = 'pegasus';
            break;
        case BNG_SEAMLESS_GAME_API:
        case T1_BNG_SEAMLESS_GAME_API:
            $dir = 'booongo';
            break;
        case SPINIX_SEAMLESS_GAME_API:
        case T1_SPINIX_SEAMLESS_GAME_API:
            $dir = 'spinix';
            break;
        case DRAGOONSOFT_SEAMLESS_GAME_API:
        case T1_DRAGOONSOFT_SEAMLESS_GAME_API:
            $dir = 'dragoonsoft';
            break;
        case FASTSPIN_SEAMLESS_GAME_API:
        case T1_FASTSPIN_SEAMLESS_GAME_API:
            $dir = 'fastspin';
            break;
        case ONEAPI_PP_SEAMLESS_GAME_API:
            $dir = 'oneapi_pp';
            break;
        case ONEAPI_BGAMING_SEAMLESS_GAME_API:
            $dir = 'oneapi_bgaming';
            break;
        case ONEAPI_HABANERO_SEAMLESS_GAME_API:
            $dir = 'oneapi_habanero';
            break;
        case ONEAPI_EVOPLAY_SEAMLESS_GAME_API:
            $dir = 'oneapi_evoplay';
            break;
        case ONEAPI_NETENT_SEAMLESS_GAME_API:
            $dir = 'oneapi_netent';
            break;
        case ONEAPI_REDTIGER_SEAMLESS_GAME_API:
            $dir = 'oneapi_redtiger';
            break;
        case ONEAPI_EZUGI_SEAMLESS_GAME_API:
            $dir = 'oneapi_ezugi';
            break;
        case ONEAPI_JDB_SEAMLESS_GAME_API:
            $dir = 'oneapi_jdb';
            break;
        case ONEAPI_QUEENMAKER_SEAMLESS_GAME_API:
            $dir = 'oneapi_kingmidas'; #used Kingmidas
            break;
        case ONEAPI_YEEBET_SEAMLESS_GAME_API:
            $dir = 'oneapi_yeebet';
            break;
        case ONEAPI_WINFINITY_SEAMLESS_GAME_API:
            $dir = 'oneapi_winfinity';
            break;
        case ONEAPI_ILOVEU_SEAMLESS_GAME_API:
            $dir = 'oneapi_iloveu';
            break;
        case ONEAPI_ADVANTPLAY_SEAMLESS_GAME_API:
            $dir = 'oneapi_advantplay';
            break;
        case ONEAPI_BNG_SEAMLESS_GAME_API:
            $dir = 'oneapi_bng';
            break;
        case ONEAPI_NLC_SEAMLESS_GAME_API:
            $dir = 'oneapi_nlc';
            break;
        case ONEAPI_BTG_SEAMLESS_GAME_API:
            $dir = 'oneapi_btg';
            break;
        case ONEAPI_JDBGTF_SEAMLESS_GAME_API:
            $dir = 'oneapi_jdbgtf';
            break;
        case ONEAPI_SPINIX_SEAMLESS_GAME_API:
            $dir = 'oneapi_spinix';
            break;
        case ONEAPI_SPADEGAMING_SEAMLESS_GAME_API:
            $dir = 'oneapi_spadegaming';
            break;
        case ONEAPI_YELLOWBAT_SEAMLESS_GAME_API:
            $dir = 'oneapi_yellowbat';
            break;
        case ONEAPI_RELAXGAMING_SEAMLESS_GAME_API:
            $dir = 'oneapi_relaxgaming';
            break;
        case ONEAPI_PNG_SEAMLESS_GAME_API:
            $dir = 'oneapi_png';
            break;
        case ONEAPI_HACKSAW_SEAMLESS_GAME_API:
            $dir = 'oneapi_hacksaw';
            break;
        case ONEAPI_CQ9_SEAMLESS_GAME_API:
            $dir = 'oneapi_cq9';
            break;
        case ONEAPI_FACHAI_SEAMLESS_GAME_API:
            $dir = 'oneapi_fachai';
            break;
        case ONEAPI_SPRIBE_SEAMLESS_GAME_API:
            $dir = 'oneapi_spribe';
            break;
        case ONEAPI_3OAKS_SEAMLESS_GAME_API:
            $dir = 'oneapi_3oaks';
            break;
        case ONEAPI_BOOMING_SEAMLESS_GAME_API:
            $dir = 'oneapi_booming';
            break;
        case ONEAPI_SPINOMENAL_SEAMLESS_GAME_API:
            $dir = 'oneapi_spinomenal';
            break;
        case ONEAPI_EPICWIN_SEAMLESS_GAME_API:
            $dir = 'oneapi_epicwin';
            break;
        case ONEAPI_CPGAMES_SEAMLESS_GAME_API:
            $dir = 'oneapi_cpgames';
            break;
        case ONEAPI_LIVE22_SEAMLESS_GAME_API:
            $dir = 'oneapi_live22';
            break;
        case ONEAPI_CG_SEAMLESS_GAME_API:
            $dir = 'oneapi_cg';
            break;
        case ONEAPI_DB_SEAMLESS_GAME_API:
            $dir = 'oneapi_db';
            break;
        case ONEAPI_ALIZE_SEAMLESS_GAME_API:
            $dir = 'oneapi_alize';
            break;
        case ONEAPI_TURBOGAMES_SEAMLESS_GAME_API:
            $dir = 'oneapi_turbogames';
            break;
        case ONEAPI_LIVE88_SEAMLESS_GAME_API:
            $dir = 'oneapi_live88';
            break;
        case MASCOT_SEAMLESS_GAME_API:
        case T1_MASCOT_SEAMLESS_GAME_API:
            $dir = 'mascot';
            break;
        case POPOK_GAMING_SEAMLESS_GAME_API:
        case T1_POPOK_GAMING_SEAMLESS_GAME_API:
            $dir = 'popok_gaming';
            break;
        case AVIATRIX_SEAMLESS_GAME_API:
        case T1_AVIATRIX_SEAMLESS_GAME_API:
            $dir = 'aviatrix';
            break;
        case AOG_GAME_API:
            $dir = 'aog';
            break;
        case MPOKER_SEAMLESS_GAME_API:
        case T1_MPOKER_SEAMLESS_GAME_API:
            $dir = 'mpoker';
            break;
        case REDGENN_PLAYSON_SEAMLESS_GAME_API:
        case T1_REDGENN_PLAYSON_SEAMLESS_GAME_API:
        case REDGENN_PLAYSON_SEAMLESS_STREAMER_GAME_API:
            $dir = 'playson';
            break;
        case ONE_TOUCH_SEAMLESS_GAME_API:
        case T1_ONE_TOUCH_SEAMLESS_GAME_API:
            $dir = 'one_touch';
            break;
        case AB_SEAMLESS_GAME_API:
        case T1_AB_SEAMLESS_GAME_API:
            $dir = 'allbet';
            break;
        case SIMPLEPLAY_SEAMLESS_GAME_API:
            $dir = 'simpleplay';
            break;
        case FBSPORTS_SEAMLESS_GAME_API:
            $dir = 'fbsports';
            break;
        case SEXY_BACCARAT_SEAMLESS_API:
            $dir = 'aesexy';
            break;
        case CREEDROOMZ_SEAMLESS_GAME_API:
            $dir = 'creedroomz';
            break;
        case PASCAL_SEAMLESS_GAME_API:
            $dir = 'pascal';
            break;
        case LIGHTNING_SEAMLESS_GAME_API:
            $dir = 'lightning';
            break;
        case VIVOGAMING_SEAMLESS_API:
        case T1_VIVOGAMING_SEAMLESS_API:
            $dir = 'vivogaming';
            break;
        case WIZARD_PARIPLAY_SEAMLESS_API:
            $dir = 'pariplay_wizard';
            break;
        case HOLI_SEAMLESS_GAME_API:
        case T1_HOLI_SEAMLESS_GAME_API:
            $dir = 'holi';
            break;
        case WORLDMATCH_CASINO_SEAMLESS_API:
        case T1_WORLDMATCH_CASINO_SEAMLESS_API:
            $dir = 'worldmatchcasino';
            break;
        case BFGAMES_SEAMLESS_GAME_API:
        case T1_BFGAMES_SEAMLESS_GAME_API:
            $dir = 'bfgames';
            break;
        case TOM_HORN_SEAMLESS_GAME_API:
        case T1_TOM_HORN_SEAMLESS_GAME_API:
            $dir = 'tomhorn';
            break;
        case PG_JGAMEWORKS_SEAMLESS_API:
            $dir = 'jgameworks_pg';
            break;
        case JILI_JGAMEWORKS_SEAMLESS_API:
            $dir = 'jgameworks_jili';
            break;
        case PP_JGAMEWORKS_SEAMLESS_API:
            $dir = 'jgameworks_pragmatic';
            break;
        case AMEBA_SEAMLESS_GAME_API:
            $dir = 'ameba';
            break;
        case FIVEG_GAMING_SEAMLESS_API:
        case T1_FIVEG_GAMING_SEAMLESS_API:
            $dir = '5g';
            break;
        case IDN_PLAY_SEAMLESS_GAME_API:
        case T1_IDN_PLAY_SEAMLESS_GAME_API:
            $dir = 'idnplay';
            break;
        case FA_WS168_SEAMLESS_GAME_API:
        case T1_FA_WS168_SEAMLESS_GAME_API:
            $dir = 'fa_ws168';
            break;
        default:
            $dir = $game_platform_id;
            break;
    }

    return $dir;
}