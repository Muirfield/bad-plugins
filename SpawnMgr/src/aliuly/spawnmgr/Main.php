<?php
namespace aliuly\spawncontrol;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\Player;
use pocketmine\item\Item;

class Main extends PluginBase implements Listener {
	protected $items;
	protected $armor;
	protected $pvp;
	protected $tnt;

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"settings" => [
				"tnt" => true,
				"pvp" => true,
			],
			"spawnarmor"=>[
				"head"=>"-",
				"body"=>"chainmail",
				"legs"=>"leather",
				"boots"=>"leather",
			],
			"spawnitems"=>[
				"STONE_SWORD:0:1",
				"WOOD:0:16",
				"COOKED_BEEF:0:5",
			]
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			unset($defaults["spawnitems"]);
		}
		$cfg=(new Config($this->getDataFolder()."config.yml",
							  Config::YAML,$defaults))->getAll();
		$this->tnt = $cfg["settings"]["tnt"];
		$this->pvp = $cfg["settings"]["pvp"];
		$this->armor = $cfg["spawnarmor"];
		$this->items = isset($cfg["spawnitems"]) ? $cfg["spawnitems"] : [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onRespawn(PlayerRespawnEvent $e) {
		//echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$pl = $e->getPlayer();
		if (!($pl instanceof Player)) return;
		if ($pl->isCreative()) return;
		if ($pl->hasPermission("spawncontrol.spawnarmor.receive")) {
			//echo __METHOD__.",".__LINE__."\n";//##DEBUG
			foreach ([0=>"head",1=>"body",2=>"legs",3=>"boots"] as $slot=>$attr) {
				if ($pl->getInventory()->getArmorItem($slot)->getID()!=0) continue;
				if (!isset($this->armor[$attr])) continue;
				$type = strtolower($this->armor[$attr]);
				if ($type == "leather") {
					$type = 298;
				} elseif ($type == "chainmail") {
					$type = 302;
				} elseif ($type == "iron") {
					$type = 306;
				} elseif ($type == "gold") {
					$type = 314;
				} elseif ($type == "diamond") {
					$type = 310;
				} else {
					continue;
				}
				//echo __METHOD__.",".__LINE__."\n";//##DEBUG
				//echo "slot=$slot($attr) type=$type ".($type+$slot)."\n";//##DEBUG
				$pl->getInventory()->setArmorItem($slot,new Item($type+$slot,0,1));
			}
		}
		//echo __METHOD__.",".__LINE__."\n";//##DEBUG
		if ($pl->hasPermission("spawncontrol.spawnitems.receive")) {
			//echo __METHOD__.",".__LINE__."\n";//##DEBUG
			// Figure out if the inventory is empty...
			$cnt = 0;
			$max = $pl->getInventory()->getSize();
			foreach ($pl->getInventory()->getContents() as $slot => &$item) {
				if ($slot < $max) ++$cnt;
			}
			if ($cnt) return;
			//echo __METHOD__.",".__LINE__."\n";//##DEBUG
			// This player has nothing... let's give them some to get started...
			foreach ($this->items as $i) {
				$r = explode(":",$i);
				if (count($r) != 3) continue;
				$item = Item::fromString($r[0].":".$r[1]);
				$item->setCount(intval($r[2]));
				$pl->getInventory()->addItem($item);
			}
		}
	}
	public function onPvP(EntityDamageEvent $ev) {
		if ($ev->isCancelled()) return;
		if ($this->pvp) return;
		if(!($ev instanceof EntityDamageByEntityEvent)) return;
		$et = $ev->getEntity();
		if(!($et instanceof Player)) return;
		$sp = $et->getLevel()->getSpawnLocation();
		$dist = $sp->distance($et);
		if ($dist > $this->getServer()->getSpawnRadius()) return;
		$ev->setCancelled();
	}
	public function onExplode(EntityExplodeEvent $ev){
		if ($ev->isCancelled()) return;
		if ($this->tnt) return;
		$et = $ev->getEntity();
		$sp = $et->getLevel()->getSpawnLocation();
		$dist = $sp->distance($et);
		if ($dist > $this->getServer()->getSpawnRadius()) return;
		$ev->setCancelled();
	}
}
