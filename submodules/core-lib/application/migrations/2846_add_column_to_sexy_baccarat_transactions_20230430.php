<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sexy_baccarat_transactions_20230430 extends CI_Migration {

    private $tableName = 'sexy_baccarat_transactions';

    public function up() {
        
        $field = array(
            'group_transaction_id' => [
            'type' => 'VARCHAR',
            'constraint' => '100',
            'null' => true,
            ]
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('group_transaction_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);

                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_group_transaction_id', 'group_transaction_id');
               
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('group_transaction_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'group_transaction_id');
            }
        }
    }
}