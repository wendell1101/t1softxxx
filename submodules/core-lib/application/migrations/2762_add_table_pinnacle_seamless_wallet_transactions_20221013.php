<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pinnacle_seamless_wallet_transactions_20221013 extends CI_Migration {

    private $tableName = 'pinnacle_seamless_wallet_transactions';

    public function up()
    {
        $fields = array(
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
                'null' => true
            ],                 
            'timestamp' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'transaction_date' => [
                'type' => 'DATETIME',
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
            'settled_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'bet_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'wager_id' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'wager_master_id' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'wallet_adjustment_mode' => [
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
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'external_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'cost' => [
                'type' => 'INT',
                'constraint' => '5',
                'null' => true
            ]
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->CI->dbforge->add_field($fields);
            $this->CI->dbforge->add_key('id', TRUE);
            $this->CI->dbforge->create_table($this->tableName);
            # Add Index
            $this->CI->load->model('player_model');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_player_id','player_id');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_settled_at','settled_at');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_start_at','start_at');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_end_at','end_at');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_updated_at','updated_at');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_transaction_type','transaction_type');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_round_id','round_id');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_wager_id','wager_id');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_game_id','game_id');
            $this->CI->player_model->addIndex($this->tableName,'idx_seamlesstransaction_status','status');
            $this->CI->player_model->addUniqueIndex($this->tableName, 'idx_seamlesstransaction_external_uniqueid', 'external_uniqueid');

        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}