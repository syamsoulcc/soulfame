<?php
include APP_ROOT_DIR . "/incl/config/function.php";

function checkResetPath($force_https, $force_subdomain){

    $procceed = false;

    $temp_folder = APP_ROOT_DIR . "/temp";
    if(!is_dir($temp_folder)) mkdir($temp_folder, 0755, true);

    $reset_path = $temp_folder . "/reset_path";

    $current_temp_str = MD5(json_encode(Array(
        $force_https, $force_subdomain
    )));

    if(!file_exists($reset_path)) $procceed = true;
    else{
        $previous_temp_str = file_get_contents($reset_path);
        if($previous_temp_str!=$current_temp_str){
            unlink($reset_path);
            $procceed = true;
        }
    }

    if($procceed){
        $fp = fopen($reset_path, 'w');
        fwrite($fp, $current_temp_str);
        fclose($fp);

        resetPath(APP_ROOT_DIR, get_url_base_path(), $force_https, $force_subdomain);

        header("Location: index.php");
        exit();
    }
}

function resetPath($fulldir, $rootdir, $force_https=false, $force_subdomain=false){

    $myfile = fopen($fulldir . "/.htaccess", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "ErrorDocument 403 ".$rootdir."/forbidden.php\n";
    $txt .= "RewriteEngine on\n";
    if(!empty($force_subdomain) && is_string($force_subdomain)){
        $force_subdomain = str_replace(".", "\.", $force_subdomain);
        $txt .= "RewriteCond %{HTTP_HOST} ^".$force_subdomain."$\n";
        $txt .= "RewriteRule .? - [S=1]\n";
        $txt .= "RewriteRule ^ http://".$force_subdomain."/index.php [L]\n";
    }
    if($force_https){
        $txt .= "RewriteCond %{HTTPS} off\n";
        $txt .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n";
    }
    $txt .= "RewriteCond %{REQUEST_URI} !forbidden.php$\n";
    $txt .= "RewriteCond %{REQUEST_URI} !reset_path.php$\n";
    $txt .= "RewriteCond %{REQUEST_URI} !assets/.*$\n";
    $txt .= "RewriteRule ^(.*)$ index.php?page=$1 [L,QSA]\n";
    fwrite($myfile, $txt);
    fclose($myfile);


    $myfile = fopen($fulldir . "/incl/.htaccess", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "deny from all\n";
    $txt .= "ErrorDocument 403 ".$rootdir."/forbidden.php\n";
    fwrite($myfile, $txt);
    fclose($myfile);
}

function get_url_http_host(){
    if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) $http_protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    elseif(isset($_SERVER['REQUEST_SCHEME'])) $http_protocol = $_SERVER['REQUEST_SCHEME'];
    else{
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on") $http_protocol = "https";
        else $http_protocol = "http";
    }
    return $http_protocol."://".$_SERVER["HTTP_HOST"];
}

function get_url_base_path(){
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
    if(empty($script_name)) die("ERROR: URL_BASE_PATH is empty");
    $script_name = strtr($script_name, Array(
        "index.php"         => "",
        "forbidden.php"     => "",
    ));
    while(substr($script_name, -1, 1) == "/") $script_name = substr($script_name, 0, -1);
    return $script_name;
}

//general function:
function is_empty_arr($arr){
    foreach($arr as $ikey=>$eval) if(!isset($eval) || empty($eval)) return true;
    return false;
}

function sd_default($var, $def){
    if(!isset($var) || empty($var)) return $def;
    return $var;
}

function array_specific($arr, $arr_whitelist){
    $return_arr = Array();
    foreach($arr_whitelist as $attr) $return_arr[$attr] = $arr[$attr];
    return $return_arr;
}

function transparent_background($filepath, $color){
    if(file_exists($filepath)){
        $img = imagecreatefrompng($filepath);
        $colors = explode(',', $color);
        $remove = imagecolorallocate($img, $colors[0], $colors[1], $colors[2]);
        imagecolortransparent($img, $remove);
        unlink($filepath);
        imagepng($img, $filepath);
    }
}
//transparent_background(USER_ROOT_PATH."/image.png", '255,255,255');

function str_replace_first($from, $to, $content){
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $content, 1);
}

function imagefrombase64str($fullbase64str){
    $image_base64 = substr($fullbase64str, strpos($fullbase64str, "base64,")+7, strlen($fullbase64str));
    $image_extension = substr($fullbase64str, 5, strpos($fullbase64str, "base64,")-6);
    $image_extension = explode("/", $image_extension);

    $img_size = getimagesizefromstring(base64_decode($image_base64));

    return Array(
        "base64"    => $image_base64,
        "type"      => $image_extension[0],
        "ext"       => $image_extension[1],
        "width"     => $img_size[0],
        "height"    => $img_size[1],
    );
}

function imagetobase64str($image_base64, $ext){
    return "data:image/$ext;base64,$image_base64";
}

function sub_array_search($needle, $haystack){
    $result = Array();
    foreach($haystack as $ret_key=>$e_hs){
        if(in_array($needle, $e_hs)) if(!in_array($ret_key, $result)) array_push($result, $ret_key);
    }
    return $result;
}

?>
