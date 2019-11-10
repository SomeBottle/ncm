# NCM 💣 Dump
*No worry for .ncm file anymore~

----------------------
![example](https://wx4.sinaimg.cn/large/ed039e1fly1g8t79wp6dfj206105zdge)  

----------------------

## 想法🤔 
   最近借了个黑胶下了点高音质音乐，正准备丢ipod,突然发现是ncm加密格式.到github上翻了一下，竟然没有php版的ncmdump.我便萌生了移植的想法.   
   随后在omg群里和橘佬一起研究了一下，这里感谢橘佬首先移植出来了：  
   * https://github.com/juzi5201314/ncmdump  
   
## 使用的Class📦  
   * [getID3](https://github.com/JamesHeinrich/getID3)  
   * [hex2str](https://www.cnblogs.com/wangluochong/p/11383000.html)  
   * [xor](https://www.cnblogs.com/dannywang/p/5316768.html)  
   
## 参考项目  
   * [NCMdump-py](https://github.com/bolitao/ncm)  
   * [ncmdump-php](https://github.com/juzi5201314/ncmdump)  
   
## 食用方法💊  
   * 命令行  
   ```
   ncm.php -f <filepath>  
   ```
   * PHP引用  
   ```php
   require 'ncm.php';  
   NCM::dump(<filepath>,<dealwithid3>);  
   ```
   **<dealwithid3>默认是true,如果为false，不会处理封面数据，开启这一项能很好对付新版失去封面数据的情况.  
   
## 跨越的困难  
   在新版PC网易云下载的ncm文件内音乐数据好似不再写入封面图片，php在这方面的库很少，getid3的库文档也不好看.幸亏帮酷论坛那边有人分享，了解之后颇有感觉，终于解决了封面问题.   
   
## EXTENSIONS  
   * php_openssl  
   * php_exif     
   我相信很多预装的php环境已经自带了这两个扩展，开启即可.  
