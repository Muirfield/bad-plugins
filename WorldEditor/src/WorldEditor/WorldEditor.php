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

class WorldEditor extends PluginBase implements Listener{
	private $output = "";
	private static $config = false;
	private static $dataDir = false;
	
	public function onLoad(){
		$this->getLogger()->info(TextFormat::WHITE . "WorldEditor has been loaded!");
	}
	
	public function onEnable(){
		@mkdir("plugins/WorldEditor");
		self::$dataDir = $this->getServer()->getPluginPath() . "WorldEditor/";
        $this->checkConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("WorldEditor has been enabled!");
    }
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        $cmd = strtolower($command->getName());
		$params = $args;
		
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.\n");
			return false;
		}
		
		$data = $this->getData($sender);
		
		if(!$sender->isOp()){
			return false;
		}
		
		if($cmd{0} === "/"){
			$cmd = substr($cmd, 1);
		}
		
		switch($cmd){
			case "paste":
				$this->W_paste($data->get("clipboard"), new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				break;
			case "copy":
				$count = $this->countBlocks($data->get("selection"), $startX, $startY, $startZ);
				if($count > $data->get("block-limit") and $data->get("block-limit") > 0){
					$this->output .= "Block limit of ".$data->get("block-limit")." exceeded, tried to copy $count block(s).\n";
					break;
				}
				
				$blocks = $this->W_copy($data->get("selection"));
				if(count($blocks) > 0){
					$offset = array($startX - $sender->getX() - 0.5, $startY - $sender->getY(), $startZ - $sender->getZ() - 0.5);
					$data->set("clipboard", array($offset, $blocks));
					$data->save();
				}
				break;
			case "cut":
				$count = $this->countBlocks($data->get("selection"), $startX, $startY, $startZ);
				if($count > $data->get("block-limit") and $data->get("block-limit") > 0){
					$this->output .= "Block limit of ".$data->get("block-limit")." exceeded, tried to cut $count block(s).\n";
					break;
				}
				
				$blocks = $this->W_cut($data->get("selection"));
				if(count($blocks) > 0){
					$offset = array($startX - $sender->getX() - 0.5, $startY - $sender->getY(), $startZ - $sender->getZ() - 0.5);
					$data->set("clipboard", array($offset, $blocks));
					$data->save();
				}
				break;
			case "toggleeditwand":
				$data->set("wand-usage", ($data->get("wand-usage") == true ? false:true));
				$data->save();
				$this->output .= "Wand Item is now ".($data->get("wand-usage") === true ? "enabled":"disabled").".\n";
				break;
			case "wand":
				if($sender->getInventory()->contains(Item::fromString($this->getConfig()->get("wand-item")))){
					$this->output .= "You already have the wand item.\n";
					break;
				} elseif($sender->getGamemode() === 1){
					$this->output .= "You are on creative mode.\n";
				} else{
					$sender->getInventory()->addItem(Item::fromString($this->getConfig()->get("wand-item")));
				}
				$this->output .= "Break block to set pos #1 and Tap to set Pos #2.\n";
				break;
			case "desel":
				$data->set("selection", array(false, false));
				$data->save();
				$this->output = "Selection cleared.\n";
				break;
			case "limit":
				if(!isset($params[0]) or trim($params[0]) === ""){
					$this->output .= "Usage: //limit <limit>\n";
					break;
				}
				$limit = intval($params[0]);
				if($limit < 0){
					$limit = -1;
				}
				if($this->getConfig()->get("block-limit") > 0){
					$limit = $limit === -1 ? $this->getConfig()->get("block-limit"):min($this->getConfig()->get("block-limit"), $limit);
				}
				$data->set("block-limit", $limit);
				$data->save();
				$this->output .= "Block limit set to ".($limit === -1 ? "infinite":$limit)." block(s).\n";
				break;
			case "pos1":
				$this->setPosition1($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				break;
			case "pos2":
				$this->setPosition2($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()));
				break;

			case "hsphere":
				$filled = false;
			case "sphere":
				if(!isset($filled)){
					$filled = true;
				}
				if(!isset($params[1]) or $params[1] == ""){
					$this->output .= "Usage: //$cmd <block> <radius>.\n";
					break;
				}
				$radius = abs(floatval($params[1]));
				
				$items = Item::fromString($params[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$this->output .= "Incorrect block.\n";
							return;
						}
					}
					$this->W_sphere(new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()), $items, $radius, $radius, $radius, $filled);
				} else {
					$this->output .= "Incorrect block, use ID.\n";
				}
				break;
			case "cube":
				if(!isset($filled)){
					$filled = true;
				}
				if(!isset($params[1]) or $params[1] == ""){
					$this->output .= "Usage: //$cmd <block> <radius>.\n";
					break;
				}
				$radius = abs(floatval($params[1]));
				
				$items = Item::fromString($params[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$this->output .= "Incorrect block.\n";
							return;
						}
					}
					$this->W_cube(new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getLevel()), $items, $radius, $radius, $radius, $filled);
				} else {
					$this->output .= "Incorrect block, use ID.\n";
				}
				break;
			case "set":
				$count = $this->countBlocks($data->get("selection"));
				if($count > $data->get("block-limit") and $data->get("block-limit") > 0){
					$this->output .= "Block limit of ".$data->get("block-limit")." exceeded, tried to change $count block(s).\n";
					break;
				}
				$items = Item::fromString($params[0], true);
				if($items){
					foreach($items as $item){
						if($item->getID() > 0xff){
							$this->output .= "Incorrect block.\n";
							return;
						}
					}
					$this->W_set($data->get("selection"), $items);
				} else {
					$this->output .= "Incorrect block, use ID.\n";
				}
				break;
			case "replace":
				$count = $this->countBlocks($data->get("selection"));
				if($count > $data->get("block-limit") and $data->get("block-limit") > 0){
					$this->output .= "Block limit of ".$data->get("block-limit")." exceeded, tried to change $count block(s).\n";
					break;
				}
				$item1 = Item::fromString($params[0]);
				if($item1->getID() > 0xff){
					$this->output .= "Incorrect target block.\n";
					break;
				}
				$items2 = Item::fromString($params[1], true);
				if($items){
					foreach($items2 as $item){
						if($item->getID() > 0xff){
							$this->output .= "Incorrect replacement block.\n";
							return;
						}
					}
					
					$this->W_replace($data->get("selection"), $item1, $items2);
				} else {
					$this->output .= "Incorrect block, use ID.\n";
				}
				break;
			default:
			case "help":
				$this->output .= "Commands: //cut, //copy, //paste, //sphere, //hsphere, //desel, //limit, //pos1, //pos2, //set, //replace, //help, //wand, /toggleeditwand\n";
				break;
		}
		
		if($this->output != ""){
			$sender->sendMessage($this->output);
			$this->output = "";
			return true;
		}
		return false;
	}
	
	public function onPlayerInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
		$target = new Position($event->getBlock()->x, $event->getBlock()->y, $event->getBlock()->z, $event->getBlock()->getLevel());
		
		$data = $this->getData($player);
		
        if($data->get('wand-usage') && $item->getID() == Item::fromString($this->getConfig()->get("wand-item"))->getID()){
			$this->setPosition2($player, $target);
			$player->sendMessage($this->output);
			$this->output = "";
            $event->setCancelled();
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
		
        if($data->get('wand-usage') && $item->getID() == Item::fromString($this->getConfig()->get("wand-item"))->getID()){
			$this->setPosition1($player, $target);
			$player->sendMessage($this->output);
			$this->output = "";
            $event->setCancelled();
        }
    }
	
	private function checkConfig(){
        $this->getConfig()->save();

        if(!$this->getConfig()->exists("block-limit")){
            $this->getConfig()->set("block-limit", -1);
        }elseif(!$this->getConfig()->exists("wand-item")){
            $this->getConfig()->set("wand-item", "IRON_HOE");
        }

        if(!is_numeric($this->getConfig()->get("block-limit"))){
            $this->getLogger()->alert(TextFormat::RED . "Wrong format for block-limit.");
            $this->getConfig()->set("block-limit", -1);
        }

        $this->getConfig()->save();
        return true;
    }
	
	public static function getData($player) {
        if ($player instanceof Player) {
            $iusername = $player->getName();
        } elseif (is_string($player)) {
            $iusername = $player;
        } else {
            return false;
        }
		self::$config = new Config(self::$dataDir . "config.yml", Config::YAML, array());

        $iusername = strtolower($iusername);
        if (!file_exists(self::$dataDir . "players/" . $iusername{0} . "/$iusername.yml")) {
            @mkdir(self::$dataDir . "players/" . $iusername{0} . "/", 0777, true);
            $d = new Config(self::$dataDir . "players/" . $iusername{0} . "/" . $iusername . ".yml", Config::YAML, array(
                "selection" => array(false, false),
                "clipboard" => false,
				"wand-usage" => true,
				"block-limit" => self::$config->get("block-limit")
            ));

            $d->save();
            return $d;
        }
        return new Config(self::$dataDir . "players/" . $iusername{0} . "/" . $iusername . ".yml", Config::YAML, array(
            "selection" => array(false, false),
			"clipboard" => false,
			"wand-usage" => true,
			"block-limit" => self::$config->get("block-limit")
        ));
    }

    public function onDisable(){
        $this->getLogger()->info("WorldEditor has been disabled!");
    }
	
	public function setPosition1($username, Position $position){
		$data = $this->getData($username);
		$selection = $data->get("selection");
		$selection[0] = array(round($position->x), round($position->y), round($position->z), $position->getLevel()->getName());
		$data->set("selection", $selection);
		$data->save();
		$count = $this->countBlocks($selection);
		if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
		$this->output .= "First position set to (".$selection[0][0].", ".$selection[0][1].", ".$selection[0][2].")$count.\n";
		return true;
	}
	
	public function setPosition2($username, Position $position){
		$data = $this->getData($username);
		$selection = $data->get("selection");
		$selection[1] = array(round($position->x), round($position->y), round($position->z), $position->getLevel()->getName());
		$data->set("selection", $selection);
		$data->save();
		$count = $this->countBlocks($selection);
		if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
		$this->output .= "Second position set to (".$selection[1][0].", ".$selection[1][1].", ".$selection[1][2].")$count.\n";
		return true;
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
		if(count($clipboard) !== 2){
			$this->output .= "Copy something first.\n";
			return false;
		}
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
		$this->output .= "$count block(s) have been changed.\n";
		return true;
	}
	
	private function W_copy($selection){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$this->output .= "Make a selection first.\n";
			return array();
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
		$this->output .= "$count block(s) have been copied.\n";
		return $blocks;
	}
	
	private function W_cut($selection){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$this->output .= "Make a selection first.\n";
			return array();
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
		$this->output .= "$count block(s) have been cut.\n";
		return $blocks;
	}
	
	private function W_set($selection, $blocks){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$this->output .= "Make a selection first.\n";
			return false;
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
			$this->output .= "Incorrect blocks.\n";
			return false;
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
		$this->output .= "$count block(s) have been changed.\n";
		return true;
	}
	
	private function W_replace($selection, Item $block1, $blocks2){
		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
			$this->output .= "Make a selection first.\n";
			return false;
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
			$this->output .= "Incorrect blocks.\n";
			return false;
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
		$this->output .= "$count block(s) have been changed.\n";
		return true;
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
		
		$this->output .= $count." block(s) have been changed.\n";
		return true;	
	}
	
	private function W_cube(Position $pos, $blocks, $radiusX, $radiusY, $radiusZ, $filled = true){
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
		
		$this->output .= $count." block(s) have been changed.\n";
		return true;	
	}
}