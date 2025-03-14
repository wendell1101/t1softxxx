<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_messages_20220119 extends CI_Migration {
	private $tableName = 'messages';

    public function up() {
        $fields = array(
            'broadcast_id' => array(
                'type' => 'BIGINT',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('broadcast_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('broadcast_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'broadcast_id');
            }
        }
    }
}
///END OF FILE/////