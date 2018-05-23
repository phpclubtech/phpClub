# Виды разметки в теле поста

В теле постов могут встретиться только такие виды разметки: 

- `<p>....</p>` - традиционно текст поста завернут в тег <p>
- `<br>`
- `<a href="..." rel="nofollow">...</a>` - ссылка
- `<a href="..." target="_blank">...</a>` - ссылка с треда 30 (Авг 2014)
- `<a onmouseover="showPostPreview(event)" onmouseout="delPostPreview(event)" href="#315927" onclick="highlight(315927)">>>315927</a>` - ссылка на пост
- `<a href="#380394" class="post-reply-link" data-thread="377570" data-num="380394">>>380394</a>` - ссылка на пост с треда 30 (Авг 2014)
- `<strong>...</strong>`
- `<em>...</em>`
- `<sub>...</sub>`
- `<sup>...</sup>`
- `<span class="unkfunc">...</span>` - цитата (зеленый цвет)
- `<span class="spoiler">...</span>`
- `<span class="s">...</span>` - зачеркнутый текст
- `<span class="u">...</span>` - подчеркнутый текст?
- `<span class="o">...</span>` - надчеркнутый?
- `<pre><code>....</code></pre>` - код, используется `<br>` для переноса строк
- `<span class="code_container"><span class="code_line">..</span><br>...</span>` - код с треда 30 (Авг 2014)
- `<font color="#C12267"><em>(Автор этого поста был забанен.. Помянем.)</em></font>` - с треда 24 (май 2014)
- `<span class="pomyanem">(Автор этого поста был забанен.. Помянем.)</span>` - с треда 59 (Сент 2015)

Если парсер обнаруживает какую-то другую разметку, выбрасывается исключение. 
