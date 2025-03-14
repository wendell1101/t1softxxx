<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jili_seamless_wallet_transactions_20240605 extends CI_Migration {
    private $tableNames = [
        'jili_seamless_wallet_transactions',
        'jili_seamless_wallet_transactions_202405',
        'jili_seamless_wallet_transactions_202406'
    ];

    public function up() {

        $field1 = array(
            'remote_wallet_status' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );
        $field2 = array(
            'raw_data' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );
        $field3 = array(
            'is_failed' => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ),
        );

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                
                if($this->utils->table_really_exists($tableName)){
                    if(!$this->db->field_exists('remote_wallet_status', $tableName) ){
                        $this->dbforge->add_column($tableName, $field1);
                        $this->player_model->addIndex($tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
                    }
                }
                if($this->utils->table_really_exists($tableName)){
                    if(!$this->db->field_exists('raw_data', $tableName) ){
                        $this->dbforge->add_column($tableName, $field2);
                    }
                }
                if($this->utils->table_really_exists($tableName)){
                    if(!$this->db->field_exists('is_failed', $tableName) ){
                        $this->dbforge->add_column($tableName, $field3);
                        $this->player_model->addIndex($tableName, 'idx_is_failed', 'is_failed');
                    }
                }

                if($this->db->field_exists('trans_type', $tableName)){
                    $this->dbforge->modify_column($tableName, [
                        'trans_type' => [
                            'type' => 'VARCHAR',
                            'constraint' => '100',
                            'null' => true,
                        ],
                    ]);
                }
                if($this->db->field_exists('user_id', $tableName)){
                    $this->dbforge->modify_column($tableName, [
                        'user_id' => [
                            'type' => 'VARCHAR',
                            'constraint' => '100',
                            'null' => true,
                        ],
                    ]);
                }
            }
        }
    }

    public function down() {
    }
}