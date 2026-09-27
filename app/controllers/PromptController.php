<?php
// app/controllers/PromptController.php

class PromptController extends Controller
{
    public function index(): void
    {
        $this->render('prompt/index', [
            'pageTitle'  => 'ARRR Studio — Prompt Guide',
            'activePage' => 'prompt',
            'styles'     => ['prompt'],
        ]);
    }
}
