<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Thank you</title>
    <style>
        /* Inline styles for simplicity, consider using CSS classes for larger templates */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f1f1f1;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 200px;
        }

        .message {
            padding: 20px;
            background-color: #ffffff;
        }

        .message p {
            margin-bottom: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="message">
            <p>Dear {{ $name }},</p>
            <p>Thank you for chosing our dorm as part of your academic journey in Mariano Marcos State university.</p>
            <p>Rest assured that we will give our best services we can offer.</p>

            <p>Dorm Details Here.</p>
        </div>
        <div class="message">
            <p>Please visit us within 24 hours upon receiving this email to sign contract or else your reservevation will be forfited.</p>

        </div>

    </div>
</body>

</html>