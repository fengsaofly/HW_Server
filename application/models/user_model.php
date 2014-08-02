<?php


class User_model extends CI_Model{
	var $dbName = 'User';
	var $primaryKey = 'id';
	var $resource_db = 'U_Resource';
	var $resource_foreign_key = 'u_avatar';
	var $r_spath = 'resource_spath';
	var $r_lpath = 'resource_lpath';
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
	// public function get_user($slug=FALSE,$limit)
	public function get_user($start=-1,$params=FALSE)
	{

		$ids = array();
		$q_id_array = array();
		
		$ids = $params ? $this->getIDs($start,$params) : $this->getIDs($start) ;
		


  		foreach ($ids as $row) {
			array_push($q_id_array, $row['id']);
		}
		//没有数据，返回空
		if (count($q_id_array) <= 0) {
			return null;
		}



	  	$this->db->select('*');
		$this->db->from($this->dbName.' as Q ');
		// $this->db->where('(Q.q_resource = R.resource_id) ');
		$this->db->join($this->resource_db.' as R','R.resource_id = Q.'.$this->resource_foreign_key,'left');//左外连接
		$this->db->where_in('Q.id',$q_id_array);
		$this->db->order_by('Q.created_time desc');
		

		$query = $this->db->get();
		$error = $this->db->_error_message();

		return $error ?  -1 : $query->result_array();
	}
	public function get_resourceID($id,$params = FALSE)
	{
		if (!$params) {
			
		
			$this->db->select('Q.'.$this->resource_foreign_key.' , R.'.$this->r_lpath.' , R.'.$this->r_spath);
			$this->db->from($this->dbName.' as Q ,'.$this->resource_db.' as R');
			$this->db->where('Q.'.$this->primaryKey,$id);
			$q = $this->db->get($this->dbName);
			//id是唯一的，只返回一个结果
			$data = array_shift($q->result_array());

			return $data;
		}
		return null;
		
		
	}
	//获取新增用户的id
	public function get_next_id()
	{
	   // $query = $this->db->query("select MAX(resource_id) as maxid from ".$this->dbName);
	   $this->db->select_max($this->primaryKey, 'maxid');
	   $query = $this->db->get($this->dbName);
	   // $row = $query->first_row('array');
	   // $maxId = $row['resource_id'];
	   $maxId = 0;
	   if ($query->num_rows() > 0){
	   	# code...
		   	$row  = $query->row();
			 $maxId = $row->maxid;
			
			$maxId++;
	   }
	   else  $maxId = 0;

	   return $query->row()->maxid + 1;
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
	public function update_user($user_name,$data=FALSE){
		
		if (!$data) {
			return null;
		}
		// $data = array_splice($_POST,4,1); 
		$this->db->where($this->primaryKey, $user_name);
		$this->db->update($this->dbName, $data);

	}
    // public function get_user($dataArray){
    
       
    //    // $this->db->where_in('q_user', $dataArray);
    //    $query = $this->db->get($this->dbName);
        
    //    return $query->result();
    // }


	//插入数据库
	public function set_user($data)
	{
	
		//插入数据库
        $result = $this->db->insert($this->dbName,$data);
        $newUserId = mysql_insert_id();
		
        return $newUserId;
		// print_r($_POST);
	    // return	
	}


}