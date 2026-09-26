<?php

use MediaWiki\MediaWikiServices;

// SQL
// item_equipment
// main: 1=nothing in offhand, 3= can have offhand
// sub 2
// range 4
// ammo 8
// head 16
// neck 512
// ear 6144
// body 32
// hands 64
// rings 24576
// back 32768
// waist 1024
// legs 128
// feet 256


class HXI_Equipsets  {

    private $sharedLink;
    private $sharedEquipmentModel;
    private $updatedEquipmentData;

    public function __construct(HXI_Character $character, ?HXI_EquipmentSet $set = null) {
        if ($set === null) $set = new HXI_EquipmentSet();

        // Matches the old combined-model behavior: if there's no real request/saved
        // data on either half, fall back to a fully blank character + set.
        if ($character->isDefault() && $set->isDefault()) {
            $character = new HXI_Character();
            $set = new HXI_EquipmentSet();
        }

        $this->sharedLink = array_merge($character->toArray(), $set->toArray());
        $this->sharedLink['isDefault'] = $character->isDefault() && $set->isDefault();

        $this->sharedEquipmentModel = new HXI_EquipmentParser(  $this->sharedLink['equipment'] );
        $this->updatedEquipmentData = $this->updateGridItems($this->sharedEquipmentModel->getIncomingEquipmentList());

    }

    private const SLOT_NAMES = ["Main", "Sub", "Range", "Ammo", "Head", "Neck", "Ear1", "Ear2", "Body", "Hands", "Ring1", "Ring2", "Back", "Waist", "Legs", "Feet"];

    public function querySection(): string{
        $maxedSub = "<label class=\"HXI_dynamiccontent_checkContainer HXI_gs_max\"><input id=\"HXI_dynamiccontent_checkboxMaxSub\" type=\"checkbox\" checked=\"checked\"><span>Max</span></label>";
        $html = "<div class=\"HXI_Equipsets_selectOptions HXI_gs_jobs\">" .
                    "<div class=\"HXI_gs_jobRow\"><label class=\"HXI_gs_jobLabel\" for=\"HXI_equipsets_selectMJob\">Main</label>" .
                        HXI_HTMLOptions::jobDropDown("HXI_equipsets_selectMJob", $this->sharedLink['mjob']) .
                        "<span class=\"HXI_gs_lvl\"><label for=\"HXI_equipsets_selectMLevel\">Lv</label>" . HXI_HTMLOptions::levelRange("HXI_equipsets_selectMLevel", $this->sharedLink['mlvl']) . "</span>" .
                    "</div>" .
                    "<div class=\"HXI_gs_jobRow\"><label class=\"HXI_gs_jobLabel\" for=\"HXI_equipsets_selectSJob\">Sub</label>" .
                        HXI_HTMLOptions::jobDropDown("HXI_equipsets_selectSJob", $this->sharedLink['sjob']) . $maxedSub .
                        "<span class=\"HXI_gs_lvl\"><label for=\"HXI_equipsets_selectSLevel\">Lv</label>" . HXI_HTMLOptions::subLevelRange("HXI_equipsets_selectSLevel", $this->sharedLink['slvl']) . "</span>" .
                    "</div>" .
                "</div>";
        return $html;
    }

    /**
     * One row of the status window. $modId/$mod add the green/red "+n" bonus next to the value.
     */
    private function statRow(string $label, string $id, $value, ?string $modId = null, $mod = null, string $suffix = ""): string{
        $html = "<div class=\"HXI_stat\"><span class=\"HXI_stat_l\">$label</span><span class=\"HXI_stat_v\">" .
                    "<span id=\"HXI_Equipsets_stat$id\">" . ($value ?? 0) . "</span>";
        if ( $suffix !== "" ) $html .= "<span class=\"HXI_stat_suffix\">$suffix</span>";
        if ( $modId !== null ) $html .= "<span id=\"HXI_Equipsets_stat$modId\" class=\"HXI_mod " . self::modClass($mod) . "\">" . self::modText($mod) . "</span>";
        $html .= "</span></div>";
        return $html;
    }

