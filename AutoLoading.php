<?php
class AutoLoading{
    public static function autoload($className){
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, DIR.'\\'.$className).'.class.php';
        if(file_exists($fileName)){
            require $fileName;
        }else{
            echo 'error:'.$fileName.' is not exist';
            exit;
        }
    }
}