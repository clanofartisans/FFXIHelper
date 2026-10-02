<?php

/**
 * Base equippable/inventory item.
 * Fields map to item_basic + item_equipment (see sql/item_basic.sql, sql/item_equipment.sql).
 */
class HXI_Item {

    /** Server-custom items (no client DAT art of their own) start at this id. See HXI_CustomItemIconMap. */
    public const CUSTOM_ITEM_ID_THRESHOLD = 50000;

    public int $id = 0;
    public int $subId = 0;
    public string $name = "";
    public string $sortName = "";
    public int $type = 0;
    public int $stackSize = 1;
    public int $flags = 0;
    public int $auctionCategory = 0;
    public int $baseSell = 0;

    // item_equipment fields
    public int $level = 0;
    public int $iLevel = 0;
    public int $jobs = 0;
    public int $shieldSize = 0;
    public int $scriptType = 0;
    public int $slot = 0;
    public int $rslot = 0;
    public int $rslotlook = 0;
    public int $suLevel = 0;

    // From dat_details (client DAT-derived display text; see sql/DAT_details.sql)
    public string $longname = "";
    public string $descr = "";
    /** Comma-separated race codes, e.g. "HUME_M,HUME_F,GALKA" */
    public string $races = "";

    /** @var array<int,int> modId => value, from item_mods */
    private array $mods = [];

    public function __construct(int $id = 0, string $name = "") {
        $this->id = $id;
        $this->name = $name;
    }

    public function canEquipInSlot(HXI_EquipSlot $slot): bool {
        return ($this->slot & $slot->slotMask()) !== 0;
    }

    /**
     * True if this is a server-custom item added by this private server (id >= 50000), with no
     * art of its own in the retail game client - it borrows another item's icon for display
     * (see HXI_CustomItemIconMap). False means it's a genuine retail item.
     */
    public function isCustomItem(): bool {
        return $this->id >= self::CUSTOM_ITEM_ID_THRESHOLD;
    }

    public function setMod(int $modId, int $value): void {
        $this->mods[$modId] = $value;
    }

    public function getMod(int $modId): int {
        return $this->mods[$modId] ?? 0;
    }

    /** @return array<int,int> */
    public function getMods(): array {
        return $this->mods;
    }
}

?>
