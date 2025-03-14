<?php
trait withdrawal_process_flow_module {

	public function getAllTagsForPendingReview($pendingCustomTag = false){
		$this->load->model('player');
		$tags = $this->player->getAllTagsOnly();


        $tag_status = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_review_tags')->value,true);

        if($pendingCustomTag){
			$tag_status = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_custom_tags')->value,true);
		}

		$arr = array('status' => 'success', 'tags' => $tags, 'tagStatus' => $tag_status);
		echo json_encode($arr);
	}

	public function pendingRequestTags(){
		$this->load->model(array('player', 'player_model'));
		$taggedIds = $this->input->post('tagId');
		$tag_status = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_custom_tags')->value,true);
		$tag_status = count($tag_status) > 0 ? $tag_status : [];
		$tagName = [];

		if ($taggedIds) {

			foreach ($taggedIds as $tagId) {
				if(in_array($tagId, $tag_status,true)) {
					$tagName[] = $this->player_model->getTagNameByTagId($tagId);
				}
			}

			if(!empty($tagName)){
				echo json_encode(array("status" => "false", "tagStatus" => $taggedIds, "usedTagName" => $tagName));
					return;
			}

			$data = array("withdraw_pending_review_tags"=>json_encode($taggedIds));
			$newTag = $this->operatorglobalsettings->saveSettings($data);

			echo json_encode(array("status"=>"success",
								   "tagStatus" =>$taggedIds)
							);
		}else{
            $this->operatorglobalsettings->saveSettings( array("withdraw_pending_review_tags"=> null));
            $arr = array( "status"=>"success", "tagStatus" => array() );
            echo json_encode($arr);
		}
	}

	/*
	 * OGP-17242 withdraw_pending_custom_tags
	 *
	 * param array $taggedIds
	 */
	public function pendingRequestCustomTags(){
		$this->load->model(array('player'));
		$taggedIds = $this->input->post('tagId');
		$tag_status = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_review_tags')->value,true);
		$tag_status = count($tag_status) > 0 ? $tag_status : [];
		$tagName = [];

		if ($taggedIds) {
			if( empty($tag_status) ){
				$tag_status = [];
			}

			foreach ($taggedIds as $tagId) {
				if(in_array($tagId, $tag_status,true)) {
					$tagName[] = $this->player_model->getTagNameByTagId($tagId);
				}
			}

			if(!empty($tagName)){
				echo json_encode(array("status" => "false", "tagStatus" => $taggedIds, "usedTagName" => $tagName));
					return;
			}

			$data = array("withdraw_pending_custom_tags"=>json_encode($taggedIds));
			$newTag = $this->operatorglobalsettings->saveSettings($data);

			echo json_encode(array("status"=>"success",
								   "tagStatus" =>$taggedIds)
							);
		}else{
            $this->operatorglobalsettings->saveSettings( array("withdraw_pending_custom_tags"=> null));
            $arr = array( "status"=>"success", "tagStatus" => array() );
            echo json_encode($arr);
		}
	}

	/*
	 * This function will check if player tag is under withdraw pending tag
	 *
	 * param int $playerId
	 */
	public function checkPlayerIfTagIsUnderPendingWithdrawTag($playerId){
		$this->load->model(array('player_model','operatorglobalsettings'));
		$playerTag = $this->player_model->getPlayerTags($playerId,true);

		if(!$playerTag) return false;
		$withdrawPendingTags = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_review_tags')->value,true);
		if(!empty($withdrawPendingTags)){
			foreach ($playerTag as $key => $value) {
				if(in_array($value, $withdrawPendingTags)){
					return true;
				}
			}
		}
		return false;
	}

	/*
	 * This function will check if player tag is under withdraw pending tag
	 *
	 * param int $playerId
	 */
	public function checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId){
		$this->load->model(array('player_model','operatorglobalsettings'));
		$playerTag = $this->player_model->getPlayerTags($playerId,true);

		if(!$playerTag) return false;
		$withdrawPendingCustomTags = json_decode($this->operatorglobalsettings->getSetting('withdraw_pending_custom_tags')->value,true);
		if(!empty($withdrawPendingCustomTags)){
			foreach ($playerTag as $key => $value) {
				if(in_array($value, $withdrawPendingCustomTags)){
					return true;
				}
			}
		}
		return false;
	}
}