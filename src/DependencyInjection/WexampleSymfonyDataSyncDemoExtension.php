<?php

namespace Wexample\SymfonyDataSyncDemo\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyDataSyncDemo\Class\DemoContact;
use Wexample\SymfonyDataSyncDemo\Service\DemoLocalStore;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyDataSyncDemoExtension extends AbstractWexampleSymfonyExtension
{
    public const string SERVICE_CHAT = 'wexample_symfony_data_sync_demo.adapter.chat';

    public const string SERVICE_CRM = 'wexample_symfony_data_sync_demo.adapter.crm';

    /**
     * The demo's definitions, declared for symfony-data-sync as an app would.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getDefinitions(): array
    {
        return [
            'demo_chat' => [
                'local' => DemoContact::class,
                'adapter' => self::SERVICE_CHAT,
                'local_store' => DemoLocalStore::class,
                'link_property' => 'chatId',
                'match' => [
                    ['local' => 'email', 'remote' => 'email', 'normalize' => ['email']],
                    ['local' => 'username', 'remote' => 'username', 'normalize' => ['lower']],
                ],
                'fields' => [
                    'username' => 'username',
                    'email' => ['remote' => 'email', 'direction' => 'both'],
                    'name' => ['remote' => 'display_name', 'direction' => 'remote_to_local'],
                ],
                'local_exclude' => [['field' => 'active', 'operator' => 'equals', 'value' => false]],
                'remote_exclude' => [['field' => 'roles', 'operator' => 'contains', 'value' => 'bot']],
                'orphans' => ['local' => 'create_remote', 'remote' => 'report'],
                'excluded_local' => 'disable_remote',
                'conflict' => 'report',
            ],
            'demo_crm' => [
                'local' => DemoContact::class,
                'adapter' => self::SERVICE_CRM,
                'local_store' => DemoLocalStore::class,
                'link_property' => 'crmId',
                'match' => [
                    ['type' => 'fuzzy', 'fields' => [['local' => 'name', 'remote' => 'contact', 'weight' => 1]]],
                ],
                'fields' => ['name' => 'contact'],
                'thresholds' => ['auto_link' => 0.95, 'candidate' => 0.6],
            ],
        ];
    }

    public function prepend(ContainerBuilder $container): void
    {
        parent::prepend($container);

        $container->prependExtensionConfig('wexample_symfony_data_sync', ['definitions' => self::getDefinitions()]);
    }

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
