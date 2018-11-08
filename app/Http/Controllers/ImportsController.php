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

    public function getlocationfile(){
        $path = storage_path('/logs/location.csv');
        return response()->download($path);
    }

    public function getmanufacturersfile(){
        $path = storage_path('/logs/manufacturer.csv');
        return response()->download($path);
    }

    public function getcategoryfile(){
        $path = storage_path('/logs/category.csv');
        return response()->download($path);
    }

    public function getmodelfile(){
        $path = storage_path('/logs/assetmodel.csv');
        return response()->download($path);
    }

    public function getassetfile(){
        $path = storage_path('/logs/assetdata.csv');
        return response()->download($path);
    }
}
