<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_common_cashback_rules_20210608 extends CI_Migration {

    private $tableName = 'common_cashback_rules';

    public function up() {
        $fields = array(
            'note' => array(
                'type' => 'text',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('note', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('note', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'note');
            }
        }
    }
}
