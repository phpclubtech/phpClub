<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml');
$this->render('/templates/board/thread.phtml', compact('thread'));
$this->render('/templates/foot.phtml');