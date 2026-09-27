<?php
// app/controllers/ConverterController.php

class ConverterController extends Controller
{
    public function index(): void
    {
        $this->render('converter/index', [
            'pageTitle'  => 'ARRR Studio — HTML → Roblox StarterGui',
            'activePage' => 'converter',
        ]);
    }
}
