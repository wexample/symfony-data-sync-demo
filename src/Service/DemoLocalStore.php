<?php

namespace Wexample\SymfonyDataSyncDemo\Service;

use Wexample\SymfonyDataSync\Class\LocalItem;
use Wexample\SymfonyDataSync\Class\SyncDefinition;
use Wexample\SymfonyDataSync\Interface\LocalStoreInterface;
use Wexample\SymfonyDataSyncDemo\Class\DemoContact;

/**
 * The demo contacts, read from and written to their JSON file.
 */
class DemoLocalStore implements LocalStoreInterface
{
    private const string SET = 'contacts';

    public function __construct(
        private readonly DemoFileStore $files,
    ) {
    }

    public function list(SyncDefinition $definition): iterable
    {
        foreach ($this->files->read(self::SET) as $id => $fields) {
            yield $this->toItem((string) $id, $fields);
        }
    }

    public function find(SyncDefinition $definition, string $id): ?LocalItem
    {
        $records = $this->files->read(self::SET);

        return isset($records[$id]) ? $this->toItem($id, $records[$id]) : null;
    }

    public function create(SyncDefinition $definition, array $fields): LocalItem
    {
        $records = $this->files->read(self::SET);
        $id = 'contact-'.(count($records) + 1);
        $records[$id] = $fields + ['active' => true, 'chatId' => null, 'crmId' => null];
        $this->files->write(self::SET, $records);

        return $this->toItem($id, $records[$id]);
    }

    public function update(SyncDefinition $definition, LocalItem $item, array $fields): LocalItem
    {
        $records = $this->files->read(self::SET);
        $records[$item->id] = $fields + $records[$item->id];
        $this->files->write(self::SET, $records);

        return $this->toItem($item->id, $records[$item->id]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function toItem(string $id, array $fields): LocalItem
    {
        $contact = new DemoContact();
        foreach ($fields as $field => $value) {
            $contact->{$field} = $value;
        }

        return new LocalItem($id, $fields, $contact);
    }
}
