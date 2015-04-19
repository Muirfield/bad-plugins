<?php
namespace aliuly\treecapitator;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;

#use pocketmine\Server;
#use pocketmine\utils\TextFormat;
#use pocketmine\event\player\PlayerMoveEvent;
#use pocketmine\scheduler\CallbackTask;


class Main extends PluginBase implements CommandExecutor,Listener {
	protected $items;
	protected $leaves;
	protected $itemwear;
	protected $broadcast;
	protected $creative;
	protected $state;
	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$defaults = [
			"ItemIDs" => [
				Item::IRON_AXE, Item::WOODEN_AXE, Item::STONE_AXE,
				Item::DIAMOND_AXE, Item::GOLD_AXE
			],
			"need-item" => true,
			"break-leaves" => true,
			"item-wear" => 1,
			"broadcast-use" => true,
			"creative" => true,
		];
		$cfg=(new Config($this->getDataFolder()."config.yml",
									  Config::YAML,$defaults))->getAll();
		$this->leaves = $cfg["break-leaves"];
		if ($cfg["need-item"]) {
			$this->items = [];
			foreach ($cfg["ItemIDs"] as $i) {
				$this->items[$i] = $i;
			}
			$this->itemwear = $cfg["item-wear"];
		} else {
			$this->items = false;
		}
		$this->creative = $cfg["creative"];
		$this->broadcast = $cfg["broadcast-use"];
		$this->state = [];
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "treecapitator":
				if (!($sender instanceof $sender)) {
					$sender->sendMessage("You can only do this in-game");
					return false;
				}
				if (count($args) > 1) return false;
				$n = $sender->getName();
				if (!count($args)) {
					if (isset($this->state[$n])) {
						$sender->sendMessage("TreeCapitator is active");
					} else {
						$sender->sendMessage("TreeCapitator is inactive");
					}
				} else {
					if (in_array(strtolower($args[0]),["on","true","1"])) {
						$this->state[$n] = $n;
						$sender->sendMessage("TreeCapitator is activated");
					} elseif (in_array(strtolower($args[0]),["off","false","0"])) {
						if (isset($this->state[$n])) unset($this->state[$n]);
						$sender->sendMessage("TreeCapitator is de-activated");
					} else {
						$sender->sendMessage("Invalid state \"".$args[0]."\"");
					}
				}
				return true;
		}
		return false;
	}
	/////////////////////////////////////////////////////////////////////////
	//
	// Event handlers
	//
	/////////////////////////////////////////////////////////////////////////
	public function onPlayerQuit(PlayerQuitEvent $ev) {
		$n = $ev->getPlayer()->getName();
		if (isset($this->state[$n])) unset($this->state[$n]);
	}
	public function onBlockBreak(BlockBreakEvent $ev){
		if ($ev->isCancelled()) return;
		$pl = $ev->getPlayer();
		if (!isset($this->state[$pl->getName()])) return;
		if (!$pl->isCreative() || !$this->creative) {
			if ($this->items && !isset($this->items[$ev->getItem()->getId()])) {
				echo "Not using an Axe\n"; //##DEBUG
				return;
			}
		}
		if ($this->leaves) {
			$damage = $this->destroyTree($ev->getBlock());
		} else {
			$damage = $this->destroyTrunk($ev->getBlock());
		}
		if ($damage && $this->items && $this->itemwear) {
			$hand = $pl->getInventory()->getItemInHand();
			$hand->setDamage($hand->getDamage() + $this->itemwear * $damage);
			$pl->getInventory()->setItemInHand($hand);
			if ($this->broadcast)
				$this->getServer()->broadcastMessage($pl->getName().
																 " used TreeCapitator");
			else
				$pl->sendMessage("Used TreeCapitator");
		}
	}
	/////////////////////////////////////////////////////////////////////////
	//
	// Tree Capitation
	//
	/////////////////////////////////////////////////////////////////////////
	private function destroyTree(Block $bl) {
		$damage = 0;
		if ($bl->getId() != Block::WOOD) return $damage;
		$down = $bl->getSide(Vector3::SIDE_DOWN);
		if ($down->getId() == Block::WOOD) return $damage;
		$l = $bl->getLevel();

		$cX = $bl->getX();
		$cY = $bl->getY();
		$cZ = $bl->getZ();

		for ($y = $cY+1; $y < 128; ++$y) {
			if ($l->getBlockIdAt($cX,$y,$cZ) == Block::AIR) break;

			for ($x = $cX - 4; $x <= $cX + 4; ++$x) {
				for ($z = $cZ - 4; $z <= $cZ + 4; ++$z) {
					$bl = $l->getBlock(new Vector3($x,$y,$z));

					if ($bl->getId() != Block::WOOD &&
						 $bl->getId() != Block::LEAVES) continue;

					++$damage;
					if (mt_rand(1,10) < 3) {
						$l->dropItem($bl,new ItemBlock($bl));
					}
					$l->setBlockIdAt($x,$y,$z,0);
					$l->setBlockDataAt($x,$y,$z,0);
				}
			}
		}

		return $damage;

	}
	private function destroyTrunk(Block $bl) {
		$damage = 0;
		if ($bl->getId() != Block::WOOD) return $damage;
		$down = $bl->getSide(Vector3::SIDE_DOWN);
		if ($down->getId() == Block::WOOD) return $damage;
		$l = $bl->getLevel();
		for ($y= $bl->getY()+1; $y < 128; ++$y) {
			$x = $bl->getX();
			$z = $bl->getZ();
			$bl = $l->getBlock(new Vector3($x,$y,$z));
			if ($bl->getId() != Block::WOOD) break;
			++$damage;
			$l->dropItem($bl,new ItemBlock($bl));
			$l->setBlockIdAt($x,$y,$z,0);
			$l->setBlockDataAt($x,$y,$z,0);
		}
		return $damage;
	}
}
