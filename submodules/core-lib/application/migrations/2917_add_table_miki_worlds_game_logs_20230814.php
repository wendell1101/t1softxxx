<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_miki_worlds_game_logs_20230814 extends CI_Migration {

	private $tableName = 'miki_worlds_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'ref_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'player_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'channel_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'round_no' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'game_type' => array(
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ), 
            'bet_item' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'bet_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'valid_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'winning_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'payout_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'payout_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'source_result' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'result' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'transaction_status' => array(
                'type' => 'INT',
                'constraint' => '15',
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
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_ref_id', 'ref_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_name', 'player_name');
            $this->player_model->addIndex($this->tableName, 'idx_game_type', 'game_type');
            $this->player_model->addIndex($this->tableName, 'idx_round_no', 'round_no');
            $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_status', 'transaction_status');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
