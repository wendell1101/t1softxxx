<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_pos_bet_extra_info_20230418 extends CI_Migration {
    private $tableName = 'pos_bet_extra_info';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ],
            'bet_number' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ],
            'player_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'phone_number' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'id_number' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_name', 'player_name');
            $this->player_model->addIndex($this->tableName, 'idx_phone_number', 'phone_number');
            $this->player_model->addIndex($this->tableName, 'idx_id_number', 'id_number');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_bet_number', 'bet_number');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}