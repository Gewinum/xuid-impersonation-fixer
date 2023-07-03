<?php

namespace gewinum\xuidfix;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;

class FixValidation extends PluginBase
{
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be sent from console!");
            return true;
        }

        if (!isset($args[0]) or $args[0] !== "confirm") {
            $sender->sendMessage(TextFormat::YELLOW . "This plugin will kick ALL online players");
            $sender->sendMessage(TextFormat::YELLOW . "It will flush all XUIDs which are empty string, which also means that it would impact non xbox users");
            $sender->sendMessage(TextFormat::YELLOW . "To continue, type /" . $command->getName() . " confirm");
            return true;
        }

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->kick();
        }

        $playersPath = Path::join($this->getServer()->getDataPath(), "players");
        $removedEmpty = 0;

        $files = array_diff(scandir($playersPath), array('.', '..'));
        foreach ($files as $file) {
            $fullPath = $this->getDataFolder() . $file;

            if (!str_ends_with($fullPath, "dat")) {
                continue;
            }

            $playerName = explode(".", $file)[0];

            $data = $this->getServer()->getOfflinePlayerData($playerName);
            if (!isset($data)) {
                $this->getLogger()->warning("Couldn't get data of $playerName");
                continue;
            }
            if ($data->getTag(Player::TAG_LAST_KNOWN_XUID) === null) {
                continue;
            }
            $knownXuid = $data->getString(Player::TAG_LAST_KNOWN_XUID);

            if ($knownXuid !== "") {
                continue;
            }

            $data->removeTag(Player::TAG_LAST_KNOWN_XUID);
            $this->getServer()->saveOfflinePlayerData($playerName, $data);
            $removedEmpty++;
        }

        $sender->sendMessage(TextFormat::GREEN . "$removedEmpty XUIDs have been reset!");
        return true;
    }
}