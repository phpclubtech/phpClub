$(document).ready(function() {
    var popup = new PopUp();
    var postPreview = new PostPreview();
    var arrows = new Arrows();
});

function PopUp() {
    this.lightbox = $('#lightbox');
    this.fullsize = $('.fullsize');
    this.cross = $('.cross');

    this.visible = false;

    this.handle();
}

PopUp.prototype.handle = function() {
    $(window).click(function(e) {
        this.fullsize = $('.fullsize');

        if ($(e.target).closest('.file-link').length == 0) {
            if (!$(e.target).is('.fullsize')) {
                if (this.visible) {
                    this.hide();
                }
            }
        } else {
            this.fullsize.remove();

            var src = $(e.target).closest('.file-link').attr('href');
            var ext = this.getExtenstion(src);

            switch (ext) {
                case 'jpg': case 'png': case 'gif':
                    var content = $('<img/>', {class: 'fullsize', src: src});

                    $(content).on('load', function() {
                        this.fullsize = $('.fullsize');

                        this.lightbox.append(content);
                      
                        this.resize(content);
                    }.bind(this));

                    break;

                case 'mp4': case 'webm':
                    var content = $('<video/>', {class: 'fullsize', src: src, controls: 'controls'});
                    
                    $(content).on('canplay', function() {
                        this.fullsize = $('.fullsize');

                        this.lightbox.append(content);
                      
                        this.resize(content);
                    }.bind(this));

                    break;

                default:
                    return;
            }

            e.preventDefault();

            $(content).on('error', function() {
                this.fullsize.remove();

                var error = $('<div/>', {class: 'fullsize loading-error'}).text('Loading error');

                this.lightbox.append(error);

                // somehow sizes of .loading-error div equals -24px
                this.resize(content);

                this.lightbox.show();
              
                this.visible = true;
            }.bind(this));

            this.lightbox.show();
          
            this.visible = true;
        }
    }.bind(this));

    this.cross.click(function() {
        this.hide();
    }.bind(this));

    $(document).keyup(function(e) {
         if (e.keyCode == 27) {
            if (this.visible) {
                this.hide();
            }
        }
    }.bind(this));
}

PopUp.prototype.hide = function() {
    this.lightbox.hide();
    this.fullsize.remove();

    this.visible = false;
}

PopUp.prototype.getExtenstion = function(src) {
    return src.split('.').pop().toLowerCase();
}

PopUp.prototype.resize = function(content) {
    var maxWidth = $(window).width() / 100 * 66;
    var maxHeight = $(window).height() / 100 * 66;

    var cWidth = content.width();
    var cHeight = content.height();

    if (maxWidth < maxHeight) {
        if (cWidth > maxWidth) {
            content.width(maxWidth);
        }
    } else {
        if (cHeight > maxHeight) {
            content.height(maxHeight);
        }
    }

    var width = content.width();
    var height = content.height();

    this.lightbox.width(width);
    this.lightbox.height(height);
}


function PostPreview() {
    this.handle();
}

PostPreview.prototype.handle = function() {
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
}


function Arrows() {
    this.up = $('#up-nav-arrow');
    this.down = $('#down-nav-arrow');

    this.waiting = false, this.endScrollHandle;

    this.endScrollHandle = setTimeout(function () {
        this.scroll();
    }.bind(this), 333);

    this.down.show();

    this.handle();
}

Arrows.prototype.handle = function() {
    $(window).scroll(function() {
        console.log(this.waiting);

        if (this.waiting) {
            return;
        }

        this.waiting = true;

        clearTimeout(this.endScrollHandle);

        this.scroll();

        setTimeout(function () {
            this.waiting = false;
        }.bind(this), 333);
    }.bind(this));

    //is it fine to add several events in the same function? 
    this.up.click(function() {
        $(window).scrollTop(0);
    });

    this.down.click(function() {
        var height = $(document).height();

        $(window).scrollTop(height);
    });
}

Arrows.prototype.scroll = function() {
    var dHeight = $(document).height();
    var wHeight = $(window).height()

    var scroll = $(window).scrollTop();

    if (scroll > wHeight) {
      this.up.show();
    } else {
      this.up.hide();
    }

    if (scroll + wHeight < dHeight - wHeight) {
        this.down.show();
    } else {
        this.down.hide();
    }
}