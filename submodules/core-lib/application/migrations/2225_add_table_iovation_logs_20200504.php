<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_iovation_logs_20200504 extends CI_Migration {

    private $tableName = 'iovation_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'response_id' => [
                'type' => 'VARCHAR',
                'constraint' => '300'
            ],
            'result' => [
                'type' => 'VARCHAR',
                'constraint' => '30'
            ],
            'stated_ip' => [
                'type' => 'VARCHAR',
                'constraint' => '60'
            ],
            'account_code' => [
                'type' => 'VARCHAR',
                'constraint' => '60'
            ],
            'tracking_number' => [
                'type' => 'INT',
                'null' => true
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'constraint' => '11',
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
            $this->player_model->addIndex($this->tableName,'idx_response_id','response_id');
            $this->player_model->addIndex($this->tableName,'idx_account_code','account_code');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}