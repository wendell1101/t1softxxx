<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sbobet_seamless_game_transactions_20230523 extends CI_Migration
{
	private $tableName = 'sbobet_seamless_game_transactions';

    public function up() {

        $field = array(
            'sbe_round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ),
            'sbe_external_game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('sbe_round_id', $this->tableName) && !$this->db->field_exists('sbe_external_game_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);

                $this->load->model('player_model'); # Any model class will do
                $this->player_model->addIndex($this->tableName, 'idx_sbe_round_id', 'sbe_round_id');
                $this->player_model->addIndex($this->tableName, 'idx_sbe_external_game_id', 'sbe_external_game_id');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('sbe_round_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'sbe_round_id');
            }
            if($this->db->field_exists('sbe_external_game_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'sbe_external_game_id');
            }
        }
    }
}