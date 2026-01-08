<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Upload Assessment</title>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spark-md5/3.0.2/spark-md5.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; font-family: sans-serif; }
        .section { margin-bottom: 40px; border: 1px solid #eee; padding: 20px; border-radius: 8px; }
        #status-box { margin-top: 10px; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container">
    <div class="section">
        <h1>1. Bulk CSV Import</h1>
        <form action="/import/products" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Start Bulk Upsert</button>
        </form>
    </div>

    <div class="section">
        <h1>2. Chunked Image Upload</h1>
        <div style="margin-bottom: 15px;">
            <label>Target Product ID (for testing):</label>
            <input type="number" id="product_id" value="1">
        </div>
        
        <form action="/upload/chunk" class="dropzone" id="my-dropzone"></form>
        <div id="status-box"></div>
    </div>
</div>

<script>
    // Utility to calculate MD5 Checksum on the client
    function calculateMD5(file) {
        return new Promise((resolve, reject) => {
            const blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice;
            const chunks = Math.ceil(file.size / 2097152); // 2MB chunks for hashing
            let currentChunk = 0;
            const spark = new SparkMD5.ArrayBuffer();
            const fileReader = new FileReader();

            fileReader.onload = (e) => {
                spark.append(e.target.result);
                currentChunk++;
                if (currentChunk < chunks) {
                    loadNext();
                } else {
                    resolve(spark.end());
                }
            };
            fileReader.onerror = () => reject('MD5 calculation failed');

            function loadNext() {
                const start = currentChunk * 2097152;
                const end = ((start + 2097152) >= file.size) ? file.size : start + 2097152;
                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
            }
            loadNext();
        });
    }

    Dropzone.options.myDropzone = {
        url: '/upload/chunk',
        chunking: true,
        forceChunking: true,
        chunkSize: 2 * 1024 * 1024, // 2MB
        parallelChunkUploads: false,
        retryChunks: true,
        retryChunksLimit: 3,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        init: function() {
            const statusBox = document.getElementById('status-box');

            // Before upload starts, calculate MD5
            this.on("addedfile", async function(file) {
                statusBox.innerHTML = "Calculating checksum...";
                file.checksum = await calculateMD5(file);
                statusBox.innerHTML = `Checksum: ${file.checksum}`;
            });

            // Attach filename to every chunk request
            this.on("sending", function(file, xhr, formData) {
                formData.append("filename", file.name);
            });

            // When all chunks are done, fetch calling to /upload/complete
            this.on("success", function(file) {
                statusBox.innerHTML = "Chunks uploaded. Finalizing...";

                fetch('/upload/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        filename: file.name,
                        checksum: file.checksum, // The actual MD5 we calculated
                        product_id: document.getElementById('product_id').value,
                        totalChunks: file.upload.totalChunkCount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'processing' || data.status === 'completed' || data.status === 'already_exists') {
                        statusBox.innerHTML = `<span style="color: green">✔ Upload Successful: ${data.status}</span>`;
                    } else {
                        statusBox.innerHTML = `<span style="color: red">✖ Error: ${data.error}</span>`;
                    }
                })
                .catch(err => {
                    statusBox.innerHTML = `<span style="color: red">✖ Finalization failed.</span>`;
                });
            });
        }
    };
</script>
</body>
</html>