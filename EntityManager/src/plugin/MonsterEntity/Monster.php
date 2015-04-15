<?php

namespace plugin\MonsterEntity;

use pocketmine\entity\Entity;
use pocketmine\entity\Monster as MonsterEntity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

abstract class Monster extends MonsterEntity{

    protected $moveTime = 0;
    protected $bombTime = 0;
    protected $attackDelay = 0;
    /** @var Entity|null */
    protected $attacker = null;
    /** @var Vector3 */
    protected $target = null;

    private $damage = [];
    private $entityMovement = true;

    public abstract function updateTick();

    public function getDamage(){
        return $this->damage;
    }

    public function setDamage($damage, $difficulty = 1){
        if(is_array($damage)) $this->damage = $damage;
        elseif($difficulty >= 1 && $difficulty <= 3) $this->damage[(int) $difficulty] = (float) $damage;
    }

    public function isMovement(){
        return $this->entityMovement;
    }

    public function setMovement($value){
        $this->entityMovement = (bool) $value;
    }

    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new AddEntityPacket();
        $pk->eid = $this->getID();
        $pk->type = static::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = 0;
        $pk->speedY = 0;
        $pk->speedZ = 0;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);
    }

    public function updateMovement(){
        $this->lastX = $this->x;
        $this->lastY = $this->y;
        $this->lastZ = $this->z;
        $this->lastYaw = $this->yaw;
        $this->lastPitch = $this->pitch;

        foreach($this->hasSpawned as $player) $player->addEntityMovement($this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch, $this->yaw);
    }
	public function getData(){ //TODO
		$flags = 0;
		$flags |= $this->fireTicks > 0 ? 1 : 0;
		//$flags |= ($this->crouched === true ? 0b10:0) << 1;
		//$flags |= ($this->inAction === true ? 0b10000:0);
		$d = [
			0 => ["type" => 0, "value" => $flags],
			1 => ["type" => 1, "value" => $this->airTicks],
			16 => ["type" => 0, "value" => 0],
			17 => ["type" => 6, "value" => [0, 0, 0]],
		];

		return $d;
	}

    public function attack($damage, $source = EntityDamageEvent::CAUSE_MAGIC){
        if($this->attacker instanceof Entity) return;
        $health = $this->getHealth();
        parent::attack($damage, $source);
        if($source instanceof EntityDamageByEntityEvent and ($health - $damage) == $this->getHealth()){
            $this->moveTime = 100;
            $this->attacker = $source->getDamager();
        }
    }

    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){}

    public function move($dx, $dz, $dy = 0){
        if($this->onGround === false && $this->lastX !== null && $dy === 0){
            $this->motionY -= $this->gravity;
            $dy = $this->motionY;
        }
		$movX = $dx;
		$movY = $dy;
		$movZ = $dz;
		$list = $this->level->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz));
        foreach($list as $bb){
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);
        foreach($list as $bb){
			$dx = $bb->calculateXOffset($this->boundingBox, $dx);
		}
        $this->boundingBox->offset($dx, 0, 0);
        foreach($list as $bb){
            $dz = $bb->calculateZOffset($this->boundingBox, $dz);
        }
        $this->boundingBox->offset(0, 0, $dz);
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);

		$this->isCollidedVertically = $movY != $dy;
		$this->isCollidedHorizontally = ($movX != $dx or $movZ != $dz);
		$this->isCollided = ($this->isCollidedHorizontally or $this->isCollidedVertically);
		$this->onGround = ($movY != $dy and $movY < 0);
        if($this->onGround) $this->motionY = 0;
		$this->updateFallState($dy, $this->onGround);
    }

    public function knockBackCheck(){
        if(!$this->attacker instanceof Entity) return false;

        if($this->moveTime > 5) $this->moveTime = 5;
		$this->moveTime--;
        $target = $this->attacker;
        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;
        $atn = atan2($z, $x);
        $this->move(-cos($atn) * 0.38, -sin($atn) * 0.38, 0.42);
        $this->setRotation(rad2deg(atan2($z, $x) - M_PI_2), rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
        if($this->moveTime <= 0) $this->attacker = null;
        $this->entityBaseTick();
        $this->updateMovement();
        return true;
    }

    /**
     * @return Player|Vector3
     */
    public function getTarget(){
        if(!$this->isMovement()) return new Vector3();
        $target = null;
        $nearDistance = PHP_INT_MAX;
        foreach($this->getViewers() as $p){
            if(($distance = $this->distanceSquared($p)) <= 81 and $p->spawned and $p->isSurvival() and $p->dead == false and !$p->closed){
                if($distance < $nearDistance){
                    $target = $p;
                    $nearDistance = $distance;
                    continue;
                }
            }
        }
        if($target instanceof Player){
            return $target;
        }elseif($this->moveTime >= mt_rand(650, 800) or ($target === null and !$this->target instanceof Vector3)){
            $this->moveTime = 0;
            return $this->target = new Vector3($this->x + mt_rand(-100, 100), $this->y, $this->z + mt_rand(-100,100));
        }
        return $this->target;
    }

}
