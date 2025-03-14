<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_password_history_20240115 extends CI_Migration {
	private $tableName = 'player_password_history';

    public function up() {
        $field1 = array(
            'is_message_notify' => array(
                'type' => 'BOOLEAN',
                'default' => 0,
                'null' => false,
            ),
        );

        $field2 = array(
            'messageId' => array(
                'type' => 'INT',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('is_message_notify', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }

            if(!$this->db->field_exists('messageId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('is_message_notify', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'is_message_notify');
            }

            if($this->db->field_exists('messageId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'messageId');
            }
        }
    }
}
///END OF FILE/////