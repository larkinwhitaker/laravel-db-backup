<?php namespace Witty\LaravelDbBackup\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use AWS, Config, File;

use Witty\LaravelDbBackup\Commands\Helpers\BackupFile;
use Witty\LaravelDbBackup\Commands\Helpers\BackupHandler;

class BackupCommand extends BaseCommand {

	/**
	 * @var string
	 */
	protected $name = 'db:backup';
	protected $description = 'Backup the default database to `storage/dumps`';
	protected $filePath;
	protected $fileName;

	/**
	 * @return void
	 */
	public function fire()
	{
		$database = $this->getDatabase($this->input->getOption('database'));

		$this->checkDumpFolder();

		//----------------
		$dbfile = new BackupFile( $this->argument('filename'), $database, $this->getDumpsPath() );
		$this->filePath = $dbfile->path();
		$this->fileName = $dbfile->name();

		$status = $database->dump($this->filePath);
		$handler = new BackupHandler( $this->colors );

		// Error
		//----------------
		if ($status !== true)
		{
			return $this->line( $handler->errorResponse( $status ) );
		}

		// Compression
		//----------------
		if ($this->isCompressionEnabled())
		{
			$this->compress();
			$this->fileName .= ".gz";
			$this->filePath .= ".gz";
		}

		$this->line( $handler->dumpResponse( $this->argument('filename'), $this->filePath, $this->fileName ) );

		// S3 Upload
		//----------------
		if ($this->option('upload-s3'))
		{
			$this->uploadS3();
			$this->line( $handler->s3DumpResponse() );

			if ($this->option('keep-only-s3'))
			{
				File::delete($this->filePath);
				$this->line( $handler->localDumpRemovedResponse() );
			}
		}
	}

	/**
	 * Perform Gzip compression on file
	 * 
	 * @return boolean
	 */ 
	protected function compress()
	{
		$command = sprintf('gzip -9 %s', $this->filePath);

		return $this->console->run($command);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['filename', InputArgument::OPTIONAL, 'Filename or -path for the dump.'],
		];
	}

	/**
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to backup'],
			['upload-s3', 'u', InputOption::VALUE_REQUIRED, 'Upload the dump to your S3 bucket'],
			['keep-only-s3', true, InputOption::VALUE_NONE, 'Delete the local dump after upload to S3 bucket']
		];
	}

	/**
	 * @return void
	 */
	protected function checkDumpFolder()
	{
		$dumpsPath = $this->getDumpsPath();

		if ( ! is_dir($dumpsPath))
		{
			mkdir($dumpsPath);
		}
	}

	/**
	 * @return void
	 */
	protected function uploadS3()
	{
		$bucket = $this->option('upload-s3');
		$s3 = AWS::get('s3');
		$s3->putObject([
			'Bucket'     => $bucket,
			'Key'        => $this->getS3DumpsPath() . '/' . $this->fileName,
			'SourceFile' => $this->filePath,
		]);
	}

	/**
	 * @return string
	 */
	protected function getS3DumpsPath()
	{
		$default = 'dumps';

		return Config::get('db-backup.s3.path', $default);
	}
}