    /**
     * Content of #HXI_Equipsets_showstatstable (also returned by the API on every change).
     */
    public function statsSection( $stats = null): string{
        $s = fn($i) => $stats ? ($stats[$i] ?? 0) : 0;

        $html  = "<div class=\"HXI_statGroup HXI_statGroup_vitals\">" .
                    $this->statRow("HP", "HP", $s(0)) .
                    $this->statRow("MP", "MP", $s(1)) .
                 "</div>";

        $html .= "<div class=\"HXI_statGroup\">" .
                    $this->statRow("STR", "STR", $s(2),  "STRMod", $s(3)) .
                    $this->statRow("DEX", "DEX", $s(4),  "DEXMod", $s(5)) .
                    $this->statRow("VIT", "VIT", $s(6),  "VITMod", $s(7)) .
                    $this->statRow("AGI", "AGI", $s(8),  "AGIMod", $s(9)) .
                    $this->statRow("INT", "INT", $s(10), "INTMod", $s(11)) .
                    $this->statRow("MND", "MND", $s(12), "MNDMod", $s(13)) .
                    $this->statRow("CHR", "CHR", $s(14), "CHRMod", $s(15)) .
                 "</div>";

        $html .= "<div class=\"HXI_statGroup\">" .
                    $this->statRow("Defense", "DEF", $s(16)) .
                    $this->statRow("Attack", "ATT", $s(17)) .
                    $this->statRow("Accuracy", "ACC", $s(26)) .
                    $this->statRow("Evasion", "EVA", $s(27)) .
                    $this->statRow("Ranged Acc.", "RACC", $s(34)) .
                 "</div>";

        $html .= "<div class=\"HXI_statGroup\">" .
                    $this->statRow("Gear Haste", "HasteGear", $stats ? self::styleHaste($stats[28]["gear"]) : 0, null, null, "%") .
                    $this->statRow("Fast Cast", "FastCast", $s(29), null, null, "%") .
                    $this->statRow("PDT", "PDT", $s(30), null, null, "%") .
                    $this->statRow("MDT", "MDT", $s(31), null, null, "%") .
                    $this->statRow("Conserve MP", "ConserveMP", $s(32)) .
                    $this->statRow("Enmity", "Enmity", $s(33)) .
                 "</div>";

        return $html;
    }

    private static function styleHaste($stat){
        return $stat / 100;
    }

    private static function modClass($stat): string{
        if ( $stat > 0 ) return "HXI_pos";
        if ( $stat < 0 ) return "HXI_neg";
        return "";
    }

    private static function modText($stat): string{
        if ( !$stat ) return "";
        return ( $stat > 0 ? "+" : "" ) . $stat;
    }

    public function equipmentGrid($updatedGridItems = null){
        $html = "";

        for ( $s = 0; $s <= 15; $s++){
            $item = $updatedGridItems[$s] ?? null;
            $name = self::SLOT_NAMES[$s];

            // hint.css tooltip host: the API swaps the aria-label/class on this element (see HXI_Tooltips.js)
            $html .= "<div class=\"HXI_slot";
            $tip = $item[2] ?? "";
            if ( $tip ) $html .= " hint--bottom\" aria-label=\"" . str_replace("\"", "&quot;", $tip);
            $html .= "\">" .
                        "<span class=\"HXI_slot_label\">$name</span>" .
                        "<div class=\"equipsetsGridImage\" id=\"grid$s\" role=\"button\" tabindex=\"0\" title=\"Change $name\" data-value=\"" . ($item[1][0] ?? 0) . "\">" . ($item[1][1] ?? "") . "</div>" .
                     "</div>";
        }

        return $html;
    }

