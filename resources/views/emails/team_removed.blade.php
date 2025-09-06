<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Removed from Team</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; background: #ffffff; padding: 20px; border-radius: 8px;">
        <h2 style="color: #e74c3c;">You have been removed from {{ $team->team_name }}</h2>

        <p>Hello,</p>
        <p>You have been removed from the position of <strong>{{ $member->positions ?? 'Member' }}</strong> in <strong>{{ $team->team_name }}</strong>.</p>

        <p style="color: #555;">If you believe this was a mistake, please contact the team owner.</p>
    </div>
</body>
</html>
