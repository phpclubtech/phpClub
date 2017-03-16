<?php
$this->render('/templates/head.phtml');
$this->render('/templates/chain.phtml', compact('posts'));
$this->render('/templates/foot.phtml');