    public function resistances( $stats = null){
        $elements = ["Fire", "Wind", "Lightning", "Light", "Ice", "Earth", "Water", "Dark"];

        $icons = [];
        foreach ( $elements as $e ) $icons[] = "[[File:Trans_" . $e . ".gif|20px|link=]]";
        $icons = ParserHelper::wikiParse($icons);

        $html = "<div class=\"HXI_resGrid\">";
        for ( $r = 0; $r <= 7; $r++){
            $html .= "<div class=\"HXI_Equipsets_statRes HXI_res\" title=\"" . $elements[$r] . " resistance\">" . $icons[$r] .
                        "<span id=\"HXI_Equipsets_statRes" . $r . "\">" . ($stats ? $stats[$r + 18] : 0) . "</span></div>";
        }
        $html .= "</div>";
        return $html;
    }

    public function userSetsData(){
        $html = "<div class=\"HXI_gs_actions\">" .
                    HXI_HTMLOptions::saveButton("HXI_newSetButton") .
                    HXI_HTMLTableHelper::shareButton("HXI_dynamiccontent_shareEquipset", "Copy link") .
                    HXI_HTMLTableHelper::shareDiscordButton("HXI_dynamiccontent_shareDiscordEquipset", "Discord") .
                "</div>" .
                "<div id=\"HXI_dynamiccontent_newSetSection\" class=\"HXI_gs_saveRow\" style=\"display: none;\">" .
                    "<input type=\"text\" id=\"HXI_dynamiccontent_setNameInput\" class=\"HXI_dynamiccontent_setNameInput\" placeholder=\"Set name\" maxlength=\"25\" aria-label=\"Set name\">" .
                    "<button id=\"HXI_dynamiccontent_saveSet\" class=\"HXI_newSetButton HXI_saveSetButton\">Save</button>" .
                "</div>";
        return $html;
    }

    /**
     * @param $luaNamesArray array of strings | names of items
     */
    public function additionalData($luaNamesArray){
        $html = "<details class=\"HXI_win HXI_gs_collapsible HXI_Equipsets_equipList\">" .
                    "<summary class=\"HXI_win_title\">Equipment List</summary>" .
                    "<ul class=\"HXI_eqlist\">";
        for ( $i = 0; $i <= 15; $i++ ){
            $n = $luaNamesArray[$i] ?? 0;
            $label = ( $n !== 0 && $n !== null ) ? ParserHelper::wikiParse("[[" . $n . "]]") : "- ";
            $html .= "<li><span class=\"HXI_eqlist_slot\">" . self::SLOT_NAMES[$i] . "</span><span class=\"HXI_eqlist_item\" id=\"HXI_Equipsets_gridLabel$i\">$label</span></li>";
        }
        $html .= "</ul></details>";
        return $html;
    }

    public function luaContent(){
        $html = "<details class=\"HXI_win HXI_gs_collapsible HXI_Equipsets_container\">" .
                    "<summary class=\"HXI_win_title\">Export (Lua)</summary>" .
                    "<div id=\"HXI_Equipsets_showLuaSets\" class=\"HXI_gs_lua\">";
        $setsHTML = new HXI_LuaSetsHelper();
        $html .= $setsHTML->__getSetsHTML( $this->updatedEquipmentData[1] );
        $html .= "</div></details>";
        return $html;
    }

