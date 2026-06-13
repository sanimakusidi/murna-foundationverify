

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Forgot Password | Murna Foundation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

           :root {
            --green: #00A651;
            --green-light: #00C960;
            --green-dark: #007A3D;
            --blue: #002D62;
            --blue-mid: #003f8a;
            --black: #080f1a;
            --black-soft: #0d1625;
            --card: #0f1e38;
            --card-light: #162840;
            --border: rgba(255,255,255,0.07);
            --text: #c8d8f0;
            --text-muted: #6e88ab;
            --white: #ffffff;
        }
        
       
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
           background: linear-gradient(135deg, #002D62 0%, #007A3D 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            line-height: 1.5;
            color: #1e2a32;
        }

        /* Main card container */
        .reset-card {
            background: #ffffff;
            max-width: 500px;
            width: 100%;
            border-radius: 28px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.02);
            padding: 2rem 2rem 2.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .reset-card:hover {
            box-shadow: 0 25px 40px -14px rgba(0, 0, 0, 0.15);
        }

        /* Branding / logo area */
        .brand {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .brand-icon {
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .brand h1 {
            font-size: 1.85rem;
            font-weight: 600;
            letter-spacing: -0.3px;
            background: linear-gradient(125deg, #1b6b4c, #2c8f6b);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .brand .sub {
            font-size: 0.9rem;
            color: #5a6e7a;
            margin-top: 0.25rem;
        }

        /* Form title */
        .form-title {
            font-size: 1.65rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1c3a2f;
        }

        .form-description {
            font-size: 0.95rem;
            color: #4a5b66;
            margin-bottom: 1.8rem;
            border-left: 3px solid #2c8f6b;
            padding-left: 1rem;
            background: #f9fbfc;
            border-radius: 0 12px 12px 0;
        }

        /* Input group */
        .input-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #1e2a32;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: #f5f9fc;
            border: 1px solid #cbdbe0;
            border-radius: 18px;
            transition: all 0.2s;
            padding: 0.5rem 1rem;
        }

        .input-wrapper:focus-within {
            border-color: #2c8f6b;
            box-shadow: 0 0 0 3px rgba(44, 143, 107, 0.2);
            background: #ffffff;
        }

        .input-icon {
            margin-right: 0.75rem;
            font-size: 1.25rem;
            color: #6c8b7a;
        }

        input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 1rem;
            padding: 0.6rem 0;
            outline: none;
            font-family: inherit;
            color: #1e2a32;
        }

        input::placeholder {
            color: #9aaeb9;
            font-weight: 400;
        }

        /* Button */
        .btn-submit {
            background: #1b6b4c;
            color: white;
            border: none;
            width: 100%;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .btn-submit:hover {
            background: #13563e;
            transform: scale(1.01);
            box-shadow: 0 8px 18px rgba(27, 107, 76, 0.25);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        /* back to login */
        .back-link {
            text-align: center;
            margin-top: 1.8rem;
            font-size: 0.9rem;
        }

        .back-link a {
            color: #1b6b4c;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px dotted #9bc9b6;
        }

        .back-link a:hover {
            color: #0e4a35;
            border-bottom-color: #0e4a35;
        }

        /* alert / message simulation (can be dynamic later) */
        .info-message {
            background: #eef4f0;
            border-left: 4px solid #2c8f6b;
            padding: 0.8rem 1rem;
            border-radius: 16px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            color: #1e3a2f;
        }

        /* footer */
        .footer-note {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #5a6c6e;
            max-width: 460px;
        }

        /* Responsive */
        @media (max-width: 550px) {
            .reset-card {
                padding: 1.6rem;
            }
            .form-title {
                font-size: 1.45rem;
            }
            .brand h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="brand">
            <div class="brand-icon">  <img 
                src="murna.jpg" 
                alt="Murna Logo"
                style="
                    width:150px;
                    height:150px;
                    object-fit:cover;
                    border-radius:20px;
                    padding:9px;
                    background:#fff;
                    box-shadow:0 2px 8px rgba(0,0,0,0.15);
                    border:2px solid #00A651;
                "
                ></div>
            <h1>Murna Foundation Verification</h1>
           
        </div>

        <div class="form-title">Forgot password?</div>
        <div class="form-description">
            Enter your registered email address and we’ll send you a secure link to reset your password.
        </div>

        <!-- Simple info note: you may replace with dynamic session messages later -->
        <div class="info-message">
            🔐 A reset link will be valid for 30 minutes.
        </div>

        <form action="process_forgot.php" method="POST">
            <div class="input-group">
                <label for="email">Email address</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" id="email" placeholder="hello@example.com" required autocomplete="email">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>✉️</span> Send reset link
            </button>

            <div class="back-link">
                <a href="index.php">← Back to sign in</a>
            </div>
        </form>
    </div>
    <div class="footer-note">
        Murna Foundation — secure password recovery
    </div>
</body>
</html>






