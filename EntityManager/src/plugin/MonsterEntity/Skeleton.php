<?php

namespace plugin\MonsterEntity;

use pocketmine\entity\Entity;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;

class Skeleton extends Monster implements ProjectileSource{
    const NETWORK_ID = 34;

    public $width = 0.58;
    public $length = 0.6;
    public $height = 1.8;

    protected function initEntity(){
        $this->namedtag->id = new String("id", "Skeleton");
    }

    public function getName(){
        return "스켈레톤";
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
            if($this->attackDelay >= 16 && $this->distance($target) <= 7 and mt_rand(1,25) === 1){
                $this->attackDelay = 0;
                $f = 1.5;
                $yaw = $this->yaw + mt_rand(-180, 180) / 10;
                $pitch = $this->pitch + mt_rand(-90, 90) / 10;
                $nbt = new Compound("", [
                    "Pos" => new Enum("Pos", [
                        new Double("", $this->x),
                        new Double("", $this->y + 1.62),
                        new Double("", $this->z)
                    ]),
                    "Motion" => new Enum("Motion", [
                        new Double("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
                        new Double("", -sin($pitch / 180 * M_PI) * $f),
                        new Double("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
                    ]),
                    "Rotation" => new Enum("Rotation", [
                        new Float("", $yaw),
                        new Float("", $pitch)
                    ]),
                ]);
                /** @var \pocketmine\entity\Arrow $arrow */
                $arrow = Entity::createEntity("Arrow", $this->chunk, $nbt, $this);

                $ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $f);

                $this->server->getPluginManager()->callEvent($ev);
                if($ev->isCancelled()){
                    $arrow->kill();
                }else{
                    $arrow->spawnToAll();
                }
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
            return [
                Item::get(Item::BONE, 0, mt_rand(0, 2)),
                Item::get(Item::ARROW, 0, mt_rand(0, 3)),
            ];
        }
        return [];
    }

}
