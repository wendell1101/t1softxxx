<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_friendreferralsettings_20240301 extends CI_Migration {

    private $tableName='friendreferralsettings';

    public function up() {
        $column = array(
            'enabled_referred_single_choice' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            )
        );

        $column2 = array(
            'referredDepositCount' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('enabled_referred_single_choice', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
            if(!$this->db->field_exists('referredDepositCount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('enabled_referred_single_choice', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'enabled_referred_single_choice');
            }
            if($this->db->field_exists('referredDepositCount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'referredDepositCount');
            }
        }
    }
}