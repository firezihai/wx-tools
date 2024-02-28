<?php

declare(strict_types=1);

namespace Firezihai\WxTools;

use RuntimeException;

class Db
{
    public const SQLITE_FILE_HEADER = "SQLite format 3\x00";

    public const DEFAULT_PAGESIZE = 4096;

    public const KEY_SIZE = 32;

    public const DEFAULT_ITER = 64000;

    /**
     * 解密数据库文件.
     * @param string $key 密钥
     * @param string $fromFile 来源文件
     * @param string $outFile 输出文件
     * @return string
     */
    public static function decrypt(string $key, string $fromFile, string $outFile)
    {
        if (! file_exists($fromFile) || ! is_file($fromFile)) {
            throw new RuntimeException('源文件 ' . $fromFile . ' 不存在');
        }

        if (! file_exists(dirname($outFile))) {
            throw new RuntimeException('输出目录' . dirname($outFile) . ' 不存在');
        }

        if (strlen($key) != 64) {
            throw new RuntimeException('密钥必须是64位');
        }
        // 将64位的密钥转成字节
        $password = hex2bin(trim($key));
        $handle = fopen($fromFile, 'rb');
        $fileContent = fread($handle, filesize($fromFile));
        fclose($handle);
        if (strlen($fileContent) < 16) {
            throw new RuntimeException('文件小于16位');
        }

        $salt = substr($fileContent, 0, 16);
        // 获取第一页
        $firstPage = substr($fileContent, 16, static::DEFAULT_PAGESIZE - 16);

        $byteKey = hash_pbkdf2('sha1', $password, $salt, static::DEFAULT_ITER, static::KEY_SIZE, true);

        if (strlen($salt) != 16) {
            throw new RuntimeException('文件格式不正确');
        }
        // 生成密钥salt
        $macSalt = '';
        for ($i = 0; $i < 16; ++$i) {
            $macSalt .= chr(ord(substr($salt, $i, 1)) ^ 58);
        }

        $macKey = hash_pbkdf2('sha1', $byteKey, $macSalt, 2, static::KEY_SIZE, true);
        $hashMac = hash_hmac('sha1', substr($firstPage, 0, -32) . hex2bin('01000000'), $macKey, true);

        // 核对密钥
        if ($hashMac != substr($firstPage, -32, -12)) {
            throw new RuntimeException('密钥错误->' . $fromFile);
        }
        // 分割字符串，对每一页单独解密
        $newContent = str_split(substr($fileContent, static::DEFAULT_PAGESIZE), static::DEFAULT_PAGESIZE);

        $deFile = fopen($outFile, 'wb');
        // 写入文件类型
        fwrite($deFile, static::SQLITE_FILE_HEADER);
        // 解密第一页
        $decryptStr = openssl_decrypt(substr($firstPage, 0, -48), 'aes-256-cbc', $byteKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($firstPage, -48, 16));
        fwrite($deFile, $decryptStr);
        fwrite($deFile, substr($firstPage, -48));
        // 解密剩下的
        foreach ($newContent as $i) {
            $decryptStr = openssl_decrypt(substr($i, 0, -48), 'aes-256-cbc', $byteKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($i, -48, 16));
            fwrite($deFile, $decryptStr);
            fwrite($deFile, substr($i, -48));
        }
        fclose($deFile);
        return $outFile;
    }

    /**
     * 解析联系人扩展信息.
     * @param string $buf 待解析字符串
     * @return array
     */
    public static function decodeContactBufExtra($buf)
    {
        $bin = [
            '46CF10C4' => 'solgan', // 个性签名
            'A4D9024A' => 'country', // 国家
            'E2EAA8D1' => 'province', // 省份
            '1D025BBF' => 'city', // 城市
            // '759378AD' => 'telephone',
            '74752C06' => 'sex', // 性别 1男2女
        ];
        // 其他扩展信息，可将用while循环去根据规则解析出来
        $result = [];
        $offset = 0;
        foreach ($bin as $key => $title) {
            $offset = stripos($buf, hex2bin($key)) + 4;
            $char = substr($buf, $offset, 1);
            $offset = $offset + 1;
            # 四个字节的int，小端序
            if ($char == hex2bin('04')) {
                $content = substr($buf, $offset, 4);
                $content = Str::byte2Int($content, 'little');
                $offset = $offset + 4;
                $result[$title] = $content;
            } elseif ($char == hex2bin('18')) {
                $length = substr($buf, $offset, 4); // 长度
                $offset = $offset + 4;
                $length = Str::byte2int($length, 'little');
                $strContent = substr($buf, $offset, $length - 1); // 最后一位是空字符中。需要移除
                $offset = $offset + $length;
                $result[$title] = Str::utf162Utf8($strContent);
            }
        }
        return $result;
    }
}
