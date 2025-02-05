<?php

/**
 * MultiWorld - PocketMine plugin that manages worlds.
 * Copyright (C) 2018 - 2021  CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace czechpmdevs\multiworld\command\subcommand;

use czechpmdevs\multiworld\MultiWorld;
use czechpmdevs\multiworld\util\LanguageManager;
use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class UpdateSubcommand implements SubCommand {
    
    public function executeSub(CommandSender $sender, array $args, string $name): void {
        if (!isset($args[0])) {
            $sender->sendMessage(LanguageManager::getMsg($sender, "update-usage"));
            return;
        }

        switch (strtolower($args[0])) {
            case "spawn":
                if (!isset($args[1]) && ($sender instanceof Player)) {
                    $this->setSpawn($sender->getLevel(), $sender->asVector3());
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::getMsg($sender, "update-spawn-done", [$sender->getLevel()->getName()]));
                    break;
                }

                if (count($args) < 5 || !is_numeric($args[2]) || !is_numeric($args[3]) || !is_numeric($args[4])) {
                    $sender->sendMessage(LanguageManager::getMsg($sender, "update-usage"));
                    break;
                }

                if (!$this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::getMsg($sender, "update-levelnotexists"));
                    break;
                }

                $this->setSpawn($this->getServer()->getLevelByName($args[1]), new Vector3((int)$args[2], (int)$args[3], (int)$args[4]));
                $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::getMsg($sender, "update-done"));
                break;
            case "lobby":
            case "hub":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(LanguageManager::getMsg($sender, "update-notsupported"));
                    break;
                }
                $this->setLobby($sender->asPosition());
                $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::getMsg($sender, "update-lobby-done", [$sender->getLevel()->getFolderName()]));
                break;
            case "default":
            case "defaultlevel":
                if (!isset($args[1])) {
                    $sender->sendMessage(LanguageManager::getMsg($sender, "update-usage"));
                    break;
                }

                if (!$this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::getMsg($sender, "update-levelnotexists")));
                    break;
                }

                if (!$this->getServer()->isLevelLoaded($args[1])) {
                    $this->getServer()->loadLevel($args[1]);
                }

                $this->setDefaultLevel($this->getServer()->getLevelByName($args[1]));
                $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::getMsg($sender, "update-default-done")));
                break;
            default:
                $sender->sendMessage(LanguageManager::getMsg($sender, "update-usage"));
                break;
        }
    }

    public function setSpawn(Level $level, Vector3 $vector3) {
        $level->setSpawnLocation($vector3);
    }

    private function getServer(): Server {
        return Server::getInstance();
    }

    public function setLobby(Position $position) {
        $this->setDefaultLevel($position->getLevel());
        $position->getLevel()->setSpawnLocation($position->asVector3());
    }

    public function setDefaultLevel(Level $level) {
        $this->getServer()->setDefaultLevel($level);
    }
}
