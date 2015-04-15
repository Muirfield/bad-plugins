<?php
namespace WorldEditor;

use pocketmine\Player;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerQuitEvent;

class WorldEditor extends PluginBase implements Listener{
	static public $magic = "WorlEditor Clip v1\n";
	private $cfg;
	private $data;

	public static function checkExt($name,$ext) {
		if (substr($name,-strlen($ext),strlen($ext)) == $ext) return $name;
		return $name.$ext;
	}

	public function getData($player) {
		if ($player instanceof Player) {
			$iusername = $player->getName();
		} elseif (is_string($player)) {
			$iusername = $player;
		} else {
			return false;
		}
		if (!isset($this->data[$iusername])) {
			$this->data[$iusername] = [
				"block-limit" => $this->cfg["block-limit"],
				"selection" => [false, false],
				"clipboard" => null,
				"wand-usage" => true ];
		}
		return $this->data[$iusername];
	}
	public function setData($player,$data) {
		if ($player instanceof Player) {
			$iusername = $player->getName();
		} elseif (is_string($player)) {
			$iusername = $player;
		} else {
			return false;
		}
		$this->data[$iusername] = $data;
	}

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$defaults = [
			"block-limit" => -1,
			"wand-item" => "IRON_HOE",
		];
		$this->cfg=(new Config($this->getDataFolder()."config.yml",
									  Config::YAML,$defaults))->getAll();
		if(!isset($this->cfg["block-limit"])) $this->cfg["block-limit"]=-1;
		if(!isset($this->cfg["wand-item"])) $this->cfg["wand-item"]="IRON_HOE";
		if(!is_numeric($this->cfg["block-limit"])){
			$this->getLogger()->alert(TextFormat::RED . "Wrong format for block-limit.");
			$this->cfg["block-limit"] = -1;
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$cmd = strtolower($command->getName());

		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.\n");
			return false;
		}
		$data = $this->getData($sender);

		if($cmd{0} === "/"){
			$cmd = substr($cmd, 1);
		}

