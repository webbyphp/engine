<?php

use Base\Helpers\TraverseClassFile;
use Base\Controllers\ConsoleController;
use Base\Seeder\Seeder as Seeder;

class Seed extends ConsoleController
{

	/**
	 * Path Constant
	 *
	 * @var string
	 */
	private	const PATH = ROOTPATH . 'database' . DS . 'seeders';

	/**
	 * Descending Constant
	 *
	 * @var string
	 */
	private const DESC = 'DESC';

	/**
	 * Ascending Constant
	 *
	 * @var string
	 */
	private const ASC = 'ASC';

	/**
	 * Seeder files
	 *
	 * @var array
	 */
	private $seederFiles = [];

	/**
	 * Default Database
	 *
	 * @var string
	 */
	private $useDb = 'default';

	/**
	 * Seeder instance
	 *
	 * @var object
	 */
	private $seeder;

	public function __construct()
	{
		parent::__construct();

		$this->onlydev();

		$this->connectDB();
		$this->use->dbforge();

		$this->seederRequirements();

		if (!is_cli()) {
			exit('Direct access is not allowed. This is a command line tool, use the terminal');
		}
	}

	/**
	 * Connect to a database
	 *
	 * @return void
	 */
	private function connectDB()
	{
		try {
			shut_up();
			$this->use->database();
			speak_up();
		} catch (\Exception $e) {
			echo $this->error("\n\t" . $e->getMessage() . "\n");
			exit;
		}
	}

	/**
	 * Seeder Requirements
	 *
	 * @return void
	 */
	private function seederRequirements()
	{

		try {

			if (! file_exists($path = self::PATH)) {

				mkdir($path, 0777);

				file_put_contents($path . DS . "index.html", '');

				echo $this->success($path . "directory created");
			}

			$this->seederFiles = array_values(
				array_diff(
					scandir($path),
					['.', '..', 'index.html']
				)
			);
		} catch (\Exception $e) {
			echo $this->error("Error: " . $e->getMessage());
		}
	}

	/**
	 * Find Seeders
	 *
	 * @param string $path
	 * @return mixed
	 */
	protected function findSeeders($path = null)
	{
		if ($path != null) {
			return $this->seederFiles = array_values(
				array_diff(
					scandir($path),
					['.', '..', 'index.html']
				)
			);
		}

		return $this->seederFiles = array_values(
			array_diff(
				scandir(self::PATH),
				['.', '..', 'index.html']
			)
		);
	}

	/**
	 * Set Database to run migration on
	 *
	 * @param string $database
	 * @return void
	 */
	public function useDB($database = 'default')
	{
		$this->useDb = $database;
	}

	/**
	 * Seeder command entry point
	 *
	 * @return void
	 */
	public function index()
	{/* $this->run();*/
	}

	/**
	 * Get and prepare seeder
	 * 
	 * @param mixed $seeder
	 * @return void
	 */
	public function getSeeder($seeder)
	{

		$seeder = $this->prepare($seeder);

		// Get the seeder file
		$seederFile = self::PATH . DS . $seeder . EXT;

		if (!file_exists($seederFile)) {
			echo $this->error("\n[{$seeder}] file does not exists\n");
			exit;
		}

		if (file_exists($seederFile)) {
			require_once $seederFile;
		}

		$seederClass = (new TraverseClassFile)->getClassFullNameFromFile($seederFile);

		if (is_object(new $seederClass())) {
			$this->seeder = new $seederClass();
		}

		if (!$this->seeder->tableExists()) {
			throw new \Exception("[{$seeder}] table does not exist", ONE);
		}

		if (!method_exists($this->seeder, 'run') || !is_callable([$this->seeder, 'run'])) {
			throw new \Exception("[$seeder] Invalid seeder file", ONE);
		}

		if (method_exists($this->seeder, 'use') && is_callable([$this->seeder, 'use'])) {
			$this->seederFiles = $this->seeder->use();
		}
	}

