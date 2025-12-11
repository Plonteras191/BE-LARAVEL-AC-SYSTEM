{{-- resources/views/emails/appointment-rejection.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Cancellation Notice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .info-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .intro-text {
            color: #555;
            margin-bottom: 30px;
            font-size: 15px;
        }
        .info-box {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #e65100;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .service-item {
            background-color: #ffffff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .service-item:last-child {
            margin-bottom: 0;
        }
        .service-label {
            color: #f5576c;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }
        .detail-value {
            color: #333;
        }
        .note {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 25px 0;
            border-radius: 5px;
            color: #0d47a1;
            font-size: 14px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 13px;
        }
        .footer p {
            margin-bottom: 5px;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .content {
                padding: 25px 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="info-icon">ℹ️</div>
            <h1>Appointment Cancelled</h1>
            <p style="margin: 0; opacity: 0.9;">Important update regarding your booking</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $appointmentData['name'] }},</p>

            <p class="intro-text">
                We regret to inform you that we are unable to accommodate your recent appointment request.
                We sincerely apologize for any inconvenience this may cause.
            </p>

            <div class="info-box">
                <h3>📋 Cancelled Appointment Reference</h3>
                <p style="font-size: 20px; font-weight: 600; color: #e65100; margin: 0;">#{{ $appointmentData['id'] }}</p>
            </div>

            <div style="background-color: #f8f9fa; border-left: 4px solid #f5576c; padding: 20px; margin-bottom: 25px; border-radius: 5px;">
                <h3 style="color: #f5576c; font-size: 16px; margin-bottom: 15px; font-weight: 600;">🔧 Service Details</h3>
                @foreach($appointmentData['services'] as $index => $service)
                    <div class="service-item">
                        <div class="service-label">Service {{ $index + 1 }}: {{ $service['type'] }}</div>
                        <div class="detail-row">
                            <span class="detail-label">📅 Date:</span>
                            <span class="detail-value">{{ date('l, F j, Y', strtotime($service['date'])) }}</span>
                        </div>
                        @if(count($service['ac_types']) > 0)
                            <div class="detail-row">
                                <span class="detail-label">❄️ AC Types:</span>
                                <span class="detail-value">{{ implode(', ', $service['ac_types']) }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="note">
                <strong>💡 What's Next?</strong><br>
                This cancellation may be due to scheduling conflicts or availability issues.
                You're welcome to submit a new appointment request with alternative dates that might work better for you.
            </div>

            <p style="color: #555; margin-bottom: 20px;">
                If you have any questions or would like assistance in scheduling a new appointment,
                please don't hesitate to contact us. Our team is here to help!
            </p>

            <p style="color: #333; font-weight: 600; margin-bottom: 0;">
                Thank you for your understanding.
            </p>

            <div class="button-container">
                <a href="{{ url('/') }}" class="button">Book New Appointment</a>
            </div>
        </div>

        <div class="footer">
            <p style="font-weight: 600; color: #495057;">AC Service Company</p>
            <p>© {{ date('Y') }} All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                This is an automated email. Please do not reply directly to this message.
            </p>
        </div>
    </div>
</body>
</html>
