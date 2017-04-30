<?php
$this->render('templates/head.phtml');
$this->render('templates/board/header.phtml', compact('logged'));
$this->render('templates/config.phtml', compact('logged', 'errors'));
$this->render('templates/foot.phtml');