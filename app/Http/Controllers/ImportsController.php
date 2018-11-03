<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ImportsTransformer;
use App\Models\Import;
use Illuminate\Http\Request;
use App\Models\Asset;


class ImportsController extends Controller
{
    public function index()
    {
        $this->authorize('create', Asset::class);
        $imports = Import::latest()->get();
        $imports = (new ImportsTransformer)->transformImports($imports);
        return view('importer/import')->with('imports', $imports);
    }

    public function getfile(){
        $path = storage_path('/private_uploads/imports/importerror.csv');
        return response()->download($path);
    }
}
