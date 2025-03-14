<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_table_to_hogaming_seamless_idr1to7_game_logs_20200915 extends CI_Migration
{
    private $table_idr = ['hogaming_seamless_idr1_game_logs','hogaming_seamless_idr2_game_logs','hogaming_seamless_idr3_game_logs','hogaming_seamless_idr4_game_logs','hogaming_seamless_idr5_game_logs','hogaming_seamless_idr6_game_logs','hogaming_seamless_idr7_game_logs'];

    public function up()
    {
        if(!empty($this->table_idr)){
            foreach ($this->table_idr as $table) {
                $fields = array(
                    'id' => array(
                        'type' => 'BIGINT',
                        'null' => false,
                        'auto_increment' => true,
                    ),
                    'bet_start_date' => array(
                        'type' => 'DATETIME',
                        'null' => true,
                    ),
                    'bet_end_date' => array(
                        'type' => 'DATETIME',
                        'null' => true,
                    ),
                    'account_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '70',
                        'null' => true,
                    ),
                    'table_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'table_name' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'game_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'game_type' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'bet_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'bet_amount' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'payout' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'currency' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'null' => true,
                    ),
                    'bet_spot' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ),
                    'bet_no' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'null' => true,
                    ),
                    'bet_mode' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '20',
                        'null' => true,
                    ),
                    'status' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '20',
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
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                    'md5_sum' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '32',
                        'null' => true,
                    )
                );

                if (!$this->db->table_exists($table)) {
                    $this->dbforge->add_field($fields);
                    $this->dbforge->add_key('id', true);
                    $this->dbforge->create_table($table);
                    # Add Index
                    $this->load->model('player_model');
                    $this->player_model->addIndex($table, 'idx_hg_account_id', 'account_id');
                    $this->player_model->addIndex($table, 'idx_hg_table_id', 'table_id');
                    $this->player_model->addIndex($table, 'idx_hg_table_name', 'table_name');
                    $this->player_model->addIndex($table, 'idx_hg_game_id', 'game_id');
                    $this->player_model->addIndex($table, 'idx_hg_game_type', 'game_type');
                    $this->player_model->addUniqueIndex($table, 'idx_hg_external_uniqueid', 'external_uniqueid');
                } 
            }
        }
    }

    public function down()
    {
        if(!empty($this->table_idr)){
            foreach ($this->table_idr as $table) {
                if ($this->db->table_exists($table)) {
                    $this->dbforge->drop_table($table);
                }
            }
        }
    }
}
