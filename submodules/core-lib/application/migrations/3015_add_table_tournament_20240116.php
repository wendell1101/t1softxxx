<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_20240116 extends CI_Migration {
    private $tableName = 'tournament';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
            ],
            'tournamentName' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'status' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'order' => [
                'type' => 'INT',
                'null' => true,
            ],
            'tournamentTemplate' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'tournamentType' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'createdBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updateBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_tournamentName', 'tournamentName');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_updatedAt', 'updatedAt');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}