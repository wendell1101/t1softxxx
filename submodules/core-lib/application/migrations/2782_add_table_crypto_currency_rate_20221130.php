<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_crypto_currency_rate_20221130 extends CI_Migration {

    private $tableName = 'crypto_currency_rate';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'api_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'crypto_currency' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ),
            'rate' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            "transaction" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            ),
            'request_time' => array(
                'type' => 'DATETIME',
                'null' => true
            )
        );
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}