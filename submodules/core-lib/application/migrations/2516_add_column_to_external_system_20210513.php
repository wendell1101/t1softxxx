<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_external_system_20210513 extends CI_Migration {

    public function up() {
        $fields = array(
            'auto_lift_runtime_flag' => array(
                'type' => 'TINYINT',
                'null' => true,
                'default'=>0,
            ),
        );

        if(!$this->db->field_exists('auto_lift_runtime_flag', 'external_system')){
            $this->dbforge->add_column('external_system', $fields);
        }

    }

    public function down() {
    }
}