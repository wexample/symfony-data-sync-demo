<?php

namespace Wexample\SymfonyDataSyncDemo\Service;

use Wexample\SymfonyDataSync\Class\RemoteItem;
use Wexample\SymfonyDataSync\Interface\DisablableRemoteAdapterInterface;
use Wexample\SymfonyDataSync\Interface\SearchableRemoteAdapterInterface;

/**
 * A pretend remote service kept in a JSON file: declared once per demo remote
 * (chat, crm), each with its own file.
 */
class DemoRemoteAdapter implements DisablableRemoteAdapterInterface, SearchableRemoteAdapterInterface
{
    public function __construct(
        private readonly DemoFileStore $files,
        private readonly string $set,
    ) {
    }

    public function list(): iterable
    {
        foreach ($this->files->read($this->set) as $id => $fields) {
            yield new RemoteItem((string) $id, $fields);
        }
    }

    public function get(string $id): ?RemoteItem
    {
        $records = $this->files->read($this->set);

        return isset($records[$id]) ? new RemoteItem($id, $records[$id]) : null;
    }

    public function findBy(string $field, mixed $value): iterable
    {
        foreach ($this->files->read($this->set) as $id => $fields) {
            $candidate = $fields[$field] ?? null;

            if (is_string($candidate) && is_string($value) ? 0 === strcasecmp($candidate, $value) : $candidate === $value) {
                yield new RemoteItem((string) $id, $fields);
            }
        }
    }

    public function create(array $fields): RemoteItem
    {
        $records = $this->files->read($this->set);
        $id = $this->set.'-'.(count($records) + 1);
        $records[$id] = $fields;
        $this->files->write($this->set, $records);

        return new RemoteItem($id, $fields);
    }

    public function update(string $id, array $fields): RemoteItem
    {
        $records = $this->files->read($this->set);
        $records[$id] = $fields + $records[$id];
        $this->files->write($this->set, $records);

        return new RemoteItem($id, $records[$id]);
    }

    public function remove(string $id): void
    {
        $records = $this->files->read($this->set);
        unset($records[$id]);
        $this->files->write($this->set, $records);
    }

    public function isDisabled(RemoteItem $item): bool
    {
        return false === $item->get('active');
    }

    public function disable(string $id): void
    {
        $this->update($id, ['active' => false]);
    }
}
