<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace aliuly\mobsters\idiots;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as Item;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\math\AxisAlignedBB;

use pocketmine\Player;
use pocketmine\entity\Monster;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class Zombie extends Monster{
	const NETWORK_ID = 32;

	public static $range = 32;
	public static $speed = 0.2;
	public static $jump = 2.5;
	public static $attack = 1.5;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	public $stepHeight = 0.5;

	public function getName(){
		return "ZombieIdiot";
	}

	public function spawnTo(Player $player){

		$pk = new AddMobPacket();
		$pk->eid = $this->getId();
		$pk->type = Zombie::NETWORK_ID;
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
		$drops = [];
		$rnd = mt_rand(0,1);
		if ($rnd) {
			$drops[] = Item::get(Item::FEATHER, 0, $rnd);
		}
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getEntity() instanceof Player){
			if(mt_rand(0, 199) < 5){
				switch(mt_rand(0, 2)){
					case 0:
						$drops[] = Item::get(Item::IRON_INGOT, 0, 1);
						break;
					case 1:
						$drops[] = Item::get(Item::CARROT, 0, 1);
						break;
					case 2:
						$drops[] = Item::get(Item::POTATO, 0, 1);
						break;
				}
			}
		}

		return $drops;
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
			if($pl->isCreative()){
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
			//echo ("Falling...\n");
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

				if ($dist > self::$attack) {
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
				}else {
					$attack = mt_rand(0,4);
					if ($attack < 2 && $attack > 0) {
						$source = new EntityDamageByEntityEvent($this,$target,EntityDamageEvent::CAUSE_ENTITY_ATTACK,$attack);

						$target->attack($attack,$source);
					}
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
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){
		//echo __METHOD__.",".__LINE__."\n"; //##DEBUG
		parent::knockBack($attacker,$damage,$x,$z,2.5);
	}

}
