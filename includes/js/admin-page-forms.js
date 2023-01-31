const addPLink = document.querySelectorAll('.show-new-p-form')[0];

addPLink.addEventListener('click', () => {
    const pForm = document.querySelector('.new-p-form');
    const former_text = addPLink.textContent;

    // Eventually, have a cancel button inside of the thickbox. Also when the user clicks the close button toggle the text back to Add New Affiliate

    if (former_text === 'Cancel') {
        pForm.reset();
        addPLink.textContent = 'Add New Affiliate';
    } else {
        addPLink.textContent = 'Cancel';
    }
});