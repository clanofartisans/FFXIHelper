<?php

/**
 * Builds HXI_Item/HXI_Weapon objects from the rows returned by
 * DatabaseQueryWrapper::getFullItem()/getEquipment()/getEquipmentFromDB() - each of those joins
 * item_basic, item_equipment, item_weapon, item_mods and dat_details, instead of a DB row plus a
 * separate lookup into the FFXIPackageHelper_ItemDetails static array.
 */
class HXI_ItemFactory {

    /**
     * @param iterable $rows result set for a single item - one row per mod (all other columns
     * repeat across rows for that item).
     */
    public static function fromFullItemRows($rows): ?HXI_Item {
        foreach (self::group($rows) as $itemRows) {
            return self::buildFromGroup($itemRows);
        }
        return null;
    }

    /**
     * @param iterable $rows result set covering MANY items (e.g. a search) - rows for different
     * items may be interleaved; each item's own mod rows are grouped together before building.
     * @return array<int, HXI_Item> keyed by itemid
     */
    public static function fromFullItemRowsGrouped($rows): array {
        $items = [];
        foreach (self::group($rows) as $id => $itemRows) {
            $items[$id] = self::buildFromGroup($itemRows);
        }
        return $items;
    }

    /** @return array<int, array> rows grouped by itemid, order of first appearance preserved */
    private static function group($rows): array {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row->itemid][] = $row;
        }
        return $grouped;
    }

    private static function buildFromGroup(array $rows): HXI_Item {
        $first = $rows[0];

        $isWeapon = !is_null($first->skilltype ?? null);

        $name = self::resolveName($first);
        $item = $isWeapon ? new HXI_Weapon((int)$first->itemid, $name) : new HXI_Item((int)$first->itemid, $name);

        $item->subId = (int)($first->subid ?? 0);
        $item->sortName = (string)($first->sortname ?? "");
        $item->type = (int)($first->type ?? 0);
        $item->stackSize = (int)($first->stackSize ?? 1);
        $item->flags = (int)($first->flags ?? 0);
        $item->auctionCategory = (int)($first->aH ?? 0);
        $item->baseSell = (int)($first->BaseSell ?? 0);

        $item->level = (int)($first->level ?? 0);
        $item->iLevel = (int)($first->ilevel ?? 0);
        $item->jobs = (int)($first->jobs ?? 0);
        $item->shieldSize = (int)($first->shieldSize ?? 0);
        $item->scriptType = (int)($first->scriptType ?? 0);
        $item->slot = (int)($first->slot ?? 0);
        $item->rslot = (int)($first->rslot ?? 0);
        $item->rslotlook = (int)($first->rslotlook ?? 0);
        $item->suLevel = (int)($first->su_level ?? 0);

        $item->longname = (string)($first->longname ?? "");
        $item->descr = (string)($first->descr ?? "");
        $item->races = (string)($first->races ?? "");

        if ($isWeapon) {
            $item->skill = (int)$first->skilltype;
            $item->subskill = (int)($first->subskill ?? 0);
            $item->ilvlSkill = (int)($first->ilvl_skill ?? 0);
            $item->ilvlParry = (int)($first->ilvl_parry ?? 0);
            $item->ilvlMAcc = (int)($first->ilvl_macc ?? 0);
            $item->dmgType = (int)($first->dmgType ?? 0);
            $item->hit = (int)($first->hit ?? 1);
            $item->delay = (int)($first->delay ?? 0);
            $item->dmg = (int)($first->dmg ?? 0);
            $item->unlockPoints = (int)($first->unlock_points ?? 0);
        }

        foreach ($rows as $row) {
            if (!is_null($row->modid ?? null) && !is_null($row->modValue ?? null)) {
                $item->setMod((int)$row->modid, (int)$row->modValue);
            }
        }

        return $item;
    }

    private static function resolveName($row): string {
        if (!empty($row->displayName)) return (string)$row->displayName;
        return (string)($row->showname ?? "");
    }
}

?>
