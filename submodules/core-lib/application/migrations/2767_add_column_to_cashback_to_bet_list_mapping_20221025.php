<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_cashback_to_bet_list_mapping_20221025 extends CI_Migration
{
	private $tableName = 'cashback_to_bet_list_mapping';


    public function up() {
        $this->load->model('player_model');


        $fields = array(
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('player_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);

                $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            }

        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('player_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'player_id');
            }
        }



    }
}