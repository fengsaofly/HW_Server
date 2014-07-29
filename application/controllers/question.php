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
 //    function  handleWeliaoUploadFile($owner='user',$upload_name='user_pic'){

	//     $dir_base = $this->domain;     //文件上传根目录

	//     $target_base = '/'.$owner.'/';
	//     // echo $target_base;
	//     $errorImg = 'error';
	//     if(empty($_FILES)) {
	//         echo "<textarea><img src='{$target_base}error.jpg'/></textarea>";
	//         exit(0);
	//     }
 //        // $targetDir = dirname($_FILES['upload_file']['tmp_name']).'/discounts/';
 //  		//为上传文件命名
 //  		// $shop_id = $this->uri->segment(2);
 //  		// print_r('shopid:'.$shop_id);
 //  		// if ($shop_id !=null && is_numeric($shop_id)) {
 //  		// 	$gb_filename = $shop_id;
 //  		// }
 //  		// else{
	//    		$filename = $_FILES[$upload_name]['name'];
	//         $gb_filename = iconv('utf-8','gb2312',$filename);    //名字转换成gb2312处理
	//         $this->cur_picname = $gb_filename;	
 //        //保存上传图片名
 //        // }
 //        //文件不存在才上传
 //        if(!file_exists($dir_base.$gb_filename)) {
 //            $isMoved = false;  //默认上传失败
 //            $MAXIMUM_FILESIZE = 2 * 1024 * 1024;     //文件大小限制    1M = 1 * 1024 * 1024 B;
 //            $rEFileTypes = "/^\.(jpg|jpeg|gif|png){1}$/i"; 
 //            if ($_FILES[$upload_name]['size'] <= $MAXIMUM_FILESIZE && 
 //                preg_match($rEFileTypes, strrchr($gb_filename, '.'))) {  
 //            	$stor = new SaeStorage();
                
 //            	$isMoved = $stor->upload($dir_base,$target_base.$gb_filename,$_FILES[$upload_name]['tmp_name']);
 //                //$this->resizeIMG($isMoved);
 //            }
 //        }else{

 //            $isMoved = true;    //已存在文件设置为上传成功
 //        }
 //        if($isMoved){
 //            // echo "<textarea><img  src={$isMoved} width='128' height='128' title='$gb_filename'/></textarea>";

 //        }else {
 //              echo "<textarea><img src='{$dir_base}/{$errorImg}' width='128' height='128'/></textarea>";
 //        }

 

	// }
 //    private function resizeIMG($url)
 //    {
        
 //        $f = new SaeFetchurl();
 //    	$img_data = $f->fetch($url);
 //        $img = new SaeImage();
 //        $img->setData( $img_data );
 //        $img->resize(600); // 等比缩放到200宽
 //        //   $img->flipH(); // 水平翻转
 //        //$img->flipV(); // 垂直翻转
 //        $new_data = $img->exec(); // 执行处理并返回处理后的二进制数据
 //        //  $img->exec( 'jpg' , true );
        
 //        //图片处理失败时输出错误码和错误信息
 //        if ($new_data === false)
 //              var_dump($img->errno(), $img->errmsg());
 //        else
 //            return   $new_data;
      
 //    }
 //    public function renameUploadFile($targetName,$sourceName,$dbName,$userid){

	// 	$domain = $this->domain;
		
	
		
 //        	//为文件增加后缀名
	// 	$pos = strripos($sourceName,'.'); 
	// 	$fileType = substr($sourceName,$pos);
		
	// 	$targetName =  $targetName.$fileType;
        
 //        $targetUrl = $this->base_dir.'/'.$userid.'/'.$targetName;
	// 	$stor = new SaeStorage();
        
 //        $sourceUrl = '/'.$this->base_dir.'/'.$sourceName;
 //        $idTag = 'question_id';	
 //        $picTag = 'question_img';
   
		
	// 	$content = $stor->read( $domain , $sourceUrl) ;
        
 //        $imgUrl =  $stor->getUrl($domain ,  $sourceUrl);
 //        //进行缩放图片内容的读取
 //        $content = $this->resizeIMG($imgUrl);
	// 	if (!$content) {
	// 		die('获取源文件数据失败！');
	// 	}
        
	// 	if($result = $stor->write($domain,$targetUrl,$content))
	// 	{
 //            $this->final_img_url =  $result ;
	// 		$data  = array($picTag => $result );
	// 		$stor->delete($domain,$sourceUrl);
	// 		$this->db->where($idTag, $userid);
	// 		$this->db->update($dbName, $data);
	// 	}
	// 	else
 //         	var_dump($stor->errno(), $stor->errmsg());
 		
         
	// }

}


?>
