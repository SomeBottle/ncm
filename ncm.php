<?php
class NCM {
    public static function dump($path, $dealwithid3 = true) {
        /*准备getid3库*/
        require_once './getid3/getid3.php';
        // Initialize getID3 engine
        $getID3 = new getID3;
        $TaggingFormat = 'UTF-8';
        $getID3->setOption(array('encoding' => $TaggingFormat));
        getid3_lib::IncludeDependency(GETID3_INCLUDEPATH . 'write.php', __FILE__, true);
        $TagData = array();
        require_once './xor.php';
        require_once './hexstr.php';
        $corekey = Hex::hexToStr('687A4852416D736F356B496E62617857');
        $metakey = Hex::hexToStr('2331346C6A6B5F215C5D2630553C2728');
        $head = '4354454E4644414D'; /*Standard Head*/
        $music = $path;
        $f = fopen($music, "rb") or die("Unable to open file!");
        $h = fread($f, 8); /*文件头*/
        if (Hex::strToHex($h) == $head) { /*Check head*/
            fseek($f, 2, SEEK_CUR); /*Seek Current*/
            /*https://www.w3school.com.cn/php/func_misc_unpack.asp */
            $keylen = fread($f, 4); /*Get key Length*/
            $keylen = unpack('I', $keylen) [1]; /*小端数据解包*/
            $rawdata = fread($f, $keylen); /*根据解包的键长读取初始数据，存入数组*/
            /*异或处理*/
            $keydata = '';
            $rawlen = strlen($rawdata);
            $ini = 0;
            while ($ini < $rawlen) {
                $keydata = $keydata . chr(ord($rawdata[$ini]) ^ 0x64); /*逐个将字节转换为ASCII值进行异或后再获得返回字符存入字符串*/
                $ini+= 1;
            }
            /*异或处理结束*/
            $keydata = openssl_decrypt($keydata, 'AES-128-ECB', $corekey, OPENSSL_RAW_DATA); /*利用openssl aes解密*/
            $keydata = substr($keydata, 17); /*从处理后的数据中去除neteasecloudmusic (17)*/
            $keylen = strlen($keydata); /*更新key的长度*/
            /*利用标准RC4-KSA算法（Key-scheduling algorithm）去计算S-box*/
            $realkey = array();
            $ini = 0;
            while ($ini < strlen($keydata)) {
                array_push($realkey, bin2hex($keydata[$ini])); /*先把处理后的数据转换为16进制值，再逐个推入数组（类似于实现python的bytearray）*/
                $ini++;
            }
            $box = range(0, 255); /*获得256长的随机数组box*/
            $rarray = range(0, 255); /*获得256长的随机数组*/
            $c = 0;
            foreach ($rarray as $v) {
                $offsetv = hexdec($realkey[$v % $keylen]); /*实现py版keyoffset>=keylength时取零的效果，此处取余数*/
                $c = ($c + $box[$v] + $offsetv) & 0xff; /*求与*/
                list($box[$v], $box[$c]) = array($box[$c], $box[$v]);
                /*$box[$v]=$box[$c];
                 $box[$c]=$box[$v];*/
            }
            /*计算完毕*/
            /*开始读取meta，这里前面的步骤和上面key的差不多*/
            $metalen = fread($f, 4); /*Get meta Length*/
            $metalen = unpack('I', $metalen) [1]; /*小端数据解包，同上的key*/
            $metaraw = fread($f, $metalen);
            /*异或处理*/
            $metadata = '';
            $ini = 0;
            while ($ini < strlen($metaraw)) {
                $metadata = $metadata . chr(ord($metaraw[$ini]) ^ 0x63); /*逐个将字节转换为ASCII值进行异或后再获得返回字符存入字符串*/
                $ini+= 1;
            }
            /*异或处理结束，此时处理过的数据解出来是一串base64，前面还有163keyDontModify等等(22)，照例去掉*/
            $metadata = base64_decode(substr($metadata, 22));
            $metadata = openssl_decrypt($metadata, 'AES-128-ECB', $metakey, OPENSSL_RAW_DATA); /*利用openssl aes解密,得到关键json*/
            $metadata = substr($metadata, 6); /*削了开头的music:*/
            $metadata = json_decode($metadata, true); /*转成数组*/
            /*获得crc32校验码*/
            $crc32 = unpack('I', fread($f, 4)) [1];
            fseek($f, 5, SEEK_CUR); /*不知为啥又跳了五个字节*/
            $imgsize = unpack('I', fread($f, 4)) [1]; /*快速获得图像数据大小*/
            $imgdata = fread($f, $imgsize); /*获得图像数据*/
            $cover = $metadata['albumPic']; /*获得封面url*/
            $format = $metadata['format']; /*获得格式*/
            $musicname = $metadata['musicName'];
            $file = $musicname . '.' . $format;
            $maindata = fread($f, filesize($music)); /*获取音乐的数据*/
            /*用RC4-PRGA 算法，进行还原并输出文件(前面的box可以用上)*/
            $content = '';
            foreach ($rarray as $v) {
                $code = $box[$v] + $box[($v + $box[$v]) & 0xff];
                $content = $content . chr($box[($code & 0xff) ]);
            }
            $content = str_repeat($content, (intval(strlen($maindata) / 256) + 1)); /*兼容略低版本php写法，此处代替bytearray循环push*/
            $content = substr($content, 1, strlen($maindata));
            file_put_contents($file, xor_enc($maindata, $content)); /*异或解密输出文件*/
            if ($dealwithid3) {
                $info = $getID3->analyze($file); /*分析输出的音乐*/
                $previoustag = $info['tags']['id3v2']; /*检索id3 v2数据(此处暂时写死，稍后看是否有更改)*/
                /*初始化tagwriter*/
                $tagwriter = new getid3_writetags;
                $tagwriter->filename = $file; /*指定文件*/
                $tagwriter->tagformats = array('id3v2.3'); /*指定写入数据模式id3v2*/
                $tagwriter->overwrite_tags = true; /*覆盖先前的tag(不覆盖有bug，作者快更新啊喂)*/
                $tagwriter->tag_encoding = $TaggingFormat;
                $exiftype = exif_imagetype($cover); /*直接通过Url获得封面exiftype*/
                $APICdata = file_get_contents($cover); /*直接通过url就绪APIC图像数据*/
                if ($APICdata && $previoustag) { /*数据是否有效*/
                    $TagData = $previoustag; /*继承先前的id3数据(顺便把备注给去了)*/
                    $TagData['attached_picture'][0]['data'] = $APICdata;
                    $TagData['attached_picture'][0]['picturetypeid'] = '0x03'; /*此处不是很清楚，但是0x03可以用*/
                    $TagData['attached_picture'][0]['description'] = 'cover'; /*封面图片描述*/
                    $TagData['attached_picture'][0]['mime'] = image_type_to_mime_type($exiftype); /*获得封面mimetype*/
                    $tagwriter->tag_data = $TagData; /*数据存入tagwriter*/
                    $process = $tagwriter->WriteTags(); /*写入数据*/
                    if (!$process) {
                        echo 'Unable to dump:Failed to write tags!<br>' . implode('<br><br>', $tagwriter->errors);
                        fclose($f);
                        exit();
                    }
                    if (!empty($tagwriter->warnings)) {
                        echo 'There were some warnings:<br>' . implode('<br><br>', $tagwriter->warnings);
                    }
                }
            }
            echo 'Successfully dumped';
        }
        fclose($f);
    }
}
/*cmd*/
if (preg_match("/cli/i", php_sapi_name())) {
    $param_arr = getopt('f:c:');
    if (!empty($param_arr['f'])) {
		if (!empty($param_arr['c'])) {
            NCM::dump($param_arr['f']);
		}else{
			NCM::dump($param_arr['f'],true);
		}
    }
}
?>