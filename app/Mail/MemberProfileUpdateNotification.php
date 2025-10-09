<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberProfileUpdateNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Member $member;
    public array $changes;

    /**
     * Create a new message instance.
     */
    public function __construct(Member $member, array $changes)
    {
        $this->member = $member;
        $this->changes = $changes;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('【会員情報更新通知】会員がプロフィールを更新しました')
                    ->view('emails.member_profile_update')
                    ->with([
                        'member' => $this->member,
                        'changes' => $this->changes,
                    ]);
    }
}

