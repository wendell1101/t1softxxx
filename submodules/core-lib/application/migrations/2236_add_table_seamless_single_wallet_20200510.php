<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_seamless_single_wallet_20200510 extends CI_Migration {

    private $tableName = 'seamless_single_wallet';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            //game platform id+game account
            'internal_unique_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'external_unique_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'balance' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0,
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

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_internal_unique_key', 'internal_unique_key');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_key', 'external_unique_key');
        }
    }

    public function down() {
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}