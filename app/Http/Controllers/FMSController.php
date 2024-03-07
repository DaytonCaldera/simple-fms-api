<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use League\Flysystem\Filesystem;

use function Laravel\Prompts\error;

class FMSController extends Controller {

    private $excluded_files = [
        '.gitignore',
        'public', //folder
        
    ];

    public function __construct() {
        $this->middleware('auth');
    }


    public function listFiles(Request $request) {
        $path = $request->path ?? '/';
        $files = [];
        $path = urldecode($path);
        if ($path != '/') $files[] = ['nombre' => '..', 'dir' => true, 'mime' => 'return'];
        $allFiles = collect(Storage::disk('local')->files($path))
            ->reject(fn ($file) => in_array(basename($file), $this->excluded_files));
        $allDirectories = collect(Storage::disk('local')->directories($path))
            ->reject(fn ($dir) => in_array(basename($dir), $this->excluded_files));
        foreach ($allDirectories as $filePath) {
            $fileInfo = [
                'nombre' => basename($filePath),
                'dir' => true,
                'mime' => 'folder'
                // 'size' => Storage::disk('local')->size($filePath),
                // Add additional information as needed (e.g., type, created_at)
            ];
            $files[] = $fileInfo;
        }
        foreach ($allFiles as $filePath) {
            $fileInfo = [
                'nombre' => basename($filePath),
                'dir' => false,
                'mime' => File::mimeType(Storage::disk('local')->path('/' . $filePath)),
                // 'size' => Storage::disk('local')->size($filePath),
                // Add additional information as needed (e.g., type, created_at)
            ];
            $files[] = $fileInfo;
        }

        return response()->json($files);
    }

    public function show($file_name) {
        if (!Storage::disk('local')->exists('/' . $file_name)) { // Adjust disk and folder as needed
            return response()->json(['error' => 'Image not found'], 404);
        }

        $filePath = Storage::disk('local')->path('/' . $file_name); // Adjust disk and folder as needed
        $mime_type = File::mimeType($filePath);

        // Return the image content with appropriate headers
        return response()->make(file_get_contents($filePath), 200, [
            'Content-Type' => $mime_type,
            'Content-Disposition' => 'inline; filename="' . $file_name . '"',
        ]);
    }

    public function renameFile(Request $request) {
        // $file_path = Storage::disk('local')->path('/' . $request->old);
        $new_file_path = dirname($request->old) . '/' . $request->new;
        $response = [
            'old_path' => $request->old,
            'new_path' => $new_file_path
        ];
        if (Storage::move($request->old, $new_file_path)) {
            return response()->json([200]);
        }
        return response()->json('Error al renombrar archivo', 400);
    }

    public function deleteFile(Request $request) {
        try {
            $path = $request->path;
            if (File::isDirectory(Storage::path($path))) {
                $deleted = Storage::deleteDirectory($path);
                return response()->json("Se ha eliminado el directorio {$path}: " . $deleted, 200);
            } else if (File::isFile(Storage::path($path))) {
                $deleted = Storage::delete($path);
                return response()->json("Se ha eliminado el archivo {$path}: " . $deleted, 200);
            }
        } catch (Exception $ex) {
            return response()->json($ex->getMessage(), 500);
        }
    }

    public function addFolder(Request $request) {
        try {
            $path = $request->path;
            $nombre_carpeta = $request->carpeta;
            $new_folder_path = $path . '/' . $nombre_carpeta;
            if (!Storage::exists($new_folder_path)) {
                Storage::makeDirectory($new_folder_path);
                return response()->json(['ruta' => $new_folder_path], 201);
            } else {
                return response()->json('Ya existe una carpeta con ese nombre aqui: ' . $new_folder_path, 400);
            }
        } catch (Exception $ex) {
            return response()->json($ex->getMessage(), 500);
        }
    }
}
