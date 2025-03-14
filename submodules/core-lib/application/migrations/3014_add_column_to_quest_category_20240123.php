<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_quest_category_20240123 extends CI_Migration {
	private $tableName = 'quest_category';

    public function up() {
        $field1 = array(
            'showTimer' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            )
        );
        $field2 = array(
            'startAt' => array(
                'type' => 'DATETIME',
                'null' => false,
            )
        );

        $field3 = array(
            'endAt' => array(
                'type' => 'DATETIME',
                'null' => false,
            )
        );

        $field4 = array(
            'coverQuestTime' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
                'default' => '0'
            )
        );


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('showTimer', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }

            if(!$this->db->field_exists('startAt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('endAt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
        if(!$this->db->field_exists('coverQuestTime', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field4);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('showTimer', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'showTimer');
            }

            if($this->db->field_exists('startAt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'startAt');
            }
            if($this->db->field_exists('endAt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'endAt');
            }
            if($this->db->field_exists('coverQuestTime', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'coverQuestTime');
            }
        }
    }
}
///END OF FILE/////