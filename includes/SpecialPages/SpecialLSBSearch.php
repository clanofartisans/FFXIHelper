<?php


class SpecialLSBSearch extends SpecialPage {

	private $queryLimit = 1500;

    public function __construct( ) {
        parent::__construct( 'LSBSearch' );
    }

	static function onBeforePageDisplay( $out, $skin ) : void  {
		//$out->addModules(['HXI_LSBSearch']);
		if ( $out->getTitle() == "Special:LSBSearch" ) $out->addModules(['HXI_TabsController']);
	}

	function execute( $par ) {

		//$this->testing();
		$this->setHeaders();
		$request = $this->getRequest();
		$output = $this->getOutput();
		//$output->setPageTitle( "LSBSearch");
		// $output->setPageTitle( $this->msg( 'LSBSearch' )->text() );

		// # Get request data

		/**
		 *	Drop Rates Query Request Data
		 */
		$levelRangeMIN =  (int)$request->getText( 'levelRangeMIN' );
		$levelRangeMAX =  (int)$request->getText( 'levelRangeMAX' );
		$zoneNameDropDown = $request->getText( 'zoneNameDropDown' );
		$mobNameSearch = $request->getText( 'mobNameSearch' );
		$itemNameSearch = $request->getText( 'itemNameSearch' );
		$thRatesCheck = (int)$request->getText( 'thRatesCheck' );
		$showBCNMdrops = (int)$request->getText( 'showBCNMdrops' );
		$excludeNMs = (int)$request->getText( 'excludeNMs' );
		$includeSteal = (int)$request->getText( 'includeSteal' );
		$includeFished = (int)$request->getText( 'includeFished' );

		$queryDataDR = NULL;
		if ( 	$mobNameSearch == "" &&
				$itemNameSearch== "" &&
				( $zoneNameDropDown == "searchallzones" || $zoneNameDropDown == "") &&
				( ($levelRangeMIN == 0 && $levelRangeMAX == 0) || ( $levelRangeMIN > $levelRangeMAX )) ) {
					//do nothing
		}
		else {
			if ( $zoneNameDropDown != "searchallzones") $zoneNameDropDown = ucfirst($zoneNameDropDown);

			$queryDataDR = [
				$this->queryLimit,
				$mobNameSearch,
				$itemNameSearch,
				$zoneNameDropDown,
				$showBCNMdrops,
				$excludeNMs,
				$levelRangeMIN,
				$levelRangeMAX,
				$thRatesCheck,
				$includeSteal,
				$includeFished
			];
		}

		/**
		 *	Equipsets Request Data
		 */
		// $race = (int)$request->getText( 'race' );
		// $mlvl = (int)$request->getText( 'mlvl' );
		// $slvl = (int)$request->getText( 'slvl' );
		// $mjob = (int)$request->getText( 'mjob' );
		// $sjob = (int)$request->getText( 'sjob' );
		// $equipment = $request->getText( 'equipment' );

		// $equipsetsData = null;
		// if ( strlen($equipment) > 0 ){
		// 	$equipsetsData = [
		// 		$race,
		// 		$mlvl,
		// 		$slvl,
		// 		$mjob,
		// 		$sjob,
		// 		$equipment
		// 	];

		// }


        $tabs = new HXI_LSBSearch_HTMLTabsHelper();
        $tabDropRates = new HXI_HTMLTabDropRates($queryDataDR);
        $tabRecipes = new HXI_HTMLTabRecipeSearch();
		$tabEquipment = new HXI_HTMLTabEquipSearch();
        $tabFishing = new HXI_HTMLTabFishingSearch();
        $tabAdmin = new HXI_HTMLTabAdmin();
		$tabMobs = new HXI_HTMLTabMobSearch();

		$html = "<span><i><b>Disclosure:</b> All data here is from LandSandBoat(base), with minor additions/edits made based on direct feedback from players and Horizon Devs.
			Please reach out to the Wiki team on <a href=\"https://discord.com/channels/1078846428736147507/1159433939136553030\">Discord HERE</a> if you feel the data is incorrect or have suggestions. </b></i></span>";

        $html .= "<div id=\"initialHide\" style=\"display: none;\">" .
					$tabs->header() .
					$tabs->tab1($tabDropRates->searchForm()) .
					$tabs->tab2($tabRecipes->searchForm()) .
					$tabs->tab3($tabEquipment->searchForm()) .
					// $tabs->tab4($tabEquipsets->showEquipsets()) .
					$tabs->tab5($tabFishing->searchForm()) .
					$tabs->tab6($tabMobs->searchForm()) .
					$tabs->tab7($tabAdmin->showAdmin()) .
                "</div>";

		$output->addHTML( $html );
	}


}