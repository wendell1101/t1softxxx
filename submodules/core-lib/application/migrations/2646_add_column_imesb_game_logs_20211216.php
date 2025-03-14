<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_imesb_game_logs_20211216 extends CI_Migration {

	private $tableName = 'imesb_game_logs';

	public function up() {
		//add column
        $fields = array(
            'is_pk_10_bet' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'roundnum' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'drawtime' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'bettypeid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'bettypename' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'betnum' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'settlestatus' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),

        );
		
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('is_pk_10_bet', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {

	}
}