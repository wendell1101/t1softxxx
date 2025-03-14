<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_activity_stream_20250123 extends CI_Migration {
    private $tableName = 'player_activity_stream';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'unique' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'http_status_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'client_ip' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'device_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'player_activity_action_type' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],

            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
           
            'date_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
       
            'cost_ms' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],

            'request_params' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'response_params' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'raw_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'response_result_id' => [
                'type' => 'INT',
                'null' => true,
            ],
         
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');

            $this->player_model->addIndex($this->tableName, 'idx_status','status');
            $this->player_model->addIndex($this->tableName, 'idx_http_status_code','http_status_code');
            $this->player_model->addIndex($this->tableName, 'idx_domain','domain');
            $this->player_model->addIndex($this->tableName, 'idx_client_ip','client_ip');
            $this->player_model->addIndex($this->tableName, 'idx_device_type','device_type');
            $this->player_model->addIndex($this->tableName, 'idx_player_activity_action_type','player_activity_action_type');
            $this->player_model->addIndex($this->tableName, 'idx_player_id','player_id');
            $this->player_model->addIndex($this->tableName, 'idx_date_time','date_time');
            $this->player_model->addIndex($this->tableName, 'idx_response_result_id','response_result_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_request_id', 'request_id');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}