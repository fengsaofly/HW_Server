<?php

class Resource_model extends CI_Model{
	var $dbName = 'Resource';
	// var $current_id = 0;
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
	public function get_next_id()
	{
	   // $query = $this->db->query("select MAX(resource_id) as maxid from ".$this->dbName);
	   $this->db->select_max('resource_id', 'maxid');
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