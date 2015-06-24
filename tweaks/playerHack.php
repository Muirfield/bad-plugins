<?php

/**
 * This script plugin breaks some of the anti-cheating routines used by
 * PocketMine to prevent cheating.  This is good to have a very griefed
 * cheating server.  As a side effect, it prevents the "Player Moving Wrongly"
 * message.
 *
 * @name playerHack
 * @main aliuly\hack\BreakPlayer
 * @version 1.0.0
 * @api 1.12.0
 * @description Disables the PocketMine anti-cheating stuff
 * @author aliuly
 */


namespace aliuly\hack{
	use pocketmine\plugin\PluginBase;
	use pocketmine\event\Listener;
	use pocketmine\event\player\PlayerCreationEvent;
	use pocketmine\Player;
	use pocketmine\level\Location;
	use pocketmine\event\player\PlayerMoveEvent;
	use pocketmine\math\Vector3;

	class BreakPlayer extends PluginBase implements Listener{
		public function onEnable(){
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}
		public function newPlayerFactory(PlayerCreationEvent $ev){
			$ev->setPlayerClass(BrokenPlayer::class);
		}
	}
	class BrokenPlayer extends Player {
		protected function processMovement($tickDiff){
			if(!$this->isAlive() or !$this->spawned or $this->newPosition === null or $this->teleportPosition !== null){
				return;
			}

			$newPos = $this->newPosition;
			$distanceSquared = $newPos->distanceSquared($this);

			$revert = false;

			if(($distanceSquared / ($tickDiff ** 2)) > 100){
				$revert = true;
			}else{
				if($this->chunk === null or !$this->chunk->isGenerated()){
					$chunk = $this->level->getChunk($newPos->x >> 4, $newPos->z >> 4, false);
					if($chunk === null or !$chunk->isGenerated()){
						$revert = true;
						$this->nextChunkOrderRun = 0;
					}else{
						if($this->chunk !== null){
							$this->chunk->removeEntity($this);
						}
						$this->chunk = $chunk;
					}
				}
			}

			if(!$revert and $distanceSquared != 0){
				$dx = $newPos->x - $this->x;
				$dy = $newPos->y - $this->y;
				$dz = $newPos->z - $this->z;

				$this->move($dx, $dy, $dz);

				$diffX = $this->x - $newPos->x;
				$diffY = $this->y - $newPos->y;
				$diffZ = $this->z - $newPos->z;

				$yS = 0.5 + $this->ySize;
				if($diffY >= -$yS or $diffY <= $yS){
					$diffY = 0;
				}

				$diff = ($diffX ** 2 + $diffY ** 2 + $diffZ ** 2) / ($tickDiff ** 2);

				if($this->isSurvival()){
					if(!$revert and !$this->isSleeping()){
						if($diff > 0.0625){
							//$revert = true;
							$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidMove", [$this->getName()]));
						}
					}
				}

				if($diff > 0){
					$this->x = $newPos->x;
					$this->y = $newPos->y;
					$this->z = $newPos->z;
					$radius = $this->width / 2;
					$this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
				}
			}

			$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
			$to = $this->getLocation();

			$delta = pow($this->lastX - $to->x, 2) + pow($this->lastY - $to->y, 2) + pow($this->lastZ - $to->z, 2);
			$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

			if(!$revert and ($delta > (1 / 16) or $deltaAngle > 10)){

				$isFirst = ($this->lastX === null or $this->lastY === null or $this->lastZ === null);

				$this->lastX = $to->x;
				$this->lastY = $to->y;
				$this->lastZ = $to->z;

				$this->lastYaw = $to->yaw;
				$this->lastPitch = $to->pitch;

				if(!$isFirst){
					$ev = new PlayerMoveEvent($this, $from, $to);

					$this->server->getPluginManager()->callEvent($ev);

					if(!($revert = $ev->isCancelled())){ //Yes, this is intended
						if($to->distanceSquared($ev->getTo()) > 0.01){ //If plugins modify the destination
							$this->teleport($ev->getTo());
						}else{
							$this->level->addEntityMovement($this->x >> 4, $this->z >> 4, $this->getId(), $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
						}
					}
				}

				if(!$this->isSpectator()){
					$this->checkNearEntities($tickDiff);
				}

				$this->speed = $from->subtract($to);
			}elseif($distanceSquared == 0){
				$this->speed = new Vector3(0, 0, 0);
			}

			if($revert){

				$this->lastX = $from->x;
				$this->lastY = $from->y;
				$this->lastZ = $from->z;

				$this->lastYaw = $from->yaw;
				$this->lastPitch = $from->pitch;

				$this->sendPosition($from, $from->yaw, $from->pitch, 1);
				$this->forceMovement = new Vector3($from->x, $from->y, $from->z);
			}else{
				$this->forceMovement = null;
				if($distanceSquared != 0 and $this->nextChunkOrderRun > 20){
					$this->nextChunkOrderRun = 20;
				}
			}

			$this->newPosition = null;
		}
	}
}
