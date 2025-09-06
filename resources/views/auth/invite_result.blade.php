<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Team Invitation Result</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 40px; }
        .box { background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: auto; text-align: center; }
        h2 { color: #333; }
        p  { color: #555; }
        a.button { background: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Team Invitation</h2>
        <p>{{ $message ?? 'No message provided.' }}</p>
        <a href="{{ route('user.admin.myteam') }}" class="button">Go to My Teams</a>
    </div>
</body>
</html>
