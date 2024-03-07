@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="dashboard-container">
        <span style="align-self: flex-start !important">Dashboard</span>
        <div class="button-container">
            <button type="button" class="btn btn-success add-folder" id="add-folder-btn">
                Agregar carpeta
            </button>
            <button type="button" class="btn btn-primary upload-file" data-toggle="modal" data-target="#modal-upload">
                Subir archivos
            </button>
        </div>
        

    </h1>
    <br>
    <div class="container" id="breadcrumbs">
        <a style="border: none; cursor: pointer; margin-right:5px" href="/">
            <i class="fa fa-home"></i>
        </a>
    </div>
    <div class="modal fade" id="modal-upload" data-backdrop="static" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Subir archivo</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="dropzone_upload_file">

                        <form action="/upload" class="dropzone card-tabs-container" id="fileUploader"
                            enctype="multipart/form-data">
                            <input type="hidden" name="path" id="imageUploadPath">
                            @csrf
                            <div class="fallback">
                                <input name="file" type="file" multiple />
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>

        </div>

    </div>
@stop

@section('content')
    <div class="container-fluid">


        <div id="files-js-grid">

        </div>
    </div>
@stop

@section('css')
    <style>
        .dashboard-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            /* Adjust width as needed */
        }

        .dashboard-text {
            font-weight: bold;
            /* Optional: Make "Dashboard" stand out */
        }

        .buttons-container {
            display: flex;
            gap: 10px;
            /* Adjust gap between buttons as desired */
        }

        .add-folder,
        .upload-file {
            padding: 5px 10px;
            /* Adjust padding as needed */
            border: 1px solid #ccc;
            /* Optional: Add border */
            border-radius: 5px;
            /* Optional: Add rounded corners */
            cursor: pointer;
            /* Indicate interaction on hover */
        }
    </style>
@stop

