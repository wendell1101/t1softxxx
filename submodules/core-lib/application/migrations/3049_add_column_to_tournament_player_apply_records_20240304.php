<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_tournament_player_apply_records_20240304 extends CI_Migration {
	private $tableName = 'tournament_player_apply_records';
    
    public function up() {

        $field1 = array(
            'playerRank' => array(
                'type' => 'INT',
                'default' => 0,
            )
        );

        $field2 = array(
            'lastSyncTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            )
        );

        $field3 = array(
            'isSettled' => array(
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => 0,
            )
        );

        $field4 = array(
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('playerRank', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('lastSyncTime', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('isSettled', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
            if(!$this->db->field_exists('external_uniqueid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field4);
            }

            $this->player_model->addIndex($this->tableName, 'idx_playerRank', 'playerRank');
            $this->player_model->addIndex($this->tableName, 'idx_lastSyncTime', 'lastSyncTime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('playerRank', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'playerRank');
            }
            if($this->db->field_exists('lastSyncTime', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'lastSyncTime');
            }
            if($this->db->field_exists('isSettled', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'isSettled');
            }
            if($this->db->field_exists('external_uniqueid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'external_uniqueid');
            }
        }
    }
}
///END OF FILE/////