<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml', compact('logged'));
$this->render('/templates/board/searchresults.phtml', compact('logged', 'posts'));
$this->render('/templates/foot.phtml');