	/**
	 * Run Single Seeder
	 *
	 * @param  string $seeder
	 * @return void
	 */
	public function single(string $seeder, $multiple = false)
	{

		if (!empty($seeder)) {
			$this->seederFiles = [$seeder];
		}

		if (!$this->seederFiles) {
			echo $this->error("\n\tNo Seeder File Available To Run\n");
			exit;
		}

		try {

			$this->getSeeder($seeder);

			$startTime = microtime(true);

			foreach ($this->seederFiles as $count => $file) {

				$this->info("\nProcessing $file \n");

				$file = str_ext($file, true);

				$this->call($file);

				$this->info("[$file] done" . PHP_EOL);
			}

			$elapsedTime = round(microtime(true) - $startTime, THREE) * 1000;

			$this->warning("Took $elapsedTime ms to run db:seed", ONE);
		} catch (\Exception $e) {
			$this->error("\n " . $e->getMessage() . "\n");
			exit;
		}
	}

	/**
	 * Run Multiple Seeders
	 *
	 * @param string $filename
	 * @return void
	 */
	public function multiple(?array $files = [])
	{

		if (!empty($files)) {
			$this->seederFiles = $files;
		}

		if (!$this->seederFiles) {
			echo $this->error("\n\tNo Seeder File Available To Run\n");
			exit;
		}

		try {

			$startTime = microtime(true);

			foreach ($this->seederFiles as $count => $file) {

				echo $this->info("\nProcessing $file \n");

				$file = str_ext($file, true);

				$this->call($file);

				$this->info("[$file] done" . PHP_EOL);
			}

			$elapsedTime = round(microtime(true) - $startTime, THREE) * 1000;

			$this->warning("Took $elapsedTime ms to run db:seed", ONE);
		} catch (\Exception $e) {
			$this->error("\n " . $e->getMessage() . "\n");
			exit;
		}
	}

	private function prepare(string $seeder)
	{
		$seeder = ucfirst(str_replace(['Seeder', 'seeder'], '', $seeder));
		return $seeder .= 'Seeder';
	}

	/**
	 * Prepare and Call Seeder
	 *
	 * @param  string $seeder
	 * @return void
	 */
	public function call(string $seeder)
	{

		$seeder = $this->prepare($seeder);

		// Get the seeder file
		$seederFile = self::PATH . DS . $seeder . EXT;

		if (!file_exists($seederFile)) {
			echo $this->error("[{$seeder}] file does not exists\n");
			exit;
		}

		require_once $seederFile; // require class file

		$seederClass = (new TraverseClassFile)->getClassFullNameFromFile($seederFile);

		if (is_object(new $seederClass())) {
			$this->seeder = new $seederClass();
		}

		try {

			if (!$this->seeder->tableExists()) {
				throw new \Exception("[{$seeder}] table does not exist\n", ONE);
			}

			if (!method_exists($this->seeder, 'run') || !is_callable([$this->seeder, 'run'])) {
				throw new \Exception("[$seeder] Invalid seeder file", ONE);
			}
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}

		$this->seeder->run();
	}

	/**
	 * Execute a given seeder class
	 * 
	 * @return void
	 */
	protected function execute()
	{
		call_user_func([$this->seeder, 'run']);
	}

	/**
	 * Truncate a specific table
	 * 
	 * @param string $tablename
	 * @throws \Exception
	 * @return never
	 */
	public function truncate(string $tablename): never
	{
		try {

			$this->tableExists($tablename);

			if (!$this->tableExists($tablename)) {
				throw new \Exception("[{$tablename}] table does not exist", ONE);
			}

			$truncated = $this->db->truncate($tablename);

			if ($truncated) {
				$this->success("\n\t[{$tablename}] table truncated successfully \n");
				exit;
			}

			$this->error("\n\t[{$tablename}] could not be truncated \n");
			exit;
		} catch (Exception $ex) {
			$this->error("\n\t" . $ex->getMessage() . "\n");
			exit;
		}
	}

	/**
	 * Check if table to seed exists
	 * 
	 * @param string $tablename
	 * @return bool
	 */
	private function tableExists(string $tablename): bool
	{
		if ($this->db->table_exists($tablename) === false) {
			return false;
		}

		return true;
	}
}
