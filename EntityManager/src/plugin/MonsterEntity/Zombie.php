<?php

namespace plugin\MonsterEntity;

use plugin\EntityManager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;

class Zombie extends Monster{
    const NETWORK_ID = 32;

    public $width = 0.7;
    public $length = 0.4;
    public $height = 1.8;

    protected function initEntity(){
        $this->setDamage([0, 3, 4, 6]);
        $this->namedtag->id = new String("id", "Zombie");
    }

    public function getName(){
        return "좀비";
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
        if($this->knockBackCheck()) return;
        $this->moveTime++;
        $this->attackDelay++;
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
            if($this->attackDelay >= 16 && $this->distance($target) <= 0.8){
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
        $cause = $this->lastDamageCause;
        if($cause instanceof EntityDamageByEntityEvent and $cause->getEntity() instanceof Player){
            $drops = [
                Item::get(Item::FEATHER, 0, 1)
            ];
            if(mt_rand(0, 199) < 5){
                if(mt_rand(0, 1) === 0){
                    $drops[] = Item::get(Item::CARROT, 0, 1);
                }else{
                    $drops[] = Item::get(Item::POTATO, 0, 1);
                }
            }
            return $drops;
        }
        return [];
    }
}
