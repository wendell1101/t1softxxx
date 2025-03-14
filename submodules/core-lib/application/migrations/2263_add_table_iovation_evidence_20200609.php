<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_iovation_evidence_20200609 extends CI_Migration {

    private $tableName = 'iovation_evidence';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],            
            'evidence_type' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ],
            'comment' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'applied_to' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'account_code' => [
                'type' => 'VARCHAR',
                'constraint' => '60'
            ],
            'player_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'null' => true                
            ], 
            'evidence_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'response' => [
                'type' => 'TEXT',
                'null' => true,
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'status' => array(
                "type" => "TINYINT",
                "null" => true		
            ),
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
            $this->player_model->addIndex($this->tableName,'idx_account_code','account_code');
            $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            $this->player_model->addIndex($this->tableName,'idx_evidence_id','evidence_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}