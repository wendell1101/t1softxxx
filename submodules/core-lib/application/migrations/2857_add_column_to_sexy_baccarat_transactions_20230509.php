<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sexy_baccarat_transactions_20230509 extends CI_Migration {

    private $tableName = 'sexy_baccarat_transactions';

    public function up() {
        
        $field = array(
            'remote_wallet_uniqueid' => [
            'type' => 'VARCHAR',
            'constraint' => '100',
            'null' => true,
            ]
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('remote_wallet_uniqueid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);

                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_remote_wallet_uniqueid', 'remote_wallet_uniqueid');
               
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_uniqueid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_uniqueid');
            }
        }
    }
}