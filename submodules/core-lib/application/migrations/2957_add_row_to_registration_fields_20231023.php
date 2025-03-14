<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20231023 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {

        $this->db->where('alias', 'issuingLocation');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'issuanceDate');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'middleName');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'maternalName');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'isPEP');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'acceptCommunications');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 64,
                'field_order' => 64,
                'type' => '1',
                'field_name' => 'Issuing Location',
                'alias' => 'issuingLocation',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 65,
                'field_order' => 65,
                'type' => '1',
                'field_name' => 'Issuance Date',
                'alias' => 'issuanceDate',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 66,
                'field_order' => 66,
                'type' => '1',
                'field_name' => 'Middle Name',
                'alias' => 'middleName',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 67,
                'field_order' => 67,
                'type' => '1',
                'field_name' => 'Maternal Name',
                'alias' => 'maternalName',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 68,
                'field_order' => 68,
                'type' => '1',
                'field_name' => 'Is PEP',
                'alias' => 'isPEP',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 69,
                'field_order' => 69,
                'type' => '1',
                'field_name' => 'Accept Communications',
                'alias' => 'acceptCommunications',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        $this->db->insert_batch('registration_fields', $data);
	}

    public function down() {}
}
