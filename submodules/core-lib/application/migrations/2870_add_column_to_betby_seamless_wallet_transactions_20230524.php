<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_betby_seamless_wallet_transactions_20230524 extends CI_Migration
{
	private $tableName = 'betby_seamless_wallet_transactions';

    public function up() {

        $field = array(
            'sbe_player_id' => array(
                'type' => 'INT',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('sbe_player_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);

                $this->load->model('player_model'); # Any model class will do
                $this->player_model->addIndex($this->tableName, 'idx_sbe_player_id', 'sbe_player_id');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('sbe_player_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'sbe_player_id');
            }
        }
    }
}