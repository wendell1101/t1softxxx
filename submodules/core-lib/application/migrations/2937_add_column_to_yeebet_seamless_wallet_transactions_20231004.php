<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_yeebet_seamless_wallet_transactions_20231004 extends CI_Migration {

    private $tableName = 'yeebet_seamless_wallet_transactions';

    public function up() {
        $column1 = array(
            'request_body' => array(
                'type' => 'text',
                'null' => true,
            ),
        );

        $column2 = array(
            'external_unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
        );


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('request_body', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column1);
            }

            if($this->db->field_exists('external_unique_id', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('request_body', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'request_body');
            }
        }
    }
}