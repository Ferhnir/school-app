<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #ffffff;
            padding: 40px;
        }

        .header {
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }

        .school-name {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4f46e5;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a2e;
            margin-top: 4px;
        }

        .meta-table { width: 100%; margin-top: 12px; }
        .meta-table td { padding: 0; vertical-align: top; width: 50%; }
        .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; margin-bottom: 2px; }
        .meta-value { font-size: 13px; font-weight: bold; color: #111827; }

        .summary {
            background: #eef2ff;
            border-left: 4px solid #4f46e5;
            padding: 10px 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }

        .summary-text { font-size: 12px; color: #4338ca; font-weight: bold; }

        table.bookings { width: 100%; border-collapse: collapse; }
        table.bookings thead tr { background: #4f46e5; }
        table.bookings thead th {
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 14px;
            text-align: left;
        }
        table.bookings tbody tr { border-bottom: 1px solid #e5e7eb; }
        table.bookings tbody tr:nth-child(even) { background: #f9fafb; }
        table.bookings tbody td { padding: 10px 14px; font-size: 12px; color: #374151; }

        .time-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            font-weight: bold;
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 20px;
        }

        .footer { margin-top: 36px; border-top: 1px solid #e5e7eb; padding-top: 12px; }
        .footer-table { width: 100%; }
        .footer-left { font-size: 9px; color: #9ca3af; }
        .footer-right { text-align: right; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">{{ config('app.name') }}</div>
        <div class="doc-title">My Bookings — {{ $date->format('l, d F Y') }}</div>

        <table class="meta-table" style="margin-top: 12px;">
            <tr>
                <td>
                    <div class="meta-label">Parent</div>
                    <div class="meta-value">{{ $parent->name }}</div>
                </td>
                <td>
                    <div class="meta-label">Date</div>
                    <div class="meta-value">{{ $date->format('l, d F Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <span class="summary-text">
            {{ $bookings->count() }} {{ Str::plural('appointment', $bookings->count()) }} scheduled today
        </span>
    </div>

    <table class="bookings">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Teacher</th>
                <th style="width: 120px;">Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $i => $booking)
                <tr>
                    <td style="color: #9ca3af; font-size: 11px;">{{ $i + 1 }}</td>
                    <td>{{ $booking['teacher'] }}</td>
                    <td><span class="time-badge">{{ $booking['time'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #9ca3af; padding: 24px;">
                        No bookings today.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">Generated on {{ now()->format('d M Y, H:i') }}</td>
                <td class="footer-right">{{ config('app.name') }} &mdash; Confidential</td>
            </tr>
        </table>
    </div>

</body>
</html>
