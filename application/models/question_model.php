<?php

class Question_model extends CI_Model{
	var $dbName = 'Question';
	var $primaryKey = 'id';
	var $resource_db = 'Q_Resource';
	var $limit = DOWNLOAD_SLOT_SIZE;
	var $db_foreign_key = 'q_resource';
	var $reply_foreign_key ='qr_q';
	var $reply_db ='';
	var $type = 'qr_type';
	public function __construct()
	{
		$this->load->database();
		$this->reply_db = $this->dbName.'Reply';
	}
	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $slug  如果没传值，则表示查询所有问题；如果传值，则查的是指定内容
	*	
	***/
	// public function get_question($slug=FALSE,$limit)
	public function get_question($start=-1,$params=FALSE)
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

		// return $q_id_array;

	  	$this->db->select('*');
		$this->db->from($this->dbName.' as Q ');
		// $this->db->where('(Q.q_resource = R.resource_id) ');
		$this->db->join($this->resource_db.' as R','R.resource_id = Q.'.$this->db_foreign_key,'left');//左外连接
		$this->db->where_in('Q.id',$q_id_array);
		$this->db->order_by('Q.created_time desc');
		

		$query = $this->db->get();
		$error = $this->db->_error_message();

		return $error ?  -1 : $query->result_array();
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
	/*
	**获取回复的总数
	**
	*/
	public function getReplySum($id=FALSE)
	{
		if (!$id) {
			 return null;
		}
		$this->db->select('count(*) as sum');
		$this->db->from($this->reply_db);

		$this->db->where($this->reply_foreign_key,$id);
		$this->db->where($this->type,0);

		$query = $this->db->get();
		$row = $query->row_array();


		return $row['sum'];

	}
	/*
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $data  如果没有传值，则表示就是在更新问题状态，如果有传了，就是更新data中指定的字段
	*	
	***/
	public function update_question($q_id,$data=FALSE){
		
		if (!$data) {
			return null;	
		}
		$data['q_state']  =  1 ;
		$this->db->where($this->primaryKey, $q_id);
	    $this->db->update($this->dbName, $data);

		return $this->db->affected_rows();

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



}