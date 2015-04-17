<?php
namespace aliuly\fireblade;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\Config;

class FireBlade extends PluginBase implements CommandExecutor,Listener {
	protected $cf;
	protected $players;
	// Access and other permission related checks
	private function inGame(CommandSender $sender,$msg = true) {
		if ($sender instanceof Player) return true;
		if ($msg) $sender->sendMessage("You can only use this command in-game");
		return false;
	}
	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"sword1" => Item::IRON_SWORD,
			"sword2" => Item::GOLD_SWORD,
			"sword_txt" => "You must be holding an Iron Sword\nor a Gold Sword",
			"timer" => 5,
			"effect" => 10,
		];
		$this->cf = (new Config($this->getDataFolder()."config.yml",
								 Config::YAML,$defaults))->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->players = [];
		$tt = new CallbackTask([$this,"updateTimer"],[]);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($tt,$this->cf["timer"]);
	}
	public function onQuit(PlayerQuitEvent $ev) {
		$pl = $ev->getPlayer();
		$n = $pl->getName();
		if (isset($this->players[$n])) {
			unset($this->players[$n]);
		}
	}
	/**
	 * @priority HIGH
	 */
	public function onAttack(EntityDamageEvent $ev) {
		if ($ev->isCancelled()) return;
		if(!($ev instanceof EntityDamageByEntityEvent)) return;
		$pl = $ev->getDamager();
		if (!($pl instanceof Player)) return;
		$n = $pl->getName();
		if (!isset($this->players[$n])) return;
		// Burn baby burn!!!
		$ev->getEntity()->setOnFire($this->cf["effect"]);
	}

	public function updateTimer() {
		foreach (array_keys($this->players) as $n) {
			$pl = $this->getServer()->getPlayer($n);
			if (!$pl) {
				unset($this->players[$n]);
				continue;
			}
			$hand = $pl->getInventory()->getItemInHand();
			if ($hand->getId() == $this->cf["sword1"]) {
				$pl->getInventory()->setItemInHand(Item::get($this->cf["sword2"],
																			$hand->getDamage()));
			} elseif ($hand->getId() == $this->cf["sword2"]) {
				$pl->getInventory()->setItemInHand(Item::get($this->cf["sword1"],
																			$hand->getDamage()));
			} else {
				// Unloaded sword...
				$pl->sendMessage("Flame Off!");
				unset($this->players[$n]);
			}
		}
	}

	public function onItemHeld(PlayerItemHeldEvent $e) {
		$pl = $e->getPlayer();
		$n = $pl->getName();
		if (!isset($this->players[$n])) return;
		$hand = $pl->getInventory()->getItemInHand();
		if ($hand->getId() != $this->cf["sword1"] &&
			 $hand->getId() != $this->cf["sword2"]) {
			$pl->sendMessage("Flame Off!");
			unset($this->players[$n]);
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "fireblade":
				if (!$this->inGame($sender)) return true;
				$n = $sender->getName();
				if (isset($this->players[$n])) {
					unset($this->players[$n]);
					$this->updateTimer();
				} else {
					$hand = $sender->getInventory()->getItemInHand();
					if ($hand->getId() != $this->cf["sword1"] &&
						 $hand->getId() != $this->cf["sword2"]) {
						$sender->sendMessage($this->cf["sword_txt"]);
						return true;
					}
					$this->players[$n] = $n;
					$sender->sendMessage("Flame ON!");
					$this->updateTimer();
				}
				return true;
		}
		return false;
	}
}
