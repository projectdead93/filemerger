<!DOCTYPE html>
<html>
<head>
    <title>PDF Merger</title>
</head>
<body>
    <h1>Merge PDF Files</h1>

    <form action="/index.php?action=upload" method="POST" enctype="multipart/form-data">
        <label for="files">Select PDF files to merge:</label><br>
        <input type="file" name="files[]" id="files" multiple accept="application/pdf" required><br><br>

        <label for="output_dir">Destination folder for merged file(s):</label><br>
        <input type="text" name="output_dir" id="output_dir" placeholder="C:\path\to\output" required><br><br>

        <button type="submit">Start Merge</button>
    </form>
</body>
</html>