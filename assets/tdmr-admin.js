jQuery(document).ready(function() {

    var row = jQuery('select#role').closest('tr');
    row.html(jQuery('.tdmr-roles-container tr').html());
    jQuery('.tdmr-roles-container').remove();

})