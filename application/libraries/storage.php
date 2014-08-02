<?php
/**
*  
*/
class Storage 
{

    
    public function delete($files = FALSE){
        if (!$files) {
            return null;
        }
        foreach ($variable as $file) {
            unlink($file);
        }

    }

	

}


?>
