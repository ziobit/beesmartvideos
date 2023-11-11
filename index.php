<?php

namespace ZioBit\zbPHPAnimation;

function parabolic($x) {
  return $x*$x;
}


ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

define ("ZB_MP4_VIDEO", 1);
define ("ZB_MP4_WHATSAPP", 2);
define ("DEBUG_DISABLED", false);
define ("DEBUG_ENABLED", true);

$WHITE = "255,255,255";
$BLACK = "0,0,0";
$RED = "255,0,0";
$BLUE = "0,0,255";
$YELLOW = "255,255,0";
$GREEN = "0,255,0";
$DARK_GREEN = "0,128,0";
$PURPLE = "255,0,255";
$GRAY50 = "128,128,128";
$GRAY75 = "192,192,192";

function getComplementaryColor($image, $color) {
  $r = ($color >> 16) & 0xFF;
  $g = ($color >> 8) & 0xFF;
  $b = $color & 0xFF;

  $complementaryR = 255 - $r;
  $complementaryG = 255 - $g;
  $complementaryB = 255 - $b;

  return imagecolorallocate($image, $complementaryR, $complementaryG, $complementaryB);
}

class scene {
  public $durationInFrames, $zoom, $startFromBefore, $delay, $defaultLineThickness, $name;
  private $elements;

  protected $pngToStartFrom = null;
  function __construct($durationInFrames, $zoom, $startFromBefore, $delay, $defaultLineThickness=5, $name=null) {
    $this->durationInFrames = $durationInFrames;
    $this->zoom = $zoom;
    $this->startFromBefore = $startFromBefore;
    $this->delay = $delay;
    $this->name = $name;
    $this->defaultLineThickness = $defaultLineThickness;
  }
  function addElement($element) {
    $this->elements[] = $element;
  }
  function getElement($element) {
    return $this->elements[$element];
  }
  function howManyElements() {
    if ($this->elements == null)
      return 0;
    return count($this->elements);
  }

}

class zbPHPAnimation {
  private $fps, $width, $height, $backgroundColor, $backgroundPicture, $backgroundPictureTransparency, $imageFolder;
  private $scenes;
  private $warningARsent = false;

  private $debug;
  private $logoWidth, $logoHeight;
  private $fontName, $fontSize;

  function setFont($name, $size) {
    $this->fontName = $name;
    $this->fontSize = $size;
  }

  function getCenter($polygon) {
    $NumPoints = count($polygon);
    if($polygon[$NumPoints-1] == $polygon[0]){
        $NumPoints--;
    }else{
        //Add the first point at the end of the array.
        $polygon[$NumPoints] = $polygon[0];
    }
    $x = 0;
    $y = 0;
    $lastPoint = $polygon[$NumPoints - 1];
    for ($i=0; $i<=$NumPoints - 1; $i++) {
        $point = $polygon[$i];
        $x += ($lastPoint[0] + $point[0]) * ($lastPoint[0] * $point[1] - $point[0] * $lastPoint[1]);
        $y += ($lastPoint[1] + $point[1]) * ($lastPoint[0] * $point[1] - $point[0] * $lastPoint[1]);
        $lastPoint = $point;
    }
    $x /= 6*$this->ComputeArea($polygon);
    $y /= 6*$this->ComputeArea($polygon);
    return array($x, $y);
  }

  function ComputeArea($polygon)
  { 
      $NumPoints = count($polygon);
      if($polygon[$NumPoints-1] == $polygon[0]){
          $NumPoints--;
      }else{
          //Add the first point at the end of the array.
          $polygon[$NumPoints] = $polygon[0];
      }
      $area = 0;
      for( $i = 0; $i <= $NumPoints; ++$i )
        $area += $polygon[$i][0]*( @$polygon[$i+1][1] - @$polygon[$i-1][1] );
      $area /= 2;
      return $area;
  }

  function __construct($fps, $width, $height, $backgroundColor, $backgroundPicture, $backgroundPictureTransparency, $imageFolder, $debug=false) {
    $this->fps = $fps;
    $this->width = $width;
    $this->height = $height;
    $this->backgroundColor = $backgroundColor;
    $this->backgroundPicture = $backgroundPicture;
    $this->backgroundPictureTransparency = $backgroundPictureTransparency;
    $this->imageFolder = $imageFolder;
    $this->debug = $debug;
    $this->fontSize = 12;
    $this->fontName = 'arial.ttf';
  }

  function newElementInScene($scene, $element) {
    $this->scenes[$scene-1]->addElement($element);
  }


  function newScene($durationInFrames, $zoom, $startFromBefore, $delay, $defaultLineThickness, $name=null) {
    if (!$this->scenes) {
      $this->scenes[0] = new scene($durationInFrames, $zoom, $startFromBefore, $delay, $defaultLineThickness, $name);
      return 1;
    }
    return array_push($this->scenes, new scene($durationInFrames, $zoom, $startFromBefore, $delay, $defaultLineThickness, $name));
  }

  function drawDashedLine($image, $x1, $y1, $x2, $y2, $dash1, $dash2, $color) {
    $lineLength = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    $angle = atan2($y2 - $y1, $x2 - $x1);
  
    $x = $x1;
    $y = $y1;
    $newX = $x1;
    $newY = $y1;
    $remaining = $lineLength;
  
    while ($remaining > 0) {
      // Draw the dash segment
      $dashLength = min($dash1, $remaining);
      $newX = $x + cos($angle) * $dashLength;
      $newY = $y + sin($angle) * $dashLength;
      imageline($image, (int)$x, (int)$y, (int)$newX, (int)$newY, $color);
  
      $remaining -= $dashLength;
      if ($remaining <= 0) {
        break;
      }
  
      // Skip the gap segment
      $gapLength = min($dash2, $remaining);
      $x = $newX + cos($angle) * $gapLength;
      $y = $newY + sin($angle) * $gapLength;
  
      $remaining -= $gapLength;
    }
  
    // Ensure the last part of the line connected to x2/y2 is drawn
    if ($remaining <= 0) {
      imageline($image, (int)$newX, (int)$newY, (int)$x2, (int)$y2, $color);
    }
  }
  
