<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_transfer_request_external_info_20200419 extends CI_Migration {

    private $tableName = 'transfer_request_external_info';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'transfer_request_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'secure_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'response_db' => array( // resp_xxxx.yyyyy
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'external_transaction_id_from_game' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'external_trans_id_from_gamegatewayapi' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        );
        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);

            $this->dbforge->create_table($this->tableName);
            # add index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_external_trans_id_from_gamegatewayapi","external_trans_id_from_gamegatewayapi");
            $this->player_model->addIndex($this->tableName,"idx_external_transaction_id_from_game","external_transaction_id_from_game");
            $this->player_model->addIndex($this->tableName,"idx_request_id","request_id");
            $this->player_model->addIndex($this->tableName,"idx_transfer_request_id","transfer_request_id");
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}