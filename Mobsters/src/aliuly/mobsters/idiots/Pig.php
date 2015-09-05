<?php
namespace aliuly\mobsters\idiots;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\Network;
use pocketmine\Player;
use pocketmine\entity\Rideable;
use pocketmine\entity\Animal;
use pocketmine\entity\Entity;


use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;


class Pig extends Animal implements Rideable{
	const NETWORK_ID=12;

	public $width = 0.650;
	public $length = 1.3;
	public $height = 0.875;
	public $stepHeight = 0.2;
	public $knockback = 0;

	public function getName(){
		return "Porky";
	}
	public static $range = 16;
	public static $speed = 0.05;
	public static $jump = 2.5;
	public static $mindist = 3;

	public function spawnTo(Player $player){

		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk->setChannel(Network::CHANNEL_ENTITY_SPAWNING));
		parent::spawnTo($player);
	}
	public function getDrops(){
		return  [Item::get($this->fireTicks > 0 ? Item::COOKED_PORKCHOP : Item::RAW_PORKCHOP, 0, mt_rand(1, 3))];
	}

	public function noupdateMovement(){
		if($this->x !== $this->lastX or $this->y !== $this->lastY or $this->z !== $this->lastZ or $this->yaw !== $this->lastYaw or $this->pitch !== $this->lastPitch){
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;

			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;

			$pk = new MovePlayerPacket();
			$pk->eid = $this->id;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->bodyYaw = $this->yaw;

			foreach($this->hasSpawned as $player){
				$player->dataPacket($pk);
			}
		}

		if(($this->lastMotionX != $this->motionX or $this->lastMotionY != $this->motionY or $this->lastMotionZ != $this->motionZ)){
			$this->lastMotionX = $this->motionX;
			$this->lastMotionY = $this->motionY;
			$this->lastMotionZ = $this->motionZ;

			foreach($this->hasSpawned as $player){
				//$player->addEntityMotion($this->id, $this->motionX, $this->motionY, $this->motionZ);
				if ($this->chunk != null) {
					$this->level->addEntityMotion($this->chunk->getX(), $this->chunk->getZ(), $this->getId(), $this->motionX, $this->motionY, $this->motionZ);
				}
			}
		}
	}

	public function getBoundingBox(){
		$this->boundingBox = new AxisAlignedBB(
			$x = $this->x - $this->width / 2,
			$y = $this->y - $this->height / 2 + $this->stepHeight,
			$z = $this->z - $this->length / 2,
			$x + $this->width,
			$y + $this->height - $this->stepHeight,
			$z + $this->length
		);
		return $this->boundingBox;
	}

	private function findTarget(){
		$lv = $this->getLevel();
		$ps = $lv->getPlayers();
		if(!count($ps)){
			return [null,null];
		}
		$target = null;
		$dist = null;
		foreach($ps as $pl){
			$hand = $pl->getInventory()->getItemInHand();
			if ($hand->getId() !== Item::CARROT) {
				// Not holding carrots...
				continue;
			}
			$cd = $this->distance($pl);
			if(($cd > self::$range)||($dist && $cd > $dist)||$pl->getHealth()<1){
				continue;
			}
			$dist = $cd;
			$target = $pl;
		}
		return [$target,$dist];
	}

	public function onUpdate($currentTick){
		if ($this->knockback) {
			if (time() < $this->knockback) {
				return  parent::onUpdate($currentTick);
			}
			$this->knockback = 0;
		}
		$hasUpdate = false;
		$this->timings->startTiming();

		// Handle flying objects...
		$tickDiff = max(1, $currentTick - $this->lastUpdate);
		$bb = clone $this->getBoundingBox();

		$onGround = count($this->level->getCollisionBlocks($bb->offset(0, -$this->gravity, 0))) > 0;
		if(!$onGround){
			// falling or jumping...
			$this->motionY -= $this->gravity;
			$this->x += $this->motionX * $tickDiff;
			$this->y += $this->motionY * $tickDiff;
			$this->z += $this->motionZ * $tickDiff;
			//echo ("Falling...\n"); //##DEBUG
		}else{
			$this->motionX = 0; // No longer jumping/falling
			$this->motionY = 0;
			$this->motionZ = 0;
			if ($this->y != floor($this->y)) $this->y = floor($this->y);

			// Try to attack a player
			list($target,$dist) = $this->findTarget();

			if($target !== null && $dist > 0){
				$dir = $target->subtract($this);
				$dir = $dir->divide($dist);
				$this->yaw = rad2deg(atan2(-$dir->getX(),$dir->getZ()));
				$this->pitch = rad2deg(atan(-$dir->getY()));

				if ($dist > self::$mindist) {
					//
					$x = $dir->getX() * self::$speed;
					$y = 0;
					$z = $dir->getZ() * self::$speed;
				} else {
					$x = $y = $z = 0;
				}
			} else {
				$x = ((mt_rand()/mt_getrandmax())*2-1) * self::$speed;
				$y = 0;
				$z = ((mt_rand()/mt_getrandmax())*2-1) * self::$speed;
				$this->yaw = rad2deg(atan2(-$x,$z));
			}
			$isJump = count($this->level->getCollisionBlocks($bb->offset($x,2, $z))) <= 0;
			if(count($this->level->getCollisionBlocks($bb->offset(0, 0.1, $z))) > 0){
				if ($isJump) {
					$y = self::$jump;
					$this->motionZ = $z;
				}
				$z = 0;
			}
			if(count($this->level->getCollisionBlocks($bb->offset($x, 0.1, 0))) > 0){
				if ($isJump) {
					$y = self::$jump;
					$this->motionX = $x;
				}
				$x = 0;
			}
			//if ($y) echo "Jumping\n";
			//echo __METHOD__.",".__LINE__."-".$this->getName()."\n";//##DEBUG
			$ev = new \pocketmine\event\entity\EntityMotionEvent($this,new \pocketmine\math\Vector3($x,$y,$z));
			$this->server->getPluginManager()->callEvent($ev);
			if ($ev->isCancelled()) return false;

			$this->x += $x;
			$this->y += $y;
			$this->z += $z;
		}
		$bb = clone $this->getBoundingBox();
		$onGround = count($this->level->getCollisionBlocks($bb->offset(0, -$this->gravity, 0))) > 0;
		$this->onGround = $onGround;
		$this->timings->stopTiming();
		$hasUpdate = parent::onUpdate($currentTick) || $hasUpdate;
		return $hasUpdate;
	}
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){
		parent::knockBack($attacker,$damage,$x,$z,$base);
		$this->knockback = time() + 1;// Stunned for 1 second...
	}

}
