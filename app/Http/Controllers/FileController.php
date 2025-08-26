<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Str;
use Exception;

class FileController extends Controller
{
    // MIME types constantes
    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    private const PDF_MIME = 'application/pdf';

    /**
     * Sube y procesa un archivo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,pdf|max:10240',
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('uploads');
        $nombre = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getMimeType();

        $content = $this->extractContent($path, $mimeType);

        File::create([
            'nombre'    => $nombre,
            'ruta'      => $path,
            'mime_type' => $mimeType,
            'content'   => $content,
        ]);

        return redirect()->back()->with('success', 'Archivo subido y procesado correctamente.');
    }

    /**
     * Extrae contenido dependiendo del tipo MIME.
     */
    private function extractContent(string $path, string $mimeType): string
    {
        $fullPath = Storage::path($path);

        if (!Storage::exists($path)) {
            Log::error("Archivo no encontrado: {$fullPath}");
            return "Error: archivo no encontrado.";
        }

        return match ($mimeType) {
            self::DOCX_MIME => $this->extractFromDocx($fullPath),
            self::PDF_MIME  => $this->extractFromPdf($fullPath),
            default         => "Error: tipo de archivo no soportado.",
        };
    }

    /**
     * Extrae texto de archivos DOCX.
     */
    private function extractFromDocx(string $filePath): string
    {
        if (!class_exists(IOFactory::class)) {
            return 'Error al extraer texto del archivo DOCX: PhpWord no disponible.';
        }

        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . ' ';
                    }
                }
            }

            return trim($text);
        } catch (Exception $e) {
            Log::error("DOCX Extraction Error: {$e->getMessage()}");
            return 'Error al extraer texto del archivo DOCX: ' . $e->getMessage();
        }
    }

    /**
     * Extrae texto de archivos PDF.
     */
    private function extractFromPdf(string $filePath): string
    {
        if (!class_exists(\Spatie\PdfToText\Pdf::class)) {
            return 'Error al extraer texto del PDF: spatie/pdf-to-text no disponible.';
        }

        try {
            return trim(\Spatie\PdfToText\Pdf::getText($filePath));
        } catch (Exception $e) {
            Log::error("PDF Extraction Error: {$e->getMessage()}");
            return 'Error al extraer texto del PDF: ' . $e->getMessage();
        }
    }

    /**
     * Reprocesa todos los archivos con contenido vacío o con error.
     */
    public function reprocessAll(Request $request)
    {
        $filesToReprocess = File::where(function ($query) {
            $query->where('content', '')
                  ->orWhere('content', 'like', 'Error al extraer texto del archivo%');
        })->get();

        $reprocessedCount = 0;

        foreach ($filesToReprocess as $file) {
            if (!Storage::exists($file->ruta)) {
                Log::warning("Archivo no encontrado para reprocesar: {$file->nombre}");
                continue;
            }

            $newContent = $this->extractContent($file->ruta, $file->mime_type);

            if (!empty($newContent) && !Str::startsWith($newContent, 'Error al extraer texto del archivo')) {
                $file->update(['content' => $newContent]);
                $reprocessedCount++;
                Log::info("Archivo reprocesado: {$file->nombre}");
            } else {
                Log::warning("No se pudo reprocesar el archivo: {$file->nombre}");
            }
        }

        return redirect()->back()->with('success', "Se han reprocesado {$reprocessedCount} archivos.");
    }

    /**
     * Muestra la lista de archivos.
     */
    public function list()
    {
        $files = File::all();
        return view('admin.files.index', compact('files'));
    }

    /**
     * Elimina un archivo.
     */
    public function destroy(File $file)
    {
        Storage::delete($file->ruta);
        $file->delete();
        return redirect()->back()->with('success', 'Archivo eliminado correctamente.');
    }

    /**
     * Muestra el contenido del archivo (opcional).
     */
    public function showContent(File $file)
    {
        return view('files.content', ['file' => $file]);
    }

    /**
     * Permite actualizar el contenido manualmente desde el panel.
     */
    public function updateContent(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $file = File::findOrFail($id);
        $file->content = $request->input('content');
        $file->save();

        return redirect()->back()->with('success', 'Contenido actualizado correctamente.');
    }
}
