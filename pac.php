<?php
/**
 * Grab the pellets as fast as you can!
 **/

class GameSet {
    public static $width;
    public static $height;
    public static $row;
    public static $map;

    public static $me;
    public static $enemy;
    public static $point;

    /**
     * @var Pellet[]
     */
    public static $aPellets;

    public static $myScore;
    public static $opponentScore;
    public static $visiblePacCount;

    //Pac
    public static $pacId;
    public static $mine;
    public static $xPac;
    public static $yPac;
    public static $typeId;
    public static $speedTurnsLeft;
    public static $abilityCooldown;

    public static $visiblePelletCount;

    //Pellet
    public static $xPellet;
    public static $yPellet;
    public static $value;

    public static function initialise() {
        self::$me = new Player();
        self::$enemy = new Player();

        // $width: size of the grid
        // $height: top left corner is (x=0, y=0)
        fscanf(STDIN, "%d %d", self::$width, self::$height);
        $elemMap = [];
        for ($i = 0; $i < self::$height; $i++)
        {
            self::$row = stream_get_line(STDIN, self::$width + 1, "\n");// one line of the grid: space " " is floor, pound "#" is wall
            $elemMap[] = self::$row;
        }
        self::$map = new Map(self::$width, self::$height, $elemMap);
        self::$me->setMap(self::$map);
        self::$enemy->setMap(self::$map);

        self::$me->setEnemy(self::$enemy);
        self::$enemy->setEnemy(self::$me);
    }

    public static function initInfos()
    {
        fscanf(STDIN, "%d %d", self::$myScore, self::$opponentScore);
        fscanf(STDIN, "%d", self::$visiblePacCount);
        self::initPac();
        // $visiblePelletCount: all pellets in sight
        fscanf(STDIN, "%d", self::$visiblePelletCount);
        self::initPellet();
    }

    public static function initPac()
    {
        self::$me->resetPacs();
        self::$enemy->resetPacs();
        for ($i = 0; $i < self::$visiblePacCount; $i++)
        {
            // $pacId: pac number (unique within a team)
            // $mine: true if this pac is yours
            // $x: position in the grid
            // $y: position in the grid
            // $typeId: unused in wood leagues
            // $speedTurnsLeft: unused in wood leagues
            // $abilityCooldown: unused in wood leagues
            fscanf(STDIN, "%d %d %d %d %s %d %d", self::$pacId, self::$mine, self::$xPac, self::$yPac, self::$typeId, self::$speedTurnsLeft, self::$abilityCooldown);
            if (self::$mine)
            {
                self::$me->addPac(new Pac(self::$pacId, self::$mine, self::$xPac, self::$yPac, self::$typeId, self::$speedTurnsLeft, self::$abilityCooldown));
            } else {
                self::$enemy->addPac(new Pac(self::$pacId, self::$mine, self::$xPac, self::$yPac, self::$typeId, self::$speedTurnsLeft, self::$abilityCooldown));
            }
        }
    }

    public static function initPellet()
    {
        //self::$map->resetPellets();
        self::$aPellets = [];
        for ($i = 0; $i < self::$visiblePelletCount; $i++)
        {
            // $value: amount of points this pellet is worth
            fscanf(STDIN, "%d %d %d", self::$xPellet, self::$yPellet, self::$value);
            if (self::$xPellet != NULL && self::$yPellet != NULL && self::$value > 0)
            {
                self::$point = new Point(self::$xPellet, self::$yPellet);
                self::$aPellets[] = new Pellet(self::$point, self::$value);
                //self::$map->addPellet(self::$point, self::$value);
            }
        }
        self::$map->updatePellets(self::$aPellets, self::$me->getPacs());
    }

    public static function play() {
        self::$me->play();
    }
}

class Direction
{
    public $dX;
    public $dY;
    public $name;

    public static $directions = [];

    public function __construct($dX, $dY, $name)
    {
        $this->dX = $dX;
        $this->dY = $dY;
        $this->name = $name;

        self::$directions = [
            new Direction(-1, 0, "W"),
            new Direction(0, 1, "E"),
            new Direction(0, 1, "N"),
            new Direction(0, -1, "S")
        ];
    }

    public static function getDirection($dir) {
        foreach (self::$directions as $d) {
            if ($d->name == $dir) {
                return $d;
            }
        }
        return NULL;
    }

