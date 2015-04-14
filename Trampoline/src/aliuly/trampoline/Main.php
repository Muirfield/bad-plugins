<?php
namespace aliuly\trampoline;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\Config;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

use pocketmine\Player;


use pocketmine\Server;
use pocketmine\utils\TextFormat;



use pocketmine\item\Item;
use pocketmine\network\protocol\SetHealthPacket;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\scheduler\CallbackTask;


class Main extends PluginBase implements Listener {
	protected $blocks;

	public function onEnable(){
		$defaults = [
			"blocks" => [ Block::SPONGE ],
		];
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$cfg=(new Config($this->getDataFolder()."config.yml",
							  Config::YAML,$defaults))->getAll();
		$this->blocks = [];
		if (isset($cfg["blocks"]) && is_array($cfg["blocks"])) {
			foreach ($cfg["blocks"] as $id) {
				if ($id == Block::AIR) continue;
				$this->blocks[$id] = $id;
			}
		}
		if (count($this->blocks)) {
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
			$this->getLogger()->info(TextFormat::GREEN."Trampoline block ids:".
											 count($this->blocks));
		} else {
			$this->getLogger()->info(TextFormat::RED."No blocks configured");

		}
	}

	public function onFall(EntityDamageEvent $ev) {
		$cause = $ev->getCause();
		if ($cause !== EntityDamageEvent::CAUSE_FALL) return;
		$et = $ev->getEntity();
		$id = $et->getLevel()->getBlockIdAt($et->getX(),$et->getY()-1,$et->getZ());
		if (isset($this->blocks[$id])) {
			// Soft landing!
			$ev->setCancelled();
		}
	}

	public function onMove(PlayerMoveEvent $ev) {
		$from = $ev->getFrom();
		$to = $ev->getTo();
		$dir = ["dx"=>$to->getX()-$from->getX(),
				  "dy"=>$to->getY()-$from->getY(),
				  "dz"=>$to->getZ()-$from->getZ()];
		if (!$dir["dy"]) return;
		$id = $to->getLevel()->getBlockIdAt($to->getX(),$to->getY()-1,$to->getZ());
		if (isset($this->blocks[$id])) {
			$ev->getPlayer()->setMotion(new Vector3($dir["dx"],-$dir["dy"]*1.1,$dir["dz"]));

		}
	}
}
