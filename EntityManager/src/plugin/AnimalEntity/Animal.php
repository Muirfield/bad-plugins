<?php

namespace plugin\AnimalEntity;

use pocketmine\entity\Animal as AnimalEntity;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;

abstract class Animal extends AnimalEntity{

    protected $moveTime = 0;
    /** @var Vector3 */
    protected $target = null;
    /** @var Entity|null */
    protected $attacker = null;

    private $entityMovement = true;

    /**
     * @return Player|Vector3
     */
    public abstract function getTarget();
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
        $pk->metadata = $this->getData();//$this->dataProperties;
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

    public function isMovement(){
        return $this->entityMovement;
    }

    public function setMovement($value){
        $this->entityMovement = (bool) $value;
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
            $dx = $bb->calculateXOffset($this->boundingBox, $dx);
        }
        foreach($list as $bb){
            $dz = $bb->calculateZOffset($this->boundingBox, $dz);
        }
        foreach($list as $bb){
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset($dx, 0, 0);
        $this->boundingBox->offset(0, $dy, 0);
        $this->boundingBox->offset(0, 0, $dz);

        $this->isCollidedVertically = $movY != $dy;
        $this->isCollidedHorizontally = ($movX != $dx or $movZ != $dz);
        $this->isCollided = ($this->isCollidedHorizontally or $this->isCollidedVertically);
        $this->onGround = ($movY != $dy and $movY < 0);
        if($this->onGround) $this->motionY = 0;
        $this->updateFallState($dy, $this->onGround);
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
    }

    public function knockBackCheck($tick){
        if(!$this->attacker instanceof Entity) return false;

        if($this->moveTime > 5) $this->moveTime = 5;
        $this->moveTime--;
        $target = $this->attacker;
        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;
        $atn = atan2($z, $x);
        $this->move(-cos($atn) * $tick * 0.35, -sin($atn) * $tick * 0.35, 0.41);
        $this->setRotation(rad2deg(atan2($z, $x) - M_PI_2), rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
        if($this->moveTime <= 0) $this->attacker = null;
        $this->updateMovement();
        $this->entityBaseTick($tick);
        return true;
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

    public function onUpdate($currentTick){}

    public function updateTick(){
        $tk = $this->server->getTick();
        $tick = $tk - $this->lastUpdate;
        $this->lastUpdate = $tk;
        if($this->dead === true){
            if(++$this->deadTicks == 1){
                foreach($this->hasSpawned as $player){
                    $pk = new EntityEventPacket();
                    $pk->eid = $this->id;
                    $pk->event = 3;
                    $player->dataPacket($pk);
                }
            }
            $this->updateMovement();
            $this->knockBackCheck($tick);
            if($this->deadTicks >= 23) $this->close();
            return;
        }
        if($this->knockBackCheck($tick)) return;
        $this->moveTime++;
        $target = $this->getTarget();
        if($this->isMovement()){
            $x = $target->x - $this->x;
            $y = $target->y - $this->y;
            $z = $target->z - $this->z;
            $atn = atan2($z, $x);
            $this->move(cos($atn) * $tick * 0.1, sin($atn) * $tick * 0.1);
            $this->setRotation(rad2deg($atn - M_PI_2), rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
        }else{
            $this->move(0, 0);
        }
        if($target instanceof Player){
            if($this->distance($target) <= 2){
                $this->pitch = 22;
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        }else{
            if($this->distance($target) <= 1){
                $this->moveTime = 800;
            }else if($this->x === $this->lastX or $this->z === $this->lastZ){
                $this->moveTime += 50;
            }
        }
        $this->updateMovement();
        $this->entityBaseTick($tick);
    }

}
