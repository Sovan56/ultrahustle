<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Team Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; background: #ffffff; padding: 20px; border-radius: 8px;">
        <h2 style="color: #333;">You’ve been invited to join {{ $team->team_name }}</h2>

        <p>Hello,</p>
        <p>You have been selected for the position of <strong>{{ $invite->positions ?? 'Member' }}</strong> in <strong>{{ $team->team_name }}</strong>.</p>

        <p style="margin-top: 20px;">
            <a href="{{ $acceptUrl }}" style="background: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px;">
                Accept Invitation
            </a>
        </p>

        <p style="color: #555; margin-top: 20px;">If you don’t have an account, you’ll be asked to create one before joining the team.</p>
        <p style="color: #999; font-size: 12px;">This invite will expire after a certain period or if revoked by the team owner.</p>
    </div>
</body>
</html>
