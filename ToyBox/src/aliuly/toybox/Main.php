<?php
namespace aliuly\toybox;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerQuitEvent;

class Main extends PluginBase implements Listener {
	protected $state;
	protected $modules;

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"modules" => [
				"treecapitator" => true,
				"compasstp" => true,
				"trampoline" => true,
			],
			"treecapitator" => [
				"ItemIDs" => [
					Item::IRON_AXE, Item::WOODEN_AXE, Item::STONE_AXE,
					Item::DIAMOND_AXE, Item::GOLD_AXE
				],
				"need-item" => true,
				"break-leaves" => true,
				"item-wear" => 1,
				"broadcast-use" => true,
				"creative" => true,
			],
			"trampoline" => [
				"blocks" => [ Block::SPONGE ],
			],
		];
		$cnt = 0;
		$cfg=(new Config($this->getDataFolder()."config.yml",
									  Config::YAML,$defaults))->getAll();
		if ($cfg["modules"]["treecapitator"]) {
			$cnt++;
			$this->modules[]= new TreeCapitator($this,$cfg["treecapitator"]);
		}
		if ($cfg["modules"]["trampoline"]) {
			$cnt++;
			$this->modules[] = new Trampoline($this,$cfg["trampoline"]);
		}
		if ($cfg["modules"]["compasstp"]) {
			$cnt++;
			$this->modules[] = new CompassTp($this);
		}
		if ($cnt) {
			$this->state = [];
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}
		$this->getLogger()->info("enabled $cnt modules");
	}

	public function onPlayerQuit(PlayerQuitEvent $ev) {
		$n = strtolower($ev->getPlayer()->getName());
		if (isset($this->state[$n])) unset($this->state[$n]);
	}
	public function getState($label,$player,$default) {
		if ($player instanceof CommandSender) $player = $player->getName();
		$player = strtolower($player);
		if (!isset($this->state[$player])) return $default;
		if (!isset($this->state[$player][$label])) return $default;
		return $this->state[$player][$label];
	}
	public function setState($label,$player,$val) {
		if ($player instanceof CommandSender) $player = $player->getName();
		$player = strtolower($player);
		if (!isset($this->state[$player])) $this->state[$player] = [];
		$this->state[$player][$label] = $val;
	}

}
