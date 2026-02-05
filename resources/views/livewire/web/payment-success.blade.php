<!DOCTYPE html>
<html lang="ka" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>გადახდა წარმატებული - IAPI.GE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ff6900;
            --dark-color: #252525;
            --light-color: #f8f9fa;
            --success-color: #10b981;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Neue Helvetica', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            padding: 60px 40px;
            text-align: center;
            border: 2px solid var(--light-color);
            position: relative;
            overflow: hidden;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--success-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            position: relative;
            animation: iconBounce 0.8s ease-out;
        }

        @keyframes iconBounce {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .checkmark {
            animation: checkmarkDraw 0.6s ease-out;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
        }

        @keyframes checkmarkDraw {
            to {
                stroke-dashoffset: 0;
            }
        }

        h1 {
            color: var(--dark-color);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .order-details {
            background: var(--light-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 35px;
            border-left: 4px solid var(--primary-color);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .detail-value {
            color: var(--dark-color);
            font-size: 15px;
            font-weight: 600;
        }

        .amount {
            font-size: 28px;
            color: var(--primary-color);
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .confirmation-code {
            background: white;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 2px;
            word-break: break-all;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #ff8c42);
            border: none;
            border-radius: 12px;
            padding: 16px 40px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(255, 105, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(255, 105, 0, 0.3);
            color: white;
        }

        .btn-secondary {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            cursor: pointer;
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--light-color);
        }

        .info-box {
            background: linear-gradient(135deg, rgba(255, 105, 0, 0.05), rgba(255, 105, 0, 0.02));
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box strong {
            color: var(--dark-color);
            display: block;
            margin-bottom: 8px;
        }

        .contact-support {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            color: #999;
            font-size: 13px;
        }

        .contact-support a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .contact-support a:hover {
            text-decoration: underline;
        }

        /* ✅ Mobile responsiveness */
        @media (max-width: 576px) {
            .success-card {
                padding: 40px 25px;
            }

            h1 {
                font-size: 24px;
            }

            .success-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
            }

            .amount {
                font-size: 24px;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                padding: 14px 20px;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-value {
                margin-top: 5px;
            }
        }

        /* ✅ Animation for elements */
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        .fade-in:nth-child(1) { animation-delay: 0.2s; }
        .fade-in:nth-child(2) { animation-delay: 0.3s; }
        .fade-in:nth-child(3) { animation-delay: 0.4s; }
        .fade-in:nth-child(4) { animation-delay: 0.5s; }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* ✅ Loading animation for checkmark */
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body>
<div class="success-container">
    <div class="success-card">
        <div class="success-icon fade-in">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" class="checkmark">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="fade-in">
            <div class="status-badge">✓ წარმატებული</div>
        </div>
        <h1 class="fade-in">შეკვეთა მიღებულია!</h1>
        <div class="info-box fade-in">
            <strong>📦 რა ხდება შემდეგ?</strong>
            ჩვენი ოპერატორი მალე დაგიკავშირდებათ შეკვეთის დასადასტურებლად!
        </div>
        <div class="contact-support fade-in">
            <p>გაქვთ კითხვა? <a href="mailto:info@iapi.ge">დაგვიკავშირდით</a> ან დაარეკეთ <strong>+995 555 700 720</strong></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>