<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerdetails_20201226 extends CI_Migration {

    private $tableName = 'playerdetails';

    public function up() {
        $field = array(
            'cpaId' => array(
                'type' => 'json',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('cpaId', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('cpaId', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'cpaId');
        }
    }
}