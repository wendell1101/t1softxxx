<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fbsports_seamless_wallet_game_records_20240627 extends CI_Migration {

    private $tableName = 'fbsports_seamless_wallet_game_records';

    public function up() {
        $field = array(
           'external_gameid' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('external_gameid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_external_gameid', 'external_gameid');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('external_gameid', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'external_gameid');
            }
        }
    }
}