    public function showEquipsets(){
        $stats = null;
        if ( $this->sharedLink['canGenerateStats'] ) {
            $newStats = new HXI_CharacterStatCalculator( $this->sharedLink['race'],
                                                    $this->sharedLink['mlvl'],
                                                    $this->sharedLink['slvl'],
                                                    $this->sharedLink['mjob'],
                                                    $this->sharedLink['sjob'],
                                                    $this->sharedLink['merits'],
                                                    $this->sharedEquipmentModel->getItemObjects() );
            $stats =  $newStats->getStats();
        }

        $html = "<div class=\"HXI_gs\">" .
                    "<div class=\"HXI_gs_layout\">" .

                        // Equipment window: jobs + 4x4 grid + resistances + actions
                        "<section class=\"HXI_gs_main\">" .
                            "<div class=\"HXI_win HXI_gs_equip\">" .
                                "<div class=\"HXI_win_title\">Equipment</div>" .
                                $this->querySection() .
                                "<div id=\"HXI_Equipsets_equipmentgrid\" class=\"HXI_Equipsets_equipmentgrid HXI_grid\">" . $this->equipmentGrid( $this->updatedEquipmentData[0] ) . "</div>" .
                                "<div id=\"HXI_Equipsets_showstats_res\" class=\"HXI_Equipsets_showstats_res\">" . $this->resistances( $stats ) . "</div>" .
                            "</div>" .
                            $this->userSetsData() .
                        "</section>" .

                        // Stats window
                        "<section class=\"HXI_gs_stats\">" .
                            "<div class=\"HXI_win HXI_Equipsets_showstats\">" .
                                "<div class=\"HXI_win_title\">Stats</div>" .
                                "<div id=\"HXI_Equipsets_showstatstable\" class=\"HXI_Equipsets_showstatstable HXI_stats\">" . $this->statsSection( $stats ) . "</div>" .
                            "</div>" .
                        "</section>" .

                        // Saved sets, export, equipment list
                        "<section class=\"HXI_gs_side\">" .
                            HXI_HTMLOptions::setsList() .
                            $this->luaContent() .
                            $this->additionalData( $this->updatedEquipmentData[1] ) .
                        "</section>" .

                    "</div>" .
                    "<p class=\"HXI_gs_note\"><i><b>Disclosure:</b> Please reach out with any questions/comments via Discord.</i></p>" .
                "</div>";

        return $html;
    }

    public function generateTooltip($details){
        $output = "";
        if ( $details["name"] == ucwords($details["longname"]) ) $output = $details["name"] . "\n\n";
        else $output = $details["name"] . "\n(" . ucwords($details["longname"]) . ")\n\n";

        $output .= $details["descr"] . "\n\nLv." . $details["lvl"] . " ";

        if ( count($details["jobs"]) == 22 )  $output .= " All Jobs";
        else if ( count($details["jobs"]) <= 6 ) $output .= implode( "/", $details["jobs"]);
        else  {
            $chunks = array_chunk($details["jobs"], 6);
            for( $c = 0; $c < count($chunks); $c++){
                if ( $c != 0 ) $output .= "\t  ";
                $output .= implode( "/", $chunks[$c]) . "\n";
            }
        }
        return $output;
    }

    public function updateGridItems($slot, $force = false){
        
        if ($slot == null){
            for ( $s = 0; $s <= 15; $s++){
                $slot[$s] = [
                    0,
                    0,
                    1,  // set flag = 1 on the first iteration to show initial grid with no items
                    ""];
            }
        }

        $wParser = ParserHelper::wikiParseOptions();
        $title = $wParser[0];
        $parser = $wParser[1];
        $parserOptions = $wParser[2];

        $iDetails = new HXI_ItemDetails();


        $updatedGrid = array();
        $luaNames = array();
        for ( $s = 0; $s <= 15; $s++){
            $tooltip = "";
            $id = intval($slot[$s][0]);
            if ( $id > 50000 && array_key_exists($id , $iDetails->replacement) ) $id = $iDetails->replacement[ $id ];

            $slot[$s][1] = ( $id != 0 ) ? "itemid_" . $id . ".png" : self::getDefaultImageName($s);

//wfDebugLog( 'Equipsets', get_called_class() . ":updateGridItems:" . $id );

            if ( $id != 0 )  $luaNames[] = ucwords($iDetails->items[ $id ]["name"]);
            else $luaNames[] = 0;

            if ( $slot[$s][2] == 1 || $force == true ){
                $slot[$s][1] = "[[File:". $slot[$s][1] . "|64px|link=]]";
                $parserOutput = $parser->parse( $slot[$s][1], $title, $parserOptions );
                $slot[$s][1] = $parserOutput->getText();

                if ( $id != 0 ){
                    $tooltip = $this->generateTooltip($iDetails->items[ $id ]);
                }

                $updatedGrid[] = [$s, $slot[$s], $tooltip];
            }
        }
        return [$updatedGrid, $luaNames];
    }