  //C:\xampp\htdocs\beesmartvideo\latex\miktex\bin\x64

    
  function draw() {
    if (!count($this->scenes)) {
      throw new Exception('Nothing to draw');
    }

    // delete all the old pics, if any
    $files = glob("{$this->imageFolder}/*.png"); // get all file names
    foreach($files as $file){ // iterate files
      if(is_file($file)) {
        unlink($file); // delete file
      }
    }    

    $frame = 0;
    list($this->logoWidth, $this->logoHeight) = getimagesize($this->backgroundPicture);
    // this is just to create the common backgroundColor
    $im = imagecreatetruecolor(1, 1);
    $background = explode(',', $this->backgroundColor);
    $backgroundColor = imagecolorallocate($im, $background[0], $background[1], $background[2]);
    imagedestroy($im);
  
    // this is to calculate the cartesian points from pixels
    $unitPixelsValue = -1;
    $zeroX = -1;
    $zeroY = -1;
//    echo "***".count($this->scenes);
    for($scene=0; $scene < count($this->scenes); ++$scene) {
      $totalAnimationSteps = $this->scenes[$scene]->durationInFrames; 
      $lastFrameToBePrinted = $frame-1;
      for($animationStep=0; $animationStep<$totalAnimationSteps; ++$animationStep) {
        // background for all frames
        $im = imagecreatetruecolor($this->width, $this->height);
        imagefilledrectangle($im, 0, 0, $this->width-1, $this->height-1, $backgroundColor);
        if ($this->backgroundPicture) {
          $logoPic = @imagecreatefromstring(file_get_contents($this->backgroundPicture));
          imagecopymerge($im, $logoPic, ($this->width-$this->logoWidth)>>1, ($this->height-$this->logoHeight)>>1, 0, 0, $this->logoWidth, $this->logoHeight, $this->backgroundPictureTransparency);
        }
        if ($this->scenes[$scene]->startFromBefore) {
          $beforePngImage = imagecreatefrompng(sprintf("{$this->imageFolder}/%06d.png", $lastFrameToBePrinted));
          imagecopy($im, $beforePngImage, 0, 0, 0, 0, $this->width, $this->height);
          imagedestroy($beforePngImage);
        }  
        for($el=0; $el<$this->scenes[$scene]->howManyElements(); $el++) {
  
          $cmd = $this->scenes[$scene]->getElement($el);
          // then parse what you have to do
          imagesetthickness($im, $this->scenes[$scene]->defaultLineThickness); // reset thickness to default value

          if (substr($cmd, 0, 4) == 'text') {
            /* 
            $g->newElementInScene($scene, "text,x,y,x2,y2, text, [0,0,0],[0,0,255], startsize,endsize, startTrasp, endTrasp");
            1,2 startx,starty
            3,4 endx,endy
            5 text
            6..8 start color
            9..11 end color
            12 start size
            13 end size
            14 start Trasp
            15 end Trasp
            16 fontfile
            */
            $params = explode('|', $cmd);
            // normalize transparency, from 0/100% to 0/127
            $params[14] = intval($params[14]/100*127);
            $params[15] = intval($params[15]/100*127);

            if ($totalAnimationSteps == 1) {
              $microStepColorR = ($params[9]-$params[6]);
              $microStepColorG = ($params[10]-$params[7]);
              $microStepColorB = ($params[11]-$params[8]);
              $microStepTransparency = ($params[15]-$params[14]);
              $microStepSize = ($params[13]-$params[12]);
              $microStepX = ($params[3]-$params[1]);
              $microStepY = ($params[4]-$params[2]);
            } else {
              $microStepColorR = ($params[9]-$params[6])/($totalAnimationSteps-1);
              $microStepColorG = ($params[10]-$params[7])/($totalAnimationSteps-1);
              $microStepColorB = ($params[11]-$params[8])/($totalAnimationSteps-1);
              $microStepTransparency = ($params[15]-$params[14])/($totalAnimationSteps-1);
              $microStepSize = ($params[13]-$params[12])/($totalAnimationSteps-1);
              $microStepX = ($params[3]-$params[1])/($totalAnimationSteps-1);
              $microStepY = ($params[4]-$params[2])/($totalAnimationSteps-1);
            }

            $colorR = intval($params[6] + $microStepColorR * $animationStep);
            $colorG = intval($params[7] + $microStepColorG * $animationStep);
            $colorB = intval($params[8] + $microStepColorB * $animationStep);
            $currentSize = intval($params[12] + $microStepSize * $animationStep);
            $currentTransp = intval($params[14] + $microStepTransparency * $animationStep);
            $currentX = $zeroX + $unitPixelsValue * ($params[1]+$microStepX* $animationStep);
            $currentY = $zeroY - $unitPixelsValue * ($params[2]+$microStepY* $animationStep);

            $textcolor = imagecolorallocatealpha($im, $colorR, $colorG, $colorB, $currentTransp);
            imagettftext($im, (int)$currentSize, 0 ,(int)$currentX, (int)$currentY, $textcolor, @$params[16] ? @$params[16] : $this->fontName, $params[5]);

          }

          if (substr($cmd, 0, 7) == 'polygon') {
            $params = explode(',', $cmd);
            // normalize transparency, from 0/100% to 0/127
            $params[13] = intval($params[13]/100*127);
            $color = imagecolorallocatealpha($im, $params[1], $params[2], $params[3], $params[13]);
            $filledColor = imagecolorallocatealpha($im, $params[4], $params[5], $params[6], $params[13]);
            $points = [];
            for($q=14; $q<count($params); ++$q) {
              if (!($q&1)) {
                $points[] = $zeroX + $unitPixelsValue*$params[$q];
              } else {
                $points[] = $zeroY - $unitPixelsValue*$params[$q];
              }
            }
            if ($params[8]) { // if must use letters
              // for now, letters have NO transparency, but you can enable the next line to use the one from the parameters
              $lettersColor = imagecolorallocatealpha($im, $params[10], $params[11], $params[12], 0);
              // $lettersColor = imagecolorallocatealpha($im, $params[10], $params[11], $params[12], $params[13]);
              $labels = $this->getLabelCoordinates(30, $points);
              for($iLabel=0; $iLabel<count($labels); $iLabel +=2) {
                $screenX = intval($labels[$iLabel]);
                $screenY = intval($labels[$iLabel+1]);
//                echo "[{$points[$iLabel]}, {$points[$iLabel+1]}] [$screenX, $screenY]<br>";
                $tb = imagettfbbox($params[9], 0, $this->fontName, chr(ord($params[8])+$iLabel/2));
                $tbW = $tb[2]-$tb[0];
                $tbH = $tb[5]-$tb[1];
                imagettftext($im, $params[9], 0, 
                              $screenX-($tbW>>1), 
                              $screenY-($tbH>>1), 
                              $lettersColor, $this->fontName, chr(ord($params[8])+$iLabel/2));
                // imagerectangle($im, $screenX-10, $screenY-10, $screenX+10, $screenY+10, $filledColor);  
              } 
            }

            // if filled, do it first
            if ($params[7] == 'DF' || $params[7] == 'FD' || $params[7] == 'F') {
              // $colorFillAlpha = imagecolorallocatealpha($im, $params[4], $params[5], $params[6], $params[14]);
              imagefilledpolygon($im, $points, $filledColor);
            }
            // if draw the border too
            if (strpos($params[7],'D')>=0) {
              imagepolygon($im, $points, $color);
            }        
          }
  
          if (substr($cmd, 0, 11) == 'animPolygon') {
            $params = explode(',', $cmd);

            if ($totalAnimationSteps == 1) {
              $microStepColorR = ($params[7]-$params[1]);
              $microStepColorG = ($params[8]-$params[2]);
              $microStepColorB = ($params[9]-$params[3]);
              $microStepBackgroundR = ($params[10]-$params[4]);
              $microStepBackgroundG = ($params[11]-$params[5]);
              $microStepBackgroundB = ($params[12]-$params[6]);
            } else {
              $microStepColorR = ($params[7]-$params[1])/($totalAnimationSteps-1);
              $microStepColorG = ($params[8]-$params[2])/($totalAnimationSteps-1);
              $microStepColorB = ($params[9]-$params[3])/($totalAnimationSteps-1);
              $microStepBackgroundR = ($params[10]-$params[4])/($totalAnimationSteps-1);
              $microStepBackgroundG = ($params[11]-$params[5])/($totalAnimationSteps-1);
              $microStepBackgroundB = ($params[12]-$params[6])/($totalAnimationSteps-1);
            }
            $colorR = intval($params[1] + $microStepColorR * $animationStep);
            $colorG = intval($params[2] + $microStepColorG * $animationStep);
            $colorB = intval($params[3] + $microStepColorB * $animationStep);

            $colorBackgroundR = intval($params[4] + $microStepBackgroundR * $animationStep);
            $colorBackgroundG = intval($params[5] + $microStepBackgroundG * $animationStep);
            $colorBackgroundB = intval($params[6] + $microStepBackgroundB * $animationStep);
            $polyColor = imagecolorallocate($im, $colorR, $colorG, $colorB);
            $polyFill = imagecolorallocate($im, $colorBackgroundR, $colorBackgroundG, $colorBackgroundB);
        
        // animPolygon Col/FillCol/Col2/FillCol2/DF/deltaX/deltaY/RotationDEG/Points
            $deltaX = $params[14];
            $deltaY = $params[15];
            $rot = $params[16] / 180 * M_PI;
            $points = [];
            $polygon = [];
            for($q=17; $q<count($params); $q+=2) {
              $polygon[] = array($params[$q],$params[$q+1]);
            }
            list($centerX, $centerY) = $this->getCenter($polygon);
            if ($totalAnimationSteps == 1) {
              $angle = $animationStep*$rot;
            } else {
              $angle = $animationStep*$rot/($totalAnimationSteps-1);
            }
            $cos = cos($angle);
            $sin = sin($angle);
            for($q=17; $q<count($params); $q+=2) {
              $nX = ($params[$q]-$centerX) * $cos - ($params[$q+1]-$centerY)*$sin;
              $nY = ($params[$q]-$centerX) * $sin + ($params[$q+1]-$centerY)*$cos;
              if ($totalAnimationSteps == 1) {
                $points[] = $zeroX + $unitPixelsValue * ($centerX+$nX+$animationStep*$deltaX);
                $points[] = $zeroY - $unitPixelsValue * ($centerY+$nY+$animationStep*$deltaY);
              } else {
                $points[] = $zeroX + $unitPixelsValue * ($centerX+$nX+$animationStep*$deltaX)/($totalAnimationSteps-1);
                $points[] = $zeroY - $unitPixelsValue * ($centerY+$nY+$animationStep*$deltaY)/($totalAnimationSteps-1);
              }
            }
            // if filled, do it first
            if ($params[13] == 'DF' || $params[13] == 'FD' || $params[13] == 'F') {
              imagefilledpolygon($im, $points, $polyFill);
            }
            // if draw the border too
            if (strpos($params[13],'D')>=0) {
              imagepolygon($im, $points, $polyColor);
            }
          }

          if (substr($cmd, 0, 17) == 'animOffsetPolygon') {
            $params = explode(',', $cmd);

            if ($totalAnimationSteps == 1) {
              $microStepColorR = ($params[9]-$params[3]);
              $microStepColorG = ($params[10]-$params[4]);
              $microStepColorB = ($params[11]-$params[5]);
              $microStepBackgroundR = ($params[12]-$params[6]);
              $microStepBackgroundG = ($params[13]-$params[7]);
              $microStepBackgroundB = ($params[14]-$params[8]);  
            } else {
              $microStepColorR = ($params[9]-$params[3])/($totalAnimationSteps-1);
              $microStepColorG = ($params[10]-$params[4])/($totalAnimationSteps-1);
              $microStepColorB = ($params[11]-$params[5])/($totalAnimationSteps-1);
              $microStepBackgroundR = ($params[12]-$params[6])/($totalAnimationSteps-1);
              $microStepBackgroundG = ($params[13]-$params[7])/($totalAnimationSteps-1);
              $microStepBackgroundB = ($params[14]-$params[8])/($totalAnimationSteps-1);  
            }

            $colorR = intval($params[3] + $microStepColorR * $animationStep);
            $colorG = intval($params[4] + $microStepColorG * $animationStep);
            $colorB = intval($params[5] + $microStepColorB * $animationStep);
        
            $colorBackgroundR = intval($params[6] + $microStepBackgroundR * $animationStep);
            $colorBackgroundG = intval($params[7] + $microStepBackgroundG * $animationStep);
            $colorBackgroundB = intval($params[8] + $microStepBackgroundB * $animationStep);
            $polyColor = imagecolorallocate($im, $colorR, $colorG, $colorB);
            $polyFill = imagecolorallocate($im, $colorBackgroundR, $colorBackgroundG, $colorBackgroundB);
        
            $deltaX = $params[16];
            $deltaY = $params[17];
            $rot = $params[18] / 180 * M_PI;
            $points = [];
            $polygon = [];
            for($q=19; $q<count($params); $q+=2) {
              $polygon[] = array($params[$q],$params[$q+1]);
            }
            list($centerX, $centerY) = $this->getCenter($polygon);
            $centerX = $params[1];
            $centerY = $params[2];
            if ($totalAnimationSteps == 1) {
              $angle = $animationStep*$rot;
            } else {
              $angle = $animationStep*$rot/($totalAnimationSteps-1);
            }
            $cos = cos($angle);
            $sin = sin($angle);
            for($q=19; $q<count($params); $q+=2) {
              $nX = ($params[$q]-$centerX) * $cos - ($params[$q+1]-$centerY)*$sin;
              $nY = ($params[$q]-$centerX) * $sin + ($params[$q+1]-$centerY)*$cos;
              if ($totalAnimationSteps == 1) {
                $points[] = $zeroX + $unitPixelsValue * ($centerX+$nX+$animationStep*$deltaX);
                $points[] = $zeroY - $unitPixelsValue * ($centerY+$nY+$animationStep*$deltaY);
              } else {
                $points[] = $zeroX + $unitPixelsValue * ($centerX+$nX+$animationStep*$deltaX/($totalAnimationSteps-1));
                $points[] = $zeroY - $unitPixelsValue * ($centerY+$nY+$animationStep*$deltaY/($totalAnimationSteps-1));
              }
            }
            // if filled, do it first
            if ($params[15] == 'DF' || $params[15] == 'FD' || $params[15] == 'F') {
              imagefilledpolygon($im, $points, $polyFill);
            }
            // if draw the border too
            if (strpos($params[15],'D')>=0) {
              imagepolygon($im, $points, $polyColor);
            }
          }

          if (substr($cmd, 0, 10) == 'animCircle') {
            $params = explode(',', $cmd);
            if ($totalAnimationSteps == 1) {
              $microStepX = ($params[11]-$params[1]);
              $microStepY = ($params[12]-$params[2]);
              $microStepColorR = ($params[14]-$params[4]);
              $microStepColorG = ($params[15]-$params[5]);
              $microStepColorB = ($params[16]-$params[6]);
              $microStepBackgroundR = ($params[17]-$params[7]);
              $microStepBackgroundG = ($params[18]-$params[8]);
              $microStepBackgroundB = ($params[19]-$params[9]);
              $microStepRadius = ($params[13]-$params[3]);
            } else {
              $microStepX = ($params[11]-$params[1])/($totalAnimationSteps-1);
              $microStepY = ($params[12]-$params[2])/($totalAnimationSteps-1);
              $microStepColorR = ($params[14]-$params[4])/($totalAnimationSteps-1);
              $microStepColorG = ($params[15]-$params[5])/($totalAnimationSteps-1);
              $microStepColorB = ($params[16]-$params[6])/($totalAnimationSteps-1);
              $microStepBackgroundR = ($params[17]-$params[7])/($totalAnimationSteps-1);
              $microStepBackgroundG = ($params[18]-$params[8])/($totalAnimationSteps-1);
              $microStepBackgroundB = ($params[19]-$params[9])/($totalAnimationSteps-1);
              $microStepRadius = ($params[13]-$params[3])/($totalAnimationSteps-1);
            }
            $x = intval($zeroX + $unitPixelsValue*($params[1] + $microStepX*$animationStep));
            $y = intval($zeroY - $unitPixelsValue*($params[2] + $microStepY*$animationStep));
            $colorR = intval($params[4] + $microStepColorR * $animationStep);
            $colorG = intval($params[5] + $microStepColorG * $animationStep);
            $colorB = intval($params[6] + $microStepColorB * $animationStep);
            $colorBackgroundR = intval($params[7] + $microStepBackgroundR * $animationStep);
            $colorBackgroundG = intval($params[8] + $microStepBackgroundG * $animationStep);
            $colorBackgroundB = intval($params[9] + $microStepBackgroundB * $animationStep);
            $radius = $unitPixelsValue*$params[3] + $unitPixelsValue*$microStepRadius * $animationStep;
            $circleColor = imagecolorallocate($im, $colorR, $colorG, $colorB);
            $circleFill = imagecolorallocate($im, $colorBackgroundR, $colorBackgroundG, $colorBackgroundB);
            // if filled, do it first
            if ($params[10] == 'DF' || $params[10] == 'FD' || $params[10] == 'F') {
              imagefilledellipse($im, (int)$x, (int)$y, (int)$radius, (int)$radius, $circleFill);
            }
            // if draw the border too
            if (strpos($params[10],'D')>=0) {
              imageellipse($im, (int)$x, (int)$y, (int)$radius, (int)$radius, $circleColor);
            }
        
          }
                  
          if (substr($cmd, 0, 6) == 'circle') {
            $params = explode(',', $cmd);
            $x = $zeroX + $unitPixelsValue*$params[1];
            $y = $zeroY - $unitPixelsValue*$params[2];
            $radius = $unitPixelsValue*$params[3];
            $circleColor = imagecolorallocate($im, $params[4], $params[5], $params[6]);
            $circleFill = imagecolorallocate($im, $params[7], $params[8], $params[9]);
            // if filled, do it first
            if ($params[10] == 'DF' || $params[10] == 'FD' || $params[10] == 'F') {
              imagefilledellipse($im, (int)$x, (int)$y, (int)$radius, (int)$radius, $circleFill);
            }
            // if draw the border too
            if (strpos($params[10],'D')>=0) {
              imageellipse($im, (int)$x, (int)$y, (int)$radius, (int)$radius, $circleColor);
            }
          }
  
          if (substr($cmd, 0, 9) == 'funcTable') {
// funcTable x1,y1,x2,y2, func, colorFunction, colorFillCircle, colorBorderCirle, radiusCircle, sprintf, [array xvalues]
            $params = explode(',', $cmd);
            $x1 = $zeroX + $unitPixelsValue*$params[1];
            $y1 = $zeroY - $unitPixelsValue*$params[2];
            $x2 = $zeroX + $unitPixelsValue*$params[3];
            $y2 = $zeroY - $unitPixelsValue*$params[4];
            $func = $params[5];
            $funcColor = imagecolorallocate($im, $params[6], $params[7], $params[8]);
            $circleFill = imagecolorallocate($im, $params[9], $params[10], $params[11]);
            $circleColor = imagecolorallocate($im, $params[12], $params[13], $params[14]);
            $radius = $unitPixelsValue*$params[15];
            $sprintf = $params[16];
            imagefilledrectangle($im, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $circleFill);

            // print all the points up to where you are now
            $whereWeAre = 1;
            if ($totalAnimationSteps > 1) {
              $whereWeAre = $animationStep / ($totalAnimationSteps-1);
            }
            $xValues = [];
            for($i=17; $i<count($params); ++$i) {
              $xValues[] = $params[$i];
            }

            // now, for all the "X" pixels from 0 until now, print a smooth graph...
            for($i=0; $i<$whereWeAre*count($xValues)-1; ++$i) {
              $rangeValues = $xValues[$i+1]-$xValues[$i];
              $rangePixels = round($zeroX + $unitPixelsValue*$xValues[$i+1]) - round($zeroX + $unitPixelsValue*$xValues[$i]);
              for($start=0, $pixel=round($zeroX + $unitPixelsValue*$xValues[$i]); $pixel < round($zeroX + $unitPixelsValue*$xValues[$i+1]); ++$pixel, ++$start) {
                imageline($im, 
                          $pixel-1, 
                          round($zeroY - $unitPixelsValue*$func($xValues[$i]+$start*$rangeValues/$rangePixels)), 
                          $pixel, 
                          round($zeroY - $unitPixelsValue*$func($xValues[$i]+($start+1)*$rangeValues/$rangePixels)), 
                          $funcColor);
              }
            }

            // calculate the yoffset for the font (it starts printing in the lower corner, so we must push it down)
            $bbox = imagettfbbox($this->fontSize, 0, $this->fontName, "QQQ");
            $fontYOffset = $bbox[1] - $bbox[7];

            for($i=0; $i<$whereWeAre*count($xValues); ++$i) {
              $realX = $xValues[$i];
              $realY = $func($xValues[$i]);
              // echo "i=".($i*($y1-$y2)/count($xValues))."<br>";
//              qqqq
              imagettftext($im, $this->fontSize, 0, (int)$x1, (int)($fontYOffset+$y1+$i*($y2-$y1)/count($xValues)), $textColor, $this->fontName, sprintf($sprintf, round($realX), round($realY)));

              imagefilledellipse($im, 
              (int)(round($zeroX + $unitPixelsValue*$realX)), 
              (int)(round($zeroY - $unitPixelsValue*$realY)), 
              (int)$radius, 
              (int)$radius, 
              $circleFill);
            }





          }
  
          if (substr($cmd, 0, 8) == 'animLine') {
            $params = explode(',', $cmd);
            // animLine, x1,y1,x2,y2,colore, pixel per dash (se 0, non dashed), width, x3,y3,x4,y4,colore finale  
            $x1 = $params[1];
            $y1 = $params[2];
            $x2 = $params[3];
            $y2 = $params[4];
            $x3 = $params[10];
            $y3 = $params[11];
            $x4 = $params[12];
            $y4 = $params[13];
            if ($totalAnimationSteps == 1) {
              $microStepColorR = ($params[14]-$params[5]);
              $microStepColorG = ($params[15]-$params[6]);
              $microStepColorB = ($params[16]-$params[7]);
              $stepX1 = ($x3-$x1);
              $stepY1 = ($y3-$y1);
              $stepX2 = ($x4-$x2);
              $stepY2 = ($y4-$y2);
            } else {
              $microStepColorR = ($params[14]-$params[5])/($totalAnimationSteps-1);
              $microStepColorG = ($params[15]-$params[6])/($totalAnimationSteps-1);
              $microStepColorB = ($params[16]-$params[7])/($totalAnimationSteps-1);
              $stepX1 = ($x3-$x1)/($totalAnimationSteps-1);
              $stepY1 = ($y3-$y1)/($totalAnimationSteps-1);
              $stepX2 = ($x4-$x2)/($totalAnimationSteps-1);
              $stepY2 = ($y4-$y2)/($totalAnimationSteps-1);
            }
            $colorR = intval($params[5] + $microStepColorR * $animationStep);
            $colorG = intval($params[6] + $microStepColorG * $animationStep);
            $colorB = intval($params[7] + $microStepColorB * $animationStep);
            $lineColor = imagecolorallocate($im, $colorR, $colorG, $colorB);
            if ($params[9] > 0) {
              imagesetthickness($im, $params[9]);
            }
            $x1 = intval($zeroX + $unitPixelsValue*($x1+$stepX1*$animationStep));
            $y1 = intval($zeroY - $unitPixelsValue*($y1+$stepY1*$animationStep));
            $x2 = intval($zeroX + $unitPixelsValue*($x2+$stepX2*$animationStep));
            $y2 = intval($zeroY - $unitPixelsValue*($y2+$stepY2*$animationStep));

            if ($params[8]) {
              $this->drawDashedLine($im, $x1, $y1, $x2, $y2, $params[8], $params[8], $lineColor);
            } else {
              imageline($im, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $lineColor);
            }
          }
  
          if (substr($cmd, 0, 4) == 'axis') {
            $params = explode(',', $cmd);

            $printEvery = $params[25];
            if (!$printEvery)
              $printEvery = 1;

            $xMax = $params[1];
            $xMin = $params[2];
            $yMax = $params[3];
            $yMin = $params[4]; 

            if ((($xMax-$xMin)/($yMax-$yMin)) != ($this->width / $this->height)) {
              if (!$this->warningARsent) {
                echo "WARNING: Aspect ratio not matched! Axis will be drawn according to xMax/xMin!<br>";
                $this->warningARsent = true;
              }
              // recalculate yMax/yMin. The good proportion will be the one for the Xs...
              $xTot = $xMax-$xMin;
              $xPixel = $this->width / $xTot;
              $yTot = $yMax-$yMin;
              $yHowManyWillFit = $this->height / $xPixel;
              $newYMax = $yHowManyWillFit * $yMax / $yTot;
              $newYMin = $newYMax - $yHowManyWillFit;

              $yMax = $newYMax;
              $yMin = $newYMin;
            }

            // to convert from screen to math coordinates
            $unitPixelsValue = $this->width / ($xMax-$xMin);
            $zeroX  = (-$xMin * $this->width / ($xMax-$xMin)) ;
            $zeroY  = $this->height + ($yMin / ($yMax-$yMin)) * $this->height;
  
            $axisColor = imagecolorallocate($im, $params[5], $params[6], $params[7]);
            $axisWidth = intval($params[8]/2);
            $xTickPixel = $this->width/($xMax-$xMin); // length of a unit in pixel
            $yTickPixel = $this->height/($yMax-$yMin); // length of a unit in pixel

            $xCenter = $this->width/2+(($xMin+$xMax)/2)*$xTickPixel;
            $yCenter = $this->height/2+(($yMin+$yMax)/2)*$yTickPixel;

            $xCenter = $zeroX;
            $yCenter = $zeroY;

            imagefilledrectangle($im, 0, (int)($yCenter-$axisWidth), $this->width-1, (int)($yCenter+$axisWidth), $axisColor);
            imagefilledrectangle($im, (int)($xCenter-$axisWidth), 0, (int)($xCenter+$axisWidth), $this->height-1, $axisColor);
  
            // draw ticks and grid too if p20=='1'
            $tickLength = intval($params[10]/2);
            $tickWidth = intval($params[24]/2);
            $tickColor = imagecolorallocate($im, $params[11], $params[12], $params[13]);
            $gridColor = imagecolorallocate($im, $params[21], $params[22], $params[23]);
            $textColor = imagecolorallocate($im, $params[17], $params[18], $params[19]);
            $currentX = $xCenter; 
            for($xTick=1;$currentX < $this->width; $xTick++) {
              $currentX += $xTickPixel; 
              // draw the grid first, so that the tick will be drawn on top of it
              if (!($xTick % $printEvery)) {
                if ($params[20]=='1') {
                  imageline($im, (int)$currentX, 0, (int)$currentX, $this->height-1, $gridColor);
                }
                if ($params[16] > 0) {
                  // 14 L/R
                  // 15 T/B
                  list($llX, $llY, $lrX, $lrY, $urX, $urY, $ulX, $ulY) = imagettfbbox($params[16], 0, $this->fontName, intval($xTick*$params[9]));
                  if ($params[15] == 'B') {
                    imagettftext($im, $params[16], 0, (int)($currentX+$tickWidth+1), (int)(3+ $yCenter+$axisWidth+($lrY-$ulY)), $textColor, $this->fontName, (int)(intval($xTick*$params[9])));
                  } else {
                    imagettftext($im, $params[16], 0, $currentX+$tickWidth, $yCenter-$tickLength+1, $textColor, $this->fontName, intval($xTick*$params[9]));
                  }
                }
              imagefilledrectangle($im, (int)($currentX-$tickWidth), (int)($yCenter-$tickLength), (int)($currentX+$tickWidth), (int)($yCenter+$tickLength), $tickColor);
              }
            }
            $currentX = $xCenter; 
            for($xTick=1;$currentX > 0; $xTick++) {
              $currentX -= $xTickPixel; 
              if (!($xTick % $printEvery)) {
              // draw the grid first, so that the tick will be drawn on top of it
                if ($params[20]=='1') {
                  imageline($im, (int)($currentX), 0, (int)($currentX), $this->height-1, $gridColor);
                }
                if ($params[16] > 0) {
                  // 14 L/R
                  // 15 T/B
                  list($llX, $llY, $lrX, $lrY, $urX, $urY, $ulX, $ulY) = imagettfbbox($params[16], 0, $this->fontName, intval($xTick*$params[9]));
                  if ($params[15] == 'B') {
                    imagettftext($im, $params[16], 0, (int)($currentX+$tickWidth+1), (int)(3+ $yCenter+$axisWidth+($lrY-$ulY)), $textColor, $this->fontName, intval(-$xTick*$params[9]));
                  } else {
                    imagettftext($im, $params[16], 0, $currentX+$tickWidth, $yCenter-$tickLength+1, $textColor, $this->fontName, intval(-$xTick*$params[9]));
                  }
                }
              imagefilledrectangle($im, (int)($currentX-$tickWidth), (int)($yCenter-$tickLength), (int)($currentX+$tickWidth), (int)($yCenter+$tickLength), $tickColor);
              }
            }
            $currentY = $yCenter; 
            for($yTick=1;$currentY < $this->height; $yTick++) {
              $currentY += $yTickPixel; 
              if (!($yTick % $printEvery)) {
                if ($params[20]=='1') {
                  imageline($im, 0, (int)$currentY, $this->width-1, (int)$currentY, $gridColor);
                }
                if ($params[16] > 0) {
                  // 14 L/R
                  // 15 T/B
                  list($llX, $llY, $lrX, $lrY, $urX, $urY, $ulX, $ulY) = imagettfbbox($params[16], 0, $this->fontName, intval(-$yTick*$params[9]));
                  // echo "param14=? [{$params[14]}]<br>";
                  if ($params[14] == 'L') {
                    // echo "param14=L, $llY, $urY<br>";
                    imagettftext($im, $params[16], 0, (int)(-4+$xCenter-$tickLength-($urX-$llX)), (int)($currentY+intval(($llY-$urY)/2)), $textColor, $this->fontName, intval(-$yTick*$params[9]));
                  } else {
                    imagettftext($im, $params[16], 0, (int)(2+$xCenter+$tickLength), (int)($currentY+intval(($llY-$urY)/2)), $textColor, $this->fontName, intval(-$yTick*$params[9]));
                  }
                  imagefilledrectangle($im, (int)($xCenter-$tickLength), (int)($currentY-$tickWidth), (int)($xCenter+$tickLength), (int)($currentY+$tickWidth), $tickColor);
                }
              }
            }
            $currentY = $yCenter; 
            for($yTick=1;$currentY > 0; $yTick++) {
              $currentY -= $yTickPixel; 
              if (!($yTick % $printEvery)) {
                if ($params[20]=='1') {
                  imageline($im, 0, (int)$currentY, $this->width-1, (int)$currentY, $gridColor);
                }
                if ($params[16] > 0) {
                // 14 L/R
                // 15 T/B
                list($llX, $llY, $lrX, $lrY, $urX, $urY, $ulX, $ulY) = imagettfbbox($params[16], 0, $this->fontName, intval($yTick*$params[9]));
                if ($params[14] == 'L') {
                  imagettftext($im, $params[16], 0, (int)(-4+$xCenter-$tickLength-($urX-$llX)), (int)($currentY+intval(($llY-$urY)/2)), $textColor, $this->fontName, intval($yTick*$params[9]));
                } else {
                  imagettftext($im, $params[16], 0, (int)(2+$xCenter+$tickLength), (int)($currentY+intval(($llY-$urY)/2)), $textColor, $this->fontName, intval($yTick*$params[9]));
                }
                imagefilledrectangle($im, (int)($xCenter-$tickLength), (int)($currentY-$tickWidth), (int)($xCenter+$tickLength), (int)($currentY+$tickWidth), $tickColor);
                }
              }
            }
          } // axis
  
        } // element
        if ($this->debug) {
          // Allocate colors
          $backgroundColor = imagecolorallocate($im, 255, 255, 255); // White
          $textColor = imagecolorallocate($im, 0, 0, 0); // Black

          // Define text and font properties
          $fontSize = 20;
          $angle = 0;
          $x = 0;
          $y = 20;

          // Calculate text bounding box
          $text = sprintf("[%s, s:%d,f:%d]",$this->scenes[$scene]->name, $scene,$animationStep);
          $bbox = imagettfbbox($fontSize, $angle, $this->fontName, $text);
          $width = $bbox[2] - $bbox[0];
          $height = $bbox[1] - $bbox[7];

          // Draw a filled rectangle to "erase" the background
          imagefilledrectangle($im, $x, $y - $height, $x + $width, $y, $backgroundColor);

          // Render the text
          imagettftext($im, $fontSize, $angle, $x, $y, $textColor, $this->fontName, $text);         
        }
        imagepng($im, sprintf("{$this->imageFolder}/%06d.png", $frame++));
        imagedestroy($im);
      } // frame
    } // scene
  
  }

