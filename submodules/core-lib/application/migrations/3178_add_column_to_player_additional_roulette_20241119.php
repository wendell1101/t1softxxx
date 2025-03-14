<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_additional_roulette_20241119 extends CI_Migration {

    private $tableName = 'player_additional_roulette';

    public function up() {
        $fields = array(
            'generate_type' => array(
                "type" => "TINYINT",
                "null" => true,
                "unsigned" => true,
            ),
        );

        $fields2 = array(
            'player_quest_id' => array(
                "type" => "INT",
                "null" => true,
                "unsigned" => true,
            ),
        );

        $fields3 = array(
            'expiration_start_time' => array(
                "type" => "DATETIME",
                "null" => true,
            ),
        );

        $fields4 = array(
            'expiration_end_time' => array(
                "type" => "DATETIME",
                "null" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('generate_type', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }

        if(!$this->db->field_exists('player_quest_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields2);
            $this->player_model->addIndex($this->tableName,'idx_player_quest_id','player_quest_id');
        }

        if(!$this->db->field_exists('expiration_start_time', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields3);
        }

        if(!$this->db->field_exists('expiration_end_time', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields4);
        }
    }

    public function down() {
        if($this->db->field_exists('generate_type', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'generate_type');
        }

        if($this->db->field_exists('player_quest_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'player_quest_id');
        }

        if($this->db->field_exists('expiration_start_time', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'expiration_start_time');
        }

        if($this->db->field_exists('expiration_end_time', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'expiration_end_time');
        }
    }
}