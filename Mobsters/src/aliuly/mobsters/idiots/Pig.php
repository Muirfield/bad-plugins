<?php
namespace aliuly\mobsters\idiots;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\Player;
use pocketmine\entity\Rideable;
use pocketmine\entity\Animal;

use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\math\AxisAlignedBB;

class Pig extends Animal implements Rideable{
	const NETWORK_ID=12;

	public $width = 0.650;
	public $length = 1.3;
	public $height = 0.875;
	public $stepHeight = 0.2;

	public function getName(){
		return "Piggy";
	}
	public static $range = 16;
	public static $speed = 0.05;
	public static $jump = 2.5;
	public static $mindist = 3;

	public function spawnTo(Player $player){
		$pk = new AddMobPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->getData();
		$player->dataPacket($pk);

		$player->addEntityMotion($this->getId(), $this->motionX, $this->motionY, $this->motionZ);

		parent::spawnTo($player);
	}

	public function getData(){ //TODO
		$flags = 0;
		$flags |= $this->fireTicks > 0 ? 1 : 0;
		//$flags |= ($this->crouched === true ? 0b10:0) << 1;
		//$flags |= ($this->inAction === true ? 0b10000:0);
		$d = [
			0 => ["type" => 0, "value" => $flags],
			1 => ["type" => 1, "value" => $this->airTicks],
			16 => ["type" => 0, "value" => 0],
			17 => ["type" => 6, "value" => [0, 0, 0]],
		];

		return $d;
	}

	public function getDrops(){
		return  [Item::get($this->fireTicks > 0 ? Item::COOKED_PORKCHOP : Item::RAW_PORKCHOP, 0, mt_rand(1, 3))];
	}

	public function updateMovement(){
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
				$player->addEntityMotion($this->id, $this->motionX, $this->motionY, $this->motionZ);
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

}
