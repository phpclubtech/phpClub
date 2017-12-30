(function(){
    window.config = {}; //настройки
    window.thread = {}; //область хранения переменных/данных о треде
    window.config.updatePostsTimeout = 30000; //таймаут в миллисекундах функции загрузки новых постов с сервера
})(); //конфиг

window.Store = {
    get: function(path, default_value) {
        return default_value;
    }
};

//сердце макабы
//система работы с постами
(function(){
    var posts = {};
    //thread        - number, ID треда, в котором находится пост
    //threadPosts   - array, массив номеров постов в треде
    //repliesTo     - array, массив номеров постов, на которые отвечает пост
    //replies       - array, массив номеров постов, которые отвечают на пост
    //rendered      - bool, true, если пост есть на странице
    //ajax          - object, если поста нет на странице, а мы его получили по ajax, то тут будет объект из JSON
    //notfound      - bool, true, если мы запрашивали тред, а пост там не нашли, о котором знали из какого-то источника (ответ на пост?)
    //el            - object, элемент поста jquery. Создаётся и возвращается el() для кэширования
    //preloaded     - number, последний загруженный (не isGhost) num поста
    //downloadCallbacks - array, массив коллбеков, вызываемых после загрузки. Присутствие этого свойства, означает, что тред в процессе загрузки
    //cache         - object, объект кэшированных icon,email,name,trip,subject,comment (если отрендерен и нет ajax)

    var PostQuery = function(num) {
        this.num = parseInt(num);

        return this;
    };

    PostQuery.prototype = {
        //записать тред за постом и отметить, если он отрендерен на странице
        setThread: function(num, rendered) {
            num = parseInt(num);
            if(!posts.hasOwnProperty(this.num)) posts[this.num] = {}; //спорный момент
            var post = posts[this.num];
            var thread = posts[num];
            if(rendered) {
                post.rendered = true;
                if(window.thread.id && (!thread.preloaded || this.num > thread.preloaded)) thread.preloaded = this.num;
            }
            if(post.thread) return this;
            post.thread = num;

            if(!posts.hasOwnProperty(post.thread)) Post(post.thread).setThread(post.thread); //если какой-то пост в треде ссылается на пост из другого треда, о котором мы ничего не знаем
            if(!posts[post.thread].hasOwnProperty('threadPosts')) posts[post.thread].threadPosts = [];

            ////////////////////// сортируем новый пост, ибо кто-то ленивый не может это сделать в сервере и по JSONу они приходят вразброс
            var sorted = posts[post.thread].threadPosts;
            var slen = sorted.length;
            var min = sorted[0];
            var max = sorted[slen-1];

            if(!slen || this.num <= min) {
                sorted.unshift(this.num);
            } else if(this.num >= max) {
                sorted.push(this.num);
            } else {
                for(var i=1;i<slen;i++) {
                    if(this.num < sorted[i]) {
                        sorted.splice(i, 0, this.num);
                        break;
                    }
                }
            }
            //////////////////////

            return this;
        },

        isThread: function() {
            var post = posts[this.num];
            return this.num == post.thread;
        },

        exists: function() { //не забудь про isGhost()
            return posts.hasOwnProperty(this.num);
        },

        //генерациия превью поста
        previewHTML: function() {
            var num = this.num;
            var post = posts[num];
            var html;

            if(post.rendered) {
                if(this.isThread()){
                    html = $('#post-' + num).find('.post').html();
                }else{
                    html = $('#post-' + num).find('.reply').html();
                }
            }else if(post.ajax) {
                html = generatePostBody(post.ajax); //нет времени делать лучше
            }else if(post.notfound){
                html = 'Пост не найден'; //todo underfined в 3 жквери
            }else{
                html = 'DEBUG_UNDEFINED_STATE';
            }

            return html;
        },

        //скачать пост с сервера и вызвать коллбек (вызывает скачивание всего треда с отслеживанием очереди)
        download: function(tNum, pNum, callback) {
            var board = window.thread.board;

            var onsuccess = function( data ) {
                var parse = $.parseHTML(data);

                var thread_el = $(parse).find('.thread');
                var thread_num = thread_el.attr('id').substr(7);

                thread_el.find('.post').each(function(){
                    var post_el = $(this);

                    post_el.find('.post-reply-link').each(function(){
                        var reply_to = $(this).data('num');
                        var reply_num = $(post_el).data('num');

                        var $refmap = $(thread_el).find('#refmap-' + reply_to);

                        var link = '<a ' +
                        'class="post-reply-link" ' +
                        'data-num="' + reply_num + '" ' +
                        'data-thread="' + $(this).data('thread') + '" ' +
                        'href="/' + window.thread.board + '/res/' + thread_num + '.html#' + reply_num + '">' +
                        '&gt;&gt;' + reply_num +
                        '</a> ';

                        $refmap.css('display','block');
                        $refmap.append(link);
                    });
                });


                var post_el = $(parse).find('#post-' + pNum);

                if (post_el.length) {
                    if ($(post_el).attr('class') == 'oppost-wrapper') {
                        post_el = $(post_el).find('.post');
                    } else {
                        post_el = $(post_el).find('.reply');
                    }

                    var html = $(post_el).html();

                    callback(html);
                } else {
                    callback('Невозможно определить id стороннего треда');
                }
            };
            var onerror = function(jqXHR, textStatus) {
                if(jqXHR.status == 404) return callback('Тред не найден');
                if(jqXHR.status == 0) return callback('Браузер отменил запрос (' + textStatus + ')');
                callback({error:'http', errorText:textStatus, errorCode: jqXHR.status});
            };

            //$.ajax( '/makaba/mobile.fcgi?task=get_thread&board=' + board + '&thread=' + thread + '&num=' + from_post, {
            $.ajax( '/' + board + '/res/' + tNum + '.html', {
                dataType: 'html',
                timeout: window.config.updatePostsTimeout,
                success: onsuccess,
                error: onerror
            });

            return this;
        },

        //если на пост из не загруженного треда кто-то ссылался и мы знали о посте только тред и номер
        //потом мы загрузили JSON треда и проверяем нашли ли мы там посты, о которых мы знали только их номер
        //если не нашли, значит таких постов уже нет в треде
        _findRemovedPosts: function() {
            var post = posts[this.num];
            var thread = posts[post.thread];
            if(!thread.preloaded) throw new Error('_findRemovedPosts вызван для !preloaded треда. Ошибка выше в коде');

            var tmp = Post(1);
            $.each( thread.threadPosts, function( key, val ) { //все не найденные отмечаем в notfound
                tmp.num = val; //создавать новый Post в 13 раз медленнее
                if(tmp.isGhost()) tmp._notFound();
            });
        },

        //true, если тред отрендерен на странице
        isRendered: function() {
            var post = posts[this.num];

            return !!post.rendered;
        },

        //true, если мы ничего проме номера треда и номера поста не знаем о посте
        isGhost: function() {
            var post = posts[this.num];

            return !post.hasOwnProperty('ajax') && !post.rendered && !post.notfound;
        },

        
        //штука для разбора какой пост кому отвечает и кто на кого ссылается для эллементов на странице (отрендеренный HTML)
        //отслеживает все известные данные по всем тредам. Запоминает ответы из одного треда в другой
        _processRepliesElements: function(el) { //"системная" функция, не вызыйте её если не знаете что делаете
            var tmp = Post(1);
            var that = this;

            el.find('.post-reply-link').each(function(){
                var this_el = $(this);
                var thread_num = this_el.data('thread');
                var num = this_el.data('num');
                that.addReplyTo(num);
                tmp.num = num;
                tmp.setThread(thread_num).addReply(that.num);
            });

            el.find('a[onmouseover="showPostPreview(event)"][onmouseout="delPostPreview(event)"]').each(function(){
                var this_el = $(this);
                var thread_num = $(this_el).closest('.thread').attr('id').substr(7);
                var num = $(this_el).html().substr(8);
                that.addReplyTo(num);
                tmp.num = num;
                tmp.setThread(thread_num).addReply(that.num);
            });
        },

        //записать в память ответ из текущего поста в какой-то
        addReplyTo: function(reply_to_num) {
            var post = posts[this.num];
            if(!post.hasOwnProperty('repliesTo')) post.repliesTo = [];
            post.repliesTo.push(reply_to_num);

            return this;
        },

        //записать в память ответ какого-то поста на текущий
        addReply: function(reply_num) {
            var post = posts[this.num];
            if(!post.hasOwnProperty('replies')) post.replies = [];
            if(post.replies.indexOf(reply_num) >= 0) return this;

            post.replies.push(reply_num);
            this._renderReply(reply_num);

            return this;
        },

        //сгенерировать HTML ответов на этот пост
        getReplyLinks: function() {
            var post = posts[this.num];
            var text = '';
            if(!post.hasOwnProperty('replies')) return text;

            for(var i=0;i<post.replies.length;i++) {
                text += this._generateReplyLink(post.replies[i]);
            }

            return text;
        },

        _generateReplyLink: function(reply_num) {
            var reply_thread = posts[reply_num].thread;

            return  '<a ' +
                'class="post-reply-link" ' +
                'data-num="' + reply_num + '" ' +
                'data-thread="' + reply_thread + '" ' +
                'href="/' + window.thread.board + '/res/' + reply_thread + '.html#' + reply_num + '">' +
                '&gt;&gt;' + reply_num +
                '</a> ';
        },

        _renderReply: function(reply_num) {
            var post = posts[this.num];

            if(post.rendered) {
                var $refmap = $('#refmap-' + this.num);
                var link = this._generateReplyLink(reply_num);
                $refmap.css('display','block');
                $refmap.append(link);
            }
        },

        //функция для подсветки, но она мало где используется, обычно прописывается класс hiclass
        //для подсветки лучше было бы сделать отдельную систему, она слишком много где используется
        highlight: function() {
            $('.hiclass').removeClass('hiclass');
            $('#post-body-' + this.num).addClass('hiclass');
        }
    };

    window.Post = function(num) {
        num = parseInt(num);
        return (new PostQuery(num));
    };
})();

