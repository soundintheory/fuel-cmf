<?php

namespace CMF\Glide\Manipulators;

use Intervention\Image\Image;
use League\Glide;

/**
 * @property string $crop
 */
class Crop extends Glide\Manipulators\BaseManipulator
{
    /**
     * Perform crop image manipulation.
     * @param  Image $image The source image.
     * @return Image The manipulated image.
     */
    public function run(Image $image)
    {
        $coordinates = $this->getCoordinates($image);

        if ($coordinates) {
            
            $image->crop(
                $coordinates[0],
                $coordinates[1],
                $coordinates[2],
                $coordinates[3]
            );
        }

        return $image;
    }

    /**
     * Resolve coordinates.
     * @param  Image $image The source image.
     * @return int[] The resolved coordinates.
     */
    public function getCoordinates(Image $image)
    {
        $coordinates = explode(',', $this->crop);

        if (count($coordinates) !== 4 or
            (!is_numeric($coordinates[0])) or
            (!is_numeric($coordinates[1])) or
            (!is_numeric($coordinates[2])) or
            (!is_numeric($coordinates[3]))) {
            return;
        }

        return [
            (int) $coordinates[0],
            (int) $coordinates[1],
            (int) $coordinates[2],
            (int) $coordinates[3],
        ];
    }
}
