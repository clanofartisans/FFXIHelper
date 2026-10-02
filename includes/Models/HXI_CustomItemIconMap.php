<?php

/**
 * The ~22 server-custom items (id >= 50000) have no client DAT art of their own - this maps
 * each to a real item id whose icon/appearance is borrowed for display. Extracted from the old
 * FFXIPackageHelper_ItemDetails::$replacement array (name/descr/etc for these ids were already
 * resolved directly into their own dat_details row at generation time - this map is only for
 * icon/image lookups, which still need the real client-side item id).
 */
class HXI_CustomItemIconMap {

    /** @var array<int,int> custom itemid => real itemid to borrow the icon from */
    private static array $iconId = [
        50000 => 18642, // Rucke's Rung
        50001 => 27269, // Vaulters Ring
        50002 => 26930, // Luftpause Mark
        50003 => 27008, // Horuss Helm
        50004 => 27009, // Dilation Ring
        50005 => 18656, // Carapace Bullet
        50006 => 26833, // Opuntia Hoop
        50007 => 26832, // Overlords ring
        50008 => 26656, // Sprinter's belt
        50009 => 27360, // Deflecting Band
        50010 => 27184, // Duality loop
        50011 => 23045, // Shepherd's Bonnet
        50012 => 23313, // Shepherd's boot
        50013 => 23246, // Shepherd's hose
        50014 => 23179, // Shepherd's bracers
        50015 => 23112, // Shepherd's doublet
        50016 => 21388, // Sack of dream sand
        50017 => 28260, // Dream Ribbon
        50018 => 28239, // Dream Collar
        50019 => 23123, // Buffalo Helm
        50020 => 18301, // Ancient Adamantoise Egg
        50021 => 23040, // Nanaa's Charm
    ];

    /** Returns the item id whose client icon should be used to display $itemid. */
    public static function iconIdFor(int $itemid): int {
        return self::$iconId[$itemid] ?? $itemid;
    }
}

?>
