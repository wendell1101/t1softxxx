<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_modify_column_to_promocmssetting_20210716 extends CI_Migration
{

    private $tableName = "promocmssetting";

    public function up()
    {
        $fields = array(
            'promoDetails' => array(
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ),
        );

        if ($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('promoDetails', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }

    public function down()
    {
        // no action needed as data truncation may occur
    }
}