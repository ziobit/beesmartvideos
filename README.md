# beesmartvideos
An internal tool I used to create Math videos for Thai high school students, after realizing the available tools were too hard to learn.
It's written in PHP and it needs ffmpeg.exe (or whatever equivalent you have on Linux (https://www.ffmpeg.org/download.html).
Please MODIFY the maximum execution time in php.ini because the default 30s is usually not enough.

This is still in its early infancy and still only for personal use, but it's usable, it works, and if someone needs a simple Math graph video creator, give it a try.
As usual, docs are the last things written, but you can start from the examples.

This is how it works (at least, in the initial release):

- An animation is made up of scenes
- You can add several type of objects to each scene
- You tell the class to create the video
- What it actually does, according to the setup parameters, is to create a sequence of PNG
- You can use a class member to create a batch (.bat) file to call ffmpeg, to transform all the PNG in a single MP4
- You have the option to create a MP4 compatible with WhatsApp too (it uses a simpler codec)

# Your first animation
Just run it, you can check the values of the parameters later, in the next example
```
$g = new zbPHPAnimation(25, 1920, 1080, "250,250,250", "logo.png", 5, 'img');
$scene = $g->newScene(10, false, false, 0, 5);
$g->newElementInScene($scene, "axis,10,-10,5.625,-5.625, 0,0,0,6,1,20,255,0,0,L,B,25, 0,0,255, 1, 192,192,255,5");
$g->newElementInScene($scene, "animPolygon,0,0,255,128,128,128,0,0,0,0,0,255,DF,-2,-4,180,1,1,3,3,5,1");
$g->newElementInScene($scene, "animLine,-5,5,-5,-5,192,192,192,0,5,-4,-4,6,-4,0,0,255");
$g->draw();
$g->writeScript('anim1.bat', ZB_MP4_WHATSAPP);
```
Once is done, click on the "anim1.bat" file, and you will have output.mp4 and whatsapp.mp4 file ready to use.

# A more complex example
```
// First, create the animation object
$g = new zbPHPAnimation(
      25,             // the number of frames per second. It will generate 25 PNGs per second 
      1920,           // width resolution of the animation frame
      1080,           // height resolution of the animation frame
      "250,250,250",  // RGB background color
      "logo.png",     // background picture
      5,              // background picture transparency (0-100%, 100 meaning fully opaque)
      'img'           // folder in which to save the image. DO NOT USE '.', because it will erase all your PNG in the current directory
    );

// Then, create the first scene
$scene = $g->newScene(
          500,          // duration in frames
          false,        // if it's zoomed - NOT IMPLEMENTED YET
          false,        // if true, it will not erase the previous scene
          0,            // delay before starting
          5             // Default Line Thickness for the whole scene (can be changed later)
      );

// Create the axis
$g->newElementInScene($scene, "axis,100,-100,100,-5, $BLACK,6,1,20,$RED,L,B,25, $BLUE, 1, 192,192,255,5,10");
/*
          $scene,     // the scene in which we are adding the axis
          "           // it must be a string. I was planning to use JSON but to code it faster I simply use "explode" with "," to extract the params. I know. I should use JSON before it's too late and there are too many things to change
            axis,     // this is the type of element we are adding
            100,          // xmax
            -100,          // xmin
            100,          // ymax
            -5,           // ymin
            $BLACK,        // axis color
            6,            // width in pixel
            1,            // ?
            20,            // tickLength in pixel
            $RED,          // tickColor
            L,            // top/bottom
            B,            // left/right
            25,           // fontSize
            $BLUE,         // numbers color
            1,             // draw the grid 0/1
            192,192,255,  // grid color
            5,            // tick Width
            10            // print values on axis only every "n" numbers
          ");
*/

$g->setFont('monomials.ttf', 30);

// create a function table for y=x^2 and draw it 
$g->newElementInScene($scene, "funcTable,-90,100,-40,5,ZioBit\\zbPHPAnimation\\parabolic,$BLACK,$YELLOW,$BLACK,1.5,x = %3d   y = %3d,-11,-10,-9,-8,-7,-6,-5,-4,-3,-2,-1,0,1,2,3,4,5,6,7,8,9,10,11");

/*
  $scene,     // the scene in which we are adding the axis
  "            // see above
  funcTable,  // we are adding a function table
  -90,        // x1 in cartesian coordinates        
  100,        // y1 in cartesian coordinates
  -40,        // x2 in cartesian coordinates
  5,          // y2 in cartesian coordinates
  ZioBit\\zbPHPAnimation\\parabolic,  // function to be called to calculate 'y'
  $BLACK,      // color of the function
  $YELLOW,    // color of the fill for the circles (the 'points' of the function)
  $BLACK,     // color of the border of the circle
  1.5,        // radius of the circle in pixel
  x = %3d   y = %3d,  // format of the data printed in the table
  -11,-10,-9,-8,-7,-6,-5,-4,-3,-2,-1,0,1,2,3,4,5,6,7,8,9,10,11  // all the xvalues for which we want to draw the function
  ");
*/

// This will crate the PNGs
$g->draw();

// and this will create the batch file to call ffmpeg
$g->writeScript('createfiles.bat', ZB_MP4_WHATSAPP);
```

# Warning
Depending on your machine, it will generate around 5 frames per second (on my lapton, Ryzen 7).
So please CHANGE your PHP timeout: 30s, the default value, is usually not enough.
ffmpeg, on the other hand, it's fairly fast (average 5x, so if you have 250 frames, 10 seconds, it will take 2 seconds)