@section('js')
    <script>
        const host = '@php echo $_SERVER['APP_URL'] @endphp';
        const path = '@php echo $_GET['p'] ?? '' @endphp';
        const breadcrumbs = path.split('/');
        $(document).ready(() => {
            $('#imageUploadPath').val(path);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            if (breadcrumbs.length > 1) {
                let url = `/?p=`;
                breadcrumbs.forEach(element => {
                    if (element != '') {
                        url += `/${element}`;
                        $("#breadcrumbs").append(` / <a href="${url}">${element}</a>`);
                    }
                });
            }
            loadFilesTable();
            $("div#dropzone_upload_file").dropzone({
                url: host + "/upload"
            });
            $("#add-folder-btn").on('click',(e)=>{
                agregarCarpeta();
            });
        });

        async function cargarTabla() {
            $("#files-js-grid").jsGrid("loadData");
        }

        function loadFilesTable(data) {
            $('#files-js-grid').jsGrid({
                width: "100%",
                autoload: true,
                inserting: false,
                editing: false,
                delete: false,
                sorting: true,
                paging: true,
                controller: {
                    loadData: function(filter) {
                        return $.ajax({
                            type: "GET",
                            dataType: 'json',
                            url: "/data",
                            data: {
                                path: (path != '' ? path : undefined),
                                filter: filter
                            }
                        });
                    },
                },

                fields: [{
                        name: "nombre",
                        title: 'Nombre',
                        type: "text",
                        width: 150,
                        validate: "required",
                        itemTemplate: function(value, item) {
                            const icon = getIcon(item.mime)
                            const $customNameCell = $("<div>").click((e) => {
                                    if (value == '..') {
                                        const new_path = breadcrumbs.slice(0, -1).join("/");
                                        window.location.href = '/?p=' + new_path;
                                        return;
                                    }
                                    if (item.dir) {
                                        window.location.href = '/?p=' + path + '/' + item.nombre
                                            .replace('/', '') + '';
                                    } else {
                                        const temp_path = host + path + '/' + item.nombre;
                                        window.location.href = temp_path;
                                    }

                                }).append($(icon))
                                .append('&nbsp;' + item.nombre);
                            return $customNameCell;
                        }
                    },
                    {
                        name: 'dir',
                        visible: false
                    }, {
                        type: "control",
                        editButton: false,
                        deleteButton: false,
                        itemTemplate: function(value, item) {
                            if (item.nombre == '..')
                                return $("<div>");
                            var $result = jsGrid.fields.control.prototype.itemTemplate.apply(this,
                                arguments);

                            const $borrarOpc = $("<button>").attr({
                                style: "border: none; cursor: pointer; margin-right:5px"
                            }).click((e) => {
                                eliminarArchivo(item);
                            }).append($("<i class='fa fa-trash'></i>"));
                            const $editarNombreOpc = $("<button>").attr({
                                style: "border: none; cursor: pointer; margin-right:5px"
                            }).click((e) => {
                                editarNombreArchivo(item);
                            }).append($("<i class='fa fa-edit'></i>"));
                            const $enlaceDirectoOpc = $("<button>").attr({
                                style: "border: none; cursor: pointer; margin-right:5px"
                            }).click((e) => {
                                copiarEnlaceArchivo(item);
                            }).append($("<i class='fa fa-link'></i>"));
                            const $descargarOpc = $("<button>").attr({
                                style: "border: none; cursor: pointer; margin-right:5px"
                            }).click((e) => {
                                descargarArchivo(item);
                            }).append($("<i class='fa fa-download'></i>"));
                            return $("<div>").append($borrarOpc).append($editarNombreOpc).append(
                                $enlaceDirectoOpc).append($descargarOpc);
                        }
                    }
                ]
            });
        }

        function eliminarArchivo(item) {
            Swal.fire({
                title: "Desea eliminar el archivo: " + item.nombre + "?",
                text: "Al eliminarlo no podra deshacer el cambio",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si, borralo",
                preConfirm: async (login) => {
                    try {
                        const githubUrl = host + `/eliminar/?path=${breadcrumbs.join('/')}/${item.nombre}`;
                        const response = await fetch(githubUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (!response.ok) {
                            return Swal.showValidationMessage(`${JSON.stringify(await response.json())}`);
                        }
                        return response.json();
                    } catch (error) {
                        Swal.showValidationMessage(`Hubo un error al eliminar el archivo: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Eliminado!",
                        text: "Archivo: " + item.nombre + " ha sido eliminado.",
                        icon: "success"
                    });
                    cargarTabla();
                }
            });
        }

        function editarNombreArchivo(item) {
            Swal.fire({
                title: "Seguro que quiere cambiar el nombre?",
                input: "text",
                inputAttributes: {
                    autocapitalize: "off",
                },
                inputValue: item.nombre,
                showCancelButton: true,
                confirmButtonText: "Cambiar",
                cancelButtonText: "Cancelar",
                showLoaderOnConfirm: true,
                preConfirm: async (login) => {
                    try {
                        const githubUrl = host + `/editar/?new=${login}&old=${path}/${item.nombre}`;
                        const response = await fetch(githubUrl, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (!response.ok) {
                            return Swal.showValidationMessage(`${JSON.stringify(await response.json())}`);
                        }
                        return response.json();
                    } catch (error) {
                        Swal.showValidationMessage(`Hubo un error al cambiar el nombre: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    toastr.success('Archivo renombrado correctamente')
                    cargarTabla();
                }
            });
        }

        function copiarEnlaceArchivo(item) {
            navigator.clipboard.writeText(host + path + '/' + item.nombre);
            toastr.success('Enlace copiado en el portapapeles')
        }

        function descargarArchivo(item) {

        }

        function agregarCarpeta() {
            Swal.fire({
                title: "Nombre de carpeta",
                input: "text",
                inputAttributes: {
                    autocapitalize: "off",
                },
                showCancelButton: true,
                confirmButtonText: "Agregar",
                cancelButtonText: "Cancelar",
                showLoaderOnConfirm: true,
                preConfirm: async (nombre) => {
                    try {
                        const githubUrl = host + `/agregar-carpeta/?carpeta=${nombre}&path=${path}`;
                        const response = await fetch(githubUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (!response.ok) {
                            return Swal.showValidationMessage(`${JSON.stringify(await response.json())}`);
                        }
                        return response.json();
                    } catch (error) {
                        Swal.showValidationMessage(`Hubo un error al cambiar el nombre: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    toastr.success('Archivo renombrado correctamente')
                    cargarTabla();
                }
            });
        }

        function getIcon(mime) {
            // console.log(mime);
            var icon = 'fa fa-file-alt';
            var color = 'black';
            switch (mime) {
                case 'folder':
                    icon = 'far fa-folder';
                    color = '#0157b3;';
                    break;
                case 'return':
                    icon = 'fa fa-undo';
                    color = '#007bff;';
                    break;
                case 'image/png':
                case 'image/jpg':
                case 'image/jpeg':
                case 'image/gif':
                    icon = 'far fa-image';
                    color = '#26b99a;';
                    break;
                case 'text/x-php':
                    icon = 'fa fa-code';
                    color = '#cc4b4c;';
                    break;
                case 'application/octet-stream':
                    icon = 'fa fa-file-pdf';
                    color = '#FF5D49';
                    break;
                case 'video/mp4':
                    icon = "fas fa-play";
                    color = '#30C2FC;'
                    break;
            }
            return '<i class="' + icon + '" style="color:' + color + '"></i>'
        }
    </script>

    <script>
        Dropzone.options.fileUploader = {
            chunking: true,
            chunkSize: 2000000, // chunk size 2,000,000 bytes (~2MB)
            forceChunking: true,
            retryChunks: true,
            retryChunksLimit: 3,
            parallelUploads: 1,
            parallelChunkUploads: false,
            timeout: 120000,
            maxFilesize: "5000000000",
            init: function() {
                this.on("sending", function(file, xhr, formData) {
                    // let _path = (file.fullPath) ? file.fullPath : file.name;
                    // document.getElementById("fullpath").value = _path;
                    // xhr.ontimeout = (function() {
                    //     toastr.error('Error: Server Timeout');
                    // });
                }).on("success", function(res) {
                    let _response = JSON.parse(res.xhr.response);

                    if (_response.status == "error") {
                        toast(_response.info);
                    }
                }).on("error", function(file, response) {
                    toast(response);
                }).on('complete', function() {
                    toastr.success('Archivo subido correctamente');
                    cargarTabla();
                });
            }
        }
    </script>

@stop
