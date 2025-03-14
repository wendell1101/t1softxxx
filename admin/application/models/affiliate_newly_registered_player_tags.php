<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 */
class Affiliate_newly_registered_player_tags extends BaseModel {

	protected $tableName = 'affiliate_newly_registered_player_tags';

	function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * @param	$data
	 * @return	array
	 */
	/**
	 * Update the player tags by Affiliate
	 * The player tag will be referened to, and applied into the newly registered player.
	 *
	 * @param integer $affiliate_id
	 * @param array $tag_id_list The tag_id list
	 * @return array The array structure as following,
	 * - $results['clearTags'] boolean Just get the result of clear.
	 * - $results['addTags'] array The result of addTagsIntoAffiliateId().
	 */
	public function updatePlayerTagsByAffiliate($affiliate_id, $tag_id_list) {
		$results = [];
		$results['bool'] = null;
		$results['clearTags'] = $this->deleteTagsByAffiliateId($affiliate_id);
		$results['addTags'] = $this->addTagsIntoAffiliateId($affiliate_id, $tag_id_list);

		$isAdded = false;
		if( ! empty($results['addTags']) ){
			$isAdded = true;
		}

		if( empty($results['clearTags']) && empty($isAdded) ){
			$results['bool'] = false;
		}else{
			$results['bool'] = true;
		}

		return $results;
	} // EOF updatePlayerTagsByAffiliate

	/**
	 * Delete the tags by affiliate_id
	 *
	 * @param integer $affiliate_id
	 * @return boolean the result of BaseModel::runRealDelete();
	 */
	public function deleteTagsByAffiliateId($affiliate_id) {
		$this->db->where('affiliate_id', $affiliate_id);
		return $this->runRealDelete($this->tableName);
	} // EOF deleteTagsByAffiliateId

	/**
	 * Add Tags Into the Affiliate
	 *
	 *
	 * @param integer $affiliate_id F.K. The affiliates.affiliateId
	 * @param array $tag_id_list The tagId list F.K. tag.tagId
	 * @return array The array list conations tag_id and inserted id as key-value structure.
	 */
	public function addTagsIntoAffiliateId($affiliate_id, $tag_id_list) {
		$return_results = [];
		if( ! empty($tag_id_list) ){
			foreach($tag_id_list as $indexNumber => $tag_id){
				$data = [];
				$data['affiliate_id'] = $affiliate_id;
				$data['tag_id'] = $tag_id;

				$return_results[$tag_id] = $this->runInsertData($this->tableName, $data);
			}
		}
		return $return_results;
	} // EOF addTagsIntoAffiliateId

	/**
	 * Get the tag id by affiliate
	 *
	 * @param integer $affiliate_id
	 * @return array
	 */
	public function getTagsByAffiliateId($affiliate_id) {
		$this->db->where('affiliate_id', $affiliate_id);
		$this->db->join('tag', 'affiliate_newly_registered_player_tags.tag_id = tag.tagId');
		$query = $this->db->get($this->tableName);
		return $this->getMultipleRowArray($query);
	} // EOF getTagsByAffiliateId

} // EOF Affiliate_newly_registered_player_tags