<?php

class Resource_model extends CI_Model{
	var $dbName = 'Resource';
	var $primaryKey = 'resource_id';
	public function __construct()
	{
		$this->load->database();
	}
	public function get_resource($slug)
	{

	  $query = $this->db->get_where($this->dbName, array('id' => $slug));
	  return $query->row_array();
	}

	public function set_resource($resource)
	{
		//插入数据库
        $result = $this->db->insert($this->dbName,$resource);
        
	}
	public function delete($files)
	{
		foreach ($files as $key => $value) {
			unlink($value);
		}
		
	}
	public function update_resource($r_id = FALSE,$params = FALSE)
	{
		if (!$r_id || !$params) {
			return null;
		}
		// $data = array_splice($_POST,4,1); 
		$this->db->where($this->primaryKey, $r_id);
		$this->db->update($this->dbName, $params);
	}
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
	public function setDBName($dbName=FALSE)
	{
		if ($dbName) {
			$this->dbName = $dbName;
		}
	}

	

}