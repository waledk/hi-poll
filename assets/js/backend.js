jQuery(function ($) {

    $(document).on('click', '.hipoll-options-list .new-option', function (e) {
        var hipollOptionsCont = $(this).parents('.hipoll-options-list'),
            hipollOptionInput = hipollOptionsCont.find('ul li.empty-field').outerHTML();

        hipollOptionsCont.find('ul').append(hipollOptionInput);
        hipollOptionsCont.find('ul li:last-child').attr('class', '').find('input').attr('name', 'hipoll_fields[options][][unregistered]');
    });

    $(document).on('click', '.hipoll-options-list .hipoll-delete-btn', function (e) {
        $(this).parents('.hipoll-options-list ul li').remove();
        hipollUpdatePreview();
    });

    /* Select Image Script */

    $('.stof-select-media').each(function () {
        var inputHTML = $(this).get(0).outerHTML,
            selectMediaHTML = '';
        selectMediaHTML += '<div class="stof-select-media">' +
            $(inputHTML).removeClass('stof-select-media').get(0).outerHTML +
            '<button type="button" class="select">Select Image</button>' +
            '<button type="button" class="edit">Edit Image</button>' +
            '<div class="preview"></div>' +
        '</div>';
        $(this).replaceWith(selectMediaHTML);
    });

    $(document).on('click', '.hipoll-select-media .select', function (event) {
		var imgParent = $(this).parents('.hipoll-select-media'),
			imgField = imgParent.find('input'),
			imgIdInput = imgField,
			imgContainer = imgParent.find('.preview'),
			delImgLink = imgParent.find('.delete-image'),
			frameTitle = imgField.attr('data-title'),
			btnText = imgField.attr('data-button'),
			multiple = imgField.attr('data-multiple'),
            frame;

        if ( multiple == 'true' ) {
			multiple = true;
        } else {
            multiple = false;
        }

		event.preventDefault();

		// Create a new media frame
		frame = wp.media({
			title: frameTitle,
			button: {
				text: btnText
			},
			multiple: multiple,  // Set to true to allow multiple files to be selected
			library: {
				type: [ 'image' ]
			}
		});

        frame.on('open',function() {
            var selection = frame.state().get('selection');
            var ids_value = imgField.val();

            if(ids_value.length > 0) {
                var ids = ids_value.split(',');

                ids.forEach(function(id) {
                    attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                });
            }
        });

		// When an image is selected in the media frame...
		frame.on('select', function () {

			// Get media attachment details from the frame state
			var attachments = frame.state().get('selection').toJSON();

			imgsIds = '';
            imgContainer.html('');
			$(attachments).each(function (index) {
                var attachmentURL = (typeof attachments[index].sizes.thumbnail != 'undefined') ? attachments[index].sizes.thumbnail.url : attachments[index].sizes.full.url;
				// Send the attachment URL to our custom image input field.
				imgContainer.append('<div class="single-image" data-id="' + attachments[index].id + '"><button type="button" class="delete-image">Delete<i class="feather icon-trash"></i></button><img src="' + attachmentURL + '" alt=""/></div>');
			});

			var selectedImages = imgParent.find('.preview .single-image');
			$(selectedImages).each(function (index) {
				if ( index != 0 ) {
					imgsIds += ',';
				}
				imgsIds += $(this).attr('data-id');
			});

			// Send the attachment id to our hidden input
			imgIdInput.val(imgsIds);
            hipollUpdatePreview();
		});

		// Finally, open the modal on click
		frame.open();
	});

    $(document).on('click', '.hipoll-select-media .preview .single-image .delete-image', function () {
		var singleImg = $(this).parents('.hipoll-select-media .single-image'),
			selectImgDiv = $(this).parents('.hipoll-select-media');

		singleImg.remove();

		var selectedImages = selectImgDiv.find('.preview .single-image'),
			imgsIds = '';

		$(selectedImages).each(function (index) {
			if ( index != 0 ) {
				imgsIds += ',';
			}
			imgsIds += $(this).attr('data-id');
		});
		selectImgDiv.find('input').val(imgsIds);
        hipollUpdatePreview();
	});

    /* End Select Image Script */

    $(document).on('change', '[name^="hipoll_fields"]', function () {
        hipollUpdatePreview();
    });

    $('.hipoll-options-list ul').sortable({
        axis: 'y',
        update: hipollUpdatePreview
    });

    function hipollUpdatePreview() {
        var pollPreview = $('.hipoll-frontend-poll'),
            pollImg = pollPreview.find('.poll-container .poll-image img'),
            pollFields = $('[name^="hipoll_fields"]').serializeFullArray()['hipoll_fields'];
        pollPreview.find('.poll-title p').text(pollFields['title']);
        var optionsDivContent = '';

        pollFields['options'] = (typeof pollFields['options'] == 'object') ? pollFields['options'] : {};
        Object.keys(pollFields['options']).forEach(function (key) {
            pollFields['options'][key] = (typeof pollFields['options'][key] == 'object') ? pollFields['options'][key] : {};
            Object.keys(pollFields['options'][key]).forEach(function (subKey) {
                optionsDivContent += '<div class="poll-single-option"><label><input name="5c2bdd0de022f" type="radio">' + pollFields['options'][key][subKey] + '</label></div>';
            });
        });
        pollPreview.find('.poll-options').html(optionsDivContent);

        if ( pollFields['style'] == 'dark' ) {
            pollPreview.addClass('dark-mode');
        } else {
            pollPreview.removeClass('dark-mode');
        }

        pollFields['attachment'] = pollFields['attachment'].split(',')[0];
        if ( pollFields['attachment'].length == 0 ) {
            pollImg.css('display', 'none');
        }
        wp.media.attachment(pollFields['attachment']).fetch().then(function (data) {
            var imgSrc = '';
            if ( typeof data.sizes.medium != 'undefined' ) {
                imgSrc = data.sizes.medium.url;
            } else if ( typeof data.sizes.full != 'undefined' ) {
                imgSrc = data.sizes.full.url;
            }
            pollImg.css('display', '');
            pollImg.attr('src', imgSrc);
        });

    }

    $.fn.serializeFullArray = function () {
		// Grab a set of name:value pairs from the form dom.
		var set = $(this).serializeArray();
		var output = [];

		for ( var field in set ) {
			if ( ! set.hasOwnProperty(field) ) continue;

			// Split up the field names into array tiers
			var parts = set[field].name.split('[');
            $.map(parts, function (val, i) { parts[i] = val.replace(']', ''); });

            var originalParts = parts;
			// We need to remove any blank parts returned by the regex.
            parts = $.grep(parts, function(n) { return n != ''; });

			// Start ref out at the root of the output object
			var ref = output;

			for ( var segment in parts ) {
				if ( ! parts.hasOwnProperty(segment) ) continue;

				// set key for ease of use.
				var key = parts[segment];
                var value = [];

				// If we're at the last part, the value comes from the original array.
				if ( segment == parts.length - 1 ) {
                    value = set[field].value;
				}

				// Create a throwaway object to merge into output.
				var objNew = [];
                if ( originalParts[segment].length == 0 ) {
                    var p = new Array();
                    p[key] = value;
                    var t = field + segment;
                    objNew[t] = p;
                } else {
                    objNew[key] = value;
                }

				// Extend output with our temp object at the depth specified by ref.
				$.extend(true, ref, objNew);

				// Reassign ref to point to this tier, so the next loop can extend it.
				ref = ref[key];
			}
		}

        return output;
	};

    $.fn.outerHTML = function () {
		return $('<div />').append(this.eq(0).clone()).html();
	};

    function uniqId() {
        return Math.round(new Date().getTime() + (Math.random() * 100));
    }

});
