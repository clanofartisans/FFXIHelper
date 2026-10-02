<?php

/**
 * A full 16-slot equipment loadout. One HXI_EquipmentSet holds one of these.
 * Not backed by its own DB table - it's the typed form of user_sets.equipment.
 */
class HXI_Equipment {

    /** @var array<int, ?HXI_Item> keyed by HXI_EquipSlot::value */
    private array $slots = [];

    public function __construct() {
        foreach (HXI_EquipSlot::cases() as $slot) {
            $this->slots[$slot->value] = null;
        }
    }

    public function getItem(HXI_EquipSlot $slot): ?HXI_Item {
        return $this->slots[$slot->value];
    }

    public function setItem(HXI_EquipSlot $slot, ?HXI_Item $item): void {
        $this->slots[$slot->value] = $item;
    }

    public function getWeapon(HXI_EquipSlot $slot): ?HXI_Weapon {
        $item = $this->getItem($slot);
        return ($item instanceof HXI_Weapon) ? $item : null;
    }

    public function isTwoHanding(): bool {
        $mainWeapon = $this->getWeapon(HXI_EquipSlot::Main);
        return $mainWeapon !== null && $mainWeapon->is2Handed();
    }

    /** @return array<int, ?HXI_Item> keyed by HXI_EquipSlot::value, in slot order 0-15 */
    public function toArray(): array {
        $result = [];
        foreach (HXI_EquipSlot::cases() as $slot) {
            $result[$slot->value] = $this->slots[$slot->value];
        }
        return $result;
    }
}

?>