    public function isOpposite(Direction $direction) {
        if ($direction->name == "N") {
            return $this->name = "S";
        }
        elseif ($direction->name == "S") {
            return $this->name = "N";
        }
        elseif ($direction->name == "W") {
            return $this->name = "E";
        }
        elseif ($direction->name == "E") {
            return $this->name = "W";
        }
        return false;
    }

    public static function checkDirectionOriginToTarget(Point $origin, Point $target) {
        $i = "";$j = "";
        $x0 = $origin->getX();
        $y0 = $origin->getY();
        if ($x0 == $target->getX()) {
            if ($y0 > $target->getY()) {
                $i = "N";
            } elseif ($y0 < $target->getY()) {
                $i = "S";
            }
        }
        elseif ($x0 < $target->getX()) {
            $j = "E";
            if ($y0 > $target->getY()) {
                $i = "N";
            } elseif ($y0 < $target->getY()) {
                $i = "S";
            }
        }
        elseif ($x0 > $target->getX()) {
            $j = "W";
            if ($y0 > $target->getY()) {
                $i = "N";
            } elseif ($y0 < $target->getY()) {
                $i = "S";
            }
        }

        return $i . $j;
    }
}

class Map
{
    private $width;
    private $height;

    private $map;
    private $pellets;

    public function __construct($width, $height, $aMap = [])
    {
        $this->width = $width;
        $this->height = $height;

        $map = [];
        $pellets = [];
        for ($i = 0; $i < sizeOf($aMap); $i++) {
            $row = $aMap[$i];
            for ($j = 0; $j < strlen($row); $j++) {
                $map[$i][$j] = $row{$j} == "#" ? 1 : 0;
                $pellets[$i][$j] = $map[$i][$j] ? 0 : 1;
            }
        }
        $this->map = $map;
        $this->pellets = $pellets;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    function addPellet(Point $point, $value)
    {
        $this->pellets[$point->getY()][$point->getX()] = $value;
    }

    public function isCellFree(Point $point) {
        if ($point->getX() < 0 || $point->getY() < 0 || $point->getX() >= $this->width || $point->getY() >= $this->$height) {
            return false;
        }
        return !$this->map[$point->getX()][$point->getY()];
    }

    public function getPellets() {
        return $this->pellets;
    }

    public function getBestPelletsPosition() {
        $points = [];
        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                if ($this->pellets[$i][$j] == 10) {
                    $points[] = new Point($j, $i);
                }
            }
        }
        return $points;
    }

    public function updatePellets($visiblePellets, $aPlayers) {
        $this->updateBestPellets($visiblePellets);

        $visibleCells = [];
        foreach ($aPlayers as $player) {
            $visibleCells = array_merge($visibleCells, $this->getVisibleCells($player->getPosition()));
        }

        foreach ($visibleCells as $point) {
            $hasPellet = false;
            foreach ($visiblePellets as $pellet) {
                if ($point->getX() == $pellet->getX() && $point->getY() == $pellet->getY()) {
                    $hasPellet = true;
                    break;
                }
            }

            if (!$hasPellet) {
                $this->pellets[$point->getY()][$point->getX()] = 0;
            }
        }
    }

    public function getVisibleCells(Point $point) {
        $visibleCells = NULL;
        $visibleCells[] = $point;
        foreach (Direction::$directions as $dir) {
            for($i = 1; $i > 0; $i++) {
                $target = Point::getPointInDirection($point, $dir, $i);
                if (!$this->isCellFree($target)) {
                    break;
                }
                $visibleCells[] = $target;
            }
        }
        return $visibleCells;
    }

    function updateBestPellets($visiblePellets) {
        foreach ($visiblePellets as $pellet) {
            if ($pellet->getValue() == 10) {
                $this->pellets[$pellet->getPosition()->getY()][$pellet->getPosition()->getX()] = 10;
            }
        }

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                if ($this->pellets[$i][$j] == 10) {
                    $boolExist = false;
                    foreach ($visiblePellets as $pellet) {
                        if ($pellet->getValue() == 10 && $pellet->getPosition() == new Point($j, $i)) {
                            $boolExist = true;
                        }
                    }

                    if (!$boolExist) {
                        $this->pellets[$i][$j] = 0;
                    }
                }
            }
        }
    }

    function resetPellets()
    {
        for ($i = 0; $i < sizeof($this->map); $i++) {
            for ($j = 0; $j < count($this->map[$i]); $j++) {
                $this->pellets[$i][$j] = 0;
            }
        }
    }

    function getObstacles($obstaclesPoints)
    {
        $mapWithObstacles = Utils::copyGrid($this->map);
        foreach ($obstaclesPoints as $point) {
            $mapWithObstacles[$point->getY()][$point->getX()] = true;
        }
        return $mapWithObstacles;
    }

    function getNextPellet(Point $point, $obstaclesPacs)
    {
        $mapWithObstacles = $this->getObstacles($obstaclesPacs);

        $closestPellet = NULL;
        $distance = '';

        /*$bestPelletsPos = $this->getBestPelletsPosition();
        foreach ($bestPelletsPos as $p) {
            $p = new Point($p->getX(), $p->getY());
            //$currentDistance = $point->bfsDistance($p, $this->map);
            $currentDistance = $point->bfsDistance($p, $mapWithObstacles);
            if ($distance != '')
            {
                //if ($distance > $point->distance($p)) {
                if ($distance > $currentDistance) {
                    //$distance = $point->distance($p);
                    $distance = $currentDistance;
                    $closestPellet = $p;
                }
            } else {
                //$distance = $point->distance($p);
                $distance = $currentDistance;
                $closestPellet = $p;
            }
        }*/

        $closestPellet = $point->getManyPointLinear(GameSet::$aPellets, $this->width);

        if ($closestPellet == NULL) {
            for ($i = 0; $i < $this->height; $i++)
            {
                for ($j = 0; $j < $this->width; $j++) {
                    if ($this->pellets[$i][$j] == 1) {
                        $p = new Point($j, $i);
                        //$currentDistance = $point->bfsDistance($p, $this->map);
                        //$currentDistance = $point->bfsDistance($p, $mapWithObstacles);
                        //$currentDistance = $point->bfsDistanceBFS($p, $mapWithObstacles);
                        //error_log(var_export("tsotsotra=".$currentDistance, true));
                        if ($distance != '')
                        {
                            if ($distance > $point->distance($p)) {
                                //if ($distance > $currentDistance) {
                                $distance = $point->distance($p);
                                //$distance = $currentDistance;
                                $closestPellet = $p;
                            }
                        } else {
                            $distance = $point->distance($p);
                            //$distance = $currentDistance;
                            $closestPellet = $p;
                        }
                    }
                }
            }
        }

        if ($closestPellet == NULL) {
            $x = rand(0, $this->width - 1);
            $y = rand(0, $this->height - 1);
            $closestPellet = new Point($x, $y);
            error_log(var_export("nisy pellet 1pt = ".$x."--------".$y, true));
        }

        return $closestPellet;
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

    public function getPosition() {
        return $this;
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

    public function getManyPointLinear($pellets = [], $width) {
        $p = NULL;
        $countTop = 0; $pointTop = NULL;
        $countDown = 0; $pointDown = NULL;
        $countLeft = 0; $pointLeft = NULL;
        $countRight = 0; $pointRight = NULL;

        foreach ($pellets as $pellet) {
            $point = $pellet->getPosition();
            if ($this->getX() == $point->getX()) {
                $y = $this->getY() - $point->getY();
                if ($y > 0) {
                    //TOP
                    if ($pointTop != NULL) {
                        $countTop++;
                        if ($pointTop->getY() - $point->getY() > 0) {
                            $pointTop = $point;
                        }
                    } else {
                        $countTop++;
                        $pointTop = $point;
                    }
                }
                else {
                    //DOWN
                    if ($pointDown != NULL) {
                        $countDown++;
                        if ($pointDown->getY() - $point->getY() < 0) {
                            $pointDown = $point;
                        }
                    } else {
                        $countDown++;
                        $pointDown = $point;
                    }
                }
            }

            if ($this->getY() == $point->getY()) {
                //LEFT OR RIGHT
                for($i = 1; $i <= 10; $i++) {
                    $xLeft = ($this->getX() + $width - $i) % $width;
                    $xRight = ($this->getX() + $width + $i) % $width;

                    if ($point->getX() == $xLeft) {
                        //LEFT
                        $countLeft++;
                        if ($pointLeft != NULL) {
                            if ($pointLeft->getX() > $point->getX()) {
                                $pointLeft = $point;
                            }
                        } else {
                            $pointLeft = $point;
                        }
                        break;
                    }

                    if ($point->getX() == $xRight) {
                        //RIGHT
                        $countRight++;
                        if ($pointRight != NULL) {
                            if ($pointRight->getX() < $point->getX()) {
                                $pointRight = $point;
                            }
                        } else {
                            $pointRight = $point;
                        }
                        break;
                    }
                }

                /*$x = $this->getX() - $point->getX();
                if ($x > 0) {
                    //LEFT
                    if ($pointLeft != NULL) {
                        $countLeft++;

                        if ($pointLeft->getX() - $point->getX() > 0) {
                            $pointLeft = $point;
                        }
                    } else {
                        $countLeft++;
                        $pointLeft = $point;
                    }
                }
                else {
                    //RIGHT
                    if ($pointRight != NULL) {
                        $countRight++;
                        if ($pointRight->getX() - $point->getX() < 0) {
                            $pointRight = $point;
                        }
                    } else {
                        $countRight++;
                        $pointRight = $point;
                    }
                }*/
            }
        }

        $max = max($countTop, $countDown, $countLeft, $countRight);
        if ($max > 0) {
            if ($max == $countTop) {
                $p = $pointTop;
            }
            elseif ($max == $countDown) {
                $p = $pointDown;
            }
            elseif ($max == $countLeft) {
                $p = $pointLeft;
            }
            elseif ($max == $countRight) {
                $p = $pointRight;
            }
        }

        error_log(var_export("Top=".$countTop." Down=".$countDown." Left".$countLeft." Right".$countRight, true));
        error_log(var_export($pointTop, true));
        error_log(var_export($pointDown, true));
        error_log(var_export($pointLeft, true));
        error_log(var_export($pointRight, true));
        error_log(var_export("-------MAX---------", true));

        return $p;
    }

    public function getNextPoint($target) {
        $directionName = Direction::checkDirectionOriginToTarget($this, $target);
        $x = $this->getX();
        $y = $this->getY();
        switch ($directionName) {
            case 'N':
                $y -= 1;
                break;
            case 'S':
                $y += 1;
                break;
            case 'W':
                $x -= 1;
                break;
            case 'E':
                $x += 1;
                break;
            case 'NW':
                $y -= 1;
                $x -= 1;
                break;
            case 'NE':
                $y -= 1;
                $x += 1;
                break;
            case 'SW':
                $y += 1;
                $x -= 1;
                break;
            case 'SE':
                $y += 1;
                $x += 1;
                break;
        }
        return new Point($x, $y);
    }

    public static function getPointInDirection(Point $point, Direction $dir, $distance)
    {
        return new Point($point->getX() + ($dir->dX * $distance), $point->getY() + ($dir->dY * $distance));
    }

    public function bfsDistance(Point $target, $bMap)
    {
        $node = $this->bfs_path_yannick($bMap, $this->getPosition(), $target);
        if ($node != false) {
            return count($node);
        }
        return false;
    }

    public function bfsDistanceBFS(Point $target, $bMap)
    {
        $node = BFS::getPathBFS($bMap, $this->getPosition(), $target);
        error_log(var_export($node, true));
        if ($node != NULL) {
            return $node->getPathLength();
        }
        return false;
    }

    public function neighbours($map) {
        $res = [];
        $p1 = new Point($this->getX(), $this->getY()-1);
        $p2 = new Point($this->getX()+1, $this->getY());
        $p3 = new Point($this->getX(), $this->getY()+1);
        $p4 = new Point($this->getX()-1, $this->getY());

        if (Point::isFree($map, $p1->getX(), $p1->getY())) {
            $res[] = $p1;
        }
        if (Point::isFree($map, $p2->getX(), $p2->getY())) {
            $res[] = $p2;
        }
        if (Point::isFree($map, $p3->getX(), $p3->getY())) {
            $res[] = $p3;
        }
        if (Point::isFree($map, $p4->getX(), $p4->getY())) {
            $res[] = $p4;
        }

        return $res;
    }

    static function bfs_path_yannick($graph, $start, $end)
    {
        if (!Point::isFree($graph, $end->getX(), $end->getY())) {
            return NULL;
        }

        $queue = new SplQueue();
        $graph = Utils::copyGrid($graph);
        $graph[$start->getY()][$start->getX()] = true;
        # Enqueue the path
        $queue->enqueue([$start]);
        $visited = [$start];

        while ($queue->count() > 0) {
            $path = $queue->dequeue();

            # Get the last node on the path
            # so we can check if we're at the end
            $node = $path[sizeof($path) - 1];

            if ($node->getX() == $end->getX() && $node->getY() == $end->getY()) {
                return $path;
            }

            $neighbours = $node->neighbours($graph);

            if (count($neighbours) > 0) {
                foreach ($neighbours as $key => $neighbour) {
                    if (!Point::isFree($graph, $neighbour->getX(), $neighbour->getY())) {
                        unset($neighbours[$key]);
                    }
                    else {
                        if (!in_array($neighbour, $visited) && $graph[$neighbour->getY()][$neighbour->getX()] == 0) {
                            $visited[] = $neighbour;
                            $graph[$neighbour->getY()][$neighbour->getX()] = true;

                            # Build new path appending the neighbour then and enqueue it
                            $new_path = $path;
                            $new_path[] = $neighbour;

                            $queue->enqueue($new_path);
                        }
                    }
                }
            }

            if (count($neighbours) == 0) {
                break;
            }
        }

        return false;
    }

    public static function isFree($graph, $x, $y)
    {
        if (($y >= 0 && $y < sizeof($graph)) && ($x >= 0 && $x < sizeof($graph[$y]))) {
            return true;
        }
        return false;
    }
}

class Utils
{
    public static function copyGrid($source) {
        $res = [];
        for ($i = 0; $i < sizeOf($source); $i++) {
            for ($j = 0; $j < sizeOf($source[$i]); $j++) {
                $res[$i][$j] = $source[$i][$j];
            }
        }
        return $res;
    }
}

class Player
{
    /**
     * @var Pac[]
     */
    public $pacs = [];
    public $map;
    public $enemy = [];

    public function resetPacs() {
        $this->pacs = [];
    }

    public function addPac(Pac $pac)
    {
        $pac->setMap($this->map);
        $pac->setPacPlayer($this);
        $this->pacs[] = $pac;
    }

    public function setMap($map)
    {
        $this->map = $map;
    }

    public function getEnemy()
    {
        return $this->enemy;
    }

    public function getPacs()
    {
        return $this->pacs;
    }

    public function setEnemy($enemy)
    {
        $this->enemy = $enemy;
    }

    public function play() {
        $this->findClosestPacsToBestPellets();
        foreach ($this->pacs as $pac) {
            $pac->play();
        }
        echo("\n");
    }

    public function findClosestPacsToBestPellets() {
        $pelletsPos = $this->map->getBestPelletsPosition();
        $allPacs = array_merge($this->pacs, $this->enemy->getPacs());
        foreach ($pelletsPos as $pelletPos) {
            $closestPac = NULL;
            $minDist = 1000;
            foreach ($allPacs as $pac) {
                $currentDist = $pac->getPosition()->bfsDistance($pelletPos, $this->map->getObstacles($this->enemy->getPacs()));
                if ($closestPac == NULL || $minDist > $currentDist) {
                    $closestPac = $pac;
                    $minDist = $currentDist;
                }
            }

            if ($closestPac != NULL && $closestPac->isMine()) {
                $closestPac->setNextPosition($pelletPos);
            }
        }
    }
}

class Pac extends Point
{
    public $pacId;
    public $mine;
    public $typeId;
    public $speedTurnsLeft;
    public $abilityCooldown;

    public $map;
    public $pacPlayer;
    public $position;
    public $boolPlayed = false;
    public $nextPosition = NULL;
    public $enemyPacs = [];

    public function __construct($pacId, $mine, $x, $y, $typeId, $speedTurnsLeft, $abilityCooldown)
    {
        parent::__construct($x, $y);
        $this->pacId = $pacId;
        $this->mine = $mine;
        $this->typeId = $typeId;
        $this->speedTurnsLeft = $speedTurnsLeft;
        $this->abilityCooldown = $abilityCooldown;

        //Decrement
        //$this->decrementeSpeedTurnsLeft();
        //$this->decrementeAbilityCooldown();
        $this->position = parent::getPosition();
    }

    public function setPacPlayer($pacPlayer)
    {
        $this->pacPlayer = $pacPlayer;
    }

    public function setMap(Map $map) {
        $this->map = $map;
    }

    public function getPosition() {
        return new Point($this->getX(), $this->getY());
    }

    public function getNextPosition()
    {
        return $this->nextPosition;
    }

    public function setNextPosition($nextPosition)
    {
        $this->nextPosition = $nextPosition;
    }


    public function play() {

        /*if ($this->nextPosition != NULL && !$this->boolPlayed) {
            $this->moveTo($this->nextPosition);
            error_log(var_export("eo foana", true));
            return;
        }*/
        $this->computeEnemyDistances();
        $this->checkToAttack();
        $this->checkToGoSpeed();

        $this->move();
    }

    public function move() {
        if ($this->boolPlayed) {
            return;
        }

        $othersPacs = [];
        foreach ($this->pacPlayer->getPacs() as $pac) {
            if ($pac->getPacId() != $this->getPacId()) {
                $othersPacs[] = $pac->getPosition();
            }
        }

        foreach ($this->pacPlayer->getEnemy()->getPacs() as $pac) {
            $nextPosition = $pac->getNextPosition();
            if ($nextPosition != NULL) {
                $othersPacs[] = $pac->getNextPosition();
            } else {
                $othersPacs[] = $pac->getPosition();
            }
        }

        if ($this->nextPosition != NULL) {
            error_log(var_export("io", true));
            $this->moveTo($this->nextPosition);
            return;
        }

        $target = $this->map->getNextPellet($this->position, $othersPacs);
        if ($target != NULL) {
            $this->setNextPosition($target);
            $myAllPacs = $this->pacPlayer->getPacs();
            foreach($myAllPacs as $myPac) {
                if ($myPac->getPacId() != $this->getPacId()) {
                    $dist = $this->getPosition()->distance($myPac->getPosition());
                    if ($dist <= 2) {
                        if ($this->getPosition()->distance($target) > $myPac->getPosition()->distance($target)) {
                            return;
                        }
                        elseif ($this->getPosition()->distance($target) < $myPac->getPosition()->distance($target)) {
                            //Move to target
                        }
                        else {
                            $x = rand($this->getPosition()->getX(), $this->map->getWidth()-1);
                            $y = rand($this->getPosition()->getY(), $this->map->getHeight()-1);
                            $target = new Point($x, $y);
                            error_log(var_export("niova".$x."///".$y, true));
                            $this->nextPosition = NULL;
                        }
                    }
                }
            }

            //$path = Point::bfs_path_yannick($this->map->getObstacles($othersPacs), $this->position, $target);
            error_log(var_export($this->getPacId()."---------target-----------".$target->getX()."--".$target->getY(), true));
            $this->moveTo($target, 1);
        }
    }

    public function moveTo(Point $point, $nextPos = NULL) {
        if ($nextPos != NULL) {
            //$this->nextPosition = $this->getNextPoint($point);
            //error_log(var_export("Next position : ", true));
            //error_log(var_export($this->getNextPosition(), true));
        }
        echo $this->executeCommand("MOVE", $point->getX(), $point->getY());
    }

    public function checkToGoSpeed() {
        if ($this->getAbilityCooldown() == 0 && !$this->boolPlayed) {
            $this->speed();
        }
        return;
    }

    public function speed() {
        error_log(var_export("speed be", true));
        echo $this->executeCommand("SPEED");
        $this->boolPlayed = true;
    }

    public function checkToAttack() {
        error_log(var_export("-------------------".$this->getPacId()."--------------------", true));
        if (!$this->boolPlayed) {
            $enemyPac = $this->getClosestEnemy();
            if ($enemyPac == NULL) {
                return;
            }

            $distance = $this->getPosition()->distance($enemyPac->getPosition());
            if ($distance <= 3) {error_log(var_export("attack distance = ".$distance, true));
                error_log(var_export("fahavalo NextP", true));
                error_log(var_export($enemyPac->getPosition(), true));
                if (!$this->checkTypeIfChangeOrNot($enemyPac)) {
                    if ($this->getAbilityCooldown() == 0) {
                        $this->speed();
                    }
                    else {
                        return;
                        $this->moveTo($enemyPac->getPosition());
                    }
                    $this->boolPlayed = true;
                } else {
                    if ($distance <= 2) {
                        if (!$this->checkTypeIfChangeOrNot($enemyPac)) {
                            if ($this->getAbilityCooldown() == 0) {
                                $this->speed();
                            } else {
                                $this->moveTo($enemyPac->getPosition());
                            }
                        }
                        else {
                            if ($this->getAbilityCooldown() == 0) {
                                $this->goSwitch($enemyPac);
                            }
                            else {
                                if ($this->getType() == $enemyPac->getType()) {
                                    $x = rand(0, $this->map->getWidth() - 1);
                                    $y = rand(0, $this->map->getHeight() - 1);
                                    error_log(var_export("tsy olana = ".$x."-".$y, true));
                                    $this->moveTo(new Point($x, $y));
                                } else {
                                    error_log(var_export($this->getPosition(), true));
                                    if ($this->getPosition()->getX() > $enemyPac->getPosition()->getX()) {
                                        $x = rand($this->getPosition()->getX(), $this->map->getWidth() - 1);
                                        $y = rand(0, $this->map->getHeight() - 1);
                                    }
                                    else {
                                        $x = rand(0, $this->getPosition()->getX());
                                        $y = rand(0, $this->map->getHeight() - 1);
                                    }
                                    error_log(var_export("fa ahoana = ".$x."-".$y, true));
                                    $this->moveTo(new Point($x, $y));
                                }

                            }
                        }
                    }
                    else {
                        if ($this->getAbilityCooldown() == 0) {
                            $this->goSwitch($enemyPac);
                        }
                        else {
                            //move away
                            error_log(var_export("retour", true));
                            return;
                        }
                    }
                    $this->boolPlayed = true;
                }
            }
            error_log(var_export("tsy misy attack", true));
        }
        error_log(var_export("-------FIN---------".$this->getPacId()."---------FIN----------", true));
        return;
    }

    public function goSwitch($oppositePac) {
        $this->setTypeDependsOpposite($oppositePac);
        echo $this->executeCommand("SWITCH");
        $this->boolPlayed = true;
    }

    public function computeEnemyDistances() {
        foreach ($this->pacPlayer->getEnemy()->getPacs() as $pac) {
            $this->enemyPacs[] = [
                'pac' => $pac,
                'distance' => $this->getPosition()->distance($pac->getPosition())
            ];
        }
        return $this->enemyPacs;
    }

    public function getClosestEnemy() {
        $closestPac = NULL;
        $minDist = 100000;
        foreach ($this->enemyPacs as $pac) {
            if ($closestPac == NULL || $minDist > $pac["distance"]) {
                $closestPac = $pac["pac"];
                $minDist = $pac["distance"];
            }
        }
        return $closestPac;
    }

    public function getPacId()
    {
        return $this->pacId;
    }

    public function isMine()
    {
        return $this->mine;
    }

    public function getType()
    {
        return $this->typeId;
    }

    public function setType($type)
    {
        $this->typeId = $type;
    }

    /**
     * @param Pac $pPac
     * @return bool
     */
    public function checkTypeIfChangeOrNot(Pac $pPac)
    {
        $oppositeType = $pPac->getType();
        $myType = $this->getType();
        switch ($oppositeType) {
            case "ROCK":
                if ($myType == "PAPER") { return false;}
                else {return true;}
                break;

            case "PAPER":
                if ($myType == "SCISSORS") { return false;}
                else {return true;}
                break;

            case "SCISSORS":
                if ($myType == "ROCK") { return false;}
                else {return true;}
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * RULES
     * @param Pac $pPac
     */
    public function setTypeDependsOpposite(Pac $pPac)
    {
        switch ($pPac->getType()) {
            case "ROCK":
                $this->typeId = "PAPER";
                break;

            case "PAPER":
                $this->typeId = "SCISSORS";
                break;

            case "SCISSORS":
                $this->typeId = "ROCK";
                break;
        }
    }

    public function getAbilityCooldown()
    {
        return $this->abilityCooldown;
    }

    public function decrementeAbilityCoolDown()
    {
        $this->abilityCooldown = ($this->getAbilityCooldown() > 0) ? $this->getAbilityCooldown() - 1 : $this->getAbilityCooldown();
    }

    public function getSpeedTurnsLeft()
    {
        return $this->speedTurnsLeft;
    }

    public function decrementeSpeedTurnsLeft()
    {
        $this->speedTurnsLeft = ($this->getSpeedTurnsLeft() > 0) ? $this->getSpeedTurnsLeft() - 1 : $this->getSpeedTurnsLeft();
    }

    /**
     * @param string $action
     * @param string $x
     * @param string $y
     * @return string
     */
    public function executeCommand($action = 'MOVE', $x = '', $y = '')
    {
        $command = "";
        if ($action == 'MOVE') {
            if ($x !== '' && $y !== '') {
                $command = $action . " " . $this->getPacId() . " " . $x . " " . $y;
                error_log(var_export("command=", true));
                error_log(var_export($command, true));
            }
        }
        elseif ($action == 'SPEED') {   //Capacity
            $command = $action . " " . $this->getPacId();
        }
        elseif ($action == 'SWITCH') {  //capacity
            $command = $action . " " . $this->getPacId() . " " . $this->getType();
        }

        if ($command != "")
            $command .= "|";

        return $command;
    }

    public function findNearBy($aPacs = []) {
        $list = [];
        $oldDist = '';
        foreach ($aPacs as $pac) {
            if ($this->getPacId() != $pac->getPacId() && $this->getDistance($pac) <= 2) {
                $distance = $this->getDistance($pac);
                if ($oldDist != '') {
                    if (round($distance, 2) < round($oldDist, 2)) {
                        $pac->distance = round($distance, 2);
                        $list[0] = $pac;
                    }
                } else {
                    $pac->distance = $distance;
                    $list[0] = $pac;
                }
            }
        }
        return $list;
    }
}

class Pellet extends Point
{
    public $position;
    public $value;

    /**
     * Pellet constructor.
     * @param $value
     */
    public function __construct(Point $position, $value)
    {
        $this->position = $position;
        $this->value = $value;
    }

    /**
     * @return Point
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param Point $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


}

class Node
{
    public $x;
    public $y;
    public $parent;

    public function __construct($x, $y, $parent)
    {
        $this->x = $x;
        $this->y = $y;
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getPathLength()
    {
        $p = $this;
        $result = 0;
        while ($p->getParent() != NULL) {
            $p = $p->getParent();
            $result++;
        }

        return $result;
    }

    public function getNextPoint()
    {
        $point = new Point($this->x, $this->y);
        $parent = $this;
        while ($parent->getParent() != NULL) {
            $point = new Point($parent->x, $parent->y);
            $parent = $parent->getParent();
        }

        return $point;
    }

    public function getSecondNextPoint()
    {
        $point = new Point($this->x, $this->y);
        $parent = $this;
        while ($parent->getParent() != NULL && $parent->getParent()->getParent() != NULL) {
            $point = new Point($parent->x, $parent->y);
            $parent = $parent->getParent();
        }

        return $point;
    }
}

class BFS
{
    static function getPathBFS($graph, $start, $end)
    {
        $width = sizeof($graph[0]);

        if (!BFS::isFree($graph, $end->getX(), $end->getY())) {
            return NULL;
        }

        $queue = new SplQueue();
        $graph = Utils::copyGrid($graph);
        $graph[$start->getY()][$start->getX()] = true;
        # Enqueue the path
        //$queue->enqueue([$start]);
        //$visited = [$start];
        $queue->enqueue(new Node($start->getX(), $start->getY(), NULL));

        while ($queue->count() > 0) {
            $path = $queue->dequeue();

            if ($path->x == $end->getX() && $path->y == $end->getY())
            {
                error_log(var_export($path, true));
                return $path;
            }

            $x = ($path->x + $width + 1) % $width;
            if (BFS::isFree($graph, $x, $path->y)) {
                $graph[$path->y][$x] = true;
                $nextP = new Node($x, $path->y, $path);
                $queue->enqueue($nextP);
            }

            $x = ($path->x + $width - 1) % $width;
            if (BFS::isFree($graph, $x, $path->y)) {
                $graph[$path->y][$x - 1] = true;
                $nextP = new Node($x, $path->y, $path);
                $queue->enqueue($nextP);
            }

            $x = ($path->x + $width) % $width;
            if (BFS::isFree($graph, $x, $path->y + 1)) {
                $graph[$path->y + 1][$x] = true;
                $nextP = new Node($x, $path->y + 1, $path);
                $queue->enqueue($nextP);
            }

            $x = ($path->x + $width) % $width;
            if (BFS::isFree($graph, $x, $path->y - 1)) {
                $graph[$path->y - 1][$x] = true;
                $nextP = new Node($x, $path->y - 1, $path);
                $queue->enqueue($nextP);
            }
            error_log(var_export("count=".$queue->count(), true));
        }

        return NULL;
    }

    public static function isFree($graph, $x, $y)
    {
        if (($y >= 0 && $y < sizeof($graph)) && ($x >= 0 && $x < sizeof($graph[0]))) {
            return true;
        }
        return false;
    }
}

GameSet::initialise();
// game loop
while (TRUE)
{
    GameSet::initInfos();
    GameSet::play();
    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)
}
?>