<?php
// app/controllers/LandingController.php

class LandingController extends Controller
{
    public function index(): void
    {
        $this->render('landing/index', [
            'pageTitle'  => 'ARRR Studio — HTML to Roblox UI Converter',
            'activePage' => 'landing',
            'navVariant' => 'landing',
            'styles'     => ['landing'],
        ]);
    }
}