  function getLabelCoordinatesForTriangle($offset, $points) {
    $x1 = $points[0];
    $y1 = $points[1];
    $x2 = $points[2];
    $y2 = $points[3];
    $x3 = $points[4];
    $y3 = $points[5];
  
    // Calculate the centroid
    $centroid = [($x1 + $x2 + $x3) / 3, ($y1 + $y2 + $y3) / 3];
  
    // Calculate direction vectors from the centroid to each vertex
    $dir1 = [$centroid[0] - $x1, $centroid[1] - $y1];
    $dir2 = [$centroid[0] - $x2, $centroid[1] - $y2];
    $dir3 = [$centroid[0] - $x3, $centroid[1] - $y3];
    $x2 = $points[2];
  
    // Normalize directions
    $dir1Len = sqrt($dir1[0]*$dir1[0] + $dir1[1]*$dir1[1]);
    $dir2Len = sqrt($dir2[0]*$dir2[0] + $dir2[1]*$dir2[1]);
    $dir3Len = sqrt($dir3[0]*$dir3[0] + $dir3[1]*$dir3[1]);
  
    $dir1 = [$dir1[0] / $dir1Len, $dir1[1] / $dir1Len];
    $dir2 = [$dir2[0] / $dir2Len, $dir2[1] / $dir2Len];
    $dir3 = [$dir3[0] / $dir3Len, $dir3[1] / $dir3Len];
  
    // Compute offset points in the opposite direction (outside the triangle)
    $labelA = [$x1 - $offset * $dir1[0], $y1 - $offset * $dir1[1]];
    $labelB = [$x2 - $offset * $dir2[0], $y2 - $offset * $dir2[1]];
    $labelC = [$x3 - $offset * $dir3[0], $y3 - $offset * $dir3[1]];
  
    return [$labelA[0],$labelA[1], $labelB[0],$labelB[1], $labelC[0],$labelC[1]];
  }

