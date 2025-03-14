<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_hogaming_seamless_idr1to7_transaction_logs_20200914 extends CI_Migration
{
    // private $tableName = 'hogaming_seamless_idr1_transaction_logs';
    private $table_idr = ['hogaming_seamless_idr1_transaction_logs','hogaming_seamless_idr2_transaction_logs','hogaming_seamless_idr3_transaction_logs','hogaming_seamless_idr4_transaction_logs','hogaming_seamless_idr5_transaction_logs','hogaming_seamless_idr6_transaction_logs','hogaming_seamless_idr7_transaction_logs',];

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
                    'uname' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '70',
                        'null' => true
                    ),
                    'cur' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    ),
                    'amt' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'txnid' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true
                    ),
                    'gametypeid' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '20',
                        'null' => true
                    ),
                    'txnsubtypeid' => array(
                        'type' => 'INT',
                        'constraint' => '10',
                        'null' => true
                    ),
                    'gameid' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true
                    ),
                    'bAmt' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'txn_reverse_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true
                    ),
                    'category' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true
                    ),
                    'operator' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'null' => true
                    ),
                    'provider_id' => array(
                        'type' => 'INT',
                        'constraint' => '10',
                        'null' => true
                    ),
                    'before_balance' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'after_balance' => array(
                        'type' => 'DOUBLE',
                        'null' => true
                    ),
                    'sessionid' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true
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
                    $this->player_model->addIndex($table, 'idx_hgtrans_txnid', 'txnid');
                    $this->player_model->addIndex($table, 'idx_hgtrans_txn_reverse_id', 'txn_reverse_id');
                    $this->player_model->addIndex($table, 'idx_hgtrans_uname', 'uname');
                    $this->player_model->addIndex($table, 'idx_hgtrans_gameid', 'gameid');
                    $this->player_model->addIndex($table, 'idx_hgtrans_category', 'category');
                    $this->player_model->addIndex($table, 'idx_hgtrans_operator', 'operator');
                    $this->player_model->addIndex($table, 'idx_hgtrans_provider_id', 'provider_id');
                    $this->player_model->addUniqueIndex($table, 'idx_hgtrans_external_uniqueid', 'external_uniqueid');
                }
            }
        }
    }

    public function down()
    {
        // $this->dbforge->drop_table($table);
        if(!empty($this->table_idr)){
            foreach ($this->table_idr as $table) {
                if ($this->db->table_exists($table)) {
                    $this->dbforge->drop_table($table);
                }
            }
        }
    }
}
