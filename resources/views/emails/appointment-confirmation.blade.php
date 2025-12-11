{{-- resources/views/emails/appointment-confirmation.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .success-icon {
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
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #667eea;
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
            color: #667eea;
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
        .customer-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 5px;
            color: #856404;
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
            <div class="success-icon">✓</div>
            <h1>Appointment Confirmed!</h1>
            <p style="margin: 0; opacity: 0.9;">Your booking has been successfully accepted</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $appointmentData['name'] }},</p>

            <p class="intro-text">
                Great news! We're pleased to confirm that your appointment has been accepted and scheduled.
                Below are the complete details of your booking:
            </p>

            <div class="info-box">
                <h3>📋 Appointment Reference</h3>
                <p style="font-size: 20px; font-weight: 600; color: #667eea; margin: 0;">#{{ $appointmentData['id'] }}</p>
            </div>

            <div class="info-box">
                <h3>🔧 Service Details</h3>
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

            <div class="customer-info">
                <h3 style="color: #667eea; font-size: 16px; margin-bottom: 15px; font-weight: 600;">👤 Your Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $appointmentData['name'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $appointmentData['phone'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $appointmentData['email'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">{{ $appointmentData['address'] }}</span>
                </div>
            </div>

            <div class="note">
                <strong>⚠️ Important Reminder:</strong><br>
                Our technician will arrive at your location on the scheduled date. Please ensure someone is
                available to provide access to the premises.
            </div>

            <p style="color: #555; margin-bottom: 10px;">
                If you need to reschedule or have any questions, please contact us as soon as possible.
            </p>

            <p style="color: #333; font-weight: 600; margin-bottom: 0;">
                Thank you for choosing our services!
            </p>

            <div class="button-container">
                <a href="{{ url('/') }}" class="button">Visit Our Website</a>
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
