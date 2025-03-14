<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_bonusReleaseCount_to_promorules_20240109 extends CI_Migration {

    private $tableName = 'promorules';

    public function up() {
        $fields = array(
            'bonusReleaseCount' => array(
                'type' => 'int',
                'null' => false,
                'default' => 0,
            ),
            'syncReleaseCountAt' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonusReleaseCount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonusReleaseCount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonusReleaseCount');
            }
            if($this->db->field_exists('syncReleaseCountAt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'syncReleaseCountAt');
            }
        }
    }
}
