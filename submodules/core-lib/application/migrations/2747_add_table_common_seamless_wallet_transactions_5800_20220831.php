<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_common_seamless_wallet_transactions_5800_20220831 extends CI_Migration {

    private $tableName = 'seamless_wallet_transactions_5800';

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
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'before_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'after_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'game_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
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
            ],


            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'null' => true,
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'flag_of_updated_result' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_seamlesstransaction_player_id','player_id');
            $this->player_model->addIndex($this->tableName,"idx_transaction_id","transaction_id");
            $this->player_model->addIndex($this->tableName,'idx_round_id','round_id');            
            $this->player_model->addIndex($this->tableName,'idx_game_id','game_id');
            $this->player_model->addIndex($this->tableName,'idx_end_at','end_at');
            $this->player_model->addIndex($this->tableName,'idx_game_platform_id','game_platform_id');
            $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
            $this->player_model->addIndex($this->tableName,'idx_start_at','start_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_seamlesstransaction_external_unique_id', 'external_unique_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}