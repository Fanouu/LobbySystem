<?php

namespace LobbySysteme;

use pocketmine\event\CancellableTrait;
use pocketmine\event\Cancellable;
use pocketmine\plugin\PluginBase;
use pocketmine\item\ItemFactory;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

use LobbySysteme\Forms\SimpleForm;

class core extends PluginBase implements Listener, Cancellable{
    use CancellableTrait;
    
    public static nav = [];

    protected function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("Heldia Network Lobby v1");
        @mkdir($this->getDataFolder());
        $this->saveResource("settings.yml");
        $this->setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $pname = $player->getName();
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        $event->setJoinMessage("");
        $msg = str_replace("{player}", $pname, $setting->get("JoinMessage"));
        $player->getServer()->broadcastMessage($msg);

        $player->setHealth("20");
        $player->getInventory()->clearAll();

        $item = ItemFactory::getInstance()->get(ItemIds::COMPASS);
        $item->setCustomName($this->setting->getNested("MenuNav.CompassName"));
        $player->getInventory()->setItem($this->setting->getNested("MenuNav.CompassCase"), $item);

        if($setting->get("TPLocationX") != "none" or($setting->get("TPLocationY") != "none" or($setting->get("TPLocationZ") != "none" or $setting->get("TPLocationWorld") != "none"))) {
            $x = $setting->get("TPLocationX");
            $y = $setting->get("TPLocationY");
            $z = $setting->get("TPLocationZ");
            $world = $setting->get("TPLocationWorld");

            $player->teleport(new Position($x, $y, $z, $player->getWorld()->getFolderName($world)));
        }

    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $pname = $player->getName();
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        $event->setQuitMessage(" ");
        $msg = str_replace("{player}", $pname, $setting->get("QuitMessage"));
        $player->getServer()->broadcastMessage($msg);
    }

    public function BlockBreak(BlockBreakEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        if ($setting->get("BlockBreak") == true){
            $event->uncancel();
        }else if($setting->get("BlockBreak") == false){
            $event->cancel();
            $event->getPlayer()->sendMessage($setting->get("NoBlockBreak"));
        }
    }

    public function BlockPlace(BlockPlaceEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        if ($setting->get("BlockPlace") == true){
            $event->uncancel();
        }else if($setting->get("BlockPlace") == false){
            $event->cancel();
            $event->getPlayer()->sendMessage($setting->get("NoBlockPlace"));
        }
    }

    public function PlayerDamage(EntityDamageEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $entity = $event->getEntity();

        if($setting->get("PlayerDamage") == true){
            $event->uncancel();
        }else if($setting->get("PlayerDamage") == false){
            $event->cancel();    
        }
    }

    public function PlayerInteract(PlayerInteractEvent $event){
        $item = $event->getItem()->getId();

        if($item == ItemIds::COMPASS){
            $this->openForm($event->getPlayer());
        }
    }

    public function openForm($player){
        $form = self::createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            return true;

        });
        $form->setTitle($this->setting->getNested("MenuNav.MenuUi.Title"));
        $form->setContent($this->setting->getNested("MenuNav.MenuUi.Content"));
        $server = $this->setting->getNested("MenuNav.MenuUi.Server");
        $numserv = 0;
        foreach($server as $serv => $server){
            $redirec = explode("|", $server);
            $form->addButton($redirec[0]);
            self::$nav[$numserv] = "Server|" . $redirec[1];
            $numserv++;
        } 
        
        $player->sendForm($form);
    }

    public static function createSimpleForm(callable $function = null) : SimpleForm {
        return new SimpleForm($function);
    }
}
