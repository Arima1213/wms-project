<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #333; }
    h1 { font-size: 16pt; color: #1f2937; margin-bottom: 4px; }
    .subtitle { color: #6b7280; font-size: 8pt; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background-color: #1f2937; color: #fff; padding: 6px 8px; text-align: left; font-size: 8pt; font-weight: bold; }
    td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; font-size: 8pt; }
    tr:nth-child(even) td { background-color: #f9fafb; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 7pt; color: #9ca3af; }
  </style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <p class="subtitle">Dibuat: {{ $generated_at }}</p>

  <table>
    <thead>
      <tr>
        @foreach (array_keys($data[0] ?? []) as $header)
          <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($data as $row)
        <tr>
          @foreach ($row as $cell)
            <td>{{ $cell }}</td>
          @endforeach
        </tr>
      @empty
        <tr><td colspan="100" class="text-center">Tidak ada data</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">Laporan ini digenerate oleh WMS {{ date('Y') }}</div>
</body>
</html>
