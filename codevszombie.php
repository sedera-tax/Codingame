<?php
/**
 * Save humans, destroy zombies!
 **/

class Point
{
    public $x;
    public $y;
    function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    function distance(Point $p) {
        return abs($this->x - $p->x) + abs($this->y - $p->y);
    }

    function getBestHumanPosition($humans) {
        $distance = 99999999;
        $best = NULL;
        foreach($humans as $human) {
            $calc = $this->distance($human->getPoint());
            if ($distance > $calc) {
                $distance = $calc;
                $best = $human->getPoint();
            }
        }

        return $best;
    }
}

class Human
{
    public $humanId;
    public $point;
    function __construct($humanId, $point) {
        $this->humanId = $humanId;
        $this->point = $point;
    }

    function getPoint() {
        return $this->point;
    }
}

class Zombie
{
    public $zombieId;
    public $point;
    public $pointNext;
    function __construct($zombieId, $point, $pointNext) {
        $this->zombieId = $zombieId;
        $this->point = $point;
        $this->pointNext = $pointNext;
    }

    function getPoint() {
        return $this->point;
    }
}

class Game
{
    public $playerPos;
    public $humanPos;
    public $humans;
    public $zombies;

    function resetHumansAndZombies() {
        $this->humans = [];
        $this->zombies = [];
    }

    function getPlayerPos() {
        return $this->playerPos;
    }

    function getHumanPos() {
        return $this->humanPos;
    }

    function getHumans() {
        return $this->humans;
    }

    function getZombies() {
        return $this->zombies;
    }

    function setPlayerPos($playerPos) {
        $this->playerPos = $playerPos;
    }

    function setHumanNearestPos($humanPos) {
        $this->humanPos = $humanPos;
    }

    function setHumans($humans = []) {
        $this->humans = $humans;
    }

    function setZombies($zombies = []) {
        $this->zombies = $zombies;
    }

    function addHuman($human) {
        $this->humans[] = $human;
    }

    function addZombie($zombie) {
        $this->zombies[] = $zombie;
    }

    function play() {
        $humanPos = $this->getHumanPos();
        $zombies = $this->getZombies();
        $nearZombie = NULL;
        $distance = 999999;
        foreach($zombies as $z) {
            $calc = $humanPos->distance($z->getPoint());
            if ($distance > $calc && $calc > 500) {
                $distance = $calc;
                $nearZombie = $z->getPoint();
            }
        }

        //if ($nearZombie)

        return $nearZombie;
    }
}
$game = new Game();
// game loop
while (TRUE)
{
    fscanf(STDIN, "%d %d", $x, $y);
    $me = new Point($x, $y);
    $game->setPlayerPos($me);
    $game->resetHumansAndZombies();

    fscanf(STDIN, "%d", $humanCount);
    for ($i = 0; $i < $humanCount; $i++)
    {
        fscanf(STDIN, "%d %d %d", $humanId, $humanX, $humanY);
        $human = new Human($humanId, new Point($humanX, $humanY));
        $game->addHuman($human);
    }
    $bestHumanNearest = $me->getBestHumanPosition($game->getHumans());
    $game->setHumanNearestPos($bestHumanNearest);

    fscanf(STDIN, "%d", $zombieCount);
    for ($i = 0; $i < $zombieCount; $i++)
    {
        fscanf(STDIN, "%d %d %d %d %d", $zombieId, $zombieX, $zombieY, $zombieXNext, $zombieYNext);
        $zombie = new Zombie($zombieId, new Point($zombieX, $zombieY), new Point($zombieXNext, $zombieYNext));
        $game->addZombie($zombie);
    }

    $nearZombie = $game->play();
    if ($nearZombie != NULL) {
        $posX = $nearZombie->x;
        $posY = $nearZombie->y;
        echo("$posX $posY\n");
    } else {
        //Go to Nearest Zombie of Me
        $bestZombieNearest = $me->getBestHumanPosition($zombies);
        if ($bestZombieNearest != NULL) {
            $posX = $bestZombieNearest->x;
            $posY = $bestZombieNearest->y;
            echo("$posX $posY\n");
        } else {
            echo("$zombieX $zombieY\n");
        }
    }

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)
}
?>