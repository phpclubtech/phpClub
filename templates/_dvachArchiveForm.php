<?php
/** @var string $searchString */
?>

<label>
    <img src="/media/images/2ch.ico" title="Искать в архиве 2ch.hk (могут отсутствовать новые треды)">
    <form action="https://2ch.hk/makaba/makaba.fcgi" method="POST" style="display: none" enctype="multipart/form-data">
    <input type="hidden" name="task" value="search_arch">
        <input type="hidden" name="board" value="pr">
        <input type="hidden" name="find" value="<?= $searchString ?>">
        <input type="submit" value="Найти">
    </form>
</label>