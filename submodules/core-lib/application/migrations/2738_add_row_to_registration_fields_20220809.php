<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20220809 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {

        $this->db->where('alias', 'imAccount4');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 62,
                'field_order' => 62,
                'type' => '1',
                'field_name' => 'Instant Message 4',
                'alias' => 'imAccount4',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        $this->db->insert_batch('registration_fields', $data);
    }

    public function down() {
        $this->db->where('alias', 'imAccount4');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);
    }
}
