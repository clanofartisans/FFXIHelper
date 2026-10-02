<?php

class SpecialEquipsets extends SpecialPage {

    public function __construct( ) {
        parent::__construct( 'Equipsets' );
    }

	static function onBeforePageDisplay( $out, $skin ) : void  {
		//$out->addModules(['HXI_LSBSearch']);
		if ( $out->getTitle() == "Special:Equipsets" )  {
			$out->addBodyClasses( 'HXI-equipsets' );
				$out->addModules(['HXI_Equipsets']);
		}
	}

	private $userChars;
	private $shouldLoadDefaultCharacter = true;
	private $currentCharacter;
	private $currentSet;

	function execute( $par ) {
		$this->setHeaders();
		$output = $this->getOutput();
		//$output->setPageTitle( $this->msg( 'equipsets' ) );
		$request = $this->getRequest();

		// Check for user logged in
		$user = RequestContext::getMain()->getUser();
		$uid = $user->getId();

		/**
		 *	Equipsets Request Data
		 */
		$requestCharacter = new HXI_Character( $uid, (int)$request->getText( 'race' ), $request->getText( 'merits' ) );
		$requestSet = new HXI_EquipmentSet( (int)$request->getText( 'mlvl' ),
											(int)$request->getText( 'slvl' ),
											(int)$request->getText( 'mjob' ),
											(int)$request->getText( 'sjob' ),
											$request->getText( 'equipment' ) );

		if ( $uid != 0 ) { // User is logged in
			$db = new DatabaseQueryWrapper();
			$this->userChars = $db->getUserCharactersFromUserID($uid);
		}

		/**
		 * 	Determine which character to load on startup
		 *  Either the request data (from shared link) or default char saved in DB
		 */
		if ( !( $requestCharacter->isDefault() && $requestSet->isDefault() ) ) {
			$this->currentCharacter = $requestCharacter;
			$this->currentSet = $requestSet;
			$this->shouldLoadDefaultCharacter = false;
		}
		else {
				// Create blank models
				$this->currentCharacter = new HXI_Character($uid);
				$this->currentSet = new HXI_EquipmentSet();
				if ( $this->userChars){
					foreach( $this->userChars as $char ){
						/* If one of the chars in the users char array
						has the character->def property set then a default
						character exists, and use that instead*/
						if ( $char->def > 0 ) $this->currentCharacter = $char;
					}
				}
		}

		//wfDebugLog( 'Equipsets', get_called_class() . ":execute:" . json_encode($this->currentCharacter ) );
		$tabEquipsets = new HXI_Equipsets($this->currentCharacter, $this->currentSet);
		
		$tabMobs = new HXI_HTMLTabMobSearch();
		$apiHelper = new HXI_APIHelper();
        if ( $apiHelper->userIsAuth() == false ) $combatsimContent = "This page is restricted to administrators and senior-editors while under continued construction.";
		else $combatsimContent = $tabMobs->searchForm();

		$html = HXI_HTMLTableHelper::characterSelectedHeader($this->currentCharacter) .
			"<div id=\"initialHide\" style=\"display: none;\">" .
				$this->header() .
				$this->tabEquipsets( $tabEquipsets->showEquipsets() ) .
				$this->tabCharacter( $tabEquipsets->showCharacters($this->userChars, $this->shouldLoadDefaultCharacter, $this->currentCharacter) ) .
				$this->tabCombatSim( $combatsimContent ) .
				$this->tabImportLua( $tabEquipsets->importLuaForm() ) .
            "</div>";

		$output->addHTML( $html );
	}

	public function header(){
        return "<div class=\"HXI_tabs\">" .
					"<button id=\"HXI_tabs_equipsets\" class=\"tablinks\">Gear Sets</button>" .
					"<button id=\"HXI_tabs_characters\" class=\"tablinks\">Characters</button>" .
					"<button id=\"HXI_tabs_combatsim\" class=\"tablinks\">Combat Sim</button>" .
					"<button id=\"HXI_tabs_importlua\" class=\"tablinks\">Import Lua</button>" .
        		"</div>";
      }

    public function tabEquipsets($content){
        if ( !isset($content) ) $content = "<p>no content yet</p>";

        //remove when setting up for use on HXI
        //if ( isset($_SERVER['HTTP_HOST']) &&  $_SERVER['HTTP_HOST'] != 'localhost' ) $content = "<p><b>This site is still under construction. Coming soon !</b></p>";

        $html = "<div id=\"HXI_tabs_equipsets_shown\" class=\"tabcontent\">
		" . $content . "</div>";

        return $html;
    }

	public function tabCharacter($content){
		
		// $race = $this->currentCharacter->race;
		// $defaultSwitch = $this->currentCharacter->def;
		// $stats = $c->meritStats;
		// $skill = $c->meritSkills;
		// $stats = $c->getMerits();
		// $skill = $c->getMerits();
		 
		$html = "<div id=\"HXI_tabs_characters_shown\" class=\"tabcontent\">" .
				$content .
				"</div>";

        return $html;
    }

	public function tabCombatSim($content){
        if ( !isset($content) ) $content = "<p>no content yet</p>";

        //remove when setting up for use on HXI
        //if ( isset($_SERVER['HTTP_HOST']) &&  $_SERVER['HTTP_HOST'] != 'localhost' ) $content = "<p><b>This site is still under construction. Coming soon !</b></p>";
		
        $html = "<div id=\"HXI_tabs_combatsim_shown\" class=\"tabcontent\">
		" . $content . "</div>";

        return $html;
    }

	public function tabImportLua($content){
        if ( !isset($content)  ) $content = "<p>no content yet</p>";

        //remove when setting up for use on HXI
        //if ( isset($_SERVER['HTTP_HOST']) &&  $_SERVER['HTTP_HOST'] != 'localhost' ) $content = "<p><b>This site is still under construction. Coming soon !</b></p>";
		
        $html = "<div id=\"HXI_tabs_importlua_shown\" class=\"tabcontent\">
		" . $content . "</div>";

        return $html;
    }

	


}