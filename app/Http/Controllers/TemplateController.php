<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Matronator\Mtrgen\Store\Storage as MtrgenStorage;
use Matronator\Mtrgen\Template\Generator;
use Matronator\Parsem\Parser;
use Nette\PhpGenerator\PsrPrinter;

class TemplateController extends Controller
{
    public const TEMPLATES_DIR = 'templates/';

    public function findAll()
    {
        return response()->json(Template::all()->load('tags')->all());
    }

    public function findByVendor(string $vendor)
    {
        $vendor = strtolower($vendor);
        return response()->json(Template::all()->where('vendor', '=', $vendor));
    }

    public function findByName(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
        return response()->json(Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name));
    }

    public function getTemplateDetails(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);

        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);

        if (!$template)
            return response()->json(['status' => 'error', 'message' => 'No template with this identifier.'], 404);

        if ($template->type === Template::TYPE_TEMPLATE) {
            return response()->json($this->templateDetails($template));
        } else {
            return response()->json($this->bundleDetails($template));
        }
    }

    private function templateDetails(Template $template)
    {
        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        if (!Storage::exists($path))
            return response()->json(['status' => 'error', 'message' => 'Template file not found.'], 404);

        $contents = Storage::get($path);
        $template->setAttribute('content', $contents);

        $arguments = Parser::getArguments($contents);
        $templateVars = [];
        foreach ($arguments as $arg) {
            $templateVars[$arg] = '__' . strtoupper($arg) . '__';
        }
        $parsed = Generator::parse($template->filename, $contents, $templateVars);
        $printer = new PsrPrinter;
        $generated = $printer->printFile($parsed->contents);
        $template->setAttribute('preview', $generated);
        $template->setAttribute('generatedFilename', $parsed->filename);

        return $template;
    }

    private function bundleDetails(Template $bundle)
    {
        $path = self::TEMPLATES_DIR . $bundle->vendor . DIRECTORY_SEPARATOR . $bundle->filename;
        $dir = self::TEMPLATES_DIR . $bundle->vendor . DIRECTORY_SEPARATOR . $bundle->name;

        if (!Storage::exists($path))
            return response()->json(['status' => 'error', 'message' => 'Template file not found.'], 404);

        $contents = Storage::get($path);
        $bundle->setAttribute('content', json_encode(json_decode($contents), JSON_PRETTY_PRINT));

        $files = Storage::files($dir);
        $templates = [];
        foreach ($files as $file) {
            $content = Storage::get($file);
            $arguments = Parser::getArguments($content);
            $templateVars = [];
            foreach ($arguments as $arg) {
                $templateVars[$arg] = '__' . strtoupper($arg) . '__';
            }
            $parsed = Generator::parse($file, $content, $templateVars);
            $printer = new PsrPrinter;
            $generated = $printer->printFile($parsed->contents);

            $templateObject = Parser::decodeByExtension($file, $content);

            $templates[] = (object) [
                'content' => $content,
                'preview' => $generated,
                'generatedFilename' => $parsed->filename,
                'name' => $templateObject->name,
                'filename' => basename($file),
            ];
        }

        $bundle->setAttribute('templates', $templates);

        return $bundle;
    }

    public function get(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
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

        if (request()->hasHeader('X-Requested-By')) {
            if (request()->header('X-Requested-By') === 'cli') {
                $template->downloads += 1;
                $template->save();
            }
        }

        return response($contents, 200, [
            'Content-Type' => $mime,
        ]);
    }

    public function getType(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);

        return response()->json($template->type);
    }

    public function getFromBundle(string $vendor, string $name, string $templateName)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);

        if (!$template)
            return response()->json(['error' => 'No template with this identifier.'], 404);
        
        if ($template->type !== Template::TYPE_BUNDLE)
            return response()->json(['error' => 'Not a bundle.'], 400);

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        if (!Storage::exists($path))
            return response()->json(['error' => 'Template file not found.'], 404);

        $contents = Storage::get($path);
        $bundle = Parser::decodeByExtension($template->filename, $contents);

        foreach ($bundle->templates as $temp) {
            if ($temp->name !== $templateName) continue;
            $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $temp->path;
            if (!Storage::exists($path))
                return response()->json(['error' => 'Template file not found.'], 404);

            $contents = Storage::get($path);
            $mime = Storage::mimeType($path);
            if (!$mime) {
                $matched = preg_match('/^.+?(?:\.template)?\.(json|yaml|yml|neon)$/', $temp->path, $matches);
                if (!$matched) $mime = 'text/plain';
        
                $ext = $matches[1] === 'yml' ? 'yaml' : $matches[1];
                $mime = "text/$ext";
            }
        
            if (request()->hasHeader('X-Requested-By')) {
                if (request()->header('X-Requested-By') === 'cli') {
                    $template->downloads += 1;
                    $template->save();
                }
            }
        
            return response($contents, 200, [
                'Content-Type' => $mime,
            ]);
        }
    }

    public function saveBundle(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'token' => 'required|string',
            'filename' => 'required|string',
            'name' => 'required|string|alpha_num',
            'description' => 'string',
            'contents' => 'required',
            'templates' => 'required|array',
            'templates.*.name' => 'required|string|alpha_num',
            'templates.*.filename' => 'required|string',
            'templates.*.contents' => 'required',
        ]);

        $filename = request('filename');
        $name = strtolower(request('name'));
        $contents = request('contents');
        $description = request('description') ?? null;
        if (!Parser::isValidBundle($filename, $contents))
            return response()->json(['status' => 'error', 'message' => 'Invalid bundle.'], 400);

        $templates = request('templates');
        if (count($templates) < 2)
            return response()->json(['status' => 'error', 'message' => 'Bundle must have at least two templates.'], 400);
        
        $user = $request->attributes->get('user');

        foreach ($templates as $item) {
            if (!Parser::isValid($item['filename'], $item['contents']))
                return response()->json(['status' => 'error', 'message' => 'Bundle contains invalid template/s.'], 400);
            
            $path = self::TEMPLATES_DIR . $user->username . DIRECTORY_SEPARATOR . $item['filename'];
            Storage::put($path, $item['contents']);
        }

        $template = Template::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => $name,
            'filename' => $filename,
            'vendor' => strtolower($user->username),
            'type' => Template::TYPE_BUNDLE,
        ], ['user_id' => $user->id, 'name' => $name, 'filename' => $filename, 'vendor' => strtolower($user->username), 'type' => Template::TYPE_BUNDLE, 'description' => $description]);

        $template->filename = $filename;
        $template->name = $name;
        $template->user_id = $user->id;
        $template->vendor = strtolower($user->username);
        if ($description) $template->description = $description;
        $template->type = Template::TYPE_BUNDLE;
        $template->save();

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;
        Storage::put($path, $contents);

        return response()->json(['status' => 'success', 'message' => 'Bundle ' . strtolower($user->username . '/' . $name) . ' published.']);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'token' => 'required|string',
            'filename' => 'required|string',
            'name' => 'required|string|alpha_num',
            'description' => 'string',
            'contents' => 'required'
        ]);

        $filename = request('filename');
        $name = request('name');
        $contents = request('contents');
        $description = request('description') ?? null;

        if (!Parser::isValid($filename, $contents))
            return response()->json(['status' => 'error', 'message' => 'Invalid template.'], 400);

        $user = $request->attributes->get('user');

        $template = Template::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => strtolower($name),
            'filename' => $filename,
            'vendor' => strtolower($user->username),
            'type' => Template::TYPE_TEMPLATE,
        ], ['user_id' => $user->id, 'name' => strtolower($name), 'filename' => $filename, 'vendor' => strtolower($user->username), 'type' => Template::TYPE_TEMPLATE, 'description' => $description]);

        $template->filename = $filename;
        $template->name = strtolower($name);
        $template->user_id = $user->id;
        $template->vendor = strtolower($user->username);
        if ($description) $template->description = $description;
        $template->type = Template::TYPE_TEMPLATE;
        $template->save();

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        Storage::put($path, $contents);

        return response()->json(['status' => 'success', 'message' => 'Template ' . strtolower($user->username . '/' . $name) . ' published.']);
    }
}
