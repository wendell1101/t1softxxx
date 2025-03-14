<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_playstar_seamless_wallet_transactions_20250214 extends CI_Migration {
    private $tableName = 'playstar_seamless_wallet_transactions';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'member_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'member_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',  
                'null' => TRUE
            ),
            'subgame_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'bonus_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'bonus_reward' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'win_amount' => array(
				'type' => 'double',
                'null' => true,
			),
            'jp_contrib' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '3',   
                'null' => TRUE
            ),
            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'game_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'bonus_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '12',  
                'null' => TRUE
            ),
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'timestamp' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'bet_details' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'trans_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => TRUE
            ),
            'balance_adjustment_method' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',  
                'null' => TRUE
            ),
            'transaction_date' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'extra_info' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'balance_adjustment_amount' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'before_balance' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => TRUE
            ),
            'elapsed_time' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE
            ),

            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),   
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            )
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            // Add Indexes
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_timestamp', 'timestamp');
            $this->player_model->addIndex($this->tableName, 'idx_member_id', 'member_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_subgame_id', 'subgame_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');
            $this->player_model->addUniqueIndex($this->tableName, 'unique_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
