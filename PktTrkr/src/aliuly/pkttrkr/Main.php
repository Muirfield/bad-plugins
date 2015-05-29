<?php
namespace aliuly\pkttrkr;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\PlayerArmorEquipmentPacket;
use pocketmine\network\protocol\PlayerEquipmentPacket;

class Main extends PluginBase implements Listener {
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onSend(DataPacketSendEvent $ev) {
		$pk = $ev->getPacket();
		if ($pk instanceof PlayerArmorEquipmentPacket) {
			echo get_class($pk)."\n";
			print_r($pk);
			return;
		}
		if ($pk instanceof ContainerSetContentPacket) {
			echo get_class($pk)."\n";
			print_r($pk);
			return;
		}
		if ($pk instanceof ContainerSetSlotPacket) {
			echo get_class($pk)."\n";
			print_r($pk);
			return;
		}
		if ($pk instanceof PlayerArmorEquipmentPacket) {
			echo get_class($pk)."\n";
			print_r($pk);
			return;
		}
		if ($pk instanceof PlayerEquipmentPacket) {
			echo get_class($pk)."\n";
			print_r($pk);
			return;
		}
	}
}
