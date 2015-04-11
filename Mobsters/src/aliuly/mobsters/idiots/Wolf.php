<?php
namespace aliuly\mobsters\idiots;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\Player;
use pocketmine\entity\Animal;
use pocketmine\entity\Tameable;
use pocketmine\nbt\tag\String;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\protocol\MovePlayerPacket;


class Wolf extends Animal implements Tameable{
	const NETWORK_ID=14;
	public static $speed = 0.2;
	public static $jump = 2;
	public static $dist = 4;

	public $width = 0.625;
	public $length = 1.4375;
	public $height = 1.25;
	public $owner = null;

	public function getName(){
		return "LameWolf";
	}

	public function initEntity() {
		//^ changed from protected->public for 1.5
		parent::initEntity();
		if(isset($this->namedtag->Owner)){
			$this->owner = $this->namedtag["Owner"];
		} else {
			$this->owner = "";
		}
		$x = floor($this->namedtag->Pos[0]);
		$y = floor($this->namedtag->Pos[1]);
		$z = floor($this->namedtag->Pos[2]);
		$mgr = $this->server->getPluginManager()->getPlugin("Mobsters");
		if ($mgr) {
			list($owner,$id) = $mgr->getSpawner($x,$y,$z);
			if ($owner && $id == self::NETWORK_ID) {
				$this->owner = $owner;
				$this->namedtag->Owner = new String("Owner",$this->owner);
			}
		}
		if ($this->owner) echo "Perrito de ".$this->owner."\n"; //##DEBUG
	}

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
	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Owner = new String("Owner",$this->owner);
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
		return [];
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
			//echo ("Falling...\n");
		}else{
			$this->motionX = 0; // No longer jumping/falling
			$this->motionY = 0;
			$this->motionZ = 0;
			if ($this->y != floor($this->y)) $this->y = floor($this->y);

			// Try to attack a player
			$target = null;
			if ($this->owner) {
				$target = $this->server->getPlayer($this->owner);
				if ($target) {
					if ($target->getLevel() != $this->level) {
						// Pet is in a different level...
						$target = null;
					} else {
						$dist = $this->distance($target);
					}
				}
			}

			if($target !== null){
				$dir = $target->subtract($this);
				$dir = $dir->divide($dist);
				$this->yaw = rad2deg(atan2(-$dir->getX(),$dir->getZ()));
				$this->pitch = rad2deg(atan(-$dir->getY()));

				if ($dist > self::$dist) {
					//
					$x = $dir->getX() * self::$speed;
					$y = 0;
					$z = $dir->getZ() * self::$speed;
					$isJump = count($this->level->getCollisionBlocks($bb->offset($x, 1.2, $z))) <= 0;

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
			}
		}
		$bb = clone $this->getBoundingBox();
		$onGround = count($this->level->getCollisionBlocks($bb->offset(0, -$this->gravity, 0))) > 0;
		$this->onGround = $onGround;
		$this->timings->stopTiming();
		$hasUpdate = parent::onUpdate($currentTick) || $hasUpdate;
		return $hasUpdate;
	}

}
