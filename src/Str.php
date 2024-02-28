<?php

declare(strict_types=1);

namespace Firezihai\WxTools;

class Str
{
    /**
     * byte字符转成int类型.
     * @param string $byteorder
     * @param bool $signed
     */
    public static function byte2Int(string $byte, $byteorder = 'big', $signed = false): int
    {
        // 将十六进制字符串转换为字节数组

        // 根据字节顺序调整字节数组
        if ($byteorder === 'little') {
            $byte = strrev($byte); // 反转字节数组以实现小端字节序
        }

        // 将字节数组转换为整数
        $value = unpack('N', $byte)[1]; // 'N' 表示无符号长整数（32位），适用于大端
        if ($signed) {
            // 如果是有符号整数，并且是小端字节序，则需要进行额外的处理
            if ($byteorder === 'little') {
                $value = intval($value); // 转换为有符号整数
            }
            // 对于大端字节序的有符号整数，PHP的unpack已经处理了补码
        }

        return $value;
    }

    /**
     * 将utf-16编码转成utf-8编码
     * @return array|bool|string
     */
    public static function utf162Utf8(string $str)
    {
        return mb_convert_encoding($str, 'UTF-8', 'UTF-16LE');
    }

    /**
     * 十六进制转字符串.
     * @return array|bool|string
     */
    public static function hex2str(string $str)
    {
        $output = '';

        for ($i = 0; $i < strlen($str); $i += 2) {
            if (isset($str[$i + 1])) {
                $byte = chr(intval('0X' . $str[$i] . $str[$i + 1], 0));
                $output .= $byte;
            } else {
                break; // 如果输入不完整则退出循环
            }
        }

        return $output;
    }
}
