<?php
class Bitmap
{
    private $bytes;

    public function getBytes()
    {
        return $this->bytes;
    }

    public function getSignature()
    {
        return chr($this->bytes[0]).chr($this->bytes[1]);
    }

    public function getFileSize()
    {
        return $this->getNBytes(2, 4);
    }

    public function getOffset()
    {
        return $this->getNBytes(10, 4);
    }

    public function getReserved1()
    {
        return $this->getNBytes(6, 2);
    }

    public function getReserved2()
    {
        return $this->getNBytes(8, 2);
    }

    public function getWidth()
    {
        return $this->getNBytes(18, 4);
    }

    public function getHeight()
    {
        return $this->getNBytes(22, 4);
    }

    public function getPlanes()
    {
        return $this->getNBytes(26, 2);
    }

    public function getBitsPerPixel()
    {
        return $this->getNBytes(28, 2);
    }

    public function getCompressionMethod()
    {
        return $this->getNBytes(30, 4);
    }

    public function getCompressionMethodName()
    {
        $compressionValue = $this->getCompressionMethod();
        if ($compressionValue == 0) {
            return "none";
        } elseif ($compressionValue == 1) {
            return "RLE-8";
        } elseif ($compressionValue == 2) {
            return "RLE-4";
        }
        return "";
    }

    public function getImageSize()
    {
        return $this->getNBytes(34, 4);
    }

    public function getResolutionH()
    {
        return $this->getNBytes(38, 4);
    }

    public function getResolutionV()
    {
        return $this->getNBytes(42, 4);
    }

    public function getColors()
    {
        return $this->getNBytes(46, 4);
    }

    public function getRows()
    {
        return round((count($this->bytes) - $this->getOffset())/(($this->getWidth() * $this->getBitsPerPixel())/8));
    }

    public function getImportantColors()
    {
        return $this->getNBytes(50, 4);
    }

    public function getRGB()
    {
        $rgb = array();
        $padding = 0;
        $x = $this->getWidth() * 3;
        while ($x%4 != 0) {
            $padding++;
            $x++;
        }
        $size = count($this->bytes) - $padding - $this->getWidth() * 3;
        $width = $this->getWidth();
        $height = $this->getHeight();
        for ($i = 0; $i < $height; $i++) {
            for ($k = 0; $k < $width; $k++) {
                $r = $this->bytes[$size + 2];
                $g = $this->bytes[$size + 1];
                $b = $this->bytes[$size];
                $rgb[] = array($r, $g, $b);
                $size += 3;
            }
            $size = $size - ($this->getWidth() * 3 + $padding) * 2 + $padding;
        }
        return $rgb;
    }

    public function getHTML($output)
    {
        $rgb = $this->getRGB();
        $width = $this->getWidth();
        $html = "";
        $px = 0;
        foreach ($rgb as $pixel) {
            $r = $pixel[0];
            $g = $pixel[1];
            $b = $pixel[2];
            $html .= "<span style='background-color: rgb(".$r.",".$g.",".$b.");'>&nbsp;&nbsp;</span>";
            if ($px == $width) {
                $html .= "<br>";
                $px = 0;
            }
            $px++;
        }
        file_put_contents($output, $html);
    }

    public function getNBytes($offset, $len)
    {
        $value = 0;
        for ($x = $offset; $x < $offset + $len; $x++) {
            $value = $value | $this->bytes[$x] << (8 * ($x-$offset));
        }
        return $value;
    }

    public function __construct($file)
    {
        $handle = fopen($file, "rb");
        $fsize = filesize($file);
        $contents = fread($handle, $fsize);
        for ($i = 0; $i < strlen($contents); $i++) {
            $this->bytes[] = ord($contents[$i]);
        }
    }
}

$bmp = new Bitmap("image.bmp");
$methods = get_class_methods($bmp);
foreach ($methods as $method) {
    $r = new ReflectionMethod($bmp, $method);
    $params = $r->getParameters();
    if (count($params) == 0) {
        var_dump($bmp->{$method}());
    }
}
$bmp->getHTML("image.html");
