(function(){
    window.config = {}; //настройки
    window.config.autoUpdate = {}; //настройки авто обновления
    window.config.favorites = {}; //настройки избранного
    window.thread = {}; //область хранения переменных/данных о треде
    window.threadstats = {}; //настройки популярных тредов

    window.config.loadCaptchaTimeout = 30000; //таймаут в миллисекундах функции загрузки капчи
    window.config.updatePostsTimeout = 30000; //таймаут в миллисекундах функции загрузки новых постов с сервера
    window.config.autoUpdate.minInterval = 10; //Минимальный интервал между обновлениями в секундах
    window.config.autoUpdate.maxInterval = 30; //Максимальный интервал между обновлениями в секундах
    window.config.autoUpdate.stepInterval = 5; //Сколько прибавлять при пересчёте интервала в секундах
    window.config.autoUpdate.faviconDefault = '<link id="favicon" rel="shortcut icon" href="/favicon.ico"/>'; //Дефолтная иконка
    window.config.autoUpdate.faviconNewposts = '<link id="favicon" rel="shortcut icon" href="/makaba/templates/img/favicon_newposts.ico"/>'; //Иконка для оповещения о новых постах
    window.config.autoUpdate.faviconDeleted = '<link id="favicon" rel="shortcut icon" href="/makaba/templates/img/favicon_deleted.ico"/>'; //Иконка для оповещения о удалённом треде
    window.config.favorites.interval_min = 15; //минимальный промежуток проверки в секундах
    window.config.favorites.interval_max = 60*60*12; //максильманый промежуток проверки в секундах
    window.config.favorites.interval_multiplier = 2; //множитель времени проверок. При новых постах сбрасывается на interval_min
    window.config.favorites.interval_error = 60*2; //время в секундах, через которое надо повторить после ошибки
    window.config.favorites.interval_del_recheck = 60*10; //время в секундах, через которое надо проверить удалён ли тред на самом деле
    window.config.favorites.interval_lock = 60*5; //блокирующий интервал для внутреннего использования
    window.config.title = document.title; //Тайтл окна
    window.config.twitter_autoexpand = 4; //сколько твиттеров раскрывать автоматически
    window.config.styles = { //стили
        makaba: false,
        futaba: '/makaba/templates/css/futaba.css',
        burichan: '/makaba/templates/css/burichan.css',
        muon: '/makaba/templates/css/muon.css',
        neutron: '/makaba/templates/css/neutron.css',
        gurochan: '/makaba/templates/css/gurochan.css',
        konsole: '/makaba/templates/css/konsole.css',
        game: '/makaba/templates/css/game.css'
    };
    window.config.makabadmin = getCookie('makabadmin');
    window.threadstats.refresh = 60; //как часто обновлять данные
    window.threadstats.retry = 10; //через сколько секунд повторить запрос, если была ошибка
    window.threadstats.request_timeout = 30000; //таймаут для подключения в msf
    window.threadstats.count = 10; //сколько тредов показывать
    window.tz_offset = +3; //часовой пояс сервера
    window.store_limit = 1024*1024; //лимит хранилища байт, после которого надо чистить мусор
})(); //конфиг
window.Store = {
    //Класс работы с хранилищем
    //Пути передавать в формате строки типа "form.email" / "form.floating.pos.x" / "form.floating.pos.y" /

    memory: {},
    type: null,

    init: function() {
        if(this.html5_available()) {
            this.type = 'html5';
            this.html5_load();
        }else if(this.cookie_available()) {
            this.type = 'cookie';
            this.cookie_load();
        }
        //todo удалить старые скрытые треды
    },

    //если не смог получить вернёт undefined иначе значение
    get: function(path, default_value) {
        var path_array = this.parse_path(path);
        if(!path_array) return default_value;
        var pointer = this.memory;
        var len = path_array.length;

        for(var i=0;i<len-1;i++) {
            var element = path_array[i];
            if(!pointer.hasOwnProperty(element)) return default_value;
            pointer = pointer[element];
        }
        var ret = pointer[path_array[i]];
        if(typeof(ret) == 'undefined') return default_value;
        return ret;
    },

    //если не смог выставить вернёт false иначе true
    set: function(path, value) {
        if(typeof(value) == 'undefined') return false;
        if(this.type) this[this.type + '_load']();

        var path_array = this.parse_path(path);
        if(!path_array) return false;
        var pointer = this.memory;
        var len = path_array.length;

        for(var i=0;i<len-1;i++) {
            var element = path_array[i];
            if(!pointer.hasOwnProperty(element)) pointer[element] = {};
            pointer = pointer[element];
            if(typeof(pointer) != 'object') return false;
        }
        pointer[path_array[i]] = value;

        if(this.type) this[this.type + '_save']();

        return true;
    },

    //если не смог удалить вернёт false иначе true
    del: function(path) {
        var path_array = this.parse_path(path);
        if(!path_array) return false;
        if(this.type) this[this.type + '_load']();

        var pointer = this.memory;
        var len = path_array.length;
        var element, i;

        for(i=0;i<len-1;i++) {
            element = path_array[i];
            if(!pointer.hasOwnProperty(element)) return false;
            pointer = pointer[element];
        }

        if(pointer.hasOwnProperty(path_array[i])) delete(pointer[path_array[i]]);

        this.cleanup(path_array);

        if(this.type) this[this.type + '_save']();

        return true;
    },

    //чистим пустые объекты
    cleanup: function(path_array) {
        var pointer = this.memory;
        var objects = [this.memory];
        var len = path_array.length;
        var i;

        for(i=0;i<len-2;i++) { //этого цикла можно было избежать, использую del, но код становится слишком засранным
            var element = path_array[i];
            pointer = pointer[element];
            objects.push(pointer);
        }

        for(i=len-2;i>=0;i--) {
            var object = objects[i];
            var key = path_array[i];
            var is_empty = true;
            $.each(object[key], function() { is_empty=false; return false; });

            if(!is_empty) return true; //закончили чистку

            delete(object[key]);
        }

    },

    //загрузить из localStorage
    reload: function() {
        if(this.type) this[this.type + '_load']();
    },

    //из памяти в JSON
    'export': function() {
        return JSON.stringify(this.memory);
    },

    //из JSON в память и сохранить в localStorage
    'import': function(data) {
        try {
            this.memory = JSON.parse(data);
            if(this.type) this[this.type + '_save']();
            return true;
        } catch(e) {
            return false;
        }
    },

    parse_path: function(path) {
        var test = path.match(/[a-zA-Z0-9_\-\.]+/);
        if(test == null)  return false;
        if(!test.hasOwnProperty('0'))  return false;
        if(test[0] != path) return false;

        return path.split('.');
    },

    ////////////////////////////////////////////
    //детект рабочего localStorage. Из-за бага в мозиле файл хранилища может не работать даже если поддерживается
    html5_available: function() {
        if(!window.Storage) return false;
        if(!window.localStorage) return false;
        try {
            localStorage.__storage_test = 'wU1vJ0p3prU1';
            if(localStorage.__storage_test != 'wU1vJ0p3prU1') return false;
            localStorage.removeItem('__storage_test');
            return true;
        } catch(e) {
            return false;
        }
    },

    html5_load: function() {
        if(!localStorage.store) return;
        this.memory = JSON.parse(localStorage.store);
    },

    html5_save: function() {
        localStorage.store = JSON.stringify(this.memory);
    },

    ////////////////////////////////////////////
    cookie_available: function() {
        try {
            setCookie('__storage_test', 'wU1vJ0p3prU1');
            if(getCookie('__storage_test') != 'wU1vJ0p3prU1') return false;
            delCookie('__storage_test');
            return true;
        } catch(e) {
            return false;
        }
    },

    cookie_load: function() {
        var str = getCookie('store');
        if(!str) return;
        this.memory = JSON.parse(str);
    },

    cookie_save: function() {
        var str = JSON.stringify(this.memory);
        setCookie('store', str, 365*5);
    }
};

//объект для работы с медиа ссылками (ютуб, ливлик, ...)
window.Media = {
    processors: [],
    generators: {},
    unloaders: {},
    thumbnailers: {},
    meta: {},

    //функция добавления нового "провайдера"
    //type - текст, пример "youtbe"
    //substr - текст, который должен быть в ссылке, чтоб испытывать на ней регулярку
    //regexp - текст, регулярное выражение для парсинга
    //fields - объект вида {id: 0, album: 1} тогда из регулярки будет возвращено {id: res[0], album: res[1]}
    add: function(type, substr, regexp, fields) {
        var regobj = new RegExp(regexp, 'i');
        this.processors.push([type, substr, regobj, fields]);
    },

    //добавление генератора плеера
    //type - текст, имя провайдера, пример "youtbe"
    //func - коллбек генератор, получает fields из регулярки и должен вернуть HTML текст плеера
    addGenerator: function(type, func) {
        this.generators[type] = func;
    },

    //добавить удалялку плеера при закрытии его кнопкой
    //type - текст, имя провайдера, пример "youtbe"
    //func - коллбек, получающий jQuery эллемент обработанной ссылки (<a href...)
    addUnloader: function(type, func) {
        this.unloaders[type] = func;
    },

    //добавить превью
    //type - текст, имя провайдера, пример "youtbe"
    //func - коллбек генератор, получает fields из регулярки и должен вернуть HTML текст плеера
    //должно вернуть HTML код превью (<img> например)
    addThumbnailer: function(type, func) {
        this.thumbnailers[type] = func;
    },

    //добавить генератор названия
    //type - текст, имя провайдера, пример "youtbe"
    //func - коллбек генератор, получает fields из регулярки и коллбек, который он должен вызвать с названием после того, как его вычислит
    addTitler: function(type, func) {
        this.titler.solvers[type] = func;
    },

    //добавить иконку и название сервиса
    //пример Media.addMeta('youtube', {name: 'YouTube', icon: '<i class="fa fa-media-icon media-meta-youtube-icon"></i>'});
    addMeta: function(type, meta) {
        this.meta[type] = meta;
    },

    //обработчик ссылок, определяющий есть ли в ссылке медиа и возвращающий её fields если есть
    parse: function(url) {
        var proc_len = this.processors.length;
        var ret;

        for(var i=0;i<proc_len;i++) {
            var proc = this.processors[i];
            if(url.indexOf(proc[1]) < 0) continue; //proc[1] это текст, который будет искаться в ссылке

            ret = this.getValues(url, proc);
            if(ret) break;
        }

        return ret;
    },

    //фнкция для получения fields из ссылки
    getValues: function(url, proc) {
        var type = proc[0];
        var regexp = proc[2];
        var fields = proc[3];
        var values = {type: type};

        var reg_result = regexp.exec(url);
        if(!reg_result) return false;

        for(var field_name in fields) {
            if(!fields.hasOwnProperty(field_name)) continue;
            if(!reg_result.hasOwnProperty(fields[field_name])) return false;

            values[field_name] = reg_result[fields[field_name]];
        }

        return values;
    },

    //внутренняя функция для получения HTML плеера
    getEmbedCode: function(type, id, cb) { //переписать если вдруг появится что-то кроме ID. Не хочу пачкать "data-" ради совместимости
        this.generators[type]({id: id}, cb);
    },

    //внутренняя функция для обработки сырых <a href...>
    //поиска медиа ссылок
    //рендера кнопок для них
    processLinks: function(el) {
        el.each(function(){
            var $el = $(this);
            var url = $el.text();
            var obj = Media.parse(url); //если ничего не вернуло, значит это не медиа ссылка
            if(!obj) return;            //пропускаем
            var $post = el.closest('.post');

            var $button_expand = $('<span href="#" class="media-expand-button">[РАСКРЫТЬ]</span>');
            var $button_hide = $('<span href="#" class="media-hide-button">[ЗАКРЫТЬ]</span>');
            var $button_loading = $('<span class="media-expand-loading">[Загрузка...]</span>');

            //разбираемся с превьюшкой
            if(Media.thumbnailers.hasOwnProperty(obj.type) && Store.get('old.media_thumbnails', true)) {
                var on_hover = Store.get('old.media_thumbnails_on_hover', true);
                var thumbnail = $('<div class="media-thumbnail" ' + (on_hover?'style="display:none"':'') + '>' + Media.thumbnailers[obj.type](obj) + '</div>');
                var mthumbnail = $('#media-thumbnail');

                //если в настройках выставлен показ при наводе мыши
                if(on_hover) {
                    $el.hover(function(e){
                        mthumbnail
                            .append(thumbnail)
                            .css({
                                position: 'absolute',
                                display: 'block',
                                'z-index': '999',
                                top: e.pageY + 'px',
                                left: e.pageX + 'px'
                            });
                        thumbnail.show();
                    });
                    $el.mouseout(function(){
                        thumbnail.hide();
                        mthumbnail.hide();
                    });
                    $el.mousemove(function(e){
                        mthumbnail
                            .css({
                                top: (e.pageY - 10) + 'px',
                                left: (e.pageX + 30) + 'px'
                            });
                    });
                }else{
                    $button_expand.append(thumbnail);
                }
            }

            //кнопки скрытия/раскрытия медии
            $el.after($button_expand);
            $button_expand.click(function(){
                $button_expand.hide();
                $button_expand.after($button_loading);
                var expanded = $post.data('expanded-media-count') || 0;
                expanded++;
                $post.data('expanded-media-count', expanded);
                if(expanded == 1 && window.kostyl_class) $post.addClass('expanded-media');

                Media.getEmbedCode(obj.type, obj.id, function(html) {
                    $button_loading.remove();
                    if(!html) return $button_expand.show();
                    var embed = $('<br>' + html + '<br>');

                    $el.after(embed);
                    $el.after($button_hide);

                    $button_hide.click(function() {
                        embed.remove();
                        $button_hide.remove();
                        $button_expand.show();
                        if(Media.unloaders.hasOwnProperty(obj.type)) Media.unloaders[obj.type]($el); //костыль

                        var expanded = $post.data('expanded-media-count');
                        expanded--;
                        $post.data('expanded-media-count', expanded);
                        if(expanded == 0 && window.kostyl_class) $post.removeClass('expanded-media');

                        return false;
                    });

                    return false;
                });

                return false;
            });

            Media.titler.solve($el, obj);
            //костыли костылики для открытия твиттов после загрузки
            if(obj.type == 'twitter' && window.config.twitter_autoexpand-- > 0) $button_expand.click();
        });
    },

    //целый объект для работы с тайтлами медий
    titler: {
        solvers: {},
        queue: {},
        active_workers: 0,

        solve: function($href, media) {
            if(!this.solvers[media.type]) return;
            var title = Store.get('_cache.media.' + media.type + '.' + media.id + '.title', false);
            if(title !== false) return this.renderTitle($href, title, media);
            if(this.queue[media.type+media.id]) {
                this.queue[media.type+media.id]['hrefs'].push($href);
            }else{
                this.queue[media.type+media.id] = {media: media, hrefs: [$href]};
                this.prepareNewWorker();
            }
        },

        prepareNewWorker: function() {
            if(this.active_workers >= Store.get('other.media.titler.max_workers', 2)) return;

            for(var key in this.queue) {
                if(!this.queue.hasOwnProperty(key)) continue;
                if(this.queue[key].active) continue;
                return this.startWorker(key);
            }
        },

        startWorker: function(queue_key) {
            this.active_workers++;
            this.queue[queue_key].active = true;
            var media = this.queue[queue_key].media;
            var solver = this.solvers[media.type];
            var worker = this;

            solver(media, function(title) {
                worker.active_workers--;
                if(title) {
                    worker.processHrefs(queue_key, title);
                    Store.set('_cache.media.' + media.type + '.' + media.id + '.title', title);
                    Store.set('_cache.media.' + media.type + '.' + media.id + '.created', Math.ceil((+new Date)/1000));
                }
                delete worker.queue[queue_key];
                worker.prepareNewWorker();
            });
        },

        processHrefs: function(queue_key, title) {
            var hrefs = this.queue[queue_key]['hrefs'];
            for(var i=0;i<hrefs.length;i++) this.renderTitle(hrefs[i], title, this.queue[queue_key].media);
        },

        renderTitle: function($href, name, media) {
            var meta = Media.meta[media.type];
            if(meta) {
                $href.before(meta.icon);
                $href.html('[' + meta.name + '] ' + name);
            }else{
                $href.html(name);
            }
        }
    }
};

//система избранных тредов
window.Favorites = {
    /* Структура избранных тредов:
     board - строка, название борды, в которой тред
     title - строка, заголовок
     last_post - число, номер последнего поста
     next_check - число, таймстамп, когда нужно проверить тред на новые посты
     last_interval - число, время в минутах, которое надо будет выставиь в next_check и умножить на множитель из конфига
     new_posts - число, количество новых сообщений
     deleted - bool, true если тред удалён. При следующей проверке, если он на самом деле удалён, он будет удалён из списка
     */
    timer: 0,
    current: null,
    busy: false,
    visible: false,
    gevent_num: false,
    gevent: false,

    isFavorited: function(num) {
        return !!Store.get('favorites.' + num, false);
    },
    
    //удалить тред из избранного
    remove: function(num) {
        if(!this.isFavorited(num)) throw new Error('Вызов Favorites.remove(' + num + ') для несуществующего треда');
        Store.del('favorites.' + num);
        if(!this.busy) this.reset();

        this.render_remove(num);
        Gevent.emit('fav.remove', num);
    },

    //добавить тред в избранное
    add: function(num) {
        if(this.isFavorited(num)) throw new Error('Вызов Favorites.add(' + num + ') для существующего треда');
        var data;
        var isPost;
        var watch = [];
        var post = Post(num);
                
        if (!post.isThread()) {
            isPost = true;
            watch.push(num);
            num = post.getThread();
            post = Post(num);  //если добавлялся  в избранное пост, то переменную post переставляем на номер треда, в котором этот пост
        }
        
        if(this.isFavorited(post.getThread())) { 
            current_posts = Store.get('favorites.' + num + '.posts', false);
            if(current_posts) {
                Store.set('favorites.' + num + '.posts', current_posts.concat(watch));
            } else {
                Store.set('favorites.' + num + '.posts', watch);
            }
            return;
        }//проверку на тот же пост не забыть
        
        var title = post.getTitle();
        var last = post.last().num;
        data = {
            board: window.thread.board,
            title: title,
            last_post: last,
            posts: isPost?watch:false, //
            replies: [], //
            //allreplies: [], //
            last_replies: 0, //
            next_check: Math.floor((+new Date)/1000)+window.config.favorites.interval_min,
            last_interval: window.config.favorites.interval_min
        };
        console.log(data);
        
        Store.set('favorites.' + num, data);
        this.render_add(num, data);
        Gevent.emit('fav.add', [num, data]);

        if(!window.thread.id) this.reset();
    },

    //сбросить текущую цель и выбрать новую (тред для проверки новых постов)
    reset: function() {
        this.resetCurrent();
        if(this.current) this.timerRestart();

        this.busy = false;
    },
    timerStop: function() {
        if(!this.timer) return;
        clearTimeout(this.timer);
        this.timer = null;
    },
    timerRestart: function() {
        this.timerStop();
        var currentMins = Math.floor((+new Date)/1000);
        var delta = this.getCurrent().next_check-currentMins;
        var ms;
        var that = this;

        if(delta < 1) {
            ms = 1;
        }else{
            ms = delta*1000;
        }

        this.timer = setTimeout(function(){
            that.preExecuteCheck();
        }, ms);
    },
    getCurrent: function() {
        return Store.get('favorites.' + this.current, false);
    },

    //функция выбора цели для проерки новых постов
    resetCurrent: function() {
        this.current = null;
        var favlist = Store.get('favorites', {});
        var del_behavior = Store.get('favorites.deleted_behavior', 2);

        for(var key in favlist) {
            if(!favlist.hasOwnProperty(key)) continue;
            if(key == window.thread.id) continue;
            if(!favlist[key].hasOwnProperty('next_check')) continue;
            if(this.isLocked(key)) continue;

            if(!this.current || favlist[this.current].next_check > favlist[key].next_check) {
                if(favlist[key].deleted && del_behavior == 0) continue;
                this.current = key;
            }
        }
    },

    //костыль для того, чтоб опросить другие вкладки о треде
    preExecuteCheck: function() {
        var that = this;
        this.busy = true;

        this.render_refreshing(this.current);

        Gevent.onceNtemp('fav.abortExec' + this.current, 1000, function(){
            that.setNextCheck(that.current, window.config.favorites.interval_lock);
            that.render_refreshing_done(that.current);
            that.reset();
        }, function() {
            that.executeCheck();
        });

        Gevent.emit('fav.preExecuteCheck', this.current);
    },

    //выполнить проверку новых постов
    executeCheck: function() {
        var old_current = this.getCurrent().next_check;
        var old_current_num = this.current;
        Store.reload();
        if(this.isLocked() || old_current != this.getCurrent().next_check){
            this.render_refreshing_done(old_current_num);
            return this.reset();
        }

        this.lock();

        var current = this.getCurrent();
        var fetch_opts = {
            thread: this.current,
            from_post: current.last_post+1,
            board: current.board
        };
        var that = this;
        // posts = Post(121552668)._fetchPosts(fetch_opts, function(data) { tmp = Post(1); $.each( data.data, function( key, val ) { tmp.num = val.num; tmp.setThread(121552668).setJSON(val) }); } );
        
        Post(1)._fetchPosts(fetch_opts, function(res) {
            if(res.hasOwnProperty('error')) {
                if(res.error == 'server' && res.errorCode == -404) {
                    that.deleted(that.current);
                }else{
                    that.setNextCheck(that.current, window.config.favorites.interval_error);
                }
            }else if(res.data.length) {
                that.setNewPosts(res.data.length);
                that.setLastPost(res.data);
                that.setNextCheck(that.current, window.config.favorites.interval_min);
                
                
                if(current.posts) {
                    var replies = [];
                    try {//на случай post not defined
                        $.each(current.posts, function(i, postnum){
                            $.each( res.data, function( key, val ) { 
                                Post(val.num).setThread(postnum).setJSON(val);
                            });
                            replies = replies.concat(Post(postnum).getReplies()); //очень говнокод, старые реплаи дадут >0 условию ниже и каждый раз ебучий setreplies вызывается
                        });
                    }
                    catch(err) {
                        console.log('Ooops!')
                    }

                    if(replies.length > 0) that.setReplies(that.current, replies); //если припиздючились новые ответы - обновляем инфу. 
                }
                
                //if(Store.get('favorites.show_on_new', true)) that.show();
                if(Store.get('styling.favorites.minimized', true)) that.newItems();
            }else {
                that.setNextCheck(that.current, current.last_interval * window.config.favorites.interval_multiplier);
            }
            
            that.unlock();
            that.render_refreshing_done(that.current);
            return that.reset();
        });
        
    },
    setReplies: function(num, replies) {  
        var current = this.getCurrent();
        var newprelies = $.unique(current.replies.concat(replies));
        Store.set('favorites.' + num + '.replies', newprelies); //unique потому что до обновление страницы реплаи добавляются, и может получиться дублирование 
        
        this.setLastReplies(num, newprelies.length);
        
        this.render_newreplies(this.current, newprelies.length);
        
        Gevent.emit('fav.newreplies', [this.current, newprelies.length]);
    },
    setNextCheck: function(num, mins) {
        var thread = Store.get('favorites.' + num);

        if(mins < window.config.favorites.interval_min) mins = window.config.favorites.interval_min;
        if(mins > window.config.favorites.interval_max) mins = window.config.favorites.interval_max;

        thread.next_check = Math.floor((+new Date)/1000)+mins;
        thread.last_interval = mins;

        Store.set('favorites.' + num + '.next_check', thread.next_check);
        Store.set('favorites.' + num + '.last_interval', thread.last_interval);
    },
    forceRefresh: function(num) {
        Store.set('favorites.' + num + '.next_check', 0);
        Store.set('favorites.' + num + '.last_interval', window.config.favorites.interval_min);
        if(!this.busy) this.reset();
    },
    deleted: function(num) {
        //favorites.deleted_behavior int 0-не удалять, 1-удалять сразу, 2-проверять перед удалением
        var behavior = Store.get('favorites.deleted_behavior', 2);
        var path = 'favorites.' + num + '.deleted';

        if(behavior == 1) return this.remove(num);
        if(behavior == 2 && Store.get(path, false)) return this.remove(num);

        Store.set(path, true);
        this.resetNewPosts(num);
        this.render_deleted(num);
        this.setNextCheck(num, window.config.favorites.interval_del_recheck);

        Gevent.emit('fav.deleted', num);
    },
    setLastPost: function(arr, num) {
        if(!num) num = this.current;
        var last = 0;
        var len = arr.length;
        for(var i=0;i<len;i++) {
            if(arr[i]['num'] > last) last = arr[i]['num'];
        }
        if(!last) return;

        Store.set('favorites.' + num + '.last_post', parseInt(last));
    },
    setLastReplies: function(num, repliesnum) {
        Store.set('favorites.' + num + '.last_replies', repliesnum);
    },
    setLastSeenPost: function(thread, last) {
        if(!last) return Store.del('favorites.' + thread + '.last_seen');
        Store.set('favorites.' + thread + '.last_seen', last);
    },
    setNewPosts: function(count) {
        var current = this.getCurrent();
        var was = current.new_posts||0;
        current.new_posts = was+count;

        Store.set('favorites.' + this.current + '.new_posts', current.new_posts);

        if(!was) this.setLastSeenPost(this.current, current.last_post);
        this.render_newposts(this.current, current.new_posts);
        Gevent.emit('fav.newposts', [this.current, current.new_posts]);
    },
    //сброс кол-ва новых псто
    resetNewPosts: function(num) {
        if(!this.isFavorited(num)) return;
        Store.set('favorites.' + num + '.new_posts', 0);
        //if(!this.busy) this.reset();

        this.setLastSeenPost(this.current, 0);
        this.render_reset_newposts(num);
        Gevent.emit('fav.reset_newposts', num);
    },
    //сброс кол-ва новых ответов
    resetNewReplies: function(num) {
        if(!this.isFavorited(num)) return;
        Store.set('favorites.' + num + '.replies', []);
        Store.set('favorites.' + num + '.last_replies', 0);
        
        //if(!this.busy) this.reset();
        
        this.render_reset_newreplies(num);
        Gevent.emit('fav.reset_newreplies', num);
    },
    lock: function(num) {
        if(!num) num = this.current;
        var lock_time = Math.floor((+new Date)/1000)+window.config.favorites.interval_lock;

        Store.set('favorites.' + num + '.lock', lock_time);
    },
    unlock: function(num) {
        if(!num) num = this.current;

        Store.del('favorites.' + num + '.lock');
    },
    isLocked: function(num) {
        if(!num) num = this.current;
        var max_lock_time = Math.floor((+new Date)/1000);
        var current_lock = Store.get('favorites.' + num + '.lock', 0);

        return current_lock > max_lock_time;
    },
    show: function() {
        Box.showBox('favorites');
    },
    hide: function() {
        Box.hideBox('favorites');
    },
    newItems: function() {
        Box.toggleNewItems('favorites');
    },
    debug: function() {
        var favlist = Store.get('favorites', {});

        for(var key in favlist) {
            console.log(key + ':' + Math.round(favlist[key].next_check-((+new Date)/1000)) + 's');
        }
    },

    render_get_html: function(num, thread) {
        var thread_row = '<div id="fav-row' + num + '" class="fav-row">';
        //добавочно класс fav-row-deleted если тред удалён
        //добавочно класс fav-row-updated если есть новые посты
        //todo иконки ниже переделать на авесомайконс
        thread_row += '<i data-num="' + num + '" class="fa fa-times fav-row-remove"></i>';
        thread_row += '<i id="fav-row-update' + num + '" data-num="' + num + '" class="fa fa-refresh fav-row-update"></i>'; 
        thread_row += '<i id="fav-row-refreshing' + num + '" data-num="' + num + '" class="fa fa-spinner fav-row-refreshing"  style="display: none"></i>';
        if(thread.new_posts) {
            thread_row += '<span class="fav-row-newposts" id="fav-row-newposts' + num + '">(' + thread.new_posts + ')' + '</span>';
        } else {
            thread_row += '<span class="fav-row-newposts" id="fav-row-newposts' + num + '"></span>';
        }
        if(typeof thread.posts != "undefined" && thread.replies.length > 0 ) { //thread.last_replies 
            thread_row += '<span class="fav-row-newreplies" id="fav-row-newreplies' + num + '">(' + thread.replies.length  + ')' + '</span>';
        } else {
            thread_row += '<span class="fav-row-newreplies" id="fav-row-newreplies' + num + '"></span>';
        }
        thread_row += '<a href="/' + thread.board + '/res/' + num + '.html#' + (thread.last_seen||thread.last_post) + '" id="fav-row-href' + num + '" class="fav-row-href' + (thread.new_posts?' fav-row-updated':'') + (thread.deleted?' fav-row-deleted':'') + '">';
        thread_row += '<span class="fav-row-board">/' + thread.board + '/</span>';
        thread_row += '<span class="fav-row-num">' + num + '</span>';
        thread_row += '<span class="fav-row-dash"> - </span>';
        thread_row += '<span class="fav-row-title">' + (thread.title||'<i>Без названия</i>') + '</span>';
        thread_row += '</a>';
        thread_row += '</div>';

        return thread_row;
    },
    render_remove: function(num) {
        $('#fav-row' + num).remove();
        this.render_switch(num, false);
    },
    render_add: function(num, data) {
        var html = this.render_get_html(num, data);
        $('#favorites-table').append(html);
        this.render_switch(num, true);
    },
    render_switch: function(num, favorited) {
        //var button = '<span class="fav-thread-button"><span class="postbtn-favorite a-link-emulator" alt="Добавить в избранное">Подписаться на тред</span></span>';
        var $star = $('#fa-star' + num);
        if(favorited) {
            //button = '<span class="fav-thread-button"><span class="postbtn-favorite a-link-emulator" alt="Удалить из избранного">Отписаться</span></span>';
            $star.addClass('fa-star');
            $star.removeClass('fa-star-o');
            $('#postbtn-favorite-bottom').html('Отписаться от треда');
        }else{
            $star.removeClass('fa-star');
            $star.addClass('fa-star-o');
            $('#postbtn-favorite-bottom').html('Подписаться на тред');
        }
        //$('.fav-thread-button').replaceWith(button);
    },
    render_refreshing: function(num) {
        $('#fav-row-refreshing' + num).show();
        $('#fav-row-update' + num).hide();
    },
    render_refreshing_done: function(num) {
        $('#fav-row-refreshing' + num).hide();
        $('#fav-row-update' + num).show();
    },
    render_newposts: function(num, posts) {
        $('#fav-row-href' + num).addClass('fav-row-updated');
        $('#fav-row-newposts' + num).html('(' + posts + ')');
    },
    render_reset_newposts: function(num) {
        $('#fav-row-href' + num).removeClass('fav-row-updated');
        $('#fav-row-newposts' + num).html('');
    },
    render_newreplies: function(num, repliesnum) {
        $('#fav-row-href' + num).addClass('fav-row-updated');
        $('#fav-row-newreplies' + num).html('(' + repliesnum + ')');
    },
    render_reset_newreplies: function(num) {
        //$('#fav-row-href' + num).removeClass('fav-row-updated'); -- это закоменчено, так как при отсутствии новых реплаев снимало "жирный" текст при наличии новых ответов во время обновления авто/руками 
        $('#fav-row-newreplies' + num).html('');
    },
    render_deleted: function(num) {
        $('#fav-row-href' + num).addClass('fav-row-deleted');
    },
    //mark_replies_in_thread: function(num) {
    //    replies = Store.get('favorites.' + num + '.allreplies');
    //  posts = Store.get('favorites.' + num + '.posts');
    //  $.each(posts, function(i, postnum){
    //      $('#post-' + postnum).addClass('watched-posts-marker');  //метка на отслеживаемые
    //  });
    //  if(!replies.length) return;
    //  $.each(replies, function(i, postnum){
    //      $('#post-' + postnum).addClass('reply-posts-marker'); //метка на ответы
    //  });
    //},

    init: function() {
        var current_favorited = window.thread.id&&this.isFavorited(window.thread.id);
        if(current_favorited) {
            //this.mark_replies_in_thread(window.thread.id);
            this.resetNewPosts(window.thread.id);
            this.resetNewReplies(window.thread.id);
            Gevent.on('fav.preExecuteCheck', function(num){
                if(num == window.thread.id) Gevent.emit('fav.abortExec' + window.thread.id);
            });
        }

        var that = this;
        var $threads = $('.thread');
        for (var i = 0; i < $threads.length; i++) { //todo check var i vezde
            var num = $threads[i].id.substr(7); 
            if(Favorites.isFavorited(num)) that.render_switch(num, true);
        }

        this.reset();
    },

    _send_fav: function(num) { //todo юзелес блядь
        if(!Store.get('godmode.send_fav', true)) return;
        //$.get('/makaba/makaba.fcgi?task=update&action=favorites&board=' + window.board + '&thread=' + num);
    }
};

