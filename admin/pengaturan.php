<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situs Sedang Dalam Pengembangan</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        .container {
            background: #36abd9;
            background: linear-gradient(169deg, rgba(54, 171, 217, 1) 0%, rgba(204, 197, 143, 1) 100%); 
            padding: 20px;
            border-radius: 60px;
            box-shadow: 0 2px 4px rgb(0 0 0 / 10%);
            display: inline-block;
            max-width: 500px;
            width: 100%;
            margin-top: 100px;
            animation: fadeIn 1s ease-in-out
        }

        h1 {
            font-size: 2.5em;
            color: #333;
        }

        p {
            font-size: 1.2em;
            margin-top: 20px;
            color: #666;
        }

        .tombol {
            margin-top: 30px;
            padding: 10px 20px;
            background-color: #36abd9;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(54, 171, 217, 1);
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Situs Sedang Dalam Pengembangan</h1>
        <p>Mohon maaf atas ketidaknyamanannya. Kami akan segera kembali.</p>
    </div>
    <div>
    <button class="tombol">
        <a href = "javascript:history.back()">Kembali</a>
    </button>
    </div>
</body>
<script>
    // animasi fade in
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector('.container').style.opacity = 0;
        setTimeout(function() {
            document.querySelector('.container').style.transition = "opacity 1s";
            document.querySelector('.container').style.opacity = 1;
        },100)
    })

    // tombol kembali
    document.querySelector('.tombol a').addEventListener('click', function(event) {
        event.preventDefault();
        window.history.back();
    })
</script>
</html>