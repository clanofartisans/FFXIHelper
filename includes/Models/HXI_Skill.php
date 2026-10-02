<?php

enum HXI_Skill: int {
    case None = 0;
    case HandToHand = 1;
    case Dagger = 2;
    case Sword = 3;
    case GreatSword = 4;
    case Axe = 5;
    case GreatAxe = 6;
    case Scythe = 7;
    case Polearm = 8;
    case Katana = 9;
    case GreatKatana = 10;
    case Club = 11;
    case Staff = 12;
    case AutomatonMelee = 22;
    case AutomatonRanged = 23;
    case AutomatonMagic = 24;
    case Archery = 25;
    case Marksmanship = 26;
    case Throwing = 27;
    case Guard = 28;
    case Evasion = 29;
    case Shield = 30;
    case Parry = 31;
    case DivineMagic = 32;
    case HealingMagic = 33;
    case EnhancingMagic = 34;
    case EnfeeblingMagic = 35;
    case ElementalMagic = 36;
    case DarkMagic = 37;
    case SummoningMagic = 38;
    case Ninjutsu = 39;
    case Singing = 40;
    case StringInstrument = 41;
    case WindInstrument = 42;
    case BlueMagic = 43;

    public function label(): string {
        return match($this) {
            self::None => "NONE",
            self::HandToHand => "HAND_TO_HAND",
            self::Dagger => "DAGGER",
            self::Sword => "SWORD",
            self::GreatSword => "GREAT_SWORD",
            self::Axe => "AXE",
            self::GreatAxe => "GREAT_AXE",
            self::Scythe => "SCYTHE",
            self::Polearm => "POLEARM",
            self::Katana => "KATANA",
            self::GreatKatana => "GREAT_KATANA",
            self::Club => "CLUB",
            self::Staff => "STAFF",
            self::AutomatonMelee => "AUTOMATON_MELEE",
            self::AutomatonRanged => "AUTOMATON_RANGED",
            self::AutomatonMagic => "AUTOMATON_MAGIC",
            self::Archery => "ARCHERY",
            self::Marksmanship => "MARKSMANSHIP",
            self::Throwing => "THROWING",
            self::Guard => "GUARD",
            self::Evasion => "EVASION",
            self::Shield => "SHIELD",
            self::Parry => "PARRY",
            self::DivineMagic => "DIVINE_MAGIC",
            self::HealingMagic => "HEALING_MAGIC",
            self::EnhancingMagic => "ENHANCING_MAGIC",
            self::EnfeeblingMagic => "ENFEEBLING_MAGIC",
            self::ElementalMagic => "ELEMENTAL_MAGIC",
            self::DarkMagic => "DARK_MAGIC",
            self::SummoningMagic => "SUMMONING_MAGIC",
            self::Ninjutsu => "NINJUTSU",
            self::Singing => "SINGING",
            self::StringInstrument => "STRING_INSTRUMENT",
            self::WindInstrument => "WIND_INSTRUMENT",
            self::BlueMagic => "BLUE_MAGIC",
        };
    }
}

?>
