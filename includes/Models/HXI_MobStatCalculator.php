<?php

/**
 * Computes a HXI_Mob's stats from raw SQL rows (mob_pools + mob_family_system + mob_resistances + mods/traits).
 * Extracted from the old FFXIPH_Mob::importSQL/setModifiersFromSQL and FFXIPH_MobUtils, which mixed this
 * calculation logic and its DB access directly into the Mob data model.
 */
class HXI_MobStatCalculator extends HXI_BaseStatCalculator {

    private function handleMods($mods){
        foreach ($mods as $mod) {
            // Pool and Family mods
            if ( isset($mod->is_mob_mod) && $mod->is_mob_mod == 1 ) {
                $modlabel = HXI_Variables::$mobModArray[$mod->modid];
            }
            else $modlabel = HXI_ModDictionary::getName($mod->modid);
            $this->addMod( $modlabel, $mod->value );
        }
    }

    private function handleTraits($SQLtraits){
        //Trait mods
        //traits are treated a little different than pool and family mods
        //traits are prioritized based on highest trait, not added together
        $traits = [];
        foreach ( $SQLtraits as $row ) {
            if ( !isset($traits[$row->modid]) ) $traits[$row->modid] = $row->value;
            else if ( $traits[$row->modid] < $row->value ) $traits[$row->modid] = $row->value;
        }

        foreach ( $traits as $m => $v ) {
            $modlabel = HXI_ModDictionary::getName($m);
            $this->addMod($modlabel, $v);
        }
    }

    private function setModifiersFromSQL( $SQLmob, $mLvl ){
        $db = new DatabaseQueryWrapper();

        $poolMods = $db->getMobPoolMods($SQLmob->poolid);
        $this->handleMods($poolMods);

        $familyMods = $db->getMobFamilyMods($SQLmob->familyID);
        $this->handleMods($familyMods);

        $traits =  $db->getTraits($mLvl, $mLvl, $SQLmob->mJob, $SQLmob->sJob);
        $this->handleTraits($traits);
    }

    // Base value for defense and evasion.
    // See: https://w.atwiki.jp/studiogobli/pages/25.html
    // Enemy defense = [f(Lv, racial defense rank) + 8 + [VIT/2] + job characteristics] x racial characteristics
    // Enemy evasion = f(Lv, main job evasion skill rank) + [AGI/2] + job characteristics
    private static function getBaseDefEva($lvl, $rank){
        if ($lvl > 50)
        {
            switch ($rank)
            {
                case 1: return floor(153 + ($lvl - 50) * 5.0);
                case 2: return floor(147 + ($lvl - 50) * 4.9);
                case 3: return floor(142 + ($lvl - 50) * 4.8);
                case 4: return floor(136 + ($lvl - 50) * 4.7);
                case 5: return floor(126 + ($lvl - 50) * 4.5);
            }
        }
        else
        {
            switch ($rank)
            {
                case 1: return floor(6 + ($lvl - 1) * 3.0);
                case 2: return floor(5 + ($lvl - 1) * 2.9);
                case 3: return floor(5 + ($lvl - 1) * 2.8);
                case 4: return floor(4 + ($lvl - 1) * 2.7);
                case 5: return floor(4 + ($lvl - 1) * 2.5);
            }
        }

        return 0;
    }

    private static function getMagicEvasion($mLvl, $evaRank){
        return self::getBaseSkill($mLvl, $evaRank);
    }

    // Gets base skill rankings for ACC/ATT/EVA/MEVA
    private static function getBaseSkill($mlvl, $rank){
        switch ($rank)
        {
            case 1: return self::getMaxSkill( 6 /* SKILL_GREAT_AXE */ , 1, $mlvl); // A+ Skill (1)
            case 2: return self::getMaxSkill( 12 /*SKILL_STAFF */ , 1, $mlvl); // B Skill (2)
            case 3: return self::getMaxSkill( 29 /*SKILL_EVASION */, 1, $mlvl); // C Skill (3)
            case 4: return self::getMaxSkill( 25 /*SKILL_ARCHERY */, 1, $mlvl); // D Skill (4)
            case 5: return self::getMaxSkill( 27 /*SKILL_THROWING */, 2, $mlvl); // E Skill (5)
        }

        return 0;
    }

    private static function getMaxSkill($skillID, $jobID, $level){
        $db = new DatabaseQueryWrapper();

        $maxSkillRank = $db->getSkillRank($skillID, $jobID);

        if ($level > 99){ $level = 99; }

        return $db->getSkillCap($level, $maxSkillRank);
    }

