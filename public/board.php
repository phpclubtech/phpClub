<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml', compact('logged'));
$this->render('/templates/board/board.phtml', compact('logged','threads'));
$this->render('/templates/foot.phtml');