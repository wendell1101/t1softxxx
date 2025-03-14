<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_promocmssetting_20201216 extends CI_Migration {

    private $tableName = 'promocmssetting';

    public function up()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $fields = array(
                'promoOrder' => array(
                    'type' => 'TINYINT',
                    'null' => true,
                    'default' => 0
                )
            );

            if (!$this->db->field_exists('promoOrder', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'promoOrder');
    }
}