//система стадий загрузки
//умеет:
//Stage.INSTANT  - исполнение немедленно
//Stage.CONFLOAD - исполнение после загрузки конфига из настрокек борды
//Stage.DOMREADY - исполнение после полной загрузки  HTML
//Stage.ASYNCH   - исполнение немедленно асинхронно
(function(){
    var conf_loaded = false;
    var dom_ready = false;
    var conf_queue = [];
    var dom_queue = [];
    var debug_html = '';
    window.sc_stages = [];
    window.sc_time = 0;

    //это вызывается из шаблона и приносит настройки борды
    window.loadInitialConfig = function(cfg) {
        window.thread.id = cfg.thread_num; //ID текущего треда
        window.thread.board = cfg.board; //имя текущего раздела
        window.likes = !!parseInt(cfg.likes); //включены ли лайки
        window.thread.hideTimeout = 7;  //сколько дней хранить скрытые треды/посты
        window.thread.enable_oekaki = cfg.enable_oekaki;
        window.thread.enable_subject = cfg.enable_subject;
        window.thread.max_files_size = cfg.max_files_size;
        window.thread.twochannel = cfg.twochannel;
        window.thread.max_comment = cfg.max_comment;
        
        conf_loaded = true;
        for(var i=0;i<conf_queue.length;i++) bmark.apply(this, conf_queue[i]);
        conf_queue = [];
    };

    //вывод дебага внизу страницы
    $(function(){
        $('body').append('<div id="bmark_debug" style="display: none">' + debug_html + '</div>');

        dom_ready = true;
        for(var i=0;i<dom_queue.length;i++) bmark.apply(this, dom_queue[i]);
        dom_queue = [];
        debug_html = '';
    });

    //добавление новой страдии загрузки
    //смотри на практике как оно работает
 
    window.Stage = function(name, id, type, cb){
        window.sc_stages.push([id, name]);
        if(id != 'store' && Store.get('debug_disable_stage.' + id, false)) {
            append_debug('<span style="color: #0066FF">skip) ' + name + '</span><br>');
            return;
        }
        if(type == Stage.INSTANT) {
            name = '[I]' + name;
            bmark(name, cb);
        }else if(type == Stage.CONFLOAD) {
            name = '[C]' + name;
            if(conf_loaded) {
                bmark(name, cb);
            }else{
                conf_queue.push([name, cb]);
            }
        }else if(type == Stage.DOMREADY) {
            name = '[D]' + name;
            if(dom_ready) {
                bmark(name, cb);
            }else{
                dom_queue.push([name, cb]);
            }
        }else if(type == Stage.ASYNCH) {
            name = '[A]' + name;
            setTimeout(function(){
                bmark(name, cb);
            },1);
        }
    };

    //benchmark
    var bmark = function(name, cb) {
        var start = (+new Date);

        try {
            cb();
        } catch(err) {
            append_debug('<span style="color:#FF0000">На шаге "' + name + '" произошла ошибка:<br>' +
                '<pre>' +
                err + '\n'+
                err['stack'] +
                '</pre>' +
                '</span>');
            return false;
        }

        var end = (+new Date);
        var delta = end-start;
        window.sc_time += delta;

        append_debug(delta + 'ms) ' + name + '<br>');
    };

    var append_debug = function(text) {
        if(dom_ready) {
            $('#bmark_debug').append(text);
        }else{
            debug_html += text;
        }
    };

    Stage.INSTANT = 1;
    Stage.CONFLOAD = 2;
    Stage.DOMREADY = 3;
    Stage.ASYNCH = 4;
})();

