<?php

declare(strict_types=1);

namespace Firezihai\WxTools;

use RuntimeException;

class Image
{
    /**
     * 解密图片.
     * @param string $fromFile 待解密文件
     * @param bool $isDeleteSource 是否删除原文件
     * @return string
     */
    public static function decrypt(string $fromFile, bool $isDeleteSource = true)
    {
        if (! file_exists($fromFile)) {
            throw new RuntimeException('待解密文件不存在');
        }
        $handle = fopen($fromFile, 'rb');
        $fileContent = fread($handle, filesize($fromFile));
        fclose($handle);
        // 将字符转成十进制进行位异或运算
        $str1 = ord(substr($fileContent, 0, 1));
        $str2 = ord(substr($fileContent, 1, 1));
        $key1 = $str1 ^ 255;
        $key2 = $str2 ^ 216;
        $secret = '';
        $ext = '.jpg';
        if ($key1 == $key2) {
            $secret = $key1;
            $ext = '.jpg';
        }

        $key1 = $str1 ^ 71;
        $key2 = $str2 ^ 73;
        if ($key1 == $key2) {
            $secret = $key1;
            $ext = '.gif';
        }
        $key1 = $str1 ^ 137;
        $key2 = $str2 ^ 80;
        if ($key1 == $key2) {
            $secret = $key1;
            $ext = '.png';
        }

        $key1 = $str1 ^ 66;
        $key2 = $str2 ^ 777;
        if ($key1 == $key2) {
            $secret = $key1;
            $ext = '.bmp';
        }
        $content = str_split($fileContent, 1);
        $newFileContent = '';
        foreach ($content as $val) {
            // 位异或之后再转回字符
            $newStr = chr(ord($val) ^ $secret);
            $newFileContent = $newFileContent . $newStr;
        }
        $filename = dirname($fromFile) . '/' . basename($fromFile, '.dat') . $ext;
        if ($isDeleteSource) {
            unlink($fromFile);
        }
        file_put_contents($filename, $newFileContent);
        return $filename;
    }
}
