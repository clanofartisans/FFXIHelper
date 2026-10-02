<?php

/**
 * A saved player character (user_chars row). Does not include job/level/equipment -
 * those belong to HXI_EquipmentSet, since one character can have many sets.
 */
class HXI_Character {

    public int $userid = 0;
    public int $charid = 0;
    public string $charname = "";
    public int $race = 0;
    public int $def = 0; // 1 = this is the user's default character

    /** @var array<int,int> merit id => value */
    private array $merits = [];

    public string $raceString = "";

    public function __construct(int $userid = 0, int $race = 0, string $meritsURLSafe = "", int $def = 0, string $charname = "", int $charid = 0) {
        $this->userid = $userid;
        $this->race = $race;
        $this->def = $def;
        $this->charname = $charname;
        $this->charid = $charid;

        if ($meritsURLSafe !== "" && !is_null($meritsURLSafe)) {
            $this->setMerits($meritsURLSafe);
        }

        $this->raceString = (HXI_Race::tryFrom($this->race) ?? HXI_Race::Hume)->label();
    }

    public function setRace(int $race): void {
        $this->race = $race;
        $this->raceString = (HXI_Race::tryFrom($this->race) ?? HXI_Race::Hume)->label();
    }

    /**
     * Bool identifying if this is a blank/unsaved character model.
     * Matches the original FFXIPH_Character convention: race == 0 (Hume) is the "unset" sentinel.
     */
    public function isDefault(): bool {
        return $this->race == 0;
    }

    public function setMerits(?string $meritsURLSafe): void {
        $this->merits = HXI_MeritsCodec::decode($meritsURLSafe);
    }

    public function setMerit($m, $value): void {
        $this->merits[$m] = (int)$value;
    }

    public function getMeritsURLSafe(): string {
        return HXI_MeritsCodec::encode($this->merits);
    }

    public function getMerit($m): int {
        return $this->merits[$m] ?? 0;
    }

    /** @return array<int,int> */
    public function getMerits(): array {
        return $this->merits;
    }

    public function hasMeritsSet(): bool {
        return count($this->merits) > 0;
    }

    public function toArray(): array {
        return [
            'userid' => $this->userid,
            'charid' => $this->charid,
            'charname' => $this->charname,
            'race' => $this->race,
            'merits' => $this->merits,
            'def' => $this->def,
            'isDefault' => $this->isDefault(),
        ];
    }

    public function toURLsafeArray(): array {
        return [
            'userid' => $this->userid,
            'charid' => $this->charid,
            'charname' => $this->charname,
            'race' => $this->race,
            'merits' => $this->getMeritsURLSafe(),
            'def' => $this->def,
            'isDefault' => $this->isDefault(),
        ];
    }
}

?>
