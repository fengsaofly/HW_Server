<?php


class User_model extends CI_Model{
	var $dbName = 'User';
	var $primaryKey = 'id';
	var $resource_db = 'U_Resource';
	var $resource_foreign_key = 'u_avatar';
	var $r_spath = 'resource_spath';
	var $r_lpath = 'resource_lpath';
	var $userName = 'user_name';
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

	public function get_user($userName)
	{

		$ids = array();
		$q_id_array = array();



	  	$this->db->select('Q.'.$this->primaryKey.' as userId ,R.'.$this->r_spath.' ,R.'.$this->r_lpath.' ,Q.updated_time');
		$this->db->from($this->dbName.' as Q ');
		$this->db->join($this->resource_db.' as R','R.resource_id = Q.'.$this->resource_foreign_key,'left');//左外连接
		$this->db->where($this->primaryKey,$userName);
		

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
	//获取指定用户的id
	public function getUserId($userName)
	{

		$id  = 0;

		$this->db->select('id');
		$this->db->from($this->dbName);
		$this->db->where($this->userName,$userName);

		$q = $this->db->get();
		$data = array_shift($q->result_array());

		return $data['id'];
}
	//获取多人信息
	public function get_icons($data)
    {
        
        $this->db->select('*');
		$this->db->from($this->dbName.' as Q ,'.$this->resource_db.' as R');
		$this->db->where('R.resource_id = Q.'.$this->resource_foreign_key);//左外连接
		$this->db->where_in('Q.'.$this->userName, $data);

		$query = $this->db->get();
		
        return $query->result();

    }
    //只获取缩略图
	public function get_thumbIcon($user_name)
    {
        
        $this->db->select('R.'.$this->r_spath);
		$this->db->from($this->dbName.' as Q ,'.$this->resource_db.' as R');
		$this->db->where('R.resource_id = Q.'.$this->resource_foreign_key);//左外连接
		$this->db->where_in('Q.'.$this->userName, $user_name);

		$query = $this->db->get();
		
       	$data = array_shift($query->result_array());

		return $data[$this->r_spath];

    }
	/*获取用户的id信息，包含isNew变量，指明用户是否是新用户*/
	public function getID($userName)
	{
		$idInfo = array();

		$id = $this->getUserId($userName) == null ? 0 : $this->getUserId($userName);

		

		//数据库没记录
		if($id == 0)
		{
			$id = $this->get_next_id();
			$idInfo['id'] = $id;
			$idInfo['isNew'] = 'true';
		}
		else
		{
			// $id = $data['id'];
			$idInfo['id'] = $id;
			$idInfo['isNew'] = 'false';
		}

		return $idInfo;

		
	}
	/*获取指定用户相关的问题和回复*/
	public function get_question($start=-1,$params=FALSE)
	{

		$ids = array();
		$q_id_array = array();
		
		// $ids = $params ? $this->getIDs($start,$params) : $this->getIDs($start) ;
		$user = $params['user'];
		// $start = $params['start'];

		$query  = "select * from Question where q_user = ".$user." UNION 
						SELECT *  FROM  QuestionReply where qr_user = ".$user."  
								order by created_time desc limit ".$start." , ".$this->limit ;

	  	$result = $this->db->query($query);
		$error = $this->db->_error_message();

		return $error ?  -1 : $result->result_array();
	}

	public function get_email($user)
	{
		$query = $this->db->get_where($this->dbName,  array('user_name' => $user));
		$data = array_shift($query->result_array());

		return $data['email'];
	}
	/*获取指定用户相关的问题和回复*/
	public function get_story($start=-1,$params=FALSE)
	{

		$ids = array();
		$q_id_array = array();
		
		// $ids = $params ? $this->getIDs($start,$params) : $this->getIDs($start) ;
		$user = $params['user'];
		// $start = $params['start'];

		$query  = " SELECT *  FROM  StoryReply where sr_user = ".$user." UNION
		 SELECT * from Story where s_user = ".$user." order by created_time desc limit ".$start." , ".$this->limit ;

	  	$result = $this->db->query($query);
		$error = $this->db->_error_message();

		return $error ?  -1 : $result->result_array();
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
	public function getRecentQInfo($params=FALSE)
	{
		$user = '';
		$time = null;
		if (!$params) {
			return -2;
		}
		if (isset($params['user']) && $params['user']!='') {
			$user = $params['user'];
		}
		if (isset($params['time']) && $params['time']>0) {
			$time = $params['time'];
		}
		$sql = "select QR.*,UR.resource_spath from QuestionReply as QR,User as U,U_Resource as UR 
					where QR.qr_q in (select id from Question where q_user = '".$user."' ) 
							and  QR.created_time > ".$time." and qr_user!='".$user."' and U.user_name = '".$user."' and u.u_avatar = UR.resource_id";
	
		$result = $this->db->query($sql);
		$error = $this->db->_error_message();

		return $error ?  -1 : $result->result_array();

	}
	/**
	*更新问题，主要考虑更新问题状态：已解决 or 未解决 
	*@param $data  如果没有传值，则表示就是在更新问题状态，如果有传了，就是更新data中指定的字段
	*	
	***/
	public function update_user($userID,$data=FALSE){
		
		if (!$data) {
			return null;
		}
		// $data = array_splice($_POST,4,1); 
		$this->db->where($this->primaryKey, $userID);
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