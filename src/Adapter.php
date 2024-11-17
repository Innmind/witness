<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Adapter\{
    Mailboxes,
    Scheduled,
};

interface Adapter
{
    public function mailboxes(): Mailboxes;
    public function scheduled(): Scheduled;
}
