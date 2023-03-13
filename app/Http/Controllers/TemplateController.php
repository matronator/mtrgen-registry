<?php

namespace App\Http\Controllers;

use App\Helpers\BasicResponse;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Matronator\Mtrgen\Template\Generator;
use Illuminate\Http\UploadedFile;
use Matronator\Parsem\Parser;
use Nette\PhpGenerator\PsrPrinter;

class TemplateController extends Controller
{
    public const TEMPLATES_DIR = 'templates/';

    public function findAllPublic()
    {
        return response()->json(array_values(Template::allPublic()->load('tags')->all()));
    }

    public function findPublicByVendor(string $vendor)
    {
        $vendor = strtolower($vendor);
        return response()->json(array_values(Template::allPublic()->where('vendor', '=', $vendor)->load('tags')->all()));
    }

    public function findByVendor(Request $request, string $vendor)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $vendor = strtolower($vendor);

        return $this->checkUserPrivileges($request, $vendor, response()->json(array_values(Template::all()->where('vendor', '=', $vendor)->load('tags')->all())));
    }

    public function findByName(Request $request, string $vendor, string $name)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $vendor = strtolower($vendor);
        $name = strtolower($name);

        return $this->checkUserPrivileges($request, $vendor, response()->json(Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name)));
    }

    public function findPublicByName(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
        return response()->json(Template::queryPublic()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name));
    }

    public function getTemplateDetails(Request $request, string $vendor, string $name)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $vendor = strtolower($vendor);
        $name = strtolower($name);

        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);
        if (!$template)
            return response()->json(['status' => 'error', 'message' => 'No template with this identifier.'], 404);
        
        if ($template->type === Template::TYPE_TEMPLATE) {
            return $this->checkUserPrivileges($request, $vendor, response()->json($this->templateDetails($template)));
        } else {
            return $this->checkUserPrivileges($request, $vendor, response()->json($this->bundleDetails($template)));
        }
    }

    public function getPublicTemplateDetails(string $vendor, string $name)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);

        $template = Template::queryPublic()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);

        if (!$template)
            return response()->json(['status' => 'error', 'message' => 'No template with this identifier.'], 404);

        if ($template->type === Template::TYPE_TEMPLATE) {
            return response()->json($this->templateDetails($template));
        } else {
            return response()->json($this->bundleDetails($template));
        }
    }

    public function convert(Request $request)
    {
        $this->validate($request, [
            'template' => 'required|json',
        ]);

        $template = request('template');

        if (!Parser::isValid('test.template.json', $template)) {
            return BasicResponse::send('Invalid template.', BasicResponse::STATUS_ERROR, 400);
        }

        $decoded = Parser::decodeByExtension('test.template.json', $template);

        $arguments = Parser::getArguments($template);
        $templateVars = [];
        foreach ($arguments as $arg) {
            $templateVars[$arg] = '__' . strtoupper($arg) . '__';
        }

        try {
            $parsed = Generator::parse($decoded->filename . '.json', $template, $templateVars);
            $printer = new PsrPrinter;
            $generated = $printer->printFile($parsed->contents);
        } catch (\Exception $e) {
            return BasicResponse::send('Invalid template.', BasicResponse::STATUS_ERROR, 400);
        }

        return response()->json([
            'template' => $template,
            'parsed' => $parsed,
            'generated' => $generated,
        ], 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function get(Request $request, string $vendor, string $name)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $vendor = strtolower($vendor);
        $name = strtolower($name);

        $user = $request->attributes->get('user');

        if ($user->username !== $vendor) {
            return BasicResponse::send('You are not authorized to view this page.', BasicResponse::STATUS_ERROR, 401);
        }

        return $this->getPublic($vendor, $name, true);
    }

    public function getPublic(string $vendor, string $name, bool $alsoPrivate = false)
    {
        $vendor = strtolower($vendor);
        $name = strtolower($name);
        if ($alsoPrivate) {
            $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);
        } else {
            $template = Template::queryPublic()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);
        }

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

    public function setVisibility(Request $request, string $vendor, string $name)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'private' => 'required|boolean'
        ]);

        $vendor = strtolower($vendor);
        $name = strtolower($name);

        $user = $request->attributes->get('user');

        if ($user->username !== $vendor) {
            return BasicResponse::send('You are not authorized to view this page.', BasicResponse::STATUS_ERROR, 401);
        }

        $template = Template::query()->where('vendor', '=', $vendor)->firstWhere('name', '=', $name);
        if (!$template)
            return response()->json(['status' => 'error', 'message' => 'No template with this identifier.'], 404);
        
        $private = request('private', true);
        $template->private = $private;
        $template->save();

        return BasicResponse::send('Visibility changed to ' . ($private ? 'private' : 'public') . '.');
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
            'filename' => 'required|string',
            'name' => 'required|string|alpha_num',
            'private' => 'sometimes|nullable|boolean',
            'description' => 'string',
            'contents' => 'required',
            'templates' => 'required|array',
            'templates.*.name' => 'required|string|alpha_num',
            'templates.*.filename' => 'required|string',
            'templates.*.contents' => 'required',
        ]);

        $filename = request('filename');
        $name = strtolower(request('name'));
        $isPrivate = request('private') ?? false;
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

        $this->saveTemplate($user, $name, $filename, $isPrivate, $description, $contents, Template::TYPE_BUNDLE);

        return response()->json(['status' => 'success', 'message' => 'Bundle ' . strtolower($user->username . '/' . $name) . ' published.']);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'filename' => 'required|string',
            'name' => 'required|string|alpha_num',
            'private' => 'sometimes|nullable|boolean',
            'description' => 'sometimes|nullable|string',
            'contents' => 'required'
        ]);

        $filename = request('filename');
        $name = request('name');
        $isPrivate = request('private') ?? false;
        $contents = request('contents');
        $description = request('description') ?? null;

        if (!Parser::isValid($filename, $contents))
            return response()->json(['status' => 'error', 'message' => 'Invalid template.'], 400);

        $user = $request->attributes->get('user');

        $this->saveTemplate($user, $name, $filename, $isPrivate, $description, $contents);

        return response()->json(['status' => 'success', 'message' => 'Template ' . strtolower($user->username . '/' . $name) . ' published.']);
    }

    public function publish(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'bundle' => 'required|boolean',
            'name' => 'sometimes|nullable|string|alpha_num',
            'private' => 'sometimes|nullable|boolean',
            'description' => 'sometimes|nullable|string',
            'files' => 'required_without:file|array',
            'files.*' => 'file',
            'file' => 'required_without:files|file',
        ]);

        $isBundle = request('bundle');
        $isPrivate = request('private') ?? false;
        $description = request('description') ?? null;

        $user = $request->attributes->get('user');

        if ($isBundle) {
            $name = strtolower(request('name'));
            $files = $request->file('files');

            if (count($files) < 2)
                return response()->json(['status' => 'error', 'message' => 'Bundle must have at least two templates.'], 400);

            $bundleObject = (object) [
                'name' => $name,
                'templates' => [],
            ];

            /** @var UploadedFile $file */
            foreach ($files as $file) {
                $filename = $file->getClientOriginalName();
                $contents = $file->getContent();
                if (!Parser::isValid($filename, $contents))
                    return response()->json(['status' => 'error', 'message' => 'Bundle contains invalid template/s.'], 400);

                $path = self::TEMPLATES_DIR . $user->username . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $filename;
                Storage::put($path, $contents);

                $parsedTemplate = Parser::decodeByExtension($filename, $contents);

                $bundleObject->templates[] = (object) [
                    'name' => $parsedTemplate->name,
                    'path' => $name . DIRECTORY_SEPARATOR . $filename,
                ];
            }

            $bundleContents = json_encode($bundleObject, JSON_PRETTY_PRINT);
            $bundleFilename = "$name.bundle.json";

            $this->saveTemplate($user, $name, $bundleFilename, $isPrivate, $description, $bundleContents, Template::TYPE_BUNDLE);

            return response()->json(['status' => 'success', 'message' => 'Bundle ' . strtolower($user->username . '/' . $name) . ' published!']);
        } else {
            $file = $request->file('file');
            $contents = $file->getContent();

            if (!Parser::isValid($file->getClientOriginalName(), $contents))
                return response()->json(['status' => 'error', 'message' => 'Invalid template.'], 400);

            $templateObject = Parser::decodeByExtension($file->getClientOriginalName(), $contents);

            $this->saveTemplate($user, $templateObject->name, $file->getClientOriginalName(), $isPrivate, $description, $contents);

            return response()->json(['status' => 'success', 'message' => 'Template ' . strtolower($user->username . '/' . $templateObject->name) . ' published!']);
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

        try {
            $parsed = Generator::parse($template->filename, $contents, $templateVars);
            $printer = new PsrPrinter;
            $generated = $printer->printFile($parsed->contents);
            $template->setAttribute('generatedFilename', $parsed->filename);
        } catch (\Exception $e) {
            $generated = $e->getMessage();
        }
        
        $template->setAttribute('preview', $generated);
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

    private function saveTemplate(mixed $user, string $name, string $filename, bool $isPrivate, ?string $description, string $contents, string $type = Template::TYPE_TEMPLATE): void
    {
        $template = Template::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => strtolower($name),
            'filename' => $filename,
            'vendor' => strtolower($user->username),
            'type' => $type,
            'private' => $isPrivate,
        ], ['user_id' => $user->id, 'name' => strtolower($name), 'filename' => $filename, 'vendor' => strtolower($user->username), 'type' => $type, 'description' => $description]);

        $template->filename = $filename;
        $template->name = strtolower($name);
        $template->user_id = $user->id;
        $template->vendor = strtolower($user->username);
        if ($description) $template->description = $description;
        $template->type = $type;
        $template->save();

        $path = self::TEMPLATES_DIR . $template->vendor . DIRECTORY_SEPARATOR . $template->filename;

        Storage::put($path, $contents);
    }

    private function checkUserPrivileges(Request $request, string $vendor, JsonResponse $response): bool|\Illuminate\Http\JsonResponse
    {
        $user = $request->attributes->get('user');

        if ($user->username !== $vendor) {
            return BasicResponse::send('You are not authorized to view this page.', BasicResponse::STATUS_ERROR, 401);
        }

        return $response;
    }
}
