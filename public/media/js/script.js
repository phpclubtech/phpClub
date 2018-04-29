$(document).ready(function() {
    var popup = (new PopUp()).handle();

    var postPreview = (new PostPreview()).handle();
    
    var arrows = (new Arrows()).handle();
});

function PopUp() {
    this.lightbox = $('#lightbox');
    this.fullsize = $('.fullsize');
    this.loading = $('#loading');
    this.cross = $('.cross');

    this.visible = false;
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
            this.hide();

            this.loading.show(); //The show of #loading doesn't makes PopUp.visible = true

            var src = $(e.target).closest('.file-link').attr('href');
            var ext = this.getExtenstion(src);

            switch (ext) {
                case 'jpg': case 'png': case 'gif':
                    var content = $('<img/>', {class: 'fullsize', src: src});

                    $(content).on('load', function() {
                        this.loading.hide();

                        this.fullsize = $('.fullsize');

                        this.lightbox.append(content);

                        this.lightbox.show();

                        this.visible = true;
                      
                        this.resize(content);
                    }.bind(this));

                    break;

                case 'mp4': case 'webm':
                    var content = $('<video/>', {class: 'fullsize', src: src, controls: 'controls'});
                    
                    $(content).on('canplay', function() {
                        this.loading.hide();

                        this.fullsize = $('.fullsize');

                        this.lightbox.append(content);

                        this.lightbox.show();
                        
                        this.visible = true;
                      
                        this.resize(content);
                    }.bind(this));

                    break;

                default:
                    return;
            }

            e.preventDefault();

            $(content).on('error', function() {
                this.loading.hide();

                var error = $('<div/>', {class: 'fullsize loading-error'}).text('Loading error');

                this.fullsize = $('.fullsize');

                this.lightbox.append(error);

                this.lightbox.show();

                this.visible = true;

                // somehow sizes of .loading-error is wrong
                this.resize(error);
            }.bind(this));
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

    return this;
}

PopUp.prototype.hide = function() {
    this.lightbox.hide();
    this.loading.hide();
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

    return this;
}


function Arrows() {
    this.up = $('#up-nav-arrow');
    this.down = $('#down-nav-arrow');

    this.waiting = false, this.endScrollHandle;

    this.endScrollHandle = setTimeout(function () {
        this.scroll();
    }.bind(this), 333);

    this.down.show();
}

Arrows.prototype.handle = function() {
    $(window).scroll(function() {
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

    return this;
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