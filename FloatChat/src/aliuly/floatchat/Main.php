<?php
namespace aliuly\floatchat;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

use pocketmine\scheduler\PluginTask;

class cleanUpTask extends PluginTask {
	public function onRun($currentTick){
		if ($this->owner->isDisabled()) return;
		$this->owner->timerTick();
	}
}


class Main extends PluginBase implements Listener {
	protected $particles;
	protected $timeout = 5;

	protected static function iName($player) {
		return strtolower($player->getName());
	}

	// Access and other permission related checks
	private function access(CommandSender $sender, $permission) {
		if($sender->hasPermission($permission)) return true;
		$sender->sendMessage("You do not have permission to do that.");
		return false;
	}
	//////////////////////////////////////////////////////////////////////
	//
	// Standard call-backs
	//
	//////////////////////////////////////////////////////////////////////
	public function onEnable(){
		//if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->particles = [];
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new cleanUpTask($this),20);

	}

	protected function deSpawn($particle,$level) {
		if ($level === null) return;
		$particle->setInvisible();
		$level->addParticle($particle);
	}

	//////////////////////////////////////////////////////////////////////
	//
	// Event handlers
	//
	//////////////////////////////////////////////////////////////////////
	public function timerTick() {
		$now = time();
		foreach (array_keys($this->particles) as $n) {
			list($particle,$level,$expby,) = $this->particles[$n];
			if ($level !== null && $now > $expby) {
				$this->deSpawn($particle,$level);
				$this->particles[$n][1] = null;
			}
		}
	}

	public function onQuit(PlayerQuitEvent $e){
		$n = self::iName($e->getPlayer());
		if (isset($this->particles[$n])) {
			list($p,$level,,) = $this->particles[$n];
			unset($this->particles[$n]);
			$this->deSpawn($p,$level);
		}
	}

	public function onMove(PlayerMoveEvent $e) {
		$n = self::iName($e->getPlayer());
		if (!isset($this->particles[$n])) return;
		list($p,$level,,) = $this->particles[$n];
		if ($level === null) return;
		$pw = $e->getPlayer();
		$p->x = $pw->getX();
		$p->y = $pw->getY()+2+count($this->particles[$n][3])*0.5;
		$p->z = $pw->getZ();
		$pw->getLevel()->addParticle($p);
	}

	public function onChat(PlayerChatEvent $e){
		if ($e->isCancelled()) return;
		$pw = $e->getPlayer();
		// Non players are handled normally
		if (!($pw instanceof Player)) return;

		$msg = $e->getMessage();
		if (substr($msg,0,1) == ":") {
			// This messages goes to everybody on the server...
			// no need to do much...
			if (!$this->access($pw,"floatchat.broadcast.server")) {
				$e->setCancelled();
				return;
			}
			$e->setMessage(substr($msg,1));
			return;
		}
		if (substr($msg,0,1) == ".") {
			$target = [];
			if (!$this->access($pw,"floatchat.broadcast.level")) {
				$e->setCancelled();
				return;
			}
			// Send this message to everybody on this level
			$e->setMessage(substr($msg,1));
			foreach ($e->getRecipients() as $pr) {
				if ($pr instanceof Player) {
					if (!$pr->hasPermission("floatchat.spy") &&
						 $pr->getLevel() != $pw->getLevel()) continue;
				}
				$target[] = $pr;
			}
			$e->setRecipients($target);
			return;
		}
		$target = [];
		foreach ($e->getRecipients() as $pr) {
			if ($pr instanceof Player) {
				if (!$pr->hasPermission("floatchat.spy") && $pr != $pw) continue;
			}
			$target[] = $pr;
		}
		$e->setRecipients($target);
		echo __METHOD__.",".__LINE__."\n";//##DEBUG

		$n = self::iName($pw);
		if (!isset($this->particles[$n]))
			$this->particles[$n] = [new FloatingTextParticle($pw,""), null, 0, ""];

		$p = $this->particles[$n][0];
		$msg = $e->getMessage();
		if ($p->isInvisible()) {
			$this->particles[$n][3] = [ $msg ];
			$p->setText(TextFormat::YELLOW.$msg);
			$p->setInvisible(false);
		} else {
			$this->particles[$n][3][] = $msg;
			while (count($this->particles[$n][3]) > 3) {
				array_shift($this->particles[$n][3]);
			}
			$p->setText(TextFormat::YELLOW.implode("\n",$this->particles[$n][3]));
		}
		$p->x = $pw->getX();
		$p->y = $pw->getY()+2+count($this->particles[$n][3])*0.5;
		$p->z = $pw->getZ();
		$this->particles[$n][1] = $pw->getLevel();
		$this->particles[$n][2] = time()+$this->timeout;

		$pw->getLevel()->addParticle($p);
	}
}
