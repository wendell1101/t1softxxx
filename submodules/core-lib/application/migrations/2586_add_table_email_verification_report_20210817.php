<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_email_verification_report_20210817 extends CI_Migration {

	private $tableName = 'email_verification_report';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'email_address' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'email_template' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'verification_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => false,
			),
			'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'sending_status' => array(
                'type' => 'INT',
                'default'=>0,
				'null' => false,
			),
			'verify_status' => array(
                'type' => 'INT',
                'default'=>0,
				'null' => false,
            ),
			'job_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
				'null' => true,
            )
		);

        if(!$this->utils->table_really_exists($this->tableName)){

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->tableName,	'idx_player_id' , 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_email_address', 'email_address');
            $this->player_model->addIndex($this->tableName, 'idx_verification_code', 'verification_code');
            $this->player_model->addIndex($this->tableName,	'idx_created_at' , 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_sending_status', 'sending_status');
            $this->player_model->addIndex($this->tableName, 'idx_job_token', 'job_token');
        }
	}

	public function down() {
        	}
}
