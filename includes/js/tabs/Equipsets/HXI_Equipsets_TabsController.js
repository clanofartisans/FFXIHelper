var TabEquipsets = require("./HXI_TabEquipsets.js");
var TabCharacters = require("./HXI_TabCharacters.js");
var TabCombatSim = require("../HXI_TabMobSearch.js");
var TabImportLua = require("./HXI_TabImportLua.js");

var Tabs = require("../HXI_ShowTabs.js");

function onPageLoad(){
//console.log("onReady")

  const tabsButton_equipsets = document.getElementById("HXI_tabs_equipsets");
  if ( tabsButton_equipsets == null ) {
    return ;
  }
  tabsButton_equipsets.addEventListener("click", function (e) {
    Tabs.showTab(e,tabsButton_equipsets.id);
  });
  tabsButton_equipsets.click();

  const tabsButton_characters = document.getElementById("HXI_tabs_characters");
  if ( tabsButton_characters == null ) {
    return ;
  }
  tabsButton_characters.addEventListener("click", function (e) {
    Tabs.showTab(e,tabsButton_characters.id);
  });

  const tabsButton_combatsim = document.getElementById("HXI_tabs_combatsim");
  if ( tabsButton_combatsim == null ) {
    return ;
  }
  tabsButton_combatsim.addEventListener("click", function (e) {
    Tabs.showTab(e,tabsButton_combatsim.id);
  });

  const tabsButton_importlua = document.getElementById("HXI_tabs_importlua");
  if ( tabsButton_importlua == null ) {
    return ;
  }
  tabsButton_importlua.addEventListener("click", function (e) {
    Tabs.showTab(e,tabsButton_importlua.id);
  });

  return 0;
}

var initiallyLoaded = false;
mw.hook('wikipage.content').add( function () {
  //console.log('wikipage.content: fired');
  if ( initiallyLoaded == true) return;

  if ( onPageLoad() == null) {
    console.log("Equipsets - Tab Controller: Tabs not found. ");
    return;
  }

  document.getElementById("initialHide").style.display = "block";

  // One tab failing to initialise (e.g. Combat Sim elements missing for logged-out
  // users) must not stop the others from loading.
  for ( const [name, tab] of [ ["Gear Sets", TabEquipsets], ["Characters", TabCharacters], ["Combat Sim", TabCombatSim], ["Import Lua", TabImportLua] ] ) {
    try { tab.setLinks(); }
    catch (e) { console.error("Equipsets - Tab Controller: '" + name + "' failed to initialise", e); }
  }

  initiallyLoaded = true;
  console.log("Equipsets - Tab Controller: initiallyLoaded");
  });

