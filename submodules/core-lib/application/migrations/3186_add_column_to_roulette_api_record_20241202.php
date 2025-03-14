<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_roulette_api_record_20241202 extends CI_Migration {

    private $tableName = 'roulette_api_record';

    public function up() {

        $fields = array(
            'additional_id' => array(
                "type" => "INT",
                "null" => true,
                "unsigned" => true,
            ),
        );

        $fields2 = array(
            'withdraw_condition_id' => array(
                "type" => "INT",
                "null" => true,
                "unsigned" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('additional_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName,'idx_additional_id','additional_id');
        }

        if(!$this->db->field_exists('withdraw_condition_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields2);
            $this->player_model->addIndex($this->tableName,'idx_withdraw_condition_id','withdraw_condition_id');
        }
    }

    public function down() {
        if($this->db->field_exists('additional_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'additional_id');
        }

        if($this->db->field_exists('withdraw_condition_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'withdraw_condition_id');
        }
    }
}