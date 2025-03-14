<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_aggregated_table_report_hour_20201201 extends CI_Migration {

    private $tableName = 'aggregated_table_report_hour';    

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'BIGINT',
                'null' => true,                
            ],
            'agent_id' => [
                'type' => 'BIGINT',
                'null' => true,                
            ],
            'betting_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'real_betting_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'win_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'loss_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'total_logs' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'game_type_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'game_description_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'date_hour' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => false,
			),
            'date' => [
                'type' => 'DATE',
                'null' => false
            ],     
            'hour' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ),       
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'currency_key' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => false
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        $this->load->model('player_model');

        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index            
            $this->player_model->addIndex($this->tableName,'idx_aggregatedtablereporthourly_datewithin','date');
            $this->player_model->addIndex($this->tableName,'idx_aggregatedtablereporthourly_agentid','agent_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_aggregatedtablereporthourly_uniqueid', 'uniqueid');
        }
    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }        
    }
}