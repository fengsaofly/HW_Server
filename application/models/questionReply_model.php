<?php

class Questionreply_model extends CI_Model{
	var $dbName = 'QuestionReply';
	var $primaryKey = 'id';
	var $resource_db = 'QR_Resource';
	var $limit = DOWNLOAD_SLOT_SIZE;
	
	public function __construct()
	{
		$this->load->database();

	}
	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $slug  如果没传值，则表示查询所有问题；如果传值，则查的是指定内容
	*	
	***/
	// public function get_QuestionReply($slug=FALSE,$limit)
	public function get_questionReply($start,$params=FALSE)
	{
		$ids = array();
		$qr_id_array = array();
		$ids = $params ? $this->getIDs($start,$params) : $this->getIDs($start) ;


		foreach ($ids as $row) {
			array_push($qr_id_array, $row['id']);
		}
		//没有数据，返回空
		if (count($qr_id_array) <= 0) {
			return null;
		}
		// return $qr_id_array;

	  	$this->db->select('*');
		$this->db->from($this->dbName.' as QR ');
		$this->db->join($this->resource_db.' as R','QR.qr_resource = R.resource_id','left');//左外连接
		$this->db->where_in('QR.id',$qr_id_array);
		$this->db->order_by('QR.created_time desc');

		// $this->db->limit($this->limit, $start);

		$query = $this->db->get();
		$error = $this->db->_error_message();
		if ($error)	return -1;

		return $query->result_array();

	}
	public function getIDs($start=-1,$params=FALSE)
	{
		$this->db->select('id');
		$this->db->from($this->dbName);
		
		if ($params) {
			 $this->db->where($params);
		}
		$this->db->order_by('created_time desc');
		$this->db->limit($this->limit, $start);

		$query = $this->db->get();

		return $query->result_array();

	}

	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $data  如果没有传值，则表示就是在更新问题状态，如果有传了，就是更新data中指定的字段
	*	
	***/
	public function update_questionReply($id,$data=FALSE){
		
		$query = null;
		if (!$data) {
			$query  = "update ".$this->dbName." set status = status+1 where id = ".$id ;
			$this->db->query($query);
		}

		else
		{
			$this->db->where($this->primaryKey, $id);
		 	$this->db->update($this->dbName, $data);
		}
	  	
		// $data = array_splice($_POST,4,1); 
	
		return $this->db->affected_rows();

	}
    // public function get_QuestionReply($dataArray){
    
       
    //    // $this->db->where_in('q_user', $dataArray);
    //    $query = $this->db->get($this->dbName);
        
    //    return $query->result();
    // }

	public function set_questionReply($data)
	{
	
		//插入数据库
        $result = $this->db->insert($this->dbName,$data);
        $newUserId = mysql_insert_id();
		
        return $newUserId;
		// print_r($_POST);
	    // return	
	}

	public function renameUploadFile($targetName,$sourceName,$dbName,$shopid){

		$domain = 'discounts';
		//为文件增加后缀名
		$pos = strripos($sourceName,'.'); 
		$fileType = substr($sourceName,$pos);
		
		$targetName =  $targetName.$fileType;


		$targetUrl = '/Shops/'.$shopid.'/'.$targetName;
		// print_r($targetUrl);
		$stor = new SaeStorage();
		if ($dbName == $this->dbName) {
			$sourceUrl = '/shops/'.$sourceName;
			$idTag = 'shop_id';
			$picTag = 'shop_pic';
			$id = $shopid;
		}
		else{
			$sourceUrl = '/discounts/'.$sourceName;
			$idTag = 'discount_id';	
			$picTag = 'discount_picture';
			$id = $targetName;
		}
		$content = $stor->read( $domain , $sourceUrl) ;
		if (!$content) {
			die('获取源文件数据失败！');
		}
		if($result = $stor->write($domain,$targetUrl,$content))
		{
			$data  = array($picTag => $result );
			$stor->delete($domain,$sourceUrl);
			$this->db->where($idTag, $id);
			$this->db->update($dbName, $data);
			return $result;
		}
		else{
         	var_dump($stor->errno(), $stor->errmsg());
         	return FALSE;
 		}
         
	}

}