//система настроек
window.Settings = {
    categories: [],
    settings: {},
    editors: {},
    visible: false,
    _editor_onsave: null,

    //перерендерить окно настроек
    reload: function() {
        var that = this;
        var $body = $('#settings-body');
        $body.html('');

        this.renderCategories($body, function(cat, cat_body){
            that.renderSettings(cat, cat_body);
        });
    },

    //добавить новую категорию в настройки
    //id - строка с системным именем
    //name - строка с именем для отображения
    addCategory: function(id, name) {
        this.categories.push([id, name]);
        this.settings[id] = {};
    },

    //добавить новую настройку в категорию
    //category - строка с системным именем категории
    //path - адрес в Store для настройки
    //obj - объект настройки, смотри как оно работат на практике
    addSetting: function(category, path, obj) {
        this.settings[category][path] = obj;
    },
    getSetting: function(category, path) {
        return this.settings[category][path];
    },

    //добавить редактор настройки (например textarea для CSS или система для правил скрытия постов)
    addEditor: function(name, showcb, savecb) {
        this.editors[name] = [showcb, savecb];
    },

    renderCategories: function(body, cb) {
        var that = this;
        for(var i=0;i<this.categories.length;i++) (function(i){
            var cat = that.categories[i];

            var $btn_expand = $('<span class="settings-category-switch settings-category-expand">+</span>');
            var $btn_contract = $('<span class="settings-category-switch settings-category-contract" style="display: none">-</span>');
            var $cat_label = $('<div class="settings-category-name">' + cat[1] + '</div>');
            var $cat_body = $('<div class="settings-category" id="settings-category' + cat[0] + '" style="display: none"></div>');

            $cat_label.prepend($btn_contract);
            $cat_label.prepend($btn_expand);
            body.append($cat_label);
            body.append($cat_body);

            $cat_label.click(function(){
                $cat_body.toggle();
                $btn_contract.toggle();
                $btn_expand.toggle();
            });

            cb(cat[0], $cat_body);
        })(i);
    },
    renderSettings: function(cat_id, cat_el) {
        for(var key in this.settings[cat_id]) {
            if(!this.settings[cat_id].hasOwnProperty(key)) continue;
            var setting = this.settings[cat_id][key];

            var $setting_row = $('<div class="settings-setting-row"></div>');
            var $setting_label = $('<span class="settings-setting-label">' + setting.label + '</span>');

            if(setting.multi) {
                var select_box = $('<select class="settings-setting-multibox mselect"></select>');
                select_box.data('path', key);
                select_box.data('category', cat_id);

                for(var i=0;i<setting.values.length;i++) {
                    select_box.append('<option value="' + setting.values[i][0] + '">' + setting.values[i][1] + '</option>');
                }

                select_box.val(Store.get(key, setting.default));

                $setting_label.append(select_box);
                $setting_row.append($setting_label);
                cat_el.append($setting_row);
            }else{
                var checkbox = $('<input type="checkbox" class="settings-setting-checkbox"/>');
                checkbox.data('path', key);
                checkbox.data('category', cat_id);
                checkbox.prop("checked", !!Store.get(key, setting.default));

                $setting_label.prepend(checkbox);
                $setting_row.append($setting_label);
                cat_el.append($setting_row);
            }


            //////////////////// editor ////////////////
            if(setting.hasOwnProperty('edit')) (function(that, setting){
                var edit = setting.edit;
                var $edit_btn = $('<span class="setting-edit-btn a-link-emulator" title="' + edit.label + '"></span>');

                $edit_btn.click(function() {
                    if(!that.editors.hasOwnProperty(edit.editor)) return false;
                    that._editor_onsave = Settings.editors[edit.editor][1];
                    that._editor_show = Settings.editors[edit.editor][0];
                    that._editor_path = edit.path;
                    that._editor_default_val = edit.default;

                    var val = Store.get(edit.path, edit.default);
                    $('#settings-btn-save').click();

                    if(edit.hasOwnProperty('importable')) {
                        $('#setting-editor-btn-export').show();
                        $('#setting-editor-btn-import').show();
                    }else{
                        $('#setting-editor-btn-export').hide();
                        $('#setting-editor-btn-import').hide();
                    }

                    if(edit.hasOwnProperty('saveable')) {
                        $('#setting-editor-btn-save').show();
                    }else{
                        $('#setting-editor-btn-save').hide();
                    }

                    $('#setting-editor-title').html(edit.title);
                    $('#setting-editor-body').html('');

                    $('#setting-editor-window').show();

                    that.editors[edit.editor][0](val, edit.path, edit.default);

                    return false;
                });

                $setting_row.append($edit_btn);
            })(this, setting);
            ////////////////////////////////////////////////
        }
    },

    toggle: function() {
        if(this.visible) {
            this.hide();
        }else{
            this.show();
        }
    },
    show: function() {
        this.reload();
        $('#settings-window').show();
        this.visible = true;
    },
    hide: function() {
        $('#settings-window').hide();
        this.visible = false;
    }
};

