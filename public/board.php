<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml');
$this->render('/templates/board/board.phtml', compact('threads'));
$this->render('/templates/foot.phtml');