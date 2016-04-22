<?php

namespace Intervention\Image\Gd\Commands;

use Intervention\Image\Point;
use Intervention\Image\Size;

class CropCommand extends ResizeCommand
{
    /**
     * Crop an image instance
     *
     * @param  \Intervention\Image\Image $image
     * @return boolean
     */
    public function execute($image)
    {
        $width = $this->argument(0)->type('digit')->required()->value();
        $height = $this->argument(1)->type('digit')->required()->value();
        $x = $this->argument(2)->type('digit')->value();
        $y = $this->argument(3)->type('digit')->value();

        if (is_null($width) || is_null($height)) {
            throw new \Intervention\Image\Exception\InvalidArgumentException(
                "Width and height of cutout needs to be defined."
            );
        }

        $cropped = new Size($width, $height);
        $position = new Point($x, $y);
        $size = $image->getSize();

        // align boxes
        if (is_null($x) && is_null($y)) {
            $position = $size->align('center')->relativePosition($cropped->align('center'));
        }

        // crop image core
        return $this->modify($image, 0, 0, $position->x, $position->y, $cropped->width, $cropped->height, $size->width, $size->height);
    }

    /**
     * Wrapper function for 'imagecopy'
     *
     * @param  Image   $image
     * @param  integer $dst_x
     * @param  integer $dst_y
     * @param  integer $src_x
     * @param  integer $src_y
     * @param  integer $dst_w
     * @param  integer $dst_h
     * @param  integer $src_w
     * @param  integer $src_h
     * @return boolean
     */
    protected function modify($image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
    {
        // create new image
        $modified = imagecreatetruecolor($dst_w, $dst_h);

        // get current image
        $resource = $image->getCore();

        // preserve transparency
        $transIndex = imagecolortransparent($resource);

        if ($transIndex != -1) {
            $rgba = imagecolorsforindex($modified, $transIndex);
            $transColor = imagecolorallocatealpha($modified, $rgba['red'], $rgba['green'], $rgba['blue'], 127);
            imagefill($modified, 0, 0, $transColor);
            imagecolortransparent($modified, $transColor);
        } else {
            imagealphablending($modified, false);
            imagesavealpha($modified, true);
        }

        // copy content from resource
        $result = imagecopy(
            $modified,
            $resource,
            -$src_x,
            -$src_y,
            $dst_x,
            $dst_y,
            $src_w,
            $src_h
        );

        // set new content as recource
        $image->setCore($modified);

        return $result;
    }
}
