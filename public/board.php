<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board.phtml', compact('threads'));
$this->render('/templates/foot.phtml');