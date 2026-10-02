<?php

enum HXI_EquipSlot: int {
    case Main  = 0;
    case Sub   = 1;
    case Range = 2;
    case Ammo  = 3;
    case Head  = 4;
    case Neck  = 5;
    case Ear1  = 6;
    case Ear2  = 7;
    case Body  = 8;
    case Hands = 9;
    case Ring1 = 10;
    case Ring2 = 11;
    case Back  = 12;
    case Waist = 13;
    case Legs  = 14;
    case Feet  = 15;

    /**
     * Slot label matching the existing grid/lua ordering used throughout HXI_Equipsets.
     */
    public function label(): string {
        return match($this) {
            self::Main  => "Main",
            self::Sub   => "Sub",
            self::Range => "Range",
            self::Ammo  => "Ammo",
            self::Head  => "Head",
            self::Neck  => "Neck",
            self::Ear1  => "Ear1",
            self::Ear2  => "Ear2",
            self::Body  => "Body",
            self::Hands => "Hands",
            self::Ring1 => "Ring1",
            self::Ring2 => "Ring2",
            self::Back  => "Back",
            self::Waist => "Waist",
            self::Legs  => "Legs",
            self::Feet  => "Feet",
        };
    }

    /**
     * item_equipment.slot bitmask this position is validated against.
     * Ear1/Ear2 share one bitmask (either earring slot), same for Ring1/Ring2.
     */
    public function slotMask(): int {
        return match($this) {
            self::Main  => 1,
            self::Sub   => 2,
            self::Range => 4,
            self::Ammo  => 8,
            self::Head  => 16,
            self::Body  => 32,
            self::Hands => 64,
            self::Legs  => 128,
            self::Feet  => 256,
            self::Neck  => 512,
            self::Ear1, self::Ear2 => 6144,
            self::Ring1, self::Ring2 => 24576,
            self::Waist => 1024,
            self::Back  => 32768,
        };
    }
}

?>
