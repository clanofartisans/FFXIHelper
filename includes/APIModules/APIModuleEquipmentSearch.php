<?php

//use ApiBase;

class APIModuleEquipmentSearch extends ApiBase {
    public function __construct( $main, $action ) {
        parent::__construct( $main, $action);
    }

    protected function getAllowedParams() {
        return [
            'action' => null,
			'equipmentname' => "0",
            'job' => "0",
            'minitemlvl' => "0",
            'slot' => "0",
    		];
	}

    function execute( ) {
        $params = $this->extractRequestParams();
        $result = $this->getResult();

        $queryData = [  $params['equipmentname'],
                        $params['job'],
                        $params['minitemlvl'],
                        $params['slot'],
                     ];

        $finalHtml = $this->queryEquipment($queryData);
        $finalHtml = ParserHelper::wikiParse($finalHtml);
        $result->addValue($params['action'], "equipment", $finalHtml);
    }

    private function queryEquipment($queryData){
        $db = new DatabaseQueryWrapper();

        $initialQuery = $db->getEquipmentFromDB($queryData);
        if ( count($initialQuery) > 0 )  $db->incrementHitCounter("equipment");

        $items = HXI_ItemFactory::fromFullItemRowsGrouped($initialQuery);

        $job = $queryData[1] ?? null;
        if ( $job != null && $job > 0 ) {
            $items = array_filter( $items, function($item) use ($job) {
                return ParserHelper::checkJob($job, $item->jobs);
            });
        }

        $html = "";

        $html .= HXI_HTMLTableHelper::table_EquipmentQuery($items);
        return $html;
	}

}
?>