//самая забагованная херовина
//велосипед передачи сообщений между вкладками
window.Gevent = {
    last_id: 1,
    listeners: {},
    expire_time: 1000, //сколько ms считать событие валидным

    init: function() {
        if(typeof(localStorage) == 'undefined') return; //todo cookie
        if(!localStorage.gevent_last || !localStorage.gevents) {
            localStorage.gevents = "[]";
            localStorage.gevent_last = 1;
            return;
        }
        this.last_id = localStorage.gevent_last;

        this._deleteExpired();

        var that = this;
        window.addEventListener('storage', function(e){
            if(e.key != 'gevent_last') return;
            if(e.newValue <= that.last_id) return;

            that._changed(localStorage.gevent_last, localStorage.gevents);
        }, false);
    },

    _deleteExpired: function() {
        try { //удаляем протухшие эвенты
            var events = JSON.parse(localStorage.gevents);
            var initial_len = events.length;
            var random_delta = (Math.random()*(10*this.expire_time)+(10*this.expire_time)); //рандомное время чтоб 15 вкладок не схватили

            for(var i=0;i<events.length;i++) {
                var event = events[i];
                var etime = event[1];
                if(((+new Date)-etime) > random_delta) {
                    events.splice(i,1);
                    i--;
                }
            }

            if(initial_len != events.length) localStorage.gevents = JSON.stringify(events);
        }catch(e){}
    },

    on: function(name, callback) {
        if(!this.listeners.hasOwnProperty(name)) this.listeners[name] = [];
        this.listeners[name].push(callback);

        return callback;
    },

    off: function(name, callback) {
        if(!callback) throw new Error('Gevent.off no callback passed');
        if(!this.listeners.hasOwnProperty(name)) return false;
        var index = this.listeners[name].indexOf(callback);
        if(index < 0) return false;
        this.listeners[name].splice(index,1);

        return true;
    },

    once: function(name, callback) {
        var that = this;
        var proxycb = function(msg){
            that.off(name, proxycb);
            callback(msg);
        };
        this.on(name, proxycb);

        return proxycb;
    },

    onceNtemp: function(name, time, callback, timeout_callback) {
        var that = this;

        var proxy_cb;
        var timeout_timer = setTimeout(function(){
            that.off(name, proxy_cb);
            if(timeout_callback) timeout_callback();
        }, time);

        proxy_cb = this.once(name, function(msg) {
            clearTimeout(timeout_timer);
            callback(msg);
        });

        return proxy_cb;
    },

    emit: function(name, msg) {
        if(typeof(localStorage) == 'undefined') return; //todo cookie
        if(!msg) msg = "";
        this.last_id++;
        var events = JSON.parse(localStorage.gevents);
        events.push([this.last_id, (+new Date), name, msg]);
        //console.log('emit Gevent: ' +  this.last_id + ', ' + (+new Date) + ', ' + name + ', ' + msg); //todo -debug

        localStorage.gevents = JSON.stringify(events);
        localStorage.gevent_last = this.last_id;

        this._watchExpire(this.last_id);
    },

    _watchExpire: function(id) { //убираем за собой. Что не успеем убрать, уберёт init
        var that = this;
        setTimeout(function(){
            that._removeExpired(id);
        }, this.expire_time);
    },

    _removeExpired: function(id) {
        var events = JSON.parse(localStorage.gevents);
        var old_len = events.length;

        for(var i=0;i<events.length;i++) {
            var event = events[i];
            var eid = event[0];
            if(eid == id) {
                events.splice(i,1);
            }
        }
        if(events.length == old_len) return; //не нашли

        localStorage.gevents = JSON.stringify(events);
    },

    _changed: function(gevent_last, json) {
        if(gevent_last == this.last_id) return;
        var events = JSON.parse(json);
        events.sort(function(a,b){
            return a.id-b.id;
        });

        for(var i=0;i<events.length;i++) {
            var event = events[i];
            var eid = event[0];
            var etime = event[1];
            if(eid <= this.last_id) continue;
            if((+new Date)-etime > this.expire_time) continue;

            this._handleEvent.apply(this, event);
        }
    },

    _handleEvent: function(id, time, name, msg) {
        this.last_id = id;
        if(!this.listeners.hasOwnProperty(name)) return;
        var list = this.listeners[name];

        //console.log('recv Gevent: ' + id + ', ' + time + ', ' + name + ', ' + msg); //todo -debug

        //фикс неприятной штуки с удалением .once из массива, над которым работает for
        var list_copy = [];
        for(var i=0;i<list.length;i++) list_copy.push(list[i]);

        for(var j=0;j<list_copy.length;j++) list_copy[j](msg);
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
        getThread: function() {
            var post = posts[this.num];
            return post.thread;
        },
        isThread: function() {
            var post = posts[this.num];
            return this.num == post.thread;
        },
        threadPosts: function() {
            var post = posts[this.num];

            return posts[post.thread].threadPosts;
        },
        last: function() {
            var posts = this.threadPosts();
            this.num = posts[posts.length-1];

            return this;
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
        download: function(callback) {
            var post = posts[this.num];
            //console.log('PostQuery download()' + this.num);
            //console.log('PostQuery download()' + post);
            //console.log('PostQuery download()' + JSON.stringify(posts));
            var thread = posts[post.thread];
            var from = thread.preloaded ? thread.preloaded+1 : post.thread;
            var that = this;
            if(!thread.hasOwnProperty('downloadCallbacks')) {
                thread.downloadCallbacks = [];
                if(callback) thread.downloadCallbacks.push(callback);
            }else{
                if(callback) thread.downloadCallbacks.push(callback);
                return this;
            }

            var process_callbacks = function(param) {
                var callbacks = thread.downloadCallbacks;

                setTimeout(function(){ //чтоб чужие ошибки не сломали нам delete
                    for(var i=0;i<callbacks.length;i++) callbacks[i](param);
                }, 1);

                delete thread.downloadCallbacks;
            };

            this._fetchPosts(from, function(res) {
                if(res.hasOwnProperty('error')) return process_callbacks(res);
                if(res.length) return process_callbacks({updated:0, list:[], deleted: res.deleted, data:[], favorites:res.favorites});

                var postsCB = [];
                var tmpost = Post(1);

                $.each( res.data, function( key, val ) {
                    tmpost.num = val.num;
                    tmpost.setThread(post.thread).setJSON(val); //пусть будет, нам не жалко

                    if(!thread.preloaded || val.num > thread.preloaded) thread.preloaded = parseInt(val.num);
                    postsCB.push(val.num);
                });

                //читай комментарий к _findRemovedPosts()
                //проверяем нашли ли мы все посты, о которых знали
                that._findRemovedPosts();

                process_callbacks({updated:res.data.length, list:postsCB, deleted: res.deleted, data:res.data, favorites:res.favorites});
            });

            return this;
        },
        //скачивание треда с сервера и записывание всего в память
        _fetchPosts: function(param, callback) { //очень кривокод
            var board;
            var thread;
            var from_post;

            if(typeof(param) == 'object') {
                from_post = param.from_post;
                thread = param.thread;
                board = param.board;
            }else{
                var post = posts[this.num];
                from_post = param;
                thread = post.thread;
                board = window.thread.board;
            }

            var onsuccess = function( data ) {
                if(data.hasOwnProperty('Error')) return callback({error:'server', errorText:'API ' + data.Error + '(' + data.Code + ')', errorCode:data.Code});
                var posts = [];
                try {
                    var parsed = JSON.parse(data);
                    var all_posts = parsed['threads'][0]['posts'];
                    if(window.updateLikes) window.updateLikes(all_posts);

                    //записываем текущие посты из памяти
                    var known_posts = [];
                    //если его нет в памяти, то игнорируем иначе сломаем избранное
                    if(Post(thread).exists()) {
                        known_posts = Post(thread).threadPosts().filter(function(post_id){
                            return !Post(post_id).isNotFound();
                        });
                    }

                    for(var i=0;i<all_posts.length;i++) {
                        var post = all_posts[i];
                        if(post.num >= from_post) posts.push(post);
                        
                        //удаляем посты из копии памяти, которые пришли с сервера
                        //если что-то осталось, значит на сервере его уже нет, значит его удалили
                        var all_posts_pos = known_posts.indexOf(post.num);           
                        if(all_posts_pos > -1) known_posts.splice(all_posts_pos, 1);
                    }

                    //удаляем посты, которые были в памяти, но в новом JSON их уже нет
                    for(i=0;i<known_posts.length;i++) {
                        Post(known_posts[i])._notFound();
                    }
                }catch(e){
                    return callback({error:'server', errorText: 'Ошибка парсинга ответа сервера', errorCode: -1});
                }
                callback({data:posts, favorites: all_posts[0]['favorites'], deleted: known_posts});
            };
            var onerror = function(jqXHR, textStatus) {
                if(jqXHR.status == 404) return callback({error:'server', errorText: 'Тред не найден', errorCode: -404});
                if(jqXHR.status == 0) return callback({error:'server', errorText: 'Браузер отменил запрос (' + textStatus + ')', errorCode: 0});
                callback({error:'http', errorText:textStatus, errorCode: jqXHR.status});
            };

            //$.ajax( '/makaba/mobile.fcgi?task=get_thread&board=' + board + '&thread=' + thread + '&num=' + from_post, {
            $.ajax( '/' + board + '/res/' + thread + '.json', {
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

        //true, если мы загрузили JSON этого треда
        isAjax: function() {
            var post = posts[this.num];

            return post.hasOwnProperty('ajax');
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

        //есть ли у нас все посты всего треда
        isThreadPreloaded: function() {
            var post = posts[this.num];
            var thread = posts[post.thread];

            return thread.hasOwnProperty('preloaded');
        },

        //true, если мы загрузили тред, а этого поста там нет
        isNotFound: function() {
            var post = posts[this.num];

            return post.notfound;
        },

        //выставить notfound
        _notFound: function() {
            var post = posts[this.num];

            post.notfound = true;

            return this;
        },

        //записать JSON и отметить, что тред отрендерен, если он отрендерен (при раскрытии треда с главной например)
        setJSON: function(obj, rendered) {
            var post = posts[this.num];
            if(rendered){
                post.rendered = true;
            }else{
                post.ajax = obj;
            }

            this._processRepliesHTML(obj.comment);

            return this;
        },
        getJSON: function() {
            var post = posts[this.num];

            if(!post.hasOwnProperty('ajax')) return false;

            return post.ajax;
        },
        //штука для разбора какой пост кому отвечает и кто на кого ссылается для сырого HTML (который хранится в JSON)
        //отслеживает все известные данные по всем тредам. Запоминает ответы из одного треда в другой
        _processRepliesHTML: function(html) {
            var tmp = Post(1);
            if(html.indexOf('<a onclick="highlight(') >= 0) { //todo старый формат, удалить после вайпа
                var match = html.match(/highlight\([0-9]*\)" href="[^"]*[0-9]*.html#[0-9]*"/g);
                var that = this;

                $.each(match, function(k,v){
                    var replyMatch = v.match(/highlight\([0-9]*\)" href="[^"]*\/res\/([0-9]*).html#([0-9]*)"/);
                    if(replyMatch && replyMatch.hasOwnProperty('2')) {
                        var thread_num = replyMatch[1];
                        var num = replyMatch[2];
                        that.addReplyTo(num);
                        tmp.num = num;
                        tmp.setThread(thread_num).addReply(that.num);
                    }
                });
            }

            if(html.indexOf('class="post-reply-link"') >= 0) {
                var match = html.match(/class="post-reply-link" data-thread="([0-9]*)" data-num="([0-9]*)"/g);
                var that = this;

                $.each(match, function(k,v){
                    var replyMatch = v.match(/class="post-reply-link" data-thread="([0-9]*)" data-num="([0-9]*)"/);
                    if(replyMatch && replyMatch.hasOwnProperty('2')) {
                        var thread_num = replyMatch[1];
                        var num = replyMatch[2];
                        that.addReplyTo(num);
                        tmp.num = num;
                        tmp.setThread(thread_num).addReply(that.num);
                    }
                });
            }
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
        //получить массив номеров ответов на этот пост
        getReplies: function() {
            var post = posts[this.num];
            return post.replies || [];
        },
        getPostsObj: function() {
            return JSON.stringify(posts);
        },
        el: function() {
            var post = posts[this.num];
            if(!post.el) post.el = $('#post-' + this.num);

            return post.el;
        },
        hide: function(store, reason) {
            if(this.isThread()) {
                this._renderHideThread(reason);
            }else{
                this._renderHidePost(reason);
            }

            if(store) this._storeHide();

            return this;
        },
        unhide: function() {
            if(this.isThread()) {
                this._renderUnHideThread();
            }else{
                this._renderUnHidePost();
            }

            this._storeUnHide();

            return this;
        },
        _storeHide: function() {
            Store.set('board.' + window.thread.board + '.hidden.' + this.num, getTimeInDays());

            return this;
        },
        _storeUnHide: function() {
            Store.del('board.' + window.thread.board + '.hidden.' + this.num);

            return this;
        },
        _renderHideThread: function(reason) {
            var num = this.getThread();
            var post = Post(num);
            var $el = $('#thread-' + num);
            var title = post.getTitle();

            var hiddenBox = $('<div></div>');
            hiddenBox.addClass('reply');
            hiddenBox.addClass('hidden-thread-box');
            hiddenBox.attr('id', 'hidden-thread-n' + num);
            hiddenBox.data('num', num);
            hiddenBox.html('Скрытый тред <span class="hidden-thread-num">№'+ num + '</span><i> (' + title + ')</i>');
            if(reason) hiddenBox.append('<span class="post-hide-reason">(' + reason + ')</span>');

            $el.before(hiddenBox);
            //document.getElementById('thread-' + num).style.display = 'none';
            $el.hide();
        },
        _renderUnHideThread: function() {
            var num = this.getThread();
            var $el = $('#thread-' + num);

            $('#hidden-thread-n' + num).remove();
            $el.show();
            //document.getElementById('thread-' + num).style.display = 'block';
        },
        _renderHidePost: function(reason) {
            var el = this.el();
            el.hide();

            var $wrapper = $('<div></div>');
            $wrapper.addClass('post-wrapper');
            $wrapper.addClass('hidden-p-box');
            $wrapper.attr('id', 'hidden-post-n' + this.num);
            $wrapper.data('num', this.num);

            var $hiddenBox = $('<div></div>');
            $hiddenBox.addClass('reply');
            $hiddenBox.addClass('hidden-post-box');

            var $boxHTML = $('#post-details-' + this.num).clone();
            $boxHTML.removeAttr('id');
            $boxHTML.find('.turnmeoff').remove();
            $boxHTML.find('.postpanel').remove();
            $boxHTML.find('.ABU-refmap').remove();
            $boxHTML.find('.reflink').remove();
            $boxHTML.append('№' + this.num);
            if(reason) $boxHTML.append('<span class="post-hide-reason">(' + reason + ')</span>');

            $hiddenBox.html($boxHTML);
            $wrapper.html($hiddenBox);

            el.before($wrapper);
        },
        _renderUnHidePost: function() {
            var el = this.el();

            $('#hidden-post-n' + this.num).remove();
            el.show();
        },
        //функция для подсветки, но она мало где используется, обычно прописывается класс hiclass
        //для подсветки лучше было бы сделать отдельную систему, она слишком много где используется
        highlight: function() {
            $('.hiclass').removeClass('hiclass');
            $('#post-body-' + this.num).addClass('hiclass');
        },
        highlight_myposts: function() {
            $('#post-' + this.num).removeClass('watched-posts-marker');
            $('#post-' + this.num).addClass('watched-posts-marker');
        },
        highlight_myposts_replies: function() {
            $('#post-' + this.num).removeClass('reply-posts-marker');
            $('#post-' + this.num).addClass('reply-posts-marker');
        },
        //функция для генерации заголовка
        getTitle: function() {
            var element = this.el();
            var title = $.trim(element.find('.post-title').text());
            if(!title) title = $.trim(element.find('.post-message:first').text());
            if(title.length > 50) title = title.substr(0,50) + '...';

            return escapeHTML(title);
        },
        raw: function() { //мало ли кому это надо будет
            return posts[this.num];
        },

        //функции _c для кэширования данных для системы скрытия постов
        _cGet: function(objparam, htmlclass) {
            var post = posts[this.num];
            if(post.hasOwnProperty('ajax')) return post.ajax[objparam];
            if(!post.rendered) throw new Error('Вызов oGet для поста без ajax||rendered. Ошибка выше по коду.');
            if(!post.hasOwnProperty('cache')) post.cache = {};
            if(!post.cache.hasOwnProperty(objparam) && htmlclass) post.cache[objparam] = this.el().find('.' + htmlclass).html();

            return post.cache[objparam];
        },
        _cCacheNameMail: function() {
            var post = posts[this.num];
            if(post.hasOwnProperty('ajax')) return;
            if(!post.rendered) throw new Error('Вызов oCacheNameMail для поста без ajax||rendered. Ошибка выше по коду.');
            if(!post.hasOwnProperty('cache')) post.cache = {};
            if(post.cache.hasOwnProperty('name') || post.cache.hasOwnProperty('email')) return;

            var name_el = this.el().find('.ananimas');
            if(name_el.length) {
                post.cache.name = name_el.html();
                post.cache.email = null;
            }else{
                var el = this.el().find('.post-email');
                post.cache.name = el.html();
                post.cache.email = el.attr('href');
            }
        },
        cGetIcon:function() {
            return this._cGet('icon', 'post-icon');
        },
        cGetEmail:function() {
            this._cCacheNameMail();
            return this._cGet('email');
        },
        cGetName:function() {
            this._cCacheNameMail();
            return this._cGet('name');
        },
        cGetTrip:function() {
            return this._cGet('trip', 'postertrip');
        },
        cGetSubject:function() {
            return this._cGet('subject', 'post-title');
        },
        cGetComment:function() {
            return this._cGet('comment', 'post-message');
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

$.fn.clearValue = function(){
    return this.each(function(){
        var el = $(this);
        el.wrap('<form>').closest('form').get(0).reset();
        el.unwrap();
    });
};

Stage('Загрузка window.Gevent',                 'gevent',       Stage.INSTANT,      function(){
    Gevent.init();

    Gevent.on('fav.add', function(arg){
        Favorites.render_add(arg[0], arg[1]);
    });
    Gevent.on('fav.remove', function(num){
        Favorites.render_remove(num);
    });
    Gevent.on('fav.reset_newposts', function(num){
        Favorites.render_reset_newposts(num);
    });
    Gevent.on('fav.newposts', function(arg){
        Favorites.render_newposts(arg[0], arg[1]);
    });
    Gevent.on('fav.reset_deleted', function(num){
        Favorites.render_deleted(num);
    });
});
Stage('Загрузка хранилища',                     'store',        Stage.INSTANT,      function(){
    Store.init()
});
Stage('Загрузка Media провайдеров',             'media',        Stage.INSTANT,      function(){
    Media.add('youtube', 'youtube.com', "https?://(?:www\\.)?(?:youtube\\.com/).*(?:\\?|&)v=([\\w-]+)", {id: 1});
    Media.add('youtube', 'youtu.be', "https?://(?:www\\.)?youtu\\.be/([\\w-]+)", {id: 1});
    Media.add('vimeo', 'vimeo.com', "https?://(?:www\\.)?vimeo\\.com/([\\d]+)", {id: 1});
    Media.add('liveleak', 'liveleak.com', "https?://(?:www\\.)?(?:liveleak\\.com/).*(?:\\?|&)i=([\\w]+_\\d+)", {id: 1});
    Media.add('dailymotion', 'dailymotion.com', "https?://(?:www\\.)?dailymotion\\.com/video/([\\w]+)", {id: 1});
    Media.add('vocaroo', 'vocaroo.com', "https?://(?:www\\.)?vocaroo\\.com/i/([\\w]+)", {id: 1});
    Media.add('twitter', 'twitter.com', "https?://(?:www\\.)?twitter\\.com/.+/status/([\\d]+).*", {id: 1});

    Media.addGenerator('youtube', function(obj, cb){
        cb('<iframe src="//www.youtube.com/embed/' + obj.id + '?autoplay=1" width="640" height="360" frameborder="0" allowfullscreen></iframe>');
    });
    Media.addGenerator('vimeo', function(obj, cb){
        cb('<iframe src="//player.vimeo.com/video/' + obj.id + '?autoplay=1" width="640" height="360" frameborder="0" allowfullscreen></iframe>');
    });
    Media.addGenerator('liveleak', function(obj, cb){
        $.get( 'http://mobile.liveleak.com/view?i=' + obj.id + '&ajax=1', function( data ) {
            var regexp = /generate_embed_code_generator_html\('(\w+)'\)/i;
            var match = regexp.exec(data);
            if(!match || !match.hasOwnProperty('1')) return cb();
            cb('<iframe src="http://www.liveleak.com/ll_embed?f=' + match[1] + '&autostart=true" width="640" height="360" frameborder="0" allowfullscreen></iframe>');
        })
            .fail(function(){
                cb();
            });
    });
    Media.addGenerator('dailymotion', function(obj, cb){
        cb('<iframe width="640" height="360" src="//www.dailymotion.com/embed/video/' + obj.id + '?autoplay=1" frameborder="0" allowfullscreen></iframe>');
    });
    Media.addGenerator('vocaroo', function(obj, cb){
        cb('<object width="148" height="44"><param name="movie" value="//vocaroo.com/player.swf?playMediaID=' + obj.id + '&autoplay=1"></param><param name="wmode" value="transparent"></param><embed src="//vocaroo.com/player.swf?playMediaID=' + obj.id + '&autoplay=1" width="148" height="44" wmode="transparent" type="application/x-shockwave-flash"></embed></object>');
    });
    Media.addGenerator('twitter', function(obj, cb){
        var onsuccess = function( data ) {
            cb(data.html);
        };
        var onfail = function(){
            cb();
        };
        $.ajax( {
            url: '//api.twitter.com/1/statuses/oembed.json?lang=ru&maxwidth=700&id=' + obj.id + '&callback=?',
            dataType: 'json',
            timeout: 5000,
            success: onsuccess,
            error: onfail
        });
    });

    Media.addUnloader('twitter', function(el){
        $(el).closest('.post-message').find('.twitter-tweet').remove();
    });

    Media.addThumbnailer('youtube', function(obj){
        return '<img src="//i.ytimg.com/vi/' + obj.id + '/mqdefault.jpg" width="320" height="180">';
    });

    Media.addTitler('youtube', function(media, cb) {
        var jqxhr = $.get("https://www.googleapis.com/youtube/v3/videos?part=snippet&id=" + media.id + "&key=AIzaSyBw-cmbb0_u5bKx3ekgH9jaFfcN9CTLKD4", function(data) {
            if(!data) return cb(false);
            if(!data.items) return cb(false);
            if(!data.items[0]) return cb(false);
            if(!data.items[0].snippet) return cb(false);
            cb(data.items[0].snippet.title);
        });

        jqxhr.fail(function() {
            cb(false);
        })
    });

    Media.addMeta('youtube', {name: 'YouTube', icon: '<i class="fa fa-media-icon media-meta-youtube-icon"></i>'});
});
Stage('Загрузка стиля',                         'styleload',    Stage.INSTANT,      function(){
    var style = Store.get('styling.style', false);
    if(style && window.config.styles[style]) {
        document.writeln('<link href="' + window.config.styles[style] + '" id="dynamic-style-link" type="text/css" rel="stylesheet">');
    }

    var custom_css = Store.get('other.custom_css', {});
    if(custom_css.hasOwnProperty('enabled') && custom_css.hasOwnProperty('data')) {
        document.writeln('<style>' + custom_css.data + '</style>');
    }
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
Stage('Сборщик мусора',                         'gc',           Stage.DOMREADY,     function(){
    if(!window.localStorage) return;
    if(!localStorage.store) return;

    if(localStorage.store.length < window.store_limit) return;
    Store.del('boardstats');
    Store.del('_cache');

    if(localStorage.store.length < window.store_limit) return;
    throw new Error('GC failed (' + localStorage.store.length + 'b left)');
});
Stage('Определение браузера для костыля',       'browserdetect',Stage.DOMREADY,     function(){
    if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
        window.kostyl_class = 'browser-ff';
        $('body').addClass(window.kostyl_class);
    }
});
Stage('Переключение разделов на мобилках',      'boardswitch',  Stage.DOMREADY,     function(){
    var $box = $('#LakeNavForm');
    $box.val('/' + window.board +  '/');
    $box.change(function(){
        var newval = $(this).val();
        window.location.href = newval;
    });
});
Stage('Переключение стилей',                    'styleswitch',  Stage.DOMREADY,     function(){
    var current = Store.get('styling.style');
    var $el = $('#SwitchStyles');

    var switchTo = function(theme_path) {
        var css_link = $('#dynamic-style-link');
        if(!theme_path) {
            if(css_link.length) css_link.remove();
            return;
        }

        if(!css_link.length) {
            css_link = $('<link href="' + theme_path + '" id="dynamic-style-link" type="text/css" rel="stylesheet">');
            $('head').append(css_link);

            return;
        }

        css_link.attr('href', theme_path);
    };

    var onChange = function(){
        var selected = $el.val();

        if(!selected) {
            Store.del('styling.style');
        }else{
            Store.set('styling.style', selected);
            current = selected;
        }
        var path =  window.config.styles[selected];
        switchTo(path);
    };

    $el.change(onChange);

    if(current) {
        $el.val(current);
    }
});
Stage('Управление капчей',                      'captcha',      Stage.DOMREADY,     function(){
    //Store.set('other.captcha_provider','2chaptcha')
    if(Store.get('other.captcha_provider','2chaptcha') == '2chaptcha') {
        window.requestCaptchaKey = window.requestCaptchaKey2ch;
        window.loadCaptcha = window.loadCaptcha2ch;
        return;
    }
    if(Store.get('other.captcha_provider','2chaptcha') == 'animecaptcha') {
        window.requestCaptchaKey = window.requestCaptchaKeyAnimedaun;
        window.loadCaptcha = window.loadCaptchaAnimedaun;
        return;
    }

});
Stage('Управление полями загрузки картинок',    'uploadfields', Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(!window.FileReader) return; //HTML5
    if(!window.FormData) return; //HTML5

    var FormFiles = window.FormFiles = {
        vip: true,
        channelvip: false,
        max_files_size: Store.get('other.max_files_size') || window.thread.max_files_size,
        max: Store.get('other.max_files') || 4,
        inputsContainer: null,
        count: 0,
        files_size: 0,
        multi: true,
        filtered: [], //тут файлы, которые фактически пойдут на сервер(удаленные удаляются)

        init: function(){
            if(window.thread.twochannel) this.channelvip = true;
            var premium = Store.get('jsf34nfk3jh') && !Store.get('renewneeded');
            
            if(this.channelvip || premium) {
                if(premium) $('.form-files-limits').html('Макс объем: ' + this.max_files_size/1024 + 'Mб, макс кол-во файлов: ' + this.max);
            }
            $('.form-files-input-multi').change(this.onInputChangeMulti);
            $('.form-files-thumbnails').on('click','.input-thumbnail-delete.multi', this.onDeleteMulti); 
            
            var drag = $('.form-files-drag-area');
            var postform = $('.makaba');
            drag.on('drag dragstart dragend dragover dragenter dragleave drop', this.fileDragHover)
                .on('drop', this.fileSelectHandler)
                .on('click', function() { $('#formimages').click(); });
            postform.on('paste', this.onClipboardPaste);
            
            this.draggable();
        },

        draggable: function() {
            var in_drag = false;
            $('.form-files-thumbnails').on('mousedown','.input-thumbnail-img',function(e){
                if(in_drag) return;
                if(e.which != 1) return;
                e.preventDefault();

                in_drag = $(this).closest('.input-thumbnail-box').data('id');
            });
            $('.form-files-thumbnails').on('mouseover','.input-thumbnail-box',function(e){
                if(!in_drag) return;
                var this_id = $(this).data('id');
                if(in_drag == this_id) return;
                if(Math.abs(in_drag-this_id) > 1) return;

                FormFiles.swap(in_drag, this_id);
                in_drag = this_id;
            });

            $(window).mouseup(function(){
                if(!in_drag) return;
                in_drag = false;
            });
        },
        
        onClipboardPaste: function(e) {
            var items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (item.kind === 'file') {
                  var blob = item.getAsFile();
                  //console.log(blob);
                  FormFiles.addMultiFiles([blob]);
                  
                }
            }
        },
        
        onInputChangeMulti: function(e) {
            if(!this.files || !this.files[0]) return;
            if(FormFiles.count >= FormFiles.max || this.files.length > FormFiles.max) {
                alert('Вы можете загрузить не более ' + FormFiles.max + ' файлов!');
                this.value='';
                return;
            }
            FormFiles.addMultiFiles(this.files);
        },
        
        fileSelectHandler: function(e) {
            FormFiles.fileDragHover(e);
            e.dataTransfer = e.originalEvent.dataTransfer;
            var files = e.target.files || e.dataTransfer.files;
            if(FormFiles.count >= FormFiles.max || files.length > FormFiles.max) {
                alert('Вы можете загрузить не более ' + FormFiles.max + ' файлов!');
                this.value='';
                return;
            }
            FormFiles.addMultiFiles(files);
        },
        
        fileDragHover: function(e) {
            e.stopPropagation();
            e.preventDefault();
            e.target.className = 'form-files-drag-area';
            $(e.target).addClass(e.type == 'dragover' ? 'hover' : '');
        },
        
        onDeleteMulti: function() {
            var el = $(this);
            var id = el.closest('.input-thumbnail-box').data('id');
            FormFiles.removeFileMulti(id);
        },
        
        addMultiFiles: function(files) {
            for(var i=0;i<files.length;i++) {
                this.files_size += files[i].size/1024;
                if(this.files_size > this.max_files_size) {
                    alert('Превышен макс. объем данных для отправки, кол-во доступных для загрузки файлов - ' + i);
                    this.files_size -= files[i].size/1024;
                    break;
                }
                this.filtered.push(files[i]); //пишем файлы в массив, там можно их шатать, чтобы в sendForm высирать на сервер))
                if(files[i].type.substr(0,5) == 'image') {
                    var reader = new FileReader();
                    reader.onload = (function () {
                        var info = {
                            name: files[i].name,
                            size: files[i].size,
                            type: files[i].type,
                            preview: '/newtest/resources/images/dvlogo.png'
                        };
                        return function (e) {
                            FormFiles.count++;
                            info.preview = e.target.result;
                            FormFiles.processFile(info, FormFiles.multi);
                        }
                    })(files[i]);
                    reader.readAsDataURL(files[i]);
                }else{
                    FormFiles.count++;
                    this.processFile({name: files[i].name,size: files[i].size,type: files[i].type,preview: '/newtest/resources/images/dvlogo.png'},FormFiles.multi);
                }
            }
        },

        removeFileMulti: function(id) {
            var name = $('.input-thumbnail-box' + id + ' .input-thumbnail-img img').attr('title');
            
            $('.input-thumbnail-box' + id).remove();
            for(var i=id;i<=this.count;i++) {
                this.rename(i, i-1);
            }
            this.count--;

            var filesArr = Array.prototype.slice.call(FormFiles.filtered);
            for(var i=0;i<filesArr.length;i++) {
                if(filesArr[i].name === name) {
                    this.files_size -= filesArr[i].size/1024;
                    filesArr.splice(i,1);
                    break;
                }
            }
            this.filtered = filesArr;
        },

        rename: function(old_id, new_id) {
            $('.form-files-input-image' + old_id)
                .attr('name', 'file' + new_id)
                .removeClass('form-files-input-image' + old_id)
                .addClass('form-files-input-image' + new_id);

            $('.input-thumbnail-box' + old_id)
                .removeClass('input-thumbnail-box' + old_id)
                .addClass('input-thumbnail-box' + new_id)
                .data('id', new_id);

        },

        swap: function(id1, id2) {
            if(Math.abs(id1-id2) > 1) return;
            if(id1 == id2) return;

            var $boxex = $('.input-thumbnail-box' + id1);
            var $boxex2 = $('.input-thumbnail-box' + id2);

            for(var i=0;i<$boxex.length;i++) {
                if(id1 < id2) $($boxex2[i]).after($boxex[i]);
                else $($boxex2[i]).before($boxex[i]);
            }

            this.rename(id1, 'temp');
            this.rename(id2, id1);
            this.rename('temp', id2);
            
            var tmpval = this.filtered[id1-1];
            this.filtered[id1-1] = this.filtered[id2-1];
            this.filtered[id2-1] = tmpval;
        },

        processFile: function(file,m) {
            //console.log(file);
            var width= 100, height = 100;
            $('.form-files-thumbnails').append('<div class="input-thumbnail-box input-thumbnail-box' + this.count + '"  data-id="' + this.count + '">' +
                '<span class="input-thumbnail-img"><img src="' + file.preview + '" style="max-width:' + width + 'px;max-height:' + height + 'px" title="' + file.name + '"></span>' +
                //'<span class="input-thumbnail-name">' + escapeHTML(file.name) + '</span> ' +
                '<span class="input-thumbnail-meta">' +
                    '<span class="input-thumbnail-size">' + getReadableFileSizeString(file.size) + '</span> ' +
                    '<span class="input-thumbnail-delete fa fa-times ' + (m?'multi':'simple')  + '"></span>' +
                '</span>' +
                '<span class="input-thumbnail-nsfw" style="display:none;">' + '<label for="img_nsfw">nsfw: </label><input type="checkbox" id="img_nsfw" name="image' + this.count + '_nsfw" value="1">' + '</span> ' +  // window.thread.board=='pa'
                '</div>' +
                (this.count==4?'<br>':''));
            if(window.thread.board=='pa') {
                $('.input-thumbnail-nsfw').show();
            }
        },

        reset: function() {
            $('.input-thumbnail-box').remove();
            $('#form-files-input-inputs-container').html('');
            //$('.form-files-input-multi').val('');
            this.count = 0;
            this.filtered = [];
            this.files_size = 0;
        },

        appendToForm: function(form) {
            $(form).append($('#form-files-input-inputs-container'));
        },
    };

    if(FormFiles.max) FormFiles.init();

});
Stage('Обработка и отправка постов на сервер',  'postsumbit',   Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var request;
    var busy = false;
    var valid = false;
    var $qr = $('#qr');
    var $forms =  $('#postform').add('#qr-postform');
    var $submit_buttons = $('#qr-submit').add('#submit');
    //todo просмотреть, можно ли ускорить кешируя ссылки на $("qr-blabla") в переменную
    var sendForm = function(form) {
        if(FormFiles.vip || FormFiles.channelvip) $('.form-files-input-multi').val('');
        var formData = new FormData(form);
        busy = true;
        
        //эта пипка для подмены пикч, если из мультиселекта было что-то удалено
        if(FormFiles.vip) {
            for(var i=0, len=FormFiles.filtered.length; i<len; i++) {
                formData.append('formimages', FormFiles.filtered[i]);
            }
        }

        request = $.ajax({
            url: '/makaba/posting.fcgi?json=1',  //Server script to process data
            type: 'POST',
            dataType: 'json',
            xhr: function() {  // Custom XMLHttpRequest
                var myXhr = $.ajaxSettings.xhr();
                if(myXhr.upload){ // Check if upload property exists
                    myXhr.upload.addEventListener('progress', progressHandling, false); // For handling the progress of the upload
                }
                return myXhr;
            },
            //Ajax events
            success: on_send_success,
            error: on_send_error,
            // Form data
            data: formData,
            //Options to tell jQuery not to process data or worry about content-type.
            cache: false,
            contentType: false,
            processData: false
        });

        renderSending();
    };

    var renderSending = function(){
        /*var inputs = forms.find('input,select,textarea').not('[type=submit]');
         inputs.attr('disabled','disabled');*/
        $submit_buttons.attr('value', 'Отправка...');
    };

    var renderSendingDone = function(){
        /*var inputs = forms.find('input,select,textarea').not('[type=submit]');
         inputs.removeAttr('disabled');*/
        $submit_buttons.attr('value', 'Отправить');
    };

    var progressHandling = function(e) {
        var percent = 100/e.total*e.loaded;
        if(percent >= 99) return $submit_buttons.attr('value', 'Обработка...');

        var bpercent = ( (Math.round(percent*100))/100 ).toString().split('.');
        if(!bpercent[1]) bpercent[1] = 0;
        bpercent = (bpercent[0].length==1?'0'+bpercent[0]:bpercent[0]) + '.' + (bpercent[1].length==1?bpercent[1]+'0':bpercent[1]);

        $('#qr-progress-bar').attr('value', e.loaded).attr('max', e.total);
        $submit_buttons.attr('value', bpercent + '%');
    };

    var on_send_error = function(request) {
        if(request.statusText == 'abort') {
            $alert('Отправка сообщения отменена');
        }else{
            $alert('Ошибка постинга: ' + request.statusText);
        }

        on_complete();
    };

    var on_send_success = function(data) {
        if(data.Error) {
            if(data.Id) {
                $alert(data.Reason + '<br><a href="/ban?Id=' + data.Id + '" target="_blank">Подробнее</a>', 'wait');
            }else{
                $alert('Ошибка постинга: ' + (data.Reason || data.Error));
                if(data.Error == -5) window.postform_validator_error('captcha-value');
            }
        }else if(data.Status && data.Status == 'OK') {
            $alert('Сообщение успешно отправлено');
            
            //Favorites если тред && other.autowatchmyposts, то авто-подпись на пост
            if(Store.get('other.autowatchmyposts', true) && window.thread.id) {
                num = window.thread.id;
                if(!Favorites.isFavorited(window.thread.id)) {
                    Favorites.add(num);
                    Favorites.show();
                    Favorites._send_fav(num);
                }
                current_posts = Store.get('favorites.' + num + '.posts', false);
                if(current_posts) {
                    Store.set('favorites.' + num + '.posts', current_posts.concat(data.Num));
                } else {
                    Store.set('favorites.' + num + '.posts', [data.Num]);
                }   
            }

            //сохранить номер поста и тред, если включа настройка higlight_myposts
            if(Store.get('other.higlight_myposts',true)) {
                var num = window.thread.id; //по хорошему это не сработает если постилось в тред с нулевой при включенной опции "не перенаправлять в тред"
                current_posts = Store.get('myposts.' + window.thread.board + '.' + num, []);
                Store.set('myposts.' + window.thread.board + '.' + num, current_posts.concat(data.Num));
            }
            
            if(Store.get('other.qr_close_on_send', true)) $('#qr').hide();

            if(!window.thread.id) { //костыль
                var behavior = Store.get('other.on_reply_from_main', 1);
                if(behavior == 1) {
                    window.location.href = '/' + window.board + '/res/' + $('#qr-thread').val() + '.html#' + data.Num;
                }else if(behavior == 2) {
                    expandThread(parseInt($('#qr-thread').val()), function(){
                        Post(data.Num).highlight();
                        scrollToPost(data.Num);
                    });
                }
            }else{
                var highlight_num = data.Num;
                updatePosts(function(data){
                    if(Favorites.isFavorited(window.thread.id)) Favorites.setLastPost(data.data, window.thread.id);
                    Post(highlight_num).highlight();
                    //higlight_myposts
                    if(Store.get('other.higlight_myposts', true)) Post(highlight_num).highlight_myposts();
                });
            }
            resetInputs();
        }else if(data.Status && data.Status == 'Redirect') {
            var num = data.Target;
            $alert('Тред №' + num + ' успешно создан');
            
            //костылик, при создании треда для автодобавления в избранное, если есть настройка autowatchmythreads
            if(Store.get('other.autowatchmythreads', false)) Store.set('other.mythread_justcreated', true);

            window.location.href = '/' + window.board + '/res/' + num + '.html';
        }else{
            $alert('Ошибка постинга');
        }

        on_complete();
    };

    var on_complete = function() {
        busy = false;
        renderSendingDone();
        loadCaptcha();
    };

    var resetInputs = function() {
        $('#subject').val('');
        $('#shampoo, #qr-shampoo').val('');
        $('#captcha-value, #qr-captcha-value').val('');
        $('.message-byte-len').html(window.thread.max_comment);
        if(window.FormFiles) window.FormFiles.reset();
        $('.oekaki-image').val(''); //очистка оекаки
        $('.oekaki-metadata').val(''); //очистка оекаки
        $('.oekaki-clear').prop('disabled', true);
        $('.message-sticker-preview').html(''); // sticker
        $('.sticker-input').remove();
    };

    var saveToStorage = function() {
        Store.set('thread.postform.name', $('#name').val());
        Store.set('thread.postform.email', $('#e-mail').val());
        var icon = $('.anoniconsselectlist').val();
        if(icon) Store.set('thread.postform.icon.' + window.thread.board, icon);
    };

    var validator_error = window.postform_validator_error = function(id, msg) {
        var $el = $('#' + id);
        var $qr_el = $('#qr-' + id);

        if(msg) $alert(msg);

        $el.addClass('error');
        $qr_el.addClass('error');
        (activeForm.attr('id') == 'qr-shampoo') ? $qr_el.focus() : $el.focus();
    };

    var validateForm = function(is_qr, form, callback) {
        var $captcha = $('#captcha-value');
        var $c_id    = $('.captcha-key');
        var len = unescape(encodeURIComponent($('#shampoo').val())).length;
        var max_len = parseInt(window.thread.max_comment);

        if($('input[name=thread]').val()=='0' && window.FormFiles && window.FormFiles.max && !window.FormFiles.count && !is_qr && !window.thread.enable_oekaki) return $alert('Для создания треда загрузите картинку');
        if($('input[name=thread]').val()=='0' && $('input[name=subject]').val()=='' && board == 'news') return $alert('Для создания треда заполните поле "Тема"');  //вкл. обязательное поле "тема" в news
        if($('input[name=thread]').val()=='0' && $('input[name=tags]').val()=='' && board == 'vg') return $alert('Для создания треда заполните поле "Теги"'); //вкл. обязательное поле "теги" в vg
        if(!len && window.FormFiles && window.FormFiles.max && !window.FormFiles.count && !FormFiles.oekaki && !FormFiles.sticker) return validator_error('shampoo', 'Вы ничего не ввели в сообщении'); //не проверять оекаки
        if($captcha.length && !$captcha.val()) return validator_error('captcha-value', 'Вы не ввели капчу');
        if(len > max_len) return validator_error('shampoo', 'Максимальная длина сообщения ' + max_len + ' <b>байт</b>, вы ввели ' + len);
        
        //проверка капчи до отправки файлов
        if($captcha.length && window.FormFiles.count) {
            var url = '/api/captcha/2chaptcha/check/' + $c_id.val() + '?value=' + $captcha.val();
            if(callback) callback(url, form);
        } else {
            $('.error').removeClass('error');
            sendForm(form);
        }
    };
    
    var preCheckC = function(url, form) {
        $.get(url, function( data ) {
            if(data['result'] == 0) {
                return $alert('Капча невалидна');
            } else {
                $('.error').removeClass('error');
                sendForm(form);
            }
        })
    }

    $forms.on('submit', function(){
        if(typeof FormData == 'undefined') return; //старый браузер
        if(busy) {
            request.abort();
            return false;
        }
        window.FormFiles.appendToForm(this);
        var form = $(this);

        saveToStorage();
        //if(validateForm(form.attr('id') == 'qr-postform')) sendForm(form[0]);
        validateForm(form.attr('id') == 'qr-postform',form[0],preCheckC);
        
        return false;
    });

    $('#qr-cancel-upload').click(function(){
        request.abort();
    });

    resetInputs();
});

Stage('Обработка нажатий клавиш',               'keypress',     Stage.DOMREADY,     function(){
    var ctrl = false;
    $(window).keydown(function(e) {
        if(e.keyCode == 17) ctrl = true;
        if(e.keyCode == 32 && ctrl) {
            if(!Store.get('other.qr_hotkey', true)) return;
            var $qr = $('#qr');
            if($qr.is(':visible')) {
                $qr.hide();
            }else{
                $qr.show();
                loadCaptcha();
            }
        }
    })
        .keyup(function(e) {
            if(e.keyCode == 17) ctrl = false;
        })
        .blur(function() {
            ctrl = false;
        });

    $('#qr-shampoo').add('#shampoo').keydown(function(e) {
        if(e.keyCode == 13 && ctrl && Store.get('old.ctrl_enter_submit', true)) {
            if(window.activeForm.attr('id') == 'qr-shampoo') {
                $('#qr-submit').click();
            }else{
                $('#submit').click();
            }
        }
    });

});
Stage('Enable debug',                           'enabledebug',  Stage.DOMREADY,     function(){
    if(!Store.get('debug',false)) return;
    $('#bmark_debug').attr('style','inline-block');
    $('.debug').removeClass('debug');
});
Stage('NSFW',                                   'nsfw',         Stage.DOMREADY,     function(){
    var enabled = Store.get('styling.nsfw',false);

    var turn_on = function() {
        enabled = true;
        $('head').append('<style type="text/css" id="nsfw-style">' +
            '.preview{opacity:0.05}' +
            '.preview:hover{opacity:1}' +
            '</style>');
    };
    var turn_off = function() {
        enabled = false;
        $('#nsfw-style').remove();
    };

    $('#nsfw').click(function(){
        if(enabled) {
            Store.del('styling.nsfw');
            turn_off();
        }else{
            Store.set('styling.nsfw',true);
            turn_on();
        }
    });

    if(enabled) turn_on();
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
Stage('Мои доски',                              'myboards',     Stage.DOMREADY,     function(){
    if(!Store.get('other.myboards.enabled', true)) return;
    var $postbtn_favorite_board = $('.postbtn-favorite-board');
    $postbtn_favorite_board.css('display', 'inline-block');
    $('.dropd-boards').css('display', 'inline-block');
    if(!window.thread.id) $('.favorite-board').css('display', 'inline-block');

    if(!Store.get('other.myboards.list',false)) {
        Store.set('other.myboards.list',{"b":"/Б/ред","po":"Политика и новости","mmo":"Massive multiplayer online games","vg":"Видеоигры","d":"Дискуссии о Два.ч"});
    }

    if(Store.get('other.myboards.list.' + window.board, false)) {
        $postbtn_favorite_board
            .removeClass('fa-star-o')
            .addClass('fa-star');
    }

    var board_list_dropped = false;
    $('#dropd-board-btn').click(function(){
        if(board_list_dropped) {
            $('#dropd-board-list').removeClass('dropped');
        }else{
            $('#dropd-board-list').addClass('dropped');
        }
        board_list_dropped = !board_list_dropped;
    });

    $.each(Store.get('other.myboards.list',{}), function(k,v){
        $('#dropd-board-list-ul').append('<li id="dropd-board-' + k + '">' +
            '<a title="massive multiplayer online games" href="/' + k + '/">/' + k + '/ - ' + v + '</a>' +
            '</li>');
    });

    var addBoard = function() {
        var title = $.trim($('#title').text());
        $postbtn_favorite_board
            .removeClass('fa-star-o')
            .addClass('fa-star');
        Store.set('other.myboards.list.' + window.board, title);
        $('#dropd-board-list-ul').append('<li id="dropd-board-' + window.board + '">' +
            '<a title="' + title + '" href="/' + window.board + '/">/' + window.board + '/ - ' + title + '</a>' +
            '</li>');
    };

    var removeBoard = function() {
        $postbtn_favorite_board
            .addClass('fa-star-o')
            .removeClass('fa-star');
        Store.del('other.myboards.list.' + window.board);
        $('#dropd-board-' + window.board).remove();
    };

    $postbtn_favorite_board.click(function(){
        if(Store.get('other.myboards.list.' + window.board, false)) {
            removeBoard();
        }else{
            addBoard();
        }
    });

    if(!Store.get('other.myboards.menu', false)) return;
    var boards = [];

    $.each(Store.get('other.myboards.list',{}), function(k,v){
        boards.push('<a href="/' + k + '/" title="' + v + '">' + k + '</a>');
    });

    $('.rmenu').html('Разделы: [ ' + boards.join(' / ') + ' ]');
});
Stage('Скрывабщиеся блоки снизу',                 'bottomboxes',   Stage.DOMREADY,     function(){
    window.Box = {
        init: function() {
            //todo
            $('#boardstats-box').css('display','inline-block');
            $('#favorites-box').css('display','inline-block');
        },
        showBox: function(box) {
            var $toggle_btn = $('#' + box + '-arrow-down');
            $('#' + box + '-body').css('display','inline-block')
            $toggle_btn.addClass('fa-angle-double-down');
            $toggle_btn.removeClass('fa-angle-double-up');
            Store.set('styling.' + box + '.minimized', false); //todo возможно вынос в тугл, так как мы это же условие проверяем перед вызовом show/hide
            if(box == 'favorites') {
                this.noNewItems(box);
            }
        },
        hideBox: function(box) {
            var $toggle_btn = $('#' + box + '-arrow-down');
            $('#' + box + '-body').css('display','none');
            $toggle_btn.removeClass('fa-angle-double-down');
            $toggle_btn.addClass('fa-angle-double-up');
            Store.del('styling.' + box + '.minimized');
        },
        toggleVisibility: function() {
            var box = $(this).data('box');
            var minimized = Store.get('styling.' + box + '.minimized', true);
            if(!minimized) {
                Box.hideBox(box);
            }else{
                Box.showBox(box);
            }
        },
        toggleNewItems: function(box) { //todo Geven, чтобы выделение пропадало с других вкладок
            var $header = $('#' + box + '-header').find('.bb-header-text');
            $header.addClass('bb-header-text-new');
            Store.set('styling.favorites.new', true);
        },
        noNewItems: function(box) {
            var $header = $('#' + box + '-header').find('.bb-header-text');
            $header.removeClass('bb-header-text-new');
            Store.del('styling.favorites.new');
        }
    }
    var $box_header = $('#boardstats-header').add('#favorites-header');
    $box_header.click(Box.toggleVisibility);
    Box.init(); //todo возможно в Init
    if(Store.get('styling.boardstats.minimized', true)) Box.hideBox('boardstats');
    if(Store.get('styling.favorites.minimized', true)) Box.hideBox('favorites');
    if(Store.get('styling.favorites.new', false)) Box.toggleNewItems('favorites');
});
Stage('Статистика тредов',                      'boardstats',   Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(!Store.get('other.boardstats',true)) {
        $('#boardstats-box').css('display','none');
        return;
    }
    var $boardstats_update_el = $('.update-stats-box');
    var $boardstats_updating_el = $('.update-stats-box-updating');

    var busy = false;
    var timer = 0;
    var box_visible = true;
    var force_on_show = false;

    var reset = function(time, announce) {
        if(!time) time = window.threadstats.refresh;
        time = time*1000*5; //todo 5 мин
        if(timer) clearTimeout(timer);
        if(busy) busy = false;
        $boardstats_update_el.css('display','inline-block');
        $boardstats_updating_el.css('display','none');

        timer = setTimeout(announce_refresh, time);
        if(announce) Gevent.emit('boardstats_' + window.board + '_reset', time);
    };

    var announce_refresh = function() {
        if(!box_visible) {
            force_on_show = true;
            return reset();
        }
        busy = true;
        $boardstats_update_el.hide();
        $boardstats_updating_el.show();

        Gevent.onceNtemp('boardstats_' + window.board + '_abort_refresh', 1000, reset, execute_refresh);
        Gevent.emit('boardstats_' + window.board + '_announce_refresh');
    };

    var execute_refresh = function() {
        clearTimeout(timer);
        download_data(function(data){
            busy = false;
            if(!data) return reset(window.threadstats.retry);
            reset();

            Gevent.emit('boardstats_' + window.board + '_data', data);
            Store.set('boardstats.' + window.board, {time:(+new Date), data:data});

            render_data(data);
        });
    };//todo объем хранимых данных пересмотреть

    var download_data = function(cb) {
        var on_error = function(){
            cb(false);
        };

        var on_success = function(data) {
            if(!data) return cb(false);
            if(!data.hasOwnProperty) return cb(false);
            if(!data.hasOwnProperty('threads')) return cb(false);
            
            data['threads'].splice(10,data['threads'].length); 
            data['threads'].sort(function(a,b){
                return b['score']-a['score'];
            });

            cb(data['threads']);
        };

        $.ajax({
            url: '/' + window.thread.board + '/threads.json',  
            type: 'GET',
            dataType: 'json',
            success: on_success,
            error: on_error,
            timeout: window.threadstats.request_timeout
        });
    };

    var render_data = function(threads) {
        var $table = $('#boardstats-table');
        var rendered = 0;
        var html = '';
        $table.html(html);
        for(var i=0;i<threads.length;i++) {
            var thread = threads[i];
            if(!thread) break;
            if(parseInt(thread.sticky)) continue;
            if(parseInt(thread.bump_limit)) continue;

            html += '<div class="boardstats-row">' +
                '<span class="boardstats-title"><a href="/' + window.thread.board + '/res/' + thread.num + '.html">' + (thread.subject||'<i>Без названия</i>') + '</a></span>' +
                '<span class="boardstats-views">&nbsp;' +
                '<i class="fa fa-bar-chart"> ' + (Math.round(thread['score']*100)/100) + ' </i> ' +
                '<i class="fa fa-eye"> ' + thread['views'] + '&nbsp;&nbsp;&nbsp;</i> ' +
                '</span>';
            html += '</div>';

            rendered++;
            if(rendered >= window.threadstats.count) break;
        }
        $table.html(html);
    };

    $boardstats_update_el.click(function(){
        console.log(box_visible);
        if(!box_visible) Box.toggleVisibility();
        if(!busy) announce_refresh();
    });

    Gevent.on('boardstats_' + window.thread.board + '_announce_refresh', function() {
        if(busy) Gevent.emit('boardstats_' + window.thread.board + '_abort_refresh');
    });

    Gevent.on('boardstats_' + window.thread.board + '_reset', function(time) {
        if(busy) return;
        reset(time);
    });

    Gevent.on('boardstats_' + window.thread.board + '_data', function(data) {
        if(busy) return;
        Store.set('boardstats.' + window.thread.board, {time:(+new Date), data:data});
        render_data(data);
        reset(window.threadstats.refresh+Math.round(Math.random()*10));
    });

    var cached_stats = Store.get('boardstats.' + window.thread.board, false);
    if(cached_stats && cached_stats.data) {
        render_data(cached_stats.data);
    }else{
        force_on_show = true;
        if(box_visible) announce_refresh();
    }

    reset();
});
Stage('Обработка скрытия тредов и постов',      'posthide',     Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //if(window.thread.id) return;
    var hide_buttons = '.postbtn-hide,.postbtn-hide-mob';
    if(!$(hide_buttons.length)) return;

    window._hide_by_list = function(num) {
        if(!hidden.hasOwnProperty(num)) return;
        if(num == window.thread.id) return;
        /*var el = $('#thread-' + num);
         if(el.length) Post(num).hide();*/
        var post = Post(num);
        if(post.exists() && post.isRendered()) post.hide();
    };

    var cleanup = function() {
        var boards = Store.get('board', {});
        var time = getTimeInDays();

        for(var board in boards) {
            if(!boards.hasOwnProperty(board)) continue;
            if(!boards[board].hasOwnProperty('hidden')) continue;
            var threads = boards[board].hidden;
            for(var num in threads) {
                if(!threads.hasOwnProperty(num)) continue;
                var added_time = threads[num];

                if($('#post-' + num).length){
                    Post(num)._storeHide(); //обновляем время, чтоб не удалить тред
                }else if(time-added_time >= window.thread.hideTimeout) {
                    Post(num)._storeUnHide();
                }
            }
        }
    };

    $('#posts-form').on('click',hide_buttons,function(){
       var num = $(this).data('num');
       Post(num).hide(true);

       return false;
    });

    $('#posts-form').on('click', '.hidden-thread-box,.hidden-p-box', function(){
        var num = $(this).data('num');
        //var thread = $('#thread-' + num);
        Post(num).unhide();
    });

    var hidden = Store.get('board.' + window.board + '.hidden', {});
    for(var num in hidden) window._hide_by_list(num);
    cleanup();

    return false;
});
Stage('Скрытие постов по правилам v3 NABROSOK EDITION',             'hiderulesv3',    Stage.DOMREADY,     function(){
    return;
    if(Store.get('debug')) return;
    if(!window.thread.board) return; //не запускаем на главной
    
    var tmpost = Post(1);
    
    posts = JSON.parse(tmpost.getPostsObj());
    for (var i in posts ) {
        //console.log(i);
    }
    //Post(N).cGetComment()
});
Stage('Скрытие постов по правилам',             'hiderules',    Stage.DOMREADY,     function(){
    if(Store.get('debug')) return;
    if(!window.thread.board) return; //не запускаем на главной
    var rules = Store.get('other.hide_rules.list',[]);
    if(!rules.length) return;
    var tmpost = Post(1);

    var test = function(regexp, text) {
        try {
            return new RegExp(regexp, 'i').test(text);
        }catch(e){
            return false;
        }
    };

    window._hide_by_rules = function($posts) {
        $posts.each(function(){
            tmpost.num = $(this).data('num');

            for(var i=0;i<rules.length;i++) {
                var title = rules[i][0];
                var tnum = rules[i][1];
                var icon = rules[i][2];
                var email = rules[i][3];
                var name = rules[i][4];
                var trip = rules[i][5];
                var subject = rules[i][6];
                var comment = rules[i][7];
                var disabled = !!rules[i][8];

                if(disabled) continue;
                if(tnum && tmpost.num != tnum) continue;
                if(icon && !test(icon, tmpost.cGetIcon())) continue;
                if(email && !test(email, tmpost.cGetEmail())) continue;
                if(name && !test(name, tmpost.cGetName())) continue;
                if(trip && !test(trip, tmpost.cGetTrip())) continue;
                if(subject && !test(subject, tmpost.cGetSubject())) continue;
                if(comment && !test(comment, tmpost.cGetComment())) continue;

                tmpost.hide(false, 'Правило #' + (i+1) + ' ' + title);
                break;
            }
        });
    };

    window._hide_by_rules($('.post'));
});
Stage('Скрытие постов по правилам v2',          'hiderulesv2',  Stage.DOMREADY,     function(){
    return;
    if(!Store.get('debug')) return;
    if(!window.thread.board) return; //не запускаем на главной

    //render - строка - формат для показа пользователю условия
    //pre_render - функция - обрабатывать {var} перед формированием render
    //check - функция - проверка выполнения условия
    //params - объект - параметры
    //  label - строка - имя параметра для показа пользователю
    //  validator - функция - проверялка параметра для подсветки поля ввода красным

    var val_checkers = {
        gt: {
            params: {
                num: {
                    label: 'Число',
                    validator: function(input) {
                        return parseInt(input).toString() == input;
                    }
                }
            },
            render: '>{val}',
            check: function(val, params) {
                return val > params['num'];
            }
        },

        bool: {
            params: {
                inp: {
                    label: 'Значение'
                }
            },
            render: '{val}',
            pre_render: function(param) {
                if(param) {
                    return 'Да';
                }else{
                    return 'Нет';
                }
            },
            check: function(val, params) {
                return val == params['num'];
            }
        }
    };

    var val_types = {
        int: ['gt', 'lt', 'eq'],
        boolean: ['bool'],
        string: ['substr', 'regex'],
        comment: ['substr', 'regex']
    };

    //
    var val_catalog = {
        is_op: {
            label: 'ОП пост',
            type: 'boolean',
            extract: function(post) {
                return post.getThread() == post.num;
            }
        },
        num: {
            label: 'Номер поста',
            type: 'int',
            extract: function(post) {
                return post.num;
            }
        },
        icon: {
            label: 'Иконка',
            type: 'string',
            extract: function(post) {
                return post.cGetIcon();
            }
        },
        email: {
            label: 'Email',
            type: 'string',
            extract: function(post) {
                return post.cGetEmail();
            }
        },
        name: {
            label: 'Имя',
            type: 'string',
            extract: function(post) {
                return post.cGetName();
            }
        },
        trip: {
            label: 'Трипкод',
            type: 'string',
            extract: function(post) {
                return post.cGetTrip();
            }
        },
        subject: {
            label: 'Тема',
            type: 'string',
            extract: function(post) {
                return post.cGetSubject();
            }
        },
        comment: {
            label: 'Текст поста',
            type: 'comment',
            extract: function(post) {
                return post.cGetComment();
            }
        }
    };


    var rules = Store.get('hide.rules',[]);
    if(!rules.length) return;
    var tmpost = Post(1);

    var test = function(regexp, text) {
        try {
            return new RegExp(regexp, 'i').test(text);
        }catch(e){
            return false;
        }
    };

    window._hide_by_rules = function($posts) {
        $posts.each(function(){
            tmpost.num = $(this).data('num');

            for(var i=0;i<rules.length;i++) {
                var rule = rules[i];

                if(rule['disabled']) continue;
                if(rule['tnum'] && tmpost.num != rule['tnum']) continue;
                if(rule['icon'] && !test(rule['icon'], tmpost.cGetIcon())) continue;
                if(rule['email'] && !test(rule['email'], tmpost.cGetEmail())) continue;
                if(rule['name'] && !test(rule['name'], tmpost.cGetName())) continue;
                if(rule['trip'] && !test(rule['trip'], tmpost.cGetTrip())) continue;
                if(rule['subject'] && !test(rule['subject'], tmpost.cGetSubject())) continue;
                if(rule['comment'] && !test(rule['comment'], tmpost.cGetComment())) continue;

                tmpost.hide(false, 'Правило #' + (i+1) + ' ' + title);
                break;
            }
        });
    };

    window._hide_by_rules($('.post'));
});
Stage('Скрытие длинных постов',                 'hidelongpost', Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(window.thread.id) return;
    (function($) {

        // Matches trailing non-space characters.
        var chop = /(\s*\S+|\s)$/;

        // Return a truncated html string.  Delegates to $.fn.truncate.
        $.truncate = function(html, options) {
            return $('<div></div>').append(html).truncate(options).html();
        };

        // Truncate the contents of an element in place.
        $.fn.truncate = function(options) {
            if ($.isNumeric(options)) options = {length: options};
            var o = $.extend({}, $.truncate.defaults, options);

            return this.each(function() {
                var self = $(this);

                if (o.noBreaks) self.find('br').replaceWith(' ');

                var text = self.text();
                var excess = text.length - o.length;

                if (o.stripTags) self.text(text);

                // Chop off any partial words if appropriate.
                if (o.words && excess > 0) {
                    excess = text.length - text.slice(0, o.length).replace(chop, '').length - 1;
                }

                if (excess < 0 || !excess && !o.truncated) return;

                // Iterate over each child node in reverse, removing excess text.
                $.each(self.contents().get().reverse(), function(i, el) {
                    var $el = $(el);
                    var text = $el.text();
                    var length = text.length;

                    // If the text is longer than the excess, remove the node and continue.
                    if (length <= excess) {
                        o.truncated = true;
                        excess -= length;
                        $el.remove();
                        return;
                    }

                    // Remove the excess text and append the ellipsis.
                    if (el.nodeType === 3) {
                        $(el.splitText(length - excess - 1)).replaceWith(o.ellipsis);
                        return false;
                    }

                    // Recursively truncate child nodes.
                    $el.truncate($.extend(o, {length: length - excess}));
                    return false;
                });
            });
        };

        $.truncate.defaults = {

            // Strip all html elements, leaving only plain text.
            stripTags: false,

            // Only truncate at word boundaries.
            words: false,

            // Replace instances of <br> with a single space.
            noBreaks: false,

            // The maximum length of the truncated html.
            length: Infinity,

            // The character to use as the ellipsis.  The word joiner (U+2060) can be
            // used to prevent a hanging ellipsis, but displays incorrectly in Chrome
            // on Windows 7.
            // http://code.google.com/p/chromium/issues/detail?id=68323
            ellipsis: '\u2026' // '\u2060\u2026'

        };

    })(jQuery);
    var line_len = 150; //длина строки, после которой считается переход на новую
    var max_lines = 10; //сколько строк максимум

    var makeExpand = function(original, shrink) {
        var num = original.attr('id').substr(1);

        original.wrapInner('<div id="original-post' + num + '" style="display:none"></div>');

        var $shrinked = $('<div id="shrinked-post' + num + '">' + shrink + '</div>');
        original.append($shrinked);

        var $unhide = $('<span class="expand-large-comment a-link-emulator">Показать текст полностью</span>');
        $shrinked.after($unhide);
        $unhide.click(function(){
            $unhide.remove();
            $shrinked.remove();
            $('#original-post' + num).show();
        });
    };

    window._hide_long_post = function(el){
        var html = el.html();

        var lines_count = 0;
        var line_arr = html.split('<br>');
        for(var i=0;i<line_arr.length;i++) {
            var text = $('<p>' + line_arr[i] + '</p>').text();
            var lines_in_line = Math.ceil((text.length+1)/line_len); //1 символ для переноса
            if((lines_count+lines_in_line) <= max_lines) {
                lines_count += lines_in_line;
                continue;
            }

            var excess_lines = max_lines - lines_count;
            line_arr[i] = $.truncate(line_arr[i], excess_lines*line_len);
            line_arr.splice(i+1);

            makeExpand(el, line_arr.join('<br>'));
            break;
        }
    };

    var $posts = $('.post-message');
    for (var i = 0; i < $posts.length; i++) { 
        window._hide_long_post($($posts[i]));
    }

});
Stage('Обработка Media ссылок',                 'mediapeocess', Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var $links = $('.post-message').find('a').not('.post-reply-link');
    Media.processLinks($links);
});
Stage('Коррекция времени по часовому поясу',    'correcttz',    Stage.DOMREADY,     function(){
    if(!Store.get('other.correcttz', true)) return;
    //var local_tz_offset = -(new Date()).getTimezoneOffset()*1000;
    var server_tz_offset = window.tz_offset*60*60;

    if((-(new Date()).getTimezoneOffset()/60) == window.tz_offset) return;

    var days = ['Вск','Пнд','Втр','Срд','Чтв','Птн','Суб'];
    //var months = ['Янв','Фев','Мрт','Апр','Май','Июн','Июл','Авг','Сен','Окт','Нбр','Дек'];

    window.correctTZ = function(str) {
        str = str.replace(/(\d+)\/(\d+)\/(\d+) .+ (\d+:\d+:\d+)/g,"20$3-$2-$1T$4Z");
        str = $.trim(str);
        var timestamp = Date.parse(str);
        if(!timestamp) return str;
        timestamp = timestamp - server_tz_offset*1000;
        var date = new Date(timestamp);

        return '' +
            pad(date.getDate(), 2) +
            '/' +
            pad(date.getMonth()+1, 2) +
            '/' +
            pad(date.getFullYear()-2000, 2) +
            ' ' +
            days[date.getDay()] +
            ' ' +
            pad(date.getHours(), 2) +
            ':' +
            pad(date.getMinutes(), 2) +
            ':' +
            pad(date.getSeconds(), 2);
    };

    $('.posttime').each(function(){
        var str = $(this).text();
        $(this).text(window.correctTZ(str));
    });
});
Stage('Обработка формы ответа',                 'formprocess',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var active_id = '';
    var $label_top = $('.reply-label-top');
    var $label_bot = $('.reply-label-bot');
    var text_open = $label_top.first().text();
    var text_close = 'Закрыть форму постинга';
    var $postform = $('#postform');

    $('.makaba').on('click', '.reply-label-top', function(){
        if(active_id == 'bot') $label_bot.text(text_open);

        if(active_id == 'top') {
            $postform.hide();
            $label_top.text(text_open);
            active_id = '';
        }else{
            $('#TopNormalReply').after($postform);
            $postform.show();
            $label_top.text(text_close);
            active_id = 'top';
            if(!window.thread.id) $('input[name=thread]').val(0);
        }
    });

    $('.makaba').on('click', '.reply-label-bot', function(){
        if(active_id == 'top') $label_top.text(text_open);

        if(active_id == 'bot') {
            $postform.hide();
            $label_bot.text(text_open);
            active_id = '';
        }else{
            $('#BottomNormalReply').after($postform);
            $postform.show();
            $label_bot.text(text_close);
            active_id = 'bot';
            if(!window.thread.id) $('input[name=thread]').val(0);
        }
    });

    window.appendPostForm = function(num) {
        if(active_id == 'top') $label_top.text(text_open);
        if(active_id == 'bot') $label_bot.text(text_open);
        if(active_id == num) {
            active_id = '';
            $postform.hide();
            return false;
        }

        var post = Post(num);
        post.el().after($postform);
        $postform.show();
        if(!window.thread.id) $('input[name=thread]').val(post.getThread());
        active_id = num+'';

        if(!$('.captcha-image:first').html()) {
            loadCaptcha();
        }

        return true;
    };
});
Stage('Загрузка автообновления',                'autorefresh',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var enabled = false;
    var interval;
    var timeout;
    var remain;
    var isWindowFocused = true;
    var newPosts = [];

    window.scrollcb_array.push(function(scroll_top){
        if(!newPosts.length) return;

        var scroll = scroll_top+$(window).height();
        for(var i=0;i<newPosts.length;i++) {
            //[0] = номер поста
            //[1] = координаты по Y поста
            if(scroll >= newPosts[i][1]) {
                newPosts.splice(i, 1);
                i--;
            }
        }

        notifyNewPosts();
        if(!newPosts.length) reposRedLine();
    });
    $(window).blur(function(){
        isWindowFocused = false;

        reposRedLine();
    });
    $(window).focus(function(){
        isWindowFocused = true;

        if(newPosts.length) $(window).scroll();
        if(!$('.autorefresh-checkbox').is(':checked')) return;
        if(remain > window.config.autoUpdate.minInterval) setNewTimeout(window.config.autoUpdate.minInterval);
    });

    var reposRedLine = function() {
        var $line = $('.new-posts-marker');
        if($line.length) $line.removeClass('new-posts-marker');
        if(newPosts.length) $('#post-' + newPosts[0]).prev().addClass('new-posts-marker');
    };
    var current_icon;
    var setFavicon = function(icon) {
        if(icon == current_icon) return;
        if(current_icon == window.config.autoUpdate.faviconDeleted) return;
        current_icon = icon;

        $('#favicon').replaceWith(icon);
    };

    var notifyNewPosts = function() {
        var count = newPosts.length;
        if(count) {
            document.title = '(' + count + ') ' + window.config.title;
            setFavicon(window.config.autoUpdate.faviconNewposts);
        }else{
            document.title = window.config.title;
            setFavicon(window.config.autoUpdate.faviconDefault);
        }
    };
    var threadDeleted = function() {
        setFavicon(window.config.autoUpdate.faviconDeleted);
        $('.autorefresh-countdown').html(' остановлено');
    };
    var start = window.autorefresh_start = function() {
        if(enabled) return false;
        enabled = true;

        $('.autorefresh-checkbox').attr('checked','checked');

        interval = setInterval(function(){
            var $autorefresh_el = $('.autorefresh-countdown');
            remain--;
            if(remain >= 0) $autorefresh_el.html('через ' + remain);
            if(remain != 0) return;
            $autorefresh_el.html(' выполняется...');

            updatePosts(function(data){
                if(data.error) {
                    if(data.error == 'server' && data.errorCode == -404) return threadDeleted();
                    $alert('Ошибка автообновления: ' + data.errorText);
                    calcNewTimeout(-1);
                }else{
                    if(data.updated){
                        var len = data.list.length;
                        for(var i=0;i<len;i++) {
                            var $post = $('#post-' + data.list[i]);
                            newPosts.push([data.list[i], $post.offset().top + $post.height()]);
                        }

                        notifyNewPosts();
                        reposRedLine();
                    }
                    calcNewTimeout(data.updated);
                    if(Favorites.isFavorited(window.thread.id)) Favorites.setLastPost(data.data, window.thread.id);
                }
            });

        }, 1000);

        setNewTimeout(window.config.autoUpdate.minInterval);
    };
    var stop = function() {
        if(!enabled) return false;
        enabled = false;

        $('.autorefresh-checkbox').removeAttr('checked');

        clearInterval(interval);
        $('.autorefresh-countdown').html('');
    };
    var calcNewTimeout = function(posts) {
        if(posts>1) return setNewTimeout(window.config.autoUpdate.minInterval);
        if(isWindowFocused && posts != -1) return setNewTimeout(window.config.autoUpdate.minInterval); //иначе это ошибка

        var newTimeout = timeout + window.config.autoUpdate.stepInterval;
        if(newTimeout> window.config.autoUpdate.maxInterval) return setNewTimeout(window.config.autoUpdate.maxInterval);

        return setNewTimeout(newTimeout);
    };
    var setNewTimeout = function(newTimeout) {
        remain = newTimeout;
        timeout = newTimeout;
        $('.autorefresh-countdown').html('через ' + remain);
    };

    $('.autorefresh-checkbox').click(function(){
        var checked = $(this).is(':checked');
        if(checked) {
            start();
        }else{
            stop();
        }
        Store.set('thread.autorefresh', !!checked);
    });

    $('.autorefresh').css('display', 'inline-block');
});
Stage('Клонирование форм',                      'cloneform',    Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var fields = ['e-mail', 'shampoo', 'captcha-value'];
    var maxlen = parseInt(window.thread.max_comment);
    var len = fields.length;

    var newlen = function(str) {
        var len = unescape(encodeURIComponent(str)).length;
        var remain = maxlen-len;
        if(remain < 0) remain = 0;
        $('.message-byte-len').html(remain);
    };

    for(var i=0;i<len;i++) {
        var field = fields[i];
        (function(field){
            $('#' + field).keyup(function(){
                var val = $('#' + field).val();
                $('#qr-' + field).val(val);
                if(field == 'shampoo') newlen(val);
            });

            $('#qr-' + field).keyup(function(){
                var val = $('#qr-' + field).val();
                $('#' + field).val(val);
                if(field == 'shampoo') newlen(val);
            });
        })(field);
    }

    $('.anoniconsselectlist').change(function(){
        var val = $(this).val();
        $('.anoniconsselectlist').val(val);
    });
});
Stage('Отслеживание фокуса форм',               'formfocus',    Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    window.activeForm = $('#shampoo');

    window.activeForm.focus(function(){
        window.activeForm = $(this);
    });

    $('#qr-shampoo').focus(function(){
        window.activeForm = $(this);
    });
});
Stage('click эвенты',                           'clickevents',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var lastselected = '';
    var selectednum = 0;

    $('.captcha-reload-button').click(loadCaptcha);

    $('#posts-form').on('mouseup','.post',function(e){
        if (e.which != 1) return;
        var num = $(this).data('num');
        var node;
        try {
            node = window.getSelection ? window.getSelection().focusNode.parentNode:document.selection.createRange().parentElement();
        }catch(e){
            return;
        }

        if($(node).closest('.post').data('num') != num) return;

        var text = "";
        if (window.getSelection) {
            text = window.getSelection().toString();
        } else if (document.selection && document.selection.type != "Control") {
            text = document.selection.createRange().text;
        }
        text = text + '';

        if(!text){
            selectednum = 0;
            lastselected = '';
            return;
        }
        lastselected = text;
        selectednum = num;
        lastselected = '>' + lastselected.split("\n").join("\n>");
    });

    $('.reply-label').click(function(){
        if(!$('.captcha-image:first').html()) {
            loadCaptcha();
        }
    });

    $('#ed-toolbar').html(edToolbar('shampoo'));

    $('#qr-close').click(function(){
        $('#qr').hide();
    });

    $('#posts-form').on('click','.postbtn-reply-href', function() {
        var num = $(this).attr('id'); //@todo ???
        var str = '>>' + num + '\n';

        //вставляем цатату
        if(selectednum == num) {
            str += lastselected + '\n';
            selectednum = 0;
        }

        if(Store.get('old.append_postform',false)) {
            if(appendPostForm(num)) insert(str);
        } else {
            insert(str);
        }

        if(window.thread.id) return false;

        var thread = Post(num).getThread();
        $('#qr-thread').val(thread);

        return false;
    });
    
    //jscatalog: запоминаем запрос при клике на спец. ссылку
    $(".hashlink").on('mousedown',function() {
        Store.set('catalog-search-query',$(this).attr('title'));
        return true;
    });
    
    $(".ira-btn").click(function(){
        $(".ira-heart").fadeToggle();
    });
    
    var posts = [];
    $('.turnmeoff').change(function() {
        if(!$('.replypage')) return;
        if(this.checked) {
            posts += this.value + ', ';
            $('#report-form-posts').val(posts);
        } else {
            
        }
    });
    
    //tags - eng only + remove slash
    $("#tags").on('input',function(e) {
        var newstr = $(this).val().replace(/\/|\\|#/g, '');
        //newstr = newstr.replace(/\\/g, '');
        var map = [
            ["ӓ", "a"], ["ӓ̄", "a"], ["ӑ", "a"], ["а̄", "a"], ["ӕ", "ae"], ["а́", "a"], ["а̊", "a"], ["ә", "a"], ["ӛ", "a"], ["я", "a"], ["ѫ", "a"], ["а", "a"], ["б", "b"], ["в", "v"], ["ѓ", "g"], ["ґ", "g"], ["ғ", "g"], ["ҕ", "g"], ["г", "g"], ["һ", "h"], ["д", "d"], ["ђ", "d"], ["ӗ", "e"], ["ё", "e"], ["є", "e"], ["э", "e"], ["ѣ", "e"], ["е", "e"], ["ж", "zh"], ["җ", "zh"], ["ӝ", "zh"], ["ӂ", "zh"], ["ӟ", "z"], ["ӡ", "z"], ["ѕ", "z"], ["з", "z"], ["ӣ", "j"], ["и́", "i"], ["ӥ", "i"], ["і", "i"], ["ї", "ji"], ["і̄", "i"], ["и", "i"], ["ј", "j"], ["ј̵", "j"], ["й", "j"], ["ќ", "k"], ["ӄ", "k"], ["ҝ", "k"], ["ҡ", "k"], ["ҟ", "k"], ["қ", "k"], ["к̨", "k"], ["к", "k"], ["ԛ", "q"], ["љ", "l"], ["Л’", "l"], ["ԡ", "l"], ["л", "l"], ["м", "m"], ["њ", "n"], ["ң", "n"], ["ӊ", "n"], ["ҥ", "n"], ["ԋ", "n"], ["ԣ", "n"], ["ӈ", "n"], ["н̄", "n"], ["н", "n"], ["ӧ", "o"], ["ө", "o"], ["ӫ", "o"], ["о̄̈", "o"], ["ҩ", "o"], ["о́", "o"], ["о̄", "o"], ["о", "o"], ["œ", "oe"], ["ҧ", "p"], ["ԥ", "p"], ["п", "p"], ["р", "r"], ["с̀", "s"], ["ҫ", "s"], ["ш", "sh"], ["щ", "sch"], ["с", "s"], ["ԏ", "t"], ["т̌", "t"], ["ҭ", "t"], ["т", "t"], ["ӱ", "u"], ["ӯ", "u"], ["ў", "u"], ["ӳ", "u"], ["у́", "u"], ["ӱ̄", "u"], ["ү", "u"], ["ұ", "u"], ["ӱ̄", "u"], ["ю̄", "u"], ["ю", "u"], ["у", "u"], ["ԝ", "w"], ["ѳ", "f"], ["ф", "f"], ["ҳ", "h"], ["х", "h"], ["ћ", "c"], ["ҵ", "c"], ["џ", "d"], ["ч", "c"], ["ҷ", "c"], ["ӌ", "c"], ["ӵ", "c"], ["ҹ", "c"], ["ч̀", "c"], ["ҽ", "c"], ["ҿ", "c"], ["ц", "c"], ["ъ", "y"], ["ӹ", "y"], ["ы̄", "y"], ["ѵ", "y"], ["ы", "y"], ["ь", "y"], ["", ""], ["Ӓ", "a"], ["Ӓ̄", "a"], ["Ӑ", "a"], ["А̄", "a"], ["Ӕ", "ae"], ["А́", "a"], ["А̊", "a"], ["Ә", "a"], ["Ӛ", "a"], ["Я", "a"], ["Ѫ", "a"], ["А", "a"], ["Б", "b"], ["В", "v"], ["Ѓ", "g"], ["Ґ", "g"], ["Ғ", "g"], ["Ҕ", "g"], ["Г", "g"], ["Һ", "h"], ["Д", "d"], ["Ђ", "d"], ["Ӗ", "e"], ["Ё", "e"], ["Є", "e"], ["Э", "e"], ["Ѣ", "e"], ["Е", "e"], ["Ж", "zh"], ["Җ", "zh"], ["Ӝ", "zh"], ["Ӂ", "zh"], ["Ӟ", "z"], ["Ӡ", "z"], ["Ѕ", "z"], ["З", "z"], ["Ӣ", "j"], ["И́", "i"], ["Ӥ", "i"], ["І", "i"], ["Ї", "ji"], ["І̄", "i"], ["И", "i"], ["Ј", "j"], ["Ј̵", "j"], ["Й", "j"], ["Ќ", "k"], ["Ӄ", "k"], ["Ҝ", "k"], ["Ҡ", "k"], ["Ҟ", "k"], ["Қ", "k"], ["К̨", "k"], ["К", "k"], ["ԛ", "q"], ["Љ", "l"], ["Л’", "l"], ["ԡ", "l"], ["Л", "l"], ["М", "m"], ["Њ", "n"], ["Ң", "n"], ["Ӊ", "n"], ["Ҥ", "n"], ["Ԋ", "n"], ["ԣ", "n"], ["Ӈ", "n"], ["Н̄", "n"], ["Н", "n"], ["Ӧ", "o"], ["Ө", "o"], ["Ӫ", "o"], ["О̄̈", "o"], ["Ҩ", "o"], ["О́", "o"], ["О̄", "o"], ["О", "o"], ["Œ", "oe"], ["Ҧ", "p"], ["ԥ", "p"], ["П", "p"], ["Р", "r"], ["С̀", "s"], ["Ҫ", "s"], ["Ш", "sh"], ["Щ", "sch"], ["С", "s"], ["Ԏ", "t"], ["Т̌", "t"], ["Ҭ", "t"], ["Т", "t"], ["Ӱ", "u"], ["Ӯ", "u"], ["Ў", "u"], ["Ӳ", "u"], ["У́", "u"], ["Ӱ̄", "u"], ["Ү", "u"], ["Ұ", "u"], ["Ӱ̄", "u"], ["Ю̄", "u"], ["Ю", "u"], ["У", "u"], ["ԝ", "w"], ["Ѳ", "f"], ["Ф", "f"], ["Ҳ", "h"], ["Х", "h"], ["Ћ", "c"], ["Ҵ", "c"], ["Џ", "d"], ["Ч", "c"], ["Ҷ", "c"], ["Ӌ", "c"], ["Ӵ", "c"], ["Ҹ", "c"], ["Ч̀", "c"], ["Ҽ", "c"], ["Ҿ", "c"], ["Ц", "c"], ["Ъ", "y"], ["Ӹ", "y"], ["Ы̄", "y"], ["Ѵ", "y"], ["Ы", "y"], ["Ь", "y"], ["№", ""], ["\'", ""], ["\"", ""], [";", ""], [":", ""], [",", ""], [".", ""], [">", ""], ["<", ""], ["?", ""], ["!", ""], ["@", ""], ["#", ""], ["$", ""], ["%", ""], ["&", ""], ["^", ""], ["(", ""], [")", ""], ["*", ""], ["+", ""], ["~", ""], ["|", ""], ["{", ""], ["}", ""], ["|", ""], ["[", ""], ["]", ""], ["/", ""], ["`", ""], ["=", ""], ["+", ""], ["_", ""], ["/[^A-Za-z0-9\-]", ""]
        ];
        for(var i=0; i<map.length; i++){
            newstr = newstr.replace(map[i][0], map[i][1]);
        };
    
        $(this).val(newstr.trim().toLowerCase());
        
        return true;
    });
    
    $('#mod-mark-box').change(function() {
        if(this.checked) alert('Вы добавили модтег! >_>'); 
    });
    
    $(".nb__switcher").on('click', 'a',function(e) {
        var block = $(this).data('switch');
        
        News.render(News[block]);
        
        $('.nb__switcher').find('a').removeClass('nb__switcher_active');
        $(this).addClass('nb__switcher_active');
        return false;
    });
    window.News = {
        hour: [],
        day: [],
        latest: [],
        getdata: function() {
            var that = this;
            $.get('/news.json', function(data){
                that.hour = data.news_hour;
                that.day = data.news_day;
                that.latest = data.news_latest;
                that.render(that.hour);//day by default
            });
        },
        render: function(data) {
            var html = '';
            for(var i = 0; i < data.length; i++) {
                html += '<div class="nb__item"><i class="fa fa-newspaper-o"></i> <a href="/news/res/' + data[i].num + '.html">' + data[i].subject + '</a></div>';
            }
            $('.nb__data').html(html);
        }
    };
    
    News.getdata(); //@todo это и выше вынести отдельно
});
Stage('oekaki',                          'oekaki',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //==================================================================================================
    //lcanvas 
    $('#qr-oekaki-close').click(function(){
        lc.clear(); //очищаем рисунок
        $('#qr-oekaki-body').html('');
        $('#qr-oekaki').hide(); //прячем рисовалку
    });
    
    $('.oekaki-draw').click(function(e){
        var width = $('.oekaki-width').val();
        var height = $('.oekaki-height').val();
        oekakiInit(width, height);
    });
    
    $('.qr-oekaki-accept').on('click', function() {
        var lcanvasdata = lc.getImage().toDataURL().split(',')[1];
        $('.oekaki-image').val(lcanvasdata);
        $('.oekaki-metadata').val(new Date($.now()));
        $('.oekaki-clear').prop('disabled', false); 
        $('.form-files-input').prop('disabled', true); //если есть оекаки картинка, то простые грузить нельзя
        $('.form-files-thumbnails').html(''); //текущие загруженные игнорим
        lc.clear(); //очищаем рисунок
        $('#qr-oekaki-body').html(''); //удаляем плагин
        $('#qr-oekaki').hide(); //прячем рисовалку
        FormFiles.oekaki = 1; //для FormValidate
    });
    $('.oekaki-clear').click(function(){
        lc.clear();
        $('#qr-oekaki-body').html('');
        $('#qr-oekaki').hide();
        $('.oekaki-image').val('');
        $('.oekaki-metadata').val('');
        $(this).prop('disabled', true);
        $('.form-files-input').prop('disabled', false);
        FormFiles.oekaki = 0;
    });
});
Stage('stickers',                          'stickers',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //ПРОВЕРКА УДАЛЕННОГО СТИКЕРА + СУТОЧНАЯ СИНХРОНИЗАЦИЯ + INSTALL
    var stickers = '/api/stickers/';
    var mystickers;
    var freqSticker;
    
    var updateStickers = function(id) {
        $.get('/api/sticker/show/' + id, function( data ) {
            Store.del('other.sticker.packs.' + id);
            if(data.pack.id) Store.set('other.sticker.packs.' + id, data); //перезаписываем только норм ответ
        })
        
    }
    
    var getFreqStickers = function() {
        var freqHtml = '';
        freqSticker =  Store.get('other.sticker.last', []);
        for (i = freqSticker.length - 1; i >= 0; i--) {
            freqHtml += '<img data-sticker="' + freqSticker[i].id  + '" src="' + freqSticker[i].url + '">';
        }
        return freqHtml;
    };
    
    var setFreqStickers = function(sticker, url) {
        freqSticker.push({'id': sticker,'url': url});
        for (i = 0; i < freqSticker.length - 1; i++) {
            if(freqSticker[i].id == sticker) {
                freqSticker.splice(i, 1);
            } 
        }
        if(freqSticker.length > 20) {
            freqSticker.splice(0, 1);
        }
        Store.set('other.sticker.last', freqSticker);
    };
    
    $('#postform, #qr-postform').on('click', '.message-sticker-btn', function() {   
        $('#qr-sticker').show();
        //Store.reload(); //чтобы меж вкладок обновлялся сразу store
        mystickers = Store.get('other.sticker.packs');
        if(!mystickers) return;
        if(mystickers) reversed  = Object.keys(mystickers).reverse();
        var html = '';
        html += '<div class="sticker">';
        html += '<div class="sticker-name">Часто используемые</div>';
        html += getFreqStickers();
        html += '</div>';
        
        //for(var i in mystickers) { //todo посмотреть воз-ть переделать в массив объектов ; todo try catch
        for(i = 0; i < reversed.length; i++) {
            html += '<div class="sticker">';
            html += '<div class="sticker-name">' + mystickers[reversed[i]].pack.name;
            html += '<a href="#" title="Обновить" class="sticker-update" data-id="' + mystickers[reversed[i]].pack.id + '">[U]<a>';
            html += '</div>';
            for( j = 0; j <  mystickers[reversed[i]].stickers.length; j++) {  
                html += '<img data-sticker="' + mystickers[reversed[i]].pack.id + '_' + mystickers[reversed[i]].stickers[j].id + '" src="' + mystickers[reversed[i]].stickers[j].thumbnail + '">';
            }
            html += '</div>';
        }
        $('#qr-sticker-body').html('')
        $('#qr-sticker-body').append(html);
    });
    //постим
    $('#qr-sticker').on('click', 'img', function(e) {
        var sticker = $(this).data('sticker');
        var url = e.target.src;
        $('.postform').append('<input type="hidden" name="sticker0" value="' +  sticker + '" class="' +  sticker + ' sticker-input">'); //todo на Id бы..
        $('.message-sticker-preview').html('<img src="' + url + '" class="'  +  sticker + '">');
        FormFiles.sticker = 1;
        setFreqStickers(sticker, url); //запоминаем стикер
        $('#qr-sticker').hide();
    });
    //удаляем превью
    $('.message-sticker-preview').on('click', 'img', function(e) {
        var sticker = e.target.className;
        $('.' + sticker).remove();
    });
    //обновляем пак
    $('#qr-sticker').on('click', '.sticker-update', function(e) {
        var id = $(this).data('id');
        updateStickers(id);
        return false;
    });
    $('#qr-sticker-close').click(function(){
        $('#qr-sticker').hide();
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
            post.download(function(res){
                if(res.errorText) return funcPostPreview('Ошибка: ' + res.errorText);
                funcPostPreview(post.previewHTML());
                if(!post.isRendered()) Media.processLinks($('#m' + pNum + ' a'));
            });
        }else{
            funcPostPreview(post.previewHTML());
        }
        $del($id(pView.id)); //удаляет старый бокс поста
        dForm.appendChild(pView);

        if(!post.isRendered()) {
            Media.processLinks($('#m' + pNum + ' a'));
        }else{
            //todo костыль. Надо что-то с этим делать.
            var $preview_box = $('#preview-' + pNum);
            $preview_box.find('.media-expand-button').remove();
            Media.processLinks($preview_box.find('a'));
        }
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
    
    $('#posts-form').on('mouseover', '.post-reply-link', function(e){
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
});
Stage('Опции постов',                           'postoptions',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //опции постов

    var active = 0;
    var googleImageHref = function(image) {
        var host = window.location.host;
        var google = 'http://www.google.com/searchbyimage?image_url=';
        //return google + 'http://' + host + image.attr('href');  //todo ИСПРАВИТЬ КАК ИСПРАВИТ Ш
        return google + 'http://' + host + image.attr('href');
    };

    var fillMenu = function(menu, num) {
        var post = Post(num);
        var $images =  $('#post-body-' + num).find('.image').find('.desktop');;
        /////////////////////////////////////////////////////////
        var $replyRow = $('<a href="#">Ответить</a>');
        $replyRow.click(function(){
            $(document.getElementById(num)).click();
            return false;
        });
        menu.append($replyRow);
        /////////////////////////////////////////////////////////
        var $watchRow = $('<a href="#">Следить</a>');
        $watchRow.click(function(){
            console.log('We watch ur ' + num + ' post replies');
            Favorites.add(num);
            Favorites.show();
            Favorites._send_fav(num);
            return false;
        });
        menu.append($watchRow);
        if(window.thread.id){
            var $reportRow = $('<a href="#">Пожаловаться</a>');
            $reportRow.click(function(){
                var field = $('#report-form-comment');
                field.val( '>>' + num + ' ' + field.val() );
                hideMenu();
                field.focus();

                return false;
            });
            menu.append($reportRow);
        }
        /////////////////////////////////////////////////////////
        if($images.length == 1) {
            menu.append('<a href="' + googleImageHref($images) + '" target="_blank">Найти картинку</a>');
        }else if($images.length > 1) {
            $images.each(function(k){
                var v = $(this);
                menu.append('<a href="' + googleImageHref(v) + '" target="_blank">Найти картинку ' + (k+1) + '</a>');
            });
        }
        if(window.config.makabadmin) {
            if($images.length == 1) {
                menu.append('<a class="mod-action-massban" href="/makaba/makaba.fcgi?task=vk_export&board=' + window.thread.board + '&num=' + num + '&file=' + 0 + '&publish=' + 1 + '" onclick="return writePablos(this);">Отправить на стену</a>');
                menu.append('<a class="mod-action-massban" href="/makaba/makaba.fcgi?task=vk_export&board=' + window.thread.board + '&num=' + num + '&file=' + 0 + '&publish=' + 0 + '" onclick="return writePablos(this);">Отправить в альбом</a>');
            }else if($images.length > 1) {
                $images.each(function(k){
                    var v = $(this);
                    menu.append('<a class="mod-action-massban" href="/makaba/makaba.fcgi?task=vk_export&board=' + window.thread.board + '&num=' + num + '&file=' + k + '&publish=' + 1 + '" onclick="return writePablos(this);">Отправить на стену ' + (k+1) + '</a>');
                });
                $images.each(function(k){
                    var v = $(this);
                    menu.append('<a class="mod-action-massban" href="/makaba/makaba.fcgi?task=vk_export&board=' + window.thread.board + '&num=' + num + '&file=' + k + '&publish=' + 0 + '" onclick="return writePablos(this);">Отправить в альбом ' + (k+1) + '</a>');
                });
            }
            // /makaba.fcgi?task=vk_export&board=b&num=129123370&file=0&to=ru2chvg&publish=1
        }
        /////////////////////////////////////////////////////////
        if(post.isThread()){
            var label = 'В избранное';
            var is_favorited = Favorites.isFavorited(num);
            if(is_favorited) label = 'Из избранного';
            var $favRow = $('<a href="#">' + label + '</a>');
            $favRow.click(function(){
                if(!is_favorited) {
                    Favorites.add(num);
                    Favorites.show();
                    Favorites._send_fav(num);
                }else{
                    Favorites.remove(num);
                }
                hideMenu();

                return false;
            });
            menu.append($favRow);
        }
        /////////////////////////////////////////////////////////
        if(($images.length == 1) && (window.thread.enable_oekaki==1)) {
            var $redrawRow = $('<a href="#">Перерисовать</a>');
            var $imagesPreviews =  $('#post-body-' + num ).find('.image').find('.preview');
            $redrawRow.click(function(){
                var multiplier = 1;
                var h_p = $imagesPreviews.attr('height');
                var w_p = $imagesPreviews.attr('width'); //размеры превью
                
                var imgsize = $imagesPreviews.attr('alt').split('x'); //оригинальные размеры
                
                var win_width = $( window ).width();
                var win_height = $( window ).height();
                
                var w_scale = Math.floor(win_width/imgsize[0]*10)/10; //коэф. сжатия 
                var h_scale = Math.floor(win_height/imgsize[1]*10)/10;
                
                if(imgsize[0] > (win_width - 100) || imgsize[1] > (win_height - 100)) {
                    multiplier = w_scale<h_scale ? w_scale : h_scale;
                }
                oekakiInit(imgsize[0]*multiplier,imgsize[1]*multiplier); 
                
                var newImage = new Image();
                newImage.src = $images.attr('href');
                lc.saveShape(LC.createShape('Image', {scale: multiplier, x: 0, y: 0, image: newImage}));
                $(document.getElementsByName(num)).click();
                return false;
            });
            menu.append($redrawRow);
        }
    };

    var genPos = function(el) {
        var ret = {};
        var pos = el.offset();

        ret.left = (pos.left + el.outerWidth()) + 'px';
        ret.top = pos.top + 'px';

        return ret;
    };

    var hideMenu = function(num) {
        if(!active) return;
        active = 0;
        $('#ABU-select').remove();
    };

    $('body').click(hideMenu);

    //todo .postbtn-hide, .postbtn-rep, .postbtn-exp, .postbtn-expall, .postbtn-adm, .postbtn-options, .postbtn-report, .sticky-img, .postbtn-favorite 
    //оптимизиовать. 1 класс общий + на клики id
    //ABU-select, ABU-banreasons так же выделить в класс reply-modal абсолютного reply и id для окон
    $('#posts-form').on('click', '.postbtn-options', function(){
        var el = $(this);
        var num = el.data('num');
        var old = active;
        hideMenu(num);
        active = num;
        if(old == num) {
            active = 0;
            return false;
        }
        
        var $menu = $('<span></span>');
        $menu.attr('id', 'ABU-select');
        $menu.attr('class', 'modal');
        $menu.css(genPos(el));
        fillMenu($menu, num);
        $menu.click(hideMenu);
        //window.menu = $menu;
        $('body').append($menu);
        return false;
    });
    $('#posts-form').on('click', '.postbtn-report', function(e){
        var el = $(this);
        var num = el.data('num');
        var thread = Post(num).getThread();
        var data;
        var old = active;
        hideMenu();
        active = num;
        if(old == num) {
            active = 0;
            return false;
        }
        var html = '<form id="modReportForm" enctype="multipart/form-data" method="post">' +
                '<input name="task" value="report" type="hidden">' +
                '<input name="board" value="' + window.thread.board + '" type="hidden">' +
                '<input name="thread" value="' + thread + '" type="hidden">' +
                '<input name="posts" value="' + num + '" type="hidden">' +
                '<input name="comment" id="modReportFormComment" value="" placeholder="Жалоба" type="text">' +
                '<input value="Ок" type="button" id="modReportSend"></form>';
        var $menu = $('<span>' + html + '</span>');
        $menu.attr('id', 'ABU-select');
        $menu.attr('class', 'modal mod-report');
        $menu.css(genPos(el));
        $('body').append($menu);
        return false;
    });
    $('.makaba').on('click', '#modReportSend',function(e){
        var form = document.getElementById('modReportForm');
        var $comment = $('#modReportFormComment')
        var request = new FormData(form);
        //if(request.get('comment') == '') request.set('comment','Жалоба........');
        if(!request.get('comment')) {
            $comment.addClass('error');
            return false;
        }
        $alert( "Работаем..." );
        $.ajax({
            method: "POST",
            url:'/makaba/makaba.fcgi?json=1', 
            data: request, 
            success: function() {
                $alert( "Накляузничано." );
                hideMenu();
            },
            contentType: false,
            processData: false
        });
    });
    $('.makaba').on('click', '#ABU-select',function(e){
        e.stopPropagation();
    });
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
Stage('renderStore',                            'renderstore',  Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    renderStore();

    if(Store.get('styling.disable_bytelen_counter',false)) $('.message-byte-len').hide();
    if(Store.get('styling.portform_format_panel',true)) {
        $('.toolbar-area').css('display','table-row');
        $('#CommentToolbar').html(edToolbar('shampoo'));
        $('#qr-CommentToolbar').html(edToolbar('qr-shampoo'));
    }
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
Stage('Избранное',                              'favorites',    Stage.DOMREADY,     function(){
    if(!Store.get('other.favorites',true)) {
        $('#favorites-box').css('display','none');
        return;
    }
    var $fav_body = $('#favorites-table');
    var favorites = Store.get("favorites");

    for(var key in favorites) {
        if(!favorites.hasOwnProperty(key)) continue;
        var thread = favorites[key];
        if(typeof(thread) != 'object' || !thread.hasOwnProperty('last_post')) continue;
        var thread_row = Favorites.render_get_html(key, thread);

        $fav_body.append(thread_row);
    }

    Favorites.init();

    $fav_body.on('click', '.fav-row-remove', function(){
        var num = $(this).data('num');
        //if(confirm('Вы уверены?')) Favorites.remove(num);
        Favorites.remove(num);
    });

    $fav_body.on('click', '.fav-row-update', function(){
        var num = $(this).data('num');
        Favorites.forceRefresh(num);
    });

    $('.posts').on('click', '.postbtn-favorite,#postbtn-favorite-bottom', function(){
        var num = $(this).data('num') || window.thread.id;
        var favorited = Favorites.isFavorited(num);

        if(favorited) {
            Favorites.remove(num);
        }else{
            Favorites.add(num);
            Favorites.show();
            Favorites._send_fav(num);
        }
    });
    
    //
    $('#qr-fav-autowatchmyposts').change(function() {
        Store.set('other.autowatchmyposts', this.checked)

    });
    $('#qr-fav-autowatchmythreads').change(function() {
        Store.set('other.autowatchmythreads', this.checked)
    });
    
    $('#qr-fav-autowatchmyposts').prop('checked', Store.get('other.autowatchmyposts', true));
    $('#qr-fav-autowatchmythreads').prop('checked', Store.get('other.autowatchmythreads', false));
    
    //автодобавления тред в избранное
    if(Store.get('other.autowatchmythreads', false) && Store.get('other.mythread_justcreated', false)) {
        Favorites.add(window.thread.id);
        Favorites.show();
        Favorites._send_fav(window.thread.id);
        Store.del('other.mythread_justcreated');
    }

    if(Store.get('other.fav_stats', false)) $('.loice-bar').css('display','inline-block');
});
Stage('Загрузка плавающих окон',                'qrload',       Stage.DOMREADY,     function(){
    draggable_qr('qr', 'left');
    draggable_qr('settings-window', 'center');
    draggable_qr('setting-editor-window', 'center');
    draggable_qr('qr-oekaki', 'center');
    draggable_qr('qr-sticker', 'center');
});
Stage('Юзеропции',                              'settings',     Stage.DOMREADY,     function(){
    Settings.addCategory('favorites', 'Избранное');
    Settings.addCategory('old', 'Раньшебылолучше');
    Settings.addCategory('other', 'Другое');
    Settings.addCategory('mobile', 'Мобильная версия');
    Settings.addCategory('hide', 'Скрытие');

    Settings.addSetting('favorites',    'favorites.show_on_new', {
        label: 'Показывать избранное при новом сообщении',
        default: true
    });
    Settings.addSetting('favorites',    'favorites.deleted_behavior', {
        label: 'При удалении треда на сервере',
        multi: true,
        values: [
            ['0', 'Не удалять из избранного'],
            ['1', 'Повторно проверять перед удалением'],
            ['2', 'Удалять из избранного сразу']
        ],
        default: 1
    });
    Settings.addSetting('old',          'styling.qr.disable_if_postform', {
        label: 'Не выводить плавающую форму если развёрнута другая форма',
        default: false
    });
    Settings.addSetting('old',          'styling.qr.disable', {
        label: 'Не выводить плавающую форму при клике на номер поста',
        default: false
    });
    Settings.addSetting('old',          'styling.disable_bytelen_counter', {
        label: 'Не показывать счётчик байт в форме постинга',
        default: false
    });
    Settings.addSetting('old',        'styling.portform_format_panel', {
        label: 'Показ панели разметки текста в форме',
        default: true
    });
    Settings.addSetting('old',        'old.append_postform', {
        label: 'Показ формы постинга под постом при ответе',
        default: false
    });
    Settings.addSetting('old',        'old.ctrl_enter_submit', {
        label: 'Отправка поста по Ctrl+Enter',
        default: true
    });
    Settings.addSetting('old',        'old.media_thumbnails', {
        label: 'Показ превью видео',
        default: true
    });
    Settings.addSetting('old',  'old.media_thumbnails_on_hover', {
        label: 'Показ превью видео только при наводе мыши на ссылку',
        default: true
    });
    Settings.addSetting('old',          'other.fullscreen_expand', {
        label: 'Разворачивать картинки в центре экрана',
        default: true
    });

    Settings.addSetting('other',        'other.on_reply_from_main', {
        label: 'При ответе с главной в тред',
        multi: true,
        values: [
            ['0', 'Ничего не делать'],
            ['1', 'Перенаправлять в тред'],
            ['2', 'Разворачивать тред']
        ],
        default: 1
    });
    Settings.addSetting('other',        'other.qr_close_on_send', {
        label: 'Закрывать плавающую форму после ответа',
        default: true
    });
    Settings.addSetting('other',        'other.custom_css.enabled', {
        label: 'Пользовательский CSS',
        default: false,
        edit: {
            label: 'Редактировать',
            title: 'Редактировать СSS',
            editor: 'textarea',
            path: 'other.custom_css.data',
            //importable: true, //если true, то выводить кнопки импорта и экспорта
            saveable: true,
            default: ''
        }
    });
    Settings.addSetting('other',        'other.show_post_preview_delay', {
        label: 'Задержка показа ответа при наводе мыши на номер поста',
        multi: true,
        values: [
            ['0', 'Нет'],
            ['50', '50мс'],
            ['100', '100мс'],
            ['200', '200мс'],
            ['300', '300мс'],
            ['400', '400мс'],
            ['500', '500мс']
        ],
        default: 50
    });
    Settings.addSetting('other',        'other.hide_post_preview_delay', {
        label: 'Задержка скрытия ответа',
        multi: true,
        values: [
            ['0', 'Нет'],
            ['50', '50мс'],
            ['100', '100мс'],
            ['200', '200мс'],
            ['500', '500мс'],
            ['800', '800мс'],
            ['1000', '1000мс'],
            ['1500', '1500мс'],
            ['2000', '2000мс'],
            ['3000', '3000мс'],
            ['5000', '5000мс']
        ],
        default: 800
    });
    Settings.addSetting('other',        'other.expand_autoscroll', {
        label: 'При сворачивании длинной пикчи фокусироваться на пост',
        default: true
    });
    Settings.addSetting('other',        'other.scroll_btns', {
        label: 'Показ кнопок перемотки страницы',
        default: false
    });
    Settings.addSetting('other',          'other.qr_hotkey', {
        label: 'Выводить плавающую форму по Ctrl+Space',
        default: true
    });
    Settings.addSetting('other',          'other.boardstats', {
        label: 'Показывать топ тредов',
        default: true
    });
    Settings.addSetting('other',          'other.favorites', {
        label: 'Показывать избранное',
        default: true
    });
//    Settings.addSetting('other',          'other.fav_stats', {
//        label: 'Показывать количество подписок на треды',
//        default: false
//    });
    Settings.addSetting('other',          'other.myboards.enabled', {
        label: 'Показывать Мои доски',
        default: true
    });
    Settings.addSetting('other',          'other.myboards.menu', {
        label: 'Заменить меню разделов на Мои доски',
        default: false
    });
    Settings.addSetting('other',          'other.correcttz', {
        label: 'Коррекция часового пояса',
        default: true
    });
    Settings.addSetting('other',        'other.captcha_provider', {
        label: 'Капча',
        multi: true,
        values: [ 
            ['2chaptcha', '2chaptcha'],
            ['animecaptcha', 'animecaptcha'],
        ],
        default: '2chaptcha'
    });
    Settings.addSetting('other',        'other.navigation', {
        label: 'Бесконечная прокрутка',
        multi: true,
        values: [
            ['page', 'Отключено'],
            ['scroll', 'Автоматически'],
            ['combo', 'Переключатель'],
            ['button', 'Ручная']
        ],
        default: 'scroll'
    });
    Settings.addSetting('other',        'other.media.titler.max_workers', {
        label: 'Загрузка названий видео',
        multi: true,
        values: [
            ['0', 'Отключено'],
            ['1', '1 поток'],
            ['2', '2 потока'],
            ['3', '3 потока'],
            ['4', '4 потока'],
            ['5', '5 потоков'],
            ['6', '6 потоков'],
            ['7', '7 потоков'],
            ['8', '8 потоков'],
            ['9', '9 потоков'],
            ['10', '10 потоков']
        ],
        default: '2'
    });

    Settings.addSetting('other',        'other.higlight_id', {
        label: 'Подсветка постов по ID',
        default: true
    });
    
    Settings.addSetting('other',        'other.higlight_myposts', {
        label: 'Помечать ваши посты',
        default: true
    });
    
    Settings.addSetting('other',        'other.higlight_myposts_replies', {
        label: 'Помечать ответы на мои посты',
        default: true
    });

    Settings.addSetting('mobile',       'mobile.dont_expand_images', {
        label: 'Открывать пикчи в новом окне',
        default: false
    });
    Settings.addSetting('mobile',       'mobile.hide_qr', {
        label: 'Отключить плавающую форму',
        default: false
    });

    Settings.addSetting('hide',        'other.hide_rules.enabled', {
        label: 'Правила скрытия постов',
        default: false,
        edit: {
            label: 'Редактировать',
            title: 'Редактировать правила скрытия',
            editor: 'hiderules',
            path: 'other.hide_rules.list',
            importable: true,
            default: []
        }
    });
    /////////////////////////////////////////////////////////////////////////////////////
    Settings.addEditor('textarea', function(val){
        var $body = $('#setting-editor-body');
        var textarea = $('<textarea id="setting-editor-textarea-textarea"></textarea>');
        textarea.val(val);
        $body.append(textarea);
    }, function(){
        //save
        return $('#setting-editor-textarea-textarea').val();
    });
    /////////////////////////////////////////////////////////////////////////////////////
    Settings.addEditor('singleinput', function(val){
        var $body = $('#setting-editor-body');
        var input = $('<span id="setting-editor-singleinput-text">Укажите список разделов через запятую.<br>Приммер: b,fag,po<br></span><input type="text" id="setting-editor-singleinput-input" />');
        input.val(val);
        $body.append(input);
    }, function(){
        //save
        return $('#setting-editor-singleinput-input').val();
    });
    /////////////////////////////////////////////////////////////////////////////////////
    var rules = [];
    Settings.addEditor('hiderules', function(val){
        var that = this;
        var last_rule = 0;
        var append_row = function(title,tnum,icon,email,name,trip,subject,comment,disabled) {
            var empty_cell = '<span class="hiderules-table-empty-cell">.*</span>';

            table.append('<tr id="hiderules-table-row' + i + '" class="' + (disabled?'hiderules-table-row-disabled':'') + '">' +
                '<td>№' + last_rule + '</td>' +
                '<td>' + (escapeHTML(title) || '') + '</td>' +
                '<td>' + (escapeHTML(tnum) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(icon) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(email) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(name) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(trip) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(subject) || empty_cell) + '</td>' +
                '<td>' + (escapeHTML(comment) || empty_cell) + '</td>' +
                '<td>' +
                '<input type="button" value="Экспорт" class="hiderules-table-row-export-btn" data-num="' + i + '">' +
                '<input type="button" value="Удалить" class="hiderules-table-row-delete-btn" data-num="' + i + '">' +
                '</td>' +
                '</tr>');
        };

        var $body = $('#setting-editor-body');
        var table = $('<table id="hiderules-table" class="hiderules-table">' +
            '<thead>' +
            '<tr id="hiderules-table-header">' +
            '<td>№</td>' +
            '<td>Название</td>' +
            '<td>#треда</td>' +
            '<td>Иконка</td>' +
            '<td>Email</td>' +
            '<td>Имя/ID</td>' +
            '<td>Трипкод</td>' +
            '<td>Тема</td>' +
            '<td>Сообщение</td>' +
            '<td>Управление</td>' +
            '</tr>' +
            '</thead>' +
            '</table>');
        rules = val;
        $body.html('');

        for(var i=0;i<rules.length;i++) {
            last_rule = i+1;
            var title = rules[i][0];
            var tnum = rules[i][1];
            var icon = rules[i][2];
            var email = rules[i][3];
            var name = rules[i][4];
            var trip = rules[i][5];
            var subject = rules[i][6];
            var comment = rules[i][7];
            var disabled = !!rules[i][8];

            append_row.apply(this, rules[i]);
        }

        table.append(
            '<tr id="hiderules-add-form">' +
                '<td class="hiderules-add-row">№' + (i+1) + '</td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-title"    class="hiderules-add-input error"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-tnum"     class="hiderules-add-input"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-icon"     class="hiderules-add-input" placeholder=".*"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-email"    class="hiderules-add-input" placeholder=".*"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-name"     class="hiderules-add-input" placeholder=".*"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-trip"     class="hiderules-add-input" placeholder=".*"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-subject"  class="hiderules-add-input" placeholder=".*"></td>' +
                '<td class="hiderules-add-row"><input type="text" id="hiderules-add-input-comment"  class="hiderules-add-input" placeholder=".*"></td>' +
                '<td><input id="hiderules-add-submit-btn" type="button" value="Добавить" disabled="disabled"></td>' +
                '</tr>');

        var add_form = $(
            '<div id="hiderules-add-form">' +
                '<div class="hiderules-add-row"><span class="hiderules-add-label">Правило:</span>  <input type="text" id="hiderules-add-json-input" placeholder="Можно вставить сохранённое ранее"></div>' +
                'В полях указываются регулярные выражения.<br>' +
                'Для конвертации строк в регулярки используйте конвертер:<br>' +
                '<input type="text" id="hiderules-add-converter-str"> -> <input type="text" id="hiderules-add-converter-regex" readonly="readonly"><br>' +
                '</div>');

        $body.append(table);
        $body.append(add_form);
        $body.append('<div id="hiderules-bottom">Нижние кнопки импорта и экспорта импортируют/экспортируют ВСЕ правила</div>');
        ///////////////////////////////////////////////////////////////////////////////////////////////////////
        $('.hiderules-table-row-export-btn').click(function(){
            var num = $(this).data('num');
            var rule =  Store.get('other.hide_rules.list.' + num);
            prompt('Скопируйте', JSON.stringify(rule));
        });
        $('.hiderules-table-row-delete-btn').click(function(){
            var num = $(this).data('num');
            var rules =  Store.get('other.hide_rules.list');
            rules.splice(num,1);
            Store.set('other.hide_rules.list', rules);
            Settings._editor_show(rules);
        });
        $('#hiderules-add-converter-str').keyup(function(){
            var val = $.trim($(this).val());
            var json = String(val).replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\-]', 'g'), '\\$&');
            $('#hiderules-add-converter-regex').val(json);
        });
        ///////////////////////////////////////////////////////////////////////////////////////////////////////
        var check_errors = function() {
            var err = false;
            for(var i=0;i<el.length;i++) {
                var el_name = el[i][0];
                var el_el = el[i][1];
                if(el_name == 'title') if(!el_el.val()) {
                    err = true;
                    el_el.addClass('error');
                    continue;
                }else{
                    el_el.removeClass('error');
                    continue;
                }

                try{
                    new RegExp(el_el.val());
                    el_el.removeClass('error');
                }catch(e){
                    el_el.addClass('error');
                }
            }

            return err;
        };

        var el = [];
        el.push(['title', $('#hiderules-add-input-title')]);
        el.push(['tnum', $('#hiderules-add-input-tnum')]);
        el.push(['icon', $('#hiderules-add-input-icon')]);
        el.push(['email', $('#hiderules-add-input-email')]);
        el.push(['name', $('#hiderules-add-input-name')]);
        el.push(['trip', $('#hiderules-add-input-trip')]);
        el.push(['subject', $('#hiderules-add-input-subject')]);
        el.push(['comment', $('#hiderules-add-input-comment')]);

        var $submit_btn = $('#hiderules-add-submit-btn');
        var $json_input = $('#hiderules-add-json-input');

        $('.hiderules-add-input').keyup(function(){
            var arr = [];
            for(var i=0;i<el.length;i++) arr.push(el[i][1].val());
            $json_input.val( JSON.stringify(arr) );

            if(check_errors()) {
                $submit_btn.attr('disabled','disabled');
            }else{
                $submit_btn.removeAttr('disabled','disabled');
            }

            $json_input.removeClass('error');
        })
            .focus(function(){
                $(this).attr('size', '25');
            })
            .blur(function(){
                $(this).removeAttr('size');
            });
        $json_input.keyup(function(){
            var arr;
            try {
                arr = JSON.parse($json_input.val());
            }catch(e){
                $json_input.addClass('error');
                return;
            }
            if(!arr.length || (arr.length != 8 && arr.length != 9)) {
                $json_input.addClass('error');
                return;
            }
            for(var i=0;i<8;i++) {
                el[i][1].val( arr[i] );
            }
            $json_input.removeClass('error');
            check_errors();
        });

        $submit_btn.click(function(){
            var arr = [];
            for(var i=0;i<el.length;i++) arr.push($.trim(el[i][1].val()));
            var c_arr = Store.get('other.hide_rules.list',[]);
            c_arr.push(arr);
            Store.set('other.hide_rules.list', c_arr);
            last_rule++;
            //append_row.apply(that,arr);
            Settings._editor_show(c_arr);
        });
    }, function(){
        //save
        //return $('#setting-editor-textarea-textarea').val();
    });
    /////////////////////////////////////////////////////////////////////////////////////

    $('#settings').click(function(){
        Settings.toggle();
    });
    $('#settings-btn-close').click(function(){
        Settings.hide();
    });
    $('#settings-btn-export').click(function(){
        var myWindow = window.open("", "JSON Settings", '_blank');
        myWindow.document.write('<textarea style="width:100%; height:100%;">' + escapeHTML(Store.export()) + '</textarea>');
        myWindow.focus();
        
        //var data = Store.export();
        //var url = 'data:text/json;charset=utf8,' + encodeURIComponent(data);
        //window.open(url, '_blank');
        //window.focus();
        //prompt('Скопируйте и сохраните', Store.export());
    });
    $('#settings-btn-import').click(function(){
        var json = prompt('Вставьте сохранённые настройки');
        if(!json) return;

        try {
            JSON.parse(json);
        }catch(e){
            return $alert('Неверный формат');
        }

        localStorage.store = json;

        Store.reload();
        Settings.hide();
        $alert('Для применения настроек обновите страницу');
    });
    $('#settings-btn-save').click(function(){
        var changed = [];

        $('.settings-setting-checkbox').each(function(){
            var $box = $(this);
            var category = $box.data('category');
            var path = $box.data('path');
            var setting = Settings.getSetting(category, path);
            var current_value = Store.get(path, setting.default);
            var new_value = $box.is(':checked');
            if(current_value == new_value) return;

            changed.push(path);
            if(new_value == setting.default) {
                Store.del(path);
            }else{
                Store.set(path, new_value);
            }
        });

        $('.settings-setting-multibox').each(function(){
            var $box = $(this);
            var category = $box.data('category');
            var path = $box.data('path');
            var setting = Settings.getSetting(category, path);
            var current_value = Store.get(path, setting.default);
            var new_value = $box.val();
            if(current_value == new_value) return;

            changed.push(path);
            if(new_value == setting.default) {
                Store.del(path);
            }else{
                Store.set(path, new_value);
            }
        });

        if(changed.length) $alert('Для применения настроек обновите страницу');
        Settings.hide();
    });

    $('#setting-editor-btn-save').click(function(){
        var newval = Settings._editor_onsave();
        //var currentval = Store.get(Settings._editor_path, Settings._editor_default_val);
        if(newval == Settings._editor_default_val) {
            Store.del(Settings._editor_path);
        }else{
            Store.set(Settings._editor_path, newval);
        }
        $('#setting-editor-window').hide();
    });

    $('#setting-editor-btn-close').click(function(){
        $('#setting-editor-window').hide();
    });

    $('#setting-editor-btn-export').click(function(){
        prompt('Скопируйте и сохраните', JSON.stringify(Store.get(Settings._editor_path, {})));
    });

    $('#setting-editor-btn-import').click(function(){
        var json = prompt('Вставьте сохранённое');
        var obj;
        if(!json) return;

        try {
            obj = JSON.parse(json);
        }catch(e){
            return $alert('Неверный формат');
        }

        Store.set(Settings._editor_path, obj);
        $('#setting-editor-window').hide();
    });
});
Stage('Подсветка якоря',                        'ancorlight',   Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    var match;

    if(match=/#([0-9]+)/.exec(document.location.toString())) {
        var post = Post(match[1]);
        if(!post.exists() || !post.isRendered()) return;
        Post(match[1]).highlight();
        scrollToPost(match[1]);
        history.pushState('', document.title, window.location.pathname);
    }

});
Stage('Предупреждение о анальной цензуре',      'censure',      Stage.DOMREADY,     function(){
    var checks = 0;
    var interval = setInterval(function(){
        if($('#de-panel').length && !$('.jcaptcha').length && (+new Date)-Store.get('tmp.censure',0) > 1000*60*60*3) {
            $alert('У вас установлен куклоскрипт, который без вашего ведома может удалять треды из выдачи и ' +
                'премодерировать информацию. Рекомендуем избавиться от него.' +
                '<a href="https://twitter.com/abunyasha/status/520708815038451712" target="_blank">Подробнее</a><br>' +
                '<a href="#" id="censure-notice-close">Закрыть</a>', 'wait');

            $('#censure-notice-close').click(function(event) {
                event.preventDefault();
                $close($id('ABU-alert-wait'));
                Store.set('tmp.censure',(+new Date));
            });

            clearInterval(interval);
        }
        checks++;
        if(checks >= 10) clearInterval(interval);
    },1000);
});
Stage('Взрослые разделы',                      'adultcheck',    Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //18 years old validate
    var ageallow = getCookie('ageallow');
    if(ageallow != 1) {
        if (top.location.pathname == '/test/' || top.location.pathname == '/fg/' || top.location.pathname == '/fur/' || top.location.pathname == '/g/' || top.location.pathname == '/ga/' || top.location.pathname == '/hc/' || top.location.pathname == '/e/' || top.location.pathname == '/fet/' || top.location.pathname == '/sex/' || top.location.pathname == '/fag/') {
            generateWarning('agebox');
        }
    }
    $("#ageboxallow").click(function(){
        setCookie("ageallow", 1, 365);
        $('.warningcover, .warningbox').remove();
        return false;
    });
    //
});

Stage('Удалятель ссылок, уродливый, как твоя мамаша','linkremover',    Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    //link remover
    window.linkremover = function() {
        if(window.thread.board=='b') {
            var x = $("a[href^='http']:not([href*='store.steampowered.com/app/444520']):not([href*='life.ru']):not([href*='2ch.pm']):not([href*='2ch.hk']):not([href*='2ch.pm']):not([href*='twitch.tv/abu1nyasha']):not([href*='2chtv.ru']):not([href*='telegram.me/twochannel']):not([href*='telegram.me/dvachannel']):not([href*='change.org']):not([href*='vk.com/ru2ch']):not([href*='itunes.apple.com']):not([href*='youtube.com']):not([href*='youtu.be']):not([href*='steampowered.com']):not([href*='twitter.com']):not([href*='2channel.hk'])").contents().unwrap();
        }
    };
    linkremover();
    
    function cl(link){
        var img = new Image(1,1);
        img.src = '//www.liveinternet.ru/click?*' + link;
    }

    (window.linkUpdater = function() {
        if(window.thread.board == 'b') {
            var list = "a[href^='http'][href*='twitter.com'],a[href^='http'][href*='youtu.be'],a[href^='http'][href*='youtube.com'],a[href^='http'][href*='itunes.apple.com'],a[href^='http'][href*='vk.com/ru2ch']" +
                        "a[href^='http'][href*='change.org'],a[href^='http'][href*='telegram.me/dvachannel'],a[href^='http'][href*='telegram.me/twochannel'],a[href^='http'][href*='2channel.hk'],a[href^='http'][href*='twitch.tv/abu1nyasha'],a[href^='http'][href*='life.ru'],a[href*='/banners/']";  
            var $links = $(list);
            var len = $links.length;
            for(var i = 0; i < len; i++) {
                $links[i].onclick = function () {
                    ga('send', 'event', 'outbound', 'click', this.href, { 'transport': 'beacon'});
                    //trackOutboundLink(this.href);  
                    //cl(this);
                }
            }
        } else {
            var list = "a[href^='http']:not([href*='2ch.pm']):not([href*='2ch.hk']):not([href*='2ch.pm']),a[href*='/banners/']";  
            //var x = $(list).each(function() {
            //  this.href = 'http://li.ru/go?' + this.href.split('://')[1];
            //});
            var $links = $(list);
            var len = $links.length;
            for(var i = 0; i < len; i++) {
                $links[i].onclick = function () {
                    ga('send', 'event', 'outbound', 'click', this.href, { 'transport': 'beacon'});
                    //trackOutboundLink(this.href);  
                    //cl(this);
                }
            }
        }
    })();

    //arch fixer
    if(location.pathname.split(/\//)[2]=='arch') {
        var arch_mark = '<h3 class="archive-thread">Тред в архиве!</h3>';
        $('.logo').append(arch_mark);

        $('.rekl').html('<div id="lx_602368"></div><div id="lx_602319"></div>');
    }
    
});
Stage('Бесконечная прокрутка',                  'escroll',      Stage.DOMREADY,     function(){
    var enabled = false;
    var active_page = 1;
    var max_page = 0;
    var busy = false;
    var done = false;
    var navigation = Store.get('other.navigation', 'scroll');
    
    if(navigation == 'page') return;
    if(window.thread.id) return;
    if(!window.thread.board) return;
    if($('.pager strong').text() != '0') return;
    var activateButton = $('<span class="a-link-emulator escroll-switch">' + (navigation=='button'?'Ещё':'Все') + '</span>');
    var pagerEl = $('<span class="escroll-pager">[ </span>').append(activateButton).append(' ]');

    var rekls = 0;
    var appendRekl = function() { //если что перенести в глобальный скоуп
        var postshtml = '';
        rekls += 1;

        postshtml += '<hr class="pre-rekl" style="display:none;">';  //prev
        postshtml += '<section class="moneymoneymoney"><a href="https://vk.com/ru2ch" target="blank"><img src="/images/vkru2ch.png"></a></section>';
        postshtml += '<hr>';
        
        
        //if((window.board == 'hc') || (window.board == 'e') || (window.board == 'fet')) {
        //    postshtml += '';
        //    postshtml += '<hr>';
        //} else {
        //    postshtml += '<center></center>';
        //    postshtml += '<hr>';
        //}

        $('#posts-form').append(postshtml);

        return true;
    };

    var onScroll = function(top) {
        if(!enabled) return;
        if(done) return;
        if(busy) return;
        if(!top) top = $(window).scrollTop();
        if($(document).height() - (top+$(window).height()) > 300) return;

        preloadPage(active_page++);
    };

    var preloadPage = function(num) {
        if(busy) return;
        busy = true;

        renderLoading();
        loadThreads(num, function(threads) {
            renderLoaded();
            busy = false;
            if(threads.no_more_threads) {
                done = true;
            }else if(threads.fail_to_fetch) {
                $alert('Ошибка при загрузке страницы');
                active_page--;
            }else{
                appendRekl();
                threads.forEach(function(thread) {
                    var post = Post(thread.thread_num);
                    if(post.exists() && post.isRendered()) return;
                    processThread(thread);
                });
                if(active_page > max_page) done = true;
                linkremover();//linkremover
                linkUpdater();  
            }
        });
    };

    var renderLoading = function() {
        $('.pager').after('<center class="escroll-loading"><h1>Загрузка...</h1></center>'); //pager to ID
    };

    var renderLoaded = function() {
        $('.escroll-loading').remove();
        if(navigation == 'button') {
            activateButton.addClass('a-link-emulator').unwrap('<strong></strong>');
            enabled = false;
        }

    };

    var loadThreads = function(num, cb) {
        $.getJSON( '/' + window.board + '/' + num + '.json', function(data) {
            if(!data) return cb({fail_to_fetch: true});
            if(!data.threads || !data.threads.length) return cb({no_more_threads: true});
            cb(data.threads);
        })
            .fail(function(answer) {
                if(answer.status == 404) return cb({no_more_threads: true});
                cb({fail_to_fetch: true});
            });
    };

    var processThread = function(thread) {
        for(var i=0;i<thread.posts.length;i++) {
            var post = thread.posts[i];
            Post(post.num).setThread(thread.thread_num).setJSON(post, true);

            if(!i) {
                appendThread(thread);
            }else{
                appendPost(post);
            }
        }
    };

    activateButton.click(function(){
        if(enabled) return;
        if(done) return;
        $(this).removeClass('a-link-emulator').wrap('<strong></strong>');
        enabled = true;

        if(navigation != 'scroll') preloadPage(active_page++);
    });
    if(navigation != 'button') window.scrollcb_array.push(onScroll);

    var $pager = $('.pager');
    $pager.find('a').each(function(){
        var page = parseInt($(this).text());
        if(page > max_page) max_page = page;
        return true;
    });
    $pager.prepend(pagerEl);

    if(navigation == 'combo' || navigation == 'button') return;
    activateButton.click();
    $pager.hide();
});
Stage('Подсветка постов по ID',                'highlight_id', Stage.DOMREADY,     function(){
    if(!Store.get('other.higlight_id', true)) return;

    $('head').append('<style>.ananimas{cursor:pointer}</style>');

    $('#posts-form').on('click', '.ananimas', function() {
        var post_el = $(this).closest('.post');
        var hadclass = post_el.hasClass('hiclass');
        $('.hiclass').removeClass('hiclass');
        if(hadclass) return;

        var num = post_el.data('num');
        var post = Post(num);
        var posts = post.threadPosts();
        var tmpost = Post(1);
        var name = post.cGetName();

        if(name.indexOf('id="id_tag_') < 0) return;

        for(var i=0;i<posts.length;i++) {
            tmpost.num = posts[i];
            if(!tmpost.isRendered()) continue;
            if(tmpost.cGetName() != name) continue;

            $('#post-body-' + posts[i]).addClass('hiclass');
        }
    });
});
Stage('Подсветка личных постов',                'higlight_myposts', Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(!window.thread.id) return; //не запускаем на нулевой
    if(!Store.get('other.higlight_myposts', true)) return; 
    
    var mark_replies = Store.get('other.higlight_myposts_replies', true)
    var thread = window.thread.id; //вот из-за этого на нулевой не светит :с
    var myposts = Store.get('myposts.' + window.thread.board + '.' + thread, []);
    var today = new Date().toLocaleDateString();
    
    //проверка на старые посты и удаление из myposts, раз в сутки, если чт вынести в глобал
    var checkToDel = function(thread) {
        Post(1)._fetchPosts({thread: thread,from_post: thread, board: window.thread.board}, function(res) {
            if(res.hasOwnProperty('error')) {
                if(res.error == 'server' && res.errorCode == -404) {
                    Store.del('myposts.' + window.thread.board + '.' + thread);
                }
            }
        });
    }
    
    if(!(Store.get('other.check_deleted_myposts') == today)) {
        var mythreads = Store.get('myposts.' + window.thread.board, {});
        for(var thread in mythreads) {
            if (mythreads.hasOwnProperty(thread)) {
                checkToDel(thread);
            }
        }
        Store.set('other.check_deleted_myposts', today);
    }
    
    
    if(myposts.length) markPosts(myposts, mark_replies);
});
Stage('Система лайков',                         'likes',        Stage.DOMREADY,     function(){
    if(!window.thread.board) return; //не запускаем на главной
    if(!window.likes) return; //отключено
    var liked = Store.get('_cache.liked', []);
    var disliked = Store.get('_cache.disliked', []);
    var $postroot = $('#posts-form'); //возможно стоит сделать глобал, часто юзается
    var $like = $('.like-div');
    var $dislike = $('.dislike-div');
    //var neechosee = '<img src="/images/neechosee.png?abu" class="hehe-ne-bolee neechoosee" alt="НИЧОСИ">';
    //var chosee      = '<img src="/images/chosee.png?abu" class="hehe-ne-bolee choosee" alt="ЧОСИ">';

    window.updateLikes = function(posts) { //костыль/10
        for(var i=0;i<posts.length;i++) {
            var post = posts[i];
            if(post.likes) $('#like-count' + post.num).html(post.likes);
            if(post.dislikes) $('#dislike-count' + post.num).html(post.dislikes);
        }
    };

    var like = function(num, el, dislike) {
        var task = dislike?'dislike':'like';
        var store_name = dislike?'_cache.disliked':'_cache.liked';

        var onsuccess = function( data ) {
            if(!data) return $alert('Ошибка лайка: нет ответа');
            //if(data.Error == -2) return $alert('Ошибка лайка: не указан раздел');
            //if(data.Error == -4) return $alert('Ошибка лайка: нет доступа');
            //if(data.Error == -3) return $alert('Ошибка лайка: не указан пост');
            //if(data.Error == -8) return $alert('Вы двачуете слишком часто');
            //if(data.Error == -1) return $alert('Ошибка базы данных');
            //if(data.Status != 'OK') return $alert('Ошибка лайка: неизвестная ошибка<br>' + JSON.stringify(data));
            if(data.Status != 'OK') return $alert(data.Reason);

            liked.push(window.board + num);
            Store.set(store_name, liked);
            renderClicked(el, dislike, num);

            var count_el = $('#' + task + '-count' + num);
            var count = parseInt(count_el.text()) || 0;
            count++;
            count_el.html(count);
        };

        var onerror = function(jqXHR, textStatus) {
            $alert('Ошибка лайка: ' + jqXHR.status + '(' + textStatus + ')');
        };

        $.ajax( '/makaba/likes.fcgi?task=' + task + '&board=' + window.board + '&num=' + num, {
            dataType: 'json',
            timeout: 5000,
            success: onsuccess,
            error: onerror
        });
    };

    var renderClicked = function(el, dislike, num) {
        if(dislike) {
            el.addClass('dislike-div-checked');
            $('#like-div' + num).addClass('dislike-div-checked');
        }else{
            el.addClass('like-div-checked');
            $('#dislike-div' + num).addClass('like-div-checked');
        }
    };

    $like.each(function() {
        var id = this.id.substr(8);
        if(liked.indexOf(window.board + id) < 0) return;
        renderClicked($(this), false, id);
    });

    $dislike.each(function() {
        var id = this.id.substr(11);
        if(disliked.indexOf(window.board + id) < 0) return;
        renderClicked($(this), true, id);
    });

    $postroot.on('click', '.like-div', function() {
        var el = $(this);
        if(el.hasClass('like-div-checked')) return;
        if(el.hasClass('dislike-div-checked')) return;
        like(this.id.substr(8), el);

        //$(document.documentElement).append(neechosee);setTimeout(function() { $('.neechoosee').remove();}, 1000);
    });

    $postroot.on('click', '.dislike-div', function() {
        var el = $(this);
        if(el.hasClass('dislike-div-checked')) return;
        if(el.hasClass('like-div-checked')) return;
        like(this.id.substr(11), el, true);

        //$(document.documentElement).append(chosee); setTimeout(function() { $('.choosee').remove();}, 1000); ;
    });
});
Stage('[debug]Stage controller',                'debug_sc',     Stage.DOMREADY,     function(){
    if(!Store.get('debug', false)) return;
    $('#settings-body').css('overflow','auto').css('height','400px');
    Settings.addCategory('sc_menu', '[debug] Отключение стадий');
    for(var i=0;i<window.sc_stages.length;i++) {
        var id = window.sc_stages[i][0];
        var name = window.sc_stages[i][1];
        var path = 'debug_disable_stage.' + id;

        Settings.addSetting('sc_menu',    path, {
            label: 'Отключить: ' + name,
            default: false
        });
    }
    $('#bmark_debug').append('<b>Total: ' + window.sc_time + 'ms</b><br>');
});

function updatePosts(callback) { //todo временная заплатка
    Post(window.thread.id).download(function( data ) {
        if(data.hasOwnProperty('error')) return callback && callback(data);
        if(!data.list.length && !data.deleted.length) return callback && callback({updated:0, list:[], deleted:[], data: [], favorites:data.favorites});
        var tmpost = Post(1);
        var render_more = Store.get('other.on_delete', 'sync');

        //удаление постов
        if(data.deleted) for(var i=0;i<data.deleted.length;i++) {
            if(render_more == 'sync') {
                $('#post-' + data.deleted[i]).remove();
            }else if(render_more == 'mark') {
                $('#post-body-' + data.deleted[i]).css('background-color', '#FF6666');
            }
        }

        $.each( data.list, function( key, val ) {
            tmpost.num = val;

            appendPost(tmpost.getJSON());
            tmpost.raw().rendered = true;
            
        });
        
        //higlight_myposts
        var myposts = Store.get('myposts.' +  window.thread.board + '.' + window.thread.id, []);
        if(myposts.length) markPosts(myposts, true);

        if(Store.get('other.fav_stats',false) && data.favorites) {
            $('#loice-bar' + window.thread.id)
                .html(data.favorites)
                .removeClass('loice-bar-empty')
                .show();
        }

        if(callback) callback(data);
    });
}
function generatePostBody(post) {
    //если !parseInt(post.parent) то это ОП-пост
    post.comment = post.comment.replace('<script ', '<!--<textarea '); //какой-то УМНИК решил, что будет гениальной идеей в посты вставлять кривой <script> с document.write
    post.comment = post.comment.replace('</script>', '</textarea>-->');

    var postshtml = '';
    var replyhtml = Post(post.num).getReplyLinks();

    postshtml += '<div id="post-details-' + post.num + '" class="post-details">';
    postshtml += '<input type="checkbox" name="delete"  class="turnmeoff" value="' + post.num + '" /> ';
    if(post.subject && parseInt(window.thread.enable_subject)) {
        postshtml += '<span class="post-title">';
        postshtml +=  post.subject + (post.tags?' /'+ post.tags + '/':'');
        postshtml += '</span> ';
    }
    if(post.email) {
        postshtml += '<a href="' + post.email + '" class="post-email">' + post.name + '</a> ';
    }else{
        postshtml += '<span class="ananimas">' + post.name + '</span> ';
    }
    if(post.icon) {
        postshtml += '<span class="post-icon">' + post.icon + '</span>';
    }
    switch (post.trip) {
        case '!!%adm%!!':        postshtml += '<span class="adm">## Abu ##<\/span>'; break;
        case '!!%mod%!!':        postshtml += '<span class="mod">## Mod ##<\/span>'; break;
        case '!!%Inquisitor%!!': postshtml += '<span class="inquisitor">## Applejack ##<\/span>'; break;
        case '!!%coder%!!':      postshtml += '<span class="mod">## Кодер ##<\/span>'; break;
        case '!!%curunir%!!':    postshtml += '<span class="mod">## Curunir ##<\/span>'; break;
        default:                 
                                 if(post.trip_style) {
                                     postshtml += '<span class="' + post.trip_style + '">' + post.trip + '</span>';
                                 } else {
                                     postshtml += '<span class="postertrip">' + post.trip + '<\/span>';
                                 };
    }
    if(post.op == 1) {
        postshtml += '      <span class="ophui"># OP</span>&nbsp;';
    }
    postshtml += '  <span class="posttime">' + (window.correctTZ?window.correctTZ(post.date):post.date) + '&nbsp;</span>';
    postshtml += '  <span class="reflink">';
    postshtml += '<a href="/' + window.thread.board + '/res/' + (parseInt(post.parent)||post.num) + '.html#' + post.num + '">№</a>';
    postshtml += '<a href="/' + window.thread.board + '/res/' + (parseInt(post.parent)||post.num) + '.html#' + post.num + '" class="postbtn-reply-href" id="' + post.num + '">' + post.num + '</a>';
    postshtml += '      <span class="postpanel desktop"> ';
    if(!parseInt(post.parent)) {
        postshtml += '<i title="Добавить в избранное" class="fa fa-star-o postbtn-favorite" data-num="' + post.num + '" id="fa-star' + post.num + '"></i> ';
        postshtml += '<a class="postbtn-exp" href="#" onclick="expandThread(\'' + post.num + '\'); return false;"></a> ';
    }
    postshtml += '          <a class="postbtn-hide" href="#" data-num="' + post.num + '"></a> ';
    postshtml += '          <a href="#" data-num="' + post.num + '" class="postbtn-report" title="Пожаловаться"></a>';
    postshtml += '          <a href="#" data-num="' + post.num + '" class="postbtn-options" title="Опции поста"></a>';
    postshtml += '          <a class="postbtn-adm" style="display:none" href="#" onclick="addAdminMenu(this); return false;" onmouseout="removeAdminMenu(event); return false;"></a>';
    postshtml += '      </span>';
    postshtml += '  </span>';
    if(!parseInt(post.parent)) postshtml += '   <span class="desktop"> [<a class="orange" href="/' + window.thread.board + '/res/' + post.num + '.html">Ответ</a>]</span>';
    if(window.likes) {
        postshtml += '<div id="like-div' + post.num + '" class="like-div">';
        postshtml += '    <span class="like-icon"><i class="fa fa-bolt"></i></span>';
        postshtml += '    <span class="like-caption">Двачую</span>';
        postshtml += '    <span id="like-count' + post.num + '" class="like-count">' + (post.likes>0?post.likes:'') + '</span>';
        postshtml += '</div>';
        postshtml += '<div id="dislike-div' + post.num + '" class="dislike-div">';
        postshtml += '    <span class="dislike-icon"><i class="fa fa-thumbs-down"></i></span>';
        postshtml += '    <span class="dislike-caption">RRRAGE!</span>';
        postshtml += '    <span id="dislike-count' + post.num + '" class="dislike-count">' + (post.dislikes>0?post.dislikes:'') + '</span>';
        postshtml += '</div>';
    }
    postshtml += '  <br class="turnmeoff" />';
    postshtml += '</div>';
    

    if(post.files && post.files.length > 0) {
        postshtml += '<div class="images ' + ((post.files && post.files.length==1)?'images-single':'') + ((post.files && post.files.length>1)?'images-multi':'') + '">';
        var len = post.files.length;
        for(var i=0;i<len;i++) {
            var file = post.files[i];
            var is_webm = file.type == 6;
            var is_sticker = file.type == 100;
            postshtml += '          <figure class="image">';
            postshtml += '              <figcaption class="file-attr">';
            postshtml += '                  <a class="desktop" target="_blank" href="' + (is_sticker?file.install:file.path) + '" title="' + file.fullname + '" id="title-' + post.num + '-' + file.md5 + '">' + file.displayname + '</a>';
            postshtml += '                  <span class="filesize">(' + file.size + 'Кб, ' + file.width + 'x' + file.height + (is_webm?', ' + file.duration:'') + ')</span>';
            postshtml += '              </figcaption>';
            postshtml += '              ';
            postshtml += '              <div id="exlink-' + post.num + '-' + file.md5 + '" class="image-link">';
            postshtml += '                  <a href="' + file.path + '" onclick="expand(\'' + post.num + '-' + file.md5 + '\',\'' +  file.path + '\',\'' + file.thumbnail + '\',' + file.width + ',' + file.height + ',' + file.tn_width + ',' + file.tn_height + ',' + 0 + ',' + is_sticker + '); return false;">';
            postshtml += '                      <img src="' + file.thumbnail + '" width="' + file.tn_width + '" height="' + file.tn_height + '" alt="' + file.size + '" class="img preview' + (is_webm?' webm-file':'') + '" />';
            postshtml += '                  </a>';
            postshtml += '              </div>';
            postshtml += '          </figure>';
        }
        postshtml += '</div>';
    } else if(post.video) {
        postshtml += '      <div style="float: left; margin: 5px; margin-right:10px">';
        postshtml += '          ' + post.video;
        postshtml += '      </div>';
    }
    postshtml += '<blockquote id="m' + post.num + '" class="post-message">';
    postshtml += post.comment;
    if(post.banned == 1) postshtml += '         <br/><span class="pomyanem">(Автор этого поста был забанен. Помянем.)</span>';
    else if(post.banned == 2) postshtml += '    <br/><span class="pomyanem">(Автор этого поста был предупрежден.)</span>';
    postshtml += '</blockquote>';
    postshtml += '<div id="refmap-' + post.num + '" class="ABU-refmap" style="' + (replyhtml?'':'display: none;') + '"><em>Ответы: </em>' + replyhtml + '</div>';

    return postshtml;
}
function appendPost(post) {
    //todo перенести в ООП
    if(!post.hasOwnProperty('num')) return false; //это какой-то неправильный пост
    if($('#post-' + post.num).length) return false;
    var postshtml = '';

    postshtml += '<div id="post-' + post.num + '" class="post-wrapper">';
    postshtml += '<div class="reply post" id="post-body-' + post.num + '" data-num="' + post.num + '">';
    postshtml += generatePostBody(post);
    postshtml += '</div>';
    postshtml += '</div>';

    $('#thread-' + (parseInt(post.parent) || post.num)).append(postshtml);

    Media.processLinks($('#post-' + post.num + ' a'));
    if(window._hide_by_rules) window._hide_by_rules($('#post-body-' + post.num));
    if(window._hide_by_list) window._hide_by_list(post.num);
    
    News.getdata();
    linkremover();//linkremover
    linkUpdater(); //@todo wtf

    return true;
}
function appendThread(thread) {
    //todo перенести в ООП
    var postshtml = '';

    postshtml += '<div id="thread-' + thread.thread_num + '" class="thread">';
    postshtml += '<div id="post-' + thread.thread_num + '" class="oppost-wrapper">';
    postshtml += '<div class="post oppost" id="post-body-' + thread.thread_num + '" data-num="' + thread.thread_num + '">';
    postshtml += generatePostBody(thread.posts[0]);
    postshtml += '</div>';
    
    postshtml += '<div class="oppost-options-mob mobile">'
    postshtml += '<span class="mess-post-mob"><strong>Пропущено ' + thread.posts_count + ' постов</strong><br>' + (thread.files_count?'' + thread.files_count + ' с картинками.':'') + '</span>';
    postshtml += '<div class="hide-view"><a href="/' + window.board + '/res/' + thread.thread_num + '.html" class="button-mob">В тред</a><a class="button-mob postbtn-hide-mob" data-num="' + thread.thread_num + '">Скрыть</a></div>'
    postshtml += '</div>';
    
    postshtml += '<span class="mess-post desktop">Пропущено ' + thread.posts_count + ' постов' + (thread.files_count?', ' + thread.files_count + ' с картинками':'') + '. Нажмите <a href="/' + window.board + '/res/' + thread.thread_num + '.html">ответ</a>, чтобы посмотреть.</span>';
    postshtml += '</div>';
    postshtml += '</div>';
    postshtml += '<hr>';

    $('#posts-form').append(postshtml);

    Media.processLinks($('#post-' + thread.thread_num + ' a'));
    $('#thread-' + thread.thread_num + ' .post-message').each(function(){
        window._hide_long_post($(this));
    });
    if(window._hide_by_rules) window._hide_by_rules($('#post-body-' + thread.thread_num));
    if(window._hide_by_list) window._hide_by_list(thread.thread_num);

    return true;
}
function updateThread() {
    //функция обновления треда, которая вызывается по кнопке "обновить тред"
    $alert('Загрузка...', 'wait');

    updatePosts(function(data) {
        $close($id('ABU-alert-wait'));

        if(data.updated) $alert('Новых постов: ' + data.updated);
        else if(data.error) $alert('Ошибка: ' + data.errorText);
        else $alert('Нет новых постов');

        if(Favorites.isFavorited(window.thread.id)) Favorites.setLastPost(data.data, window.thread.id);
    });
    
    News.getdata();
    linkremover();//linkremover
    linkUpdater(); //@todo wtf
    
}
function requestCaptchaKey2ch(callback) {

    var userCode = getCookie('passcode_auth');
    
    var url;
    url = '/api/captcha/2chaptcha/id?board=' + window.thread.board + '&thread=' + window.thread.id;
    var abort = false;

    var abortTimer = setTimeout(function(){
        abort = true;
        if(callback) callback('Превышен интервал ожидания');
    }, window.config.loadCaptchaTimeout);

    $.get(url, function( data ) {
        if(abort) return false;
        clearTimeout(abortTimer);

        if(data['warning']) return callback({ warning: data['warning']});
        else if(data['banned']) return callback({ banned: data['banned']});
        else if(data['result'] == 0) return callback('VIPFAIL');
        else if(data['result'] == 2) return callback('VIP');
        else if(data['result'] == 0) return callback('SQLFAIL');
        else if(data['result'] == 3) return callback('DISABLED');
        else if(data['result'] == 1) return callback({key: data['id']});
        else return callback(data);
    })
        .fail(function(jqXHR, textStatus) {
            if(abort) return false;
            clearTimeout(abortTimer);
            if(callback) callback(textStatus);
        });
}
function loadCaptcha2ch() {
    var dead = false; //ТЕХРАБОТЫ 
    if(dead) {
        generateWarning('dead');
    }
    
    requestCaptchaKey(function(data){
        if(!data.key) {
            if(data.warning) {
                generateWarning('warning', data.warning, function() {
                    $("#warningponyal").click(function(){
                        $.get('/api/captcha/message', function() {
                            loadCaptcha();
                        })
                        return false;
                    });
                });
            }else if(data.banned) {
                generateWarning('banned', data.banned, function() {
                    delCookie('op_' + window.board + '_' + window.thread.id); //??WTF
                }); 
            }else if(data == 'VIP') {
                $('.captcha-box').html('Вам не нужно вводить капчу, у вас введен пасс-код.');
                Store.set('renewneeded',0);
            }else if(data == 'VIPFAIL') {
                $('.captcha-box').html('Ваш пасс-код не действителен, пожалуйста, перелогиньтесь. <a href="#" id="renew-pass-btn">Обновить</a>');
                Store.set('renewneeded',1);
            }else if(data == 'DISABLED') {
                $('.captcha-box').html('');
                $('.captcha-row').hide();
            }else{
                $('.captcha-image').html(data);
            }
        }else{
            $('.captcha-image').html('<img src="/api/captcha/2chaptcha/image/' + data.key + '">');
            $('input[name="captcha_type"]').val('2chaptcha');
            $('input[name="2chaptcha_id"]').val(data.key);

        }
    });
    renewPass();
}

function requestCaptchaKeyAnimedaun(callback) {
    
    var userCode = getCookie('passcode_auth');
    
    url = '/api/captcha/animecaptcha/id?board=' + window.thread.board + '&thread=' + window.thread.id;

    var abort = false;
    

    var abortTimer = setTimeout(function(){
        abort = true;
        if(callback) callback('Превышен интервал ожидания');
    }, window.config.loadCaptchaTimeout);
    
    $.ajaxSetup({xhrFields: { withCredentials: true } });

    $.get(url, function( data ) {
        if(abort) return false;
        clearTimeout(abortTimer);

        if(data['result'] == 0) return callback('VIPFAIL');
        else if(data['result'] == 2) return callback('VIP');
        else if(data['result'] == 0) return callback('SQLFAIL');
        else if(data['result'] == 3) return callback('DISABLED');
        else if(data['result'] == 1) return callback({key: data['id'], values: data['values']});
        else return callback(data);
    })
        .fail(function(jqXHR, textStatus) {
            if(abort) return false;
            clearTimeout(abortTimer);
            if(callback) callback(textStatus);
        });
}
function loadCaptchaAnimedaun() {
    requestCaptchaKey(function(data){
        var html = '';
        if(!data.key) {
            if(data == 'VIP') {
                $('.captcha-box').html('Вам не нужно вводить капчу, у вас введен пасс-код.');
                Store.set('renewneeded',0);
            }else if(data == 'VIPFAIL') {
                $('.captcha-box').html('Ваш пасс-код не действителен, пожалуйста, перелогиньтесь. <a href="#" id="renew-pass-btn">Обновить</a>');
                Store.set('renewneeded',1);
            }else if(data == 'DISABLED') {
                $('.captcha-box').html('');
                $('.captcha-row').hide();
            }else{
                $('.captcha-image').html(data);
            }
        }else{
            $('.captcha-box').addClass('animedaun');
            $('.captcha-image').html('<img src="/api/captcha/animecaptcha/image/' + data.key + '">');
            if($('.captcha-radiogr').length) {
                $('.captcha-radiogr').html('');
            } else {
                $('.captcha-box').append('<div class="captcha-radiogr"></div>');
            }
            
            for(var i in data.values) {
                html += '<label><input type="radio" name="animeGroup" value="' + data.values[i]['id'] + '">' + data.values[i]['name'] + '</label><br>';
            }
            $('.captcha-radiogr').html(html);
            $('input[name="captcha_type"]').val('animacaptcha');
            $('#captcha-value, #qr-captcha-value').remove();

        }
    });
    renewPass();
}

function showQrForm(qr_box) {
    if(!qr_box) qr_box = $('#qr');
    if(Store.get('styling.qr.disable', false)) return;
    if(Store.get('styling.qr.disable_if_postform', false) && $('#postform').is(':visible')) return;

    qr_box.show();
    loadCaptcha();
}
function insert(myValue) {
    //переписанный insert
    var form = window.activeForm;
    var area = form[0];

    var $qr_form = $('#qr-shampoo');
    var qr_area = $qr_form[0];
    var $qr_box = $('#qr');

    var $win = $(window);

    if(!$qr_box.is(':visible')) {
        if(($win.width() >= 480 && $win.height() >= 480) || !Store.get('mobile.hide_qr',false)) {
            showQrForm($qr_box);
        }
    }

    if (document.selection) { // IE
        qr_area.focus();
        var sel = document.selection.createRange();
        sel.text = myValue;
        qr_area.focus();
    } else if (area.selectionStart || area.selectionStart == '0') { // Real browsers
        var startPos = area.selectionStart;
        area.selectionStart = 0;
        //var scrollTop = area.scrollTop;
        //area.value = area.value.substring(0, startPos) + myValue + area.value.substring(endPos, area.value.length);
        qr_area.value = area.value.substring(0, startPos) + myValue + area.value.substring(startPos);
        qr_area.focus();
        qr_area.selectionStart = startPos + myValue.length;
        qr_area.selectionEnd = startPos + myValue.length;
        //area.scrollTop = scrollTop;
    } else {
        qr_area.value += myValue;
        qr_area.focus();
    }

    $qr_form.keyup();
}
function getTimeInDays() {
    return Math.ceil((+new Date)/1000/60/60/24);
}
function renderStore() {
    $('#name').val(Store.get('thread.postform.name',''));

    var email = Store.get('thread.postform.email','');
    $('#qr-e-mail,#e-mail').val(email);
    $('#sagecheckbox').prop('checked', (email=='sage'));

    var watermark = !!Store.get('thread.postform.watermark',false);
    $('#makewatermark').prop('checked', watermark);

    var icon = Store.get('thread.postform.icon.' + window.thread.board, false);
    if(icon) $('.anoniconsselectlist').val(icon);

    if(!window.thread.id) return false;

    var autorefresh = !!Store.get('thread.autorefresh',false);
    var $autorefresh_el = $('.autorefresh-checkbox');
    $autorefresh_el.prop('checked', autorefresh);
    if(autorefresh) autorefresh_start();
}
function expandThread(tNum, callback) { //todo перенести в ООП наверное. А может и нет.
    var post = Post(tNum);
    var posts = post.threadPosts();
    var $expanded_posts = $('#expanded-posts' + tNum);

    var proceed = function() {
        var tmp = Post(1);
        var $elThread = $('#thread-' + tNum);
        var expanded_posts_el = $('<span id="expanded-posts' + tNum + '"></span>');
        var posts_el = $elThread.find('.post-wrapper');
        var last_posts_count = posts_el.length;
        $elThread.append(expanded_posts_el);

        for(var i=0;i<posts.length;i++) {
            tmp.num = posts[i];

            if(tmp.isThread()) continue;
            if(tmp.isNotFound()) continue;
            if(tmp.isRendered()) {
                expanded_posts_el.append( tmp.el() );
            }else{ //todo переписать этот пиздец на ООП
                var postshtml;

                postshtml = '<div id="post-' + tmp.num + '" class="post-wrapper">';
                postshtml += '<div class="reply post" id="post-body-' + tmp.num  + '" data-num="' + tmp.num  + '">';
                postshtml += generatePostBody(tmp.getJSON());
                postshtml += '</div>';
                postshtml += '</div>';

                expanded_posts_el.append( postshtml );
                Media.processLinks($('#post-' + tmp.num).find('a'));

                var te = tmp.raw();
                te.rendered = true;
            }
        }

        var last_posts = expanded_posts_el.find('.post-wrapper').slice(-last_posts_count);
        $elThread.append(last_posts);

        $elThread.find('.mess-post, .mess-post-mob').remove();
        if(callback) callback();
    };

    if($expanded_posts.length) return $expanded_posts.toggle();
    if(!post.isThreadPreloaded()) {
        $alert('Загрузка...', 'wait');
        post.download(function(res){
            $close($id('ABU-alert-wait'));
            if(res.hasOwnProperty('errorText')) return $alert('Ошибка: ' + res.errorText);

            proceed();
        });
    }else{
        proceed();
    }
}
function scrollToPost(num) {
    //$('html, body').animate({ scrollTop: $('#post-' + num).offset().top }, 'slow');
    $(document).scrollTop($('#post-' + num).offset().top);
}
function escapeHTML(str) {
    return (str+'')
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function draggable_qr(id, pos) {
    //функция плавающих окон с записыванием их положения в хранилище
    var inDrag = false;
    var lastCursorX = 0;
    var lastCursorY = 0;
    var cursorInBoxPosX = 0;
    var cursorInBoxPosY = 0;

    var $form = $('#' + id);
    var formX = 0;
    var formY = 0;

    var moveForm = function(x, y) {
        var win = $(window);
        var windowWidth = win.width();
        var windowHeight = win.height();
        var formWidth = $form.innerWidth();
        var formHeight = $form.innerHeight();

        if(x+formWidth > windowWidth) x = windowWidth-formWidth;
        if(y+formHeight > windowHeight) y = windowHeight-formHeight;
        if(x<0) x = 0;
        if(y<0) y = 0;


        $form.css('top', y + 'px');
        $form.css('left', x + 'px');

        formX = x;
        formY = y;
    };

    $('#' + id + '-header').mousedown(function(e){
        e.preventDefault();

        var win = $(window);
        lastCursorX = e.pageX - win.scrollLeft();
        lastCursorY = e.pageY - win.scrollTop();

        cursorInBoxPosX = lastCursorX-formX;
        cursorInBoxPosY = lastCursorY-formY;

        inDrag = true;
    });

    $(document).mousemove(function(e){
        if(!inDrag) return;
        var win = $(window);
        var mouseX = e.pageX - win.scrollLeft();
        var mouseY = e.pageY - win.scrollTop();
        lastCursorX = mouseX;
        lastCursorY = mouseY;

        moveForm(mouseX-cursorInBoxPosX, mouseY-cursorInBoxPosY);
    });

    $(document).mouseup(function(){
        if(!inDrag) return;

        Store.set('styling.' + id + '.x', formX);
        Store.set('styling.' + id + '.y', formY);

        inDrag = false;
    });

    $( window ).resize(function(){
        moveForm(formX, formY);
    });

    var win = $(window);

    //обернул для багфикса (иначе страница не успеет отрендериться)
    $(function(){
        var store_x = Store.get('styling.' + id + '.x', false);
        var store_y = Store.get('styling.' + id + '.y', false);

        if(typeof(store_x) == 'number' && typeof(store_y) == 'number') {
            moveForm(store_x, store_y);
        }else{
            if(pos == 'center') {
                moveForm((win.width()-$form.width())/2, Math.floor(win.height()/3-$form.height()/2));
            }else{
                moveForm(win.width()-$form.width(), Math.floor(win.height()/3-$form.height()/2));
            }

        }
    });
}
function draggable(el, events) {
    var in_drag = false;
    var moved = 0;
    var last_x, last_y;

    var win = $(window);

    el.mousedown(function(e){
        if(e.which != 1) return;
        if(events && events.mousedown && events.mousedown(e.clientX, e.clientY) === false) return;
        e.preventDefault();
        in_drag = true;
        moved = 0;

        last_x = e.clientX;
        last_y = e.clientY;
    });

    win.mousemove(function(e){
        var delta_x, delta_y;
        var el_top, el_left;

        if(!in_drag) return;

        delta_x = e.clientX-last_x;
        delta_y = e.clientY-last_y;
        moved += Math.abs(delta_x) + Math.abs(delta_y);

        last_x = e.clientX;
        last_y = e.clientY;

        el_top = parseInt(el.css('top'));
        el_left = parseInt(el.css('left'));

        el.css({
            top: (el_top+delta_y) + 'px',
            left: (el_left+delta_x) + 'px'
        });
    });

    win.mouseup(function(e) {
        if(!in_drag) return;
        in_drag = false;
        if(moved < 6 && events && events.click) events.click(last_x, last_y);
    });
}

function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}
function getReadableFileSizeString(fileSizeInBytes) {
    var i = -1;
    var byteUnits = ['Кб', 'Мб', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    do {
        fileSizeInBytes = fileSizeInBytes / 1024;
        i++;
    } while (fileSizeInBytes > 1024);

    return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
}

function oekakiInit(w,h) {
    $('.qr-oekaki').show();
    $('.qr-oekaki-body').width(parseInt(w) + 61); //467
    $('.qr-oekaki-body').height(parseInt(h) + 31); //461   (-24, когда min-height auto)
    lc = LC.init($('#qr-oekaki-body').get(0), {
        imageURLPrefix: '/makaba/templates/js/lcanvas/img',
        backgroundColor: '#fff',
        imageSize: {width: w, height: h},
    });
    return lc;
}

//warning
function generateWarning(type, data, callback) {
    var body;
    var buttons;
    var head = '<div class="warningcover"></div><div class="warningbox">';
    var audio = '<audio loop autoplay><source src="/makaba/templates/img/monkey.mp3?1" type="audio/mpeg" ></audio>'
    if(type=='warning') {
        buttons = '<a href="#" id="warningponyal">Я понел(((</a>';
        body    = '<div><img src="/makaba/templates/img/makaka.gif" alt="tsok tsok tsok tsok tsok tsok..."></div>' +
                  '<div>' + decodeURIComponent(data['message']) + ' За этот пост <a href="' + data['path'] + '" target="_blank" >это</a></div>' + audio;
    }else if(type=='banned') {
        buttons = '<a href="#" id="warningponyal">Я понел(((</a>';
        body    = '<div><img src="/makaba/templates/img/makaka.gif" alt="tsok tsok tsok tsok tsok tsok..."></div>' +
                  '<div>' + data['message'] + 'Вот за <a href="' + data['path'] + '" target="_blank" >это</a></div>' +
                  '<div>Купить пасскод и получить мгновенный разбан можно <a href="/market.html" target="_blank">тут</a></div>' + audio;
    }else if(type=='agebox') {
        buttons = '<a href="#" id="ageboxallow">Я согласен и подтверждаю, что мне есть 18 лет</a><br><a  id="ageboxdisallow" href="/">Уйти отсюда</a>';
        body    = '<span>Получая доступ ко взрослым разделам Двача вы осознаете и соглашаетесь со следующими пунктами:<ul><li>Содержимое этого сайта предназначено только для лиц, достигших совершеннолетия. Если вы несовершеннолетний, покиньте эту страницу.</li>' +
                  '<li>Сайт предлагается вам "как есть", без гарантий (явных или подразумевающихся). Нажав на "Я согласен", вы соглашаетесь с тем, что  Двач не несет ответственности за любые неудобства, которые может понести за собой использование вами сайта, ' +
                  'а также что вы понимаете, что опубликованное на сайте содержимое не является собственностью или созданием Двача, однако принадлежит и создается пользователями Двача.</li>' +
                  '<li>Существенным условием вашего присутствия на сайте в качестве пользователя является согласие с "Правилами" Двача, ссылка на которые представлена на главной странице. Пожалуйста, прочтите <a href="/rules.html" target="_blank">Правила</a> ' +
                  'внимательно, так как они важны.</li></ul></span>';
    }else if(type=='unban') {
        buttons = '<a  id="" href="/">Уйти отсюда</a>';
        body    = '<div class="warning-header">Реквест разбана</div>';
        body   += '<div class="unban-warning">';
        body   += '<div class="unban-warning-left">';
        body   += '<input id="unban-ban-num-input" value="" autocomplete="off" type="text" placeholder="Номер бана">';
        body   += '<textarea rows="2" cols="45" id="unban-comment-input"  placeholder="Замечательная история получения бана"></textarea>';
        body   += '<div><input name="2chaptcha_id" value="" type="hidden" id="unban-captcha-val"><div id="unban-captcha-div"></div><label for="unban-ban-num-input">Введите капчу:</label>' +
                  '<input type="text" id="unban-captcha-input" value="" autocomplete="off"/></div>' +
                  '<input onclick="UnbanSubmit(); return false;" value="Отправить запрос" type="submit">';
        body   += '</div>';
        body   += '<div class="unban-warning-right">';
        body   += 'Нет надежды на кровавую модерацию? Устал ждать разбана? Просто купи разбан всего за 149.99р! <br>';
        body   += '<input id="unban-ban-num-input-buy" value="" autocomplete="off" placeholder="EMAIL|номер бана" type="text"><input style="" value="Замолить грехи" id="unban-buy-submit" type="submit">';
        body   += '</div>';
        body   += '</div>';
        
    }else if(type=='dead') {
        buttons = '<a href="#" id="warningponyal">Я понел(((</a>';
        body    = '<div><img src="/makaba/templates/img/makaka.gif" alt="tsok tsok tsok tsok tsok tsok..."></div>' +
                  '<div>У нас небольшие техработы, постинг будет доступен через 10 минут.</div>';
    }
    var foot = '<div class="warningboxbutton">' + buttons + '</div></div>';
    
    var output = head + body + foot;
    $('.makaba').append(output);
    $("#warningponyal").click(function(){
        $('.warningcover').add('.warningbox').remove();
        return false;
    });
    if(callback) callback();
    return false;
}

//higlight_myposts
function markPosts(posts,mark_replies) {
    for(var i=0;i<posts.length;i++) {
        var post = posts[i];
        try {//обработка возможно удаленных постов
            var replies = Post(post).getReplies();
            Post(post).highlight_myposts();
            
            if(mark_replies) {
                for(var j=0;j<replies.length;j++) {
                    Post(replies[j]).highlight_myposts_replies();
                }
            };
        }
        catch(err) {
            console.log(post + ' has gone!');
        }
    }
}

//cookie funcs
function getCookie(name){
    with(document.cookie) {
        var regexp = new RegExp('(^|;\\s+)' + name + '=(.*?)(;|$)');
        var hit = regexp.exec(document.cookie);

        if(hit && hit.length > 2) return unescape(hit[2]);
        else return null;
    }
}
function getSCookie(cname) {
    var name = cname;// + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(c.indexOf('=') + 1,c.length);
        }
    }
    return null;
} 

function setCookie(key, value, days) {
    if(days)
    {
        var date=new Date();
        date.setTime(date.getTime() + days*24*60*60*1000);
        var expires = '; expires=' + date.toGMTString();

    }
    else expires = '';

    document.cookie = key + '=' + value + expires + '; path=/';
}

function delCookie(key) {
    document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
    return !getCookie(key);
}

//some newakaba funcs
function $alert(txt, id){
    var el, nid = 'ABU-alert';

    if(id)  {
        nid += '-' + id;
        el = $id(nid);
    }

    if(!el) {
        el = $new('div',
        {
            'class': 'reply',
            'id': nid,
            'style':
            'float:right; clear:both; opacity:0; width:auto; min-width:0; padding:0 10px 0 10px;' +
            ' margin:1px; overflow:hidden; white-space:pre-wrap; outline:0; border:1px solid grey'
        });

        if(id == 'wait') el.appendChild($new('span', {'class': 'ABU-icn-wait'}));
        el.appendChild($new('div', {'style': 'display:inline-block; margin-top:4px'}));
        $show($id('ABU-alertbox').appendChild(el));
    }

    $t('div', el)[0].innerHTML = txt;

    if(id != 'wait') setTimeout(function(){
        $close(el);
    }, 6000);
}
function $id(id) {
    return document.getElementById(id);
}
function $n(id) {
    return document.getElementsByName(id)[0];
}
function $t(id, root) {
    return (root || document).getElementsByTagName(id);
}
function $c(id, root) {
    return (root || document).getElementsByClassName(id);
}
function $each(arr, fn) {
    for(var el, i = 0; el = arr[i++];)
        fn(el);
}
function $html(el, htm) {
    var cln = el.cloneNode(false);
    cln.innerHTML = htm;
    el.parentNode.replaceChild(cln, el);
    return cln;
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
function $before(el, nodes) {
    for(var i = 0, len = nodes.length; i < len; i++)
        if(nodes[i]) el.parentNode.insertBefore(nodes[i], el);
}
function $after(el, nodes) {
    var i = nodes.length;

    while(i--) if(nodes[i]) el.parentNode.insertBefore(nodes[i], el.nextSibling);
}
function $new(tag, attr, events) {
    var el = document.createElement(tag);

    if(attr) $attr(el, attr);

    if(events) $event(el, events);

    return el;
}
function $disp(el) {
    el.style.display = el.style.display == 'none' ? '' : 'none';
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
function $close(el) {
    if(!el) return;

    var h = el.clientHeight - 18;
    el.style.height = h + 'px';
    var i = 8;
    var closing = setInterval(function() {
        if(!el || i-- < 0) {
            clearInterval(closing);
            $del(el);
            return;
        }

        var s = el.style;
        s.opacity = i/10;
        s.paddingTop = parseInt(s.paddingTop) - 1 + 'px';
        s.paddingBottom = parseInt(s.paddingBottom) - 1 + 'px';
        var hh = parseInt(s.height) - h/10;
        s.height = (hh < 0 ? 0 : hh) + 'px';
    }, 35);
}
function $show(el) {
    var i = 0;
    var showing = setInterval(function(){
        if(!el || i++ > 8) {
            clearInterval(showing);
            return;
        }

        var s = el.style;
        s.opacity = i/10;
        s.paddingTop = parseInt(s.paddingTop) + 1 + 'px';
        s.paddingBottom = parseInt(s.paddingBottom) + 1 + 'px';
    }, 35);
}
function _disabled_insert(txt) {
    var el = document.forms.postform.shampoo;

    if(el) {
        if(el.createTextRange && el.caretPos) {
            var caretPos = el.caretPos;
            caretPos.txt = caretPos.txt.charAt(caretPos.txt.length-1) == ' ' ? txt + ' ' : txt;

        }
        else if(el.setSelectionRange) {
            var start = el.selectionStart;
            var end = el.selectionEnd;
            el.value = el.value.substr(0,start) + txt + el.value.substr(end);
            el.setSelectionRange(start + txt.length, start + txt.length);

        }
        else el.value += txt + ' ';

        el.focus();
    }
}

//обновление пасса
function renewPass() {
    var renewusercode = Store.get('jsf34nfk3jh');
    var renewneeded   = Store.get('renewneeded');
    if (typeof renewusercode != 'undefined' && renewneeded == 1) {
        $('#renew-pass-btn').show();
        var renewdata = {task: 'auth',usercode: renewusercode}
        $(document).on('click', '#renew-pass-btn', function(e) {
            $.ajax({
                url: '/makaba/makaba.fcgi',
                dataType: 'text',
                type: 'post',
                contentType: 'multipart/form-data',
                data: renewdata,
                success: function( data, textStatus, jQxhr ){
                    $('.captcha-box').html('Пасскод успешно обновлен!');
                    console.log(data);
                },
                error: function( jqXhr, textStatus, errorThrown ){
                    console.log( errorThrown );
                }
            });
            e.preventDefault();
        });
    Store.set('renewneeded',0);
    }
    //  
}

//раскрытие всех пикч в тредю
function expandAllPics() { //@todo пздц
    window.expand_all_img = true;
    var Pic = document.getElementsByClassName('image-link');
    
    for(var i = 0; i < Pic.length; i++)
    {
        if(Pic[i].getElementsByTagName("img")[0].className.indexOf("webm-file")==12) {
            continue;
        } else {
            Pic[i].getElementsByTagName('a')[0].click();
        }
    }
    delete window.expand_all_img;
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

function webmPlayStarted(el) {
    var video = $(el).get(0);
    video.volume = Store.get('other.webm_vol', 0);
}

function webmVolumeChanged(el) {
    var video = $(el).get(0);
    var vol = video.volume;
    if(video.muted) vol = 0;

    Store.set('other.webm_vol', vol);
}

function ToggleSage() {
    if($("#e-mail").val() == "sage"){
        $("#e-mail").val('');
        $("#sagecheckbox").prop('checked', false);;
    }else {
        $("#e-mail").val('sage');
        $("#sagecheckbox").prop('checked', true);;
    }
}



//разбаны. переписать на stage и этот способ получения капчки убрать
function GetCaptcha(CaptchaDiv, PostFormCaptcha) {
    var Resp = '';
    var Url = '/api/captcha/2chaptcha/service_id';
    $.ajax({
        url: Url,
        dataType : "json",
        timeout: 20000,
        async: false,
        success: function(data) {
            Resp = data;
        }
    });
    console.log(Resp.id);
    if(Resp['result'] == 0) {
        Resp = '<span class=\"captcha-notif\">Ваш пасс-код не действителен, пожалуйста, перелогиньтесь..</span>';
    } else if(Resp['result'] == 2) {
        Resp = '<span class=\"captcha-notif\">Вам не нужно вводить капчу, у вас введен пасс-код.</span>';
    } else if(Resp['result'] == 1) {
        var Key = Resp['id'];
        $('#unban-captcha-val').val(Key);
        Resp = '<img src="/api/captcha/2chaptcha/image/' + Key + '">';
    } else {
        Resp = '<p>'+Resp+'</p>';
    }

    if(CaptchaDiv == '') {
        //document.write(Resp);
    } else {
        $id(CaptchaDiv).innerHTML = Resp;
    }
}


function UnbanHide(ids){
    $(ids).parent().hide();
}

function UnbanShow(){
    $('#unban-div').css("display","");
    GetCaptcha('unban-captcha-div', false);
    $("#unban-div").on( 'keyup', '#ShpEmail-mail, #ShpEmail-bannum', function () {
        $('.unban-buy #account').val( 'email=' + $('#ShpEmail-mail').val() + '&type=passcode&ban=' + $('#ShpEmail-bannum').val() );
    })
    .change();
}

function UnbanSubmit(){
    var HashArray = new Object();
    HashArray['task'] = 'unban';
    HashArray['2chaptcha_id'] = $('#unban-captcha-val').val();
    HashArray['captcha_type'] = '2chaptcha';
    HashArray['2chaptcha_value'] = $('#unban-captcha-input').val();
    HashArray['ban_num'] = $('#unban-ban-num-input').val();
    HashArray['request'] = $('#unban-comment-input').val();

    var Multipart = '--AaB03x';
    for(var k in HashArray)
    {
        if(HashArray.hasOwnProperty(k))
        {
            Multipart += '\r\nContent-Disposition: form-data; name="' + k + '"\r\n\r\n' + HashArray[k] + '\r\n--AaB03x';
        }
    }
    Multipart += '--\r\n';

    $.ajax(
    {
        type: "POST",
        url: '/makaba/makaba.fcgi',
        data: Multipart,
        dataType : "html",
        async: false,
        contentType: 'multipart/form-data; charset=UTF-8; boundary=AaB03x',
        success: function(data, status)
        {
            try
            {
                var JSONData = $.parseJSON(data);
                if(JSONData.Error == null)
                {
                    alert(JSONData.Result);
                }
                else
                {
                    alert(JSONData.Error);
                }
            }
            catch(e) {}
        },
        error: function(xhr, desc, err)
        {
            alert('Ошибка соединения!');
        }
    });
    $('#unban-div').hide();
    $('#unban-form')[0].reset();
}


//toolbar
var ToolbarTextarea;
function _disables_edToolbar(obj) {
    document.write("<span class=\"m-bold\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/bold.png\" title=\"Жирный\" onClick=\"doAddTags('[b]','[/b]','" + obj + "')\"></span>");
    document.write("<span class=\"m-italic\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/italic.png\" title=\"Наклонный\" onClick=\"doAddTags('[i]','[/i]','" + obj + "')\"></span>");
    document.write("<span class=\"m-quote\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/quote1.png\" title=\"Цитирование\" onClick=\"doAddTags('>','','" + obj + "')\"></span>");
    document.write("<span class=\"m-underline\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/underline.png\" title=\"Нижнее подчёркивание\" onClick=\"doAddTags('[u]','[/u]','" + obj + "')\"></span>");
    document.write("<span class=\"m-overline\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/overline.png\" title=\"Верхнее подчёркивание\" onClick=\"doAddTags('[o]','[/o]','" + obj + "')\"></span>");
    document.write("<span class=\"m-spoiler\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/spoiler.png\" title=\"Спойлер\" onClick=\"doAddTags('[spoiler]','[/spoiler]','" + obj + "')\"></span>");
    document.write("<span class=\"m-strike\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/strike.png\" title=\"Зачёркнутый\" onClick=\"doAddTags('[s]','[/s]','" + obj + "')\"></span>");
    document.write("<span class=\"m-sup\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/sup.png\" title=\"Сдвиг текста вверх\" onClick=\"doAddTags('[sup]','[/sup]','" + obj + "')\"></span>");
    document.write("<span class=\"m-sub\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/sub.png\" title=\"Сдвиг текста вниз\" onClick=\"doAddTags('[sub]','[/sub]','" + obj + "')\"></span>");
    document.write("<br>");
}
function edToolbar(obj) {
    var ret = '';
    ret += ("<span class=\"m-bold\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/bold.png\" title=\"Жирный\" onClick=\"doAddTags('[b]','[/b]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-italic\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/italic.png\" title=\"Наклонный\" onClick=\"doAddTags('[i]','[/i]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-quote\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/quote1.png\" title=\"Цитирование\" onClick=\"doAddTags('>','','" + obj + "')\"></span>");
    ret += ("<span class=\"m-underline\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/underline.png\" title=\"Нижнее подчёркивание\" onClick=\"doAddTags('[u]','[/u]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-overline\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/overline.png\" title=\"Верхнее подчёркивание\" onClick=\"doAddTags('[o]','[/o]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-spoiler\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/spoiler.png\" title=\"Спойлер\" onClick=\"doAddTags('[spoiler]','[/spoiler]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-strike\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/strike.png\" title=\"Зачёркнутый\" onClick=\"doAddTags('[s]','[/s]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-sup\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/sup.png\" title=\"Сдвиг текста вверх\" onClick=\"doAddTags('[sup]','[/sup]','" + obj + "')\"></span>");
    ret += ("<span class=\"m-sub\"><img class=\"markup\" src=\"/icons/markup_buttons/photon/sub.png\" title=\"Сдвиг текста вниз\" onClick=\"doAddTags('[sub]','[/sub]','" + obj + "')\"></span>");
    ret += ("<br>");
    return ret;
}
function doAddTags(tag1,tag2,obj) {
    ToolbarTextarea = $id(obj);
    if (document.selection)
    {
        var sel = document.selection.createRange();
        sel.text = tag1 + sel.text + tag2;
    }
    else
    {
        var len = ToolbarTextarea.value.length;
        var start = ToolbarTextarea.selectionStart;
        var end = ToolbarTextarea.selectionEnd;
        var scrollTop = ToolbarTextarea.scrollTop;
        var scrollLeft = ToolbarTextarea.scrollLeft;
        var sel = ToolbarTextarea.value.substring(start, end);
        var rep = tag1 + sel + tag2;

        ToolbarTextarea.value =  ToolbarTextarea.value.substring(0,start) + rep + ToolbarTextarea.value.substring(end,len);
        ToolbarTextarea.scrollTop = scrollTop;
        ToolbarTextarea.scrollLeft = scrollLeft;
        ToolbarTextarea.focus();
        ToolbarTextarea.setSelectionRange(start+tag1.length, end+tag1.length);
    }

    $('#' + obj).keyup();
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

//arch 
if(location.pathname.split(/\//)[2]=='arch') {
    (function(){var f=false,b=document,c=b.documentElement,e=window;function g(){var a="";a+="rt="+(new Date).getTime()%1E7*100+Math.round(Math.random()*99);a+=b.referrer?"&r="+escape(b.referrer):"";return a}function h(){var a=b.getElementsByTagName("head")[0];if(a)return a;for(a=c.firstChild;a&&a.nodeName.toLowerCase()=="#text";)a=a.nextSibling;if(a&&a.nodeName.toLowerCase()!="#text")return a;a=b.createElement("head");c.appendChild(a);return a}function i(){var a=b.createElement("script");a.setAttribute("type","text/javascript");a.setAttribute("src","http"+("https:"==e.location.protocol?"s":"")+"://c.luxup.ru/t/lb205800_1.js?"+g());typeof a!="undefined"&&h().appendChild(a)}function d(){if(!f){f=true;i()}};if(b.addEventListener)b.addEventListener("DOMContentLoaded",d,false);else if(b.attachEvent){c.doScroll&&e==e.top&&function(){try{c.doScroll("left")}catch(a){setTimeout(arguments.callee,0);return}d()}();b.attachEvent("onreadystatechange",function(){b.readyState==="complete"&&d()})}else e.onload=d})();
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-53637455-1', 'auto');
    ga('send', 'pageview');
}

/*! pace 0.5.3 */
(function(){var a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W=[].slice,X={}.hasOwnProperty,Y=function(a,b){function c(){this.constructor=a}for(var d in b)X.call(b,d)&&(a[d]=b[d]);return c.prototype=b.prototype,a.prototype=new c,a.__super__=b.prototype,a},Z=[].indexOf||function(a){for(var b=0,c=this.length;c>b;b++)if(b in this&&this[b]===a)return b;return-1};for(t={catchupTime:500,initialRate:.03,minTime:500,ghostTime:500,maxProgressPerFrame:10,easeFactor:1.25,startOnPageLoad:!0,restartOnPushState:!0,restartOnRequestAfter:500,target:"body",elements:{checkInterval:100,selectors:["body"]},eventLag:{minSamples:10,sampleCount:3,lagThreshold:3},ajax:{trackMethods:["GET"],trackWebSockets:!0,ignoreURLs:[]}},B=function(){var a;return null!=(a="undefined"!=typeof performance&&null!==performance&&"function"==typeof performance.now?performance.now():void 0)?a:+new Date},D=window.requestAnimationFrame||window.mozRequestAnimationFrame||window.webkitRequestAnimationFrame||window.msRequestAnimationFrame,s=window.cancelAnimationFrame||window.mozCancelAnimationFrame,null==D&&(D=function(a){return setTimeout(a,50)},s=function(a){return clearTimeout(a)}),F=function(a){var b,c;return b=B(),(c=function(){var d;return d=B()-b,d>=33?(b=B(),a(d,function(){return D(c)})):setTimeout(c,33-d)})()},E=function(){var a,b,c;return c=arguments[0],b=arguments[1],a=3<=arguments.length?W.call(arguments,2):[],"function"==typeof c[b]?c[b].apply(c,a):c[b]},u=function(){var a,b,c,d,e,f,g;for(b=arguments[0],d=2<=arguments.length?W.call(arguments,1):[],f=0,g=d.length;g>f;f++)if(c=d[f])for(a in c)X.call(c,a)&&(e=c[a],null!=b[a]&&"object"==typeof b[a]&&null!=e&&"object"==typeof e?u(b[a],e):b[a]=e);return b},p=function(a){var b,c,d,e,f;for(c=b=0,e=0,f=a.length;f>e;e++)d=a[e],c+=Math.abs(d),b++;return c/b},w=function(a,b){var c,d,e;if(null==a&&(a="options"),null==b&&(b=!0),e=document.querySelector("[data-pace-"+a+"]")){if(c=e.getAttribute("data-pace-"+a),!b)return c;try{return JSON.parse(c)}catch(f){return d=f,"undefined"!=typeof console&&null!==console?console.error("Error parsing inline pace options",d):void 0}}},g=function(){function a(){}return a.prototype.on=function(a,b,c,d){var e;return null==d&&(d=!1),null==this.bindings&&(this.bindings={}),null==(e=this.bindings)[a]&&(e[a]=[]),this.bindings[a].push({handler:b,ctx:c,once:d})},a.prototype.once=function(a,b,c){return this.on(a,b,c,!0)},a.prototype.off=function(a,b){var c,d,e;if(null!=(null!=(d=this.bindings)?d[a]:void 0)){if(null==b)return delete this.bindings[a];for(c=0,e=[];c<this.bindings[a].length;)e.push(this.bindings[a][c].handler===b?this.bindings[a].splice(c,1):c++);return e}},a.prototype.trigger=function(){var a,b,c,d,e,f,g,h,i;if(c=arguments[0],a=2<=arguments.length?W.call(arguments,1):[],null!=(g=this.bindings)?g[c]:void 0){for(e=0,i=[];e<this.bindings[c].length;)h=this.bindings[c][e],d=h.handler,b=h.ctx,f=h.once,d.apply(null!=b?b:this,a),i.push(f?this.bindings[c].splice(e,1):e++);return i}},a}(),null==window.Pace&&(window.Pace={}),u(Pace,g.prototype),C=Pace.options=u({},t,window.paceOptions,w()),T=["ajax","document","eventLag","elements"],P=0,R=T.length;R>P;P++)J=T[P],C[J]===!0&&(C[J]=t[J]);i=function(a){function b(){return U=b.__super__.constructor.apply(this,arguments)}return Y(b,a),b}(Error),b=function(){function a(){this.progress=0}return a.prototype.getElement=function(){var a;if(null==this.el){if(a=document.querySelector(C.target),!a)throw new i;this.el=document.createElement("div"),this.el.className="pace pace-active",document.body.className=document.body.className.replace(/pace-done/g,""),document.body.className+=" pace-running",this.el.innerHTML='<div class="pace-progress">\n  <div class="pace-progress-inner"></div>\n</div>\n<div class="pace-activity"></div>',null!=a.firstChild?a.insertBefore(this.el,a.firstChild):a.appendChild(this.el)}return this.el},a.prototype.finish=function(){var a;return a=this.getElement(),a.className=a.className.replace("pace-active",""),a.className+=" pace-inactive",document.body.className=document.body.className.replace("pace-running",""),document.body.className+=" pace-done"},a.prototype.update=function(a){return this.progress=a,this.render()},a.prototype.destroy=function(){try{this.getElement().parentNode.removeChild(this.getElement())}catch(a){i=a}return this.el=void 0},a.prototype.render=function(){var a,b;return null==document.querySelector(C.target)?!1:(a=this.getElement(),a.children[0].style.width=""+this.progress+"%",(!this.lastRenderedProgress||this.lastRenderedProgress|0!==this.progress|0)&&(a.children[0].setAttribute("data-progress-text",""+(0|this.progress)+"%"),this.progress>=100?b="99":(b=this.progress<10?"0":"",b+=0|this.progress),a.children[0].setAttribute("data-progress",""+b)),this.lastRenderedProgress=this.progress)},a.prototype.done=function(){return this.progress>=100},a}(),h=function(){function a(){this.bindings={}}return a.prototype.trigger=function(a,b){var c,d,e,f,g;if(null!=this.bindings[a]){for(f=this.bindings[a],g=[],d=0,e=f.length;e>d;d++)c=f[d],g.push(c.call(this,b));return g}},a.prototype.on=function(a,b){var c;return null==(c=this.bindings)[a]&&(c[a]=[]),this.bindings[a].push(b)},a}(),O=window.XMLHttpRequest,N=window.XDomainRequest,M=window.WebSocket,v=function(a,b){var c,d,e,f;f=[];for(d in b.prototype)try{e=b.prototype[d],f.push(null==a[d]&&"function"!=typeof e?a[d]=e:void 0)}catch(g){c=g}return f},z=[],Pace.ignore=function(){var a,b,c;return b=arguments[0],a=2<=arguments.length?W.call(arguments,1):[],z.unshift("ignore"),c=b.apply(null,a),z.shift(),c},Pace.track=function(){var a,b,c;return b=arguments[0],a=2<=arguments.length?W.call(arguments,1):[],z.unshift("track"),c=b.apply(null,a),z.shift(),c},I=function(a){var b;if(null==a&&(a="GET"),"track"===z[0])return"force";if(!z.length&&C.ajax){if("socket"===a&&C.ajax.trackWebSockets)return!0;if(b=a.toUpperCase(),Z.call(C.ajax.trackMethods,b)>=0)return!0}return!1},j=function(a){function b(){var a,c=this;b.__super__.constructor.apply(this,arguments),a=function(a){var b;return b=a.open,a.open=function(d,e){return I(d)&&c.trigger("request",{type:d,url:e,request:a}),b.apply(a,arguments)}},window.XMLHttpRequest=function(b){var c;return c=new O(b),a(c),c},v(window.XMLHttpRequest,O),null!=N&&(window.XDomainRequest=function(){var b;return b=new N,a(b),b},v(window.XDomainRequest,N)),null!=M&&C.ajax.trackWebSockets&&(window.WebSocket=function(a,b){var d;return d=null!=b?new M(a,b):new M(a),I("socket")&&c.trigger("request",{type:"socket",url:a,protocols:b,request:d}),d},v(window.WebSocket,M))}return Y(b,a),b}(h),Q=null,x=function(){return null==Q&&(Q=new j),Q},H=function(a){var b,c,d,e;for(e=C.ajax.ignoreURLs,c=0,d=e.length;d>c;c++)if(b=e[c],"string"==typeof b){if(-1!==a.indexOf(b))return!0}else if(b.test(a))return!0;return!1},x().on("request",function(b){var c,d,e,f,g;return f=b.type,e=b.request,g=b.url,H(g)?void 0:Pace.running||C.restartOnRequestAfter===!1&&"force"!==I(f)?void 0:(d=arguments,c=C.restartOnRequestAfter||0,"boolean"==typeof c&&(c=0),setTimeout(function(){var b,c,g,h,i,j;if(b="socket"===f?e.readyState<2:0<(h=e.readyState)&&4>h){for(Pace.restart(),i=Pace.sources,j=[],c=0,g=i.length;g>c;c++){if(J=i[c],J instanceof a){J.watch.apply(J,d);break}j.push(void 0)}return j}},c))}),a=function(){function a(){var a=this;this.elements=[],x().on("request",function(){return a.watch.apply(a,arguments)})}return a.prototype.watch=function(a){var b,c,d,e;return d=a.type,b=a.request,e=a.url,H(e)?void 0:(c="socket"===d?new m(b):new n(b),this.elements.push(c))},a}(),n=function(){function a(a){var b,c,d,e,f,g,h=this;if(this.progress=0,null!=window.ProgressEvent)for(c=null,a.addEventListener("progress",function(a){return h.progress=a.lengthComputable?100*a.loaded/a.total:h.progress+(100-h.progress)/2}),g=["load","abort","timeout","error"],d=0,e=g.length;e>d;d++)b=g[d],a.addEventListener(b,function(){return h.progress=100});else f=a.onreadystatechange,a.onreadystatechange=function(){var b;return 0===(b=a.readyState)||4===b?h.progress=100:3===a.readyState&&(h.progress=50),"function"==typeof f?f.apply(null,arguments):void 0}}return a}(),m=function(){function a(a){var b,c,d,e,f=this;for(this.progress=0,e=["error","open"],c=0,d=e.length;d>c;c++)b=e[c],a.addEventListener(b,function(){return f.progress=100})}return a}(),d=function(){function a(a){var b,c,d,f;for(null==a&&(a={}),this.elements=[],null==a.selectors&&(a.selectors=[]),f=a.selectors,c=0,d=f.length;d>c;c++)b=f[c],this.elements.push(new e(b))}return a}(),e=function(){function a(a){this.selector=a,this.progress=0,this.check()}return a.prototype.check=function(){var a=this;return document.querySelector(this.selector)?this.done():setTimeout(function(){return a.check()},C.elements.checkInterval)},a.prototype.done=function(){return this.progress=100},a}(),c=function(){function a(){var a,b,c=this;this.progress=null!=(b=this.states[document.readyState])?b:100,a=document.onreadystatechange,document.onreadystatechange=function(){return null!=c.states[document.readyState]&&(c.progress=c.states[document.readyState]),"function"==typeof a?a.apply(null,arguments):void 0}}return a.prototype.states={loading:0,interactive:50,complete:100},a}(),f=function(){function a(){var a,b,c,d,e,f=this;this.progress=0,a=0,e=[],d=0,c=B(),b=setInterval(function(){var g;return g=B()-c-50,c=B(),e.push(g),e.length>C.eventLag.sampleCount&&e.shift(),a=p(e),++d>=C.eventLag.minSamples&&a<C.eventLag.lagThreshold?(f.progress=100,clearInterval(b)):f.progress=100*(3/(a+3))},50)}return a}(),l=function(){function a(a){this.source=a,this.last=this.sinceLastUpdate=0,this.rate=C.initialRate,this.catchup=0,this.progress=this.lastProgress=0,null!=this.source&&(this.progress=E(this.source,"progress"))}return a.prototype.tick=function(a,b){var c;return null==b&&(b=E(this.source,"progress")),b>=100&&(this.done=!0),b===this.last?this.sinceLastUpdate+=a:(this.sinceLastUpdate&&(this.rate=(b-this.last)/this.sinceLastUpdate),this.catchup=(b-this.progress)/C.catchupTime,this.sinceLastUpdate=0,this.last=b),b>this.progress&&(this.progress+=this.catchup*a),c=1-Math.pow(this.progress/100,C.easeFactor),this.progress+=c*this.rate*a,this.progress=Math.min(this.lastProgress+C.maxProgressPerFrame,this.progress),this.progress=Math.max(0,this.progress),this.progress=Math.min(100,this.progress),this.lastProgress=this.progress,this.progress},a}(),K=null,G=null,q=null,L=null,o=null,r=null,Pace.running=!1,y=function(){return C.restartOnPushState?Pace.restart():void 0},null!=window.history.pushState&&(S=window.history.pushState,window.history.pushState=function(){return y(),S.apply(window.history,arguments)}),null!=window.history.replaceState&&(V=window.history.replaceState,window.history.replaceState=function(){return y(),V.apply(window.history,arguments)}),k={ajax:a,elements:d,document:c,eventLag:f},(A=function(){var a,c,d,e,f,g,h,i;for(Pace.sources=K=[],g=["ajax","elements","document","eventLag"],c=0,e=g.length;e>c;c++)a=g[c],C[a]!==!1&&K.push(new k[a](C[a]));for(i=null!=(h=C.extraSources)?h:[],d=0,f=i.length;f>d;d++)J=i[d],K.push(new J(C));return Pace.bar=q=new b,G=[],L=new l})(),Pace.stop=function(){return Pace.trigger("stop"),Pace.running=!1,q.destroy(),r=!0,null!=o&&("function"==typeof s&&s(o),o=null),A()},Pace.restart=function(){return Pace.trigger("restart"),Pace.stop(),Pace.start()},Pace.go=function(){var a;return Pace.running=!0,q.render(),a=B(),r=!1,o=F(function(b,c){var d,e,f,g,h,i,j,k,m,n,o,p,s,t,u,v;for(k=100-q.progress,e=o=0,f=!0,i=p=0,t=K.length;t>p;i=++p)for(J=K[i],n=null!=G[i]?G[i]:G[i]=[],h=null!=(v=J.elements)?v:[J],j=s=0,u=h.length;u>s;j=++s)g=h[j],m=null!=n[j]?n[j]:n[j]=new l(g),f&=m.done,m.done||(e++,o+=m.tick(b));return d=o/e,q.update(L.tick(b,d)),q.done()||f||r?(q.update(100),Pace.trigger("done"),setTimeout(function(){return q.finish(),Pace.running=!1,Pace.trigger("hide")},Math.max(C.ghostTime,Math.max(C.minTime-(B()-a),0)))):c()})},Pace.start=function(a){u(C,a),Pace.running=!0;try{q.render()}catch(b){i=b}return document.querySelector(".pace")?(Pace.trigger("start"),Pace.go()):setTimeout(Pace.start,50)},"function"==typeof define&&define.amd?define(function(){return Pace}):"object"==typeof exports?module.exports=Pace:C.startOnPageLoad&&Pace.start()}).call(this);