    public function showMerits(HXI_Character $c){
        $html = "<div class=\"HXI_dynamiccontent_showMerits\" >
                    <div id=\"HXI_dynamiccontent_showMerits_table\" class=\"HXI_dynamiccontent_showMerits_table HXI_merit_groups\">" .
                        $this->showMeritsTable($c) .
                    "</div>" .
                "</div>";
        return $html;
    }

    /**
     * Merit boxes: one box per category, laid out inline by CSS and wrapped as the screen narrows.
     * Each row keeps the same input ids (HXI_equipsets_merits_stats#/skill#) the JS relies on.
     */
    public function showMeritsTable(HXI_Character $c){
        $groups = [
            "Stats" => [ 2 => ["HP", "+10 per"], 5 => ["MP", "+10 per"] ],
            "Attributes" => [ 8 => ["STR", "+1 per"], 9 => ["DEX", "+1 per"], 10 => ["VIT", "+1 per"], 11 => ["AGI", "+1 per"],
                              12 => ["INT", "+1 per"], 13 => ["MND", "+1 per"], 14 => ["CHR", "+1 per"] ],
            "Combat Skills" => [ 80 => ["Hand to Hand", "+2 per"], 81 => ["Dagger", "+2 per"], 82 => ["Sword", "+2 per"],
                                 83 => ["Great Sword", "+2 per"], 84 => ["Axe", "+2 per"], 85 => ["Great Axe", "+2 per"],
                                 86 => ["Scythe", "+2 per"], 87 => ["Polearm", "+2 per"], 88 => ["Katana", "+2 per"],
                                 89 => ["Great Katana", "+2 per"], 90 => ["Club", "+2 per"], 91 => ["Staff", "+2 per"],
                                 104 => ["Archery", "+2 per"], 105 => ["Marksmanship", "+2 per"], 106 => ["Throwing", "+2 per"],
                                 107 => ["Guard", "+2 per"], 108 => ["Evasion", "+2 per"], 109 => ["Shield", "+2 per"], 110 => ["Parry", "+2 per"] ],
            "Magic Skills" => [ 111 => ["Divine Magic", "+2 per"], 112 => ["Healing Magic", "+2 per"], 113 => ["Enhancing Magic", "+2 per"],
                                114 => ["Enfeebling Magic", "+2 per"], 115 => ["Elemental Magic", "+2 per"], 116 => ["Dark Magic", "+2 per"],
                                117 => ["Summoning Magic", "+2 per"], 118 => ["Ninjutsu", "+2 per"], 119 => ["Singing", "+2 per"],
                                120 => ["String Instrument", "+2 per"], 121 => ["Wind Instrument", "+2 per"] ],
            "Other Skills" => [ 27 => ["Enmity Increase", "+1 per"], 999 => ["Enmity Decrease", "-1 per"], 165 => ["Crit Hit Rate", "+1% per"],
                                166 => ["Enemy Crit Hit Rate", "-1% per"], 168 => ["Spell Interruption Rate", "-2% per"] ],
        ];

        $html = "";
        foreach ( $groups as $title => $rows ) {
            $html .= "<div class=\"HXI_merit_group\"><h4>$title</h4><div class=\"HXI_merit_rows\">";
            foreach ( $rows as $id => $row ) {
                $html .= "<div class=\"HXI_merit\">" .
                            "<span class=\"HXI_merit_l\">" . $row[0] . " <small>(" . $row[1] . ")</small></span>" .
                            $this->meritIncrement($id, $c->getMerit($id)) .
                         "</div>";
            }
            $html .= "</div></div>";
        }
        return $html;
    }

