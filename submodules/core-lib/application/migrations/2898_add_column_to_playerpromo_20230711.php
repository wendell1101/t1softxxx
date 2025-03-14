<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerpromo_20230711 extends CI_Migration {
    private $tableName = 'playerpromo';

    public function up() {
        $fields = [
            'referralId' => [
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('referralId', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_referralId', 'referralId');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('referralId', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'referralId');
            }
        }
    }
}
