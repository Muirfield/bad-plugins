<?php
namespace aliuly\autoheal;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\TextFormat;
use pocketmine\permission\Permission;

//use pocketmine\Player;
//use pocketmine\Server;
//use pocketmine\item\Item;
//use pocketmine\network\protocol\SetHealthPacket;

class Main extends PluginBase{
	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"ranks" => [
				"vip1" => [40, 1, false],
				"vip2" => [80, 2, false],
				"vip3" => [800, 1, false],
			],
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			unset($defaults["ranks"]);
		}
		$cfg = (new Config($this->getDataFolder()."config.yml",
								 Config::YAML,$defaults))->getAll();
		if (!isset($cfg["ranks"])) $cfg["ranks"] = [];

		$cnt = 0;
		foreach ($cfg["ranks"] as $rank=>$dat) {
			if (count($dat) == 3) {
				list($rate,$amount,$perms) = $dat;
			} elseif (count($dat) == 2) {
				list($rate,$amount) = $dat;
				$perms = false;
			} else {
				$this->getLogger()->info(TextFormat::RED.
												 "Skipping rank: ".$rank);
				continue;
			}
			$p = new Permission("autoheal.".$rank,"Enables auto heal for ".$rank,
									  $perms);
			$this->getServer()->getPluginManager()->addPermission($p);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"healTimer"],[$rank,$amount]),$rate);
			++$cnt;
		}
		if ($cnt == 0) {
			$this->getLogger()->info(TextFormat::RED.
											 "No ranks defined, disabling...");
			return;
		}
		$this->getLogger()->info($cnt." ranks configured");
	}
	public function healTimer($rank,$amount) {
		$pls = $this->getServer()->getOnlinePlayers();
		foreach($pls as $pl) {
			if (!$pl->hasPermission("autoheal")) continue;
			if (!$pl->hasPermission("autoheal.".$rank)) continue;
			// Yes, this is a vip!
			$new = $pl->getHealth() + $amount;
			if ($new > $pl->getMaxHealth()) $new = $pl->getMaxHealth();
			$pl->setHealth($new);
		}
	}
}
