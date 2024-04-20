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
            <p>Your Subscription han been approve.</p>
            <p>Here are the credentials you ca use to log in</p>


        </div>
        <div class="message">
            <p><strong>email:</strong>{{$email}} <br>
                <strong>password:</strong>{{$password}}
            </p>
            <a href="http://localhost:8012/login"> Click here to log in</a>
        </div>

    </div>
</body>

</html>