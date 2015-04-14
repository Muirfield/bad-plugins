<?php

namespace plugin\MonsterEntity;

use plugin\EntityManager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;

class Enderman extends Monster{
    const NETWORK_ID = 38;

    public $width = 0.7;
    public $height = 2.8;

    protected function initEntity(){
        $this->setDamage([0, 6, 4, 6]);
        $this->namedtag->id = new String("id", "Enderman");
    }

    public function getName(){
        return "엔더맨";
    }

    public function onUpdate($currentTick){
    }

    public function updateTick(){
        if($this->dead === true){
            if(++$this->deadTicks == 1){
                foreach($this->hasSpawned as $player){
                    $pk = new EntityEventPacket();
                    $pk->eid = $this->id;
                    $pk->event = 3;
                    $player->dataPacket($pk);
                }
            }
            $this->knockBackCheck();
            $this->updateMovement();
            if($this->deadTicks >= 23) $this->close();
            return;
        }

        $this->attackDelay++;
        if($this->knockBackCheck()) return;

        $this->moveTime++;
        $target = $this->getTarget();
        if($this->isMovement()){
            $x = $target->x - $this->x;
            $y = $target->y - $this->y;
            $z = $target->z - $this->z;
            $atn = atan2($z, $x);
            $this->move(cos($atn) * 0.1, sin($atn) * 0.1);
            $this->setRotation(rad2deg($atn - M_PI_2), rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
        }else{
            $this->move(0, 0);
        }
        if($target instanceof Player){
            if($this->attackDelay >= 16 && $this->distance($target) <= 1){
                $this->attackDelay = 0;
                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage()[EntityManager::core()->getDifficulty()]);
                $target->attack($ev->getFinalDamage(), $ev);
            }
        }else{
            if($this->distance($target) <= 1){
                $this->moveTime = 800;
            }elseif($this->x == $this->lastX or $this->z == $this->lastZ){
                $this->moveTime += 20;
            }
        }
        $this->entityBaseTick();
        $this->updateMovement();
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getEntity() instanceof Player){
            $drops = [];
            return $drops;
        }
        return [];
    }
}
