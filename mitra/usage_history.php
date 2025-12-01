<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situs Sedang Dalam Pengembangan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url("https://freeimage.host/i/fxJu7DJ");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }
        
        /* Overlay untuk meningkatkan keterbacaan teks */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            z-index: 0;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            margin: 20px;
            animation: fadeIn 1s ease-in-out;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .construction-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #36abd9;
            animation: bounce 2s infinite;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        p {
            font-size: 1.2rem;
            margin-top: 20px;
            color: #666;
            line-height: 1.6;
        }
        
        .highlight {
            color: #36abd9;
            font-weight: 600;
        }
        
        .tombol {
            margin-top: 30px;
            padding: 12px 30px;
            background: linear-gradient(90deg, #36abd9, #2a8bc0);
            border: none;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(54, 171, 217, 0.4);
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
            position: relative;
            z-index: 1;
        }
        
        .tombol:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 171, 217, 0.6);
        }
        
        .tombol a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            display: block;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            margin: 30px 0 20px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 65%;
            background: linear-gradient(90deg, #36abd9, #fdbb2d);
            border-radius: 4px;
            animation: progress 2s ease-in-out infinite alternate;
        }
        
        .contact-info {
            margin-top: 25px;
            font-size: 1rem;
            color: #777;
        }
        
        .social-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #36abd9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
            background: #2a8bc0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes progress {
            0% { transform: translateX(-5%); }
            100% { transform: translateX(5%); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1.1rem;
            }
            
            .construction-icon {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 1.8rem;
            }
            
            p {
                font-size: 1rem;
            }
            
            .construction-icon {
                font-size: 2.5rem;
            }
            
            .tombol {
                padding: 10px 25px;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class="construction-icon">🚧</div>
        <h1>Situs Sedang Dalam Pengembangan</h1>
        <p>Mohon maaf atas ketidaknyamanannya. Kami sedang bekerja keras untuk menyelesaikan situs ini secepat mungkin.</p>
        <p>Perkiraan waktu penyelesaian: <span class="highlight">2-3 minggu</span></p>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        
        <div class="contact-info">
            Untuk informasi lebih lanjut, hubungi: <span class="highlight">admin@example.com</span>
        </div>
        
        <div class="social-links">
            <a href="#" class="social-icon">f</a>
            <a href="#" class="social-icon">in</a>
            <a href="#" class="social-icon">ig</a>
        </div>
    </div>
    
    <div>
        <button class="tombol">
            <a href="javascript:history.back()">Kembali ke Halaman Sebelumnya</a>
        </button>
    </div>
</body>
<script>
    // Animasi fade in
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector('.container').style.opacity = 0;
        setTimeout(function() {
            document.querySelector('.container').style.transition = "opacity 1s";
            document.querySelector('.container').style.opacity = 1;
        }, 100);
        
        // Animasi untuk tombol
        setTimeout(function() {
            document.querySelector('.tombol').style.opacity = 0;
            document.querySelector('.tombol').style.transition = "opacity 0.5s";
            document.querySelector('.tombol').style.opacity = 1;
        }, 800);
    });

    // Tombol kembali
    document.querySelector('.tombol a').addEventListener('click', function(event) {
        event.preventDefault();
        window.history.back();
    });
</script>
</html>