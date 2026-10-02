<?php

/**
 * Equippable weapon. Adds item_weapon fields on top of HXI_Item (see sql/item_weapon.sql).
 */
class HXI_Weapon extends HXI_Item {

    public int $skill = 0;
    public int $subskill = 0;
    public int $ilvlSkill = 0;
    public int $ilvlParry = 0;
    public int $ilvlMAcc = 0;
    public int $dmgType = 0;
    public int $hit = 1;
    public int $delay = 0;
    public int $dmg = 0;
    public int $unlockPoints = 0;

    // Skill ids, matching the numbering used elsewhere in this codebase
    // (FFXIPH_MobUtils::getBaseSkill / HXI_EquipmentParser's legacy skilltype checks).
    private const SKILL_H2H          = 1;
    private const SKILL_GREAT_SWORD  = 4;
    private const SKILL_GREAT_AXE    = 6;
    private const SKILL_SCYTHE       = 7;
    private const SKILL_POLEARM      = 8;
    private const SKILL_GREAT_KATANA = 10;
    private const SKILL_STAFF        = 12;

    public function isH2H(): bool {
        return $this->skill === self::SKILL_H2H;
    }

    public function is2Handed(): bool {
        return in_array($this->skill, [
            self::SKILL_GREAT_SWORD,
            self::SKILL_GREAT_AXE,
            self::SKILL_SCYTHE,
            self::SKILL_POLEARM,
            self::SKILL_GREAT_KATANA,
            self::SKILL_STAFF,
        ], true);
    }
}

?>
