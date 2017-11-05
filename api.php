<?php
error_reporting(0);
$ice_page = 'https://kan.msxiaobing.com/ImageGame/Portal?task=yanzhi';
$ice_yanzhi_api = 'http://kan.msxiaobing.com/Api/ImageAnalyze/Process?service=yanzhi';
$upload_file_api = 'http://kan.msxiaobing.com/Api/Image/UploadBase64';
################################---函数---###########################################
function curl_request($url,$post='',$cookie='', $returnCookie=0){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
            curl_setopt($curl, CURLOPT_REFERER, "https://kan.msxiaobing.com/ImageGame/Portal?task=yanzhi");
            if($post) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
            }
            if($cookie) {
                curl_setopt($curl, CURLOPT_COOKIE, $cookie);
            }
            curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($curl);
            if (curl_errno($curl)) {
                return curl_error($curl);
            }
            curl_close($curl);
            if($returnCookie){
                list($header, $body) = explode("\r\n\r\n", $data, 2);
                preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
				//var_dump($matches);
				$info["cookie"] = "";
				for($i = 0; $i < count($matches[1]); $i++){
					$info['cookie'] .= substr($matches[1][$i], 1);
					if($i != count($matches[1]) - 1) $info["cookie"].="; ";
				}
                $info['content'] = $body;
                return $info;
            }else{
                return $data;
            }
}
function curl_getimg($url, $data)
    {
        $headers = array(
            'Host:kan.msxiaobing.com',
            'Connection:keep-alive',
            'Accept:*/*',
            'Origin:http://kan.msxiaobing.com',
            'X-Requested-With:XMLHttpRequest',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.73 Safari/537.36',
            'Content-Type:application/x-www-form-urlencoded; charset=UTF-8',
            'Referer:http://kan.msxiaobing.com/V3/Portal',
            'Accept-Encoding:gzip, deflate',
            'Accept-Language:zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',
            'Expect:'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 7);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $output);
        return json_decode($body, true);
}
##################################---执行---###########################################
$file = $_FILES['image'];
$name = $file['name'];
$upload_path = UPLOAD_SAVE_PATH;	//自行替换上传位置
$arr=curl_request($ice_page,'','', 1);
$cookie = $arr["cookie"];
//echo $cookie;
$img64 = base64_encode(file_get_contents($upload_path));
$msimg_url_res = curl_getimg("http://kan.msxiaobing.com/Api/Image/UploadBase64", $img64);
$msimg_url = $msimg_url_res['Host'] . $msimg_url_res['Url'];
//echo $msimg_url;
list($s1, $s2) = explode(' ', microtime());
$mtime = (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
$data = array(
			'MsgId' => $mtime,
			'CreateTime' => time(),
			'Content[imageUrl]' => $msimg_url  #上传到微软服务器的图片
		);
$ms_ret = curl_request($ice_yanzhi_api, $data, $cookie, 0);
$score_arr = (array)json_decode($ms_ret);
$content = (array)($score_arr["content"]);
$text = $content["text"];
$img = $content["imageUrl"];
$return = ["msg"=>$text, "img"=>$img];
echo json_encode($return);
