<?php
namespace aliuly\getping;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;

use pocketmine\utils\Binary;

use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;

class Main extends PluginBase implements CommandExecutor,Listener {
	protected $lastPing;

	public function onEnable(){
		$this->lastPing = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function getPing($n) {
		if (isset($this->lastPing[strtolower($n)])) {
			return $this->lastPing[strtolower($n)];
		}
		return "N/A";
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "getping":
				if (!count($args)) {
					foreach ($this->getServer()->getOnlinePlayers() as $m) {
						$n = $m->getName();
						$sender->sendMessage($n.": ".$this->getPing($n));
					}
				} else {
					foreach ($args as $n) {
						$p = $this->getServer()->getPlayer($n);
						if ($p == null) {
							$sender->sendMessage($n.": Not found");
							continue;
						}
						$n = $p->getName();
						$sender->sendMessage($n.": ".$this->getPing($n));
					}
				}
				return true;
				break;
		}
		return false;
	}
	//
	// Event Handlers
	//
	public function onPkt(DataPacketReceiveEvent $e) {
		if ($e->getPacket()->pid() !== 0x00) return;
		$this->lastPing[strtolower($e->getPlayer()->getName())]
			= Binary::readLong($e->getPacket()->buffer)/1000.0;
	}
	public function onQuit(PlayerQuitEvent $e) {
		unset($this->lastPing[strtolower($e->getPlayer()->getName())]);
	}
}
