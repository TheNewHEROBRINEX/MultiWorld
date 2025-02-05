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

namespace czechpmdevs\multiworld\util;

use czechpmdevs\multiworld\api\WorldGameRulesAPI;
use czechpmdevs\multiworld\api\WorldUtils;
use czechpmdevs\multiworld\form\CustomForm;
use czechpmdevs\multiworld\MultiWorld;
use pocketmine\form\Form;
use pocketmine\Player;

class FormManager {

    public const FORM_CREATE = 0;
    public const FORM_DELETE = 1;
    public const FORM_GAMERULES = 2;
    public const FORM_INFO = 3;
    public const FORM_LOAD_UNLOAD = 4;
    public const FORM_TELEPORT = 5;
    public const FORM_TELEPORT_PLAYER = 6;
    public const FORM_UPDATE = 7;

    /** @var MultiWorld */
    public MultiWorld $plugin;

    public function __construct(MultiWorld $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @param mixed $data
     */
    public function handleFormResponse(Player $player, $data, Form $form): void {
        if ($data === null) return;
        $customForm = new CustomForm("World Manager");
        $customForm->mwId = $data;

        switch ($data) {
            case self::FORM_CREATE:
                $customForm->addLabel("Create world");
                $customForm->addInput("Level name");
                $customForm->addInput("Level seed");
                $customForm->addDropdown("Generator", ["Normal", "Custom", "Nether", "End", "Flat", "Void", "SkyBlock"]);
                $player->sendForm($customForm);
                break;
            case self::FORM_DELETE:
                $customForm->addLabel("Remove world");
                $customForm->addDropdown("Level name", WorldUtils::getAllLevels());
                $player->sendForm($customForm);
                break;
            case self::FORM_GAMERULES:
                $customForm->addLabel("Update level GameRules");
                $rules = WorldGameRulesAPI::getLevelGameRules($player->getLevelNonNull());
                foreach ($rules as $rule => [1 => $value]) {
                    $customForm->addToggle((string)$rule, $value);
                }
                $player->sendForm($customForm);
                break;
            case self::FORM_INFO:
                $customForm->addLabel("Get information about the level");
                $customForm->addDropdown("Levels", WorldUtils::getAllLevels());
                $player->sendForm($customForm);
                break;
            case self::FORM_LOAD_UNLOAD:
                $customForm->addLabel("Load/Unload world");
                $customForm->addInput("Level to load §o(optional)");
                $customForm->addInput("Level to unload §o(optional)");
                $player->sendForm($customForm);
                break;
            case self::FORM_TELEPORT:
                $customForm->addLabel("Teleport to level");
                $customForm->addDropdown("Level", WorldUtils::getAllLevels());
                $player->sendForm($customForm);
                break;
            case self::FORM_TELEPORT_PLAYER:
                $customForm->addLabel("Teleport player to level");
                $players = [];
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    $players[] = $p->getName();
                }
                $customForm->addDropdown("Player", $players);
                $customForm->addDropdown("Level", WorldUtils::getAllLevels());
                $player->sendForm($customForm);
                break;
            case self::FORM_UPDATE:
                $customForm->addLabel("Update level");
                $customForm->addToggle("Update world spawn", true);
                $customForm->addToggle("Update server lobby", false);
                $player->sendForm($customForm);
                break;
        }
    }

    /**
     * @param mixed $data
     */
    public function handleCustomFormResponse(Player $player, $data, CustomForm $form): void {
        if ($data === null) return;
        switch ($form->mwId) {
            case self::FORM_CREATE:
                if ($data[1] === "" || (strlen($data[2]) > 2 && !is_numeric($data[2]))) {
                    LanguageManager::getMsg($player, "forms-invalid");
                    break;
                }
                $name = (string)$data[1];
                $seed = (int)$data[2];
                $gen = (int)$data[3];
                $genName = "Normal";
                switch ($gen) {
                    case WorldUtils::GENERATOR_NORMAL:
                        $genName = "Normal";
                        break;
                    case WorldUtils::GENERATOR_HELL:
                        $genName = "Hell";
                        break;
                    case WorldUtils::GENERATOR_ENDER:
                        $genName = "End";
                        break;
                    case WorldUtils::GENERATOR_VOID:
                        $genName = "Void";
                        break;
                    case WorldUtils::GENERATOR_SKYBLOCK:
                        $genName = "SkyBlock";
                        break;
                    case WorldUtils::GENERATOR_HELL_OLD:
                        $genName = "Nether_Old";
                        break;
                    case WorldUtils::GENERATOR_NORMAL_CUSTOM:
                        $genName = "Custom";
                }
                $this->plugin->getServer()->dispatchCommand($player, "mw create $name $seed $genName");
                break;
            case self::FORM_DELETE:
                $this->plugin->getServer()->dispatchCommand($player, "mw delete " . WorldUtils::getAllLevels()[$data[1]]);
                break;
            case self::FORM_GAMERULES:
                array_shift($data);
                $gameRules = array_keys(WorldGameRulesAPI::getLevelGameRules($player->getLevelNonNull()));
                foreach ($data as $i => $v) {
                    $this->plugin->getServer()->dispatchCommand($player, "gamerule {$gameRules[$i]} " . ((bool)$v ? "true" : "false"));
                }
                break;
            case self::FORM_INFO:
                $this->plugin->getServer()->dispatchCommand($player, "mw info " . WorldUtils::getAllLevels()[(int)$data[1]]);
                break;
            case self::FORM_LOAD_UNLOAD:
                if ($data[1] != "") {
                    $this->plugin->getServer()->dispatchCommand($player, "mw load {$data[1]}");
                }
                if ($data[2] != "") {
                    $this->plugin->getServer()->dispatchCommand($player, "mw unload {$data[2]}");
                }
                break;
            case self::FORM_TELEPORT:
                $this->plugin->getServer()->dispatchCommand($player, "mw tp " . WorldUtils::getAllLevels()[$data[1]]);
                break;
            case self::FORM_TELEPORT_PLAYER:
                $players = [];
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    $players[] = $p->getName();
                }
                $this->plugin->getServer()->dispatchCommand($player, "mw tp " . WorldUtils::getAllLevels()[$data[2]] . " " . $players[$data[1]]);
                break;
            case self::FORM_UPDATE:
                array_shift($data);
                if ((bool)array_shift($data)) {
                    $this->plugin->getServer()->dispatchCommand($player, "mw update spawn");
                }
                if ((bool)array_shift($data)) {
                    $this->plugin->getServer()->dispatchCommand($player, "mw update lobby");
                }
                break;

        }
    }
}