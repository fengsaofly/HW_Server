<?php
// require APPPATH.'/libraries/HttpClient.class.php';
/**
*  
*/
class LearnEnglish extends CI_Controller
{
	var $ak = '7244d82a2ef54bfa015a0d7d6f85f372';
	
    

	 // var $perpage = 5;

    var $base_dir = 'LearnEnglish';
    var $cur_picname = '';
    var $db_name = 'LearnEnglish';
    var $resourceDB = 'L_Resource';
    var $final_img_url = '';
    var $mustPostNums = 2;
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        // $this->load->library('upload');
       
        $this->load->model('learnenglish_model');
        $this->load->model('resource_model');
        $this->load->model('user_model');
        //设置资源表
        $this->resource_model->setDBName($this->resourceDB);
        // $this->load->controller('storage');
	}

	public function index($page = 1) {

	}
    //添加破题信息
	function add() {
        $status = 0;
        $success = true;
        $message = "add success";
        $data = array();
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

            // print_r($_POST);die();
            foreach($_POST as $index => $value) {
    		    // if ($index=='l_title') {
          //           $data['l_title']= $value;
          //           $postNums++;
          //       }
                if ($index=='l_text') {
    				# code...
    				$data['l_text'] = $value;
                    $postNums++;
    			}
                // elseif($index=='l_grade'){
                //     $data['l_grade'] = $value;
                //     $postNums++;
                // }
               
                elseif($index=='l_user'){
                    $data['l_user'] = $value;
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
            if($status==0){
                
                    // $statusArray  = array('status' => $status,'message'=>$message );
                    // echo json_encode($statusArray);
                    // return false;
                
          		if ($postNums < $this->mustPostNums) {
                    $status = 3;
                    $message = 'parameter number is not enough';
                }
                //没有上传资源
                // elseif ($_FILES == null) {
                //     $status = 4;
                //     $message = 'no post record file';
                // }
                //上传包含文件    
                else   
                {
                    if ($_FILES != null) {

                        //载入配置信息
                        $config['upload_path'] = STORAGE_BASE_URL;
                        $config['allowed_types'] = 'gif|jpg|png|jpeg|amr';
                        $config['max_size'] = '5000';
                        $config['max_width'] = '5000';
                        $config['max_height'] = '4000';
                        
                        $this->load->library('upload', $config);
                       
                        $user_id = $data['l_user'] ;
                        //上传资源
                        $resultArray = $this->upload->multiple('l_resources',$user_id,'learnEnglish');


                        //上传资源出错
                        if (!empty($resultArray['error'])) {
                            $status = 5;
                            $message = 'upload resource failed';
                        }
                        //上传成功,创建缩略图，保存到数据库
                        else{

                            $images = array('resource_lpath' => array(),'resource_spath' => array());
                            
                            // 保存资源信息到images，所有图片进行缩放处理
                            foreach ($resultArray['real_path'] as $key => $value) {

                                //获取文件类型：image or audio
                                $pos = strripos($value,'.'); 
                                $fileType = substr($value,$pos+1);
                                //只处理图片
                                if ($fileType != 'amr') {
                                    $errors[$key] = $this->resizeIMG($value);
                            
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
                            for( $i = 0 ; $i < count($images['resource_spath']) ; $i++ ) {

                                $resource  = array('resource_spath' => $images['resource_spath'][$i] ,
                                                         'resource_lpath' => $images['resource_lpath'][$i] ,
                                                                'resource_id' => $resource_id);

                                $this->resource_model->set_resource($resource);
                            }
                            //将问题信息存入LearnEnglish表
                            $data['l_resource'] = $resource_id;
                            $newIDs['resource_id'] = $resource_id;
                        }
                    }

                    $data['created_time'] = time();
                    $newIDs['created_time'] = $data['created_time'];
                    $newIDs['l_id'] = $this->learnenglish_model->set_learnEnglish($data);
                    //插入LearnEnglish表出错
                    if ($newIDs['l_id'] <= 0 ) {
                        $status = 6;
                        $message = 'insert a new  LearnEnglish topic failed';
                    }

                }
                 
        


            }
        }

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
        $config['image_library'] = 'gd2';
        $config['source_image'] = $image;
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['width'] = 120;
        $config['height'] = 120; 

        $this->load->library('image_lib', $config); 
        if ($this->image_lib->resize()) {
            return '';
        }
        return  $this->image_lib->display_errors();
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
    public function get(){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
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

            // print_r($start);die();

            if ($status == 0) {
                    // $ids = $this->learnenglish_model->getIDs($start,$data);
                    $contentArray = $this->learnenglish_model->get_learnEnglish($start,$data);

                // print_r($contentArray);
                // die();
                $final_result_array = array();
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no LearnEnglishs you can get';
                }
                //有破题内容
                else{
                   // $contentArray = $this->mergeSearchResult($contentArray);
                    // print_r($final_result_array;);
                   $contentArray = array_values($contentArray);

                   //增加回复总数
                    foreach($contentArray as $index => $row) {
                
                        $rowReplySum =  $this->learnenglish_model->getReplySum($row['id']);
                        $contentArray[$index]['replySum'] = $rowReplySum;


                        //添加用户头像
                        $avatar = $this->user_model->get_thumbIcon($row['l_user']);
                        $contentArray[$index]['avatar_thumb_path'] = $avatar;
                       
                  
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
    //             elseif($index=='LearnEnglish_friends'){
    //                 $include_friends = true;
    //                 //echo 'include_friends';
    //             	$dataArray = json_decode($value,true);
    //                 if($dataArray == null){
    //         			$message = "LearnEnglish_friends decode result is null";
    //            	 		$status = 3;
                
    //         		}
    //             }
    //             else{
    //                 $message = "some parameters are not expected !";
    //                 $status = 2;
    //                 $success = false;
    //                 break;
				// }
            }
          	
            if ($status == 0) {
                $contentArray = $this->learnenglish_model->get_learnEnglish();
                
                if($contentArray == null){
                
                    $status = 2;
                    $message = 'no LearnEnglishs you can get';
                }
            }
        	
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  	// echo json_encode($result);
        echo base64_encode(json_encode($result));
        
       
        
    }

}


?>
