<?php
namespace southcoastweb\Helpers;

use Config;

class Useful {
    // returns current page without http
	public static function cpage()
    {
		return rtrim(ltrim($_SERVER["REQUEST_URI"],FOLDER),'/');
	}

    public static function previousPage()
    {
        return ltrim(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH),'/');
    }


    // returns current page with http
	public static function currentPage()
    {
		$pageURL = 'http';
	    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	    $pageURL .= "://";
	    if ($_SERVER["SERVER_PORT"] != "80") {
	        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	    } else {
	        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	    }
	    return $pageURL;
	}

// creates a safe url from data
	public static function slug($string){
	//Unwanted: {UPPERCASE}; /?:@&=+$,.!~*'( )
	    $string = strtolower($string);
	//Strip any unwanted characters
	    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
	//Clean multiple dashes or whitespaces
	    $string = preg_replace("/[\s-]+/", " ", $string);
	//Convert whitespaces and underscore to dash
	    $string = preg_replace("/[\s_]/", "-", $string);
	    $string = trim($string,"-");
	    return $string;
	}

    public function safeSlug($string, $table = 'pages')
    {
        $check = '1';
        $db = Database::get();
        $slug = $string;
        // check if slug exists
        $loop = 2;
        while ($check == '1') {
            $slugged = self::slug($slug);
            $rows = $db->select("SELECT count(*) as cnt FROM ".PREFIX.$table." WHERE slug = :slugged", array(':slugged' => $slugged));
            if($rows[0]->cnt >= 1){
                // if it exists in database
                $slug = $string." ".$loop;
                $loop++;
            } else {
                // if it does not
                $check = 'TRUE';
                $loop = 2;
            }
        }
        return $slugged;
    }

// create an excert of x words
	public static function excerpt($text, $length=100){
		if (strlen($text) > $length) {
	  		$text = substr($text, 0, $length);
	  		$text = substr($text,0,strrpos($text," "));
	  		$etc = " ...";
	  		$text = $text.$etc;
	  	}
		return $text;
	}

// creates a random password
	public static function createRandomPassword($length = 12) {
	    $chars = "abcdefghijkmnpqrstuvwxyz023456789";
	    srand((double)microtime()*1000000);
	    $i = 0;
	    $pass = '' ;
	    while ($i <= $length) {
	        $num = rand() % 33;
	        $tmp = substr($chars, $num, 1);
	        $pass = $pass . $tmp;
	        $i++;
	    }
	    return $pass;
	}

// creates a UK postcode look nice
	public static function makePostcode($postcode){
		return strtoupper(str_replace(' ','',$postcode));
	}

	public static function makeUKPostcode($postcode){
	    $postcode = strtoupper(str_replace(' ','',$postcode));
	    $split = strlen($postcode) - 3;
	    $newpostcode = substr($postcode, 0, $split).' '.substr($postcode, $split,3);
	    return $newpostcode;
	}

