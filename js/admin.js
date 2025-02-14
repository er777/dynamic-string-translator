jQuery(document).ready(function($) {
	// Show first tab content by default
	    $('#simple').show();

	    // Add instruction text
	    $('.wrap h2').after('<p class="description">Enter original text in the left field and its translation in the right field.</p>');
	});

    // Tab switching
	$('.nav-tab-wrapper a').click(function(e) {
        e.preventDefault();
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });
	
    // Add new row
    $('.add-row').click(function() {
        var table = $(this).closest('table');
        var newRow = `
            <tr>
                <td><input type="text" name="original[]" value="" /></td>
                <td><input type="text" name="translated[]" value="" /></td>
                <td>
                    <button type="button" class="button remove-row">Remove</button>
                </td>
            </tr>
        `;
        table.find('tr:last').before(newRow);
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });
});
