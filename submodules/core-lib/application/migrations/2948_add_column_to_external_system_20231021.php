<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_external_system_20231021 extends CI_Migration {

    private $tableName = 'external_system';

    public function up() {
        $attributes_field = [
            'attributes' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ];

        $flag_show_in_site_field = [
            'flag_show_in_site' => [
                'type' => 'INT',
                'null' => false,
                'default' => 1,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('attributes', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $attributes_field);
                $this->load->model('player_model');
            }

            if (!$this->db->field_exists('flag_show_in_site', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $flag_show_in_site_field);
                $this->load->model('player_model');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('attributes', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'attributes');
            }

            if ($this->db->field_exists('flag_show_in_site', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'flag_show_in_site');
            }
        }
	}
}