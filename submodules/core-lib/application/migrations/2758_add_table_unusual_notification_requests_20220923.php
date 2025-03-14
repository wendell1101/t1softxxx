<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_unusual_notification_requests_20220923 extends CI_Migration {

	private $tableName = 'unusual_notification_requests';

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'status_code' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'status_type' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'data_transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 40,
            ),
            'data_payer_bank' => array(
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true
            ),
            'data_payer_account' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true
            ),
            'data_payee_bank' => array(
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true
            ),
            'data_payee_account' => array(
                "type" => "BIGINT",
                "null" => false,
            ),
            'data_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_data_transaction_id', 'data_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
