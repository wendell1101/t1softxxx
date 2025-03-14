<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sv388_seamless_wallet_transactions_20230512 extends CI_Migration {

    private $tableName = 'sv388_seamless_wallet_transactions';

    public function up() {
        
        $fields = array(
			'group_transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
			),
			'remote_wallet_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
			),
		);


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('group_transaction_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);

                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_group_transaction_id', 'group_transaction_id');
                $this->player_model->addIndex($this->tableName, 'idx_remote_wallet_uniqueid', 'remote_wallet_uniqueid');
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