<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_description_history_20220218 extends CI_Migration {
	private $tableName = 'game_description_history';

    public function up() {
        $userIdField = array(
            'user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true
            ),
        );

        $userIpAddressField = array(
            'user_ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ),
        );


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('user_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $userIdField);
            }
            if(!$this->db->field_exists('user_ip_address', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $userIpAddressField);
            }
            
            $this->load->model('player_model');	 
            if($this->db->field_exists('user_id', $this->tableName)){
                $this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');	  
            }
            if($this->db->field_exists('user_ip_address', $this->tableName)){
                $this->player_model->addIndex($this->tableName, 'idx_user_ip_address', 'user_ip_address');	  
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('user_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'user_id');
            }
            if($this->db->field_exists('user_ip_address', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'user_ip_address');
            }
        }
    }
}
///END OF FILE/////