  function getLabelCoordinates($offset, $points) {
    // Count the number of vertices
    $numVertices = count($points) / 2;
  
    // Calculate the centroid
    $centroid = [0, 0];
    for ($i = 0; $i < $numVertices; $i++) {
      $centroid[0] += $points[2 * $i];
      $centroid[1] += $points[2 * $i + 1];
    }
    $centroid[0] /= $numVertices;
    $centroid[1] /= $numVertices;
  
    // Initialize the array for label coordinates
    $labelCoordinates = [];
  
    // Calculate label coordinates for each vertex
    for ($i = 0; $i < $numVertices; $i++) {
      $x = $points[2 * $i];
      $y = $points[2 * $i + 1];
  
      // Calculate direction vector from the centroid to the vertex
      $dir = [$centroid[0] - $x, $centroid[1] - $y];
  
      // Normalize direction
      $dirLen = sqrt($dir[0]*$dir[0] + $dir[1]*$dir[1]);
      $dir = [$dir[0] / $dirLen, $dir[1] / $dirLen];
  
      // Compute offset point in the opposite direction (outside the shape)
      $labelX = $x - $offset * $dir[0];
      $labelY = $y - $offset * $dir[1];
  
      // Add label coordinates to the array
      array_push($labelCoordinates, $labelX, $labelY);
    }
  
    return $labelCoordinates;
  }
    
