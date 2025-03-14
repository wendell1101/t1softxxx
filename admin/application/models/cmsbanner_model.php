<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * CMS Banner
 */
class Cmsbanner_model extends BaseModel {
    /**
     * detail: Get all cms banner
     *
     * @param string $sort
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllCMSBanner() {
        $this->db
        	->select([
        		'B.*' ,
        		'admin1.username AS createdBy' ,
        		'admin2.username AS updatedBy' ,
        		'E.system_code AS game_platform_system_code' ,
                'B.sort_order as sort_order'
        		// 'json_extract(extra, "$.game.goto_lobby") AS game_goto_lobby' ,
        		// 'json_extract(extra, "$.game.platform_id") AS game_platform_id' ,
        		// 'json_extract(extra, "$.game.gametype") AS game_gametype'
        	])
        	->select([
        		"extra->>'$.game.goto_lobby' != 'false' AS game_goto_lobby" ,
        		"extra->>'$.game.platform_id' AS game_platform_id" ,
        		"extra->>'$.game.gametype' AS game_gametype"
        	], false)
        	->from('cmsbanner AS B')
        	->join('adminusers AS admin1', 'admin1.userId = B.createdBy', 'left')
        	->join('adminusers AS admin2', 'admin2.userId = B.updatedBy', 'left')
        	->join('external_system AS E',"B.extra->>'$.game.platform_id' = E.id", 'left')
            ->order_by("sort_order", "asc")
            ->order_by("createdOn", "asc")
        ;

        $res = $this->runMultipleRowArray();

        return $res;
    }

	/**
	 * detail: Will get cms banner
	 *
	 * @param int $bannercmsId
	 * @return array
	 */
	public function getBannerCmsDetails($bannercmsId) {
		$this->db
			->from('cmsbanner')
			->where('bannerId', $bannercmsId)
			->select([
        		'*' ,
        		"extra->>'$.game.goto_lobby' != 'false' AS game_goto_lobby" ,
        		"extra->>'$.game.platform_id' AS game_platform_id" ,
        		"extra->>'$.game.gametype' AS game_gametype"
        	], false)
        ;
		return $this->runOneRowArray();
	}

    /**
     * Get cms banner
     *
     * $bannerType int
     * @return	$array
     */
    public function getCmsBannerByCategory($category_id) {
        $this->db->from('cmsbanner');
        $this->db->where('cmsbanner.category', $category_id);

        return $this->runMultipleRowArray();
    }

    /**
     * Get cms banner for Player Center
     *
     * $bannerType int
     * @return	$array
     */
    public function getActiveCmsBannerByCategory($category_id) {
        $this->db->from('cmsbanner');
        $this->db->where('cmsbanner.category', $category_id);
        $this->db->where('cmsbanner.status', 'active');

        return $this->runMultipleRowArray();
    }

    /**
     * Data source for player center API listBanner
     * Returns all active banners
     * @return array
     */
    public function comapiGetActiveCmsBanners() {
        $this->db
            ->from('cmsbanner')
            ->where('cmsbanner.status', 'active')
            ->select([
                'bannerId AS id' ,
                // 'status' ,
                'title' ,
                'summary AS description' ,
                'link' ,
                'link_target' ,
                'bannerName' ,
                "extra->>'$.game.goto_lobby' != 'false' AS game_goto_lobby" ,
                "extra->>'$.game.platform_id' AS game_platform_id" ,
                "extra->>'$.game.gametype' AS game_gametype" ,
                'updatedOn' ,
                'sort_order' ,
            ], false)
            ->order_by('updatedOn', 'desc')
        ;

        return $this->runMultipleRowArray();
    }

	/**
	 * detail: banner list by bannerId from cms table
	 *
	 * @param int $banner_id cmsbanner bannerId
	 * @return array
	 */
	public function getBannerById($banner_id) {
		$this->db->from('cmsbanner');
		$this->db->where('bannerId', $banner_id);
		return $this->runOneRowArray();
	}

	/**
	 * detail: edit banner by bannerId to cms banner table
	 *
	 * @param array $data array
	 * @param int $bannercmsId cmsbanner bannerId field
	 * @return mixed
	 */
	public function editBannerCms($data, $bannercmsId) {
        $data['updatedOn'] = $this->CI->utils->getNowForMysql();
        $data['updatedBy'] = $this->CI->authentication->getUserId();

		$this->db->where('bannerId', $bannercmsId);
		$res = $this->db->update('cmsbanner', $data);

		$this->utils->debug_log(__METHOD__, 'edit sql', $this->db->last_query());

		return $res;
	}

	/**
	 * detail: Inserts data to banner cms
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function addCmsBanner($data) {
        $data['createdOn'] = $this->CI->utils->getNowForMysql();
        $data['createdBy'] = $this->CI->authentication->getUserId();

        $data['updatedOn'] = $this->CI->utils->getNowForMysql();
        $data['updatedBy'] = $this->CI->authentication->getUserId();
		return $this->db->insert('cmsbanner', $data);
	}

	/**
	 * detail: Will delete cms banner
	 *
	 * @param int $bannerId cmsbanner field
	 * @return mixed
	 */
	public function deleteBannerCms($bannerId) {
		$this->db->where('bannerId', $bannerId);
		return $this->db->delete('cmsbanner');
	}

	/**
	 * detail: activate banner cms
	 *
	 * @param array $data cmsbanner field
	 * @return mixed
	 */
	public function activateBannerCms($bannerId, $status) {
        $data = array(
            'updatedBy' => $this->authentication->getUserId(),
            'updatedOn' => date("Y-m-d H:i:s"),
            'status' => $status
        );

        $this->db->where('bannerId', $bannerId);
		return $this->db->update('cmsbanner', $data);
	}
}

/* End of file cmsbanner_model.php */
/* Location: ./application/models/cmsbanner_model.php */
