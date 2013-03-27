<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\Format;

/**
 * The base video interface
 *
 * @author Romain Neutron imprec@gmail.com
 */
interface VideoInterface extends AudioInterface
{
    /**
     * Returns the number of passes
     *
     * @return string
     */
    public function getPasses();
}
