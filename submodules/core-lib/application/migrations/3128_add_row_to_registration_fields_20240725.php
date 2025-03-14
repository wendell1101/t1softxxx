<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20240725 extends CI_Migration {
    
    private $tableName = 'registration_fields';

    public function up() {

        $this->db->trans_start();
        
        $this->db->where('alias', 'isInterdicted');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'isInjunction');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'storeCode');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 76,
                'field_order' => 76,
                'type' => '1',
                'field_name' => 'Is Interdicted',
                'alias' => 'isInterdicted',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 77,
                'field_order' => 77,
                'type' => '1',
                'field_name' => 'Is Injunction',
                'alias' => 'isInjunction',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 78,
                'field_order' => 78,
                'type' => '1',
                'field_name' => 'Store Code',
                'alias' => 'storeCode',
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