	private function meritIncrement($merit, $value = 0){
		if ( $merit <= 14 ) $type = "stats";
		else $type = "skill";

        $html = "<div id=\"HXI_dynamiccontent_counterbox\" class=\"HXI_dynamiccontent_counterbox\">" . 
            HXI_HTMLTableHelper::incrementMinus() .
            "<input id=\"HXI_equipsets_merits_$type$merit\" class=\"HXI_dynamiccontent_incrementInput\" type=\"text\" value=\"$value\" readonly >" . 
            HXI_HTMLTableHelper::incrementPlus() .
            "</div>";

			return $html;
    }

    // private function showShareButton($id){
    //     return HXI_HTMLTableHelper::shareButton($id);
    // }

    private function getDefaultImageName($s){
        switch($s){
            case 0: return "Main.jpg";
            case 1: return "Sub.jpg";
            case 2: return "Range.jpg";
            case 3: return "Ammo.jpg";
            case 4: return "Head.jpg";
            case 5: return "Neck.jpg";
            case 6: return "Ear1.jpg";
            case 7: return "Ear2.jpg";
            case 8: return "Body.jpg";
            case 9: return "Hands.jpg";
            case 10: return "Ring1.jpg";
            case 11: return "Ring2.jpg";
            case 12: return "Back.jpg";
            case 13: return "Waist.jpg";
            case 14: return "Legs.jpg";
            case 15: return "Feet.jpg";
        }
    }

    public function showCharacters($userChars, $shouldLoadDefaultCharacter, HXI_Character $c){
        $defaultChecked = ( $c->def == 1 ) ? " checked" : "";

        $html = "<div class=\"HXI_gs HXI_gs_chars\">" .
                    "<div id=\"HXI_equipsets_charTab\" class=\"HXI_gs_charTab\">" .

                        // Character select window
                        "<div class=\"HXI_win\">" .
                            "<div class=\"HXI_win_title\">Characters</div>" .
                            HXI_HTMLOptions::selectableButtonsBar("HXI_equipsets_charSelect", $userChars, $shouldLoadDefaultCharacter) .
                        "</div>" .

                        "<div id=\"HXI_equipsets_charSelectMerits\">" .

                            // Character details window
                            "<div class=\"HXI_win\">" .
                                "<div class=\"HXI_win_title\">Details</div>" .
                                "<div class=\"HXI_equipsets_charSelectOptionsMenu\">" .
                                    "<button id=\"HXI_editCharButton\" class=\"HXI_editCharButton\">Edit</button>" .
                                    "<button id=\"HXI_dynamiccontent_saveChar\" class=\"HXI_newCharButton HXI_saveCharButton\">Save</button>" .
                                    "<button id=\"HXI_deleteCharButton\" class=\"HXI_deleteCharButton\">Remove this character</button>" .
                                "</div>" .
                                "<div id=\"HXI_dynamiccontent_newCharSection\" class=\"HXI_charField\" style=\"display: none;\" >" .
                                    "<label class=\"HXI_charLabel\" for=\"HXI_dynamiccontent_charNameInput\">Name</label>" .
                                    "<input type=\"text\" id=\"HXI_dynamiccontent_charNameInput\" class=\"HXI_dynamiccontent_charNameInput\" placeholder=\"Character Name\" maxlength=\"25\">" .
                                "</div>" .
                                "<div class=\"HXI_equipsets_selectRace HXI_charFields\">" .
                                    "<div class=\"HXI_charField\">" .
                                        "<span class=\"HXI_charLabel\">Race</span>" . HXI_HTMLOptions::raceDropDown("HXI_equipsets_selectRace", $c->race) .
                                    "</div>" .
                                    "<div class=\"HXI_charField\">" .
                                        "<span class=\"HXI_charLabel\">Default</span>" .
                                        "<label class=\"HXI_dynamiccontent_addCharDefaultLabel\">" .
                                            "<input type=\"checkbox\" id=\"HXI_dynamiccontent_defaultChar\" class=\"HXI_dynamiccontent_addCharDefaultInput\" disabled$defaultChecked>" .
                                            "<span class=\"HXI_dynamiccontent_addCharDefaultSpan HXI_dynamiccontent_addCharDefaultSpanround\"></span>" .
                                        "</label>" .
                                    "</div>" .
                                "</div>" .
                            "</div>" .

                            // Merits window
                            "<div class=\"HXI_win\">" .
                                "<div class=\"HXI_win_title\">Merits</div>" .
                                "<div class=\"HXI_dynamiccontent_showMerits\" >
                                    <div id=\"HXI_dynamiccontent_showMerits_table\" class=\"HXI_dynamiccontent_showMerits_table HXI_merit_groups\">" .
                                        $this->showMeritsTable($c) .
                                    "</div>" .
                                "</div>" .
                            "</div>" .

                        "</div>" .
                    "</div>" .
                    "<p class=\"HXI_gs_note\"><i><b>Disclosure:</b> Users must be logged in to save a character. Saving a character stores the RACE and MERITS set here. The character will be de-selected if any changes are made.</i></p>" .
                "</div>";
        return $html;
    }

