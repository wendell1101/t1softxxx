<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_level_adjustment_history_20201125 extends CI_Migration {

    private $tableName = 'player_level_adjustment_history';

    public function up() {
        $fields=array(
            'id' => array(
                'type' => 'BIGINT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'is_met_offset_rules_info' => array(
                'type' => 'json',
                'null' => true,
            ),
            'previous_vipsettingcashbackrule' => array(
                'type' => 'json',
                'null' => true,
            ),
            'previous_vipupgradesetting' => array(
                'type' => 'json',
                'null' => true,
            ),
            'current_vipsettingcashbackrule' => array(
                'type' => 'json',
                'null' => true,
            ),
            'current_vipupgradesetting' => array(
                'type' => 'json',
                'null' => true,
            ),
            'initial_amount' => array(
                'type' => 'json',
                'null' => true,
            ),
            'formula' => array(
                "type" => "json",
                'null' => true
            ),
            'result_formula' => array(
                "type" => "json",
                'null' => true
            ),
            'is_condition_met' => array(
                'type' => 'INT',
                'null' => false,
            ),
            // request_time
            // request_grade
            // request_type
            'request_grade' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'request_time' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'request_type' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_player_id' , 'player_id');
            $this->player_model->addIndex($this->tableName,'idx_is_condition_met' , 'is_condition_met');
            $this->player_model->addIndex($this->tableName,'idx_updated_at' , 'updated_at');
            $this->player_model->addIndex($this->tableName,'idx_created_at' , 'created_at');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}