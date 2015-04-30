<?php
namespace aliuly\spawnmgr;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
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
	protected $spawnmode;
	protected $keepinv;
	protected $cmd;
	protected $reserved;

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"settings" => [
				"tnt" => true,
				"pvp" => true,
				"reserved" => false,
				"spawn-mode" => "default",
				"keep-inventory" => false,
				"home-cmd" => "/home",
			],
			"armor"=>[
				"chain_chestplate",
				"leather_pants",
				"leather_boots",
			],
			"items"=>[
				"STONE_SWORD,1",
				"WOOD,16",
				"COOKED_BEEF,5",
			],
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			unset($defaults["items"]);
			unset($defaults["armor"]);
		}
		$cfg=(new Config($this->getDataFolder()."config.yml",
							  Config::YAML,$defaults))->getAll();
		$this->tnt = $cfg["settings"]["tnt"];
		$this->pvp = $cfg["settings"]["pvp"];
		$this->reserved = $cfg["settings"]["reserved"];
		switch(strtolower($cfg["settings"]["spawn-mode"])) {
			case "home":
			case "default":
			case "world":
			case "always":
				$this->spawnmode = strtolower($cfg["settings"]["spawn-mode"]);
				break;
			default:
				$this->spawnmode = "default";
				$this->getLogger()->info("Invalid spawn-mode setting!");
		}
		$this->cmd = $cfg["settings"]["home-cmd"];
		$this->keepinv = $cfg["settings"]["keep-inventory"];
		$this->armor = isset($cfg["armor"]) ? $cfg["armor"] : [];
		$this->items = isset($cfg["items"]) ? $cfg["items"] : [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function itemName(Item $item) {
		$items = [];
		$constants = array_keys((new \ReflectionClass("pocketmine\\item\\Item"))->getConstants());
		foreach ($constants as $constant) {
			$id = constant("pocketmine\\item\\Item::$constant");
			$constant = str_replace("_", " ", $constant);
			$items[$id] = $constant;
		}
		$n = $item->getName();
		if ($n != "Unknown") return $n;
		if (isset($items[$item->getId()])) return $items[$item->getId()];
		return $n;
	}


	public function mwteleport($pl,$pos) {
		if (($pos instanceof Position) &&
			 ($mw = $this->owner->getServer()->getPluginManager()->getPlugin("ManyWorlds")) != null) {
			// Using ManyWorlds for teleporting...
			$mw->mwtp($pl,$pos);
		} else {
			$pl->teleport($pos);
		}
	}
	public function onPlayerKick(PlayerKickEvent $event){
		if (!$this->reserved) return;
		//echo $event->getReason()."\n";//##DEBUG
		if ($event->getReason() == "server full" &&
			 $event->getReason() == "disconnectionScreen.serverFull") {
			if (!$event->getPlayer()->hasPermission("spawnmgr.reserved"))
				return;
			if($this->reserved !== true) {
				// OK, we do have a limit...
				if(count($this->getServer()->getOnlinePlayers()) >
					$this->getServer()->getMaxPlayers() + $this->reserved) return;
			}
			$ev->setCancelled();
			return;
		}
		// Not server full message...
	}
	public function onDeath(PlayerDeathEvent $e) {
		if (!$this->keepinv) return;
		if (!$e->getEntity()->hasPermission("spawnmgr.keepinv")) return;
		$e->setKeepInventory(true);
		$e->setDrops([]);
	}

	public function onJoin(PlayerJoinEvent $e) {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$pl = $e->getPlayer();
		if (!$pl->hasPermission("spawnmgr.spawnmode")) return;

		switch($this->spawnmode) {
			case "world":
				$this->mwteleport($pl,$pl->getLevel()->getSafeSpawn());
				break;
			case "always":
				$this->mwteleport($pl,$this->getServer()->getDefaultLevel()->getSafeSpawn());
				break;
			case "home":
				$this->getServer()->dispatchCommand($pl,$this->cmd);
				break;
		}
	}
	public function onRespawn(PlayerRespawnEvent $e) {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$pl = $e->getPlayer();
		if (!($pl instanceof Player)) return;
		if ($pl->isCreative()) return;
		if ($pl->hasPermission("spawnmgr.receive.armor")) {
			echo __METHOD__.",".__LINE__."\n";//##DEBUG
			$slot_map = [ "helmet" => 0, "chestplate" => 1, "leggings" => 2,
							  "boots" => 3 , "cap" => 0, "tunic" => 1,
							  "pants" => 2 ];
			$inventory = [];
			foreach ($this->armor as $j) {
				$item = Item::fromString($j);
				echo __METHOD__.",".__LINE__."-".$item->getId()."\n";//##DEBUG
				$itemName = explode(" ",strtolower($this->itemName($item)),2);
				if (count($itemName) != 2) {
					$this->getLogger()->info("Invalid armor item: $j");
					continue;
				}
				list($material,$type) = $itemName;
				if (!isset($slot_map[$type])) {
					$this->getLogger()->info("Invalid armor type: $type for $material");
					continue;
				}
				$slot = $slot_map[$type];
				$inventory[$slot] = $item;
			}
			foreach ($inventory as $slot => $item) {
				if ($pl->getInventory()->getArmorItem($slot)->getID()!=0) continue;
				$pl->getInventory()->setArmorItem($slot,clone $item);
			}
		}
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		if ($pl->hasPermission("spawnmgr.receive.items")) {
			echo __METHOD__.",".__LINE__."\n";//##DEBUG
			// Figure out if the inventory is empty...
			$cnt = 0;
			$max = $pl->getInventory()->getSize();
			foreach ($pl->getInventory()->getContents() as $slot => &$item) {
				if ($slot < $max) ++$cnt;
			}
			if ($cnt) return;
			echo __METHOD__.",".__LINE__."\n";//##DEBUG
			// This player has nothing... let's give them some to get started...
			foreach ($this->items as $i) {
				$r = explode(",",$i);
				if (count($r) != 2) continue;
				echo __METHOD__.",".__LINE__."i=$i ($r[0]-$r[1])\n";//##DEBUG
				$item = Item::fromString($r[0]);
				$item->setCount(intval($r[1]));
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
