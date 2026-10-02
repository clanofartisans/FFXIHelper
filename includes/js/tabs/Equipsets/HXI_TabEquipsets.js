var API = require("./HXI_ActionAPI.js");
var Data = require("./HXI_DataManager.js");
var ModalWindow = require("./Modals/HXI_ModalWindow.js");
var ActionButtons = require("./HXI_ActionButtons.js");

var ModalSetManagement = require("./Modals/HXI_ModalSetManagement.js");
//var ModalCharManagement = require("./HXI_ModalCharManagement.js");

var Tooltip = require("./HXI_Tooltips.js");

const NEWSET_BUTTON = document.getElementById("HXI_newSetButton");
const SAVE_BUTTON = document.getElementById("HXI_dynamiccontent_saveSet");
// const REMOVE_BUTTON = document.getElementById("HXI_deleteSetButton");
//const //SELECTSET_DROPDOWN = document.getElementById("HXI_equipsets_selectSet");

const hiddenDiv = document.getElementById("HXI_dynamiccontent_newSetSection");
hiddenDiv.offsetTop;

var raceDropdown = null;
var mJobDropdown = null;
var sJobDropdown = null;
var mlvlDropdown = null;
var slvlDropdown = null;

// let currentSetName = null;
let setsModal = null;

module.exports.setLinks = function (){
    cleanAllTables();

    NEWSET_BUTTON.addEventListener("click", function () {
        if ( mJobDropdown.value == 0  || sJobDropdown.value == 0){
            mw.notify( "Main job and Sub job must be selected to save a set.", { autoHide: true,  type: 'error' } );
            return;
        }

        let inputElement = document.getElementById('HXI_dynamiccontent_setNameInput');
        inputElement.value = '';

        //toggle New button
        toggleNewButton();
    });

    SAVE_BUTTON.addEventListener('click', (e) =>  {
        saveSetClicked();
    });

    setupSetsList();

    /**
     * Modal Windows
     * All equip slots
     */
    for (let v = 0; v <= 15; v++) {
        let modal = new ModalWindow(v, { searchCallback: API.actionAPI, returnCallback: Data.updateEquipmentGrid});

        let str = "grid" + v;
        let slot = document.getElementById(str);

        slot.addEventListener("click", function (e) {
            modal.open(Data.getEquipID(v));
        });
        slot.addEventListener("keydown", function (e) {
            if ( e.key === "Enter" || e.key === " " ) {
                e.preventDefault();
                slot.click();
            }
        });
    }

    /**
     * Level range elements for both maind and sub jobs
     */
    slvlDropdown = document.getElementById("HXI_equipsets_selectSLevel");
    slvlDropdown.addEventListener("change", (e) =>  {
        //console.log(e.target.value);
        sJobMaxCheckbox.checked = 0;
        Data.updateStats();
    });

    mlvlDropdown = document.getElementById("HXI_equipsets_selectMLevel");
    mlvlDropdown.addEventListener("change", (e) =>  {
        //console.log(e.target.value);
        if ( document.getElementById("HXI_dynamiccontent_checkboxMaxSub").checked == 1 ){
            slvlDropdown.value = (e.target.value > 1) ? Math.floor(e.target.value / 2) : 1;
        }
        Data.updateStats();
    });

    raceDropdown = document.getElementById("HXI_equipsets_selectRace");
    // raceDropdown.addEventListener("change", (e) =>  {
    //     //console.log(e.target.value);
    //     Data.updateStats();
    //     Data.setHeaderCharacterDetails();
    // });

    /**
     * Main and Sub job elements
     */
    mJobDropdown = document.getElementById("HXI_equipsets_selectMJob");
    mJobDropdown.addEventListener("change", (e) => {
        //console.log(e.target.value);
        Data.updateStats();
        resetSetList();
    });

    sJobDropdown = document.getElementById("HXI_equipsets_selectSJob");
    sJobDropdown.addEventListener("change", (e) => {
        //console.log(e.target.value);
        Data.updateStats();
    });

    sJobMaxCheckbox = document.getElementById("HXI_dynamiccontent_checkboxMaxSub");
    sJobMaxCheckbox.addEventListener("change", (e) => {
        if ( e.target.checked == 1 ){
            slvlDropdown.value = (mlvlDropdown.value > 1) ? Math.floor(mlvlDropdown.value / 2) : 1;
        }
        Data.updateStats();
    });

    let shareEquipset = document.getElementById("HXI_dynamiccontent_shareEquipset");
    shareEquipset.addEventListener("click", function (e) {
        shareQueryClicked("HXI_dynamiccontent_shareEquipset", Data.getStatsData(true));
    });

    let shareDiscordEquipset = document.getElementById("HXI_dynamiccontent_shareDiscordEquipset");
    shareDiscordEquipset.addEventListener("click", function (e) {
        shareQueryClicked("HXI_dynamiccontent_shareDiscordEquipset", Data.getStatsData(true));
    });
    

     // Load Merit Edits section
    // MeritEdits.setLinks(Data.updateStats);
    
    // Saved sets sidebar: open on wide screens, collapsed on phones/tablets to keep the grid on screen
    const setsPanel = document.getElementById("HXI_Equipsets_setManagement");
    if ( setsPanel && window.matchMedia("(max-width: 1099px)").matches ) setsPanel.open = false;

    // Enter in the set name box saves
    document.getElementById("HXI_dynamiccontent_setNameInput").addEventListener("keydown", function (e) {
        if ( e.key === "Enter" ) { e.preventDefault(); saveSetClicked(); }
    });


    /**
     * DEV ONLY
     */
    // mlvlDropdown.value=75;
    // slvlDropdown.value=37;
    //mJobDropdown.value=1;
    //sJobDropdown.value=2;
    // raceDropdown.value=3;


    Data.getMeritsData();
    Tooltip.setupPageTooltips();
}

