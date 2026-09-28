<?php

namespace Wexample\SymfonyDataSyncDemo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * JSON files under var/data_sync_demo, seeded from the package's fixtures the
 * first time they are read, so what the screens apply persists between
 * requests until reset().
 */
class DemoFileStore
{
    public const array SETS = ['contacts', 'chat', 'crm'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/data_sync_demo')]
        private readonly string $directory,
    ) {
    }

    /**
     * @return array<string, array<string, mixed>> records by id
     */
    public function read(string $set): array
    {
        if (! is_file($this->path($set))) {
            $this->write($set, $this->fixture($set));
        }

        return json_decode((string) file_get_contents($this->path($set)), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, array<string, mixed>> $records
     */
    public function write(string $set, array $records): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }

        file_put_contents($this->path($set), json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function reset(): void
    {
        foreach (self::SETS as $set) {
            $this->write($set, $this->fixture($set));
        }
    }

    private function fixture(string $set): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../Resources/fixtures/'.$set.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function path(string $set): string
    {
        return $this->directory.'/'.$set.'.json';
    }
}
