<?php

namespace App\Http\Requests;

use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use League\Csv\Reader;

class ItemImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'import-type' => 'required',
        ];
    }

    public function import(Import $import)
    {
        ini_set('max_execution_time', 0); //600 seconds = 10 minutes
        ini_set('memory_limit', '3072M');
        $filename = config('app.private_uploads') . '/imports/' . $import->file_path;
        $import->import_type = $this->input('import-type');
        $class = title_case($import->import_type);
        $classString = "App\\Importer\\{$class}Importer";
        if($class == "Asset"){
            if (is_file($filename)) {
                $csv = Reader::createFromPath($filename);
            } else {
                $csv = Reader::createFromString($filename);
            }
            $records = $csv->fetchAll();
            if(count($records) > 500){
                $error["Data"]["Message"] = "We cannot import data more that 500 records";
                return $error;
            }
        }
        $importer = new $classString($filename);
        $import->field_map  = request('column-mappings');
        $import->save();
        $fieldMappings=[];
        if ($import->field_map) {
            // We submit as csv field: column, but the importer is happier if we flip it here.
            $fieldMappings = array_change_key_case(array_flip($import->field_map), CASE_LOWER);
                        // dd($fieldMappings);
        }
        $importer->setCallbacks([$this, 'log'], [$this, 'progress'], [$this, 'errorCallback'])
                 ->setUserId(Auth::id())
                 ->setUpdating($this->has('import-update'))
                 ->setShouldNotify($this->has('send-welcome'))
                 ->setUsernameFormat('firstname.lastname')
                 ->setFieldMappings($fieldMappings);
        // $logFile = storage_path('logs/importer.log');
        // \Log::useFiles($logFile);
        $importer->import();
        if($class == "Asset" && $this->errors){
            array_push($this->errors, array("Message" => "There are some import errors. <a href='http://localhost:9090/import/getfile'>Click Here</a> to download the error files."));
        }
        return $this->errors;
    }

    public function log($string)
    {
         \Log::Info($string);
    }

    public function progress($count)
    {
        // Open for future
        return;
    }
    public function errorCallback($item, $field, $errorString)
    {
        $this->errors[$item->name][$field] = $errorString;
    }

    private $errors;
}
