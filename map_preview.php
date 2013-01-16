<?php
class Wall
{
    var $points = array();
}

class Zone
{
    var $x;
    var $y;
    var $radius;
    var $color;
}

class Spawn
{
    var $x;
    var $y;
    var $xdir;
    var $ydir;
    var $angle = false;
}

/* Leave these values alone! */
$minx = 10000;
$maxx = -10000;
$miny = 10000;
$maxy = -10000;
/**/

$margin = 8;
$stroke = 2;	//	the thickness of the walls
$factor = 1.15;	//	similar to size_factor

//$arrow = array(-5,0, -5,20, -15,20, 0,40, 15,20 ,5,20, 5,0);
$arrow = array(15,0, 15,20, 5,20, 20,40, 35,20 ,25,20, 25,0);

$wallList = array();
$zoneList = array();
$spawnList = array();

$wallPointsX = array();
$wallPointsY = array();

//$map = "zonetest-0.1.0.aamap.xml";
//$map = "example-1.0.0.aamap.xml";
$map = "Crossfire-1.0.0.aamap.xml";
//$map = "Alternative_Worlds-1.0.0.aamap.xml"; 

$dom = new DOMDocument;
$dom->load($map);

//	let's get data from the walls
$walls = $dom->getElementsByTagName('Wall');
if (!is_null($walls))
{
    foreach($walls as $wall)
    {
        $newWall = new Wall();
        $points = $wall->getElementsByTagName('Point');

        if (!is_null($points))
        {
            foreach($points as $point)
            {
                $xData = $yData = 0;

                $xData = $point->getAttribute("x") * $factor;	
                $yData -= $point->getAttribute("y") * $factor;

                $newWall->points[] = $xData + 4;
                $newWall->points[] = $yData + 4;

                $wallPointsX[] = $xData;
                $wallPointsY[] = $yData;
            }
        }
        $wallList[] = $newWall;
    }
}

//	let's get data from the zones
$zones = $dom->getElementsByTagName('Zone');
if (!is_null($zones))
{
    foreach($zones as $zone)
    {
        $newZone = new Zone();
        $color = "0x00ffa500";

        //	fetch what kind of zone it is
        $effect = $zone->getAttribute('effect');
        if (!is_null($effect) && $effect == 'win')
        $color = '0x0000ff00';
        elseif(!is_null($effect) && $effect == 'death')
        $color = '0x00ff0000';
        elseif(!is_null($effect) && $effect == 'flag')
        $color = '0x000000ff';
        elseif(!is_null($effect) && $effect == 'ball')
        $color = '0x00aa5500';
        elseif(!is_null($effect) && $effect == 'rubber')
        $color = '0x00ffdd00';
        elseif(!is_null($effect) && $effect == 'target')
        $color = '0x0000ff99';
        elseif(!is_null($effect) && $effect == 'teleport')
        $color = '0x000055ff';

        $shapes = $zone->getElementsByTagName('ShapeCircle');
        if (!is_null($shapes))
        {
            foreach($shapes as $shape)
            {
                $radius = $shape->getAttribute('radius');
                $points = $shape->getElementsByTagName('Point');

                $x = $y = 0;
                foreach($points as $point)
                {					
                    $x = $point->getAttribute('x')  * $factor + 4;
                    $y -= $point->getAttribute('y') * $factor - 4;
                }

                $newZone->x = $x;
                $newZone->y = $y;
                $newZone->color = $color;
                $newZone->radius = $radius * 2 * $factor;

                $zoneList[] = $newZone;

                $wallPointsX[] = $x - 20;
                $wallPointsY[] = $y;
            }
        }
    }
}

//	let's get the data from the spawn points
$spawns = $dom->getElementsByTagName('Spawn');
if (!is_null($spawns))
{
    foreach($spawns as $spawn)
    {
        $newSpawn = new Spawn();

        $x = $y = 0;

        $x = $spawn->getAttribute('x')  * $factor + 4;
        $y -= $spawn->getAttribute('y') * $factor + 4;

        $xdir = $spawn->getAttribute('xdir');
        $ydir = $spawn->getAttribute('ydir');

        $newSpawn->x = $x;
        $newSpawn->y = $y;
        $newSpawn->xdir = $xdir;
        $newSpawn->ydir = $ydir;

        if ($spawn->hasAttribute('angle'))
            $newSpawn->angle = $spawn->getAttribute('angle');

        $spawnList[] = $newSpawn;

        $wallPointsX[] = $x;
        $wallPointsY[] = $y;
    }
}

