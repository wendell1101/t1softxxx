<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_biggaming_game_logs_20201228 extends CI_Migration {

	private $tableNames = ['biggaming_game_logs','biggaming_thb1_game_logs'];

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'tran_id' => array(
				'type' => 'BIGINT',
				'null' => true,
            ),
            'a_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'login_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'order_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'module_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ),
            'order_status' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'play_id' => array(
				'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'uid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
			'order_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
            'game_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ),
            'payment' => array(
                'type' => 'double',
                'null' => true,
            ),
            'sn' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'b_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'module_id' => array(
				'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'no_comm' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'play_name_en' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'issue_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'play_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'user_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'valid_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'game_name_en' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'from_ip' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'table_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'order_from' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'bet_content' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'valid_bet' => array(
                'type' => 'double',
                'null' => true,
            ),
            'last_update_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
		

			# SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );
        foreach($this->tableNames as $tableName){

            if(!$this->db->table_exists($tableName)){
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table($tableName);
                
                # Add Index
                $this->load->model('player_model');
                $this->player_model->addIndex($tableName, 'idx_login_id', 'login_id');
                $this->player_model->addIndex($tableName, 'idx_order_id', 'order_id');
                $this->player_model->addIndex($tableName, 'idx_game_id', 'game_id');
                $this->player_model->addIndex($tableName, 'idx_order_time', 'order_time');            
                $this->player_model->addIndex($tableName, 'idx_updated_at', 'updated_at');            
                $this->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');
            }
        }
        
	}

	public function down() {
        foreach($this->tableNames as $tableName){
            if($this->db->table_exists($tableName)){
                $this->dbforge->drop_table($tableName);
            }
        }
	}
}
