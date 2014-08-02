<?php
// require APPPATH.'/libraries/HttpClient.class.php';
/**
*  
*/
class Question extends CI_Controller
{
	var $ak = '7244d82a2ef54bfa015a0d7d6f85f372';
	
    

	 // var $perpage = 5;
	var $domain = 'user';
    var $base_dir = 'question';
    var $cur_picname = '';
    var $db_name = 'question';
    var $final_img_url = '';
    var $mustPostNums = 5;
    var $resourceDB = 'Q_Resource';
    var $resizeConfig = array();
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        // $this->load->library('upload');
       
        $this->load->model('question_model');
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
    		    if ($index=='q_title') {
                    $data['q_title']= $value;
                    $postNums++;
                }
                elseif ($index=='q_text_content') {
    				# code...
    				$data['q_text_content'] = $value;
                    $postNums++;
    			}
                elseif($index=='q_grade'){
                    $data['q_grade'] = $value;
                    $postNums++;
                }
                elseif($index=='q_subject'){
                    $data['q_subject'] = $value;
                    $postNums++;
                }
                elseif($index=='q_user'){
                    $data['q_user'] = $value;
                    $postNums++;
                }
    			else{
    				$message = "some parameters are not expected !";
    				$status = 2;
    				$success = false;
    				break;
    			}
    		}
            //上传有错
            if($status!=0){
            
                $statusArray  = array('status' => $status,'message'=>$message );
                // echo json_encode($statusArray);
                // return false;
            }
      		elseif ($postNums!=$this->mustPostNums) {
                $status = 3;
                $message = 'parameter number is not enough';
            }
            //允许上传
            else {
                
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
                   
                    $user_id = $data['q_user'] ;
                    //上传资源
                    $resultArray = $this->upload->multiple('q_resources',$user_id);


                    //上传资源出错
                    if (!empty($resultArray['error'])) {
                        $status = 3;
                        $message = 'upload resource failed';
                    }
                    //上传成功,创建缩略图，保存到数据库
                    else{

                        //载入图片压缩配置信息
                        $this->initialImageResize();

                        // print_r($resultArray);
                        // 获取已上传图片真实路径
                        $images = array('resource_lpath' => array(),'resource_spath' => array());
                        $errors = array();
                        // 保存资源信息到images，所有图片进行缩放处理
                        foreach ($resultArray['real_path'] as $key => $value) {

                            //获取文件类型：image or audio

                            // print_r($value);
                            $pos = strripos($value,'.'); 
                            $fileType = substr($value,$pos+1);
                            //只处理图片
                            if ($fileType != 'amr') {
                                if ($error = $this->resizeIMG($value)) {
                                    array_push($errors, $error);
                                }
                                // print_r($error.'<br>');
                                // print_r($value.'<br>');
                                //保存缩略图 spath
                                array_push( $images['resource_spath'] , $this->getThumbPath($resultArray['final_path'][$key]) );       
                            }
                            //如果是音频，不保存spath
                            else
                                array_push( $images['resource_spath'] , '' ); 
                            //保存原始资源
                            array_push($images['resource_lpath'], $resultArray['final_path'][$key]);
    
                        }
                        //生成缩略图失败
                        if (count($errors) > 0) {
                            $status = 5;
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
                            //将问题信息存入question表
                            $data['q_resource'] = $resource_id;
                            $newIDs['resource_id'] = $resource_id;
                        }
            
                    }

                }
                //插入数据库
                if ($status == 0) {
                    $data['created_time'] = time();
                    $newIDs['created_time'] = $data['created_time'];
                    $newIDs['q_id'] = $this->question_model->set_question($data);
                    //插入question表出错
                    if ($newIDs['q_id'] <= 0 ) {
                        $status = 4;
                        $message = 'insert a new question failed';
                    }
                }
                 
            }
        }
        
        //print_r($_FILES);
           

        $statusArray  = array('status' => $status,'message'=>$message,'newIDs'=> $newIDs);
        // echo json_encode($statusArray);
        echo base64_encode(json_encode($statusArray));
        // echo "<br>";
        // echo base64_decode(base64_encode(json_encode($statusArray)));
        // //  print_r($_FILES);
        //echo '<br>'.$result;
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
        $this->resizeConfig['width'] = 480;
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
 //                elseif($index=='question_friends'){
 //                	$dataArray = json_decode($value,true);
 //                }
 //                else{
 //                    $message = "some parameters are not expected !";
 //                    $status = 2;
 //                    $success = false;
 //                    break;
	// 			}
 //            }
            
 //        	$contentArray = $this->question_model->get_question_from_friends($dataArray);
        
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
                $ids = $this->question_model->getIDs($start,$data);
                // print_r($ids);
                // die();
                $contentArray = $this->question_model->get_question($start,$data);

                // print_r($contentArray);
                // die();
 
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no questions you can get';
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
                        
                          $rowReplySum =  $this->question_model->getReplySum($row['id']);
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
        // echo json_encode($result);
        echo base64_encode(json_encode($result));
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
                $contentArray = $this->question_model->get_question($start);//参数0--表示start从0开始算，返回limit个结果
                $ids = $this->question_model->getIDs(0);
                print_r($contentArray);
                die();
                
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no questions you can get';
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


}


?>
