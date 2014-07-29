<?php

class Question_model extends CI_Model{
	var $dbName = 'Question';
	var $primaryKey = 'id';
	var $resource_db = 'Resource';
	public function __construct()
	{
		$this->load->database();
	}
	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $slug  如果没传值，则表示查询所有问题；如果传值，则查的是指定内容
	*	
	***/
	// public function get_question($slug=FALSE,$limit)
	public function get_question($slug=FALSE)
	{
		if (!$slug) {
			// print_r('没有');
			// $this->db->where();
			// $this->db->query('select * from '.$this->dbName.','.$this->resource_db.'where ');
			// $query = $this->db->get($this->dbName);
			// return $query->result_array();

			$this->db->select('*');
			$this->db->from($this->dbName.' as Q , '.$this->resource_db.' as R');
			$this->db->where('(Q.q_resource = R.resource_id)');
			// if($limit) {
			// 	$this->db->limit($limit, $offset);
			// }
			$query = $this->db->get();
			$error = $this->db->_error_message();
			if ($error)	return -1;
			return $query->result_array();
		}
	  
	  $query = $this->db->get_where($this->dbName, array($this->primaryKey => $slug));
	  return $query->result_array();//返回结果数组
	}
	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $data  如果没有传值，则表示就是在更新问题状态，如果有传了，就是更新data中指定的字段
	*	
	***/
	public function update_question($q_id,$data=FALSE){
		
		if (!$data) {
			$data  = array('q_status' => 1 );
		}
		// $data = array_splice($_POST,4,1); 
		$this->db->where($this->primaryKey, $q_id);
		$this->db->update($this->dbName, $data);

	}
    // public function get_question($dataArray){
    
       
    //    // $this->db->where_in('q_user', $dataArray);
    //    $query = $this->db->get($this->dbName);
        
    //    return $query->result();
    // }

	public function set_question($data)
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