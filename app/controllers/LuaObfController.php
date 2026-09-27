<?php
// app/controllers/LuaObfController.php — Lua Obfuscator / Deobfuscator (logic: assets/js/pages/luaobf.js)

class LuaObfController extends Controller
{
    public function index(): void
    {
        $this->render('luaobf/index', [
            'pageTitle'  => 'ARRR Studio — Lua Obfuscator',
            'activePage' => 'luaobf',
            'styles'     => ['library', 'spoofer', 'luaobf'],
        ]);
    }
}
