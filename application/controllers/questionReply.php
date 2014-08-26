<?php
// require APPPATH.'/libraries/HttpClient.class.php';
/**
*  
*/
class QuestionReply extends CI_Controller
{
	var $ak = '7244d82a2ef54bfa015a0d7d6f85f372';
	
    

	 // var $perpage = 5;
	var $domain = 'user';
    var $base_dir = 'QuestionReply';
    var $cur_picname = '';
    var $db_name = 'QuestionReply';
    var $final_img_url = '';
    var $mustPostNums = 2;
    var $resourceDB = 'QR_Resource';
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        // $this->load->library('upload');
       
        $this->load->model('questionreply_model');
        $this->load->model('resource_model');
        $this->load->model('user_model');
        $this->resource_model->setDBName($this->resourceDB);
        // $this->load->controller('storage');
	}

	public function index($page = 1) {


	}
    //添加破题信息
	function add($q_id) {
        $status = 0;
        // $success = true;
        $message = "add success";
        $data = array();
        $newIDs = array();
        if (!$q_id) {
            $status = 1;
            $message = "not specify q_id ";

            $statusArray  = array('status' => $status,'message'=>$message);
 
        }
        else
        {


            //保存q_id
            $data['qr_q'] = $q_id;
    		if(!$_POST)
            {
                $message = "no request parameters!";
    			$status = 2;
            }
            else
            {

                $postNums=0;//必须post参数个数
                // $resIndex=0;
                //获取post参数
                foreach($_POST as $index => $value) {
                    if ($index=='qr_text') {
        				# code...
        				$data['qr_text'] = $value;
                        $postNums++;
        			}
                    elseif($index=='qr_type'){
                        $data['qr_type'] = $value;
                        $postNums++;
                    }
                    elseif($index=='qr_user'){
                        $data['qr_user'] = $value;
                        $postNums++;
                    }
                    elseif($index=='lon'){
                        $data['lon'] = $value;
                        $postNums++;
                    }
                    elseif($index=='lat'){
                        $data['lat'] = $value;
                        $postNums++;
                    }
        			else{
        				$message = "some parameters are not expected !";
        				$status = 3;
        				break;
        			}
        		}
                //上传有错
                if($status==0){
          
      
                  
              		if ($postNums < $this->mustPostNums) {
                        $status = 4;
                        $message = 'parameter number is not enough';

                    }
                    else
                    {


                        $data['qr_resource'] = 0;
                        //允许上传
                        //上传包含的文件
                        if ($_FILES!=null) {
                            //载入配置信息
                            $config['upload_path'] = STORAGE_BASE_URL;
                            $config['allowed_types'] = 'gif|jpg|png|jpeg|amr';
                            $config['max_size'] = '5000';
                            $config['max_width'] = '5000';
                            $config['max_height'] = '4000';
                            
                            $this->load->library('upload', $config);
                           
                            $user_id = $data['qr_user'] ;

                            // print_r($_FILES);die();
                            //上传资源
                            $resultArray = $this->upload->multiple('qr_resources',$user_id,'questionreply');
                            

                            //上传资源出错
                            if (!empty($resultArray['error'])) {
                                $status = 3;
                                $message = 'upload resource failed';
                            }
                            //上传成功,创建缩略图，保存到数据库
                            else{
                                 //载入图片压缩配置信息
                                $this->initialImageResize();
                                // print_r($resultArray);die();
                                // 获取已上传图片真实路径
                                $images = array('resource_lpath' => array(),'resource_spath' => array());
                                
                                // 保存资源信息到images，所有图片进行缩放处理
                                // print_r($_FILES);
                                // print_r($resultArray);die();
                                foreach ($resultArray['real_path'] as $key => $value) {

                                    //获取文件类型：image or audio
                                    $pos = strripos($value,'.'); 
                                    $fileType = substr($value,$pos+1);
                                    //只处理图片
                                    if ($fileType != 'amr') {
                                        if ($error = $this->resizeIMG($value)) {
                                            array_push($errors, $error);
                                        }
                                
                                        //保存缩略图 spath
                                        array_push( $images['resource_spath'] , $this->getThumbPath($resultArray['final_path'][$key]) );
                                        
                                    }
                                    //如果是音频，不保存spath
                                    else
                                        array_push( $images['resource_spath'] , '' );

                                    //保存原始资源
                                    array_push($images['resource_lpath'], $resultArray['final_path'][$key]);
                              

                                    
                                }

                                // print_r($images);die();
                                //将资源信息存入资源表
                                $resource_id = $this->resource_model->get_next_id();

                                // print_r($resource_id);die();
                                for( $i = 0 ; $i < count($images['resource_spath']) ; $i++ ) {

                                    $resource  = array('resource_spath' => $images['resource_spath'][$i] ,
                                                             'resource_lpath' => $images['resource_lpath'][$i] ,
                                                                    'resource_id' => $resource_id);

                                    // print_r($resource);die();
                                    $this->resource_model->set_resource($resource);
                                }
                                //将问题信息存入QuestionReply表
                                $data['qr_resource'] = $resource_id;
                                $newIDs['resource_id'] = $resource_id;
                            }
                         }        

                        $data['created_time'] = time();


                        $newIDs['qr_id'] = $this->questionreply_model->set_questionReply($data);
                        $newIDs['created_time'] = $data['created_time'];
                        // print_r($data);
                        // die();
                        //插入QuestionReply表出错
                        if ($newIDs['qr_id'] <= 0 ) {
                            $status = 4;
                            $message = 'insert a new QuestionReply failed';
                        }
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

    public function get($qr_q=false){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();
        $start = 0;
        if (!$qr_q) {
            $status = 1;
            $message = 'no specify qid';
        }
        else{
            $data['qr_q'] = $qr_q;
            if($_GET==null){
            
                $status = 1;
                $message = 'no get params';
                
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
                    elseif ($index == 'start') {
                            $start = $value;
                             unset($data[$index]);//去除start
                        }

                    elseif ($index == 'floorTime') {
             
                            $data['created_time >'] = $value;
                            // unset($data[$index]);//去除start
                             
                        }
                    elseif ($index == 'topTime') {
         
                        $data['created_time <'] = $value;
                        // unset($data[$index]);//去除start
                         
                    }
                    elseif ($index == 'qr_type') {
         
                        $data['qr_type'] = $value;
                        // unset($data[$index]);//去除start
                         
                    }


                }
                // print_r($data);die();
                if ($status == 0) {
                    $contentArray = $this->questionreply_model->get_questionReply($start,$data);
                    // print_r($contentArray);
                    // die();
                    $final_result_array = array();
                    if($contentArray == null){
                    
                        $status = 2;
                        $message = 'no QuestionReplys you can get';
                    }
                    //有破题内容
                    else{

                        $contentArray = $this->mergeSearchResult($contentArray);
                        $contentArray = array_values($contentArray);
                        foreach ($contentArray as $index => $row) {
                            //添加用户头像
                            $avatar = $this->user_model->get_thumbIcon($row['qr_user']);
                            $contentArray[$index]['avatar_thumb_path'] = $avatar;
                        }
                        
                        // die();
                        // $contentArray  = $final_result_array;
                    }
                  
                }
                
                
            }
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
        // echo json_encode($result);
        echo base64_encode(json_encode($result));
    } 
     /*更新操作 更新问题状态：增加点赞数*/
    public function update($id=FALSE)
    {
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();

        if (!$id) {
            $status = 1;
            $message = 'no specify id';
        }

        else
        {
            $exist = $this->questionreply_model->getIDs(0, array('id' => $id ));

            if ($exist != null && count($exist) > 0 ) {
                // foreach($_POST as $index => $value) {
                //     if ($index=='q_state') {
                //         # code...
                //         $data['q_state'] = $value;
                //             // $postNums++;
                //         }
                //     else{
                //         $message = "some parameters are not expected !";
                //         $status = 2;
                //         $success = false;
                //         break;
                //     }
                // }
                $data = $_POST;

                if ($status == 0) {
                    $contentArray = $this->questionreply_model->update_questionReply($id);//参数0--表示start从0开始算，返回limit个结果
                    
                    if($contentArray != 1){
                    
                        $status = 2;
                        $message = 'can not update database' ;
                    }

                  
                }
            }
            else
            {
                $status = 6;
                $message = 'id is not exist';
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
            }
          	
            if ($status == 0) {
                $contentArray = $this->questionreply_model->get_questionReply(0);
                // print_r($contentArray);
                // die();
                $final_result_array = array();
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no QuestionReplys you can get';
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

                if($id_index != $row['id']) {
                    $id_index = $row['id'];

                    $final_result_array[$id_index] = $row;
                    $final_result_array[$id_index]['resource_spath']  = array();
                    $final_result_array[$id_index]['resource_lpath']  = array();
                 
                    
               }    
                if($row['resource_spath'] != null) {
                    array_push($final_result_array[$id_index]['resource_spath'],$row['resource_spath'] );
                }
                if ($row['resource_lpath'] != null) {
                    array_push($final_result_array[$id_index]['resource_lpath'],$row['resource_lpath'] );
                }
            // }

        }
        return $final_result_array;
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

}


?>
