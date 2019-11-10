<?php
/*异或解密https://www.cnblogs.com/dannywang/p/5316768.html */
function xor_enc($q,$k){
        $crytxt='';
        for ($i=0;$i<strlen($q);$i++){
            $m=$i%strlen($k);
            $crytxt .=$q[$i]^$k[$m];
        }
        return $crytxt;

    }