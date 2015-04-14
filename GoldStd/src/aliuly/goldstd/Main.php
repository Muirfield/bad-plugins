<?php
namespace aliuly\goldstd;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;

class Main extends PluginBase implements CommandExecutor,Listener {
	public static $defaults;
	protected $currency;
	protected $state;

	// Access and other permission related checks
	private function access(CommandSender $sender, $permission) {
		if($sender->hasPermission($permission)) return true;
		$sender->sendMessage("You do not have permission to do that.");
		return false;
	}
	private function inGame(CommandSender $sender,$msg = true) {
		if ($sender instanceof Player) return true;
		if ($msg) $sender->sendMessage("You can only use this command in-game");
		return false;
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Standard call-backs
	//
	//////////////////////////////////////////////////////////////////////
	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$defaults = [
			"settings" => [
				"currency" => 266,
			],
			"defaults" => [
				"payment" => 1,
				"timeout" => 30,
			],
		];
		$cf = (new Config($this->getDataFolder()."config.yml",
								Config::YAML,$defaults))->getAll();
		$this->currency = $cf["settings"]["currency"];
		self::$defaults = $cf["defaults"];
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Economy/Money API
	//
	//////////////////////////////////////////////////////////////////////
	public function giveMoney($player,$money) {
		$pl = $this->getServer()->getPlayer($player);
		if (!$pl) return false;
		while ($money > 0) {
			$item = Item::get($this->currency);
			if ($money > $item->getMaxStackSize()) {
				$item->setCount($item->getMaxStackSize());
			} else {
				$item->setCount($money);
			}
			$money -= $item->getCount();
			$pl->getInventory()->addItem(clone $item);
		}
		return true;
	}
	public function takeMoney($player,$money) {
		$pl = $this->getServer()->getPlayer($player);
		if (!$pl) return null;
		foreach ($pl->getInventory()->getContents() as $slot => &$item) {
			if ($item->getId() != $this->currency) continue;
			if ($item->getCount() > $money) {
				$item->setCount($item->getCount() - $money);
				$pl->getInventory()->setItem($slot,clone $item);
				break;
			}
			$money -= $item->getCount();
			$pl->getInventory()->clear($slot);
			if (!$money) break;
		}
		if ($money) return $money; // They don't have enough money!
		return true;
	}
	public function grantMoney($p,$money) {
		if ($money < 0) {
			return $this->takeMoney($p,-$money);
		} elseif ($money > 0) {
			return $this->giveMoney($p,$money);
		} else {
			return true;
		}
	}
	public function getMoney($player) {
		$pl = $this->getServer()->getPlayer($player);
		if (!$pl) return null;
		$g = 0;
		foreach ($pl->getInventory()->getContents() as $slot => &$item) {
			if ($item->getId() != $this->currency) continue;
			$g += $item->getCount();
		}
		return $g;
	}
	public function setMoney($player,$money) {
		$now = $this->getMoney($player);
		if ($money < $now) {
			return $this->takeMoney($player, $now - $money);
		} elseif ($money > $now) {
			return $this->giveMoney($player, $money - $now);
		} elseif ($money == $now) return true; // Nothing to do!
		$this->getLogger()->info("INTERNAL ERROR AT ".__FILE__.",".__LINE__);
		return false;
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Manipulate internal state
	//
	//////////////////////////////////////////////////////////////////////
	protected function getAttr($pl,$attr, $def = null) {
		if ($def === null) $def = self::$defaults[$attr];
		if ($pl instanceof Player) $pl = $pl->getName();
		if (!isset($this->state[$pl])) {
			$this->state[$pl] = [ $attr => $def ];
		}
		if (!isset($this->state[$pl][$attr])) {
			$this->state[$pl][$attr] =  $def;
		}
		return $this->state[$pl][$attr];
	}
	protected function setAttr($pl,$attr, $val) {
		if ($pl instanceof Player) $pl = $pl->getName();
		if (!isset($this->state[$pl])) {
			$this->state[$pl] = [ $attr => $val ];
		}
		$this->state[$pl][$attr] =  $val;
		return;
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Command implementations
	//
	//////////////////////////////////////////////////////////////////////
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "pay":
				if (!$this->inGame($sender)) return false;
				if (count($args) == 1) {
					if (is_numeric($args[0])) {
						$money = intval($args[0]);
						if ($this->getMoney($sender->getName()) < $money) {
							$sender->sendMessage("You do not have enough money");
							return true;
						}
						$this->setAttr($sender,"payment",$money);
						$sender->sendMessage("Next payout will be for ".$money."G");
						return true;
					}
					return false;
				} elseif (count($args) == 0) {
					$sender->sendMessage("Next payout will be for ".
												$this->getAttr($sender,"payment") ."G");
					return true;
				}
				return false;
			case "balance":
				if (!$this->inGame($sender)) return false;
				$sender->sendMessage("You have ".$this->getMoney($sender->getName()).
											"G");
				return true;
		}
		return false;
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Event handlers
	//
	//////////////////////////////////////////////////////////////////////

	public function onPlayerQuitEvent(PlayerQuitEvent $e) {
		$pl = $e->getPlayer()->getName();
		if (isset($this->state[$pl])) unset($this->state[$pl]);
	}
	public function onPlayerPayment(EntityDamageEvent $ev) {
		if ($ev->isCancelled()) return;
		if(!($ev instanceof EntityDamageByEntityEvent)) return;
		$giver = $ev->getDamager();
		$taker = $ev->getEntity();
		if (!($giver instanceof Player)) return;
		$hand = $giver->getInventory()->getItemInHand();
		if ($hand->getId() != $this->currency) return;
		if ($taker instanceof Player) {
			$ev->setCancelled(); // OK, we want to pay, not to fight!
			$this->onPlayerPaid($giver,$taker);
		} //else playing an Entity!
	}
	/*
	 * Events
	 -  $this->getServer()->getPluginManager()->callEvent(new Event(xxx))
	*/
	public function onPlayerPaid(Player $giver,Player $taker) {
		$gg = $this->getAttr($giver,"payment");
		$this->setAttr($giver,"payment",self::$defaults["payment"]);
		if ($this->getMoney($giver->getName()) < $gg) {
			$giver->sendMessage("You don't have that much money!");
			return;
		}
		$this->takeMoney($giver->getName(),$gg);
		$this->giveMoney($taker->getName(),$gg);
		list($when,$amt,$ptaker) = $this->getAttr($giver,"counter",[0,0,""]);
		if (time() - $when < self::$defaults["timeout"] && $ptaker == $taker->getName()) {
			// Still the same transaction...
			$amt += $gg;
		} else {
			// New transaction!
			$when = time();
			$amt = $gg;
			$ptaker = $taker->getName();
		}
		$this->setAttr($giver,"counter",[$when,$amt,$ptaker]);

		$giver->sendMessage("Paid ".$amt."G, you now have ".
								  $this->getMoney($giver->getName())."G");
		$taker->sendMessage("Received ".$amt."G, you now have ".
								  $this->getMoney($taker->getName())."G");
	}
}
