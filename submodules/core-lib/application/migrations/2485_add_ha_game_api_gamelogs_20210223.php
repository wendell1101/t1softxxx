<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_ha_game_api_gamelogs_20210223 extends CI_Migration {

	private $origTableName = 'ha_gaming_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'operator_token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'trans_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'ts' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'date' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'term' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'sign' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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
				'constraint' => '100',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            )
		);

	    if(!$this->db->table_exists($this->origTableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->origTableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->origTableName, 'idx_player_id', 'player_id');
	        $this->player_model->addIndex($this->origTableName, 'idx_term', 'term');
	        $this->player_model->addIndex($this->origTableName, 'idx_type', 'type');
	        $this->player_model->addIndex($this->origTableName, 'idx_ts', 'ts');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_trans_id', 'trans_id');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->origTableName);
	}
}
