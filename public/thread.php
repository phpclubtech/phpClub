<?php
$this->render('/templates/head.phtml');
$this->render('/templates/thread.phtml', compact('thread'));
$this->render('/templates/foot.phtml');