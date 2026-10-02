<?php


class HXI_HTMLTabMobSearch {
    public function __construct() { }

    public function searchForm(){
        $html = "<div id=\"HXI_tabs_combatsim_searchForm\">" .
                "<span><i><b>This page not meant for NMs. Site very much under construction.</b></i></span>" .
                "<table><tbody>
                    <tr><td>
                        <table><tbody>
                        <tr>" . 
                            //<td>Mob Name<br><input class=\"HXI_dynamiccontent_textinput\" name=\"mobNameSearch\" value=\"$this->mobName\" size=\"25\"></td>
                            "<td>Mob Name<br><input id=\"HXI_dynamiccontent_combatsim_mobsearch\" class=\"HXI_dynamiccontent_textinput\" size=\"25\">" . 
                                "<br>Level: " . HXI_HTMLTableHelper::selectLvlDropDown("HXI_dynamiccontent_selectLvlMob", 95) . 
                            "</td>" .
                        "</tr>
                        <tr>
                            <td><b>AND / OR</b></td>
                        </tr>
                        <tr>
                            <td>Zone<br>" . HXI_HTMLOptions::zonesDropDown("HXI_dynamiccontent_selectMobZoneName") . "<br><br><button id=\"HXI_dynamiccontent_searchForMobAndZone\" class=\"HXI_dynamiccontent_customButton\">Find Mob</button></td>
                        </tr>
                        </tbody></table>
                        </td>
                    </tr></tbody></table>
                    <div id=\"HXI_tabs_combatsim_queryresult\"></div>
                </div>";
        return $html;
    }

}



?>