<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_seamless_free_bet_record_20221019 extends CI_Migration {

    private $tableName = 'seamless_free_bet_record';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'game_platform_id' => array(
                'type' => 'SMALLINT',
                'null' => false,
            ),
            'unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0,
            ),
            'turnover' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0,
            ),
            'request' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'null' => false,
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
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_unique_id', 'unique_id');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}