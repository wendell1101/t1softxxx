<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_t1lottery_transactions_20210721 extends CI_Migration {

    private $tableName ='t1lottery_transactions';

    public function up() {
        $field = array(
            'game_code' => array(
                'type' => 'varchar',
                'constraint' => 20,
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_code', $this->tableName)){  
                $this->dbforge->add_column($this->tableName, $field);
                # Add Index
	            $this->load->model('player_model');	 
                $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');	        
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_code', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_code');
            }
        }
    }
}