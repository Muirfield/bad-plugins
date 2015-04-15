<?php
namespace aliuly\pmptemplate;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

use pocketmine\utils\Config;


use pocketmine\item\Item;


use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CallbackTask;


class Main extends PluginBase implements CommandExecutor,Listener {
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

	// Paginate output
	private function getPageNumber(array &$args) {
		$pageNumber = 1;
		if (count($args) && is_numeric($args[count($args)-1])) {
			$pageNumber = (int)array_pop($args);
			if($pageNumber <= 0) $pageNumber = 1;
		}
		return $pageNumber;
	}
	private function paginateText(CommandSender $sender,$pageNumber,array $txt) {
		$hdr = array_shift($txt);
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage( TextFormat::GREEN.$hdr.TextFormat::RESET);
			foreach ($txt as $ln) $sender->sendMessage($ln);
			return true;
		}
		$pageHeight = 5;
		$hdr = TextFormat::GREEN.$hdr. TextFormat::RESET;
		if (($pageNumber-1) * $pageHeight >= count($txt)) {
			$sender->sendMessage($hdr);
			$sender->sendMessage("Only ".intval(count($txt)/$pageHeight+1)." pages available");
			return true;
		}
		$hdr .= TextFormat::RED." ($pageNumber of ".intval(count($txt)/$pageHeight+1).")".TextFormat::RESET;
		$sender->sendMessage($hdr);
		for ($ln = ($pageNumber-1)*$pageHeight;$ln < count($txt) && $pageHeight--;++$ln) {
			$sender->sendMessage($txt[$ln]);
		}
		return true;
	}
	private function paginateTable(CommandSender $sender,$pageNumber,array $tab) {
		$cols = [];
		for($i=0;$i < count($tab[0]);$i++) $cols[$i] = strlen($tab[0][$i]);
		foreach ($tab as $row) {
			for($i=0;$i < count($row);$i++) {
				if (($l=strlen($row[$i])) > $cols[$i]) $cols[$i] = $l;
			}
		}
		$txt = [];
		foreach ($tab as $row) {
			$txt[] = sprintf("%-$cols[0]s %-$cols[1]s %-$cols[2]s %-$cols[3]s",
								  $row[0],$row[1],$row[2],$row[3]);
		}
		return $this->paginateText($sender,$pageNumber,$txt);
	}
	// Standard call-backs
	public function onDisable() {
		$this->getLogger()->info("Commander Unloaded!");

	}
	public function onEnable(){
		$this->getLogger()->info("* Commander Enabled!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$data = new \volt\api\MonitoredWebsiteData($this);
		$page = new \volt\api\DynamicPage("/page", $this);
		$data["pmptemplate"] = ["one","two","Three"];
		print_r($data["pmptemplate"]);
		$page("This is content\n".
				"pmtemplate<br/>".
				"{{#each pmptemplate}}<h6>{{this}}</h6>{{/each}}");
	}
	public function onMove(PlayerMoveEvent $ev) {
		return;
		$from = $ev->getFrom();
		$to = clone $ev->getTo();
		$dx = $to->getX()-$from->getX();
		$dy =$to->getY()-$from->getY();
		$dz =$to->getZ()-$from->getZ();
		$to->setComponents($from->getX() - $dx*2, $to->getY(), $from->getZ() - $dz*2);
	//$ev->getPlayer()->teleport(new Vector3($from->getX() - $dx, $to->getY(), $from->getZ() - $dz));
		$ev->setTo($to);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "m":
				if (!$this->access($sender,"cmd.cmd.m")) return true;
				return $this->cmdMain($sender,$args);
		}
		return false;
	}
	// Command implementations

	private function cmdMain(CommandSender $c,$args) {
		$c->sendMessage("ARGS: ".implode(',',$args));
		$c->setMotion(new \pocketmine\math\Vector3(0,20,0));
		//if (count($args) == 0) return false;
		//$p = $this->getServer()->getPlayer($args[0]);
		//if ($p == null) return false;
		//$pk = new SetHealthPacket;
		//$pk->health = $p->getHealth()-1;
		//$p->dataPacket($pk);
		return true;
	}
}
