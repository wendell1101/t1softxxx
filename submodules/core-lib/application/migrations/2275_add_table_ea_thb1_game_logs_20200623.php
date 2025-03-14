<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_ea_thb1_game_logs_20200623 extends CI_Migration {

    private $tableName = 'ea_thb1_game_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_code' => [
                'type' => 'INT',
                'constraint' => '7',
                'null' => true
            ],
            'provider_game_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'deal_id' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'status' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 1
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'handle' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'hold' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'payout_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'withold_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'valid_turnover' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'login_name' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'bet_result' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'bet_details' => [
                'type' => 'JSON',
                'null' => true
            ],
            'deal_details' => [
                'type' => 'JSON',
                'null' => true
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
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
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_eathb1_deal_id','deal_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_eathb1_external_unique_id', 'external_unique_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}