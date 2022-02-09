<?php
namespace app\hj212;

class VerifyUtil {

    public static function  verifyChar($tar, $src,$e){
        if(empty($tar)) {
            throw new \Exception("tar cannot be empty");
        }
        if(empty($src)) {
            throw new \Exception("tar cannot be empty");
        }
        if($tar != $src ){
            throw new \Exception("Static data core: " . $e . ' | ' . $tar . ": " . $tar . " -> " . $src);
        }
    }

    public static function verifyEqualLen(int $count, int $length, $e) {
        if($count != $length){
            throw new \Exception("Length does not core: " . $e . ' | ' . $count . " -> " . $length);
        }
    }

    public static function  verifyRangeLen(String $str, int $min, int $max, $e) {
        if(empty($str)){
            return;
        }
        $len = strlen($str);

        if($len >= $min  && $len <= $max){

        }else{
            throw new \Exception("Length does not in range: " . $e . " : " . $str . " -> (" . $min . "," . $max . ")");
        }
    }

    public static function  verifyRange(int $src, int $min, int $max, $e) {
        if($src >= $min  && $src <= $max){

        }else{
            throw new \Exception("Length does not in range: " . $src . " -> (" . $min . "," . $max . ")");
        }
    }

//    public static function  verifyRange(String $str, int $min, int $max, $e) {
//        $src = 0;
//        if($str != null){
//            $src = strlen($str);
//        }
//
//        if(src >= min  && src <= max){
//        }else{
//            throw new Exception("Length does not in range: " . $src . " -> (" . $min . "," . $max . ")");
//        }
//        return str;
//    }

    public static function  verifyCrc($msg, $crc, $e) {
//        $crc16 = T212Parser.crc16Checkout($msg,strlen($msg));
//        $crcSrc = Integer.parseInt(new String(crc),16);
//
//        if($crc16 != $crcSrc){
//            throw new Exception("Crc Verification failed: " . $msg . ": " . $crc);
//        }
    }

    public static function  verifyHave($object, $e){
//        if(!object.containsKey(e.name())){
//            throw new Exception("Field is missing: " . $e . ": " . $object);
//        }
    }

}
