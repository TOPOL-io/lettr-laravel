<?php

declare(strict_types=1);

namespace Lettr\Laravel\Support;

use Illuminate\Mail\Mailer;
use Lettr\Laravel\Mail\LettrPendingMail;

/**
 * @mixin Mailer
 */
class MailerMixin
{
    /**
     * Start a Lettr mail chain.
     */
    public function lettr(): LettrPendingMail
    {
        /** @phpstan-ignore-next-line */
        return new LettrPendingMail($this);
    }
}