Stage('Init функции',                     'initialisation',     Stage.DOMREADY,      function(){
    //это забрано из юзерспейс функции newakaba fastload()
    dForm = $('#posts-form')[0];
    if(!dForm) return;
    $('#posts-form').append('<div id="ABU-alertbox"></div>');
    if($('#usercode-input')) $('.usercode-input,.qr-usercode-input').val(getCookie('passcode_auth'));
    //
    //if(window.config.makabadmin) fastload();
    window.board = window.location.toString().split('/')[3]; //todo староверство
});

Stage('Наполнение карты постов',                'mapfill',      Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    $('.thread').each(function(){ //наполняем карту после загрузки страницы
        var thread_el = $(this);
        var thread_num = thread_el.attr('id').substr(7);
        var thread_obj = Post(thread_num);
        thread_obj.setThread(thread_num, true); //одинаковые пост и тред делают пост тредом. Жертва оптимизации
        var post_obj = Post(1);

        thread_el.find('.post').each(function(){
            var post_el = $(this);
            post_obj.num = post_el.data('num');
            post_obj.setThread(thread_num, true);
            post_obj._processRepliesElements(post_el);
        });

        if(thread_num == window.thread.id) thread_obj._findRemovedPosts(); //костыли костылики
    });
});

Stage('Превью постов',                          'postpreview',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(($(window).width() < 480 || $(window).height() < 480 )) return; //не запускаем на мобильных
    //==================================================================================================
    // POST PREVIEW BY >>REFLINKS
    //скопировано из старого кода
    var pView;
    var busy = false;

    var delPostPreview = function(e) {
        pView = e.relatedTarget;
        if(!pView) return;

        while(1)
        {
            if(/^preview/.test(pView.id)) break;

            else
            {
                pView = pView.parentNode;

                if(!pView) break;
            }
        }

        setTimeout(function()
        {
            if(!pView) $each($t('div'), function(el)
            {
                if(/^preview/.test(el.id)) $del(el);
            });
            else while(pView.nextSibling) $del(pView.nextSibling);
        }, Store.get('other.hide_post_preview_delay', 800)); 
    };

    var funcPostPreview = function(htm) {
        if(!pView) return;

        pView.innerHTML = htm;
    };

    var showPostPreview = function(e, pNum, tNum) {
        var link = e.target;
        var scrW = document.body.clientWidth || document.documentElement.clientWidth;
        var scrH = window.innerHeight || document.documentElement.clientHeight;
        x = $offset(link, 'offsetLeft') + link.offsetWidth/2;
        y = $offset(link, 'offsetTop');

        if(e.clientY < scrH*0.75) y += link.offsetHeight;

        pView = $new('div',
            {
                'id': 'preview-' + pNum,
                'data-num': pNum,
                'class': 'reply post',
                'html': '<span class="ABU-icn-wait">&nbsp;</span>&nbsp;Загрузка...',
                'style':
                    ('position:absolute; z-index:300; border:1px solid grey; '
                        + (x < scrW/2 ? 'left:' + x : 'right:' + parseInt(scrW - x + 2)) + 'px; '
                        + (e.clientY < scrH*0.75 ? 'top:' + y : 'bottom:' + parseInt(scrH - y - 4)) + 'px')
            },
            {
                'mouseout': delPostPreview,
                'mouseover': function()
                {
                    if(!pView) pView = this;
                }
            });



        var post = Post(pNum);

        if(!post.exists() || post.isGhost()) {
            post.download(tNum, pNum, function(html){
                //if(res.errorText) return funcPostPreview('Ошибка: ' + res.errorText);
                funcPostPreview(html);
                //if(!post.isRendered()) Media.processLinks($('#m' + pNum + ' a'));
            });
        }else{
            funcPostPreview(post.previewHTML());
        }
        $del($id(pView.id)); //удаляет старый бокс поста
        dForm.appendChild(pView);

        //cut
        // if(!post.isRendered()) {
        //     Media.processLinks($('#m' + pNum + ' a')); 
        // }else{
        //     //todo костыль. Надо что-то с этим делать.
        //     var $preview_box = $('#preview-' + pNum);
        //     $preview_box.find('.media-expand-button').remove();
        //     //Media.processLinks($preview_box.find('a')); 
        // }
    };

    var timers = {};
    var clearTimer = function(num){
        if(timers.hasOwnProperty(num)) {
            clearTimeout(timers[num]);
            delete timers[num];
        }
    };
    var timer_ms = Store.get('other.show_post_preview_delay', 50); 
    
    /*if(($(window).width() < 480 || $(window).height() < 480 )) {
        $('#posts-form').on('click', '.post-reply-link', function(e){
            var $el = $(this);
            var num = $el.data('num');
            var thread = $el.data('thread');

            if(timer_ms) {
                timers[num] = setTimeout(function(){
                    clearTimer(num);
                    showPostPreview(e, num, thread);
                }, timer_ms);
            }else{
                showPostPreview(e, num, thread);
            }
        })
        return;
    }*/
    
    $('.posts').on('mouseover', '.post-reply-link', function(e){
        var $el = $(this);
        var num = $el.data('num');
        var thread = $el.data('thread');

        if(timer_ms) {
            timers[num] = setTimeout(function(){
                clearTimer(num);
                showPostPreview(e, num, thread);
            }, timer_ms);
        }else{
            showPostPreview(e, num, thread);
        }
    })
        .on('mouseout', '.post-reply-link', function(e){
            var $el = $(this);
            var num = $el.data('num');
            clearTimer(num);

            delPostPreview(e);
        })
        .on('click', '.post-reply-link', function(){
            var $el = $(this);
            var num = $el.data('num');
            Post(num).highlight();
        });


    $('.posts').on('mouseover', 'a[onmouseover="showPostPreview(event)"][onmouseout="delPostPreview(event)"]', function(e){
        var $el = $(this);
        var num = $el.html().substr(8);
        var thread = $el.closest('.thread').attr('id').substr(7);

        if(timer_ms) {
            timers[num] = setTimeout(function(){
                clearTimer(num);
                showPostPreview(e, num, thread);
            }, timer_ms);
        }else{
            showPostPreview(e, num, thread);
        }
    })
        .on('mouseout', 'a[onmouseover="showPostPreview(event)"][onmouseout="delPostPreview(event)"]', function(e){
            var $el = $(this);
            var num = $el.data('num');
            clearTimer(num);

            delPostPreview(e);
        })
        .on('click', 'a[onmouseover="showPostPreview(event)"][onmouseout="delPostPreview(event)"]', function(){
            var $el = $(this);
            var num = $el.data('num');
            Post(num).highlight();
        });
});

