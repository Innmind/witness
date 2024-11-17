<?php
declare(strict_types = 1);

namespace Innmind\Witness;

interface Adapter
{
    public function mailboxes(): Mailboxes;
    public function scheduled(): Scheduled;
}
