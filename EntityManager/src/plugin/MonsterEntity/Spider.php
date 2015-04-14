<?php

namespace plugin\MonsterEntity;

use plugin\EntityManager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;

class Spider extends Monster{
    const NETWORK_ID = 35;

    public $width = 1.5;
    public $length = 0.8;
    public $height = 1.12;

    public function __construct(FullChunk $chunk, Compound $nbt){
        parent::__construct($chunk, $nbt);
    }

    protected function initEntity(){
        $this->setMaxHealth(16);
        $this->setDamage([0, 2, 2, 3]);
        $this->namedtag->id = new String("id", "Spider");
    }

    public function getName(){
        return "거미";
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
            if($this->attackDelay >= 16 && $this->distance($target) <= 1.1){
                $this->attackDelay = 0;
                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage()[EntityManager::core()->getDifficulty()]);
                $target->attack($ev->getFinalDamage(), $ev);
            }
        }else{
            if($this->distance($target) <= 1){
                $this->moveTime = 800;
            }elseif($this->x === $this->lastX or $this->z === $this->lastZ){
                $this->moveTime += 20;
            }
        }
        $this->entityBaseTick();
        $this->updateMovement();
    }

    public function getDrops(){
        $cause = $this->lastDamageCause;
        if($cause instanceof EntityDamageByEntityEvent and $cause->getEntity() instanceof Player){
            return [
                Item::get(Item::STRING, 0, mt_rand(0, 3))
            ];
        }
        return [];
    }

}