Stage('Загрузка системы скруллинга',            'scrollcb',     Stage.INSTANT,      function(){
    window.scrollcb_array = [];
    var timer = 0;
    var win = $(window);

    win.scroll(function(){
        if(timer) clearTimeout(timer);

        timer = setTimeout(function(){
            timer = 0;
            var pos = win.scrollTop();

            for(var i=0;i<window.scrollcb_array.length;i++) window.scrollcb_array[i](pos);
        },100);
    });
});

Stage('Кнопки перемотки страницы',              'scrollbtns',   Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной

    //if(!Store.get('other.scroll_btns',false)) return;

    var border = 300;
    var $up_bnt = $('#up-nav-arrow');
    var $down_bnt = $('#down-nav-arrow');
    var up_visible = false;
    var down_visible = false;

    var up_show = function(){
        if(up_visible) return;
        up_visible = true;
        $up_bnt.css('display','block');
    };
    var up_hide = function(){
        if(!up_visible) return;
        up_visible = false;
        $up_bnt.css('display','none');
    };
    var down_show = function(){
        if(down_visible) return;
        down_visible = true;
        $down_bnt.css('display','block');
    };
    var down_hide = function(){
        if(!down_visible) return;
        down_visible = false;
        $down_bnt.css('display','none');
    };

    window.scrollcb_array.push(function(scroll_top){
        if(scroll_top > border){
            up_show();
        }else{
            up_hide();
            down_show();
            return;
        }
        var max_scroll = $(document).height()-$(window).height();
        var delta = max_scroll - scroll_top;

        if(delta > border){
            down_show();
        }else{
            down_hide();
            up_show();
        }
    });

    $up_bnt.click(function(){
        $(window).scrollTop(0).scroll();
    });

    $down_bnt.click(function(){
        $(window).scrollTop($(document).height()).scroll();
    });
    //добавляет 1000мс в треде на 3500 постов
    //if($(document).height() != $(window).height()) down_show();
    down_show();
});

