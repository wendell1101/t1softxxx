<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_yuxing_cq9_game_logs_20211006 extends CI_Migration {

	private $tableName = 'yuxing_cq9_game_logs';

	public function up() {
		if($this->db->table_exists('yuxing_cq9_game_logs')){
			return;
		}

		$fields = array(
			'id' => array(
				'type' => 'INT',
				'null' => false,
				'auto_increment' => TRUE,
			),

            'status' => array(
				'type' => 'VARCHAR',
				'constraint' => '5',
				'null' => true,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => false,
			),
			'channel' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => false,
			),
			'agent' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => false,
			),
			'createtime' => array(
                'type' => 'DATETIME',
				'null' => false,
			),
			'groupfor' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
			'gametype' => array(
                'type' => 'INT',
				'null' => true,
			),
			'roomid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
				'null' => true,
			),
			'tableid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
				'null' => true,
			),
			'roundid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
				'null' => true,
			),
			'betamount' => array(
                'type' => 'double',
				'null' => true,
			),
			'validbetamount' => array(
                'type' => 'double',
				'null' => true,
			),
			'betpoint' => array(
                'type' => 'TEXT',
				'null' => true,
			),
            'odds' => array(
                'type' => 'double',
                'null' => true,
            ),
            'money' => array(
                'type' => 'double',
                'null' => true,
            ),
            'servicemoney' => array(
                'type' => 'double',
                'null' => true,
            ),
            'begintime' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'endtime' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'isbanker' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'gameinfo' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'gameresult' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'jp' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'info1' => array(
                'type' => 'TEXT',
                'null' => true,
            ),

			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'response_result_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
		);


		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->add_key('username');
		$this->dbforge->add_key('gametype');
		$this->dbforge->add_key('createtime');
		$this->dbforge->add_key('begintime');
		$this->dbforge->add_key('endtime');

		$this->dbforge->add_key('external_uniqueid');
		$this->dbforge->create_table($this->tableName);
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
