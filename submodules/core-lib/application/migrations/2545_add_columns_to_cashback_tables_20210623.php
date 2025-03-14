<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_cashback_tables_20210623 extends CI_Migration {

    private $tableName = 'common_cashback_multiple_range_settings';
    private $tableName_total_cashback_player_game_daily = 'total_cashback_player_game_daily';

    public function up() {
        $this->load->model('player_model');
        if($this->utils->table_really_exists($this->tableName)){

            if( ! $this->db->field_exists('enabled_tier_calc_cashback', $this->tableName)){
                $fields = [
                    'enabled_tier_calc_cashback' => ['type' => 'TINYINT', 'constraint' => '4', 'null' => TRUE, 'default' => 0]
                ];
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }

        /// for total_cashback_player_game_daily
        if($this->utils->table_really_exists($this->tableName_total_cashback_player_game_daily)){
            $fields = array(
                'applied_info' => array(
                    'type' => 'JSON',
                    'null' => true
                ),
                'appoint_id' => array(
                    'type' => 'INT',
                    'null' => false,
                ),
            );

            if(!$this->db->field_exists('applied_info', $this->tableName_total_cashback_player_game_daily)){
                $this->dbforge->add_column($this->tableName_total_cashback_player_game_daily, $fields);

                $this->player_model->addIndex($this->tableName_total_cashback_player_game_daily, 'idx_appoint_id', 'appoint_id');
            }
        }


    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if( $this->db->field_exists('enabled_tier_calc_cashback', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'enabled_tier_calc_cashback');
            }
        }


        /// for total_cashback_player_game_daily
        if($this->utils->table_really_exists($this->tableName_total_cashback_player_game_daily)){
            if($this->db->field_exists('applied_info', $this->tableName_total_cashback_player_game_daily)){
                $this->dbforge->drop_column($this->tableName_total_cashback_player_game_daily, 'applied_info');
            }
            if($this->db->field_exists('appoint_id', $this->tableName_total_cashback_player_game_daily)){
                $this->dbforge->drop_column($this->tableName_total_cashback_player_game_daily, 'appoint_id');
            }
        }
    }
}
