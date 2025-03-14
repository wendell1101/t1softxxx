<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_cashback_to_bet_list_mapping_20220314 extends CI_Migration {

	private $tableName = 'cashback_to_bet_list_mapping';

	public function up() {
		$fields=array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'cashback_table' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'cashback_id' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ),
            'bet_source_table' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'bet_source_id' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex('cashback_to_bet_list_mapping','idx_cashback_table' , 'cashback_table');
            $this->player_model->addIndex('cashback_to_bet_list_mapping','idx_cashback_id' , 'cashback_id');
            $this->player_model->addIndex('cashback_to_bet_list_mapping','idx_bet_source_table' , 'bet_source_table');
            $this->player_model->addIndex('cashback_to_bet_list_mapping','idx_bet_source_id' , 'bet_source_id');
        }
	}

	public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
