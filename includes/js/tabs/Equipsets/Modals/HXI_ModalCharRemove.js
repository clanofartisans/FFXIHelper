
class ModalCharRemoveWindow {
    constructor(options = {}) {
        this.charname = null;

        this.options = {
            overlay: true,
            ...options
        };

        this.createModal();
    }
  
    createModal() {
        this.modal = document.createElement('div');
        this.modal.classList.add('modal');

        const contentWrapper = document.createElement('div');
        contentWrapper.classList.add('modal-content');

        const br = document.createElement("br");
        const removeCharTitleText = document.createElement("h2");
        removeCharTitleText.id = "HXI_dynamiccontent_removeCharTitleText";
        //removeCharTitleText.textContent = 'Remove ';
        contentWrapper.appendChild(removeCharTitleText);
        contentWrapper.appendChild(br);

        const inputWrapper = document.createElement('div');
        inputWrapper.setAttribute('style', 'display: inline-flex; gap: 20px;');

            const closeButton = document.createElement('button');
            closeButton.id = 'HXI_dynamiccontent_closeCharsRemove';
            closeButton.classList.add("close-modal", "HXI_dynamiccontent_customButton", "customButton_cancel");
            closeButton.textContent = 'Cancel';

            const removeButton = document.createElement('button');
            removeButton.id = 'HXI_dynamiccontent_removeChar';
            removeButton.classList.add("HXI_dynamiccontent_customButton", "customButton_removeItem");
            removeButton.textContent = 'Remove';

            inputWrapper.appendChild(removeButton);
            inputWrapper.appendChild(closeButton);

        contentWrapper.appendChild(inputWrapper);
        contentWrapper.appendChild(br);

        this.modal.appendChild(contentWrapper);
        document.body.appendChild(this.modal);

        this.addEventListeners();
    }
  
    addEventListeners() {
        const closeButton =  document.getElementById("HXI_dynamiccontent_closeCharsRemove" );
        closeButton.addEventListener('click', (e) => {
                this.close();
            });

        const removeButton = document.getElementById("HXI_dynamiccontent_removeChar");
        removeButton.addEventListener('click', (e) =>  {
            this.options.removeCallback(this.charname);
            this.close();
        });
    }
  

    open(charname) {
        this.charname = charname;
        const titleText = document.getElementById("HXI_dynamiccontent_removeCharTitleText");
        titleText.textContent = 'Remove \'' + this.charname + '\'';

        this.modal.classList.add('open');
    }
  
    close() {
        this.modal.classList.remove('open');
    }
}


module.exports = ModalCharRemoveWindow;