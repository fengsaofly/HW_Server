<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
class Utilsclass 
{
	var $EARTH_RADIUS = 6378137;  
    var $RAD = 0;

    // singleton instance 
  	private static $instance; 

    public function __construct()
    {
        $this->RAD = pi () / 180.0; 
    }

  	/*计算经纬度的范围*/
     public  function getAround($lat,$lon,$raidus){  
          
        $latitude = $lat;  
        $longitude = $lon;  
          
        $degree = (24901*1609)/360.0;  
        $raidusMile = $raidus;  
          
        $dpmLat = 1/$degree;  
        $radiusLat = $dpmLat*$raidusMile;  
        $minLat = $latitude - $radiusLat;  
        $maxLat = $latitude + $radiusLat;  
          
        $mpdLng = $degree*cos($latitude * (pi ()/180));  
        $dpmLng = 1 / $mpdLng;  
        $radiusLng = $dpmLng*$raidusMile;  
        $minLng = $longitude - $radiusLng;  
        $maxLng = $longitude + $radiusLng;

        $data = array();
        $data['lat >='] =  $minLat;
        $data['lon >='] =  $minLng;
        $data['lat <='] =  $maxLat;
        $data['lon <='] =  $maxLng;


        
        return $data;  
    }  

	

}


?>
