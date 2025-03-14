<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_sso_platform_20241101 extends CI_Migration {
    private $tableName = 'sso_platform';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'auto_increment' => TRUE,
            ),
            'platformName' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'status' => array(
                'type' => 'INT',
                'default' => 1,
            ),
            'settings' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'updateBy' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
