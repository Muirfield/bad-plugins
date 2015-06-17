<?php

/**
 * Fixes flying tile entities when teleporting worlds.
 *
 * @name teleportFix
 * @main aliuly\hack\teleportFix
 * @version 1.0.0
 * @api 1.12.0
 * @description Fixes flying tile entities when teleporting worlds.
 * @author aliuly
 */


namespace aliuly\hack{
	use pocketmine\plugin\PluginBase;
	use pocketmine\event\Listener;
	use pocketmine\event\entity\EntityTeleportEvent;
	use pocketmine\network\protocol\UpdateBlockPacket;
	use pocketmine\Player;

	class teleportFix extends PluginBase implements Listener{
		public function onEnable(){
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}
		/**
		 * @priority MONITOR
		 */
		public function onTeleport(EntityTeleportEvent $ev){
			if ($ev->isCancelled()) return;
			$pl = $ev->getEntity();
			if (!($pl instanceof Player)) return;

			$from = $ev->getFrom()->getLevel();
			$to = $ev->getTo()->getLevel();
			if (!$from) return;
			if (!$to) return;
			if ($from === $to) return;
			//TODO HACK: removes tile entities that linger whenever
			// to a different world
			$pk = new UpdateBlockPacket();
			foreach($from->getTiles() as $tile){
				$pk->records[] = [$tile->x, $tile->z, $tile->y, 0, 0, UpdateBlockPacket::FLAG_NONE];
			}
			if(count($pk->records)) $pl->dataPacket($pk);
		}
	}
}
