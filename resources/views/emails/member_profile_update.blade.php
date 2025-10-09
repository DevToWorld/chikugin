<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .member-info {
            background-color: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #0066cc;
            margin: 20px 0;
        }
        .changes-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .changes-table th, .changes-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .changes-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .old-value {
            color: #dc3545;
            text-decoration: line-through;
        }
        .new-value {
            color: #28a745;
            font-weight: 500;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin: 0; color: #0066cc;">会員情報更新通知</h2>
    </div>

    <div class="content">
        <p>会員が自身のプロフィール情報を更新しました。</p>

        <div class="member-info">
            <h3 style="margin-top: 0;">会員情報</h3>
            <p><strong>会員ID:</strong> {{ $member->id }}</p>
            <p><strong>会社名:</strong> {{ $member->company_name }}</p>
            <p><strong>代表者名:</strong> {{ $member->representative_name }}</p>
            <p><strong>メールアドレス:</strong> {{ $member->email }}</p>
            <p><strong>更新日時:</strong> {{ $member->updated_at->format('Y年m月d日 H:i') }}</p>
        </div>

        @if(!empty($changes))
            <h3>更新された項目</h3>
            <table class="changes-table">
                <thead>
                    <tr>
                        <th>項目名</th>
                        <th>変更前</th>
                        <th>変更後</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($changes as $field => $change)
                        <tr>
                            <td><strong>{{ $change['label'] }}</strong></td>
                            <td class="old-value">{{ $change['old'] ?: '(未設定)' }}</td>
                            <td class="new-value">{{ $change['new'] ?: '(未設定)' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p style="margin-top: 20px;">
            管理画面から詳細を確認することができます。
        </p>
    </div>

    <div class="footer">
        <p>このメールは自動送信されています。返信しないでください。</p>
        <p>© {{ date('Y') }} 地域経済研究所. All rights reserved.</p>
    </div>
</body>
</html>

