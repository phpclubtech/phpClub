$(document).ready(function() {
    var api = new API();

    var postPreview = (new PostPreview(api)).handle();

    var popup = (new PopUp()).handle();
    
    var arrows = (new Arrows()).handle();
});

function API() {

}

API.prototype.getPost= function(id) {
    var promise = $.get({
      url: '/api/board/get/message/' + id + '/',
      dataType: 'json'
    });

    return promise;
}

API.prototype.handleError = function(jqXHR, textStatus) {
    console.log(jqXHR.status, textStatus);
}


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
                        this.show(content);
                    }.bind(this));

                    break;

                case 'mp4': case 'webm':
                    var content = $('<video/>', {class: 'fullsize', src: src, controls: 'controls'});
                    
                    $(content).on('canplay', function() {
                        this.show(content);
                    }.bind(this));

                    break;

                default:
                    return;
            }

            e.preventDefault();

            $(content).on('error', function() {
                // somehow sizes of .loading-error is wrong
                var error = $('<div/>', {class: 'fullsize loading-error'}).text('Loading error');

                this.show(error);
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

PopUp.prototype.show = function(content) {
    this.loading.hide();

    this.fullsize = $('.fullsize');

    this.lightbox.append(content);

    this.lightbox.show();
    
    this.visible = true;
    
    this.resize(content);
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


function PostPreview(api) {
    this.api = api;
}

PostPreview.prototype.handle = function() {
    var that = this;

    $(document).on('mouseover', '.post-reply-link', function() {
        var id = $(this).data('num');

        var post = $('.post[data-id="' + id +'"]');

        if (post.length != 0) {
            var clone = post.clone();

            that.render(this, clone);
        } else {
            that.api.getPost(id).then(
                function(data) {
                    data['data']['dateFormatted'] = data['data']['dateFormatted'];

                    var template = $('#post-template').html();
                    var html = ejs.render(template, {post: data['data']});

                    var preview = $(html);

                    that.render(this, preview);
                }.bind(this),

                function(jqXHR, textStatus) {
                    var error = $('<div/>', {class: 'loading-error'}).text('Loading error');

                    var template = error.prop('outerHTML');
                    var html = ejs.render(template, {});

                    var preview = $(html);

                    that.render(this, preview);

                    that.api.handleError(jqXHR, textStatus);
                }.bind(this)
            );
        }
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

PostPreview.prototype.render = function(replyLink, preview) {
    var offset = $(replyLink).offset();
    var width = $(replyLink).width();
    var height = $(replyLink).height();

    preview.addClass('post-preview');

    if (preview.hasClass('op-post')) {
        preview.removeClass('op-post');
    }

    preview.css('top', offset.top + height);
    preview.css('left', offset.left + width / 2);

    $('body').append(preview);
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