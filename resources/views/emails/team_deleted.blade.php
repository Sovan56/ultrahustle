<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Team Deleted</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; background: #ffffff; padding: 20px; border-radius: 8px;">
        <h2 style="color: #e67e22;">{{ $team->team_name }} has been deleted</h2>

        <p>Hello,</p>
        <p>The team <strong>{{ $team->team_name }}</strong> you were part of has been deleted by the owner.</p>

        <p style="color: #555;">You no longer have access to this team or its resources.</p>
    </div>
</body>
</html>
