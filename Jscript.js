var main = () => {
    handle_post_settings();
    multiple_select_box();
    //toggleImageSettings();
}

var handle_post_settings = () => {
    var bulkActionSelector = document.getElementById('bulk-action-selector');
    var categorySelector = document.getElementById('category-selector');
    var actionButton = document.getElementById('action-button');

    if (bulkActionSelector) {
        bulkActionSelector.addEventListener('change', function() {
            var selectedAction = this.value;
            if (selectedAction === 'add_category') {
                categorySelector.style.display = '';
                actionButton.style.display = '';
            } else if (selectedAction === 'remove_category' || selectedAction === 'delete_posts') {
                categorySelector.style.display = 'none';
                actionButton.style.display = '';
            } else {
                categorySelector.style.display = 'none';
                actionButton.style.display = 'none';
            }
        });
    }

}

var multiple_select_box = () => {
    // Select all checkboxes with the name 'post_ids[]'
    let checkboxes = document.querySelectorAll('input[type="checkbox"][name="post_ids[]"]');
    let lastChecked = null;

    if (checkboxes) {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                if (!lastChecked) {
                    lastChecked = this;
                    return;
                }
    
                if (e.shiftKey) {
                    let start = Array.from(checkboxes).indexOf(this);
                    let end = Array.from(checkboxes).indexOf(lastChecked);
                    let checkboxesToChange = (start < end) ? 
                        Array.from(checkboxes).slice(start, end) : 
                        Array.from(checkboxes).slice(end + 1, start + 1);
    
                    checkboxesToChange.forEach(box => {
                        box.checked = lastChecked.checked;
                    });
                }
    
                lastChecked = this;
            });
        });
    }

}

document.addEventListener('DOMContentLoaded', main);