<?php

namespace ifteam\SoldierGame;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\entity\Snowball;
use pocketmine\entity\Entity;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\entity\Creature;
use pocketmine\level\Explosion;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\particle\MobSpawnParticle;

class SoldierGame extends PluginBase implements Listener {
	public $config, $config_Data;
	public function onEnable() {
		$this->config = new Config ( $this->getDataFolder () . "gameData.yml", Config::YAML, [ "enable-soldiergame" => 1,"enable-explode" => 1,"enable-broadcast" => 1 ] );
		$this->config_Data = $this->config->getAll ();
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
	}
	public function onDisable() {
		$this->config->setAll ( $this->config_Data );
		$this->config->save ();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, Array $args) {
		if (strtolower ( $command->getName () ) == "soldiergame") {
			if (! $sender->hasPermission ( "soldiergame" )) return false;
			if (! isset ( $args [0] )) {
				$this->onHelp ( $sender );
				return true;
			}
			switch (strtolower ( $args [0] )) {
				case "enable" :
					$this->config_Data ["enable-soldiergame"] = 1;
					$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] The snowball fight has been activated." );
					break;
				case "disable" :
					$this->config_Data ["enable-soldiergame"] = 0;
					$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] The snowball fight has been disabled." );
					break;
				case "explode" :
					if ($this->config_Data ["enable-explode"]) {
						$this->config_Data ["enable-explode"] = 0;
						$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] The explosion disabled." );
					} else {
						$this->config_Data ["enable-explode"] = 1;
						$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] Explosion has been activated." );
					}
					break;
				case "broadcast" :
					if ($this->config_Data ["enable-broadcast"]) {
						$this->config_Data ["enable-broadcast"] = 0;
						$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] Combat Alert has been deactivated." );
					} else {
						$this->config_Data ["enable-broadcast"] = 1;
						$sender->sendMessage ( TextFormat::DARK_AQUA . "[SoldierGame] Combat Alert has been activated." );
					}
					break;
				default :
					$this->onHelp ( $sender );
					break;
			}
			return true;
		}
	}
	public function onHelp(Player $sender) {
		if ($sender->isOp ()) {
			$sender->sendMessage ( TextFormat::DARK_AQUA . "/SoldierGame enable - activate a snowball fight" );
			$sender->sendMessage ( TextFormat::DARK_AQUA . "/SoldierGame disable - disable a snowball fight" );
			$sender->sendMessage ( TextFormat::DARK_AQUA . "/SoldierGame explode - Explosion active | inactive " );
			$sender->sendMessage ( TextFormat::DARK_AQUA . "/SoldierGame broadcast - Activate combat alert | disabled" );
		}
		$sender->sendMessage ( TextFormat::DARK_AQUA . "/SoldierGame score <User name> - full display. " );
	}
	public function checkEnableSoldierGame() {
		return ( bool ) $this->config_Data ["enable-soldiergame"];
	}
	public function checkEnableExplode() {
		return ( bool ) $this->config_Data ["enable-explode"];
	}
	public function SnowballExplode(EntityDespawnEvent $event) {
		if ($event->getType () == 81 and $this->checkEnableExplode ()) $this->SoldierGame ( $event->getEntity () );
	}
	public function SoldierGame(Entity $entity) {
		if ($this->checkEnableSoldierGame () and $entity->shootingEntity instanceof Player) {
			$this->getServer ()->getPluginManager ()->callEvent ( $ev = new ExplosionPrimeEvent ( $entity, 1 ) );
			if (! $ev->isCancelled ()) {
				$entity->getLevel ()->addParticle ( new MobSpawnParticle ( $entity, 0.5, 0.5 ) );
				$explosion = new Explosion ( $entity, $ev->getForce (), $entity->shootingEntity );
				$explosion->explodeB ();
			}
		}
	}
	public function onDamage(EntityDamageEvent $event) {
		if ($event instanceof EntityDamageByEntityEvent) {
			if ($event->getEntity () instanceof \pocketmine\entity\Item) {
				$event->setCancelled ();
			}
		}
	}
	public function blockBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer ();
		$block = $event->getBlock ();
		
		if ($block->getId () == Block::SNOW_LAYER or $block->getId () == Block::SNOW_BLOCK)
			$event->getBlock()->getLevel()->dropItem($block, Item::get ( Item::SNOWBALL, 0, 1 ));
	}
}

?>
