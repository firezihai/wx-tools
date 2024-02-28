<?php

declare(strict_types=1);

namespace Firezihai\WxTools;

use Exception;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class Voice
{
    public static function toMp3($silkFile, $mp3File)
    {
        $ext = substr($silkFile, -4);
        if ($ext != 'silk') {
            throw new RuntimeException('非silk格式文件');
        }
        if (! file_exists($silkFile)) {
            throw new RuntimeException('silk文件不存在');
        }

        $voiceDir = dirname($mp3File, 1);
        if (! is_dir($voiceDir)) {
            mkdir($voiceDir, 0755, true);
        }

        $basename = basename($silkFile, '.slik');

        $pcmFile = $voiceDir . '/' . $basename . '.pcm';

        $process = new Process(explode(' ', 'python ' . __DIR__ . '/silk.py ' . $silkFile . ' ' . $pcmFile));
        $process->run();
        if (! $process->isSuccessful()) {
            throw new Exception('执行python转换silk文件失败');
        }
        // 不存在pcm文件
        if (! file_exists($pcmFile)) {
            throw new RuntimeException('silk转换pcm失败');
        }
        $ffmpegBin = '/usr/local/ffmpeg/bin/ffmpeg';
        // $ffmpegBin = 'ffmpeg';

        // 生成mp3
        try {
            $process = new Process(explode(' ', $ffmpegBin . ' -y -f s16le -i ' . $pcmFile . ' -ar 44100 -ac 1 ' . $mp3File));
            $process->run();
            if (! $process->isSuccessful()) {
                throw new Exception('执行ffmpeg命令错误');
            }
            unlink($pcmFile);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
