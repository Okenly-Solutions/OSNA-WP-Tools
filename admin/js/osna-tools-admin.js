(function ($) {
    'use strict';

    /**
     * Ultimate Sliders Admin JS
     */
    $(document).ready(function () {
        // Media uploader for slide images
        $(document).on('click', '.upload-image', function (e) {
            e.preventDefault();

            var button = $(this);
            var container = button.closest('.image-preview-container');
            var preview = container.find('.image-preview');
            var input = container.find('input[type="hidden"]');
            var removeButton = container.find('.remove-image');

            var frame = wp.media({
                title: osna_tools_admin.i18n.select_image,
                multiple: false,
                library: {
                    type: 'image'
                },
                button: {
                    text: osna_tools_admin.i18n.use_image
                }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();

                preview.html('<img src="' + attachment.url + '" alt="" style="max-width: 100%; max-height: 150px;">');
                input.val(attachment.id);
                removeButton.show();
            });

            frame.open();
        });

        // Remove image
        $(document).on('click', '.remove-image', function (e) {
            e.preventDefault();

            var button = $(this);
            var container = button.closest('.image-preview-container');
            var preview = container.find('.image-preview');
            var input = container.find('input[type="hidden"]');

            preview.html('');
            input.val('');
            button.hide();
        });

        // Toggle media fields based on media type
        $(document).on('change', '.media-type-select', function () {
            var mediaType = $(this).val();
            var container = $(this).closest('.slide-container');

            // Hide all media fields
            container.find('.media-field').hide();

            // Show appropriate field based on selected media type
            container.find('.' + mediaType + '-field').show();

            // Clear values from hidden fields to prevent data conflicts
            if (mediaType === 'image') {
                // If switching to image, clear video fields
                container.find('.video-field input').val('');
            } else if (mediaType === 'video') {
                // If switching to video, clear image fields
                container.find('.image-field .image-preview').html('');
                container.find('.image-field input[type="hidden"]').val('');
                container.find('.remove-image').hide();
            }
        });

        // Add slide
        $('#add-slide').on('click', function () {
            var slides = $('#ultimate-slider-slides');
            var index = slides.children().length;
            var template = $('#slide-template').html();

            // Replace placeholder index with actual index
            template = template.replace(/\{\{index\}\}/g, index);

            slides.append(template);

            // Initialize media type select
            slides.find('.slide-container:last-child .media-type-select').trigger('change');
        });

        // Remove slide
        // Remove slide
        $(document).on('click', '.remove-slide', function () {
            if (confirm('Are you sure you want to remove this slide?')) {
                $(this).closest('.slide-container').remove();

                // Reindex slides
                $('#ultimate-slider-slides .slide-container').each(function (index) {
                    var container = $(this);
                    container.attr('data-index', index);
                    container.find('h3').text('Slide ' + (index + 1));

                    // Update all input names and IDs
                    container.find('input, select, textarea, label, div.image-preview-container').each(function () {
                        // Update name attribute
                        var name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/slides\[\d+\]/, 'slides[' + index + ']');
                            $(this).attr('name', name);
                        }

                        // Update id attribute
                        var id = $(this).attr('id');
                        if (id && id.includes('slides[')) {
                            id = id.replace(/slides\[\d+\]/, 'slides[' + index + ']');
                            $(this).attr('id', id);
                        }

                        // Update for attribute on labels
                        var forAttr = $(this).attr('for');
                        if (forAttr && forAttr.includes('slides[')) {
                            forAttr = forAttr.replace(/slides\[\d+\]/, 'slides[' + index + ']');
                            $(this).attr('for', forAttr);
                        }
                    });
                });
            }
        });


        // Apple-like design enhancements
        $('.osna-admin-panel').addClass('bg-white rounded-xl shadow-md p-6 mb-6');
        $('.osna-field-group').addClass('mb-4');
        $('.osna-field-group label').addClass('block text-sm font-medium text-gray-700 mb-1');
        $('.osna-field-group input[type="text"], .osna-field-group input[type="url"], .osna-field-group input[type="number"], .osna-field-group textarea, .osna-field-group select').addClass('mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50');
        $('.osna-field-group input[type="checkbox"]').addClass('rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2');
        $('.slide-container').addClass('bg-gray-50 rounded-xl p-6 mb-6');
        $('.slide-header').addClass('flex items-center justify-between mb-4');
        $('.slide-header h3').addClass('text-lg font-medium text-gray-900');
        $('.slide-content').addClass('space-y-4');
        $('.button').addClass('inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500');
        $('.button-primary, #add-slide').addClass('text-white bg-blue-600 hover:bg-blue-700');
        $('.remove-slide').addClass('text-white bg-red-600 hover:bg-red-700');
        $('.upload-image').addClass('text-white bg-green-600 hover:bg-green-700');
        $('.remove-image').addClass('text-white bg-gray-600 hover:bg-gray-700');

        // Smooth animations
        $('.slide-container').css({
            'transition': 'all 0.3s ease-in-out'
        });
    });

})(jQuery);
