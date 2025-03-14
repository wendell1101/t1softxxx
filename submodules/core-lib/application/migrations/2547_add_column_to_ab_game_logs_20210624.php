<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ab_game_logs_20210624 extends CI_Migration {

    private $tableName = 'ab_game_logs';

    public function up() {
        $field1 = array(
            'gameResult2' => array(
                'type' => 'VARCHAR',
                'constraint' => '2000',
                'null' => true
            )
        );

        $field2 = array(
            'appType' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            )
        );

        $field3 = array(
            'betMode' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            )
        );

        # SBE additional info

        $field4 = array(
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        $field5 = array(
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            )
        );

        $field6 = array(
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        $field7 = array(
            'uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            )
        );

        $field8 = array(
            'external_game_id' => array(
                 'type' => 'VARCHAR',
                 'constraint' => '300',
                 'null'=> true
             )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('gameResult2', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('appType', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('betMode', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
            if(!$this->db->field_exists('md5_sum', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field4);
            }
            if(!$this->db->field_exists('response_result_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field5);
            }
            if(!$this->db->field_exists('external_uniqueid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field6);
            }
            if(!$this->db->field_exists('uniqueid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field7);
            }
            if(!$this->db->field_exists('external_game_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field8);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('gameResult2', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'gameResult2');
            }
            if($this->db->field_exists('appType', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'appType');
            }
            if($this->db->field_exists('betMode', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'betMode');
            }
            if($this->db->field_exists('md5_sum', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'md5_sum');
            }
            if($this->db->field_exists('response_result_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'response_result_id');
            }
            if($this->db->field_exists('external_uniqueid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'external_uniqueid');
            }
            if($this->db->field_exists('uniqueid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'uniqueid');
            }
            if($this->db->field_exists('external_game_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'external_game_id');
            }
        }
    }
}
