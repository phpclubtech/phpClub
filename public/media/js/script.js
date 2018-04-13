$(document).ready(function() {
    $(document).on('mouseover', '.post-reply-link', function() {
        var id = $(this).data('num');

        var post = $('.post[data-id="' + id +'"]');

        var clone = post.clone();

        var offset = $(this).offset();
        var width = $(this).width();
        var height = $(this).height();

        clone.addClass('post-preview');
        clone.css('top', offset.top + height);
        clone.css('left', offset.left + width / 2);

        $('body').append(clone);
    });

    $(document).on('mouseleave', '.post-preview', function(e) {
        if ($('.post-preview').last().is($(this))) {
            $(this).remove();
        }

        if ($(e.relatedTarget).closest('.post-preview').length == 0) {
            $('.post-preview').remove();
        }
    });


    var lightbox = $('#lightbox');
    var a = $('.file-link');

    $(window).click(function(e) {
        var fullsize = $('.fullsize');

        if ($(a).has(e.target).length == 0 && !$(e.target).is('.file-link')) {
            if (!$(e.target).is('.fullsize')) {
                hideLightbox(lightbox, fullsize);
            }
        } else {
            fullsize.remove();

            var src = $(e.target).closest(a).attr('href');
            var ext = getExtenstion(src);

            switch (ext) {
                case 'jpg': case 'png': case 'gif':
                    var content = $('<img/>', {class: 'fullsize', src: src});

                    $(content).on('load', function() {
                        centerContent(content, lightbox);
                    });

                    break;

                case 'mp4': case 'webm':
                    var content = $('<video/>', {class: 'fullsize', src: src, controls: 'controls'});
                    
                    $(content)[0].oncanplay = function() {
                        centerContent(content, lightbox);
                    };

                    break;

                default:
                    return;
            }

            e.preventDefault();

            lightbox.append(content);
            lightbox.show();
        }
    });

    var cross = $('.cross');

    lightbox.hover(
        function() {
            cross.show();
        },

        function() {
            cross.hide();
        }
    );

    cross.click(function() {
        var fullsize = $('.fullsize');

        hideLightbox(lightbox, fullsize);
    });

    $(document).keyup(function(e) {
         if (e.keyCode == 27) {
            if (lightbox.css('display') == 'block') {                
                var fullsize = $('.fullsize');

                hideLightbox(lightbox, fullsize);
            }
        }
    });

    function getExtenstion(src) {
        return src.split('.').pop().toLowerCase();
    }

    function centerContent(content, lightbox) {
        var width = $(window).width();
        var cWidth = $(content).width();

        var height = $(window).height();
        var cHeight = $(content).height();

        var top = (height - cHeight) / 2;
        var left = (width - cWidth) / 2;

        lightbox.css('top', top);
        lightbox.css('left', left);
    }

    function hideLightbox(lightbox, fullsize) {
        lightbox.hide();
        fullsize.remove();
    }


    var up = $('#up-nav-arrow');
    var down = $('#down-nav-arrow');

    down.show();

    $(window).scroll(function() {
        var dHeight = $(document).height();
        var wHeight = $(window).height()

        var scroll = $(this).scrollTop();

        if (scroll > wHeight) {
            up.show();
        } else {
            up.hide();
        }

        if (scroll + wHeight < dHeight - wHeight) {
            down.show();
        } else {
            down.hide();
        }
    });

    up.click(function() {
        $(window).scrollTop(0);
    });

    down.click(function() {
        var height = $(document).height();

        $(window).scrollTop(height);
    });
});

