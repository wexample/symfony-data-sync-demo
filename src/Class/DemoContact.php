<?php

namespace Wexample\SymfonyDataSyncDemo\Class;

/**
 * The demo's local entity: a contact, kept in a JSON file rather than a
 * table so the showcase needs no migration. chatId and crmId hold the remote
 * ids, as an app keeping them in columns would (link_property).
 */
class DemoContact
{
    public ?string $username = null;

    public ?string $email = null;

    public ?string $name = null;

    public bool $active = true;

    public ?string $chatId = null;

    public ?string $crmId = null;
}
