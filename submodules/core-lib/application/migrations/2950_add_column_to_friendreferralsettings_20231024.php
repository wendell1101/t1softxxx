<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_friendreferralsettings_20231024 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

    public function up() {
        $field = array(
            'bonusRateInReferrer' => array(
                'type' => 'double',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonusRateInReferrer', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonusRateInReferrer', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonusRateInReferrer');
            }
        }
    }
}