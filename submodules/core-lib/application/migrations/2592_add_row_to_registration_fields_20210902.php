<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20210902 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {

        $this->db->where('alias', 'pix_number');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 61,
                'field_order' => 61,
                'type' => '1',
                'field_name' => 'Pix Number',
                'alias' => 'pix_number',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        $this->db->insert_batch('registration_fields', $data);
    }

    public function down() {}
}
