<?php

/**
 * One saved gear set (user_sets row). A character can have many of these.
 */
class HXI_EquipmentSet {

    public int $usersetid = 0;
    public string $setname = "";
    public int $mlvl = 0;
    public int $slvl = 0;
    public int $mjob = 0;
    public int $sjob = 0;

    /** Raw urlencoded/delimited equipment string as stored on user_sets.equipment. */
    public string $equipmentString = "";

    /** Populated on demand by callers that need typed slot data (e.g. HXI_EquipmentParser). */
    public ?HXI_Equipment $equipment = null;

    public function __construct(int $mlvl = 0, int $slvl = 0, int $mjob = 0, int $sjob = 0, string $equipmentString = "", string $setname = "", int $usersetid = 0) {
        $this->mlvl = $mlvl;
        $this->slvl = $slvl;
        $this->mjob = $mjob;
        $this->sjob = $sjob;
        $this->equipmentString = $equipmentString;

        if ($this->equipmentString !== "") {
            $equipmentDecoded = urldecode($this->equipmentString);
            $this->equipmentString = base64_decode($equipmentDecoded);
        }

        $this->setname = $setname;
        $this->usersetid = $usersetid;
    }

    /**
     * Bool identifying if this is a blank/unset set (e.g. nothing chosen yet on a fresh page load).
     */
    public function isDefault(): bool {
        return $this->mlvl == 0 && $this->slvl == 0 && $this->mjob == 0 && $this->sjob == 0;
    }

    public function canGenerateStats(): bool {
        return $this->mlvl != 0 && $this->slvl != 0 && $this->mjob != 0 && $this->sjob != 0;
    }

    public function toArray(): array {
        return [
            'usersetid' => $this->usersetid,
            'setname' => $this->setname,
            'mlvl' => $this->mlvl,
            'slvl' => $this->slvl,
            'mjob' => $this->mjob,
            'sjob' => $this->sjob,
            'equipment' => $this->equipmentString,
            'isDefault' => $this->isDefault(),
            'canGenerateStats' => $this->canGenerateStats(),
        ];
    }
}

?>
