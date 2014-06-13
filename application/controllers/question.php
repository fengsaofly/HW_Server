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
    function __construct() {
        parent::__construct();
  
        $this->load->helper('url');//加载url辅助函数
        $this->load->helper('form');
	    $this->load->library('form_validation');
        $this->load->library('upload');
        $this->load->model('question_model');

	}

	public function index($page = 1) {
         
  
	}
    //添加破题信息
	function add() {
        $status = 0;
        $success = true;
        $message = "add success";
        $data = array();
		if($_POST==null)
        {
            $message = "no request parameters!";
			$status = 3;
			$success = false;
        }
        $i=0;
        foreach($_POST as $index => $value) {
			// if ($index=='ak') {
			// 	# code...
			// 	if ( $this->ak!=$value) {
			// 		# code...
			// 		$message = "ak is error!";
			// 		$status = 1;
   //                  $success = false;
			// 		break;
			// 	}
			// }
		    if ($index=='q_title') {
                $data['q_title']= $value;
            }
            elseif ($index=='q_text_content') {
				# code...
				$data['q_text_content'] = $value;
			
			elseif ($index=='q_audio') {
				# code...
				$data['q_audio'] = $value;
			}
		
            elseif($index=='q_img_index[]'){
            	$data['q_img_index'][$i] = $value;
                $i++;
                
            }
            elseif($index=='q_grade'){
                $data['q_grade'] = $value;
                
            }
            elseif($index=='q_subject')
                $data['q_subject'] = $value;
                
            }
			else{
				$message = "some parameters are not expected !";
				$status = 2;
				$success = false;
				break;
			}
		}
        if($status!=0){
        
              $statusArray  = array('status' => $status,'message'=>$message );
              echo json_encode($statusArray);
              return false;
        }
  		
        // $newUserId = $this->question_model->set_question($data);
        //获取图片文件名
        //    $picUrl = $_POST['user_pic'];
        //$pos = strripos($picUrl,'/'); 
        //$name = substr($picUrl,$pos+1);
        //修改图片文件名
        //  $this->shops_model->renameUploadFile('User',$name,'User',$newUserId);
        //print_r($data);
    
     
        if ($_FILES!=null) {

                
                $data['q_img_result'] = $this->unload->multiple('q_img_index[]');
                // $this->handleWeliaoUploadFile($this->base_dir,'question_img');
                $cur_time = time();
                echo "时间戳:".$cur_time.'<br>';

                

                // $this->renameUploadFile($cur_time,$this->cur_picname,$this->db_name,$newUserId);
                // if ($this->final_img_url == '') {
                //     $status = 3;
                //     $message = 'img upload failed';
                // }
             
        }
        
        //print_r($_FILES);
           

        $statusArray  = array('status' => $status,'message'=>$message,'question_img'=> $data);
        echo json_encode($statusArray);
        //  print_r($_FILES);
        //echo '<br>'.$result;
       	return true;
	}
    //获取指定用户信息
    public function view($user_identifier=false){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $dataArray = array();
        if($user_identifier==false){
        
            
            $status = 1;
            $message = '未指定用户id';
            
            
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
                elseif($index=='question_friends'){
                	$dataArray = json_decode($value,true);
                }
                else{
                    $message = "some parameters are not expected !";
                    $status = 2;
                    $success = false;
                    break;
				}
            }
            
        	$contentArray = $this->question_model->get_question_from_friends($dataArray);
        
            if($contentArray == null){
            
                $status = 2;
                $message = '指定的用户不存在';
            }
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  	echo json_encode($result);
       
        
    } 
      //获取单一用户信息
    public function getinfo(){
        $status = 0;
        $message = 'access is successful!';
        $contentArray = null;
        $include_friends = false;
        $dataArray = array();
        
        if($_POST==null){
        
            
            $status = 1;
            $message = '未包含指定参数';
            
            
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
                elseif($index=='question_friends'){
                    $include_friends = true;
                    //echo 'include_friends';
                	$dataArray = json_decode($value,true);
                    if($dataArray == null){
            			$message = "question_friends decode result is null";
               	 		$status = 3;
                
            		}
                }
                else{
                    $message = "some parameters are not expected !";
                    $status = 2;
                    $success = false;
                    break;
				}
            }
          	
            
           
            
            // print_r($dataArray);
            $jsonArray = array();
            
            foreach($dataArray as $index => $value) {
                $jsonVal = json_decode($value,true);
                $values = array_values($jsonVal);
            	array_push($jsonArray,@$values[0]);
                
            }
            // print_r($jsonArray);
            if($status!=0)
            {
                if($include_friends == false){
                
                    $status = 4;
                    $message = 'POST has no friends info';
                }
                $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  			echo json_encode($result);
                return false;
        
            }
        	$contentArray = $this->question_model->get_question_from_friends($jsonArray);
        	
            if($contentArray == null){
            
                $status = 5;
                $message = '指定的用户不存在';
            }
            
        }
        $result  = array('status' => $status, 'message'=> $message,'content'=>$contentArray);
	  	echo json_encode($result);
        
        
       
        
    }
    function  handleWeliaoUploadFile($owner='user',$upload_name='user_pic'){

	    $dir_base = $this->domain;     //文件上传根目录

	    $target_base = '/'.$owner.'/';
	    // echo $target_base;
	    $errorImg = 'error';
	    if(empty($_FILES)) {
	        echo "<textarea><img src='{$target_base}error.jpg'/></textarea>";
	        exit(0);
	    }
        // $targetDir = dirname($_FILES['upload_file']['tmp_name']).'/discounts/';
  		//为上传文件命名
  		// $shop_id = $this->uri->segment(2);
  		// print_r('shopid:'.$shop_id);
  		// if ($shop_id !=null && is_numeric($shop_id)) {
  		// 	$gb_filename = $shop_id;
  		// }
  		// else{
	   		$filename = $_FILES[$upload_name]['name'];
	        $gb_filename = iconv('utf-8','gb2312',$filename);    //名字转换成gb2312处理
	        $this->cur_picname = $gb_filename;	
        //保存上传图片名
        // }
        //文件不存在才上传
        if(!file_exists($dir_base.$gb_filename)) {
            $isMoved = false;  //默认上传失败
            $MAXIMUM_FILESIZE = 2 * 1024 * 1024;     //文件大小限制    1M = 1 * 1024 * 1024 B;
            $rEFileTypes = "/^\.(jpg|jpeg|gif|png){1}$/i"; 
            if ($_FILES[$upload_name]['size'] <= $MAXIMUM_FILESIZE && 
                preg_match($rEFileTypes, strrchr($gb_filename, '.'))) {  
            	$stor = new SaeStorage();
                
            	$isMoved = $stor->upload($dir_base,$target_base.$gb_filename,$_FILES[$upload_name]['tmp_name']);
                //$this->resizeIMG($isMoved);
            }
        }else{

            $isMoved = true;    //已存在文件设置为上传成功
        }
        if($isMoved){
            // echo "<textarea><img  src={$isMoved} width='128' height='128' title='$gb_filename'/></textarea>";

        }else {
              echo "<textarea><img src='{$dir_base}/{$errorImg}' width='128' height='128'/></textarea>";
        }

 

	}
    private function resizeIMG($url)
    {
        
        $f = new SaeFetchurl();
    	$img_data = $f->fetch($url);
        $img = new SaeImage();
        $img->setData( $img_data );
        $img->resize(600); // 等比缩放到200宽
        //   $img->flipH(); // 水平翻转
        //$img->flipV(); // 垂直翻转
        $new_data = $img->exec(); // 执行处理并返回处理后的二进制数据
        //  $img->exec( 'jpg' , true );
        
        //图片处理失败时输出错误码和错误信息
        if ($new_data === false)
              var_dump($img->errno(), $img->errmsg());
        else
            return   $new_data;
      
    }
    public function renameUploadFile($targetName,$sourceName,$dbName,$userid){

		$domain = $this->domain;
		
	
		
        	//为文件增加后缀名
		$pos = strripos($sourceName,'.'); 
		$fileType = substr($sourceName,$pos);
		
		$targetName =  $targetName.$fileType;
        
        $targetUrl = $this->base_dir.'/'.$userid.'/'.$targetName;
		$stor = new SaeStorage();
        
        $sourceUrl = '/'.$this->base_dir.'/'.$sourceName;
        $idTag = 'question_id';	
        $picTag = 'question_img';
   
		
		$content = $stor->read( $domain , $sourceUrl) ;
        
        $imgUrl =  $stor->getUrl($domain ,  $sourceUrl);
        //进行缩放图片内容的读取
        $content = $this->resizeIMG($imgUrl);
		if (!$content) {
			die('获取源文件数据失败！');
		}
        
		if($result = $stor->write($domain,$targetUrl,$content))
		{
            $this->final_img_url =  $result ;
			$data  = array($picTag => $result );
			$stor->delete($domain,$sourceUrl);
			$this->db->where($idTag, $userid);
			$this->db->update($dbName, $data);
		}
		else
         	var_dump($stor->errno(), $stor->errmsg());
 		
         
	}

}


?>
