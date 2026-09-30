<?php

namespace Wexample\SymfonyDataSyncDemo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyDataSync\Class\SyncReport;
use Wexample\SymfonyDataSync\DependencyInjection\Configuration;
use Wexample\SymfonyDataSync\Enum\SyncSide;
use Wexample\SymfonyDataSync\Service\DoctrineLinkStore;
use Wexample\SymfonyDataSync\Service\Matcher;
use Wexample\SymfonyDataSync\Service\SyncDefinitionRegistry;
use Wexample\SymfonyDataSync\Service\SyncExecutor;
use Wexample\SymfonyDataSync\Service\SyncPlanner;
use Wexample\SymfonyDataSync\Service\SyncResolver;
use Wexample\SymfonyDataSync\Service\SyncRunner;
use Wexample\SymfonyDataSyncDemo\DependencyInjection\WexampleSymfonyDataSyncDemoExtension;
use Wexample\SymfonyDataSyncDemo\Service\DemoFileStore;
use Wexample\SymfonyDataSyncDemo\Service\DemoLocalStore;
use Wexample\SymfonyDataSyncDemo\Service\DemoRemoteAdapter;

/**
 * The showcase must show every case: this pins what the fixtures plan.
 */
class DemoScenarioTest extends TestCase
{
    private string $directory;

    private DemoFileStore $files;

    private SyncRunner $runner;

    private SyncResolver $resolver;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/data_sync_demo_'.uniqid();
        $this->files = new DemoFileStore($this->directory);
        $locals = new DemoLocalStore($this->files);

        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'definitions' => WexampleSymfonyDataSyncDemoExtension::getDefinitions(),
        ]]);
        $registry = new SyncDefinitionRegistry(
            $this->createStub(DoctrineLinkStore::class),
            $config['definitions'],
            new ServiceLocator([
                WexampleSymfonyDataSyncDemoExtension::SERVICE_CHAT => fn () => new DemoRemoteAdapter($this->files, 'chat'),
                WexampleSymfonyDataSyncDemoExtension::SERVICE_CRM => fn () => new DemoRemoteAdapter($this->files, 'crm'),
                DemoLocalStore::class => fn () => $locals,
            ]),
        );
        $planner = new SyncPlanner(new Matcher());
        $this->runner = new SyncRunner($registry, $planner, new SyncExecutor());
        $this->resolver = new SyncResolver($registry, $planner, new SyncExecutor());
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory.'/*.json'));
        rmdir($this->directory);
    }

    public function testTheChatDefinitionShowsEveryDecision(): void
    {
        $this->assertSame([
            'ada' => 'local_update',
            'dora' => 'remote_disable',
            'eve' => 'local_unlink',
            'finn' => 'conflict',
            'bob' => 'local_link',
            'cid' => 'remote_create',
            'chat-ghost' => 'unmatched',
        ], $this->operations($this->runner->run('demo_chat')));
    }

    public function testTheCrmDefinitionProposesACandidate(): void
    {
        $operations = $this->operations($this->runner->run('demo_crm'));

        $this->assertSame('local_link', $operations['ada']);
        $this->assertSame('candidate', $operations['bob']);
        $this->assertSame('unmatched', $operations['crm-3']);
    }

    public function testApplyingPersistsAndLeavesOnlyWhatNeedsAHuman(): void
    {
        $this->runner->run('demo_chat', dryRun: false);

        $this->assertSame('Ada King', $this->files->read('contacts')['ada']['name']);
        $this->assertFalse($this->files->read('chat')['chat-dora']['active']);
        $this->assertSame('chat-bob', $this->files->read('contacts')['bob']['chatId']);

        // Dora is disabled once and left alone; Eve, unlinked, now gets an account;
        // Bob, just linked, and Finn differ on fields both sides own: a human decides.
        $this->assertSame([
            'bob' => 'conflict',
            'finn' => 'conflict',
            'eve' => 'remote_create',
            'chat-ghost' => 'unmatched',
        ], $this->operations($this->runner->run('demo_chat')));
    }

    public function testResolvingAConflictSettlesThePair(): void
    {
        $this->runner->run('demo_chat', dryRun: false);
        $this->resolver->resolve('demo_chat', 'bob', SyncSide::Local);
        $this->resolver->resolve('demo_chat', 'finn', SyncSide::Remote);

        $this->assertSame('bob@example.test', $this->files->read('chat')['chat-bob']['email']);
        $this->assertSame('finn@old.example.test', $this->files->read('contacts')['finn']['email']);
        $this->assertSame([
            'eve' => 'remote_create',
            'chat-ghost' => 'unmatched',
        ], $this->operations($this->runner->run('demo_chat')));
    }

    public function testResetRestoresTheFixtures(): void
    {
        $this->runner->run('demo_chat', dryRun: false);
        $this->files->reset();

        $this->assertSame('Ada Lovelace', $this->files->read('contacts')['ada']['name']);
    }

    /**
     * @return array<string, string> operation by local id, or remote id when there is no local
     */
    private function operations(SyncReport $report): array
    {
        $operations = [];
        foreach ($report->outcomes as $outcome) {
            $relation = $outcome->relation;
            $operations[$relation->local?->id ?? $relation->link?->localId ?? $relation->remote->id] = $relation->operation->value;
        }

        return $operations;
    }
}
