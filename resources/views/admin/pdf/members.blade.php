<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Members</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    th { background: #f2f2f2; }
    h2 { margin: 0 0 10px; }
  </style>
</head>
<body>
  <h2>All Members</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Unique ID</th><th>Name</th><th>Email</th><th>Phone Number</th><th>Location</th><th>Created At</th>
      </tr>
    </thead>
    <tbody>
      @foreach($users as $u)
      <tr>
        <td>{{ $u->id }}</td>
        <td>{{ $u->unique_id }}</td>
        <td>{{ $u->name ?? ($u->first_name.' '.$u->last_name) }}</td>
        <td>{{ $u->email }}</td>
        <td>{{ $u->phone_number }}</td>
        <td>{{ $u->uad_location }}</td>
        <td>{{ optional($u->created_at)->toDateTimeString() }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
