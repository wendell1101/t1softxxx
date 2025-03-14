<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_tracking_platform_20240429 extends CI_Migration {

    private $tableName ='tracking_platform';

    public function up() {
        $fields = array(
            'domain' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('domain', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }
    public function down() {
    }
}
