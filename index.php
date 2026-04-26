<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUP System - Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .setup-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            margin: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .setup-button:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ TUP System Database Setup</h1>
        <p>This will create all necessary database tables for the TUP Portal system.</p>
        
        <button class="setup-button" onclick="setupDatabases()">
            ▶️ Run Database Setup
        </button>

        <div id="message" class="message"></div>
    </div>

    <script>
        function setupDatabases() {
            const button = document.querySelector('.setup-button');
            const messageDiv = document.getElementById('message');
            
            button.disabled = true;
            button.textContent = 'Setting up...';
            messageDiv.innerHTML = '';
            messageDiv.className = 'message';

            // Create an iframe to run setup scripts
            const setupFrame = document.createElement('iframe');
            setupFrame.style.display = 'none';
            setupFrame.src = 'javascript:void(0)';
            document.body.appendChild(setupFrame);

            // Run setup scripts
            fetch('setup_database.php')
                .then(response => response.text())
                .then(data => {
                    fetch('setup_registrar_db.php')
                        .then(response => response.text())
                        .then(data2 => {
                            button.disabled = false;
                            button.textContent = '✓ Setup Complete!';
                            messageDiv.innerHTML = `
                                <strong>✓ All databases have been set up successfully!</strong><br>
                                <br>
                                You can now:<br>
                                • <a href="templates/admission.php">Go to Admissions</a><br>
                                • <a href="templates/login.php">Go to Login</a><br>
                                • <a href="templates/home.php">Go to Home</a>
                            `;
                            messageDiv.className = 'message success';
                        })
                        .catch(error => {
                            button.disabled = false;
                            button.textContent = 'Setup Failed - Try Again';
                            messageDiv.textContent = 'Error during setup: ' + error;
                            messageDiv.className = 'message error';
                        });
                })
                .catch(error => {
                    button.disabled = false;
                    button.textContent = 'Setup Failed - Try Again';
                    messageDiv.textContent = 'Error during setup: ' + error;
                    messageDiv.className = 'message error';
                });
        }
    </script>
</body>
</html>
