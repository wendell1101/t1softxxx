<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_agent_and_structure_game_platform_table_20220616 extends CI_Migration {

		
    private $tables = ['agency_structure_game_platforms', 'agency_agent_game_platforms'];


    public function up() {
        $field1 = array(
            'rolling_comm_basis' => array(
				'type' => 'VARCHAR',
				'constraint' => 100,
				'null' => true,
			)
        );

        $field2 = array(
			'rev_share' => array(
				'type' => 'DOUBLE',
				'null' => false,
                'default' => 0
			)
        );

        $field3 = array(
			'rolling_comm' => array(
				'type' => 'DOUBLE',
				'null' => false,
                'default' => 0
			)
        );

        $field4 = array(
			'rolling_comm_out' => array(
				'type' => 'DOUBLE',
				'null' => false,
                'default' => 0
            )
        );

        $field5 = array(
            'bet_threshold' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            )
        );

        $field6 = array(
            'platform_fee' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            )
        );

        $field7 = array(
            'min_rolling_comm' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),			
        );

        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('rolling_comm_basis', $table)){
                    $this->dbforge->add_column($table, $field1);
                }
                if(!$this->db->field_exists('rev_share', $table)){
                    $this->dbforge->add_column($table, $field2);
                }
                if(!$this->db->field_exists('rolling_comm', $table)){
                    $this->dbforge->add_column($table, $field3);
                }
                if(!$this->db->field_exists('rolling_comm_out', $table)){
                    $this->dbforge->add_column($table, $field4);
                }
                if(!$this->db->field_exists('bet_threshold', $table)){
                    $this->dbforge->add_column($table, $field5);
                }
                if(!$this->db->field_exists('platform_fee', $table)){
                    $this->dbforge->add_column($table, $field6);
                }
                if(!$this->db->field_exists('min_rolling_comm', $table)){
                    $this->dbforge->add_column($table, $field7);
                }
            }
        }  
    }

    public function down() {
    }
	
}
