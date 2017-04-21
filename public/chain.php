<?php
$this->render('/templates/head.phtml');
$this->render('/templates/board/header.phtml');
$this->render('/templates/board/chain.phtml', compact('posts'));
$this->render('/templates/foot.phtml');