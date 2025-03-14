<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_wcc_game_logs_20240214 extends CI_Migration {

	private $tableName = 'wcc_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
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
            'user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'fight_no' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'bet_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'bet_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'winner' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'payout' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'odds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'refund' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'wallets' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'winner_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'remark' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'bet_result' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
            'raw_data' => array(
				'type' => 'JSON',
				'null' => true,
			),
            'bet_time' => array(
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
            $this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_bet_code', 'bet_code');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
