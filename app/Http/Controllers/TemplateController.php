<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Matronator\Parsem\Parser;

class TemplateController extends Controller
{
    public const TEMPLATES_DIR = 'templates/';

    public function findAll()
    {
        return response()->json(Template::all());
    }

    public function findByVendor(string $vendor)
    {
        return response()->json(Template::all()->where('vendor', '=', $vendor));
    }

    public function findByName(string $vendor, string $name)
    {
        return response()->json(Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name));
    }

    public function get(string $vendor, string $name)
    {
        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);

        if (!$template)
            return response()->json(['error' => 'No template with this identifier.'], 404);

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        if (!Storage::exists($path))
            return response()->json(['error' => 'Template file not found.'], 404);

        $contents = Storage::get($path);
        $mime = Storage::mimeType($path);
        if (!$mime) {
            $matched = preg_match('/^.+?(?:\.template)?\.(json|yaml|yml|neon)$/', $template->filename, $matches);
            if (!$matched) $mime = 'text/plain';

            $ext = $matches[1] === 'yml' ? 'yaml' : $matches[1];
            $mime = "text/$ext";
        }

        return response($contents, 200, [
            'Content-Type' => $mime,
        ]);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'token' => 'required|string',
            'filename' => 'required|string',
            'name' => 'required|string|alpha_num',
            'contents' => 'required'
        ]);

        $filename = request('filename');
        $name = request('name');
        $contents = request('contents');

        if (!Parser::isValid($filename, $contents))
            return response()->json(['status' => 'error', 'message' => 'Invalid template.'], 400);

        $user = $request->attributes->get('user');

        $template = Template::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => $name,
            'filename' => $filename,
            'vendor' => $user->name,
        ], ['user_id' => $user->id, 'name' => $name, 'filename' => $filename, 'vendor' => $user->name]);

        $template->filename = $filename;
        $template->name = $name;
        $template->user_id = $user->id;
        $template->vendor = $user->name;
        $template->save();

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        Storage::put($path, $contents);

        return response()->json(['status' => 'success', 'message' => 'Template ' . $user->name . '/' . $name . ' published.']);
    }
}