  function toString() {
    for($i=0; $i<count($this->scenes); ++$i) {
      echo "Scene $i [".$this->scenes[$i]->name."]<br>";
      for($j=0; $j<$this->scenes[$i]->howManyElements(); ++$j) {
        echo "El$j=".$this->scenes[$i]->getElement($j)."<br>";
      }
    }
  }

  function writeScript($file, $type) {
    file_put_contents($file, "ffmpeg -r {$this->fps} -s {$this->width}x{$this->height} -i {$this->imageFolder}/%%06d.png -vcodec libx264 -crf 25 output.mp4");    
    if ($type == ZB_MP4_WHATSAPP) {
      file_put_contents($file, "\nffmpeg -i output.mp4 -c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p whatsapp.mp4", FILE_APPEND);    
    }

  }
}

// Offical tried DOC!!!
// circle: circle, x, y, radius, colorx3, colorfillx3, DF
// animCircle: animCircle, x, y, radius, colorx[4..6], colorfill[7..9], DF, finalX, finalY, finalRadius color[14..16], bkg[17..19]
// axis: xmax, xmin, ymax, ymin, color[5..7], width, [9], tickLength[10], tickColor[11.13], Top/Bottom[14],Left/Right[15], fontsize[16], textcolor[17..19], writethegrid 0/1[20], gridcolor[21..23], tickWidth[24], printEveryTotValue
// axis WILL AUTOMATICALLY FIND THE CORRECT ASPECT RATIO, if it's not the same. It will keep the "x" valid, and it will adapt the "y"
// polygon: polygon color[1..3] filledColor[4..6], DF[7], mustUseLetters/firstletter if yes[8], size[9], letterColor[10..12], transp[13] (0/100: 100=totally trasparent), [x1,y1,x2,y2...]
// animPolygon: animPoligon Startcolor[1..3], StartBkg[4..6], EndColor[7..9], EndBkg[10..12],'DF', quanto in totale si deve spostare sulle x, quanto in totale si deve spostare sulle y, quanto in totale in gradi deve ruotare CCW (per CW, mettere gradi negativi, attenzione, giro completo mettere 359.999 o 360.001, se si mette 360, non si muove per ovvi motivi trigonometrici), [x1,y1,x2,y2...]
// animPolygonOffset: animPoligonOffset fulcroX, fulcroY, Startcolor[3..5], StartBkg[6..8], EndColor[9..11], EndBkg[12..14],'DF', quanto in totale si deve spostare sulle x, quanto in totale si deve spostare sulle y, quanto in totale in gradi deve ruotare CCW (per CW, mettere gradi negativi, attenzione, giro completo mettere 359.999 o 360.001, se si mette 360, non si muove per ovvi motivi trigonometrici), [x1,y1,x2,y2...]
// animLine, x1,y1,x2,y2,coloreIniziale, pixel per dash (se 0, non dashed), width, x3,y3,x4,y4,colore finale  
// text,x1,y1,x2,y2, text, [0,0,0],[0,0,255], startsize,endsize, startTrasp, endTrasp, fontfile
// to PAUSE the video for "xxx" frames, use $scene = $g->newScene(xxx, null, true, 0, 5);
// funcTable x1,y1,x2,y2, func, colorFunction, colorFillCircle, colorBorderCirle, radiusCircle, [array xvalues]



