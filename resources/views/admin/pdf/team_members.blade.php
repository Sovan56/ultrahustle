<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Team Export</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    th { background: #f2f2f2; }
    h2,h3 { margin: 0 0 10px; }
  </style>
</head>
<body>
  <h2>Team: {{ $team->team_name }}</h2>

  @if($scope === 'members')
    <h3>Members</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Positions</th><th>Status</th><th>Created At</th>
        </tr>
      </thead>
      <tbody>
        @foreach($members as $m)
          <tr>
            <td>{{ $m->id }}</td>
            <td>{{ $m->member_name }}</td>
            <td>{{ $m->member_email }}</td>
            <td>{{ $m->role }}</td>
            <td>{{ $m->positions }}</td>
            <td>{{ $m->status }}</td>
            <td>{{ optional($m->created_at)->toDateTimeString() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <h3>Portfolio</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Title</th><th>Description</th><th>Image Count</th><th>Created At</th>
        </tr>
      </thead>
      <tbody>
        @foreach($projects as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->title ?? $p->project_title }}</td>
            <td>{{ trim(preg_replace('/\s+/', ' ', strip_tags((string)($p->description ?? $p->project_desc)))) }}</td>
            <td>{{ $p->images ? $p->images->count() : 0 }}</td>
            <td>{{ optional($p->created_at)->toDateTimeString() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</body>
</html>
