<?php
/*https://www.cnblogs.com/wangluochong/p/11383000.html */
class Hex{ 
/**
*字符串转十六进制函数
*@pream string $str='abc';
*/
public static function strToHex($str){ 
$hex="";
for($i=0;$i<strlen($str);$i++)
$hex.=dechex(ord($str[$i]));
$hex=strtoupper($hex);
return $hex;
} 

/**
*十六进制转字符串函数
*@pream string $hex='616263';
*/ 
public static function hexToStr($hex){ 
$str=""; 
for($i=0;$i<strlen($hex)-1;$i+=2)
$str.=chr(hexdec($hex[$i].$hex[$i+1]));
return $str;
} 
}
?>