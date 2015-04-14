<?php

namespace plugin;

use plugin\AnimalEntity\Animal;
use plugin\AnimalEntity\Chicken;
use plugin\AnimalEntity\Cow;
use plugin\AnimalEntity\Pig;
use plugin\AnimalEntity\Sheep;
use plugin\MonsterEntity\Creeper;
use plugin\MonsterEntity\Enderman;
use plugin\MonsterEntity\Monster;
use plugin\MonsterEntity\PigZombie;
use plugin\MonsterEntity\Skeleton;
use plugin\MonsterEntity\Spider;
use plugin\MonsterEntity\Zombie;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class EntityManager extends PluginBase implements Listener{

    public $path;
    public $tick = 0;

    public static $entityData;
    public static $spawnerData;

    public function __construct(){
        Entity::registerEntity(Cow::class);
        Entity::registerEntity(Pig::class);
        Entity::registerEntity(Sheep::class);
        Entity::registerEntity(Chicken::class);

        Entity::registerEntity(Zombie::class);
        Entity::registerEntity(Creeper::class);
        Entity::registerEntity(Skeleton::class);
        Entity::registerEntity(Spider::class);
        Entity::registerEntity(PigZombie::class);
        Entity::registerEntity(Enderman::class);
    }

    public function onEnable(){
        //if($this->isPhar() === true){
            @mkdir($this->path = self::core()->getDataPath() . "plugins/EntityManager/");
            $this->readData();
            self::core()->getPluginManager()->registerEvents($this, $this);
            self::core()->getLogger()->info(TextFormat::GOLD . "[EntityManager]플러그인이 활성화 되었습니다");
            self::core()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "updateEntity"]), 1);
        /*}else{
            self::core()->getLogger()->info(TextFormat::GOLD . "[EntityManager]플러그인을 Phar파일로 변환해주세요");
        }*/
    }

    public function onDisable(){
        file_put_contents($this->path . "SpawnerData.yml", yaml_emit(self::$spawnerData, YAML_UTF8_ENCODING));
    }

    public static function yaml($file){
        return preg_replace("#^([ ]*)([a-zA-Z_]{1}[^\:]*)\:#m", "$1\"$2\":", file_get_contents($file));
    }

    public static function core(){
        return Server::getInstance();
    }

    /**
     * @return Animal|Monster[]
     */
    public static function getEntities(){
        $entities = [];
        foreach(self::core()->getDefaultLevel()->getEntities() as $id => $ent){
            if($ent instanceof Animal or $ent instanceof Monster) $entities[$id] = $ent;
        }
        return $entities;
    }

    public static function clearEntity(){
        foreach(self::core()->getDefaultLevel()->getEntities() as $ent){
            if(
                $ent instanceof Animal
                || $ent instanceof Monster
                || $ent instanceof Arrow
                || $ent instanceof \pocketmine\entity\Item
            ){
                $ent->close();
            }
        }
    }

    public function readData(){
        if(file_exists($this->path. "EntityData.yml")){
            self::$entityData = yaml_parse($this->yaml($this->path . "EntityData.yml"));
        }else{
            self::$entityData = [
                "entity-limit" => 45,
                "spawn-tick" => 200,
                "spawn-radius" => 25,
                "auto-spawn" => true,
                "spawn-mob" => true,
                "spawn-animal" => true,
            ];
            file_put_contents($this->path . "EntityData.yml", yaml_emit(self::$entityData, YAML_UTF8_ENCODING));
        }

        if(file_exists($this->path. "SpawnerData.yml")){
            self::$spawnerData = yaml_parse($this->yaml($this->path . "SpawnerData.yml"));
        }else{
            self::$spawnerData = [];
            file_put_contents($this->path . "SpawnerData.yml", yaml_emit(self::$spawnerData, YAML_UTF8_ENCODING));
        }
    }

    public static function getData($key){
        $vars = explode(".", $key);
        $base = array_shift($vars);
        if(!isset(self::$entityData[$base])) return false;
        $base = self::$entityData[$base];
        while(count($vars) > 0){
            $baseKey = array_shift($vars);
            if(!is_array($base) or !isset($base[$baseKey])) return false;
            $base = $base[$baseKey];
        }
        return $base;
    }

    /**
     * @param int|string $type
     * @param Position $source
     *
     * @return Entity
     */
    public static function createEntity($type, Position $source){
        if(self::getData("entity-limit") <= count(self::getEntities())) return null;
        $chunk = $source->getLevel()->getChunk($source->getX() >> 4, $source->getZ() >> 4, true);
        if($chunk === null or !$chunk->isGenerated()) $source->getLevel()->generateChunk($source->getX() >> 4, $source->getZ() >> 4);
        $nbt = new Compound("", [
            "Pos" => new Enum("Pos", [
                new Double("", $source->x),
                new Double("", $source->y),
                new Double("", $source->z)
            ]),
            "Motion" => new Enum("Motion", [
                new Double("", 0),
                new Double("", 0),
                new Double("", 0)
            ]),
            "Rotation" => new Enum("Rotation", [
                new Float("", 0),
                new Float("", 0)
            ]),
        ]);
        if(in_array($type, ["Cow", "Pig", "Sheep", "Chicken", 10, 11, 12, 13]) && !self::getData("spawn-animal")) return null;
        if(in_array($type, ["Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman", 32, 33, 34, 35, 36, 38]) && !self::getData("spawn-mob")) return null;
        return Entity::createEntity($type, $chunk, $nbt);
    }

    public function updateEntity(){
        if(++$this->tick >= self::getData("spawn-tick")){
            foreach(self::$spawnerData as $pos => $data){
                if(mt_rand(0, 1) > 0 or count($data["mob-list"]) == 0) continue;
                $radius = (int) $data["radius"];
                $level = self::core()->getDefaultLevel();
                $pos = (new Vector3(...explode(":", $pos)))->add(mt_rand(-$radius, $radius), mt_rand(-$radius, $radius), mt_rand(-$radius, $radius));
                $bb = $level->getBlock($pos)->getBoundingBox();
                $bb1 = $level->getBlock($pos->add(0, 1))->getBoundingBox();
                $bb2 = $level->getBlock($pos->add(0, -1))->getBoundingBox();
                if(
                    ($bb !== null and $bb->maxY - $bb->minY > 0)
                    || ($bb1 !== null and $bb1->maxY - $bb1->minY > 0)
                    || $bb2 === null or ($bb2 !== null and $bb2->maxY - $bb2->minY !== 1)
                ) continue;
                $entity = self::createEntity($data["mob-list"][mt_rand(0, count($data["mob-list"]) - 1)], Position::fromObject($pos, $level));
                if($entity instanceof Entity){
                    $entity->spawnToAll();
                    $entity->setPosition($pos);
                }
            }
            if(self::getData("auto-spawn")) foreach(self::core()->getOnlinePlayers() as $player){
                if(mt_rand(0, 4) > 0) continue;
                $level = $player->getLevel();
                $rad = self::getData("spawn-radius");
                $pos = $player->add(mt_rand(-$rad, $rad), mt_rand(-$rad, $rad), mt_rand(-$rad, $rad));
                $bb = $level->getBlock($pos)->getBoundingBox();
                $bb1 = $level->getBlock($pos->add(0, 1))->getBoundingBox();
                $bb2 = $level->getBlock($pos->add(0, -1))->getBoundingBox();
                if(
                    ($bb !== null and $bb->maxY - $bb->minY > 0)
                    || ($bb1 !== null and $bb1->maxY - $bb1->minY > 0)
                    || $bb2 === null or ($bb2 !== null and $bb2->maxY - $bb2->minY !== 1)
                ) continue;
                $ent = [
                    ["Cow", "Pig", "Sheep", "Chicken", null, null],
                    ["Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman"]
                ];
                $entity = self::createEntity($ent[mt_rand(0, 1)][mt_rand(0, 5)], Position::fromObject($pos, $level));
                if($entity instanceof Entity){
                    $entity->spawnToAll();
                    $entity->setPosition($pos);
                }
            }
            $this->tick = 0;
        }
        foreach(self::getEntities() as $entity) $entity->updateTick();
    }

    public function PlayerInteractEvent(PlayerInteractEvent $ev){
        $item = $ev->getItem();
        $pos = $ev->getBlock()->getSide($ev->getFace());

        if($item->getId() === Item::SPAWN_EGG && $ev->getFace() !== 255){
            $entity = self::createEntity($item->getDamage(), $pos);
            if($entity instanceof Entity) $entity->spawnToAll();
            if($ev->getPlayer()->isSurvival()){
                $item->count -= 1;
                $ev->getPlayer()->getInventory()->setItemInHand($item);
            }
            $ev->setCancelled();
        }elseif($item->getId() === Item::MONSTER_SPAWNER && $ev->getFace() !== 255){
            self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}"] = [
                "radius" => 4,
                "mob-list" => ["Cow", "Pig", "Sheep", "Chicken", "Zombie", "Creeper", "Skeleton", "Spider", "PigZombie", "Enderman"],
            ];
            $ev->getPlayer()->sendMessage("[EntityManager]스포너가 설치되었습니다");
        }
    }

    public function BlockBreakEvent(BlockBreakEvent $ev){
        $pos = $ev->getBlock();
        if($pos->getId() === Item::MONSTER_SPAWNER && isset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}"])){
            $ev->getPlayer()->sendMessage("[EntityManager]스포너가 파괴되었습니다");
            unset(self::$spawnerData["{$pos->x}:{$pos->y}:{$pos->z}"]);
        }
    }

    public function onCommand(CommandSender $i, Command $cmd, $label, array $sub){
        $output = "[EntityManager]";
        switch($cmd->getName()){
            case "제거":
                self::clearEntity();
                $output .= "소환된 엔티티를 모두 제거했어요";
                break;
            case "체크":
                $output .= "현재 소환된 수:" . count(self::getEntities()) . "마리";
                break;
            case "스폰":
                if(!is_numeric($sub[0]) and gettype($sub[0]) !== "string"){
                    $output .= "엔티티 이름이 올바르지 않습니다";
                    break;
                }
				$pos = null;
                if(count($sub) >= 4) $pos = new Position($sub[1], $sub[2], $sub[3], self::core()->getDefaultLevel());
				elseif($i instanceof Player) $pos = $i->getPosition();
                if($pos !== null && self::createEntity($sub[0], $pos) !== null){
					$output .= "몬스터가 소환되었어요";
				}else{
					$output .= "사용법: /스폰 <id|name> (<x> <y> <z>)";
				}
                break;
        }
        $i->sendMessage($output);
        return true;
    }

}