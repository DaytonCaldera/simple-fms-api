<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        $contents = $this->listFiles('/');
        // $table_options = [
        //     'width'=> "100%",
        //     'inserting'=> true,
        //     'editing'=> true,
        //     'delete'=> false,
        //     'sorting'=> true,
        //     'paging'=> true,
        //     'data'=>$contents->original,
        //     'fields'=> [[
        //         'name'=> "nombre",
        //         'title'=> 'Nombre',
        //         'type'=> "text",
        //         'width'=> 150,
        //         'validate'=> "required"
        //     ],
        //     [
        //         'type'=> "control",
        //     ]
        // ]
        // ];
        return view('home')->with(['content' => $contents]);
    }

    public function store(Request $request) {
        $request->validate([
            'file' => 'required|file', // Adjust validation rules as needed
            'path' => 'required|string'
        ]);

        $file = $request->file('file');
        $path = $request->path;

        // Generate a unique filename
        $fileName = $file->getClientOriginalName();

        // Store the file in the local disk
        Storage::disk('local')->put($path . '/' . $fileName, $file->getContent());
        $fileUrl = Storage::disk('local')->url('uploads/' . $fileName);
        // Store any additional information about the file in the database (optional)

        return response()->json(['url' => $fileUrl]);
    }

    public function retrieveFile($fileName) {
        // Validate the file name (optional)
        // ...

        if (!Storage::disk('local')->exists('uploads/' . $fileName)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $filePath = Storage::disk('local')->path('uploads/' . $fileName);
        $mimeType = File::mimeType($filePath);

        dd($mimeType);
        return response()->streamDownload(function () use ($filePath) {
            $stream = fopen($filePath, 'r');
            stream_copy($stream, fopen('php://output', 'w'));
        }, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function listFiles($folderPath = 'uploads') {
        $files = [];
        $allFiles = Storage::disk('local')->allFiles($folderPath);

        foreach ($allFiles as $filePath) {
            $fileInfo = [
                'nombre' => basename($filePath),
                // 'size' => Storage::disk('local')->size($filePath),
                // Add additional information as needed (e.g., type, created_at)
            ];
            $files[] = $fileInfo;
        }

        return response()->json($files);
    }
}
