<?php


class HXI_HTMLTabAdmin {
    public function __construct() {
    }

    public function showAdmin(){
        $html = "<div id=\"HXI_tabs_admin_display\">";

        $apiHelper = new HXI_APIHelper();
        if ( $apiHelper->userIsAuth() == false ) return $html . "This page is restricted to administrators and senior-editors.</div>";

        $db = new DatabaseQueryWrapper();
        $drhits = $db->getHitCounter("droprate");
        $eqhits = $db->getHitCounter("equipment");
        $fihits = $db->getHitCounter("fishing");
        $rehits = $db->getHitCounter("recipes");
        $mobsearchhits = $db->getHitCounter("mobsearch");

         $html = "<div id=\"HXI_tabs_admin_display\">" .
                    "<h3>LSBSearch Clicks:</h3>
                    <pre>Drop Rates: $drhits\nEquipment: $eqhits\nRecipes: $rehits\nFishing: $fihits\nMob Search: $mobsearchhits</pre>" .
                "</div>";
         return $html;
    }
}

?>