Stage('Система раскрытия на полный экран',      'screenexpand', Stage.DOMREADY,     function(){
    var $container = $('<div id="fullscreen-container"></div>');
    var $win = $( window );
    //var $controls = $('<div id="fullscreen-container-controls"><i class="fa-thumb-tack fa"></i><i class="fa-times fa"></i></div>');
    var active = false;
    var pinned = false;
    var mouse_on_container = false;
    var img_width, img_height;
    var multiplier = 1;
    var container_mouse_pos_x = 0;
    var container_mouse_pos_y = 0;
    var webm = false;

    var border_offset = 8; //magic number

    $('body').append($container);

    window.fullscreenExpand = function(num, src, thumb_src, image_width, image_height, cloud) {
        abortWebmDownload();
        if(active == num) {
            hide();
            return false;
        }

        var win_width = $win.width();
        var win_height = $win.height();

        img_width = image_width;
        img_height = image_height;
        multiplier = 1;
        active = num;
        webm = src.substr(-5) == '.webm' || src.substr(-4) == '.mp3';
        mp3 = src.substr(-4) == '.mp3';
        mouse_on_container = false;

        $container
            .html(webm?'<video id="html5video" onplay="webmPlayStarted(this)" onvolumechange="webmVolumeChanged(this)" name="media" loop="1" ' + (Store.get('other.webm_vol',false)?'':'muted="1"') + ' controls="" autoplay="" height="100%" width="100%"><source class="video" height="100%" width="100%" type="video/webm" src="' + src + '"></source></video>':'<img src="' + src + '" width="100%" height="100%" />')

            //.append(!cloud?$controls:'')
            .css('top', (((win_height-image_height)/2) - border_offset) + 'px')
            .css('left', (((win_width-image_width)/2) - border_offset) + 'px')
            .css('background-color', (cloud?'transparent':'#555555'))
            .width(image_width)
            .height(!mp3?image_height:'200px')
            .show();
        
        if(image_width > win_width || image_height > win_height) {
            var multiplier_width = Math.floor(win_width/image_width*10)/10;
            var multiplier_height = Math.floor(win_height/image_height*10)/10;
            if(multiplier_width < 0.1) multiplier_width = 0.1;
            if(multiplier_height < 0.1) multiplier_height = 0.1;

            resize(multiplier_width<multiplier_height ? multiplier_width : multiplier_height, true);
        }

        return false;
    };

    var hide = function() {
        abortWebmDownload();
        active = false;
        mouse_on_container = false;
        $container.hide();
        if(webm) {
            $container.html('');
        }
    };

    var resize = function(new_multiplier, center) {
        if(new_multiplier < 0.1) return;
        if(new_multiplier > 5) return;
    
        repos(new_multiplier, center);
        multiplier = new_multiplier;
        $container
            .width(img_width * multiplier)
            .height(img_height * multiplier);
    };

    var repos = function(new_multiplier, center) {
        var scroll_top = $win.scrollTop();
        var scroll_left = $win.scrollLeft();
        var container_offset = $container.offset();
        var x_on_container;
        var y_on_container;
        if(center) {
            x_on_container = img_width/2;
            y_on_container = img_height/2;
        }else{
            x_on_container = (container_mouse_pos_x-container_offset.left+scroll_left);
            y_on_container = (container_mouse_pos_y-container_offset.top+scroll_top);
        }
        var container_top = container_offset.top-scroll_top;
        var container_left = container_offset.left-scroll_left;
        var delta_multiplier = new_multiplier-multiplier;
        var delta_top = delta_multiplier*y_on_container/multiplier;
        var delta_left = delta_multiplier*x_on_container/multiplier;

        $container
            .css('left', (container_left-delta_left) + 'px')
            .css('top', (container_top-delta_top) + 'px');
    };

    $container.mouseover(function(){
        mouse_on_container = true;
    });

    $container.mouseout(function(){
        mouse_on_container = false;
    });

    $container.mousemove(function(e){
        container_mouse_pos_x = e.clientX;
        container_mouse_pos_y = e.clientY;
    });

    //$container.on('mousedown', $controls, function(e) {
    //  if($(e.target).closest('#fullscreen-container-controls').length) {
    //      console.log(e.target);
    //      return false;
    //  } 
    //});
    
    $win.keyup(function(e){
        if(!active) return;
        var move;
        var code = e.keyCode || e.which;

        if(code == 37 || code == 65 || code == 97 || code == 1092) {
            move = -1;
        }else if(code == 39 || code == 68 || code == 100 || code == 1074) {
            move = 1;
        }else if(code == 27) {
            return hide();
        }else{
            return;
        }

        var $images = $('.image-link');
        var active_index = $images.index($('#exlink-' + active));
        var new_index = active_index + move;
        if(new_index < 0) new_index = $images.length-1;
        if(new_index > $images.length-1) new_index = 0;
        var next = $images.eq(new_index);

        next.find('a').click();
    });

    $win.click(function(e){
        if(!active) return;
        if(pinned) return;
        if(e.which != 1) return;
        if($(e.target).closest('.img').length) return;
        //if($(e.target).attr('name') == 'expandfunc') return;
        if($(e.target).closest('#fullscreen-container').length) return;
        hide();
    });

    $win.on((/Firefox/i.test(navigator.userAgent)) ? "DOMMouseScroll" : "mousewheel", function(e){
        if(!active) return;
        if(!mouse_on_container) return;
        e.preventDefault();
        var evt = window.event || e; //equalize event object
        evt = evt.originalEvent ? evt.originalEvent : evt; //convert to originalEvent if possible
        var delta = evt.detail ? evt.detail*(-40) : evt.wheelDelta; //check for detail first, because it is used by Opera and FF

        if(delta > 0) {
            resize(multiplier+0.1);
        }
        else{
            resize(multiplier-0.1);
        }
    });

    draggable($container, {
        click: function(){
            hide(); //todo по клику на вебм не скрывать бы
        },
        mousedown: function(e_x,e_y){
            if(!webm) return;
            var container_top = parseInt($container.css('top'));
            var container_height = $container.height();

            if((container_top+container_height) - e_y < 35) return false;
        }});
});

