<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_sagaming_game_logs_20241106 extends CI_Migration {
    private $tableName = 'sagaming_game_logs';

    public function up()
    {
        // Add `created_at` column with default current timestamp
        if (!$this->db->field_exists('created_at', $this->tableName)) {
            $this->db->query("ALTER TABLE {$this->tableName} 
                              ADD COLUMN `created_at` TIMESTAMP 
                              DEFAULT CURRENT_TIMESTAMP 
                              NOT NULL");
        }

        // Add `updated_at` column with default current timestamp and update on modification
        if (!$this->db->field_exists('updated_at', $this->tableName)) {
            $this->db->query("ALTER TABLE {$this->tableName} 
                              ADD COLUMN `updated_at` TIMESTAMP 
                              DEFAULT CURRENT_TIMESTAMP 
                              ON UPDATE CURRENT_TIMESTAMP 
                              NOT NULL");
        }
    }

    public function down()
    {
        // Drop both `created_at` and `updated_at` columns during migration rollback
        $this->dbforge->drop_column($this->tableName, 'created_at');
        $this->dbforge->drop_column($this->tableName, 'updated_at');
    }
}