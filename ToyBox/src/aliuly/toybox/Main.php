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
				"powertool" => true,
				"cloakclock" => true,
			],
			"compasstp" => [
				"item" => Item::COMPASS,
			],
			"cloakclock" => [
				"item" => Item::CLOCK,
			],
			"powertool" => [
				"ItemIDs" => [
					Item::IRON_PICKAXE, Item::WOODEN_PICKAXE, Item::STONE_PICKAXE,
					Item::DIAMOND_PICKAXE, Item::GOLD_PICKAXE
				],
				"need-item" => true,
				"item-wear" => 1,
				"creative" => true,
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
		if ($cfg["modules"]["treecapitator"])
			$this->modules[]= new TreeCapitator($this,$cfg["treecapitator"]);
		if ($cfg["modules"]["powertool"])
			$this->modules[]= new PowerTool($this,$cfg["powertool"]);
		if ($cfg["modules"]["trampoline"])
			$this->modules[] = new Trampoline($this,$cfg["trampoline"]);
		if ($cfg["modules"]["compasstp"])
			$this->modules[] = new CompassTp($this,$cfg["compasstp"]["item"]);
		if ($cfg["modules"]["cloakclock"])
			$this->modules[] = new CloakClock($this,$cfg["cloakclock"]["item"]);
		if (count($this->modules)) {
			$this->state = [];
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}
		$this->getLogger()->info("enabled ".count($this->modules)." modules");
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
	public function unsetState($label,$player) {
		if ($player instanceof CommandSender) $player = $player->getName();
		$player = strtolower($player);
		if (!isset($this->state[$player])) return;
		if (!isset($this->state[$player][$label])) return;
		unset($this->state[$player][$label]);
	}

}
