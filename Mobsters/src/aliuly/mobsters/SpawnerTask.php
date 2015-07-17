<?php
namespace aliuly\mobsters;
use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\math\Vector3;

use pocketmine\entity\Living;
use pocketmine\entity\Human;
use pocketmine\entity\Creature;
use pocketmine\level\Level;
use pocketmine\level\Location;


class SpawnerTask extends PluginTask{
	public $maxmobs = 100;
	public $last = [];

	public function onRun($currentTick){
		if ($this->owner->isDisabled()) return;

		$cnt = [ "" => 0 ];
		foreach ($this->owner->getServer()->getLevels() as $lv) {
			foreach ($lv->getEntities() as $e) {
				if ($e instanceof Humans) continue;
				if ($e instanceof Creature) ++$cnt[""];
				$id = get_class($e).":".$lv->getName();
				if (!isset($cnt[$id])) $cnt[$id]=0;
				++$cnt[$id];
			}
		}
		if ($cnt[""] > $this->maxmobs) return;
		foreach ($this->owner->getServer()->getLevels() as $lv) {
			foreach ($lv->getTiles() as $tile) {
				if (!($tile instanceof Sign)) continue;
				$text = $tile->getText();
				if ($text[0] != "[spawner]") continue;
				echo __METHOD__.",".__LINE__." ID:".$tile->getId()."\n";//##DEBUG
				// 1 - <mob name>
				// 2 - <radius>,<max-number>,<freq>,<odds>
				// 3 - time-time or time|time

				$class = $this->owner->mobClass($text[1]);
				if ($class === null) continue;
				echo __METHOD__.",".__LINE__."  class=$class\n";//##DEBUG

				// Apply time based restrictions
				$now = $lv->getTime() % Level::TIME_FULL;
				if (strpos("-",$text[3]) !== false) {
					echo __METHOD__.",".__LINE__."  -text=".$text[3]."\n";//##DEBUG
					$times = explode("-",$text[3]);
					if (count($times) > 2) continue;
					if (count($times) == 1) $times[1] = "";
					if ($times[0] == "") $times[0] = 0;
					if ($times[1] == "") $times[1] = Level::TIME_FULL;
					if ($now < $times[0] || $now > $times[1]) continue;
				} elseif (strpos("|",$text[3]) !== false) {
					echo __METHOD__.",".__LINE__."  |text=".$text[3]."\n";//##DEBUG
					$times = explode("|",$text[3]);
					if (count($times) > 2) continue;
					if (count($times) == 1) $times[1] = "";
					if ($times[0] == "") $times[0] = 0;
					if ($times[1] == "") $times[1] = Level::TIME_FULL;
					if ($times[0] < $now && $now < $times[1]) continue;
				} elseif (strtolower($text[3]) == "day") {
					echo __METHOD__.",".__LINE__."  day=".$text[3]."\n";//##DEBUG
					if ($now > Level::TIME_NIGHT) continue;
				} elseif (strtolower($text[3]) == "night") {
					echo __METHOD__.",".__LINE__."---NIGHT vs $now\n";//##DEBUG
					if ($now < Level::TIME_NIGHT) continue;
				}
				echo __METHOD__.",".__LINE__."\n";//##DEBUG

				$opts = explode(",",$text[2]);
				$rad = count($opts) ? intval(array_shift($opts)) : 3;
				$maxlv = count($opts) ? intval(array_shift($opts)) : 10;
				$freq = count($opts) ? intval(array_shift($opts)) : 10;
				$odds = count($opts) ? intval(array_shift($opts)) : 10;

				if (isset($this->last[$tile->getId()])) {
					if (--$this->last[$tile->getId()] > 0) continue;
					unset($this->last[$tile->getId()]);
				}
				$id = $class.":".$lv->getName();
				echo __METHOD__.",".__LINE__." id=$id cnt=".(isset($cnt[$id]) ? $cnt[$id] : "N/A")." maxlv=$maxlv\n";//##DEBUG
				if (isset($cnt[$id]) && $cnt[$id] > $maxlv) continue;
				echo __METHOD__.",".__LINE__."\n";//##DEBUG
				if (mt_rand(0,$odds) != 1) continue;
				echo __METHOD__.",".__LINE__."\n";//##DEBUG
				// OK SPAWN A MOB!
				$pos = $lv->getSafeSpawn($tile);
				$location = new Location($pos->x+mt_rand(-$rad,$rad),$pos->y,$pos->z+mt_rand(-$rad,$rad),0,0,$lv);
				$this->owner->spawnMob($class,$location);

				$this->last[$tile->getId()] = $freq;
			}
		}
	}
}
