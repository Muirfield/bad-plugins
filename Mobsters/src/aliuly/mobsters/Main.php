<?php
namespace aliuly\mobsters;

use pocketmine\plugin\PluginBase;
use pocketmine\block\Block;
use pocketmine\item\Item;;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;

use aliuly\mobsters\idiots\Chicken;
use aliuly\mobsters\idiots\Pig;
use aliuly\mobsters\idiots\Sheep;
use aliuly\mobsters\idiots\Cow;
use aliuly\mobsters\idiots\Mooshroom;
use aliuly\mobsters\idiots\Wolf;
use aliuly\mobsters\idiots\Enderman;
use aliuly\mobsters\idiots\Spider;
use aliuly\mobsters\idiots\Skeleton;
use aliuly\mobsters\idiots\PigZombie;
use aliuly\mobsters\idiots\Creeper;
use aliuly\mobsters\idiots\Slime;
use aliuly\mobsters\idiots\Silverfish;
use aliuly\mobsters\idiots\Zombie;
use aliuly\mobsters\idiots\Villager;

use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Location;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Tag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;


class Main extends PluginBase implements Listener,CommandExecutor{
	public $spawner = [];
  protected $classtab;

	public function onEnable(){
		$this->spawner = [];
		$this->classtab = [];
		$ns = "aliuly\\mobsters\\idiots\\";
		foreach([
			"Chicken", "Pig", "Sheep", "Cow","Mooshroom", "Wolf",
			"Enderman", "Spider", "Skeleton", "PigZombie", "Creeper",
			"Silverfish", "-Zombie", "-Villager",
		] as $type){
			$class = $ns.$type;
			if ($type{0} == "-") {
				$type = substr($type,1);
				$class = $ns.$type;
			} else {
				$id = $class::NETWORK_ID;
				Item::addCreativeItem(Item::get(Item::SPAWN_EGG,$id));
			}
			Entity::registerEntity($class);
			$this->classtab[$class::NETWORK_ID] = $class;
			$this->classtab[strtolower($type)] = $class;
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onPlayerInteract(PlayerInteractEvent $e) {
		$pl = $e->getPlayer();
		$hand = $pl->getInventory()->getItemInHand();
		if ($hand->getId() != Item::SPAWN_EGG) return;
		$bl = $e->getBlock();
		if (!$bl->isSolid()) return;
		$bl = $bl->getSide($e->getFace());
		if ($hand->getDamage() == Wolf::NETWORK_ID) {
			$this->spawner[implode(",",[$bl->getX(),$bl->getY(),$bl->getZ()])] = [ $pl->getName() , $hand->getDamage(), time() ];
		}
	}
	public function getSpawner($x,$y,$z) {
		$k = implode(",",[$x,$y,$z]);
		if (isset($this->spawner[$k])) {
			list($owner,$id,$tm) = $this->spawner[$k];
			if (time() - $tm < 5) {
				return [$owner,$id];
			}
		}
		return ["",0];
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		if ($cmd->getName() != "mobster") return false;
		if (isset($args[0]) && strtolower($args[0]) == "spawn") array_shift($args);
		if (!$sender->hasPermission("mobsters.cmd.spawn")) {
			$sender->sendMessage("You are not allowed to do that!");
			return true;
		}
    if (count($args) != 2) return false;
		$mob = strtolower(array_shift($args));
		if (!isset($this->classtab[$mob])) {
			$sender->sendMessage("Unknown mob class: $mob");
			return true;
		}
		$class = $this->classtab[$mob];
		$location  = explode(":",array_shift($args),2);
		if (count($location)<2) {
			if ($sender instanceof Player)
			  $level = $sender->getLevel();
			else {
				$level =  $this->getServer()->getDefaultLevel();
			}
		} else {
			$level = $this->getServer()->getLevelByName($location[1]);
			if ($level === null) {
				$sender->sendMessage("Unknown level: ".$location[1]);
				return true;
			}
		}
		$location = array_map("floatval",explode(",",$location[0],5));
		if (count($location)<3) {
			$sender->sendMessage("Invalid location specified");
			return false;
		}
		$location = new Location(...$location);
		$location->setLevel($level);
		$nbt =  new Compound("",[
			new Enum("Pos",[
				new Double("",$location->x),
				new Double("",$location->y),
				new Double("",$location->z),
				]),
			new Enum("Motion", [
				new Double("",0),
				new Double("",0),
				new Double("",0),
				]),
			new Enum("Rotation",[
				new Float("",$location->yaw),
				new Float("",$location->pitch),
				])
			]);
		$entity = new $class($location->getLevel()->getChunk($location->x >> 4, $location->z >> 4), $nbt);
		$entity->spawnToAll();
		$sender->sendMessage("Spawned $mob");
		return true;
	}
}
