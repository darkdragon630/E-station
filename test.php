<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Distribusi Gamma</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .graph-section {
            margin-bottom: 50px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .graph-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .graph-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .chart-container {
            position: relative;
            height: 400px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1976D2;
            font-size: 16px;
        }
        .info-box ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .info-box li {
            color: #555;
            font-size: 13px;
            margin: 5px 0;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .download-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: background 0.3s;
        }
        .download-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Grafik Distribusi Gamma</h1>
        <div class="subtitle">UTS Statistika dan Probabilitas 2025 - Muhammad Burhanudin Syaifullah Azmi</div>

        <!-- GRAFIK 1: PDF dengan berbagai k -->
        <div class="graph-section">
            <div class="graph-title">Grafik 1: PDF Distribusi Gamma untuk Berbagai Nilai k</div>
            <div class="graph-description">
                Grafik ini menunjukkan bagaimana bentuk distribusi Gamma berubah berdasarkan parameter shape (k). 
                Semakin besar k, distribusi semakin mendekati bentuk Normal dan semakin sedikit skewness-nya.
            </div>
            <div class="chart-container">
                <canvas id="chart1"></canvas>
            </div>
            <button class="download-btn" onclick="downloadChart('chart1', 'Grafik_1_PDF_Gamma_Berbagai_K.png')">
                💾 Download Grafik 1
            </button>
            <div class="info-box">
                <h4>📌 Interpretasi:</h4>
                <ul>
                    <li><strong>k = 0.5 (Biru):</strong> Sangat skewed, menurun drastis dari 0</li>
                    <li><strong>k = 2 (Hijau):</strong> Moderately skewed, ada peak yang jelas</li>
                    <li><strong>k = 5 (Merah):</strong> Mendekati Normal, lebih simetris</li>
                </ul>
            </div>
        </div>

        <!-- GRAFIK 2: PDF untuk k=2.3, θ=2.6 -->
        <div class="graph-section">
            <div class="graph-title">Grafik 2: PDF Distribusi Gamma (k=2.3, θ=2.6)</div>
            <div class="graph-description">
                Grafik ini menunjukkan distribusi waktu tunggu dengan parameter yang sesuai soal. 
                Peak berada di sekitar <span class="highlight">3-4 menit</span> dengan mean ≈ 6 menit.
            </div>
            <div class="chart-container">
                <canvas id="chart2"></canvas>
            </div>
            <button class="download-btn" onclick="downloadChart('chart2', 'Grafik_2_PDF_Gamma_k2.3_theta2.6.png')">
                💾 Download Grafik 2
            </button>
            <div class="info-box">
                <h4>📌 Parameter:</h4>
                <ul>
                    <li>Shape (k) = 2.3</li>
                    <li>Scale (θ) = 2.6</li>
                    <li>Mean = k × θ = 5.98 ≈ 6 menit</li>
                    <li>Variance = k × θ² = 15.548</li>
                    <li>Standar Deviasi = 3.94 menit</li>
                </ul>
            </div>
        </div>

        <!-- GRAFIK 3: PDF dengan area P(X > 12) -->
        <div class="graph-section">
            <div class="graph-title">Grafik 3: PDF dengan Area P(X > 12)</div>
            <div class="graph-description">
                Grafik ini menunjukkan area di bawah kurva untuk x > 12 menit (area berwarna merah). 
                Area ini merepresentasikan probabilitas <span class="highlight">P(X > 12) ≈ 8.04%</span>.
            </div>
            <div class="chart-container">
                <canvas id="chart3"></canvas>
            </div>
            <button class="download-btn" onclick="downloadChart('chart3', 'Grafik_3_PDF_Area_P(X_greater_12).png')">
                💾 Download Grafik 3
            </button>
            <div class="info-box">
                <h4>📌 Interpretasi:</h4>
                <ul>
                    <li>Area merah = P(X > 12) ≈ <strong>8.04%</strong></li>
                    <li>≈ 8 dari 100 penumpang menunggu lebih dari 12 menit</li>
                    <li>≈ 92% penumpang menunggu kurang dari 12 menit</li>
                </ul>
            </div>
        </div>

        <!-- GRAFIK 4: CCDF (Survival Function) -->
        <div class="graph-section">
            <div class="graph-title">Grafik 4: CCDF / Survival Function P(X > x)</div>
            <div class="graph-description">
                Grafik ini menunjukkan probabilitas waktu tunggu lebih dari x menit. 
                Pada x = 12 menit, probabilitas adalah <span class="highlight">0.0804 (8.04%)</span>.
            </div>
            <div class="chart-container">
                <canvas id="chart4"></canvas>
            </div>
            <button class="download-btn" onclick="downloadChart('chart4', 'Grafik_4_CCDF_Survival_Function.png')">
                💾 Download Grafik 4
            </button>
            <div class="info-box">
                <h4>📌 Interpretasi:</h4>
                <ul>
                    <li>Kurva menurun eksponensial dari kiri ke kanan</li>
                    <li>Titik (12, 0.08) menunjukkan P(X > 12) = 8%</li>
                    <li>Semakin besar x, semakin kecil probabilitas tunggu lebih lama</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Gamma menggunakan pendekatan Stirling
        function gamma(z) {
            if (z < 0.5) {
                return Math.PI / (Math.sin(Math.PI * z) * gamma(1 - z));
            }
            z -= 1;
            const p = [
                676.5203681218851, -1259.1392167224028,
                771.32342877765313, -176.61502916214059,
                12.507343278686905, -0.13857109526572012,
                9.9843695780195716e-6, 1.5056327351493116e-7
            ];
            let y = 0.99999999999980993;
            for (let i = 0; i < p.length; i++) {
                y += p[i] / (z + i + 1);
            }
            const t = z + p.length - 0.5;
            return Math.sqrt(2 * Math.PI) * Math.pow(t, z + 0.5) * Math.exp(-t) * y;
        }

        // PDF Gamma
        function gammaPDF(x, k, theta) {
            if (x <= 0) return 0;
            return (Math.pow(x, k - 1) * Math.exp(-x / theta)) / (gamma(k) * Math.pow(theta, k));
        }

        // Incomplete Gamma (untuk CDF) - pendekatan numerik
        function incompleteGamma(k, x) {
            if (x <= 0) return 0;
            let sum = 0;
            let term = 1;
            for (let n = 0; n < 200; n++) {
                sum += term;
                term *= x / (k + n);
            }
            return Math.pow(x, k) * Math.exp(-x) * sum / gamma(k);
        }

        // CDF Gamma
        function gammaCDF(x, k, theta) {
            if (x <= 0) return 0;
            return incompleteGamma(k, x / theta);
        }

        // Generate data points
        function generateData(k, theta, min, max, points) {
            const data = [];
            const step = (max - min) / points;
            for (let i = 0; i <= points; i++) {
                const x = min + i * step;
                data.push({
                    x: x,
                    y: gammaPDF(x, k, theta)
                });
            }
            return data;
        }

        // Chart 1: Multiple k values
        const ctx1 = document.getElementById('chart1').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'k = 0.5 (θ = 2)',
                        data: generateData(0.5, 2, 0, 15, 200),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'k = 2 (θ = 2)',
                        data: generateData(2, 2, 0, 15, 200),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'k = 5 (θ = 2)',
                        data: generateData(5, 2, 0, 15, 200),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'PDF Distribusi Gamma untuk Berbagai Nilai k',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Waktu Tunggu (menit)',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0,
                        max: 15
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Probability Density',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0
                    }
                }
            }
        });

        // Chart 2: k=2.3, θ=2.6
        const ctx2 = document.getElementById('chart2').getContext('2d');
        const data2 = generateData(2.3, 2.6, 0, 20, 300);
        new Chart(ctx2, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'PDF Gamma (k=2.3, θ=2.6)',
                    data: data2,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'PDF Distribusi Gamma (k=2.3, θ=2.6)',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line',
                                xMin: 5.98,
                                xMax: 5.98,
                                borderColor: 'red',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Mean = 5.98',
                                    position: 'start'
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Waktu Tunggu (menit)',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0,
                        max: 20
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Probability Density',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0
                    }
                }
            }
        });

        // Chart 3: PDF with shaded area for P(X > 12)
        const ctx3 = document.getElementById('chart3').getContext('2d');
        const data3Full = generateData(2.3, 2.6, 0, 20, 300);
        const data3Before12 = data3Full.filter(d => d.x <= 12);
        const data3After12 = data3Full.filter(d => d.x >= 12);
        
        new Chart(ctx3, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'P(X ≤ 12) ≈ 92%',
                        data: data3Before12,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.3)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'P(X > 12) ≈ 8.04%',
                        data: data3After12,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'PDF dengan Area P(X > 12) = 8.04%',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Waktu Tunggu (menit)',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0,
                        max: 20
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Probability Density',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0
                    }
                }
            }
        });

        // Chart 4: CCDF (Survival Function)
        const ctx4 = document.getElementById('chart4').getContext('2d');
        const ccdfData = [];
        for (let x = 0; x <= 20; x += 0.1) {
            ccdfData.push({
                x: x,
                y: 1 - gammaCDF(x, 2.3, 2.6)
            });
        }
        
        new Chart(ctx4, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'CCDF: P(X > x)',
                        data: ccdfData,
                        borderColor: 'rgb(153, 102, 255)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0
                    },
                    {
                        label: 'Titik (12, 0.0804)',
                        data: [{x: 12, y: 0.0804}],
                        borderColor: 'red',
                        backgroundColor: 'red',
                        pointRadius: 8,
                        pointStyle: 'circle',
                        showLine: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'CCDF (Survival Function) - P(X > x)',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Waktu Tunggu (menit)',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0,
                        max: 20
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'P(X > x)',
                            font: { size: 14, weight: 'bold' }
                        },
                        min: 0,
                        max: 1
                    }
                }
            }
        });

        // Function to download chart as image
        function downloadChart(chartId, filename) {
            const canvas = document.getElementById(chartId);
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = filename;
            link.href = url;
            link.click();
        }
    </script>
</body>
</html>