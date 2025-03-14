<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_nextspin_game_logs_20220528 extends CI_Migration {

	private $tableName = 'nextspin_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => true,
			),
			'ticketId' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'roundId' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'acctId' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'categoryId' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'luckyDrawId' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gameCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'ticketTime' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'betIp' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'betAmount' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
			'winLoss' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
            'jackpotAmount' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
            'jpWin' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
            'balance' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
			'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'result' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'completed' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'sequence' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'channel' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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

		if(!$this->utils->table_really_exists($this->tableName)) 
        {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_acctId', 'acctId');
            $this->player_model->addIndex($this->tableName, 'idx_ticketId', 'ticketId');
            $this->player_model->addIndex($this->tableName, 'idx_ticketTime', 'ticketTime');
            $this->player_model->addIndex($this->tableName, 'idx_gameCode', 'gameCode');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

	public function down() 
    {
		$this->dbforge->drop_table($this->tableName);
	}
}