/**
 * One delegated listener on the sets container, so the list can be re-rendered
 * by the API without re-binding (or re-creating the remove modal) each time.
 */
function setupSetsList(){
    const container = document.getElementById("HXI_Equipsets_setManagement_setsList");
    if ( !container ) return;

    setsModal = new ModalSetManagement({ removeCallback: API.actionAPI, returnCallback: setRemoved });

    container.addEventListener("click", (e) => {
        const remove = e.target.closest(".HXI_setRemove");
        if ( remove ) {
            removeSetClicked(remove.dataset.value, remove.dataset.name);
            return;
        }

        const load = e.target.closest(".HXI_setLoad");
        if ( load ) {
            container.querySelectorAll(".HXI_setActive").forEach(el => el.classList.remove("HXI_setActive"));
            load.classList.add("HXI_setActive");
            selectSetClicked(load.dataset.value);
        }
    });
}

function selectSetClicked(usersetid){
    if ( usersetid == null ) return;

    const data = Data.getCharData();
    data.action = "equipsets_selectset",
    data.usersetid = usersetid;

    API.actionAPI(data, data.action, null, Data);
}

function removeSetClicked(setID, setName){
    setsModal.open(setID, setName);
}


function saveSetClicked(){
    const data = Data.getSetData();
    //console.log(data);
    if ( data.setname.length == 0 ){
        mw.notify( "Set name must be filled.", { autoHide: true,  type: 'error' } );
        return;
    }

    data.action = "equipsets_saveset";
    API.actionAPI(data, data.action, null, setSaved);

    toggleNewButton();
}

function setSaved(results){
    resetSetList(results);
}

function setRemoved(results){
    resetSetList(results);
}


function resetSetList(results){

    clearSetList();
    if (results){
        //console.log(results);
        buildSetslist(results);
    }
    else {
        const data = {
            action: "equipsets_getsets",
            mjob:document.getElementById("HXI_equipsets_selectMJob").value,
            };

        API.actionAPI(data, data.action, null, buildSetslist);
    }
}

function buildSetslist(results){
    if ( typeof results !== "string" ) return;
    document.getElementById("HXI_Equipsets_setManagement_setsList").innerHTML = results;
}

function clearSetList(){

}


function shareQueryClicked(shareID, params) {
    let GETparams = "";
    GETparams = "&race=" + params['race'] +
            "&mlvl=" + params['mlvl'] +
            "&slvl=" + params['slvl'] +
            "&mjob=" + params['mjob'] +
            "&sjob=" + params['sjob'] +
            "&merits=" + params['merits'] +
            "&equipment=" + params['equipment'];

    //const encodedParams = encodeURIComponent(GETparams);
    let wgServer = mw.config.get( 'wgServer' );
    let wgScriptPath = mw.config.get( 'wgScriptPath' );
    let url = wgServer +  wgScriptPath + "/index.php?title=Special:Equipsets" + GETparams;

    //console.log(url);

    if ( shareID == "HXI_dynamiccontent_shareDiscordEquipset" ){
        let mjob = document.getElementById("HXI_equipsets_selectMJob");

        mjob = mjob.options[mjob.selectedIndex].text;
        let sjob = document.getElementById("HXI_equipsets_selectSJob");
        sjob = sjob.options[sjob.selectedIndex].text;
        url = `[ ${mjob}/${sjob} - Wiki Equipset](` + url + `)`;
        //console.log(url);
    }

    navigator.clipboard.writeText(url).then(function() {
        //console.log('copyURLToClipboard(): Copied!');
        mw.notify( 'Copied to Clipboard !', { autoHide: true,  type: 'warn' } );
    }, function() {
      mw.notify( 'Error copying to clipboard. Please report on our Discord.', { autoHide: true,  type: 'error' } );
      //console.log('Clipboard error');
    });
};



function showSetButtonSelected(button, selected){
    if ( selected == true ) button.classList.add('HXI_setButtonselected');
    else button.classList.remove('HXI_setButtonselected');
}

function toggleNewButton() {
    NEWSET_BUTTON.classList.toggle('HXI_newSetButton_Grayed');
    const newset_buttonText = document.getElementById("HXI_newSetButton-text");
    const saving = NEWSET_BUTTON.classList.contains('HXI_newSetButton_Grayed');
    newset_buttonText.innerText = saving ? "Cancel" : "Save this set";

    // Job/level are part of the saved set, so lock them while naming it
    for ( const el of [ mJobDropdown, sJobDropdown, mlvlDropdown, slvlDropdown ] ) el.disabled = saving;

    hiddenDiv.style.display = saving ? "flex" : "none";
    if ( saving ) document.getElementById('HXI_dynamiccontent_setNameInput').focus();
}

function setDisabledState_AllSavedSetButtons(state){
    const setsDIV = document.getElementById("HXI_equipsets_setSelect");
    const setButtons = setsDIV.querySelectorAll('button[id*=HXI_setButton_]');
    //console.log(setButtons);
    for ( const button of setButtons ){
        button.disabled = state;
    }
}

function cleanAllTables(){
    let allDivsWithTables = document.getElementsByClassName("content-table-wrapper overflowed scroll-right");
    if ( allDivsWithTables.length > 0 ){
        for ( const div of allDivsWithTables ){
            div.classList.toggle("overflowed");
            div.classList.toggle("scroll-right");
        }
    }
    //console.log(allDivsWithTables) ;
}
