<?php


class HXI_HTMLOptions {
    public function __construct() {
      }

      public static function jobDropDown($classname, $sharedJob = null){
        $html = "<select id=\"". $classname ."\" defaultValue=\"0\" class=\"HXI_dynamiccontent_customDropDown\">";
        $html .= "<option value=\"0\">Any</option>";
        $html .= "<option value=\"1\">Warrior</option>";
        $html .= "<option value=\"2\">Monk</option>";
        $html .= "<option value=\"3\">White Mage</option>";
        $html .= "<option value=\"4\">Black Mage</option>";
        $html .= "<option value=\"5\">Red Mage</option>";
        $html .= "<option value=\"6\">Thief</option>";
        $html .= "<option value=\"7\">Paladin</option>";
        $html .= "<option value=\"8\">Dark Knight</option>";
        $html .= "<option value=\"9\">Beastmaster</option>";
        $html .= "<option value=\"10\">Bard</option>";
        $html .= "<option value=\"11\">Ranger</option>";
        $html .= "<option value=\"12\">Samurai</option>";
        $html .= "<option value=\"13\">Ninja</option>";
        $html .= "<option value=\"14\">Dragoon</option>";
        $html .= "<option value=\"15\">Summoner</option>";
        $html .= "<option value=\"16\">Blue Mage</option>";
        $html .= "<option value=\"17\">Corsair</option>";
        $html .= "<option value=\"18\">Puppetmaster</option>";
        // $html .= "<option value=\"19\">Dancer</option>";
        // $html .= "<option value=\"20\">Scholar</option>";
        // $html .= "<option value=\"21\">Geomancer</option>";
        // $html .= "<option value=\"22\">Rune Fencer</option>";

        // Select the job option from the shared link
        if ( $sharedJob != 0 ){
            $html = str_replace("value=\"$sharedJob\"", "value=\"$sharedJob\" selected=\"selected\"", $html);
        }
        
        $html .= "</select>";
        return $html;
    }

    public static function raceDropDown($classname, $sharedRace = null){
        $html = "<select id=\"". $classname ."\" defaultValue=\"0\" class=\"HXI_dynamiccontent_customDropDown\" disabled>";
        $html .= "<option value=\"0\" selected=\"selected\">Hume</option>";
        $html .= "<option value=\"1\">Elvaan</option>";
        $html .= "<option value=\"2\">Tarutaru</option>";
        $html .= "<option value=\"3\">Mithra</option>";
        $html .= "<option value=\"4\">Galka</option>";
        $html .= "</select>";

        // Select the race option from the shared link
        if ( $sharedRace != 0 ){
            $html = str_replace("value=\"$sharedRace\"", "value=\"$sharedRace\" selected=\"selected\"", $html);
        }
        
        //throw new Exception ($html);
        return $html;
    }

    public static function setsDropDown($classname){
        $user = RequestContext::getMain()->getUser();
        $uid = $user->getId();
        $db = new DatabaseQueryWrapper();
        $userSets = $db->getUserSetsFromUserID($uid);

        $html = "<select id=\"". $classname ."\" defaultValue=\"0\" class=\"HXI_dynamiccontent_customDropDown\" ";

        if ( count($userSets) > 0 ){
            $html .= ">";
            //$vars = new HXI_Variables();

            $currentJobType = 0;
            foreach ($userSets as $jobtype => $val ) {
                if ( $currentJobType != $jobtype ){
                    $currentJobType = $jobtype;
                    $html .= " <optgroup label=\"" . $jobtype . "\">";
                }
                foreach ( $val as $set ){
                    $html .= "<option value=\"" . $set["usersetid"] . "\">" . $set["setname"] . "</option>";
                    // $html .= "<option value=\"1\">Elvaan</option>";
                    //$html .= "<button id=\"HXI_setButton_" . $set["setname"] . "\" class=\"" . $classname . "\">" . $set["setname"] . "</button>";
                }
            }

        }
        else {
            $html .= "disabled>";
            $html .= "<option value=\"0\">None</option>";
        }

        $html .= "</select>";
        return $html;

    }

    public static function levelRange($classname, $sharedLvl = null){
        $html = "<select id=\"". $classname ."\" class=\"HXI_dynamiccontent_customDropDown\">";

        for ($i = 0; $i <= 75; $i++) {
            if ( $i == 0 ) $html .= "<option value=\"" . $i . "\">None</option>";
            else $html .= "<option value=\"" . $i . "\">" . $i . "</option>";
        }
        $html .= "</select>";

        // Select the lvl range option from the shared link
        if ( $sharedLvl != 0 ){
            $html = str_replace("value=\"$sharedLvl\"", "value=\"$sharedLvl\" selected=\"selected\"", $html);
        }
        

        return $html;
    }

