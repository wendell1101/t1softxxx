<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_payment_account_related_fields_20231109 extends CI_Migration {

	public function up() {
        $this->load->model('player_model');

        $tableName = 'payment_account';
        if( $this->utils->table_really_exists($tableName) ){
            // - payment_account.payment_type_id
            // - payment_account.status
            // - payment_account.flag
            // - payment_account.external_system_id
            // - payment_account.payment_order
            if( $this->db->field_exists('payment_type_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_payment_type_id', 'payment_type_id');
            }
            if( $this->db->field_exists('status', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_status', 'status');
            }
            if( $this->db->field_exists('flag', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_flag', 'flag');
            }
            if( $this->db->field_exists('external_system_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_external_system_id', 'external_system_id');
            }
            if( $this->db->field_exists('payment_order', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_payment_order', 'payment_order');
            }
        }

        $tableName = 'payment_account_player';
        if( $this->utils->table_really_exists($tableName) ){
            // - payment_account_player.payment_account_id
            // - payment_account_player.player_id
            if( $this->db->field_exists('payment_account_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_payment_account_id', 'payment_account_id');
            }
            if( $this->db->field_exists('player_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_player_id', 'player_id');
            }
        }


        $tableName = 'dispatch_account_level';
        if( $this->utils->table_really_exists($tableName) ){
            // - dispatch_account_level.status
            if( $this->db->field_exists('status', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_status', 'status');
            }
        }

        $tableName = 'dispatch_account_level_payment_account';
        if( $this->utils->table_really_exists($tableName) ){
            // - dispatch_account_level_payment_account.payment_account_id
            // - dispatch_account_level_payment_account.dispatch_account_level_id
            if( $this->db->field_exists('payment_account_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_payment_account_id', 'payment_account_id');
            }
            if( $this->db->field_exists('dispatch_account_level_id', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_dispatch_account_level_id', 'dispatch_account_level_id');
            }
        }

        $tableName = 'responsible_gaming';
        if( $this->utils->table_really_exists($tableName) ){
            // - responsible_gaming.type
            // - responsible_gaming.status
            if( $this->db->field_exists('type', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_type', 'type');
            }
            if( $this->db->field_exists('status', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_status', 'status');
            }
        }

	}

	public function down() {

	}
}