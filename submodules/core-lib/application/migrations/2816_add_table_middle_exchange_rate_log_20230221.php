<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_middle_exchange_rate_log_20230221 extends CI_Migration
{
	private $tableName = 'middle_exchange_rate_log';

    public function up() {
        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'rate' => [
                'type' => 'double',
                'null' => false
            ],
            'status' => [
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ],
            'updated_by' => [
				'type' => 'INT',
				'null' => true,
			],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ]
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_updated_by', 'updated_by');

            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}