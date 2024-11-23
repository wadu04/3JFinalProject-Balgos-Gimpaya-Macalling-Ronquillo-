<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/register-style.css">
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>Login</h2>
            <form action="process-login.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>
            <div id="message"></div>
            <p style="text-align: center; margin-top: 20px;">
                Don't have an account? <a href="register-form.php" style="color: #4CAF50;">Register here</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process-login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.textContent = data.message;
                messageDiv.className = data.status;
                
                if (data.status === 'success') {
                    // Redirect based on user role
                    if (data.user.role === 'admin') {
                        window.location.href = 'dashboard.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('message').textContent = 'An error occurred. Please try again.';
            });
        });
    </script>
</body>
</html>