    public static function subLevelRange($classname, $sharedLvl = null){
        $html = "<select id=\"". $classname ."\" class=\"HXI_dynamiccontent_customDropDown\">";

        for ($i = 0; $i <= 37; $i++) {
            if ( $i == 0 ) $html .= "<option value=\"" . $i . "\">None</option>";
            else $html .= "<option value=\"" . $i . "\">" . $i . "</option>";
        }
        $html .= "</select>";

        // Select the lvl range option from the shared link
        if ( $sharedLvl != 0 ){
            $html = str_replace("value=\"$sharedLvl\"", "value=\"$sharedLvl\" selected=\"selected\"", $html);
        }        

        return $html;
    }

    private static function zoneNamelist($fishing = null){
        $db = new DatabaseQueryWrapper();
        if ( $fishing == true ) $zonelist = $db->getZoneListFishing();
        else $zonelist = $db->getZoneList();

        foreach ($zonelist as $row) {
			$temp = ParserHelper::zoneERA_forList($row->name);
			if ( !isset($temp) ) continue;
            if ( $fishing == false && ExclusionsHelper::zoneIsTown($temp) ) continue;
            if ( ctype_digit($temp) ) continue;
			$result[$temp]=$row->name;
			//print_r($result[$temp] .", " . $row->name);
		}
		$result[' ** Search All Zones ** '] = "searchallzones";
		ksort($result);
		return $result ;
    }

    public static function zonesDropDown($id = null){
        if ( $id == null ) $id = "HXI_dynamiccontent_selectZoneName";
        $html = "<select id=\"$id\" class=\"HXI_dynamiccontent_customDropDown\">";
        $zoneNamesList = self::zoneNameList();

        foreach ($zoneNamesList as $key => $value) {
            $html .= "<option value=\"" . $value . "\">" . $key . "</option>";
        }

        $html .= "</select>";
        return $html;
    }

    public static function fishZonesDropDown(){
        $html = "<select id=\"HXI_dynamiccontent_selectFishingZone\" class=\"HXI_dynamiccontent_customDropDown\">";
        $zoneNamesList = self::zoneNameList(true);

        foreach ($zoneNamesList as $key => $value) {
            $html .= "<option value=\"" . $value . "\">" . $key . "</option>";
        }

        $html .= "</select>";
        return $html;
    }

    /**
     * 
     *      True = shoud select default character from array
     *      False = should select NONE character
     *      (string) = should select character with name (string)
     */
    public static function charactersButtonsList($userCharacters, $selectDefaultCharacter = null){

        // "<button id=\"HXI_newCharButton\" class=\"HXI_newCharButton\"></button>"
        // $html = "<button id=\"HXI_newCharButton\" class=\"HXI_newCharButton\"></button>";
        $html = "";

        //wfDebugLog( 'Equipsets', get_called_class() . ":charactersButtonsList:" . json_encode($selectDefaultCharacter) . ":gettype " .  gettype($selectDefaultCharacter) );
        //wfDebugLog( 'Equipsets', get_called_class() . ":charactersButtonsList:" . $html );

        if ( !is_null($userCharacters) && count($userCharacters) > 0 ){
            /* array of FFIXPH_Character objects */
            foreach ($userCharacters as $char) {
                $classlist = "HXI_charButton";    
                if ( $char->def != 0 ) {
                    $classlist .= " HXI_charButton_default";
                    
                    if ( $selectDefaultCharacter === true )  {
                        //$html = str_replace("HXI_charButtonselectDefaultCharactered", "", $html);
                        $classlist .= " HXI_charButtonselected";
                    }
                }

                if ( gettype($selectDefaultCharacter) == 'string' && $char->charname == $selectDefaultCharacter && !str_contains($classlist, "HXI_charButtonselected") ) {
                    $classlist .= " HXI_charButtonselected";
                }

                $html .= "<button id=\"HXI_charButton_" . $char->charname . "\" class=\"" . $classlist . "\">" . $char->charname . "</button>";
            }
        }
        
        $classlist = "HXI_charButton";
        if ( $selectDefaultCharacter === false ||  is_null($userCharacters) ){
            $classlist .= " HXI_charButtonselected";
            //wfDebugLog( 'Equipsets', get_called_class() . ":charactersButtonsList:" . json_encode($selectDefaultCharacter) );
        }
        $html = "<button id=\"HXI_charButtonNone\" class=\"" . $classlist . "\">None</button>" . $html;
        //$html .= "<button id=\"HXI_charButtonNone\" class=\"HXI_charButton\">None</button>";

        return $html;
    }


