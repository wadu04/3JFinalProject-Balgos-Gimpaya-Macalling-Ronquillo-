<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="css/register-style.css">
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>User Registration</h2>
            <form action="process-register.php" method="POST" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="submit-btn">Register</button>
            </form>
            <div id="message"></div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process-register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.textContent = data.message;
                messageDiv.className = data.status;
                
                if (data.status === 'success') {
                    // Clear the form
                    this.reset();
                    // Redirect to index page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
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
