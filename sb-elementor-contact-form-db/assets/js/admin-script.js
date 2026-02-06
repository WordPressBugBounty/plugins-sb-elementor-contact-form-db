jQuery(document).ready(function ($) {

    function handleTermLink() {
        const termsLinks = document.querySelectorAll('.fdbgp-ccpw-see-terms');
        const termsBox = document.getElementById('termsBox');

        termsLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (termsBox) {
                    // Toggle display using plain JavaScript
                    const isVisible = termsBox.style.display === 'block';
                    termsBox.style.display = isVisible ? 'none' : 'block';
                    link.innerHTML = !isVisible ? 'Hide Terms' : 'See terms';
                }
            });
        });
    }

    handleTermLink();

    jQuery('.copy-btn').click(function (e) {
        e.preventDefault();

        var target = $(this).data('clipboard-target');
        var text = $(target).text();
        var $button = $(this);
        var originalText = $button.text();

        // Function to show feedback
        function showCopiedFeedback() {
            $button.text('Copied!');
            $button.addClass('button-primary');

            setTimeout(function () {
                $button.text(originalText);
                $button.removeClass('button-primary');
            }, 2000);
        }

        // Check if secure context and navigator.clipboard available
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(showCopiedFeedback)
                .catch(function (err) {
                    console.log('Clipboard API failed, falling back to execCommand:', err);
                    fallbackCopy();
                });
        } else {
            // Fallback for insecure contexts
            fallbackCopy();
        }

        function fallbackCopy() {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();

            try {
                var successful = document.execCommand('copy');
                if (successful) showCopiedFeedback();
            } catch (err) {
                console.log('execCommand copy failed: ', err);
            }

            $temp.remove();
        }
    });


    // Show/hide Generate Token button based on Client ID and Secret
    jQuery('#client_id, #client_secret').on('input', function () {
        var clientId = $('#client_id').val().trim();
        var clientSecret = $('#client_secret').val().trim();
        var $authBtn = $('#authbtn');

        if (clientId && clientSecret && !$('#client_token').val()) {
            $authBtn.show();
        } else {
            $authBtn.hide();
        }
    });

    // Handle plugin install and activate button
    jQuery('.fdbgp-install-active-btn').on('click', function (e) {
        e.preventDefault();
        var $button = jQuery(this);
        var action = $button.data('action');
        var slug = $button.data('slug');
        var init = $button.data('init');
        var $loader = jQuery('#fdbgp-loader');

        if(slug !== 'conditional-fields-for-elementor-form' && slug !== 'conditional-fields-for-elementor-form-pro'){
            return;
        }
        
        $loader.show();

        if (action === 'install') {
            // Install and then activate 
            jQuery.ajax({
                type: 'POST',
                url: fdbgp_plugin_vars.ajaxurl,
                data: {
                    action: 'fdbgp_plugin_install',
                    slug: slug,
                    _ajax_nonce: fdbgp_plugin_vars.installNonce
                },
                success: function (res) {
                    if (res.success) {
                        // Activate after install
                        activatePlugin(init);
                    } else {
                        alert('Installation failed. Please try to install manually.');
                        $loader.hide();
                    }
                },
                error: function () {
                    alert('Installation error. Please try to install manually.');
                    $loader.hide();
                }
            });
        } else if (action === 'activate') {
            activatePlugin(init);
        }

        function activatePlugin(pluginInit) {
            jQuery.ajax({
                type: 'POST',
                url: fdbgp_plugin_vars.ajaxurl,
                data: {
                    action: 'fdbgp_plugin_activate',
                    init: pluginInit,
                    security: fdbgp_plugin_vars.nonce
                },
                success: function (res) {
                    if (res.success) {
                        window.location.reload();
                    } else {
                        alert('Activation failed: ' + (res.data ? res.data.message : 'Unknown error'));
                        $loader.hide();
                    }
                },
                error: function () {
                    alert('Activation error. Please try to activate manually.');
                    $loader.hide();
                }
            });
        }
    });

})