<?php

/**
 *
 * @param {$equipmentString} string, input from GET request, as itemid for each slot 0-15
 */
class HXI_EquipmentParser {
    private $incomingEquipmentList = [];
    /** @var array<int, ?HXI_Item> real typed item/weapon per slot, built via HXI_ItemFactory */
    private $itemObjects = [];

    public function __construct($equipment) {
        if ($equipment == null || $equipment == '' ) return;
        //wfDebugLog( 'Equipsets', get_called_class() . ":" . gettype($equipment) );
        if ( $this->detectDelimiter($equipment) == ',') $equipment = explode( ",", $equipment);
        else if ( $this->detectDelimiter($equipment) == '|' ) $equipment = explode( "|", $equipment);
        else if ( strlen($equipment) == 0 ) throw new Exception ("equp string len = 0");
        else throw new Exception("unknown detectDelimiter: " . json_encode($equipment));

        for ( $i = 0; $i <= 15; $i++ ){

            $temp = explode(',', $equipment[$i]);

            //throw new Exception ( json_encode($temp));

            $incItemID = intval($temp[0]);
            $incItemChangeFlag = intval($temp[1]);

            //if ( $incItemID != 0 ) {
                $itemObject = $this->queryItem( $incItemID );
                $this->itemObjects[$i] = $itemObject;
                $name = ($itemObject === null || $itemObject->name == null) ? "" : $itemObject->name;

                $this->incomingEquipmentList[$i] = [
                    $incItemID,
                    "",
                    $incItemChangeFlag,
                    $name
                ];
        }
    }

    /**
     * Builds the real typed item/weapon for one itemid via a single joined query
     * (DatabaseQueryWrapper::getFullItem + HXI_ItemFactory), replacing the old
     * getItem() + DataModel::parseEquipment() + HXI_ItemDetails lookup.
     */
    private function queryItem($item): ?HXI_Item {
        $db = new DatabaseQueryWrapper();
        $results = $db->getFullItem($item);
        return HXI_ItemFactory::fromFullItemRows($results);
    }

    public function getIncomingEquipmentList(){
        return $this->incomingEquipmentList;
    }

    /** @return array<int, ?HXI_Item> real typed item/weapon per slot (0-15) */
    public function getItemObjects(): array {
        return $this->itemObjects;
    }

    public function getWeaponInSlot(int $slot): ?HXI_Weapon {
        $item = $this->itemObjects[$slot] ?? null;
        return ($item instanceof HXI_Weapon) ? $item : null;
    }

    protected function detectDelimiter($line) {
        $test=explode('|', $line);
        if (count($test)>1) return '|';

        $test=explode(',', $line);
        if (count($test)>1) return ',';

        $url=rawurldecode($line);
        $test=explode('|', $url);
        if (count($test)>1) return '|';

        return null;
    }
} 
?>