function $id(id) {
    return document.getElementById(id);
}

function $t(id, root) {
    return (root || document).getElementsByTagName(id);
}

function $each(arr, fn) {
    for(var el, i = 0; el = arr[i++];)
        fn(el);
}

function $attr(el, attr) {
    for(var key in attr) {
        if(key == 'text') {
            el.textContent = attr[key];
            continue;
        }

        if(key == 'value') {
            el.value = attr[key];
            continue;
        }

        if(key == 'html') {
            el.innerHTML = attr[key];
            continue;
        }

        el.setAttribute(key, attr[key]);
    }

    return el;
}

function $event(el, events) {
    for(var key in events) {
        if(!events.hasOwnProperty(key)) continue;
        if(el.addEventListener) {
            el.addEventListener(key, events[key], false);
        }else{
            el.attachEvent(key,events[key]);
        }
    }
}

function $new(tag, attr, events) {
    var el = document.createElement(tag);

    if(attr) $attr(el, attr);

    if(events) $event(el, events);

    return el;
}

function $del(el) {
    if(!el) return;
    if(el.parentNode) el.parentNode.removeChild(el);
}

function $offset(el, xy) {
    var c = 0;

    while(el) {
        c += el[xy];
        el = el.offsetParent;
    }

    return c;
}

//операции с вебм
function abortWebmDownload() {
    var el = $("#html5video");
    if(!el.length) return;

    var video = el.get(0);
    video.pause(0);
    video.src = "";
    video.load();
    el.remove();
}