//	adjusting the width and height of image frame
$minx = min($wallPointsX);
$maxx = max($wallPointsX);
$miny = min($wallPointsY);
$maxy = max($wallPointsY);

//	start creating the image
$img = imagecreatetruecolor($maxx - $minx + $margin, $maxy - $miny + $margin);

//	fill the background of the image
imagefill($img, 0, 0, "0x00ffffff");

//	set the thickness of the lines (doesn't apply to zones)
imagesetthickness($img, $stroke);

//	let's draw the walls in the image
if (count($wallList) > 0)
{
    foreach($wallList as $selWall)
    {
        if ($selWall)
        {
            $num_points = (count($selWall->points) / 2);

            //	let's draw the line
            if ($num_points > 1)
            {
                $xSet = false;

                $xs1 = $selWall->points[0] - $minx;
                $ys1 = $selWall->points[1] - $miny;

                for ($i = 2; $i < count($selWall->points); $i++)
                {
                    $x1 = $y1 = 0;

                    $x1 = $selWall->points[$i] 		- $minx;
                    $y1 = $selWall->points[$i + 1] 	- $miny;

                    $i += 1;

                    imageline($img, $xs1, $ys1, $x1, $y1, "0x00000000");

                    $xs1 = $x1;
                    $ys1 = $y1;
                }
            }
        }
    }
}

//	let's draw the zones in the image
if (count($zoneList) > 0)
{
    foreach($zoneList as $selZone)
    {
        $x = $y = 0;
        if ($selZone)
        {			
            $x = $selZone->x - $minx;
            $y = $selZone->y - $miny;

            imagearc($img, $x, $y, $selZone->radius, $selZone->radius, 0, 180, $selZone->color);
            imagearc($img, $x, $y, $selZone->radius, $selZone->radius, 180, 360, $selZone->color);
        }
    }
}

//	let's draw the spawn arrows
if (count($spawnList) > 0)
{
    foreach ($spawnList as $selSpawn)
    {
        $angle = $x = $y = $xdir = $ydir = 0;
        $spawnPoints = array();		
        if ($selSpawn)
        {
            $x = $selSpawn->x - $minx;
            $y = $selSpawn->y - $miny;
            $xdir = $selSpawn->xdir;
            $ydir -= $selSpawn->ydir;

            //	get the angle of the spawn
            if ($selSpawn->angle != false)
                $angle = $selSpawn->angle;

            if(!($xdir == 0 && $ydir == 0))
                $angle = rad2deg(atan2($ydir, $xdir));


            $angle = 360 - $angle + 90;

            $xSpots = $ySpots = array();

            //	let's get to work
            for($i = 0; $i < count($arrow); $i++)
            {
                //$arrow[] = $x + (($arrows[$i] * 0.4));
                //$arrow[] = $y + (($arrows[$i + 1] * 0.4)) - 8;

                $newX = $arrow[$i] * 0.4;
                $newY = $arrow[$i + 1] * 0.4;

                $spawnPoints[] = $newX;
                $spawnPoints[] = $newY;

                $xSpots[] = $newX;
                $ySpots[] = $newY;

                $i += 1;
            }

            //	finally draw the spawn shaped arrow
            $spawnArrow = imagecreatetruecolor(max($xSpots), max($ySpots));
            imagefilledpolygon($spawnArrow, $spawnPoints, count($spawnPoints) / 2, "0x00ff0000");
            $spawnArrow = imagerotate($spawnArrow, $angle, 0);
            imagecopymerge($img, $spawnArrow, $x, $y, 0, 0, max($xSpots) + 6, max($ySpots), 300);
            imagedestroy($spawnArrow);
        }
    }
}

//	smoothen the image
imagefilter($img, IMG_FILTER_SMOOTH, 10);

//	finish off by saving the image
imagepng($img, "image.png");
imagedestroy($img);

//	load image file onto webpage
echo '<img src="./image.png" />';
?>