		switch($cmd){
			case "paste":
				$m = $this->W_paste($data["clipboard"], new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				if ($m) $sender->sendMessage($m);
				break;
			case "load":
				if (count($args) == 0) return false;
				$f = self::checkExt(implode(" ",$args),".pmc");
				if (!file_exists($this->getDataFolder().$f)) {
					$sender->sendMessage("File $f not found!");
					return true;
				}
				$txt = file_get_contents($this->getDataFolder().$f);
				if ($txt === false) {
					$sender->sendMessage("Error reading $f");
					return true;
				}
				if (substr($txt,0,strlen(self::$magic)) != self::$magic) {
					$sender->sendMessage("$f is not in the right format!");
					return true;
				}
				$data["clipboard"] = unserialize(substr($txt,strlen(self::$magic)));
				$this->setData($sender,$data);
				$sender->sendMessage("$f loaded into the clipboard");
				break;
			case "save":
				if (count($data["clipboard"]) !== 2) {
					$sender->sendMessage("Copy something first.");
					return false;
				}
				if (count($args) == 0) return false;
				$f = self::checkExt(implode(" ",$args),".pmc");
				if (!file_put_contents($this->getDataFolder().$f,
											  self::$magic.serialize($data["clipboard"]))){
					$sender->sendMessage("Error writing $f");
				}
				$sender->sendMessage("Clipboard saved as $f");
				break;
			case "copy":
				$count = $this->countBlocks($data["selection"],
													 $starX,$starY,$starZ);
				if ($data["block-limit"] > 0 && $count > $data["block-limit"]) {
					$sender->sendMessage("Block limit of ".$data["block-limit"]
												." exceeded, tried to copy ".
												$count." block(s).");
					break;
				}
				$blocks = $this->W_copy($data["selection"],$m);
				if ($m) $sender->sendMessage($m);
				if(count($blocks) > 0){
					$offset = [ $starX - $sender->getX() - 0.5,
									$starY - $sender->getY(),
									$starZ - $sender->getZ() - 0.5 ];
					$data["clipboard"] =  [ $offset, $blocks ];
					$this->setData($sender,$data);
				}
				break;
			case "cut":
				$count = $this->countBlocks($data["selection"],
													 $startX,$startY,$startZ);
				if ($data["block-limit"] > 0 && $count > $data["block-limit"]) {
					$sender->sendMessage("Block limit of ".$data["block-limit"]
												." exceeded, tried to cut ".
												$count." block(s).");
					break;
				}
				$blocks = $this->W_cut($data["selection"],$m);
				if ($m) $sender->sendMessage($m);
				if(count($blocks) > 0){
					$offset = [ $startX - $sender->getX() - 0.5,
									$startY - $sender->getY(),
									$startZ - $sender->getZ() - 0.5];
					$data["clipboard"] =  [ $offset, $blocks ];
					$this->setData($sender,$data);
				}
				break;
			case "toggleeditwand":
				$data["wand-usage"] = !$data["wand-usage"];
				$this->setData($sender,$data);
				$sender->sendMessage("Wand Item is now ".
											($data["wand-usage"]? "enabled":"disabled"));
				break;
			case "wand":
				if($sender->isCreative()){
					$sender->sendMessage("You are on creative mode");
				} elseif($sender->getInventory()->contains(Item::fromString($this->cfg["wand-item"]))) {
					$sender->sendMessage("You already have the wand item.");
					break;
				} else{
					$sender->getInventory()->addItem(Item::fromString($this->cfg["wand-item"]));
				}
				$sender->sendMessage("Break block to set pos #1 and Tap to set Pos #2.");
				break;
			case "selection":
				$sender->sendMessage("pos1: (".implode(",",$data["selection"][0]).")");
				$sender->sendMessage("pos2: (".implode(",",$data["selection"][1]).")");
				break;
			case "desel":
				$data["selection"] = [false,false];
				$this->setData($sender,$data);
				$sender->sendMessage("Selection cleared.");
				break;
			case "limit":
				if(!isset($args[0]) or trim($args[0]) === "") return false;
				$limit = intval($args[0]);
				if($limit < 0){
					if($this->cfg["block-limit"] > 0){
						$limit = $this->cfg["block-limit"];
					} else {
						$limit = -1;
					}
				} elseif ($this->cfg["block-limit"] > 0) {
					$limit = min($this->cfg["block-limit"],$limit);
				}
				$data["block-limit"] = $limit;
				$this->setData($sender,$data);
				$sender->sendMessage("Block limit set to ".
											($limit === -1 ? "none":$limit)." block(s).");
				break;
			case "pos1":
				$m = $this->setPosition1($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				if ($m) $sender->sendMessage($m);
				break;
			case "pos2":
				$m = $this->setPosition2($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				if ($m) $sender->sendMessage($m);
				break;
			case "hsphere":
			case "sphere":
				if (count($args) != 2) return false;
				$filled =  $cmd == "sphere";
				$radius = abs(floatval($args[1]));
				$items = Item::fromString($args[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$sender->sendMessage("Incorrect block");
							return true;
						}
					}
					$m = $this->W_sphere(new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()), $items, $radius, $radius, $radius, $filled);
					if ($m) $sender->sendMessage($m);
				} else {
					$this->sendMessage("Incorrect block, use ID.");
				}
				break;
			case "set":
				if (count($args) != 1) return false;
				$count = $this->countBlocks($data["selection"]);
				if($count > $data["block-limit"] and $data["block-limit"] > 0){
					$sender->sendMessage("Block limit of ".$data["block-limit"].
												" exceeded, tried to change $count blocks");
					break;
				}
				$items = Item::fromString($args[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$sender->sendMessage("Incorrect block.");
							return true;
						}
					}
					$m = $this->W_set($data["selection"], $items);
					if ($m) $sender->sendMessage($m);
				} else {
					$this->sendMessage("Incorrect block, use ID.");
				}
				break;
			case "fill":
				if (count($args) != 1) return false;
				$count = $this->countBlocks($data["selection"]);
				if($count > $data["block-limit"] and $data["block-limit"] > 0){
					$sender->sendMessage("Block limit of ".$data["block-limit"].
												" exceeded, tried to change $count blocks");
					break;
				}
				$items = Item::fromString($args[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$sender->sendMessage("Incorrect block.");
							return true;
						}
					}
					$m = $this->W_fill($data["selection"], $items);
					if ($m) $sender->sendMessage($m);
				} else {
					$this->sendMessage("Incorrect block, use ID.");
				}
				break;
			case "hregion":
				if (count($args) != 1) return false;
				$count = $this->countBlocks($data["selection"]);
				if($count > $data["block-limit"] and $data["block-limit"] > 0){
					$sender->sendMessage("Block limit of ".$data["block-limit"].
												" exceeded, tried to change $count blocks");
					break;
				}
				$items = Item::fromString($args[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$sender->sendMessage("Incorrect block.");
							return true;
						}
					}
					$m = $this->W_region($data["selection"], $items);
					if ($m) $sender->sendMessage($m);
				} else {
					$this->sendMessage("Incorrect block, use ID.");
				}
				break;
			case "replace":
				if (count($args) != 2) return false;
				$count = $this->countBlocks($data["selection"]);
				if($count > $data["block-limit"] and $data["block-limit"] > 0){
					$sender->sendMessage("Block limit of ".$data["block-limit"].
												" exceeded, tried to change $count blocks");
					break;
				}
				$item1 = Item::fromString($args[0]);
				if($item1->getID() > 0xff){
					$this->output .= "Incorrect block.";
					break;
				}

				$items2 = Item::fromString($args[2], true);
				if($items2){
					foreach($items2 as $item){
						if($item->getID() > 0xff){
							$sender->sendMessage("Incorrect block.");
							return true;
						}
					}
					$m = $this->W_replace($data->get("selection"), $item1, $items2);
					if ($m) $sender->sendMessage($m);
				} else {
					$sender->sendMessage("Incorrect block, use ID.");
				}
				break;
			default:
			case "help":
				$sender->sendMessage("Commands: //cut, //copy, //paste, //sphere, //hsphere, //desel, //limit, //pos1, //pos2, //set, //replace, //help, //wand, /toggleeditwand");
				break;
			default:
				return false;
		}
		return true;
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		$target = new Position($event->getBlock()->x, $event->getBlock()->y, $event->getBlock()->z, $event->getBlock()->getLevel());

		$data = $this->getData($player);

		if($data['wand-usage'] && $item->getID() == Item::fromString($this->cfg["wand-item"])->getID()){
			$m = $this->setPosition2($player, $target);
			if ($m) $player->sendMessage($m);
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		$target = new Position($event->getBlock()->x, $event->getBlock()->y, $event->getBlock()->z, $event->getBlock()->getLevel());

		$data = $this->getData($player);

		if($data['wand-usage'] && $item->getID() == Item::fromString($this->cfg["wand-item"])->getID()){
			$m = $this->setPosition1($player, $target);
			if ($m) $player->sendMessage($m);
			$event->setCancelled();
		}
	}

	public function setPosition1($username, Position $position){
		$data = $this->getData($username);
		$data["selection"][0] = [ round($position->x),
										  round($position->y),
										  round($position->z),
										  $position->getLevel()->getName() ];
		$this->setData($username,$data);
		$count = $this->countBlocks($data["selection"]);
		if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
		return "First position set to (".implode(",",$data["selection"][0]).
												  ")$count.";
	}

	public function setPosition2($username, Position $position){
		$data = $this->getData($username);
		$data["selection"][1] = [ round($position->x),
										  round($position->y),
										  round($position->z),
										  $position->getLevel()->getName() ];
		$this->setData($username,$data);
		$count = $this->countBlocks($data["selection"]);
		if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
		return "Second position set to (".implode(",",$data["selection"][1]).
												  ")$count.";
	}

	private function countBlocks($selection, &$startX = null, &$startY = null, &$startZ = null){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			return false;
		}
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);
		return ($endX - $startX + 1) * ($endY - $startY + 1) * ($endZ - $startZ + 1);
	}

	private function W_paste($clipboard, Position $pos){
		if(count($clipboard) !== 2) return "Copy something first.";
		$clipboard[0][0] += $pos->x - 0.5;
		$clipboard[0][1] += $pos->y;
		$clipboard[0][2] += $pos->z - 0.5;
		$offset = array_map("round", $clipboard[0]);
		$count = 0;

		foreach($clipboard[1] as $x => $i){
			foreach($i as $y => $j){
				foreach($j as $z => $block){
					$b = Block::get(ord($block{0}), ord($block{1}));
					$count += (int) $pos->getLevel()->setBlock(new Vector3($x + $offset[0], $y + $offset[1], $z + $offset[2]), $b, false);
					unset($b);
				}
			}
		}
		return "$count block(s) have been changed.";
	}

	private function W_copy($selection,&$m){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$m = "Make a selection first.";
			return [];
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);

		$blocks = array();
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);
		$count = $this->countBlocks($selection);
		for($x = $startX; $x <= $endX; ++$x){
			$blocks[$x - $startX] = array();
			for($y = $startY; $y <= $endY; ++$y){
				$blocks[$x - $startX][$y - $startY] = array();
				for($z = $startZ; $z <= $endZ; ++$z){
					$b = $level->getBlock(new Vector3($x, $y, $z));
					$blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
					unset($b);
				}
			}
		}
		$m = "$count block(s) have been copied.";
		return $blocks;
	}

	private function W_cut($selection,&$m){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$m = "Make a selection first.";
			return [];
		}
		$totalCount = $this->countBlocks($selection);
		if($totalCount > 524288){
			$send = false;
		}else{
			$send = true;
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);

		$blocks = array();
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);
		$count = $this->countBlocks($selection);
		$air = new Air();
		for($x = $startX; $x <= $endX; ++$x){
			$blocks[$x - $startX] = array();
			for($y = $startY; $y <= $endY; ++$y){
				$blocks[$x - $startX][$y - $startY] = array();
				for($z = $startZ; $z <= $endZ; ++$z){
					$b = $level->getBlock(new Vector3($x, $y, $z));
					$blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
					$level->setBlock(new Vector3($x, $y, $z), $air, false, $send);
					unset($b);
				}
			}
		}
		if($send === false){
			$forceSend = function($X, $Y, $Z){
				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
			};
			$forceSend->bindTo($level, $level);
			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
						$forceSend($X,$Y,$Z);
					}
				}
			}
		}
		$m = "$count block(s) have been cut.";
		return $blocks;
	}

	private function W_set($selection, $blocks){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			return "Make a selection first.";
		}
		$totalCount = $this->countBlocks($selection);
		if($totalCount > 524288){
			$send = false;
		}else{
			$send = true;
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);
		$bcnt = count($blocks) - 1;
		if($bcnt < 0){
			return "Incorrect blocks.";
		}
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);

		$count = 0; //$count = $this->countBlocks($selection);
		for($x = $startX; $x <= $endX; ++$x){
			for($y = $startY; $y <= $endY; ++$y){
				for($z = $startZ; $z <= $endZ; ++$z){
					$a = $level->getBlock(new Vector3($x, $y, $z));
					$b = $blocks[mt_rand(0, $bcnt)];
					if($a->getID() != 0){
						$count += (int) $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
					}
				}
			}
		}
		if($send === false){
			$forceSend = function($X, $Y, $Z){
				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
			};
			$forceSend->bindTo($level, $level);
			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
						$forceSend($X,$Y,$Z);
					}
				}
			}
		}
		return "$count block(s) have been changed.";
	}

	private function W_fill($selection, $blocks){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			return "Make a selection first.";
		}
		$totalCount = $this->countBlocks($selection);
		if($totalCount > 524288){
			$send = false;
		}else{
			$send = true;
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);
		$bcnt = count($blocks) - 1;
		if($bcnt < 0){
			return "Incorrect blocks.";
		}
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);

		$count = 0; //$count = $this->countBlocks($selection);
		for($x = $startX; $x <= $endX; ++$x){
			for($y = $startY; $y <= $endY; ++$y){
				for($z = $startZ; $z <= $endZ; ++$z){
					$a = $level->getBlock(new Vector3($x, $y, $z));
					$b = $blocks[mt_rand(0, $bcnt)];
					$count += (int) $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
				}
			}
		}
		if($send === false){
			$forceSend = function($X, $Y, $Z){
				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
			};
			$forceSend->bindTo($level, $level);
			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
						$forceSend($X,$Y,$Z);
					}
				}
			}
		}
		return "$count block(s) have been changed.";
	}

	private function W_region($selection, $blocks){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			return "Make a selection first.";
		}
		$totalCount = $this->countBlocks($selection);
		if($totalCount > 524288){
			$send = false;
		}else{
			$send = true;
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);
		$bcnt = count($blocks) - 1;
		if($bcnt < 0){
			return "Incorrect blocks.";
		}
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);

		$count = 0; //$count = $this->countBlocks($selection);

		for($x = $startX; $x <= $endX; ++$x){
			for($y = $startY; $y <= $endY; ++$y){
				foreach([$startZ,$endZ] as $z) {
					$a = $level->getBlock(new Vector3($x, $y, $z));
					$b = $blocks[mt_rand(0, $bcnt)];
					$count += (int) $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
				}
			}
		}
		for($x = $startX; $x <= $endX; ++$x){
			for($z = $startZ; $z <= $endZ; ++$z){
				foreach([$startY,$endY] as $y) {
					$a = $level->getBlock(new Vector3($x, $y, $z));
					$b = $blocks[mt_rand(0, $bcnt)];
					$count += (int) $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
				}
			}
		}
		for($y = $startY; $y <= $endY; ++$y){
			for($z = $startZ; $z <= $endZ; ++$z){
				foreach([$startX,$endX] as $x) {
					$a = $level->getBlock(new Vector3($x, $y, $z));
					$b = $blocks[mt_rand(0, $bcnt)];
					$count += (int) $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
				}
			}
		}


		if($send === false){
			$forceSend = function($X, $Y, $Z){
				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
			};
			$forceSend->bindTo($level, $level);
			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
						$forceSend($X,$Y,$Z);
					}
				}
			}
		}
		return "$count block(s) have been changed.";
	}

	private function W_replace($selection, Item $block1, $blocks2){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			return "Make a selection first.";
		}

		$totalCount = $this->countBlocks($selection);
		if($totalCount > 524288){
			$send = false;
		}else{
			$send = true;
		}
		$level = $this->getServer()->getLevelByName($selection[0][3]);
		$id1 = $block1->getID();
		$meta1 = $block1->getDamage();

		$bcnt2 = count($blocks2) - 1;
		if($bcnt2 < 0){
			return "Incorrect blocks.";
		}

		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);
		$count = 0;
		for($x = $startX; $x <= $endX; ++$x){
			for($y = $startY; $y <= $endY; ++$y){
				for($z = $startZ; $z <= $endZ; ++$z){
					$b = $level->getBlock(new Vector3($x, $y, $z));
					if($b->getID() === $id1 and ($meta1 === false or $b->getDamage() === $meta1)){
						$count += (int) $level->setBlock($b, $blocks2[mt_rand(0, $bcnt2)]->getBlock(), false, $send);
					}
					unset($b);
				}
			}
		}
		if($send === false){
			$forceSend = function($X, $Y, $Z){
				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
			};
			$forceSend->bindTo($level, $level);
			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
						$forceSend($X,$Y,$Z);
					}
				}
			}
		}
		return "$count block(s) have been changed.";
	}

	public static function lengthSq($x, $y, $z){
		return ($x * $x) + ($y * $y) + ($z * $z);
	}

	private function W_sphere(Position $pos, $blocks, $radiusX, $radiusY, $radiusZ, $filled = true){
		$count = 0;

		$radiusX += 0.5;
		$radiusY += 0.5;
		$radiusZ += 0.5;

		$invRadiusX = 1 / $radiusX;
		$invRadiusY = 1 / $radiusY;
		$invRadiusZ = 1 / $radiusZ;

		$ceilRadiusX = (int) ceil($radiusX);
		$ceilRadiusY = (int) ceil($radiusY);
		$ceilRadiusZ = (int) ceil($radiusZ);

		$bcnt = count($blocks) - 1;

		$nextXn = 0;
		$breakX = false;
		for($x = 0; $x <= $ceilRadiusX and $breakX === false; ++$x){
			$xn = $nextXn;
			$nextXn = ($x + 1) * $invRadiusX;
			$nextYn = 0;
			$breakY = false;
			for($y = 0; $y <= $ceilRadiusY and $breakY === false; ++$y){
				$yn = $nextYn;
				$nextYn = ($y + 1) * $invRadiusY;
				$nextZn = 0;
				$breakZ = false;
				for($z = 0; $z <= $ceilRadiusZ; ++$z){
					$zn = $nextZn;
					$nextZn = ($z + 1) * $invRadiusZ;
					$distanceSq = WorldEditor::lengthSq($xn, $yn, $zn);
					if($distanceSq > 1){
						if($z === 0){
							if($y === 0){
								$breakX = true;
								$breakY = true;
								break;
							}
							$breakY = true;
							break;
						}
						break;
					}

					if($filled === false){
						if(WorldEditor::lengthSq($nextXn, $yn, $zn) <= 1 and WorldEditor::lengthSq($xn, $nextYn, $zn) <= 1 and WorldEditor::lengthSq($xn, $yn, $nextZn) <= 1){
							continue;
						}
					}

					$count += (int) $pos->getLevel()->setBlock($pos->add($x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add(-$x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add($x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add($x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add(-$x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add($x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add(-$x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
					$count += (int) $pos->getLevel()->setBlock($pos->add(-$x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);

				}
			}
		}

		return $count." block(s) have been changed.";
	}

	public function onPlayerQuit(PlayerQuitEvent $ev) {
		$p = $ev->getPlayer()->getName();
		if (isset($this->data[$p])) unset($this->data[$p]);
	}
}
