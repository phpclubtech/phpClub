<?php
$this->render('/templates/head.phtml');
$this->render('/templates/header.phtml');
$this->render('/templates/login.phtml', compact('post', 'errors'));
$this->render('/templates/foot.phtml');