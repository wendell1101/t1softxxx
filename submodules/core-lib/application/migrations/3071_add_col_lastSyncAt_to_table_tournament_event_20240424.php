<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_add_col_lastSyncAt_to_table_tournament_event_20240424 extends CI_Migration {
	private $tableName = 'tournament_event';

	public function up() {
        $field1 = array(
            'lastSyncAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );

        $field2 = array(
            'releaseAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('lastSyncAt', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if( ! $this->db->field_exists('releaseAt', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field2);
            }


            if( $this->db->field_exists('lastSyncAt', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_lastSyncAt', 'lastSyncAt');
            }
            if( $this->db->field_exists('releaseAt', $this->tableName) ){
                $this->player_model->addIndex($this->tableName, 'idx_releaseAt', 'releaseAt');
            }
            
        }
	}

	public function down() {

	}
}
