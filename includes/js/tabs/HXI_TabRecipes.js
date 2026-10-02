const API = require("./Equipsets/HXI_ActionAPI.js");

module.exports.setLinks = function (){
    const searchRecipeSubmit = document.getElementById("HXI_dynamiccontent_searchRecipeSubmit");
    searchRecipeSubmit.addEventListener("click", function (e) {
    submitRecipeRequest();
    });

    const recipeName_enterKeysearchRecipesSubmit = document.querySelectorAll('input[name=recipeNameSearch]')[0];
    recipeName_enterKeysearchRecipesSubmit.addEventListener("keyup", function(e) {
    e.preventDefault();
    if (e.keyCode === 13) {
        submitRecipeRequest();
    }
    });

    const ingredient_enterKeysearchRecipesSubmit = document.querySelectorAll('input[name=ingredientSearch]')[0];
    ingredient_enterKeysearchRecipesSubmit.addEventListener("keyup", function(e) {
    e.preventDefault();
    if (e.keyCode === 13) {
        submitRecipeRequest();
    }
    });

    const inputElement = document.getElementById("HXI_dynamiccontent_selectCraft");
    inputElement.addEventListener("change", (event) => {
      // Code to execute when the input value changes
      if ( event.target.value !=  "none" ){
        document.getElementById("HXI_dynamiccontent_selectSkillRank").disabled = false;
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").disabled = false;
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").disabled = false;
      }
      else {
        document.getElementById("HXI_dynamiccontent_selectSkillRank").disabled = true;
        document.getElementById("HXI_dynamiccontent_selectSkillRank").value = "0";

        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").disabled = true;
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "0";

        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").disabled = true;
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "0";

      }
      //console.log(event.target.value);
    });

    const skillRankSelect = document.getElementById("HXI_dynamiccontent_selectSkillRank");
    skillRankSelect.addEventListener("change", (event) => {
      // Code to execute when the input value changes
      if ( event.target.value ==  "1" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "0";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "10";
      }
      else if ( event.target.value ==  "11" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "11";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "20";
      }
      else if ( event.target.value ==  "21" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "21";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "30";
      }
      else if ( event.target.value ==  "31" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "31";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "40";
      }
      else if ( event.target.value ==  "41" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "41";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "50";
      }
      else if ( event.target.value ==  "51" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "51";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "60";
      }
      else if ( event.target.value ==  "61" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "61";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "70";
      }
      else if ( event.target.value ==  "71" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "71";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "80";
      }
      else if ( event.target.value ==  "81" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "81";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "90";
      }
      else if ( event.target.value ==  "91" ){
        document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value = "91";
        document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value = "100";
      }
    });
}


function validRecipesQuery(params){
    if( params['recipename'] == "" && params['ingredient'] == "" && params['craft'] == "none" )return false;
    else return true;
  }

function getRecipesQueryParams(){
    return {
      action: "recipesearch",
      craft: document.getElementById("HXI_dynamiccontent_selectCraft").value,
      recipename: document.querySelectorAll('input[name=recipeNameSearch]')[0].value,
      ingredient: document.querySelectorAll('input[name=ingredientSearch]')[0].value,
      crystal: document.getElementById("HXI_dynamiccontent_selectCrystal").value,
      skillrank: document.getElementById("HXI_dynamiccontent_selectSkillRank").value,
      mincraftlvl: document.getElementById("HXI_dynamiccontent_selectMinCraftLvl").value,
      maxcraftlvl: document.getElementById("HXI_dynamiccontent_selectMaxCraftLvl").value,
      includedesynth: ( document.getElementById("HXI_dynamiccontent_checkboxIncludeDesynths").checked  ) ? 1 : 0
    };
  }

function submitRecipeRequest(){
    const params = getRecipesQueryParams();
  
    if ( validRecipesQuery(params) == false) {
      //document.getElementById("HXI_tabs_recipeSearch_queryresult").innerHTML = "<p style=\"color:red;\">Either Recipe Name, Ingredient, or Craft are required to search.</p>";
      mw.notify( 'Either Recipe Name, Ingredient, or Craft are required to search', { autoHide: true,  type: 'error' } );
      return;
    }
  
    const currentButton = document.getElementById("HXI_dynamiccontent_searchRecipeSubmit");
    currentButton.disabled = true;
    document.getElementById("HXI_tabs_recipeSearch_queryresult").innerHTML = "Loading query...";
  
    //console.log(params);
    API.actionAPI(params, "recipesearch", "HXI_dynamiccontent_searchRecipeSubmit");
  }

