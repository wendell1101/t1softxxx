<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_common_seamless_error_logs_20200427 extends CI_Migration {

    private $tableName = 'common_seamless_error_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '6'
            ],
            'response_result_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'error_date' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_seamlesserrorlog_request_id','request_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}