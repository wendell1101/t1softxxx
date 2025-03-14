<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Tag extends BaseModel {
    private $tableName = 'tag';

    public function __construct() {
        parent::__construct();
    }

    public function getTagById($db = null, $tagId) {
        if (!empty($db)) {
            $this->db = $db;
        }

        $this->db->from($this->tableName)->where(['tagId' => $tagId]);
        return $this->runOneRowArray();
    }

    public function getTagNameById($db = null, $tagId) {
        if (!empty($db)) {
            $this->db = $db;
        }

        return $this->getSpecificColumn($this->tableName, 'tagName', ['tagId' => $tagId]);
    }

    public function getTagColorByTagName($db = null, $tagName) {
        if (!empty($db)) {
            $this->db = $db;
        }

        return $this->getSpecificColumn($this->tableName, 'tagColor', ['tagName' => $tagName]);
    }
}
