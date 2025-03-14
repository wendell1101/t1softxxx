<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_bgsoft_game_logs_20220812 extends CI_Migration {

	private $tableName = 'bgsoft_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
			),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
			),
            'game_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
			),
            'bet_time' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'payout_time' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'game_finish_time' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'bet_amount' => array(
                'type' => 'DOUBLE',
				'null' => true,
			),
            'payout_amount' => array(
                'type' => 'DOUBLE',
				'null' => true,
			),
            'period' => array(
                'type' => 'INT',
				'constraint' => '15',
				'null' => true,
			),
            'bet_status' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'bet_details' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'result_details' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            

            # SBE additional info
           'response_result_id' => array(  //record response_results.id
                'type' => 'INT',
                'null' => true,
            ),
            'external_uniqueid' => array(   //unique id of each row from api
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at' => array(  // first create date time
                'type' => 'DATETIME',
                'null' => false,
            ),
            'updated_at' => array(  // last update time
                'type' => 'DATETIME',
                'null' => false,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
		);

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_uniqueid', 'uniqueid');
            $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');
            $this->player_model->addIndex($this->tableName, 'idx_payout_time', 'payout_time');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
