<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sexy_baccarat_game_logs_table_20220512 extends CI_Migration {
    private $tableName = 'sexy_baccarat_game_logs';

    public function up() {
        

        $fields = array(
            'bet_status' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bet_status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);                
            }

             # Add Index
             $this->load->model('player_model');
             $this->player_model->addIndex($this->tableName, 'idx_bet_status', 'bet_status');
            
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bet_status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bet_status');
            }
        }
    }
}
