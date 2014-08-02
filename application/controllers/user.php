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

        $this->resource_model->setDBName($this->resourceDB);
        // $this->load->controller('storage');
	}

	public function index($page = 1) {


      

        $to = "350043263@qq.com";
        $subject = "Test mail";
        $message = "Hello! This is a simple email message. your HandWrite account code is : 123456";
        $from = "123456@qq.com";
        $headers = "From: $from";
        mail($to,$subject,$message,$headers);
        echo "Mail Sent.";

     
  
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
                        $user_id = $this->user_model->get_next_id();
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
        echo json_encode($statusArray);
        // echo base64_encode(json_encode($statusArray));

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
        $message = "add success";
        $data = array();
        $error= array();
        $newIDs = array();


           
        if (!$user_id) 
        {
            $message = "not specify user_id!";
            $status = 1;

        }
        elseif ($_POST != null) 
        {
            $message = "not need post parameters!";
            $status = 2;
            
        }
        elseif ($_FILES == null) 
        {
            $message = "no post avatar!";
            $status = 3;
        }
        else
        {
            $postNums=0;//必须post参数个数
            // $resIndex=0;
            // foreach($_POST as $index => $value) {
                
            //     // if ($index=='ak') {
            //     //     # code...
            //     //     if ( $this->ak!=$value) {
            //     //         # code...
            //     //         $message = "ak is error!";
            //     //         $status = 1;
            //     //         $success = false;

                        
            //     //         break;
            //     //     }

            //     //     unset($data[$index]);//去除ak

                    
            //     // }
            //     else{
            //         $message = "some parameters are not expected !";
            //         $status = 4;
            //         $success = false;
            //         break;
            //     }
            // }
            if ($status == 0) {
                //保存用户名
                $data['user_id'] = $user_id;
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
                                    unlink($absPath);
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
 //    //获取指定用户信息
 //    public function view($user_identifier=false){
 //        $status = 0;
 //        $message = 'access is successful!';
 //        $contentArray = null;
 //        $dataArray = array();
 //        if($user_identifier==false){
        
            
 //            $status = 1;
 //            $message = '未指定用户id';
            
            
 //        }
 //        else
 //        {
 //            foreach($_POST as $index => $value) {
 //                if ($index=='ak') {
 //                    # code...
 //                    if ( $this->ak!=$value) {
 //                        # code...
 //                        $message = "ak is error!";
 //                        $status = 1;
 //                        $success = false;
 //                        break;
 //                    }
 //                }
 //                elseif($index=='user_friends'){
 //                	$dataArray = json_decode($value,true);
 //                }
 //                else{
 //                    $message = "some parameters are not expected !";
 //                    $status = 2;
 //                    $success = false;
 //                    break;
	// 			}
 //            }
            
 //        	$contentArray = $this->user_model->get_user_from_friends($dataArray);
        
 //            if($contentArray == null){
            
 //                $status = 2;
 //                $message = '指定的用户不存在';
 //            }
            
 //        }
 //        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	//   	echo json_encode($result);
       
        
 //    }
    public function get(){
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
                        $status = 1;
                        $success = false;

                        
                        break;
                    }

                    unset($data[$index]);//去除ak

                    
                }
                elseif ($index == 'start') {
                        $start = $value;
                         unset($data[$index]);//去除start
                    }

                elseif ($index == 'floorTime') {
         
                        $data['created_time >'] = $value;
                        unset($data[$index]);//去除start
                         
                    }
                elseif ($index == 'topTime') {
     
                    $data['created_time <'] = $value;
                    unset($data[$index]);//去除start
                     
                }

            }

            // print_r($data);die();
            $final_result_array = array();
            if ($status == 0) {
                $ids = $this->user_model->getIDs($start,$data);
                // print_r($ids);
                // die();
                $contentArray = $this->user_model->get_user($start,$data);

                // print_r($contentArray);
                // die();
 
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no users you can get';
                }
                //有破题内容
                else{
                   $contentArray = $this->mergeSearchResult($contentArray);
                    // print_r($final_result_array);
                    // print_r($contentArray);
                    // die();
                   $contentArray = array_values($contentArray);//去除索引值，变为一般数组

                    // print_r($contentArray);
                    // die();
                   foreach($contentArray as $index => $row) {
                        
                          $rowReplySum =  $this->user_model->getReplySum($row['id']);
                          $contentArray[$index]['replySum'] = $rowReplySum;

                          // echo "replySum: ".$rowReplySum;
                          // echo "<br>";
                        
                    }
                    // print_r($contentArray);
                    // die();
                    // $contentArray  = $final_result_array;
                }
              
            }
            
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        echo json_encode($result);
        // echo base64_encode(json_encode($result));
    } 
      //获取所有用户信息
    public function getAll(){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $dataArray = array();
        
        if($_GET==null){
        
            $status = 1;
            $message = '未包含指定参数';
            
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
                elseif ($index=='start') {
                    # code...
                    $start = $value;
                    // $postNums++;
                }
                else{
                    $message = "some parameters are not expected !";
                    $status = 2;
                    $success = false;
                    break;
                }
            }
          	$final_result_array = array();
            if ($status == 0 && $start != null) {
                $contentArray = $this->user_model->get_user($start);//参数0--表示start从0开始算，返回limit个结果
                $ids = $this->user_model->getIDs(0);
                print_r($contentArray);
                die();
                
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no users you can get';
                }
                //有破题内容
                else{
                    //合并搜索结果
                    $contentArray = $this->mergeSearchResult($contentArray);
                    $contentArray = array_values($contentArray);
                }
              
            }
        	
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  	// echo json_encode($result);
        echo base64_encode(json_encode($result)); 
        
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


}


?>
