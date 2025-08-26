@extends('layouts.app') {{-- O tu layout específico para admin, ej. layouts.admin --}}

@section('content')
<div class="container mx-auto p-6 bg-white shadow-lg rounded-lg mt-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Gestión de Archivos para la IA</h1>

    {{-- Formulario de Subida de Archivos --}}
    <div class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Subir Nuevo Archivo</h2>
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">¡Éxito!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">¡Error!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('admin.files.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700">Seleccionar archivo (PDF, TXT, DOCX)</label>
                <input type="file" name="file" id="file" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" required>
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Subir Archivo
            </button>
        </form>
    </div>

    {{-- Lista de Archivos Subidos --}}
    <div class="p-6 border border-gray-200 rounded-lg shadow-sm">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Archivos Subidos</h2>
        @if ($files->isEmpty())
            <p class="text-gray-500 text-center">No hay archivos subidos aún.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($files as $file)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $file->nombre }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $file->mime_type }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $file->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {{-- Botón para ver contenido (opcional) --}}
                                    <button onclick="viewFileContent('{{ $file->id }}')" class="text-blue-600 hover:text-blue-900 mr-3">Ver</button>
                                    {{-- Botón para eliminar --}}
                                    <form action="{{ route('admin.files.destroy', $file) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este archivo? Esto afectará al conocimiento del chatbot.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal para mostrar contenido de archivo (simplificado) --}}
<div id="fileContentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <h3 class="text-xl font-bold mb-4">Contenido del Archivo</h3>
        <div id="modalFileContent" class="whitespace-pre-wrap max-h-96 overflow-y-auto border p-3 rounded bg-gray-50 text-gray-800"></div>
        <div class="mt-4 text-right">
            <button onclick="document.getElementById('fileContentModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Cerrar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    async function viewFileContent(fileId) {
        try {
            const response = await fetch(`/admin/files/${fileId}/content`); // Nueva ruta para obtener contenido
            const data = await response.json();

            if (data.success) {
                document.getElementById('modalFileContent').innerText = data.content;
                document.getElementById('fileContentModal').classList.remove('hidden');
            } else {
                alert('Error al obtener el contenido del archivo: ' + data.message);
            }
        } catch (error) {
            console.error('Error de red al obtener contenido del archivo:', error);
            alert('Error de conexión al intentar ver el contenido del archivo.');
        }
    }
</script>
@endpush
