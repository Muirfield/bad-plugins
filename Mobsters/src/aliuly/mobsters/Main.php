<?php
namespace aliuly\mobsters;

use pocketmine\plugin\PluginBase;
use pocketmine\block\Block;
use pocketmine\item\Item;;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;

use aliuly\mobsters\idiots\Chicken;
use aliuly\mobsters\idiots\Pig;
use aliuly\mobsters\idiots\Sheep;
use aliuly\mobsters\idiots\Cow;
use aliuly\mobsters\idiots\Mooshroom;
use aliuly\mobsters\idiots\Wolf;
use aliuly\mobsters\idiots\Enderman;
use aliuly\mobsters\idiots\Spider;
use aliuly\mobsters\idiots\Skeleton;
use aliuly\mobsters\idiots\PigZombie;
use aliuly\mobsters\idiots\Creeper;
use aliuly\mobsters\idiots\Slime;
use aliuly\mobsters\idiots\Silverfish;
use aliuly\mobsters\idiots\Zombie;
use aliuly\mobsters\idiots\Villager;

class Main extends PluginBase implements Listener{
	public function onEnable(){
		foreach([
			Chicken::NETWORK_ID, Pig::NETWORK_ID, Sheep::NETWORK_ID,
			Cow::NETWORK_ID, Mooshroom::NETWORK_ID, Wolf::NETWORK_ID,
			Enderman::NETWORK_ID, Spider::NETWORK_ID, Skeleton::NETWORK_ID,
			PigZombie::NETWORK_ID, Creeper::NETWORK_ID, Slime::NETWORK_ID,
			Silverfish::NETWORK_ID, Villager::NETWORK_ID, Zombie::NETWORK_ID
		] as $type){
			Block::$creative[] = [ Item::SPAWN_EGG, $type ];
		}
		Entity::registerEntity(Chicken::class);
		Entity::registerEntity(Pig::class);
		Entity::registerEntity(Sheep::class);
		Entity::registerEntity(Cow::class);
		Entity::registerEntity(Mooshroom::class);
		Entity::registerEntity(Wolf::class);
		Entity::registerEntity(Enderman::class);
		Entity::registerEntity(Spider::class);
		Entity::registerEntity(Skeleton::class);
		Entity::registerEntity(PigZombie::class);
		Entity::registerEntity(Creeper::class);
		//Entity::registerEntity(Slime::class);
		Entity::registerEntity(Silverfish::class);
		Entity::registerEntity(Villager::class);
		Entity::registerEntity(Zombie::class);
	}
}
