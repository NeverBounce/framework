<?php namespace Illuminate\Foundation\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Console\Command;

class KeyGenerateCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'key:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Set the application key";

	/**
	 * Create a new key generator command.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 */
	public function fire()
	{
		$key = $this->generateRandomKey();

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (! $this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['app.key'] = $key;
        $this->info('Application key set successfully.');
	}

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @throws \Exception
     */
	protected function generateRandomKey()
	{
        return 'base64:'.base64_encode(
                Encrypter::generateKey($this->laravel['config']['app.cipher'])
            );
	}

    /**
     * @return bool
     */
	protected function confirmToProceed()
    {
        return $this->confirm('Are you sure you want to continue?');
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['app.key'];
        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }
        $this->writeNewEnvironmentFileWith($key);
        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        file_put_contents($this->laravel['path'] . '/../.env', preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            file_get_contents($this->laravel['path'] . '/../.env')
        ));
    }
    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['app.key'], '/');
        return "/^APP_KEY{$escaped}/m";
    }

}
