
// function searchInput(slot){
//     return  "<input id=\"HXI_equipsets_searchInput" + slot + "\" class=\"HXI_dynamiccontent_textinput\" size=\"20\">";
// }

// function searchButton(slot){
//     return "<button id=\"HXI_equipsets_search" + slot + "\" class=\"HXI_dynamiccontent_customButton\">Search</button>";
// }

// function searchResults(slot){
//     return "<br><br><div class=\"HXI_equipsets_searchResults_div\" style=\"max-height: 350px;overflow-y: auto;\"><p></p><dl id=\"HXI_equipsets_searchResults" + slot + "\" ></dl></div>";
// }

class ModalCharsWindow {
    constructor(options = {}) {

        this.options = {
            overlay: true,
            ...options
        };
        this.createModal();
    }
  
    createModal() {
        this.modal = document.createElement('div');
        this.modal.classList.add('modal');

        // if (this.options.overlay) {
        //     this.overlay = document.createElement('div');
        //     this.overlay.classList.add('overlay');
        //     this.modal.appendChild(this.overlay);
        // }

        const contentWrapper = document.createElement('div');
        contentWrapper.id = `HXI_equipsets_contentWrapperUserChars`;
        contentWrapper.classList.add('modal-content');

        let closeHTML = `<br><br><button style="float:left;" id="HXI_dynamiccontent_closeCharsWindow" class="close-modal HXI_dynamiccontent_customButton customButton_cancel">Cancel</button>`;
        let saveCharHTML = `<br><button class="HXI_dynamiccontent_customButton">Save Character</button>`;


        contentWrapper.innerHTML = `<h2>Char Management</h2>` + saveCharHTML +
            "<br><br><span style=\"position: absolute;font-size: .80em;font-style: italic;\"> Saves the current character RACE and MERITS </span>" +
            "<br>" + closeHTML;

        this.modal.appendChild(contentWrapper);

        // var originalMerits = document.querySelector(".HXI_dynamiccontent_showMerits");
        // var clonedMerits = originalMerits.cloneNode(true);
        // this.modal.appendChild(clonedMerits);

        document.body.appendChild(this.modal);

        this.addEventListeners();
    }
  
    addEventListeners() {

        const closeButton =  document.getElementById("HXI_dynamiccontent_closeCharsWindow" );
        closeButton.addEventListener('click', (e) => {
                this.close();
            });

        // const searchButton = document.getElementById("HXI_equipsets_search" + this.slot);
        // searchButton.addEventListener('click', (e) =>  {
        //     this.options.searchCallback(searchEquip(this.slot), "equipsets_search", null, this);
        // });
    }
  

    open() {
        this.modal.classList.add('open');
    }
  
    close() {
        this.modal.classList.remove('open');
    }
}


module.exports = ModalCharsWindow;