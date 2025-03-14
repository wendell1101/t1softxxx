<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playernotes_20201228 extends CI_Migration {

    private $tableName = 'playernotes';

    public function up() {
        $fields = array(
            'component' => array(
                'type' => 'TINYINT',
                'constraint' => '1',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('component', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('component', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'component');
        }
    }
}