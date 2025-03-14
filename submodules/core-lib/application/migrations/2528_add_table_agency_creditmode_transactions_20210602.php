<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_agency_creditmode_transactions_20210602 extends CI_Migration {

    private $tableName = 'agency_creditmode_transactions';

    public function up() {
        $fields=array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => false,
            ),
            'transaction_type' => array(
                'type' => 'INT',
                'null' => false,
                'constraint' => '1',
            ),
            'agent_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'null' => false,
                'unsigned' => TRUE,
            ),
            'player_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'agent_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'sub_wallet_id' => array(
                'type' => 'INT',
                'constraint' => '10',
                'unsigned' => TRUE,
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'content' => array(
                'type' => 'TEXT',
                'null' => true,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex('agency_creditmode_transactions','idx_player_id' , 'player_id');
            $this->player_model->addIndex('agency_creditmode_transactions','idx_sub_wallet_id' , 'sub_wallet_id');
            $this->player_model->addIndex('agency_creditmode_transactions','idx_created_at' , 'created_at');
            $this->player_model->addIndex('agency_creditmode_transactions','idx_transaction_type' , 'transaction_type');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
