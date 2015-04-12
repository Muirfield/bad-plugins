<?php
namespace aliuly\autoheal;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\TextFormat;

//use pocketmine\Player;
//use pocketmine\Server;
//use pocketmine\item\Item;
//use pocketmine\network\protocol\SetHealthPacket;

class Main extends PluginBase{
	protected $players;

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"ranks" => [
				"vip1" => [40, 1],
				"vip2" => [80, 2],
				"vip3" => [800, 1],
			],
			"players" => [
				"joe" => "vip1",
				"tom" => "vip2",
				"smith" => "vip3",
			],
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			unset($defaults["ranks"]);
			unset($defaults["players"]);
		}
		$cfg = (new Config($this->getDataFolder()."config.yml",
								 Config::YAML,$defaults))->getAll();
		if (!isset($cfg["ranks"])) $cfg["ranks"] = 0;
		$cnt = 0;
		$this->players = [];
		if (isset($cfg["players"])) {
			foreach ($cfg["players"] as $name => $rank) {
				if (!isset($cfg["ranks"][$rank])) continue;
				++$cnt;
				$this->players[$rank][$name] = $name;
			}
		}
		if ($cnt == 0) {
			$this->getLogger()->info(TextFormat::RED.
											 "No ranks or players defined, disabling...");
			return;
		}
		$rcnt = 0;
		foreach ($cfg["ranks"] as $rank=>$det) {
			if (!isset($this->players[$rank])) continue;
			++$rcnt;
			list($rate,$amount) = $det;
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"healTimer"],[$rank,$amount]),$rate);
		}
		$this->getLogger()->info($rcnt." ranks defined");
		$this->getLogger()->info($cnt." players registered");
	}
	public function healTimer($rank,$amount) {
		$pls = $this->getServer()->getOnlinePlayers();
		foreach($pls as $pl) {
			if (!isset($this->players[$rank][$pl->getName()])) continue;
			// Yes, this is a vip!
			$new = $pl->getHealth() + $amount;
			if ($new > $pl->getMaxHealth()) $new = $pl->getMaxHealth();
			$pl->setHealth($new);
		}
	}
}
