<?php

/**
 * Mob type flags (bitmask - a mob's mobType column can have multiple bits set).
 * Each case is one flag; combine/check with & against the raw int, e.g.
 * HXI_MobType::Notorious->value & $mob->mobType.
 */
enum HXI_MobType: int {
    case Normal = 0x00;
    case Notorious = 0x02;
    case Fished = 0x04;
    case Called = 0x08;
    case Battlefield = 0x10;
    case Event = 0x20;

    public function label(): string {
        return match($this) {
            self::Normal => "NORMAL",
            self::Notorious => "NOTORIOUS",
            self::Fished => "FISHED",
            self::Called => "CALLED",
            self::Battlefield => "BATTLEFIELD",
            self::Event => "EVENT",
        };
    }
}

?>
