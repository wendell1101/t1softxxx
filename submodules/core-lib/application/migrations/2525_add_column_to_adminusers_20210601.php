<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_adminusers_20210601 extends CI_Migration
{
    private $tableName = 'adminusers';

    public function up() {

        $fields1 = array(
            "cs0maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields2 = array(
            'cs0singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields3 = array(
            "cs0approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        $fields4 = array(
            "cs1maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields5 = array(
            'cs1singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields6 = array(
            "cs1approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        $fields7 = array(
            "cs2maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields8 = array(
            'cs2singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields9 = array(
            "cs2approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        $fields10 = array(
            "cs3maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields11 = array(
            'cs3singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields12 = array(
            "cs3approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        $fields13 = array(
            "cs4maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields14 = array(
            'cs4singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields15 = array(
            "cs4approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        $fields16 = array(
            "cs5maxWidAmt" => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => false,
                'default' => 0
            ),
        );
        $fields17 = array(
            'cs5singleWidAmt' => array(
                'type' => 'INT',
                'constraint' => 40,
                'null' => true,
            ),
        );
        $fields18 = array(
            "cs5approvedWidAmt" => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );
        
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('cs0maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('cs0singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('cs0approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }
            if(!$this->db->field_exists('cs1maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields4);
            }
            if(!$this->db->field_exists('cs1singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields5);
            }
            if(!$this->db->field_exists('cs1approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields6);
            }
            if(!$this->db->field_exists('cs2maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields7);
            }
            if(!$this->db->field_exists('cs2singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields8);
            }
            if(!$this->db->field_exists('cs2approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields9);
            }
            if(!$this->db->field_exists('cs3maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields10);
            }
            if(!$this->db->field_exists('cs3singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields11);
            }
            if(!$this->db->field_exists('cs3approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields12);
            }
            if(!$this->db->field_exists('cs4maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields13);
            }
            if(!$this->db->field_exists('cs4singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields14);
            }
            if(!$this->db->field_exists('cs4approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields15);
            }
            if(!$this->db->field_exists('cs5maxWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields16);
            }
            if(!$this->db->field_exists('cs5singleWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields17);
            }
            if(!$this->db->field_exists('cs5approvedWidAmt', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields18);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cs0maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs0maxWidAmt');
            }
            if($this->db->field_exists('cs0singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs0singleWidAmt');
            }
            if($this->db->field_exists('cs0approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs0approvedWidAmt');
            }
            if($this->db->field_exists('cs1maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs1maxWidAmt');
            }
            if($this->db->field_exists('cs1singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs1singleWidAmt');
            }
            if($this->db->field_exists('cs1approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs1approvedWidAmt');
            }
            if($this->db->field_exists('cs2maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs2maxWidAmt');
            }
            if($this->db->field_exists('cs2singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs2singleWidAmt');
            }
            if($this->db->field_exists('cs2approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs2approvedWidAmt');
            }
            if($this->db->field_exists('cs3maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs3maxWidAmt');
            }
            if($this->db->field_exists('cs3singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs3singleWidAmt');
            }
            if($this->db->field_exists('cs3approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs3approvedWidAmt');
            }
            if($this->db->field_exists('cs4maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs4maxWidAmt');
            }
            if($this->db->field_exists('cs4singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs4singleWidAmt');
            }
            if($this->db->field_exists('cs4approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs4approvedWidAmt');
            }
            if($this->db->field_exists('cs5maxWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs5maxWidAmt');
            }
            if($this->db->field_exists('cs5singleWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs5singleWidAmt');
            }
            if($this->db->field_exists('cs5approvedWidAmt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cs5approvedWidAmt');
            }
        }
    }
}