<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_wm_casino_transactions_20230718 extends CI_Migration {
    private $tableName = 'wm_casino_transactions';

    public function up() {
        
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'user' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'cmd' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'money' => array(
                'type' => 'double',
                'null' => true,
            ),
            'request_date' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),

            'dealid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'gtype' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'betdetail' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'gameno' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'category' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            'game_platform_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ),

            'bet_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ),

            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            
            'wallet_adjustment_mode' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),

            'payout' => array(
                'type' => 'double',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'BIGINT',
                'null' => false,
            ),
            'trans_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'before_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'null' => true,
                'default' => 0,
            ),    
            'game_logs_status' => array(
                'type' => 'INT',
                'constraint' => '11',
                'default' => 0,
            ),                 
            'raw_data' => array(
                'type' => 'JSON',
                'null' => true
            ),
            'bet_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'result_amount' => array(
                'type' => 'double',
                'null' => true,
            ),

            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'elapsed_time' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->CI->dbforge->add_field($fields);
            $this->CI->dbforge->add_key('id', TRUE);
            $this->CI->dbforge->create_table($this->tableName);
            # Add Index
            $this->CI->load->model('player_model');
            $this->CI->player_model->addIndex($this->tableName, 'idx_dealid', 'dealid');
            $this->CI->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->CI->player_model->addIndex($this->tableName, 'idx_gtype', 'gtype');
            $this->CI->player_model->addIndex($this->tableName, 'idx_code', 'code');
            $this->CI->player_model->addIndex($this->tableName, 'idx_cmd', 'cmd');
            $this->CI->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');
            $this->CI->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->CI->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->CI->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->CI->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->CI->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}