    public static function setsButtonsList(){
        $html = "";

        $user = RequestContext::getMain()->getUser();
        $uid = $user->getId();
        if ( $uid != 0 && $uid != null ){
            $db = new DatabaseQueryWrapper();
            $userSets = $db->getUserSetsFromUserID($uid);
            //throw new Exception ( json_encode($userSets));
            if ( count($userSets) > 0 ){
                foreach ($userSets as $set) {
                    // throw new Exception ( json_encode($set));
                    $classlist = "HXI_setButton";
                    // if ( $set["def"] != 0  ) $classlist .= " HXI_setButton_default";
                    $html .= "<button id=\"HXI_setButton_" . $set["usersetid"] . "\" class=\"" . $classlist . "\">" . $set["setname"] . "</button>";
                }
            }
        }

        return $html;
    }

    public static function selectableButtonsBar($barClassname, $userCharacters, $selectDefaultCharacter = null){
        $divName = "";
        $newButton = "";
        if ( $barClassname == "HXI_equipsets_setSelect"){
            $divName = "HXI_equipsets_setSelect";
            $newButton = "HXI_newSetButton";
        }
        else if ( $barClassname == "HXI_equipsets_charSelect" ){
            $divName = "HXI_equipsets_charSelect";
            $newButton = "HXI_newCharButton";
        }

        $html = "<div id=\"$divName\">" .
            "<button id=\"$newButton\" class=\"$newButton\">
                <svg width=\"10\" height=\"10\" viewBox=\"0 0 10 10\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">
                    <line x1=\"0\" y1=\"5\" x2=\"10\" y2=\"5\"  stroke-linecap=\"round\"/>
                    <line x1=\"5\" y1=\"0\" x2=\"5\" y2=\"10\"  stroke-linecap=\"round\"/>
                </svg>
                <span id=\"$newButton-text\">";
        if ( $newButton == "HXI_newSetButton") $html .= "Save this set" ;
        else $html .= "New";
        $html .= "</span></button>";
        
        $html .= "<div id=\"HXI_equipsets_charactersButtonsList\">";
        if ( $barClassname == "HXI_equipsets_charSelect" ) $html .= self::charactersButtonsList($userCharacters, $selectDefaultCharacter);
        //else if ( $barClassname == "HXI_equipsets_setSelect") $html .= self::setsButtonsList();
					
		$html .= "</div></div>";
        return $html;
    }

    public static function saveButton($classname){
        $html = "<button id=\"$classname\" class=\"$classname\">
                <svg width=\"10\" height=\"10\" viewBox=\"0 0 10 10\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">
                    <line x1=\"0\" y1=\"5\" x2=\"10\" y2=\"5\"  stroke-linecap=\"round\"/>
                    <line x1=\"5\" y1=\"0\" x2=\"5\" y2=\"10\"  stroke-linecap=\"round\"/>
                </svg>
                <span id=\"$classname-text\">";
        if ( $classname == "HXI_newSetButton") $html .= "Save this set" ;
        else $html .= "New";
        $html .= "</span></button>";
        return $html;
    }

    public static function setsList(){
        $html = "<details id=\"HXI_Equipsets_setManagement\" class=\"HXI_win HXI_gs_sets HXI_Equipsets_setManagement\" open>";
        $html .= "<summary class=\"HXI_win_title\">Saved Sets</summary>";
        $html .= "<div id=\"HXI_Equipsets_setManagement_setsList\" class=\"HXI_Equipsets_setManagement_setsList HXI_setsList\">";

        $user = RequestContext::getMain()->getUser();
        $uid = $user->getId();
        if ( $uid != 0 && $uid != null ){
            $db = new DatabaseQueryWrapper();
            $html .= self::setsListTable( $db->getUserSetsFromUserID($uid) );
        }
        else {
            $html .= "<p class=\"HXI_setsEmpty\">Log in to save and load your own sets.</p>";
        }
        $html .= "</div>";

        $html .= "</details>";
        return $html;
    }

    public static function setsListTable($userSets){
        $html = "<div id=\"HXI_Equipsets_setManagement_setsListTable\" class=\"HXI_setsGroups\">";
        if ( !empty($userSets) ){
            foreach ($userSets as $jobtype => $val ) {
                $html .= "<div class=\"HXI_setGroup\"><h4>" . htmlspecialchars($jobtype) . "</h4><ul>";
                foreach ( $val as $set ){
                    if ( gettype($set) == "string") throw new Exception ( json_encode($val));
                    $id = (int)$set["usersetid"];
                    $name = htmlspecialchars($set["setname"], ENT_QUOTES);
                    $html .= "<li>" .
                                "<button type=\"button\" class=\"HXI_setLoad\" data-value=\"$id\">$name</button>" .
                                "<button type=\"button\" class=\"HXI_setRemove\" data-value=\"$id\" data-name=\"$name\" title=\"Remove set\" aria-label=\"Remove set $name\">&times;</button>" .
                             "</li>";
                }
                $html .= "</ul></div>";
            }
        }
        else {
            $html .= "<p class=\"HXI_setsEmpty\">No saved sets yet. Equip some gear and press <b>Save this set</b>.</p>";
        }
        $html .= "</div>";

        return $html;
    }

}

?>