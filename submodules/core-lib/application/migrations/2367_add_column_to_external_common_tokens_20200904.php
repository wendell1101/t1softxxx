<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_external_common_tokens_20200904 extends CI_Migration
{
    private $tableName = 'external_common_tokens';

    public function up()
    {
        $fields = array(
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('currency', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'currency');
    }
}