$g = new zbPHPAnimation(25, 1920, 1080, "250,250,250", "logo.png", 5, 'img');
$scene = $g->newScene(500, false, false, 0, 5);
$g->newElementInScene($scene, "axis,100,-100,100,-5, $BLACK,6,1,20,$RED,L,B,25, $BLUE, 1, 192,192,255,5,10");
// $g->newElementInScene($scene, "animLine,-5,5,-5,-5,192,192,192,0,5,-4,-4,6,-4,0,0,255");
// $g->newElementInScene($scene, "animOffsetPolygon,1,1,0,0,255,128,128,128,0,0,0,0,0,255,DF,0,0,359.99,1,1,3,3,5,1");
$g->setFont('monomials.ttf', 30);
$g->newElementInScene($scene, "funcTable,-90,100,-40,5,ZioBit\\zbPHPAnimation\\parabolic,$BLACK,$YELLOW,$BLACK,1.5,x = %3d   y = %3d,-11,-10,-9,-8,-7,-6,-5,-4,-3,-2,-1,0,1,2,3,4,5,6,7,8,9,10,11");

// TODO copy 'functable' with the name 'drawfunc' that only draws the graph, with no table, and maybe with no circles but pixel by pixel all the functions from/to

$g->toString();
$g->draw();
$g->writeScript('createfiles.bat', ZB_MP4_WHATSAPP);

