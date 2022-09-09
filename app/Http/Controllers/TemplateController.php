<?php

namespace App\Http\Controllers;

use App\Models\Template;

class TemplateController extends Controller
{
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
}
