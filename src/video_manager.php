<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class VideoManager
{
    public static function createPoster($input, $output, $frame = 0)
    {
        $ffmpeg = VideoManager::getFFMPEG();
        $ffmpeg->open($input)
            ->frame(TimeCode::fromSeconds($frame))
            ->save($output);
    }

    private static function getFFMPEG()
    {
        $ffprobe_path = exec('which ffprobe');
        $ffmpeg_path = exec('which ffmpeg');
        return FFMpeg::create(
            array(
                'ffprobe.binaries' => $ffprobe_path,
                'ffmpeg.binaries' => $ffmpeg_path
            )
        );
    }
}