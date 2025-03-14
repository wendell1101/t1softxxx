<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_vip_grade_report_20210114 extends CI_Migration {

    private $tableName = 'vip_grade_report';

    public function up() {

        $field1 = array(
            'applypromomsg' => array(
                'type' => 'json',
                'null' => true
            ),
        );

        if(!$this->db->field_exists('applypromomsg', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field1);
        }
    }

    public function down() {
        if($this->db->field_exists('applypromomsg', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'applypromomsg');
        }
    }
}