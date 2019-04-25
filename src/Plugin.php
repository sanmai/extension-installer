<?php declare(strict_types = 1);

namespace PHPStan\ExtensionInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class Plugin implements PluginInterface, EventSubscriberInterface
{

	/** @var string */
	private static $generatedFileTemplate = <<<'PHP'
<?php declare(strict_types = 1);

namespace PHPStan\ExtensionInstaller;

/**
 * This class is generated by phpstan/extension-installer.
 */
final class Extensions
{

	public const CONFIG = %s;

	private function __construct()
	{
	}

}

PHP;

	public function activate(Composer $composer, IOInterface $io)
	{
		// noop
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ScriptEvents::POST_INSTALL_CMD => 'process',
			ScriptEvents::POST_UPDATE_CMD => 'process',
		];
	}

	public function process(Event $event): void
	{
		$io = $event->getIO();
		$composer = $event->getComposer();
		$installationManager = $composer->getInstallationManager();

		$data = [];
		foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
			if ($package->getType() !== 'phpstan-extension') {
				continue;
			}
			$data[$package->getName()] = [
				'install_path' => $installationManager->getInstallPath($package),
				'extra' => $package->getExtra()['phpstan'] ?? null,
			];
		}

		file_put_contents(__DIR__ . '/Extensions.php', sprintf(self::$generatedFileTemplate, var_export($data, true)));
		$io->write('<info>phpstan/extension-installer:</info> Extension config generated');
	}

}
