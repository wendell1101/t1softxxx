<?php
/**
 *   filename:   Elasticsearch.php
 *   date:       2017-12-22
 *   @brief:     class for operations associated with Elasticsearch. You can choose send message to Elasticsearch.
 */
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Elasticsearch Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category    Elasticsearch	
 */
class CI_Elasticsearch {
    protected $client = null;
    const HOSTS = [
    ];

	public function __construct() {
		// Note:  Do not log messages from this constructor.
	}
	// insertDocument {{{2
	/**
	 *  create a document in given index
	 *
	 *  @param  string index name
	 *  @param  string type
	 *  @return bool
	 */
	public function insertDocument($message = '', $idx = null, $type = null, $hostname = '', $id = null) {
        if(empty($this->client)){
            return null;
        }

	} // insertDocument  }}}2
}
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of Elasticsearch.php
