<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Player_points extends BaseModel {

	protected $tableName = 'player_points';	

	# TYPE
	const TYPE_FROZEN = 'frozen';
	
	/**
	 * overview : Point_transactions constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

    public function getFozenPlayerPoints($playerId){

        $data = $this->getPlayerPoints($playerId, self::TYPE_FROZEN);    
        $points = isset($data['points'])?floatval($data['points']):0;
        return $points;
    }

    public function getPlayerPoints($playerId, $type){

        //get data
        $this->db->from($this->tableName);
		$this->db->where('player_id', $playerId);		
        $this->db->where('type', $type);		
		$query = $this->db->get();
		$data = $query->row_array();
        if(empty($data)){
            return false;
        }else{
            return $data;
        }        
    }

    public function insertUpdatePlayerPoints($playerId, $type, $points){

        //get data
        $this->db->from($this->tableName);
		$this->db->where('player_id', $playerId);		
        $this->db->where('type', $type);		
		$query = $this->db->get();
		$data = $this->getPlayerPoints($playerId, $type);
        if($data){
            $updateData = [                
                'points'=>$points
            ];
            $this->db->set($updateData);
            $this->db->where("player_id", $playerId);
            $this->db->where("type", $type);
            return $this->runAnyUpdate($this->tableName);
        }else{
            $data = [
                'type'=>$type,
                'player_id'=>$playerId,
                'points'=>$points
            ];
    
            return $this->insertRow($data);
        }        
    }

    public function incrementPlayerPoints($playerId, $addPoints, $type){
		$data = $this->getPlayerPoints($playerId, $type);
        if(!empty($data)){
            $newPoints = floatval($addPoints)+floatval($data['points']);
            $updateData = [                
                'points'=>$newPoints
            ];
            $this->db->set($updateData);
            $this->db->where("player_id", $playerId);
            $this->db->where("type", $type);
            return $this->runAnyUpdate($this->tableName);
        }else{
            $data = [
                'type'=>$type,
                'player_id'=>$playerId,
                'points'=>$addPoints
            ];
    
            return $this->insertRow($data);
        }        
    }

    public function decrementPlayerPoints($playerId, $decPoints, $type){

		$data = $this->getPlayerPoints($playerId, $type);
        if(!empty($data)){
            if(floatval($data['points'])<floatval($decPoints)){
                return false;
            }
            $newPoints = floatval($data['points'])-floatval($decPoints);
            $updateData = [                
                'points'=>$newPoints
            ];
            $this->db->set($updateData);
            $this->db->where("player_id", $playerId);
            $this->db->where("type", $type);
            return $this->runAnyUpdate($this->tableName);
        }else{                
            return false;
        }        
    }

}

/* Location: ./application/models/player_points.php */
