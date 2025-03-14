<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_hogamingseamless_transaction_logs_20200925 extends CI_Migration {

    private $tableName = 'hogamingseamless_transaction_logs';

    public function up()
    {
        
        $field = array(
            'related_data' => array(
                'type' => 'json',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('related_data', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
            
    }

    public function down()
    {
        
        if($this->db->field_exists('related_data', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'related_data');
        }   
    }
}