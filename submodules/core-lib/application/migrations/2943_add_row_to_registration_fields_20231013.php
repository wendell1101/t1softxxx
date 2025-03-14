<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20231013 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {

        $data = array(
            array(
                'registrationFieldId' => 63,
                'field_order' => 63,
                'type' => '1',
                'field_name' => 'Instant Message 5',
                'alias' => 'imAccount5',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            $row = $this->db->get_where($this->tableName, array(
                'alias' => 'imAccount5',
                'type' => '1'
            ))->row_array();
            if(empty($row)) {
                $this->db->insert_batch('registration_fields', $data);
            }
        }
    }

    public function down() {
    }
}
