<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MemberProfileUpdateNotification;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class MemberProfileController extends Controller
{
    private function maybeDecrypt($value)
    {
        if (!is_string($value) || $value === '') return $value;
        $decoded = base64_decode($value, true);
        if ($decoded === false) return $value;
        $isPayload = function ($str) {
            $arr = json_decode($str, true);
            return is_array($arr) && isset($arr['iv'], $arr['value'], $arr['mac']);
        };
        $out = $value;
        for ($i = 0; $i < 2; $i++) {
            $decoded = base64_decode($out, true);
            if ($decoded === false || !$isPayload($decoded)) break;
            try { $out = \Illuminate\Support\Facades\Crypt::decryptString($out); }
            catch (\Throwable $e) { return $value; }
        }
        return $out;
    }

    private function presentMember($member)
    {
        return [
            'id' => $member->id,
            'email' => $this->maybeDecrypt($member->email),
            'company_name' => $member->company_name,
            'representative_name' => $this->maybeDecrypt($member->representative_name),
            'position' => $this->maybeDecrypt($member->position),
            'department' => $this->maybeDecrypt($member->department),
            'phone' => $this->maybeDecrypt($member->phone),
            'postal_code' => $this->maybeDecrypt($member->postal_code),
            'address' => $this->maybeDecrypt($member->address),
            'capital' => $member->capital,
            'industry' => $member->industry,
            'region' => $member->region,
            'concerns' => $this->maybeDecrypt($member->concerns),
            'notes' => $this->maybeDecrypt($member->notes),
            'membership_type' => $member->membership_type,
            'status' => $member->status,
            'joined_date' => $member->joined_date,
            'started_at' => $member->started_at,
            'membership_expires_at' => $member->membership_expires_at,
            'is_active' => (bool)$member->is_active,
            'created_at' => $member->created_at,
            'updated_at' => $member->updated_at,
        ];
    }
    /**
     * 会員プロフィール情報を取得
     */
    public function show(Request $request)
    {
        try {
            $member = Auth::guard('sanctum')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $this->presentMember($member)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'プロフィール情報の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 会員プロフィール情報を更新
     */
    public function update(Request $request)
    {
        try {
            $member = Auth::guard('sanctum')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です'
                ], 401);
            }

            // バリデーション
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'representative_name' => 'required|string|max:255',
                'position' => 'nullable|string|max:100',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'postal_code' => 'nullable|string|max:10',
                'address' => 'nullable|string|max:500',
                'capital' => 'nullable|integer|min:0',
                'industry' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:50',
                'concerns' => 'nullable|string',
                'notes' => 'nullable|string',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('members', 'email_index')->ignore($member->id),
                ],
            ], [
                'company_name.required' => '会社名は必須です',
                'representative_name.required' => '代表者名は必須です',
                'email.required' => 'メールアドレスは必須です',
                'email.email' => 'メールアドレスの形式が正しくありません',
                'email.unique' => 'このメールアドレスは既に使用されています',
            ]);

            // 変更内容を追跡
            $changes = $this->detectChanges($member, $validated);

            // プロフィール更新
            // email_index を同時更新
            if (!empty($validated['email'])) {
                $validated['email_index'] = mb_strtolower(trim($validated['email']));
            }
            $member->update($validated);

            // 更新後のデータを返却
            $member->refresh();

            // 管理者にメール通知を送信
            if (!empty($changes)) {
                $this->sendAdminNotification($member, $changes);
            }

            return response()->json([
                'success' => true,
                'message' => 'プロフィールを更新しました',
                'data' => $this->presentMember($member)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーがあります',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'プロフィールの更新に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * パスワード変更
     */
    public function updatePassword(Request $request)
    {
        try {
            $member = Auth::guard('sanctum')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です'
                ], 401);
            }

            // バリデーション
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'current_password.required' => '現在のパスワードは必須です',
                'new_password.required' => '新しいパスワードは必須です',
                'new_password.min' => 'パスワードは8文字以上で入力してください',
                'new_password.confirmed' => 'パスワード確認が一致しません',
            ]);

            // 現在のパスワードが正しいかチェック
            if (!Hash::check($validated['current_password'], $member->password)) {
                return response()->json([
                    'success' => false,
                    'message' => '現在のパスワードが正しくありません'
                ], 400);
            }

            // パスワード更新
            $member->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'パスワードを変更しました'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーがあります',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'パスワードの変更に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 変更内容を検出
     */
    private function detectChanges(Member $member, array $validated): array
    {
        $changes = [];
        $fieldLabels = [
            'company_name' => '会社名',
            'representative_name' => '代表者名',
            'position' => '役職',
            'department' => '部署',
            'phone' => '電話番号',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'capital' => '資本金',
            'industry' => '業種',
            'region' => '地域',
            'concerns' => '関心事',
            'notes' => '備考',
            'email' => 'メールアドレス',
        ];

        foreach ($validated as $field => $newValue) {
            if ($field === 'email_index') continue; // Skip internal field
            
            $oldValue = $member->$field;
            
            // 暗号化されている可能性のあるフィールドを復号化
            $oldValue = $this->maybeDecrypt($oldValue);
            
            // 値が変更された場合のみ記録
            if ((string)$oldValue !== (string)$newValue) {
                $changes[$field] = [
                    'label' => $fieldLabels[$field] ?? $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * 管理者に通知メールを送信
     */
    private function sendAdminNotification(Member $member, array $changes): void
    {
        try {
            $adminEmail = config('mail.admin_notification_email');
            
            if (empty($adminEmail)) {
                \Log::warning('Admin notification email not configured. Skipping notification.');
                return;
            }

            Mail::to($adminEmail)->send(new MemberProfileUpdateNotification($member, $changes));
        } catch (\Exception $e) {
            // メール送信失敗時はログに記録するが、エラーは投げない
            \Log::error('Failed to send admin notification email: ' . $e->getMessage(), [
                'member_id' => $member->id,
                'changes' => $changes,
            ]);
        }
    }

    /**
     * アカウント削除（論理削除）
     */
    public function deleteAccount(Request $request)
    {
        try {
            $member = Auth::guard('sanctum')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です'
                ], 401);
            }

            // バリデーション
            $validated = $request->validate([
                'password' => 'required|string',
                'confirmation' => 'required|string|in:DELETE',
            ], [
                'password.required' => 'パスワードは必須です',
                'confirmation.required' => '削除確認文字列は必須です',
                'confirmation.in' => '削除確認には "DELETE" と入力してください',
            ]);

            // パスワードが正しいかチェック
            if (!Hash::check($validated['password'], $member->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'パスワードが正しくありません'
                ], 400);
            }

            // アカウントを無効化（論理削除）
            $member->update([
                'status' => 'cancelled',
                'is_active' => false,
            ]);

            // 全トークンを削除（ログアウト）
            $member->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'アカウントを削除しました'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーがあります',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'アカウントの削除に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
