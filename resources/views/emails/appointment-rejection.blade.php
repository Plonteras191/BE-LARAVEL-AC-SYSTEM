{{-- resources/views/emails/appointment-rejection.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Appointment Rejection</title>
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
            background-color: #e74c3c;
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
            background-color: #3498db;
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
        <h1>Appointment Update</h1>
    </div>

    <div class="content">
        <p>Dear {{ $appointmentData['name'] }},</p>

        <p>We regret to inform you that we are unable to accommodate your recent appointment request (ID: #{{ $appointmentData['id'] }}).</p>

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

        <p>This may be due to scheduling conflicts or availability issues. We sincerely apologize for any inconvenience this may cause.</p>

        <p>You're welcome to submit a new appointment request with alternative dates that might work better for you.</p>

        <p>If you have any questions or would like assistance in scheduling a new appointment, please don't hesitate to contact us.</p>

        <p>Thank you for your understanding.</p>

        <div style="text-align: center;">
            <a href="{{ url('/') }}" class="button">Book New Appointment</a>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Your AC Service Company. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
    </div>
</body>
</html>
