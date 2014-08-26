<?php
require APPPATH.'/libraries/storage.php';
/**
*  
*/
class User extends CI_Controller
{
	var $ak = '7244d82a2ef54bfa015a0d7d6f85f372';
	
	 // var $perpage = 5;
	var $domain = 'user';
    var $cur_picname = '';
    var $db_name = 'User';
    var $final_img_url = '';
    var $mustPostNums = 1;
    var $resizeConfig = array();
    var $resourceDB = 'U_Resource';
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        // $this->load->library('upload');
       
        $this->load->model('user_model');
        $this->load->model('resource_model');
        $this->load->library('Mailer');
        $this->resource_model->setDBName($this->resourceDB);
        // $this->load->controller('storage');
	}

	public function index($page = 1) {


      

        // $to = "350043263@qq.com";
        // $subject = "Test mail";
        // $message = "Hello! This is a simple email message. your HandWrite account code is : 123456";
        // $from = "123456@qq.com";
        // $headers = "From: $from";
        // mail($to,$subject,$message,$headers);
        // echo "Mail Sent.";

     
  
	}

    //添加破题信息
	function add() {
        $status = 0;
        $success = true;
        $message = "add success";
        $data = array();
        $error= array();
        $newIDs = array();

		if($_POST==null)
        {
            $message = "no request parameters!";
			$status = 1;
			$success = false;
        }
        else
        {

            $postNums=0;//必须post参数个数
            // $resIndex=0;
            foreach($_POST as $index => $value) {
    		    if ($index=='user_name') {
                    $data['user_name']= $value;
                    $postNums++;
                }

    			else{
    				$message = "some parameters are not expected !";
    				$status = 2;
    				$success = false;
    				break;
    			}
    		}
            // //上传参数正确
            if($status==0){


                if ($postNums!=$this->mustPostNums) {
                    $status = 3;
                    $message = 'parameter number is not enough';
                }
                elseif ($_FILES==null) {
                    $status = 4;
                    $message = 'not post avatar';
                }
                //允许上传
                else {
                    $newIDs = array();
                    //上传包含文件
                    if ($_FILES!=null) {
                        # code...
                      
                        //载入图片上传配置信息
                        $config['upload_path'] = STORAGE_BASE_URL;
                        $config['allowed_types'] = 'gif|jpg|png|jpeg|amr';
                        $config['max_size'] = '5000';
                        $config['max_width'] = '5000';
                        $config['max_height'] = '4000';
                        
                        $this->load->library('upload', $config);


                        //获取新增用户id
                        $idInfo = $this->user_model->getID($data['user_name']);
                        $user_id = $idInfo['id'];
                        $isNew = $idInfo['isNew'] == 'true' ? true : false;

                        //如果用户已经存在，则使用update方法
                        if (!$isNew) {
                            return $this->update($user_id);
                        }
                        $data['id'] = $user_id;
                        //上传资源
                        $resultArray = $this->upload->multiple('u_avatar',$user_id,'User');

                        //上传资源出错
                        if (!empty($resultArray['error'])) {
                            $status = 5;
                            $message = 'upload resource failed';
                        }
                        //上传成功,创建缩略图，保存到数据库
                        else{

                            //载入图片压缩配置信息
                            $this->initialImageResize();

                            // 获取已上传图片真实路径
                            $images = array('resource_lpath' => array(),'resource_spath' => array());
                            $errors = array();
                            // 保存资源信息到images，所有图片进行缩放处理
                            foreach ($resultArray['real_path'] as $key => $value) {

                                $pos = strripos($value,'.'); 
                                $fileType = substr($value,$pos+1);
                                //只处理图片
                                if ($error = $this->resizeIMG($value)) {
                                    array_push($errors, $error);
                                }

                                //保存缩略图 spath
                                array_push( $images['resource_spath'] , $this->getThumbPath($resultArray['final_path'][$key]) );       
                                //保存原始资源
                                array_push($images['resource_lpath'], $resultArray['final_path'][$key]);
        
                            }
                            //生成缩略图失败
                            if (count($errors) > 0) {
                                $status = 6;
                                $message = 'create thumb image failed';
                            }
                            else{
                                //将资源信息存入资源表
                                $resource_id = $this->resource_model->get_next_id();
                                for( $i = 0 ; $i < count($images['resource_spath']) ; $i++ ) {

                                    $resource  = array('resource_spath' => $images['resource_spath'][$i] ,
                                                             'resource_lpath' => $images['resource_lpath'][$i] ,
                                                                    'resource_id' => $resource_id);

                                    $this->resource_model->set_resource($resource);
                                }
                                //将问题信息存入user表
                                $data['u_avatar'] = $resource_id;
                                $newIDs['u_avatar'] = $resource_id;
                            }
                
                        }

                    }
                    //插入数据库
                    if ($status == 0) {
                        $data['created_time'] = time();
                        $data['updated_time'] = $data['created_time'];
                        $newIDs['created_time'] = $data['created_time'];
                        $newIDs['u_id'] = $this->user_model->set_user($data);
                        //插入user表出错
                        if ($newIDs['u_id'] <= 0 ) {
                            $status = 7;
                            $message = 'update a new user failed';
                        }
                    }
                     
                }
            }
        }
           

        $statusArray  = array('status' => $status,'message'=>$message,'newIDs'=> $newIDs);
        // echo json_encode($statusArray);
        echo base64_encode(json_encode($statusArray));

       	return true;
	}
    /*
    *更新用户的头像
    *
    *user_id 用户表的主键 - id
    *
    */
    public function update($user_id = FALSE)
    {
        $status = 0;
        $success = true;
        $message = "update success";
        $data = array();
        $error= array();
        $newIDs = array();


           
        if (!$user_id) 
        {
            $message = "not specify user_id!";
            $status = 1;

        }
        elseif ($_FILES == null) 
        {
            $message = "no post avatar!";
            $status = 3;
        }
        else
        {
            $postNums=0;//必须post参数个数

            if ($status == 0) {
                //保存用户名
                // $data['user_id'] = $user_id;
                // print_r($data);die();
                 //载入图片上传配置信息
                $config['upload_path'] = STORAGE_BASE_URL;
                $config['allowed_types'] = 'gif|jpg|png|jpeg|amr';
                $config['max_size'] = '5000';
                $config['max_width'] = '5000';
                $config['max_height'] = '4000';
                
                $this->load->library('upload', $config);


                //根据用户名获取用户id
                $resourceInfo = $this->user_model->get_resourceID($user_id);
                $u_avatar = $resourceInfo['u_avatar'] ;
                // print_r($resourceInfo);die();
                //去掉resource_id,方便删除spath，lpath
                unset($resourceInfo['u_avatar']);


                // print_r($u_avatar);die();
                $data['id'] = $user_id;
                //上传资源
                $resultArray = $this->upload->multiple('u_avatar',$user_id,'User');

                //上传资源出错
                if (!empty($resultArray['error'])) {
                    $status = 5;
                    $message = 'upload resource failed';
                }
                //上传成功,创建缩略图，保存到数据库
                else{

                    //载入图片压缩配置信息
                    $this->initialImageResize();

                    // 获取已上传图片真实路径
                    $images = array('resource_lpath' => array(),'resource_spath' => array());
                    $errors = array();
                    // 保存资源信息到images，所有图片进行缩放处理
                    foreach ($resultArray['real_path'] as $key => $value) {

                        $pos = strripos($value,'.'); 
                        $fileType = substr($value,$pos+1);
                        //只处理图片
                        if ($error = $this->resizeIMG($value)) {
                            array_push($errors, $error);
                        }

                        //保存缩略图 spath
                        array_push( $images['resource_spath'] , $this->getThumbPath($resultArray['final_path'][$key]) );       
                        //保存原始资源
                        array_push($images['resource_lpath'], $resultArray['final_path'][$key]);

                    }
                    //生成缩略图失败
                    if (count($errors) > 0) {
                        $status = 6;
                        $message = 'create thumb image failed';
                    }
                    else{
                        //将资源信息存入资源表
                     
                        for( $i = 0 ; $i < count($images['resource_spath']) ; $i++ ) {

                            $resource  = array('resource_spath' => $images['resource_spath'][$i] ,
                                                     'resource_lpath' => $images['resource_lpath'][$i]);

                            $this->resource_model->update_resource($u_avatar,$resource);
                        }
                        //将问题信息存入user表
                        // $data['u_avatar'] = $u_avatar;
                        $newIDs['u_avatar'] = $u_avatar;



                        //更新数据库
                        if ($status == 0) {
                            $data['updated_time'] = time();
                            $newIDs['updated_time'] = $data['updated_time'];
                            $newIDs['u_id'] = $user_id ;
                            // print_r($data);die();
                            // unset($data['user_id']);
                            $this->user_model->update_user($user_id,$data);
                            //插入user表出错
                            if ($newIDs['u_id'] <= 0 ) {
                                $status = 7;
                                $message = 'update  user avatar failed';
                            }
                            else
                            {

                     
                                //删除spath和lpath
                                foreach ($resourceInfo as $key => $value) {
                                
                                    $pos = stripos($value,'/',8);//从http://之后开始找‘/’
                                    $relatePath =  substr($value,$pos);
                                    $absPath = getcwd().$relatePath;
                                    //删除文件
                                    @unlink($absPath);
                                }
                                
                                
                            }
                        }
                    }
        
                }
            }

        }
        $statusArray  = array('status' => $status,'message'=>$message,'updateInfo'=> $newIDs);
        // echo json_encode($statusArray);
        echo base64_encode(json_encode($statusArray));

        return true;
        
    }
    /*
    *初始化缩略图设置
    *
    *
    *
    */
    private function initialImageResize()
    {
        $this->resizeConfig['image_library'] = 'gd2';
        $this->resizeConfig['source_image'] = './upload';
        $this->resizeConfig['create_thumb'] = TRUE;
        $this->resizeConfig['maintain_ratio'] = TRUE;
        $this->resizeConfig['width'] = 320;
        $this->resizeConfig['height'] = 320; 

        $this->load->library('image_lib', $this->resizeConfig); 
    }
    /**
    * @param image    图片url
    */
    public function resizeIMG($image)
    {
        $this->resizeConfig['source_image'] = $image; //文件路径带文件名
        $this->image_lib->initialize($this->resizeConfig);

        if (!$this->image_lib->resize()) {
            return  $this->image_lib->display_errors();
        }
        
    }
    public function getThumbPath($originPath)
    {
         //为文件增加后缀名
        $endPos = strripos($originPath,'.'); 
        $startPos = strripos($originPath,'/');
        $originFileName =  substr($originPath,$startPos+1,$endPos-$startPos);
        $originFileSuffix = substr($originPath,$endPos);
        $newFileExtend = '_thumb';

        $newPath = substr_replace($originPath,$newFileExtend.$originFileSuffix,$endPos);
 
        return $newPath;


    }
     //获取指定用户信息
    public function get($user_name){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $dataArray = array();
        $data = array();
        $start = null;
        if($_GET==null){
        
            $status = 1;
            $message = 'no get params';
            
        }
        else
        {
            $data = $_GET;
             foreach($_GET as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 2;
                        $success = false;

                        
                        break;
                    }

                    unset($data[$index]);//去除ak

                    
                }


            }

            $final_result_array = array();
            if ($status == 0) {
                $user_id = $this->user_model->getUserId($user_name);
        
                $contentArray = $this->user_model->get_user($user_id);

 
                if($contentArray == null){
                
                    $status = 0;
                    $message = 'you specified user has no avatar';
                }
                //有破题内容
                else{

                //    // $contentArray = $this->mergeSearchResult($contentArray);

                        // $contentArray = array_values($contentArray);//去除索引值，变为一般数组

                }
              
            }
            
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        // echo json_encode($result);
        echo base64_encode(json_encode($result));
    } 
    //获取好友或多个用户的头像
    function getInfos(){
    
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $dataArray = array();
        
        if($_POST==null){
         
            $status = 1;
            $message = 'no POST params';
            
        }
        else
        {
             foreach($_POST as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 1;
                        $success = false;
                        break;
                    }
                }
                elseif($index=='users'){
                    $include_friends = true;
                    //echo 'include_friends';
                    $dataArray = json_decode($value,true);
                    if($dataArray == null){
                        $message = "find_friends decode result is null";
                        $status = 3;
                        break;
                    }
                }
                else{
                    $message = "some parameters are not expected !";
                    $status = 2;
                    $success = false;
                    break;
                }
            }
            if($status==0)
            {
               
               if(!$include_friends){
                
                    $status = 4;
                    $message = 'POST has no friends info';
               }
               else{
                
                     $jsonArray = array();
                     foreach($dataArray as $index => $value) {
                         $jsonVal = json_decode($value,true);
                         $values = array_values($jsonVal);
                         array_push($jsonArray,@$values[0]);
 
                    }
                    $contentArray = $this->user_model->get_icons($jsonArray);
                    if($contentArray == null){
                
                        $status = 0;
                        $message = 'search result is null';
                    }
               }
                    
            }
           
          
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        // echo json_encode($result);
        echo base64_encode(json_encode($result));
    }
    //获取关于我的问题
    public function getMyQuestion(){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();
        $start = 0;
        $needGetNums = 1;

        if($_GET==null){
        
            $status = 1;
            $message = '未包含指定参数';
            
        }
        else
        {
            $getNums=0;//必须post参数个数
            foreach($_GET as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 1;
                        $success = false;

                        
                        break;
                    }

                    unset($data[$index]);//去除ak

                    
                }
                elseif ($index == 'user') {
                        $data['user'] = $value;
                        $getNums++;
                    }
                elseif ($index == 'start') {
                        $start = $value;
                         unset($data[$index]);//去除start
                    }

                elseif ($index == 'floorTime') {
         
                        $data['created_time >'] = $value;
                        unset($data[$index]);//去除start0
                   
                    }
                elseif ($index == 'topTime') {
     
                    $data['created_time <'] = $value;
                    unset($data[$index]);//去除start
                     
                }
                else
                {
                    $message = "some params are not needed!";
                    $status = 2;
                }

            }
          	// $final_result_array = array();
           //  print_r($data);
                // die();
            if ($status == 0 ) {

                if ($getNums < $needGetNums) {
                    $message = "some params are not needed!";
                    $status = 2;
                }

                else{

                    $contentArray = $this->user_model->get_question($start,$data);
                    // $contentArray = $this->user_model->get_user($start);//参数0--表示start从0开始算，返回limit个结果
                    // $ids = $this->user_model->getIDs(0);
                    // print_r($contentArray);
                    // die();
                    
                    if($contentArray == null){
                    
                        $status = 2;
                        $message = 'no users you can get';
                    }
                    //有破题内容
                    else{
                        //合并搜索结果
                        // $contentArray = $this->mergeSearchResult($contentArray);
                        $contentArray = array_values($contentArray);
                    }
                }

              
            }
        	
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  	// echo json_encode($result);
        echo base64_encode(json_encode($result)); 
        
    }
    /*获取关于我的校园故事*/
    public function getMyStory(){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();
        $start = 0;
        $needGetNums = 1;

        if($_GET==null){
        
            $status = 1;
            $message = '未包含指定参数';
            
        }
        else
        {
            $getNums=0;//必须post参数个数
            foreach($_GET as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 1;
                        $success = false;

                        
                        break;
                    }

                    unset($data[$index]);//去除ak

                    
                }
                elseif ($index == 'user') {
                        $data['user'] = $value;
                        $getNums++;
                    }
                elseif ($index == 'start') {
                        $start = $value;
                         unset($data[$index]);//去除start
                    }

                elseif ($index == 'floorTime') {
         
                        $data['created_time >'] = $value;
                        unset($data[$index]);//去除start0
                   
                    }
                elseif ($index == 'topTime') {
     
                    $data['created_time <'] = $value;
                    unset($data[$index]);//去除start
                     
                }
                else
                {
                    $message = "some params are not needed!";
                    $status = 2;
                }

            }
            // $final_result_array = array();
           //  print_r($data);
                // die();
            if ($status == 0 ) {

                if ($getNums < $needGetNums) {
                    $message = "some params are not needed!";
                    $status = 2;
                }

                else{

                    $contentArray = $this->user_model->get_story($start,$data);
                    // $contentArray = $this->user_model->get_user($start);//参数0--表示start从0开始算，返回limit个结果
                    // $ids = $this->user_model->getIDs(0);
                    // print_r($contentArray);
                    // die();
                    
                    if($contentArray == null){
                    
                        $status = 2;
                        $message = 'no users you can get';
                    }
                    //有破题内容
                    else{
                        //合并搜索结果
                        // $contentArray = $this->mergeSearchResult($contentArray);
                        $contentArray = array_values($contentArray);
                    }
                }

              
            }
            
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        // echo json_encode($result);
        echo base64_encode(json_encode($result)); 
        
    }
    function getRecentQInfo($user_name = FALSE)
    {
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();
        $start = 0;
        $needGetNums = 1;

        if($_GET==null){
        
            $status = 1;
            $message = '未包含指定参数';
            
        }
        elseif (!$user_name) {
            $status = 7;
            $message = 'no specify user';
        }
        else
        {
            $data['user'] = $user_name;

            print_r($_GET);
            $getNums=0;//必须post参数个数
            foreach($_GET as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 1;
                        $success = false;

                        
                        break;
                    }


                    
                }
                elseif ($index == 'time') {
                    $data['time'] = $value;
                    $getNums++;
                }

                else
                {
    
                    $message = "some params are not needed!";
                    $status = 2;
                }

            }
            // $final_result_array = array();
           //  print_r($data);
                // die();
            if ($status == 0 ) {

                if ($getNums < $needGetNums) {
                    $message = "some params are not needed!";
                    $status = 2;
                }

                else{

                    $contentArray = $this->user_model->getRecentQInfo($data);
                    // $contentArray = $this->user_model->get_user($start);//参数0--表示start从0开始算，返回limit个结果
                    // $ids = $this->user_model->getIDs(0);
                    // print_r($contentArray);
                    // die();
                    
                    if($contentArray == null){
                    
                        $status = 2;
                        $message = 'no infos you can get';
                    }
                    //有破题内容
                    else{
                        //合并搜索结果
                        // $contentArray = $this->mergeSearchResult($contentArray);
                        $contentArray = array_values($contentArray);
                    }
                }

              
            }
            
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        echo json_encode($result);
        // echo base64_encode(json_encode($result)); 
        
    }
    function mergeSearchResult($contentArray)
    {
        
        $id_index = 0;
        foreach ($contentArray as $row)
        {
            //有资源的问题
            // if (isset($row['resource_spath']) && isset($row['resource_spath'])) {
             // print_r($row['id']); 
               if ($id_index != $row['id']) {
                    $id_index = $row['id'];
                   # code...
                    $final_result_array[$id_index] = $row;
                    $final_result_array[$id_index]['resource_spath']  = array();
                    $final_result_array[$id_index]['resource_lpath']  = array();
                 

               }    
               if ($row['resource_spath'] != null) {
                    array_push($final_result_array[$id_index]['resource_spath'],$row['resource_spath'] );
                    array_push($final_result_array[$id_index]['resource_lpath'],$row['resource_lpath'] );
                }
            // }

        }
        return $final_result_array;
    }
    function deleteFromArray(&$array, $deleteIt, $useOldKeys = FALSE)
    {
        $key = array_search($deleteIt,$array,TRUE);
        if($key === FALSE) return FALSE;

        unset($array[$key]);
        if(!$useOldKeys)  $array = array_values($array);

        return TRUE;
    }
    public function retrievePWD($user_name)
    {
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $data = array();

        $email = $this->user_model->get_email($user_name);
        if($email == null){
            $status = 1;
            $message = 'specified user`s email is null';
        }
        else
        {
            foreach($_GET as $index => $value) {
                if ($index=='ak') {
                    # code...
                    if ( $this->ak!=$value) {
                        # code...
                        $message = "ak is error!";
                        $status = 1;
                        $success = false;

                        
                        break;
                    }

                }

                else
                {

                    $message = "some params are not needed!";
                    $status = 2;
                }

            }
            if ($status != 0) {
                $message = "something wrong";
                $status = 3;
            }
            else
            {
                print_r($email);
                $time = date("Y-m-d H:i:s"); 

            
                $mail_body =   '<div style="padding:72px 100px">
                    <div style="height:71px;background:#51a0e3;"><img src="../../images/cq_logo.png" title="破题高手安全设置提醒"></div>
                    <div style="padding:37px 0 81px 0;border:1px solid #e7e7e7;font-size:14px;color:#6e6e6e;background:#FFF;">
                        <div style="padding:0 0 10px 41px;font-weight:bold;color:#6e6e6e;">亲爱的破题高手用户：</div>
                        <div style="padding:0 0 26px 41px;color:#6e6e6e;">您的帐号 <span style="color:#efa229;font-weight:bold;"><a style="color:#efa229;text-decoration:none;cursor:text;">'.$user_name.'</a></span> 请求找回密码，操作已成功！</div>
      
                        <div style="padding:0 0 26px 41px;">您的密码已被重置为:<span style="color:#efa229;font-weight:bold;"><a style="color:#333333;text-decoration:none;cursor:text;"> 123456</a></span>                             
                        </div>
                        <div style="padding:0 0 26px 41px;color:#6e6e6e;">
                            <div style="padding-bottom:8px;">破题高手</div>
                            <div><span style="border-bottom:1px dashed #ccc;" t="5" times="">'.$time.'</span></div>
                        </div>
                        <div style="padding:0 0 26px 41px;color:#6e6e6e;font-size:12px;">
                            本电子邮件地址不能接受回复邮件。有关详情，请访问 <a href="http://help.163.com/special/sp/urs_index.html" target="_blank" style="color:#3058a8;">破题高手帮助中心</a>。
                        </div>
                    </div>
                    <div style="width:700px;height:129px;padding-top:20px;overflow:hidden;">
                        <a href="http://reg.163.com/yixin/caipiaoact.do#from=ursgnzyyc" target="_blank" style="border:none;"><img src="http://reg.163.com/images/secmail/adv.png" title="关注通行证公众号，帐号安全实时提醒。现在还有3元红包免费领！" style="border: none; display: none !important; visibility: hidden !important; opacity: 0 !important; background-position: 0px 0px;" width="0" height="0"></a>
                    </div>
                    <div style="padding-top:24px;text-align:right;color:#999;"><span style="border-bottom:1px dashed #ccc;" t="5" times="">'.$time.'</span>(本邮件由系统自动发出，请勿回复)</div>
                </div>';

                $contentArray = $this->mailer->sendmail(
                                        $email,
                                        '肖逸飞',
                                        '密码找回 '.$time,
                                        $mail_body
                                    );

                if ($contentArray == null) {
                    $status = 5;
                    $message = 'send mail failed';
                }
            }
           
        }

      
   
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        echo json_encode($result);
        // echo base64_encode(json_encode($result)); 
    }

}


?>
