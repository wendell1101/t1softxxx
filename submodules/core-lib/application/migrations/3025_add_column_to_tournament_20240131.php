<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_tournament_20240131 extends CI_Migration {
	private $tableName = 'tournament';

    public function up() {
        $field1 = array(
            'gamePlatformId' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );

        $field2 = array(
            'gameTypeId' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );

        $field3 = array(
            'gameTagId' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );

        $field4 = array(
            'gameDescriptionId' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('gamePlatformId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('gameTypeId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('gameTagId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
            if(!$this->db->field_exists('gameDescriptionId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field4);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('gamePlatformId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'gamePlatformId');
            }
            if($this->db->field_exists('gameTypeId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'gameTypeId');
            }
            if($this->db->field_exists('gameTagId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'gameTagId');
            }
            if($this->db->field_exists('gameDescriptionId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'gameDescriptionId');
            }
        }
    }
}
///END OF FILE/////