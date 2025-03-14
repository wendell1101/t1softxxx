<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_cms_navigation_game_platform_20200903 extends CI_Migration {

    private $tableName = 'cms_navigation_game_platform';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'game_platform_lang' => array(
                'type' => 'VARCHAR',
                'constraint' => '2000',
                'null' => false,
            ),
            'navigation_setting_id' => array(
                'type' => 'BIGINT',
                'null' => false,
            ),
            'game_platform_id' => array(
                'type' => 'BIGINT',
                'null' => false,
            ),
            'order' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => '1',
                'null' => false,
            ),
            'icon' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
