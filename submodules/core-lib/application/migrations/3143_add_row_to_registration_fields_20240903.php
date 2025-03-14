<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20240903 extends CI_Migration {
    
    private $tableName = 'registration_fields';

    public function up() {

        $this->db->trans_start();
        
        $this->db->where('alias', 'sourceIncome');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'natureWork');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 79,
                'field_order' => 79,
                'type' => '1',
                'field_name' => 'Source Income',
                'alias' => 'sourceIncome',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 80,
                'field_order' => 80,
                'type' => '1',
                'field_name' => 'Nature Work',
                'alias' => 'natureWork',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        $this->db->insert_batch('registration_fields', $data);

        $this->db->trans_complete();
	}

    public function down() {}
}
