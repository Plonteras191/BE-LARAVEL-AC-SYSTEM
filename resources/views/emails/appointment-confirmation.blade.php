<!DOCTYPE html>
<html>
<head>
    <title>Appointment Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .service-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Appointment Confirmed</h1>
    </div>

    <div class="content">
        <p>Dear {{ $appointmentData['name'] }},</p>

        <p>We're pleased to confirm that your appointment has been accepted and scheduled. Here are the details:</p>

        <h3>Appointment ID: #{{ $appointmentData['id'] }}</h3>

        <h3>Service Details:</h3>
        @foreach($appointmentData['services'] as $index => $service)
            <div class="service-item">
                <p><strong>Service {{ $index + 1 }}:</strong> {{ $service['type'] }}</p>
                <p><strong>Date:</strong> {{ date('F j, Y', strtotime($service['date'])) }}</p>
                @if(count($service['ac_types']) > 0)
                    <p><strong>AC Types:</strong> {{ implode(', ', $service['ac_types']) }}</p>
                @endif
            </div>
        @endforeach

        <h3>Your Information:</h3>
        <p><strong>Name:</strong> {{ $appointmentData['name'] }}</p>
        <p><strong>Phone:</strong> {{ $appointmentData['phone'] }}</p>
        <p><strong>Email:</strong> {{ $appointmentData['email'] }}</p>
        <p><strong>Address:</strong> {{ $appointmentData['address'] }}</p>

        <p>Our technician will arrive at your location on the scheduled date. Please ensure someone is available to provide access to the premises.</p>

        <p>If you need to reschedule or have any questions, please contact us as soon as possible.</p>

        <p>Thank you for choosing our services!</p>

        <div style="text-align: center;">
            <a href="{{ url('/') }}" class="button">Visit Our Website</a>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Your AC Service Company. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
    </div>
</body>
</html>
