<?php

declare(strict_types=1);

namespace ExamplePlugin;

use AdapterKit\Core\Contracts\HooksInterface;
use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Contracts\OptionStorageInterface;
use AdapterKit\Core\PluginContext;
use Psr\Log\LoggerInterface;

/**
 * Wires hooks and builds services from the injected adapters.
 * Does not call get_option(), wp_remote_post(), or any WordPress function
 * directly — all WordPress interaction goes through the injected contracts.
 */
final class Plugin
{
    private PluginContext          $context;
    private HooksInterface         $hooks;
    private LicenseService         $license;

    public function __construct(
        PluginContext          $context,
        HooksInterface         $hooks,
        OptionStorageInterface $options,
        HttpClientInterface    $http,
        LoggerInterface        $logger
    ) {
        $this->context = $context;
        $this->hooks   = $hooks;
        $this->license = new LicenseService(
            $options,
            $http,
            $logger,
            $context->getOptionPrefix() . 'license'
        );
    }

    public function register(): void
    {
        $this->hooks->addAction('admin_menu', [$this, 'addAdminMenu']);
        $this->hooks->registerRestRoute('example-plugin/v1', '/license/activate', [
            'methods'  => 'POST',
            'callback' => [$this->license, 'activate'],
        ]);
    }

    public function addAdminMenu(): void
    {
        // add_menu_page() lives here — acceptable because this method IS the
        // adapter boundary for the admin menu hook.
    }
}
