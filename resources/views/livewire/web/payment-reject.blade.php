<!DOCTYPE html>
<html lang="ka" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>გადახდა უარყოფილი - IAPI.GE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ff6900;
            --error-color: #ef4444;
            --dark-color: #252525;
            --light-color: #f8f9fa;
            --warning-color: #f59e0b;
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

        .decline-container {
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

        .decline-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            padding: 60px 40px;
            text-align: center;
            border: 2px solid var(--light-color);
            position: relative;
            overflow: hidden;
        }

        .decline-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--error-color), var(--warning-color));
        }

        .error-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, var(--error-color), #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            position: relative;
            animation: iconShake 0.6s ease-out;
        }

        @keyframes iconShake {
            0% {
                transform: scale(0) rotate(-45deg);
            }
            50% {
                transform: scale(1.1) rotate(0deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
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

        .error-details {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(239, 68, 68, 0.04));
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 35px;
            border-left: 4px solid var(--error-color);
            text-align: left;
        }

        .error-code {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .error-code-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-right: 10px;
        }

        .error-code-value {
            color: var(--error-color);
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            font-weight: 600;
        }

        .error-message {
            color: var(--dark-color);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
            margin-top: 10px;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--error-color), #dc2626);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .reason-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
        }

        .reason-list li {
            padding: 10px 0;
            color: #666;
            font-size: 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
            display: flex;
            align-items: flex-start;
        }

        .reason-list li:last-child {
            border-bottom: none;
        }

        .reason-list li:before {
            content: '✕';
            color: var(--error-color);
            font-weight: 700;
            margin-right: 10px;
            font-size: 16px;
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

        .help-box {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(245, 158, 11, 0.02));
            border-left: 4px solid var(--warning-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .help-box strong {
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

        .tip-section {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid var(--primary-color);
            color: #0369a1;
            font-size: 13px;
            line-height: 1.6;
        }

        .tip-section strong {
            color: var(--primary-color);
        }

        /* ✅ Mobile responsiveness */
        @media (max-width: 576px) {
            .decline-card {
                padding: 40px 25px;
            }

            h1 {
                font-size: 24px;
            }

            .error-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                padding: 14px 20px;
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

        .warning-pulse {
            animation: warningPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes warningPulse {
            0%, 100% {
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            }
            50% {
                box-shadow: 0 20px 60px rgba(239, 68, 68, 0.15);
            }
        }
    </style>
</head>
<body>
<div class="decline-container">
    <div class="decline-card warning-pulse">
        <!-- Icon -->
        <div class="error-icon fade-in">
            ✕
        </div>
        <div class="fade-in">
            <div class="status-badge">გადახდა ვერ შესრულდა</div>
        </div>
        <div class="fade-in" style="margin-top: 40px;">
            <a href="{{ route('web.main.index') }}" class="btn-primary">მაღაზიაში დაბრუნება</a>
        </div>
        <div class="contact-support fade-in">
            <p>მაინც არ მუშაობს? <a href="mailto:info@iapi.ge">დაგვიკავშირდით</a> ან დაარეკეთ <strong>+995 555 700 720</strong></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>