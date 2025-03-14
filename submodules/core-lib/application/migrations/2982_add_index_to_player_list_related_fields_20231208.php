<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_list_related_fields_20231208 extends CI_Migration {

	public function up() {
        $this->load->model('player_model');

        // affiliates.parentId in affiliatemodel::getDirectDownlinesAffiliateIdsByParentId()
        $tableName = 'affiliates';
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists('parentId', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_parentId', 'parentId');
            }
        }
        // affiliates.username in affiliatemodel::getAffiliateIdByUsername()
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists('username', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_username', 'username');
            }
        }


        /// Ignored, not found any input in using.
        // // game_provider_auth.game_provider_id
        // // game_provider_auth.is_blocked
        // $tableName = 'game_provider_auth';
        // if( $this->utils->table_really_exists($tableName) ){
        //     if( $this->db->field_exists('game_provider_id', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_game_provider_id', 'game_provider_id');
        //     }
        // }
        // if( $this->utils->table_really_exists($tableName) ){
        //     if( $this->db->field_exists('is_blocked', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_is_blocked', 'is_blocked');
        //     }
        // }

        // player.allowed_withdrawal_status #1
        // player.approved_deposit_count #2
        // player.disabled_cashback #3
        // player.disabled_promotion #4
        // player.invitationCode #5
        // player.levelId #6
        // player.registered_by #7
        // player.totalDepositAmount #8
        // player.total_total_nofrozen #9
        // player.verified_email #10
        // player.verified_phone #11
        $tableName = 'player';
        // if( $this->utils->table_really_exists($tableName) ){ // #1 /// Ignored, not found any input in using.
        //     if( $this->db->field_exists('allowed_withdrawal_status', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_allowed_withdrawal_status', 'allowed_withdrawal_status');
        //     }
        // }
        // if( $this->utils->table_really_exists($tableName) ){ // #2
        //     if( $this->db->field_exists('approved_deposit_count', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_approved_deposit_count', 'approved_deposit_count');
        //     }
        // }
        if( $this->utils->table_really_exists($tableName) ){ // #3
            if( $this->db->field_exists('disabled_cashback', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_disabled_cashback', 'disabled_cashback');
            }
        }
        if( $this->utils->table_really_exists($tableName) ){ // #4
            if( $this->db->field_exists('disabled_promotion', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_disabled_promotion', 'disabled_promotion');
            }
        }
        if( $this->utils->table_really_exists($tableName) ){ // #5
            if( $this->db->field_exists('invitationCode', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_invitationCode', 'invitationCode');
            }
        }
        if( $this->utils->table_really_exists($tableName) ){ // #6
            if( $this->db->field_exists('levelId', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_levelId', 'levelId');
            }
        }
        if( $this->utils->table_really_exists($tableName) ){ // #7
            if( $this->db->field_exists('registered_by', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_registered_by', 'registered_by');
            }
        }
        // if( $this->utils->table_really_exists($tableName) ){ // #8
        //     if( $this->db->field_exists('totalDepositAmount', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_totalDepositAmount', 'totalDepositAmount');
        //     }
        // }
        /// Ignored, not found any input in using.
        // if( $this->utils->table_really_exists($tableName) ){ // #9
        //     if( $this->db->field_exists('total_total_nofrozen', $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_total_total_nofrozen', 'total_total_nofrozen');
        //     }
        // }
        if( $this->utils->table_really_exists($tableName) ){ // #10
            if( $this->db->field_exists('verified_email', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_verified_email', 'verified_email');
            }
        }
        if( $this->utils->table_really_exists($tableName) ){ // #11
            if( $this->db->field_exists('verified_phone', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_verified_phone', 'verified_phone');
            }
        }

        // player_runtime.lastLoginIp
        $tableName = 'player_runtime';
        $fieldName = 'lastLoginIp';
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }

        /// Ignored, not found any input in using.
        // playeraccount.totalBalanceAmount
        // playertype.typeOfPlayer aka. playeraccount.typeOfPlayer
        // $tableName = 'playeraccount';
        // $fieldName = 'totalBalanceAmount';
        // if( $this->utils->table_really_exists($tableName) ){
        //     if( $this->db->field_exists($fieldName, $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
        //     }
        // }
        // $fieldName = 'typeOfPlayer';
        // if( $this->utils->table_really_exists($tableName) ){
        //     if( $this->db->field_exists($fieldName, $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
        //     }
        // }

        // playerbankdetails.bankAccountNumber
        $tableName = 'playerbankdetails';
        $fieldName = 'bankAccountNumber';
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }

        // playerdetails.birthdate #1
        // playerdetails.city #2
        // playerdetails.firstName #3
        // playerdetails.id_card_number #4
        // playerdetails.lastName #5
        // playerdetails.pix_number #6
        // playerdetails.registrationIP #7
        // playerdetails.registrationWebsite #8
        // playerdetails.residentCountry #9
        $tableName = 'playerdetails';
        $fieldName = 'birthdate'; #1
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'city'; #2
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'firstName'; #3
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'id_card_number'; #4
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'lastName'; #5
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'pix_number'; #6
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'registrationIP'; #7
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'registrationWebsite'; #8
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'residentCountry'; #9
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }

        /// Ignored, not found any input in using.
        // // promorules.promoCode
        // $tableName = 'promorules';
        // $fieldName = 'promoCode';
        // if( $this->utils->table_really_exists($tableName) ){
        //     if( $this->db->field_exists($fieldName, $tableName) ){
        //         $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
        //     }
        // }

        /// used in Responsible_gaming::getData()
        // responsible_gaming.date_from
        // responsible_gaming.date_to
        // responsible_gaming.period_type
        $tableName = 'responsible_gaming';
        $fieldName = 'date_from'; #1
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'date_to'; #2
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
        $fieldName = 'period_type'; #3
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists($fieldName, $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_'. $fieldName, $fieldName);
            }
        }
	}

	public function down() {

	}
}