function expand(num, src, thumb_src, n_w, n_h, o_w, o_h, minimize,cloud) {
    var $win = $(window);
    if(Store.get('mobile.dont_expand_images',false) && ($win.width() < 480 || $win.height() < 480)) return; 
    if(!minimize && !window.expand_all_img && Store.get('other.fullscreen_expand',true)) return fullscreenExpand(num, src, thumb_src, n_w, n_h,cloud); 
    
    /*******/
    var element = $('#exlink-' + num).closest('.images');
    if(element.length) {
        if(element.hasClass('images-single')) {
            element.removeClass('images-single');
            element.addClass('images-single-exp');
        }else if(element.hasClass('images-single-exp')) {
            element.addClass('images-single');
            element.removeClass('images-single-exp');
        }
    }
    //todo screen был не так и плох
    var win_width = $win.width();
    var win_height = $win.height();
    var k = n_w/n_h;
    
    if(n_w > win_width || n_h > win_height){
        n_h = win_height - 10;
        n_w = n_w*k;
    } 
    var filetag, parts, ext;
    parts = src.split("/").pop().split(".");
    ext = (parts).length > 1 ? parts.pop() : "";
    if (((ext == 'webm') || (ext == 'mp3')) && n_w > o_w && n_h > o_h) {
        closeWebm = $new('a',
        {
            'href': src,
            'id': 'close-webm-' + num,
            'class': 'close-webm',
            'html': '[Закрыть]',
            'onclick': ' return expand(\'' + num + "\','" + src + "','" + thumb_src + "'," + o_w + ',' + o_h + ',' + n_w + ',' + n_h + ', 1);'
        });
        refElem = $id('title-' + num);
        refElem.parentNode.insertBefore(closeWebm, refElem.nextSibling);
        $('#exlink-' + num).prev().css('width','auto');
        if(ext == 'mp3') {
            filetag = ' <audio controls><source src="' + src + '" type="audio/mpeg"></audio> ';
        } else {
            filetag = '<video id="html5video" onplay="webmPlayStarted(this)" onvolumechange="webmVolumeChanged(this)" controls="" autoplay="" width="' + n_w + '" height="' + n_h + '"' + (Store.get('other.webm_vol',false)?'':'muted="1"') + ' loop="1" name="media"><source src="' + src + '" type="video/webm" class="video" ></video>';
        }
        
    } else {
        if (ext == 'webm') {
            var el = document.getElementById('close-webm-' + num);
            el.parentNode.removeChild(el);
        }
        filetag = '<a href="' + src + '" onClick="return expand(\'' + num + "\','" + src + "','" + thumb_src + "'," +
            o_w + ',' + o_h + ',' + n_w + ',' + n_h + ',' + (minimize?0:1) + ',' + cloud + ');"><img src="' + (!minimize ? src : thumb_src) + '" width="' + n_w + '" height="' + n_h + '" class="img ' + (!minimize ? 'fullsize' : 'preview') +  ((ext=='webm') ? ' webm-file' : '') + '" /></a>';
        if(minimize && Store.get('other.expand_autoscroll', true)) { 
            var post = Post(num);
            var post_el;
            if(post.isRendered()) {
                post_el = post.el();
            }else{
                post_el = $('#preview-' + parseInt(num));
            }

            var doc = $(document);
            var pos = post_el.offset().top;
            var scroll = doc.scrollTop();

            if(scroll > pos) doc.scrollTop(pos);

        }
    }
    $id('exlink-' + num).innerHTML = filetag;
    return false;
}