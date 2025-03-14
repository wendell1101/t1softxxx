<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Migration_modify_column_for_sbtech_bti_game_logs_and_sbtech_new_game_logs_20240206 extends CI_Migration
{
    private $tableName1 = 'sbtech_bti_game_logs';
    private $tableName2 = 'sbtech_new_game_logs';

    public function up()
    {
        // Define the new column attributes
        $column = [
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '50', // Modify this constraint based on your requirements
                'null' => true,
            ]
        ];

        // Check if the table exists and the 'status' column doesn't exist
        if ($this->utils->table_really_exists($this->tableName1)) {
            if ($this->db->field_exists('status', $this->tableName1)) {
                $this->dbforge->modify_column($this->tableName1, $column);
            }
        }

        // Check if the table exists and the 'status' column doesn't exist
        if ($this->utils->table_really_exists($this->tableName2)) {
            if ($this->db->field_exists('status', $this->tableName2)) {
                $this->dbforge->modify_column($this->tableName2, $column);
            }
        }
    }

    public function down()
    {
        // The down migration is not implemented in your example
        // You may need to consider reverting the changes made in the 'up' method
        // For example, you might revert to the previous data type and constraints
    }
}
