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
 * @author Romain Neutron imprec@gmail.com
 */
interface Transcodable extends AudioInterface
{

    /**
     * Returns the audio codec
     *
     * @return string
     */
    public function getAudioCodec();
}
