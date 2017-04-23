<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml', compact('logged'));
$this->render('/templates/board/thread.phtml', compact('logged', 'thread'));
$this->render('/templates/board/footer.phtml', compact('logged', 'thread'));
$this->render('/templates/foot.phtml');