<?php
// app/controllers/api/GenerateApiController.php — node tree → Lua / rbxmx / plugin (POST JSON {nodes, canvasW, canvasH, billboard})

class GenerateApiController extends ApiController
{
    public function handle(): void
    {
        $input = Request::json();
        if ($input === null || !isset($input['nodes'])) {
            $this->error('Invalid input');
        }

        try {
            $this->json((new ExportService())->build(
                (array)$input['nodes'],
                (int)($input['canvasW'] ?? config('app.canvas.width')),
                (int)($input['canvasH'] ?? config('app.canvas.height')),
                $input['billboard'] ?? null
            ));
        } catch (Throwable $e) {
            $this->exceptionResponse($e);
        }
    }
}
