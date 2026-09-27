<?php
// app/controllers/api/ConvertApiController.php — HTML → node tree (POST JSON {html, rectMap})

class ConvertApiController extends ApiController
{
    public function handle(): void
    {
        $guest = Auth::check() ? null : new GuestLimiter();

        // ==== GUEST LIMIT (server-side gate) ====
        if ($guest !== null) {
            if ($guest->exceeded()) {
                $this->json([
                    'error'        => 'guest_limit_exceeded',
                    'message'      => 'Kuota gratis habis. Login untuk lanjut.',
                    'uses'         => $guest->uses(),
                    'max'          => $guest->max(),
                    'login_url'    => url('login'),
                    'auth_google'  => url('auth_google'),
                    'auth_discord' => url('auth_discord'),
                ], 429);
            }
            // Increment SEBELUM parsing — request yang gagal pun tetap dihitung
            $guest->increment();
        }

        // ==== VALIDASI INPUT ====
        $input = Request::json();
        if ($input === null) {
            $this->error('Invalid JSON / empty request body');
        }
        if (empty($input['html'])) {
            $this->error('Empty input (html field missing)');
        }

        $html    = (string)$input['html'];
        $rectMap = is_array($input['rectMap'] ?? null) ? $input['rectMap'] : [];

        if (strlen($html) > config('app.max_html_size')) {
            $this->error('HTML terlalu besar (>500KB)');
        }

        // ==== PARSE ====
        try {
            $parser = new HtmlParser(config('app.canvas.width'), config('app.canvas.height'));
            $nodes  = $parser->parse($html, $rectMap);

            if (!is_array($nodes) || empty($nodes)) {
                $this->error('Parser menghasilkan 0 nodes. Cek rectMap / struktur HTML.', [
                    'debug' => ['htmlLen' => strlen($html), 'rectCount' => count($rectMap)],
                ]);
            }

            try {
                NodeNamer::assignProfessionalNames($nodes);
            } catch (Throwable $e) {
                error_log('[convert] Naming error: ' . $e->getMessage());
            }

            $this->json([
                'nodes' => $nodes,
                'guest' => $guest?->info(),   // null kalau sudah login
                'debug' => $this->debugInfo($html, $rectMap, $nodes),
            ]);
        } catch (Throwable $e) {
            $this->exceptionResponse($e);
        }
    }

    private function debugInfo(string $html, array $rectMap, array $nodes): array
    {
        $count = fn(callable $fn) => count(array_filter($nodes, $fn));

        return [
            'htmlLen'     => strlen($html),
            'rectCount'   => count($rectMap),
            'nodeCount'   => count($nodes),
            'hiddenCount' => $count(fn($n) => !empty($n['selfHidden'])),
            'buttonCount' => $count(fn($n) => in_array($n['robloxClass'] ?? '', ['TextButton', 'ImageButton'], true)),
            'actionCount' => $count(fn($n) => !empty($n['action'])),
            'targetCount' => $count(fn($n) => !empty($n['target'])),
        ];
    }
}
