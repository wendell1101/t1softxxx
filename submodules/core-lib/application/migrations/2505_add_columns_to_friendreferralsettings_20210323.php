<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_friendreferralsettings_20210323 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

    public function up() {
        $fields1 = array(
            'referrerBet' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ),
        );

        $fields2 = array(
            'referrerDeposit' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('referrerBet', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }

            if(!$this->db->field_exists('referrerDeposit', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('referrerBet', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'referrerBet');
            }
            if($this->db->field_exists('referrerDeposit', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'referrerDeposit');
            }
        }
    }
}