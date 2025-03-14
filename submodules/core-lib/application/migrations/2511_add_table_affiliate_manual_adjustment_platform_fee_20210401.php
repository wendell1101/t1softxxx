<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_affiliate_manual_adjustment_platform_fee_20210401 extends CI_Migration
{
    private $tableName = 'addon_affiliate_platform_fee';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true,
            ),
            'year_month' => array(
                'type' => 'INT',
                'null' => false,

            ),
            'affiliate_id' => array(
                'type' => 'INT',
                'null' => false,

            ),
            'platform_fee' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0,
            ),
            'note' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'updated_by' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => false,

            ),
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }

    }

    public function down()
    {}
}
