<?php
// require APPPATH.'/libraries/HttpClient.class.php';
/**
*  
*/
class LearnEnglishReply extends CI_Controller
{
	var $ak = '7244d82a2ef54bfa015a0d7d6f85f372';
	
    
    var $base_dir = 'learnEnglishReply';
    var $cur_picname = '';
    var $db_name = 'LearnEnglishReply';
    var $final_img_url = '';
    var $mustPostNums = 1;
    var $resourceDB = 'LR_Resource';
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        // $this->load->library('upload');
       
        $this->load->model('learnenglishreply_model');
        $this->load->model('resource_model');
        // $this->load->controller('storage');
        $this->load->model('user_model');
        $this->resource_model->setDBName($this->resourceDB);
	}

	public function index($page = 1) {


	}
    //添加破题信息
	function add($lr_id = FALSE) {
        $status = 0;
        // $success = true;
        $message = "add success";
        $data = array();
        $newIDs = array();

        //没有指定回复对象
        if (!$lr_id) {
            $status = 1;
            $message = "not specify id ";

        }
        else
        {
            //保存回复对象
            $data['lr_l'] = $lr_id;
            //没有传递参数
    		if(!$_POST)
            {
                $message = "no request parameters!";
    			$status = 2;
     
            }
            else
            {
                //必须post参数个数
                $postNums=0;
                // print_r($_POST);
                //获取post参数
                foreach($_POST as $index => $value) {

            
                    if ($index=='lr_text') {
                        # code...
                        $data['lr_text'] = $value;
                        $postNums++;
                    }
                    elseif($index=='lr_user'){
                        $data['lr_user'] = $value;
                        $postNums++;
                    }
                    // elseif($index=='lr_type'){
                    //     $data['lr_type'] = $value;
                    //     $postNums++;
                    // }
                    else{
                        $message = "some parameters are not expected !";
                        $status = 3;
                        $success = false;
                        break;
                    }
        		}
                //上传没错
                if($status==0){
                    //上传参数太少
                    if ($postNums < $this->mustPostNums) {
                        $status = 4;
                        $message = 'parameter number is not enough';

                    }
                    elseif ($_FILES == null) {
                        $status = 5;
                        $message = 'not post record file';
                    }
                    else
                    {
             
                            
                        //载入配置信息
                        $config['upload_path'] = STORAGE_BASE_URL;
                        $config['allowed_types'] = 'gif|jpg|png|jpeg|amr';
                        $config['max_size'] = '5000';
                        $config['max_width'] = '5000';
                        $config['max_height'] = '4000';
                        
                        $this->load->library('upload', $config);
                       
                        $user_id = $data['lr_user'] ;

                        // print_r($_FILES);die();
                        //上传资源
                        $resultArray = $this->upload->multiple('lr_resources',$user_id,'storyReply');
                        

                        //上传资源出错
                        if (!empty($resultArray['error'])) {
                            $status = 7;
                            $message = 'upload resource failed';
                        }
                        //上传成功,创建缩略图，保存到数据库
                        else{
                             //载入图片压缩配置信息
                            $this->initialImageResize();
                            // print_r($resultArray);die();
                            // 获取已上传图片真实路径
                            $images = array('resource_lpath' => array(),'resource_spath' => array());
                            

                            foreach ($resultArray['real_path'] as $key => $value) {

                                array_push( $images['resource_spath'] , '' );
                                //保存原始资源
                                array_push($images['resource_lpath'], $resultArray['final_path'][$key]);

                            }

                            // print_r($images);die();
                            //将资源信息存入资源表
                            $resource_id = $this->resource_model->get_next_id();
                            for( $i = 0 ; $i < count($images['resource_spath']) ; $i++ ) {

                                $resource  = array('resource_spath' => $images['resource_spath'][$i] ,
                                                         'resource_lpath' => $images['resource_lpath'][$i] ,
                                                                'resource_id' => $resource_id);

                                $this->resource_model->set_resource($resource);
                            }
                            //将问题信息存入storyReply表
                            $data['lr_resource'] = $resource_id;
                            $newIDs['resource_id'] = $resource_id;
                            $data['created_time'] = time();
                            $newIDs['lr_id'] = $this->learnenglishreply_model->set_learnEnglishReply($data);
                            $newIDs['created_time'] = $data['created_time'];
                            // print_r($newIDs);
                            // die();
                            //插入QuestionReply表出错
                            if ($newIDs['lr_id'] <= 0 ) {
                                $status = 8;
                                $message = 'insert a new QuestionReply failed';
                            }
                        }
                    
                  
                    }
               
                }
            }
        }
        // $newIDs['created_time'] = $data['created_time'];
        
        $statusArray  = array('status' => $status,'message'=>$message,'newIDs'=> $newIDs);
        // echo json_encode($statusArray);
        echo base64_encode(json_encode($statusArray));

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

    public function get($lr_l=false){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $data = array();
        $start = null;
        //未指定id
        if (!$lr_l) {
            $status = 1;
            $message = 'no specify id';
        }
        else{

            $data['lr_l'] = $lr_l;

            //get参数为空
            if($_GET==null){
            
                $status = 2;
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
                            $status = 3;
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
                            unset($data[$index]);//去除start
                             
                        }
                    elseif ($index == 'topTime') {
         
                        $data['created_time <'] = $value;
                        unset($data[$index]);//去除start
                         
                    }

                }
                // print_r($data);die();
                if ($status == 0) {
                    $contentArray = $this->learnenglishreply_model->get_learnEnglishReply($start,$data);
                    // print_r($contentArray);
                    // die();
                    $final_result_array = array();
                    //没有获取到数据
                    if($contentArray == null){
                    
                        $status = 4;
                        $message = 'no learnEnglishReplys you can get';
                    }
                    //有内容
                    else{

                        $contentArray = array_values($contentArray);
                        foreach($contentArray as $index => $row) {
                
                        //添加用户头像
                        $avatar = $this->user_model->get_thumbIcon($row['lr_user']);
                        $contentArray[$index]['avatar_thumb_path'] = $avatar;
                       
                  
                        }    
                    

                    }
                  
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
            }
          	
            if ($status == 0) {
                $contentArray = $this->learnenglishreply_model->get_learnEnglishReply(0);
                // print_r($contentArray);
                // die();
                $final_result_array = array();
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no learnEnglishReplys you can get';
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
         
         // print_r($row['id']); 
           if ($id_index != $row['id']) {
                $id_index = $row['id'];
               # code...
                $final_result_array[$id_index] = $row;
                $final_result_array[$id_index]['resource_spath']  = array();
                $final_result_array[$id_index]['resource_lpath']  = array();
                array_push($final_result_array[$id_index]['resource_spath'],$row['resource_spath'] );
                array_push($final_result_array[$id_index]['resource_lpath'],$row['resource_lpath'] );
           }    
           else
           {
                // print_r($row['resource_spath']);
                array_push($final_result_array[$id_index]['resource_spath'],$row['resource_spath'] );
                array_push($final_result_array[$id_index]['resource_lpath'],$row['resource_lpath'] );
           }
           

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
        $this->resizeConfig['width'] = 120;
        $this->resizeConfig['height'] = 120; 

        $this->load->library('image_lib', $this->resizeConfig); 
    }

}


?>
