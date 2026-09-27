<?php
// app/controllers/DocsController.php

class DocsController extends Controller
{
    public function index(): void
    {
        $this->render('docs/index', [
            'pageTitle'  => 'ARRR Studio — Docs',
            'activePage' => 'docs',
            'styles'     => ['docs'],
        ]);
    }
}
