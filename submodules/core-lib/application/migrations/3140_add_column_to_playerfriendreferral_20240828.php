<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerfriendreferral_20240828 extends CI_Migration {

    private $tableName = 'playerfriendreferral';

    public function up() {
        $column = [
            'same_ip_with_referrer' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ],
        ];

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('same_ip_with_referrer', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('same_ip_with_referrer', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'same_ip_with_referrer');
            }
        }
    }
}