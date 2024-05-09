<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Matronator\Mtrgen\Template\Generator;

class GeneratorController extends Controller
{
    public function generate(Request $request)
    {
        $this->validate($request, [
            'template' => 'required|string',
            'params' => 'required|array',
        ]);

        $template = $request->input('template');
        $params = $request->input('params');

        $file = static::virtualFile($template);

        $fileObject = Generator::parseAnyFile($file, $params);

        return response()->json([
            'template' => $template,
            'generated' => $fileObject->contents,
            'filename' => $fileObject->filename,
        ], 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public static function virtualFile(string $content): string
    {
       return 'data://text/plain,' . urlencode($content);
    }
}