// encrypt urls - bit of safe fun
	public static function safe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

	public static function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

	public static function encrypt_url($value){
      	if(!$value){return false;}
        $text       = $value;
        $iv_size    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv         = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext  = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, substr(config('app.key'),0,32), $text, MCRYPT_MODE_ECB, $iv);
        return trim(self::safe_b64encode($crypttext));
    }

	public static function decrypt_url($value){
        if(!$value){return false;}
        $crypttext  = self::safe_b64decode($value);
        $iv_size    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv         = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, substr(config('app.key'),0,32), $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }

    public static function makeUserPostcode(){
		$postcode = Session::get('postcode');
		if($postcode == ''){
			// no pstcode session set so grab from cookie
			if(isset($_COOKIE['_pc'])){
				Session::set('postcode',$_COOKIE['_pc']);
			}
			if(isset($_COOKIE['_long'])){
				Session::set('longtitude',$_COOKIE['_long']);
			}
			if(isset($_COOKIE['_latt'])){
				Session::set('lattitude',$_COOKIE['_latt']);
			}
		}
		if(Session::get('distance') == ''){
			Session::set('distance','2000');
		}

		if(Session::get('postcode') == ''){
			Session::set('postcode','');
			Session::set('longtitude','0.1420');
			Session::set('lattitude','51.5010');
		}
		setcookie("_pc",Session::get('postcode'), time()+3600*24*14,'/',DOMAINCOOKIE);
		setcookie("_long",Session::get('longtitude'), time()+3600*24*14,'/',DOMAINCOOKIE);
		setcookie("_latt",Session::get('lattitude'), time()+3600*24*14,'/',DOMAINCOOKIE);
	}

	public static function geoPostcode($postcode){
		Session::set('postcode',\helpers\usefull::makePostcode($postcode));
        $address = urlencode(Session::get('postcode'));
	// bounce postcode to google to get lattitude and longitude
        $url ="http://maps.googleapis.com/maps/api/geocode/json?address=$address&sensor=false";
        $result = file_get_contents($url);
        sleep(1);
	// ensure got a result back from google and set the session values
        if($result){
            $data = json_decode($result);
            $long = $data->results[0]->geometry->location->lng;
            $lat = $data->results[0]->geometry->location->lat;
            Session::set('longtitude',$long);
            Session::set('lattitude',$lat);
        } else {
        	Session::set('postcode','United Kingdom');
			Session::set('longtitude','0.1420');
			Session::set('lattitude','51.5010');
        }

		setcookie("_pc",Session::get('postcode'), time()+3600*24*14,'/',DOMAINCOOKIE);
		setcookie("_long",Session::get('longtitude'), time()+3600*24*14,'/',DOMAINCOOKIE);
		setcookie("_latt",Session::get('lattitude'), time()+3600*24*14,'/',DOMAINCOOKIE);
	}

	public function buildBreadCrumb($catid)
    {
        $db = \helpers\database::get();
        $row = $db->select("SELECT id FROM ".PREFIX."category_path WHERE categoryid = :catid LIMIT 1",array(':catid' => $catid));
        $rows = $db->select("SELECT p.*,c.category,c.category_slug FROM ".PREFIX."category_path p
                                                                LEFT JOIN ".PREFIX."categories c
                                                                ON c.categoryid = p.categoryid
                                                                WHERE p.id <= :catid",array('catid' => $row[0]->id));
        $rows = array_reverse($rows);
        $catid = $rows[0]->categoryid;
        foreach ($rows as $key => $value) {
            if($value->categoryid == $catid){
                if($value->parentid != 0){
                    $breadcrumb[$value->category] = '/category/'.$value->categoryid.'/'.$value->category_slug;
                    $catid = $value->parentid;
                }
            }
        }
        $breadcrumb['<i class="fa fa-home"></i>'] = '/home';
        $breadcrumb = array_reverse($breadcrumb);
        return $breadcrumb;
    }

    public static function writetolog($file,$string){
        if(file_exists($_SERVER['DOCUMENT_ROOT'].FOLDER.'logs/'.$file.'.csv')){
            $content = @file_get_contents($_SERVER['DOCUMENT_ROOT'].FOLDER.'logs/'.$file.'.csv');
        }
        $file = fopen($_SERVER['DOCUMENT_ROOT'].FOLDER.'logs/'.$file.'.csv','w+');
        fwrite($file,$string);
        fwrite($file,$content);
        fclose($file);
    }

    public function stars($qty = 0){
        $whole = round($qty,0,PHP_ROUND_HALF_DOWN);
        $empty = 5 - round($qty,0,PHP_ROUND_HALF_DOWN);
        for ($i=1; $i <= $whole ; $i++) {
            echo '<i class="fa fa-fw fa-star"></i>';
        }

        if($qty - $whole != 0){
            echo '<i class="fa fa-fw fa-star-half-o"></i>';
        }

        for ($i=1; $i < $empty  ; $i++) {
            echo '<i class="fa fa-fw fa-star-o"></i>';
        }
    }

    public function ping($sitemap_url = '')
    {
        if($sitemap_url == '') $sitemap_url = DIR.'/sitemap.xml';
        $curl_req = array();
        $urls = array();
        // below are the SEs that we will be pining
        $urls[] = "http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($sitemap_url);
        $urls[] = "http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($sitemap_url);
        $urls[] = "http://submissions.ask.com/ping?sitemap=".urlencode($sitemap_url);
        $urls[] = "http://rpc.weblogs.com/pingSiteForm?name=".urlencode($title)."&url=".urlencode($siteurl)."&changesURL=".urlencode($sitemap_url);

        foreach ($urls as $url){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, 1);
            $curl_req[] = $curl;
        }

        //initiating multi handler
        $multiHandle = curl_multi_init();

        // adding all the single handler to a multi handler
        foreach($curl_req as $key => $curl) {
            curl_multi_add_handle($multiHandle,$curl);
        }
        $isactive = null;
        do
        {
            $multi_curl = curl_multi_exec($multiHandle, $isactive);
        }
        while ($isactive || $multi_curl == CURLM_CALL_MULTI_PERFORM );

        $success = true;
        foreach($curl_req as $curlO){
            if(curl_errno($curlO) != CURLE_OK){
                $success = false;
            }
        }
        curl_multi_close($multiHandle);
        return $success;
    }

    public static function formatDate($checkDateTime, $dateFormat = "d/m/Y")
    {
        if($checkDateTime == null || substr($checkDateTime, 0, 4) == '0000' || substr($checkDateTime, 0, 4) == '1970') {
            return 'Never';
        } else {
            $checkDateTime  = strtotime($checkDateTime);
            $checkDate      = date($dateFormat, $checkDateTime);
            $checkTime      = date("H:i", $checkDateTime);

            $todayDate      = strtotime(date("Y-m-d"));

            $datediff       = $checkDateTime - $todayDate;
            $difference     = floor($datediff/(60*60*24));

            if($difference==0) {
                return $checkTime == '00:00' ? 'Today' : 'Today at '.$checkTime;
            } else if($difference > 1) {
                return $checkDate;
            } else if($difference > 0) {
                return $checkTime == '00:00' ? 'Tomorrow' : 'Tomorrow at '.$checkTime;
            } else if($difference < -1) {
                return $checkDate;
            } else {
                return $checkTime == '00:00' ? 'Yesterday' : 'Yesterday at '.$checkTime;
            }
        }
    }

    public static function getAssetPath($filePath, $templateFolder = 'DEFAULT')
    {
        $templateFolder = $templateFolder == 'DEFAULT' ? Config::get('app.template') : $templateFolder;
        return str_replace('public_html', '', $_SERVER['DOCUMENT_ROOT']).'app/Templates/'.$templateFolder.'/Assets'.$filePath;
    }

    /**
     * Detect brightness of colour and return appropriate font colour
     * @param  string $hex   Hex code to detect
     * @param  string $light Light colour to apply to dark backgrounds
     * @param  string $dark  Dark colour to apply to light backgrounds
     * @return string        Appropriate colour for passed Hex code
     */
    public static function fontColor($hex, $light = '#FFFFFF', $dark = '#000000')
    {
        $hex = trim((string)$hex); // Remove any whitespace
        $hex = ltrim($hex, '#'); // Remove hash if present
        
        if(strlen($hex) >= 6) $hexSplit = str_split($hex, 2);
        if(strlen($hex) < 6)  $hexSplit = str_split($hex);

        $r = hexdec($hexSplit[0]);
        $g = hexdec($hexSplit[1]);
        $b = hexdec($hexSplit[2]);

        // $lightness = (max($r, $g, $b) + min($r, $g, $b)) / 510;
        // if($lightness >= .55){
        //     return $dark;
        // }
        // return $light;
        
        $yiq = (($r*299)+($g*587)+($b*114))/1000;
        return ($yiq >= 128) ? $dark : $light;
    }
}
