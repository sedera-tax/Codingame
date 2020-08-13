<?php
class GameSet
{
    public static $map;
    public static $elevators;
    public static $player;

    public static function initialise() {
        // $nbFloors: number of floors
        // $width: width of the area
        // $nbRounds: maximum number of rounds
        // $exitFloor: floor on which the exit is found
        // $exitPos: position of the exit on its floor
        // $nbTotalClones: number of generated clones
        // $nbAdditionalElevators: ignore (always zero)
        // $nbElevators: number of elevators
        fscanf(STDIN, "%d %d %d %d %d %d %d %d", $nbFloors, $width, $nbRounds, $exitFloor, $exitPos, $nbTotalClones, $nbAdditionalElevators, $nbElevators);
        self::$map = new Map($nbFloors, $width, $nbRounds, $exitFloor, $exitPos, $nbTotalClones, $nbAdditionalElevators, $nbElevators);
        
        self::$elevators = [];
        for ($i = 0; $i < $nbElevators; $i++)
        {
            // $elevatorFloor: floor on which this elevator is found
            // $elevatorPos: position of the elevator on its floor
            fscanf(STDIN, "%d %d", $elevatorFloor, $elevatorPos);
            self::$elevators[] = new Elevator(new Localisation($elevatorFloor, $elevatorPos));
            //error_log(var_export(new Localisation($elevatorFloor, $elevatorPos), true));
        }
        self::$map->setElevators(self::$elevators);
        self::$map->orderElevators();
    }

    public static function initClone() {
        // $cloneFloor: floor of the leading clone
        // $clonePos: position of the leading clone on its floor
        // $direction: direction of the leading clone: LEFT or RIGHT
        fscanf(STDIN, "%d %d %s", $cloneFloor, $clonePos, $direction);
        self::$player = new Player(new Localisation($cloneFloor, $clonePos), $direction);
        self::$player->setMap(self::$map);
    }

    public static function play() {
        self::$player->play();
    }
}

class Map
{
    public $nbFloors;
    public $width;
    public $nbRounds;
    public $exitFloor;
    public $exitPos;
    public $nbTotalClones;
    public $nbAdditionalElevators;
    public $nbElevators;

    public $elevators;

    function __construct($nbFloors, $width, $nbRounds, $exitFloor, $exitPos, $nbTotalClones, $nbAdditionalElevators, $nbElevators) {
        $this->nbFloors = $nbFloors;
        $this->width = $width;
        $this->nbRounds = $nbRounds;
        $this->exitFloor = $exitFloor;
        $this->exitPos = $exitPos;
        $this->nbTotalClones = $nbTotalClones;
        $this->nbAdditionalElevators = $nbAdditionalElevators;
        $this->nbElevators = $nbElevators;
    }

    function getWidth() {
        return $this->width;
    }

    function getNbFloors() {
        return $this->nbFloors;
    }

    function getExitFloor() {
        return $this->exitFloor;
    }

    function getExitPos() {
        return $this->exitPos;
    }

    function getElevators() {
        return $this->elevators;
    }

    function setElevators($elevators) {
        $this->elevators = $elevators;
    }

    function orderElevators() {
        $elevators = $this->getElevators();
        for ($i = 0; $i < count($elevators) - 1; $i++) {
            for ($j = 0; $j < count($elevators) - 1 - $i; $j++) {
                if ($elevators[$j]->getLocalisation()->getFloor() > $elevators[$j+1]->getLocalisation()->getFloor() ) {
                    $temp = $elevators[$j+1];
                    $elevators[$j+1] = $elevators[$j];
                    $elevators[$j] = $temp;
                }
            }
        }
        $this->setElevators($elevators);
        return $elevators;
    }
}

class Localisation
{
    public $floor;
    public $pos;

    function __construct($floor, $pos) {
        $this->floor = $floor;
        $this->pos = $pos;
    }

    function getFloor() {
        return $this->floor;
    }

    function getPos() {
        return $this->pos;
    }

    function getDistance($pos) {
        return abs($this->getPos() - $pos);
    }
}
class Elevator 
{
    public $localisation;

    function __construct(Localisation $localisation) {
        $this->localisation = $localisation;
    }

    function getLocalisation() {
        return $this->localisation;
    }
}

class Player
{
    public $localisation;
    public $direction;

    public $map;
    public $elevators = [];

    public function __construct(Localisation $localisation, $direction) {
        $this->localisation = $localisation;
        $this->direction = $direction;
    }

    public function getLocalisation() {
        return $this->localisation;
    }

    public function getMap() {
        return $this->map;
    }

    public function setMap(Map $map) {
        $this->map = $map;
    }

    public function setDirection($direction = 'RIGHT') {
        $this->direction = $direction;
    }

    public function getDirection() {
        return $this->direction;
    }

    public function play() {
        if ($this->getLocalisation()->getFloor() < $this->getMap()->getNbFloors()) {
            if (GameSet::$elevators != NULL) {
                $elevator = isset($this->getMap()->getElevators()[$this->getLocalisation()->getFloor()]) ? $this->getMap()->getElevators()[$this->getLocalisation()->getFloor()] : NULL;
                if ($elevator != NULL) {
                    if ($this->getLocalisation()->getPos() < $elevator->getLocalisation()->getPos() && $this->getDirection() == 'LEFT') {
                        $this->block();
                    } elseif ($this->getLocalisation()->getPos() > $elevator->getLocalisation()->getPos() && $this->getDirection() == 'RIGHT') {
                        $this->block();
                    } else {
                        $this->wait();
                    }
                }
                else {
                    if ($this->getLocalisation()->getPos() < $this->getMap()->getExitPos() && $this->getDirection() == 'LEFT') {
                        $this->block();
                    } elseif ($this->getLocalisation()->getPos() > $this->getMap()->getExitPos() && $this->getDirection() == 'RIGHT') {
                        $this->block();
                    } else {
                        $this->wait();
                    }   
                }
            } else {
                if ($this->getLocalisation()->getPos() < $this->getMap()->getExitPos() && $this->getDirection() == 'LEFT') {
                    $this->block();
                } elseif ($this->getLocalisation()->getPos() > $this->getMap()->getExitPos() && $this->getDirection() == 'RIGHT') {
                    $this->block();
                } else {
                    $this->wait();
                }
            }
        }
        else {
            if ($this->getLocalisation()->getPos() < $this->getMap()->getExitPos() && $this->getDirection() == 'LEFT') {
                $this->block();
            } elseif ($this->getLocalisation()->getPos() > $this->getMap()->getExitPos() && $this->getDirection() == 'RIGHT') {
                $this->block();
            } else {
                $this->wait();
            }
        }
    }

    public function wait()
    {
        echo("WAIT\n");
    }

    public function block()
    {
        echo("BLOCK\n");
    }
}

GameSet::initialise();
// game loop
while (TRUE)
{
    GameSet::initClone();
    GameSet::play();
    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)

    //echo("WAIT\n"); // action: WAIT or BLOCK
}
?>