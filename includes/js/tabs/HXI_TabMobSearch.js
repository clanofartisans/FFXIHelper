var API = require("./Equipsets/HXI_ActionAPI.js");
var Data = require("./Equipsets/HXI_DataManager.js");

var ModalCombatSimMobSelect = require("./Equipsets/Modals/HXI_ModalCombatSimMobSelect.js");


let modalMobSelect = null;

module.exports.setLinks = function (){

    let mobName_enterKeySubmit = document.getElementById("HXI_dynamiccontent_combatsim_mobsearch");
    if ( mobName_enterKeySubmit ) mobName_enterKeySubmit.addEventListener("keypress", (e) =>  {
        if (e.key === "Enter") {
            submitMobSearchRequest();
        }
    });

    let searchMobNameSubmit = document.getElementById("HXI_dynamiccontent_searchForMobAndZone");
    if ( searchMobNameSubmit ) searchMobNameSubmit.addEventListener("click", function (e) {
        submitMobSearchRequest();
    });

}

function validMobSearchQuery(params){
    if( params['mobname'] == "" && params['zonename'] == "searchallzones" ) return false;
    else return true;
}

function getQueryParams(){
    return {
      action: "combatsim_mobsearch",
      mobname: document.getElementById("HXI_dynamiccontent_combatsim_mobsearch").value, 
      zonename: document.getElementById("HXI_dynamiccontent_selectMobZoneName").value,
      moblevel: document.getElementById("HXI_dynamiccontent_selectLvlMob").value,
    };
}

function submitMobSearchRequest(){
  let params = getQueryParams();

  if( validMobSearchQuery(params) == false ){
      //document.getElementById("HXI_tabs_droprates_queryresult").innerHTML = "<i>*Please use the fields above to query a search.</i>";
      mw.notify( 'Mob name or zone are required.', { autoHide: true,  type: 'error' } );
      return;
    }

  let currentButton = document.getElementById("HXI_dynamiccontent_searchForMobAndZone");
  currentButton.disabled = true;
  document.getElementById("HXI_tabs_combatsim_queryresult").innerHTML = "Loading query...";

  API.actionAPI(params, "combatsim_mobsearch", "HXI_dynamiccontent_searchForMobAndZone", mobSearchRequestCallback);
}

function mobSearchRequestCallback(result){
    //console.log(result);
    if ( result['moblisttable'] ){
        document.getElementById("HXI_tabs_combatsim_queryresult").innerHTML = "";
        //updateMobAndZoneTable(result['moblisttable']);
        modalMobSelect = new ModalCombatSimMobSelect({ selectMobCallback: selectMob });
        modalMobSelect.open(result['moblisttable']);
        //setsModal = new ModalSetManagement({ removeCallback: API.actionAPI, returnCallback: setRemoved });
    }
    else if ( result['mobstatstable'] ){
        updateMobAndZoneTable(result['mobstatstable']);
    }
    else {
        updateMobAndZoneTable(result['noresults']);
    }

}

function updateMobAndZoneTable(incomingMobAndZoneTable){
  let combatSimTab = document.getElementById("HXI_tabs_combatsim_queryresult");
  combatSimTab.innerHTML = incomingMobAndZoneTable;
  //mw.hook( 'wikipage.content' ).fire($('#HXI_tabs_combatsim_queryresult'));
}

function selectMob(zone, mob, moblevel){
    modalMobSelect = null;
    let params = Data.getStatsData();

    params.action = "combatsim_selectedmob";
    params.mobname = mob;
    params.zonename = zone;
    params.moblevel = moblevel;
    //console.log(params);
    API.actionAPI(params, "combatsim_selectedmob", null, mobSearchRequestCallback );
}