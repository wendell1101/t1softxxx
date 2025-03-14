<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_get_response_contacts_20230103 extends CI_Migration
{
	private $tableName = 'get_response_contacts';

    public function up() {
        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false
            ],            
            'player_username' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false
            ],                
            'contact_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],       
            'player_token' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],       
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],              
            'confirm_email_status' => [
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 0,
            ],
            'date_registered' => [
				'type' => 'DATETIME',
				'null' => true,
			],
            'date_last_login' => [
				'type' => 'DATETIME',
				'null' => true,
			],
            'date_last_deposit' => [
				'type' => 'DATETIME',
				'null' => true,
			],
            'date_first_deposit' => [
				'type' => 'DATETIME',
				'null' => true,
			],
            'deposit_count' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'wallet_balance' => [
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ],
            'game_data' => [
                'type' => 'JSON',
                'null' => true
            ],            
            'additional_data' => [
                'type' => 'JSON',
                'null' => true
            ],
            'balance_limit' => [
                'type' => 'JSON',
                'null' => true
            ],    
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],            
            'last_activity_time' => [
				'type' => 'DATETIME',
				'null' => true,
			],


            
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        );       

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_contact_id', 'contact_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_username', 'player_username');
            $this->player_model->addIndex($this->tableName, 'idx_email', 'email');
            $this->player_model->addIndex($this->tableName, 'idx_date_registered', 'date_registered');
            $this->player_model->addIndex($this->tableName, 'idx_date_last_login', 'date_last_login');
            $this->player_model->addIndex($this->tableName, 'idx_date_last_deposit', 'date_last_deposit');
            $this->player_model->addIndex($this->tableName, 'idx_date_first_deposit', 'date_first_deposit');
 
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}