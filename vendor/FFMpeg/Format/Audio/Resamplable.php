<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\Format\Audio;

use FFMpeg\Format\AudioInterface;

/**
 * The resamplable audio interface
 *
 * This provide a method to define the AudiosampleRate
 *
 * @author Romain Neutron imprec@gmail.com
 */
interface Resamplable extends AudioInterface
{

    /**
     * Get the audio sample rate
     *
     * @return integer
     */
    public function getAudioSampleRate();
}
