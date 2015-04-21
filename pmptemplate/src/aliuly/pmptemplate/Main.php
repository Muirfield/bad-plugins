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
use pocketmine\network\protocol\UpdateBlockPacket;

use pocketmine\item\Item;


use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\Position;

class Main extends PluginBase implements CommandExecutor,Listener {
	static public $magic = "WorlEditor Clip v1\n";

	protected $clipboard;

	protected function loadmodel() {
		$f = $this->getDataFolder()."model.pmc";
		$txt = file_get_contents($f);
		if (substr($txt,0,strlen(self::$magic)) != self::$magic) {
			$this->getLogger()->info("$f is not in the right format!");
			return;
		}
		$this->clipboard = unserialize(substr($txt,strlen(self::$magic)));
	}
	private function W_move($clipboard,Position $from, Position $to) {
		$pktab = [];
		// Remove...
		$fx = round($clipboard[0][0]+$from->x-0.5);
		$fy = round($clipboard[0][0]+$from->y);
		$fz = round($clipboard[0][0]+$from->z-0.5);

		$l = $from->getLevel();

		foreach($clipboard[1] as $x => $i){
			foreach($i as $y => $j){
				foreach($j as $z => $block){
					$pk = new UpdateBlockPacket();
					$pk->x = $x + $fx;
					$pk->y = $y + $fy;
					$pk->z = $z + $fz;
					$pk->block = $l->getBlockIdAt($pk->x,$pk->y,$pk->z);
					$pk->meta = $l->getBlockDataAt($pk->x,$pk->y,$pk->z);
					$pktab[implode(":",[$pk->x,$pk->y,$pk->z])] = $pk;
				}
			}
		}
		// Render
		$fx = round($clipboard[0][0]+$to->x-0.5);
		$fy = round($clipboard[0][0]+$to->y);
		$fz = round($clipboard[0][0]+$to->z-0.5);

		foreach($clipboard[1] as $x => $i){
			foreach($i as $y => $j){
				foreach($j as $z => $block){
					$pk = new UpdateBlockPacket();
					$pk->x = $x + $fx;
					$pk->y = $y + $fy;
					$pk->z = $z + $fz;
					$pk->block = ord($block{0});
					$pk->meta = ord($block{1});
					$pktab[implode(":",[$pk->x,$pk->y,$pk->z])] = $pk;
				}
			}
		}
		// Transmit changes
		foreach ($pktab as $pk) {
			Server::broadcastPacket($l->getUsingChunk($pk->x >> 4,
																	$pk->z >> 4), $pk);
		}
	}

	private function W_render($clipboard, Position $pos){
		if(count($clipboard) !== 2) return;
		$clipboard[0][0] += $pos->x - 0.5;
		$clipboard[0][1] += $pos->y;
		$clipboard[0][2] += $pos->z - 0.5;
		$offset = array_map("round", $clipboard[0]);
		$count = 0;

		$l = $pos->getLevel();

		foreach($clipboard[1] as $x => $i){
			foreach($i as $y => $j){
				foreach($j as $z => $block){
					$pk = new UpdateBlockPacket();
					$pk->x = $x + $offset[0];
					$pk->y = $y + $offset[1];
					$pk->z = $z + $offset[2];
					$pk->block = ord($block{0});
					$pk->meta = ord($block{1});
					Server::broadcastPacket($l->getUsingChunk($pk->x >> 4,
																			$pk->z >> 4), $pk);
				}
			}
		}
	}
	private function W_remove($clipboard,Position $pos){
		if(count($clipboard) !== 2) return;
		$clipboard[0][0] += $pos->x - 0.5;
		$clipboard[0][1] += $pos->y;
		$clipboard[0][2] += $pos->z - 0.5;
		$offset = array_map("round", $clipboard[0]);
		$count = 0;

		$l = $pos->getLevel();

		foreach($clipboard[1] as $x => $i){
			foreach($i as $y => $j){
				foreach($j as $z => $block){

					$pk = new UpdateBlockPacket();
					$pk->x = $x + $offset[0];
					$pk->y = $y + $offset[1];
					$pk->z = $z + $offset[2];
					$pk->block = $l->getBlockIdAt($pk->x,$pk->y,$pk->z);
					$pk->meta = $l->getBlockDataAt($pk->x,$pk->y,$pk->z);

					Server::broadcastPacket($l->getUsingChunk($pk->x >> 4,
																			$pk->z >> 4), $pk);
				}
			}
		}
	}


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
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		//$this->getLogger()->info("* Commander Enabled!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->loadmodel();

		/*
		$data = new \volt\api\MonitoredWebsiteData($this);
		$page = new \volt\api\DynamicPage("/page", $this);
		$data["pmptemplate"] = ["one","two","Three"];
		print_r($data["pmptemplate"]);
		$page("This is content\n".
				"pmtemplate<br/>".
				"{{#each pmptemplate}}<h6>{{this}}</h6>{{/each}}");
		*/
	}
	public function onMove(PlayerMoveEvent $ev) {
		// Crazy thing!
		if ($ev->getPlayer()->getName() == "gordipapi") return;
		//$this->W_remove($this->clipboard,$ev->getFrom());
		//$this->W_render($this->clipboard,$ev->getTo());
		$this->W_move($this->clipboard,$ev->getFrom(),$ev->getTo());

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
		if (!$this->inGame($c)) return true;
		if (count($args) == 0) return false;
		if ($args[0] == "render") {
			$this->W_render($this->clipboard,$c);
		} elseif ($args[0] == "rm") {
			$this->W_remove($this->clipboard,$c);
		}
		return false;
		//$c->sendMessage("ARGS: ".implode(',',$args));
		//$c->setMotion(new \pocketmine\math\Vector3(0,20,0));
		//$p = $this->getServer()->getPlayer($args[0]);
		//if ($p == null) return false;
		//$pk = new SetHealthPacket;
		//$pk->health = $p->getHealth()-1;
		//$p->dataPacket($pk);
		return true;
	}
}