    public function importLuaForm(){
        $html = "<h4>Rules</h4>" .
                        "<ul>" .
                            "<li>Tool is designed for those using <b>Luashitacast</b> lua sets. More to come in the future...</li>
                            <li>Tool is NOT designed for \"Priority\" leveling sets. Each slot can only have one piece of gear listed.</li>
                            <li><b>To import a lua to your current set you must select a job and job level on the \"Gear Sets\" tab first.</b></li>
                            <li>If you import your lua there is no mechanism (yet) to check all the gear in your set with the selected job/levels from the \"Gear Sets\" tab, so all the gear will equip and the stats will calculate as such. Take care to ensure the correct job is selected when equipping based off a lua.</li>
                        </ul>".
                        "<h4>How to use this tool:</h4>" .
                        "<ul>" .
                            "<li><b>Step 1:</b>Paste your set, directly from your lua and include the set name, in the box below. One set at a time, for now...</li>" .
                            "<li><b>Step 2:</b> Click \"Verify Lua\". This will check the formatting and find the equipment assocaited with your slots.</li>" .
                            "<li><b>Step 3:</b> Clicking \"Equip set\" will equip the set on the \"Gear Sets\" page, and will clear this page.<b>To import a lua to your current set you must select a job and job level on the \"Gear Sets\" tab first.</b></li>" .
                            "<li><i>[Under Construction]</i> Clicking \"Save set\" will permanently save the set for the user under the \"Additional Sets\" section.</li>
                        </ul>".
                        "<br><br>";
        $html .= "<textarea id=\"form_importlua\" name=\"form_importlua\"
                    style=\"width: 300px; height: 150px; resize:both;\"
                    placeholder=\"Paste LUA set here...\"></textarea><br>";
        $html .= "<button id=\"HXI_verifyluabutton\" class=\"HXI_importluaButton\">Verify Lua</button>";
        $html .= "<div id=\"HXI_importlua_verificationResults\"></div>";
        $html .= "<br><button id=\"HXI_importLuaButton\" class=\"HXI_importluaButton\" disabled>Equip set</button><span id=\"HXI_importLuaComment\"></span>";
        $html .= "<br><button id=\"HXI_saveImportedSetButton\" class=\"HXI_importluaButton\" disabled>Save set</button>";
        $html .= "<br><div id=\"HXI_importlua_importReady\"></div>";
        return $html;
    }
}

?>