    /**
     * Populates $mob's stats from a raw mob_pools/mob_family_system/mob_resistances SQL row.
     * Mirrors the old FFXIPH_Mob::importSQL exactly, just relocated off the data model.
     */
    public function calculate(HXI_Mob $mob, $SQLmob, $useLvl){

        if ( $useLvl > 0 ){
            $mLvl = $useLvl;
        }
        else {
            $mLvl = $SQLmob->maxLevel;
        }

        $sLvl     = $mLvl; // mobs have 1:1 ratio for jobs
        $mJob     = $SQLmob->mJob;
        $sJob     = $SQLmob->sJob;
        $familyID = $SQLmob->familyID;

        $this->setModifiersFromSQL($SQLmob, $mLvl);
        $mob->setModifiers($this->modifiers);

        $mJobGrade = 0; // main jobs grade
        $sJobGrade = 0; // subjobs grade

        $maxHP = 0;
        $maxMP = 0;

        if ($SQLmob->HPmodifier == 0){
            $mobHP = 1; // Set mob HP

            $baseMobHP = 0; // Define base mobs hp
            $sjHP      = 0; // Define base subjob hp

            $mJobGrade = HXI_SkillGrades::$JobGrades[$mJob][0]; // main jobs grade
            $sJobGrade = HXI_SkillGrades::$JobGrades[$sJob][0]; // subjobs grade

            $base     = 0; // Column for base hp
            $jobScale = 1; // Column for job scaling
            $scaleX   = 2; // Column for modifier scale

            $BaseHP     = HXI_SkillGrades::$MobHPScale[$mJobGrade][$base];     // Main job base HP
            $JobScale   = HXI_SkillGrades::$MobHPScale[$mJobGrade][$jobScale]; // Main job scaling
            $ScaleXHP   = HXI_SkillGrades::$MobHPScale[$mJobGrade][$scaleX];   // Main job modifier scale
            $sjJobScale = HXI_SkillGrades::$MobHPScale[$sJobGrade][$jobScale]; // Sub job scaling
            $sjScaleXHP = HXI_SkillGrades::$MobHPScale[$sJobGrade][$scaleX];   // Sub job modifier scale

            $RIgrade = min($mLvl, 5); // RI Grade
            $RIbase  = 1;                        // Column for RI base

            $RI = HXI_SkillGrades::$MobRBI[$RIgrade][$RIbase]; // Random Increment addition per grade vs. base

            $mLvlIf    = ($mLvl > 5 ? 1 : 0);
            $mLvlIf30  = ($mLvl > 30 ? 1 : 0);
            $raceScale = 6;
            $mLvlScale = 0;

            if ($mLvl > 0)
            {
                $baseMobHP = $BaseHP + ((min($mLvl, 5) - 1) * ($JobScale + $raceScale - 1)) + $RI + ($mLvlIf * (min($mLvl, 30) - 5) * ((2 * ($JobScale + $raceScale) + min($mLvl, 30) - 6) / 2)) + ($mLvlIf30 * (($mLvl - 30) * (63 + $ScaleXHP) + ($mLvl - 31) * ($JobScale + $raceScale)));
            }

            // 50+ = 1 hp sjstats
            if ($mLvl > 49)
            {
                $mLvlScale = $mLvl;
            }
            // 40-49 = 3/4 hp sjstats
            else if ($mLvl > 39)
            {
                $mLvlScale = floor($mLvl * 0.75);
            }
            // 31-39 = 1/2 hp sjstats
            else if ($mLvl > 30)
            {
                $mLvlScale = floor($mLvl * 0.50);
            }
            // 25-30 = 1/4 hp sjstats
            else if ($mLvl > 24)
            {
                $mLvlScale = floor($mLvl * 0.25);
            }
            // 1-24 = no hp sjstats
            else
            {
                $mLvlScale = 0;
            }

            $sjHP = ceil(($sjJobScale * (max(($mLvlScale - 1), 0)) + (0.5 + 0.5 * $sjScaleXHP) * (max($mLvlScale - 10, 0)) + max($mLvlScale - 30, 0) + max($mLvlScale - 50, 0) + max($mLvlScale - 70, 0)) / 2);

            // Orcs 5% more hp
            if (($familyID == 189) || ($familyID == 190) || ($familyID == 334) || ($familyID == 407))
            {
                $mobHP = ($baseMobHP + $sjHP) * 1.05;
            }
            // Quadavs 5% less hp
            else if (($familyID == 200) || ($familyID == 201) || ($familyID == 202) || ($familyID == 337) || ($familyID == 397) || ($familyID == 408))
            {
                $mobHP = ($baseMobHP + $sjHP) * .95;
            }
            // Manticore family has 50% more HP
            else if ($familyID == 179)
            {
                $mobHP = ($baseMobHP + $sjHP) * 1.5;
            }
            else
            {
                $mobHP = $baseMobHP + $sjHP;
            }

            $maxHP = $mobHP;
        }
        else{
            $maxHP = $SQLmob->HPmodifier;
        }

        $hasMp = false;

        switch ($mJob)
        {
            case HXI_Variables::$jobArrayByName["PLD"]:
            case HXI_Variables::$jobArrayByName["WHM"]:
            case HXI_Variables::$jobArrayByName["BLM"]:
            case HXI_Variables::$jobArrayByName["RDM"]:
            case HXI_Variables::$jobArrayByName["DRK"]:
            case HXI_Variables::$jobArrayByName["BLU"]:
            case HXI_Variables::$jobArrayByName["SMN"]:
                $hasMp = true;
                break;
            default:
                break;
        }

        switch ($sJob)
        {
            case HXI_Variables::$jobArrayByName["PLD"]:
            case HXI_Variables::$jobArrayByName["WHM"]:
            case HXI_Variables::$jobArrayByName["BLM"]:
            case HXI_Variables::$jobArrayByName["RDM"]:
            case HXI_Variables::$jobArrayByName["DRK"]:
            case HXI_Variables::$jobArrayByName["BLU"]:
            case HXI_Variables::$jobArrayByName["SMN"]:
                $hasMp = true;
                break;
            default:
                break;
        }

        if ( isset( $this->modifiers["MOBMOD_MP_BASE"] ) ){ $hasMp = true; }

        if ($hasMp)
        {
            $scale = isset( $this->modifiers["MOBMOD_MP_BASE"] ) ? $this->modifiers["MOBMOD_MP_BASE"] / 100 : $SQLmob->MPscale;

            if ($SQLmob->MPmodifier == 0)
            {
                $maxMP = (18.2 * pow($mLvl, 1.1075) * $scale) + 10;
            }
            else
            {
                $maxMP = $SQLmob->MPmodifier;
            }

        }

        $fSTR = HXI_SkillGrades::baseToRank($SQLmob->STR, $mLvl);
        $fDEX = HXI_SkillGrades::baseToRank($SQLmob->DEX, $mLvl);
        $fVIT = HXI_SkillGrades::baseToRank($SQLmob->VIT, $mLvl);
        $fAGI = HXI_SkillGrades::baseToRank($SQLmob->AGI, $mLvl);
        $fINT = HXI_SkillGrades::baseToRank($SQLmob->INT, $mLvl);
        $fMND = HXI_SkillGrades::baseToRank($SQLmob->MND, $mLvl);
        $fCHR = HXI_SkillGrades::baseToRank($SQLmob->CHR, $mLvl);

        $mSTR = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][2], $mLvl );
        $mDEX = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][3], $mLvl );
        $mVIT = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][4], $mLvl );
        $mAGI = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][5], $mLvl );
        $mINT = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][6], $mLvl );
        $mMND = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][7], $mLvl );
        $mCHR = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$mJob][8], $mLvl );

        $sSTR = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][2], $sLvl );
        $sDEX = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][3], $sLvl );
        $sVIT = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][4], $sLvl );
        $sAGI = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][5], $sLvl );
        $sINT = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][6], $sLvl );
        $sMND = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][7], $sLvl );
        $sCHR = HXI_SkillGrades::baseToRank( HXI_SkillGrades::$JobGrades[$sJob][8], $sLvl );

        if ($mLvl >= 45)
        {
            $sSTR /= 2; $sDEX /= 2; $sAGI /= 2; $sINT /= 2; $sMND /= 2; $sCHR /= 2; $sVIT /= 2;
        }
        else if ($mLvl > 30)
        {
            $sSTR /= 3; $sDEX /= 3; $sAGI /= 3; $sINT /= 3; $sMND /= 3; $sCHR /= 3; $sVIT /= 3;
        }
        else
        {
            $sSTR /= 4; $sDEX /= 4; $sAGI /= 4; $sINT /= 4; $sMND /= 4; $sCHR /= 4; $sVIT /= 4;
        }

        $mob->setZone( $SQLmob->zonename );
        $mob->setName( $SQLmob->name );
        $mob->setMjob( $SQLmob->mJob );
        $mob->setSjob( $SQLmob->sJob );
        $mob->setMaxlvl( $mLvl );

        $mob->setHP( $maxHP );
        $mob->setMP( $maxMP );

        $mob->setSTR( ($fSTR + $mSTR + $sSTR) );
        $mob->setDEX( ($fDEX + $mDEX + $sDEX) );
        $mob->setVIT( ($fVIT + $mVIT + $sVIT) );
        $mob->setAGI( ($fAGI + $mAGI + $sAGI) );
        $mob->setINT( ($fINT + $mINT + $sINT) );
        $mob->setMND( ($fMND + $mMND + $sMND) );
        $mob->setCHR( ($fCHR + $mCHR + $sCHR) );

        $mob->setDEF( self::getBaseDefEva( $mLvl, $SQLmob->DEF ) );
        $mob->setEVA( self::getBaseDefEva( $mLvl, $SQLmob->EVA ) );
        $mob->setATT( self::getBaseSkill( $mLvl, $SQLmob->ATT ) );
        $mob->setACC( self::getBaseSkill( $mLvl, $SQLmob->ACC ) );

        $mob->setSlash_sdt( $SQLmob->slash_sdt * 1000 );
        $mob->setPierce_sdt( $SQLmob->pierce_sdt * 1000 );
        $mob->setH2H_sdt( $SQLmob->h2h_sdt * 1000 );
        $mob->setImpact_sdt( $SQLmob->impact_sdt * 1000 );
    }
}

?>
