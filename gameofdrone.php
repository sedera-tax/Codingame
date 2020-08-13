<?php
class Game
{
    public $nbPlayers;
    public $idMyPlayer;
    public $nbDrones;
    public $nbZones;

    public $zones = [];
    public $myDrones = [];

    public function __construct($P, $ID, $D, $Z)
    {
        $this->nbPlayers = $P;
        $this->idMyPlayer = $ID;
        $this->nbDrones = $D;
        $this->nbZones = $Z;
    }

    public function addZone(Point $point)
    {
        $this->zones[] = $point;
    }

    function getZones()
    {
        return $this->zones;
    }

    public function addMyDrone(Point $point)
    {
        $this->myDrones[] = $point;
    }

    function getMyDrones()
    {
        return $this->myDrones;
    }

    function initPositionPlayer()
    {
        for ($i = 0; $i < $this->nbPlayers; $i++)
        {
            for ($j = 0; $j < $this->nbDrones; $j++)
            {
                // $DX: The first D lines contain the coordinates of drones of a player with the ID 0, the following D lines those of the drones of player 1, and thus it continues until the last player.
                fscanf(STDIN, "%d %d", $DX, $DY);

                if ($i == $this->idMyPlayer)
                {
                    $this->myDrones[$j] = new Point($DX, $DY);
                }
            }
        }
    }

    function play()
    {
        for ($i = 0; $i < $this->nbDrones; $i++)
        {

            // Write an action using echo(). DON'T FORGET THE TRAILING \n
            // To debug: error_log(var_export($var, true)); (equivalent to var_dump)

            $zone = $this->getNearestZoneByDrone($this->myDrones[$i]);
            $X = $zone->getX();
            $Y = $zone->getY();
            // output a destination point to be reached by one of your drones. The first line corresponds to the first of your drones that you were provided as input, the next to the second, etc.
            echo("$X $Y\n");
        }
    }

    function getNearestZoneByDrone(Point $point)
    {
        $zone = new Point(9999999999, 9999999999);
        $distance = 9999999999;

        foreach($this->getZones() as $z)
        {
            $calc = $point->getDistance($z);
            if ($calc < $distance)
            {
                $distance = $calc;
                $zone = $z;
            }
        }

        return $zone;
    }
}

class Point
{
    private $x;
    private $y;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX()
    {
        return $this->x;
    }

    public function setX($x)
    {
        $this->x = $x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setY($y)
    {
        $this->y = $y;
    }

    public function distance(Point $target)
    {
        return abs($target->getX() - $this->getX()) + abs($target->getY() - $this->getY());
    }

    public function getDistance(Point $target)
    {
        return sqrt(pow($target->getX() - $this->getX(), 2) + pow($target->getY() - $this->getY(), 2));
    }
}

// $P: number of players in the game (2 to 4 players)
// $ID: ID of your player (0, 1, 2, or 3)
// $D: number of drones in each team (3 to 11)
// $Z: number of zones on the map (4 to 8)
fscanf(STDIN, "%d %d %d %d", $P, $ID, $D, $Z);
$game = new Game($P, $ID, $D, $Z);
for ($i = 0; $i < $game->nbZones; $i++)
{
    // $X: corresponds to the position of the center of a zone. A zone is a circle with a radius of 100 units.
    fscanf(STDIN, "%d %d", $X, $Y);
    $game->addZone(new Point($X, $Y));
}

// game loop
while (TRUE)
{
    for ($i = 0; $i < $game->nbZones; $i++)
    {
        // $TID: ID of the team controlling the zone (0, 1, 2, or 3) or -1 if it is not controlled. The zones are given in the same order as in the initialization.
        fscanf(STDIN, "%d", $TID);
    }
    
    $game->initPositionPlayer();

    $game->play();
}
?>