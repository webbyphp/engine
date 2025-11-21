<?php

use Base\Helpers\TraverseClassFile;
use Base\Controllers\ConsoleController;
use Base\Migrations\Migration as Migrate;

class Migration extends ConsoleController
{

	/**
	 * Migrations Enabled Constant
	 *
	 * @var string
	 */
	private const ENABLED = MIGRATION_ENABLED;

	/**
	 * Table Constant
	 *
	 * @var string
	 */
	private const TABLE = MIGRATION_TABLE;

	/**
	 * Path Constant
	 *
	 * @var string
	 */
	private	const PATH = MIGRATION_PATH;

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
	 * Migration files
	 *
	 * @var array
	 */
	private $migrationFiles = [];

	/**
	 * Previous Migrations
	 *
	 * @var string
	 */
	private $previousMigration = null;

	/**
	 * Default Database
	 *
	 * @var string
	 */
	private $useDb = 'default';

	/**
	 * Migration instance
	 *
	 * @var object
	 */
	private $migration;

	/**
	 * Schema Directory Exports
	 *
	 * @var string
	 */
	private $schemaExportDirectory = 'schema-exports';

	/**
	 * Database Dumps
	 *
	 * @var string
	 */
	private $databaseDumpDirectory = 'dumps';

	public function __construct()
	{
		parent::__construct();

		$this->onlydev();

		$this->connectDB();
		$this->use->dbforge();

		$this->migration = (new Migrate);

		$this->migrationRequirements();
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
	 * Migration Requirements
	 *
	 * @return void
	 */
	private function migrationRequirements()
	{
		if (!self::ENABLED) {
			echo $this->error("\tMigrations is currently disabled, please enable it to continue...");
			exit;
		}

		try {

			$table = self::TABLE;

			if (!$this->db->table_exists($table)) {

				$this->dbforge->add_field([
					'id' => [
						'type' => 'BIGINT',
						'unsigned' => true,
						'auto_increment' => true,
						'constraint' => 20,
					],
					'migration' => [
						'type' => 'VARCHAR(256)'
					],
					'batch' => [
						'type' => 'BIGINT',
						'constraint' => 20,
					],
					'run_at' => [
						'type' => 'timestamp'
					]
				])
					->add_key('id', true)
					->add_key('migration')
					->create_table($table);
			} else {

				$previousMigration = $this->db->order_by('migration', self::DESC)->get($table, 1)->result();

				$this->previousMigration = ($previousMigration)
					? $previousMigration[0]->migration
					: null;
			}

			if (! file_exists($path = self::PATH)) {

				mkdir($path, 0777);

				file_put_contents($path . DS . "index.html", '');

				echo $this->success($path . "directory created");
			}

			$this->migrationFiles = array_values(
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
	 * Check Executed Migration
	 *
	 * This checks if a single migration file has
	 * been run earlier
	 * 
	 * @param string $file
	 * @return mixed
	 */
	private function executedMigration($file)
	{
		return $this->db
			->where('migration', $file)
			->get(self::TABLE)->first_row();
	}

	/**
	 * Executed Migrations
	 *
	 * @param bool $latest
	 * @param bool $single
	 * @return mixed
	 */
	private function executedMigrations($latest = true, $single = false)
	{

		if ($single) {
			return $this->db
				->order_by('migration', $latest ? self::DESC : self::ASC)
				->get(self::TABLE)->first_row();
		}

		return $this->db
			->order_by('migration', $latest ? self::DESC : self::ASC)
			->get(self::TABLE)->result();
	}

	/**
	 * Prepare Up Migration
	 *
	 * @param  string $file
	 * @return void
	 */
	protected function prepareUpMigration($file)
	{
		require(self::PATH . DS . $file); // require class file

		$migrationFile = self::PATH . $file;
		$migrationClass = (new TraverseClassFile)->getClassFullNameFromFile($migrationFile);
		$class = null;

		if (is_object(new $migrationClass())) {
			$class = new $migrationClass();
		}

		if (!method_exists($class, 'up') || !is_callable([$class, 'up'])) {
			throw new \Exception("($file) Invalid migration file", 1);
		}

		call_user_func([$class, 'up'], $this->dbforge, $this->db);
	}

	/**
	 * Prepare Down Migration
	 *
	 * @param string $file
	 * @return void
	 */
	protected function prepareDownMigration($file)
	{
		require(self::PATH . DS . $file); // require class file

		$migrationFile = self::PATH . $file;
		$migrationClass = (new TraverseClassFile)->getClassFullNameFromFile($migrationFile);
		$class = null;

		if (is_object(new $migrationClass())) {
			$class = new $migrationClass();
		}

		if (!method_exists($class, 'down') || !is_callable([$class, 'down'])) {
			throw new \Exception("($file) Invalid migration file", 1);
		}

		call_user_func([$class, 'down'], $this->dbforge, $this->db);
	}

	/**
	 * Find Migrations
	 *
	 * @param string $path
	 * @return mixed
	 */
	protected function findMigrations($path = null)
	{
		if ($path != null) {
			return $this->migrationFiles = array_values(
				array_diff(
					scandir($path),
					['.', '..', 'index.html']
				)
			);
		}

		return $this->migrationFiles = array_values(
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
	 * Migration command entry point
	 *
	 * @return void
	 */
	public function index()
	{
		$this->run();
	}

	/**
	 * Run Migrations
	 *
	 * @param int $step
	 * @return void
	 */
	public function run($step = 0)
	{

		$lastMigrationFile = array_search($this->previousMigration, $this->migrationFiles);

		if ($this->previousMigration) {

			if ($lastMigrationFile !== false) {
				array_splice($this->migrationFiles, 0, $lastMigrationFile + 1);
			}
		}

		if (!$this->migrationFiles) {
			echo $this->error("\n\tNo Migration File Available To Run\n");
			exit;
		}

		try {

			$startTime = microtime(true);

			$batch = $this->executedMigrations(true, true);

			if (!empty($batch)) {
				$batch = $batch->batch + 1;
			} else {
				$batch = 1;
			}

			foreach ($this->migrationFiles as $count => $file) {

				echo $this->info("\tProcessing $file \n");

				$this->prepareUpMigration($file);

				$this->db->insert(self::TABLE, ['migration' => $file, 'batch' => $batch]);

				echo $this->success("\t$file done" . PHP_EOL);

				if ($step && ($count + 1) >= $step) {
					break;
				}
			}

			$elapsedTime = round(microtime(true) - $startTime, 3) * 1000;

			echo $this->warning("\tTook $elapsedTime ms to run migrations", 1);
		} catch (\Exception $e) {
			exit("Error: " . $e->getMessage() . PHP_EOL);
		}
	}

	/**
	 * Specify file to use for migrations
	 * and which method to run
	 * 
	 * @param string $file
	 * @param string $key
	 * @return void
	 */
	public function usefile($file, $key)
	{

		$migration = $this->executedMigration($file);

		if ($migration && $key === '--down') {

			try {

				$startTime = microtime(true);

				$file = $migration->migration;
				$path = self::PATH . $file;
				$exists = file_exists($path);

				if ($exists) {

					echo $this->info("\tDropping migration $file \n");

					$this->prepareDownMigration($file);

					$this->db->delete(self::TABLE, ['migration' => $migration->migration]);

					echo $this->success("\tDropped $file successfully" . PHP_EOL);
				}

				$elapsedTime = round(microtime(true) - $startTime, 3) * 1000;

				echo $this->warning("\tTook $elapsedTime ms to drop migration");
				exit;
			} catch (\Exception $e) {
				exit("Error: " . $e->getMessage() . PHP_EOL);
			}
		}

		if ($migration) {

			echo $this->info("\n\tMigration exists already, dropping: $file \n");

			$this->prepareDownMigration($file);

			$this->db->delete(self::TABLE, ['migration' => $migration->migration]);

			echo $this->warning("\tRun --up --usefile={$file} once more to complete migration" . PHP_EOL);
		}

		if (!$migration) {
			try {

				$startTime = microtime(true);

				$batch = $this->executedMigrations(true, true);

				if (!empty($batch)) {
					$batch = $batch->batch + 1;
				} else {
					$batch = 1;
				}

				echo $this->info("\tProcessing $file \n");

				$this->prepareUpMigration($file);

				$this->db->insert(self::TABLE, ['migration' => $file, 'batch' => $batch]);

				echo $this->success("\t$file done" . PHP_EOL);

				$elapsedTime = round(microtime(true) - $startTime, 3) * 1000;

				echo $this->warning("\tTook $elapsedTime ms to run migrations", 1);
			} catch (\Exception $e) {
				exit("Error: " . $e->getMessage() . PHP_EOL);
			}
		}
	}

	/**
	 * Export Database Schema As SQL
	 * 
	 * @param string $name
	 * @param string $removeTables
	 * @return void
	 */
	public function exportSchema($name = null, $removeTables = null)
	{

		$tables = $this->db->list_tables();

		if ($name === null) {
			echo $this->error("\tName cannot be empty please provide a name for file to be exported", 1);
			exit;
		}

		if ($removeTables != null) {
			$removeTables = str_replace('__', ',', $removeTables);
			$removeTables = explode(',', $removeTables);
			$tables = array_diff($tables, $removeTables);
		}

		$webbyVersion = WEBBY_VERSION;
		$appUrl = base_url();
		$dateGenerated = date('M d, Y') . ' at ' . date('H:i A');
		$phpVersion = PHP_VERSION;

		$description = <<<DESC
			-- Webby Schema Dump
			-- Version: {$webbyVersion}
			--
			-- App Url: {$appUrl}
			-- Generation Time: {$dateGenerated}
			-- PHP Version: $phpVersion

			SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
			START TRANSACTION;
			SET time_zone = "+00:00";
		DESC;

		$content = $description;
		$content .= "\r\n";

		$filepath = ROOTPATH . 'database' . DS . $this->schemaExportDirectory . DS;
		$filename = '';

		if ($tables) {
			foreach ($tables as $table) {
				$content .= "\r\n";
				$content .= 'DROP TABLE IF EXISTS `' . $table . '`;';
				$content .= "\r\n\n";
				$content .= $this->db->query('SHOW CREATE TABLE ' . $table)->row_array()['Create Table'] . ';';
				$content .= "\r\n";
			}

			$filename = $name . "-schema.sql";
		}

		if (!is_dir($filepath)) {
			mkdir($filepath, 0775);
		}

		$this->load->helper('file');
		$saved = write_file($filepath . $filename, $content);

		if ($saved) {
			echo $this->success("\n\tDatabase Schema ", 0) . $this->info("[" . $filename . "]", 0) . $this->success(" Exported successfully\n", 1);
			exit;
		}

		if ($saved) {
			echo $this->error("\n\tDatabase Schema ", 0) . $this->info("[" . $filename . "]", 0) . $this->error(" Was Not Exported\n", 1);
			exit;
		}
	}

	/**
	 * Dump All Database Schema As SQL
	 *
	 * @param string $name
	 * @return void
	 */
	public function dumpDb($name = null)
	{
		$tables = $this->db->list_tables();

		if ($name === null) {
			echo $this->error("\tName cannot be empty please provide a name for file to be dumped", 1);
			exit;
		}

		$webbyVersion = WEBBY_VERSION;
		$appUrl = base_url();
		$dateGenerated = date('M d, Y') . ' at ' . date('H:i A');
		$phpVersion = PHP_VERSION;

		$description = <<<DESC
			-- Webby Database Dump
			-- Version: {$webbyVersion}
			--
			-- App Url: {$appUrl}
			-- Generation Time: {$dateGenerated}
			-- PHP Version: $phpVersion

			SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
			START TRANSACTION;
			SET time_zone = "+00:00";
		DESC;

		$content = $description;
		$content .= "\r\n";

		$filepath = ROOTPATH . 'database' . DS . $this->databaseDumpDirectory . DS;
		$filename = '';

		if ($tables) {
			$i = 0;
			foreach ($tables as $table) {

				$content .= "\r\n";
				$content .= 'DROP TABLE IF EXISTS `' . $table . '`;';
				$content .= "\r\n\n";
				$content .= $this->db->query('SHOW CREATE TABLE ' . $table)->row_array()['Create Table'] . ';';
				$content .= "\r\n";

				$fields = $this->db->list_fields($table);
				$tableData = $this->db->query('SELECT * FROM ' . $table)->result_array();
				$insertField = '';
				$insertValues = '';

				if ($fields && $tableData) {

					$insertField .= "\r\n";
					$insertField .= 'INSERT INTO `' . $table . '` (';

					foreach ($fields as $field) {
						$insertField .= '`' . $field . '`,';
					}

					$insertField = substr($insertField, 0, -1);
					$insertField .= ')';

					$insertValues .= ' VALUES ';
					$insertValues .= "\r\n";

					foreach ($tableData as $tableRow) {

						$tableData .= '(';

						foreach ($tableRow as $column => $value) {
							$insertValues .= "'" . addslashes($value) . "',";
						}

						$insertValues = substr($insertValues, 0, -1);
						$insertValues .= '),';
						$insertValues .= "\r\n";
					}

					$insertValues = substr($insertValues, 0, -3) . ';';
					$insertValues .= "\r\n";
				}

				$content .= $insertField . $insertValues;

				$i++;
			}

			$filename = $name . "-dump.sql";
		}

		if (!is_dir($filepath)) {
			mkdir($filepath, 0775);
		}

		$this->load->helper('file');
		$saved = write_file($filepath . $filename, $content);

		if ($saved) {
			echo $this->success("\n\tDatabase Schema With Data  ", 0) . $this->info("[" . $filename . "]", 0) . $this->success(" Has Been Dumped successfully\n", 1);
			exit;
		}

		if (!$saved) {
			echo $this->error("\n\tDatabase Schema With Data ", 0) . $this->info("[" . $filename . "]", 0) . $this->error(" Was Not Dumped\n", 1);
			exit;
		}
	}

	/**
	 * Truncate Migrations Table
	 *
	 * @return void
	 */
	public function truncate($database = '')
	{
		$this->db->truncate(self::TABLE);
		echo $this->success("\n\tMigration table truncated successfully\n");
	}

	/**
	 * Reset Migrations
	 *
	 * @return void
	 */
	public function reset()
	{
		if ($this->previousMigration == null) {
			echo $this->warning("\n\tNo Migrations To Reset\n");
			exit;
		}

		if ($this->previousMigration) {
			$this->rollback(INF); // infinity
		}

		echo $this->warning("\n\tMigration has been reset to initial state successfully\n");
	}

	/**
	 * Rollback Migrations
	 *
	 * @param int $step
	 * @return void
	 */
	public function rollback($step = 0)
	{
		$previousMigrations = $this->executedMigrations();

		if (!$previousMigrations) {
			echo $this->warning("\n\tNo Migrations To Rollback\n");
			exit;
		}

		try {

			$startTime = microtime(true);

			foreach ($previousMigrations as $count => $migration) {

				$file = $migration->migration;
				$path = self::PATH . $file;
				$exists = file_exists($path);

				if ($exists) {

					echo $this->info("\n\tRolling back $file\n");

					$this->prepareDownMigration($file);

					$this->db->delete(self::TABLE, ['migration' => $migration->migration]);

					echo $this->success("\n\t$file done" . PHP_EOL);
				}

				if ($step && ($count + 1) >= $step) {
					break;
				}
			}

			$elapsedTime = round(microtime(true) - $startTime, 3) * 1000;

			echo $this->warning("\n\tTook $elapsedTime ms to rollback migrations\n");
		} catch (\Exception $e) {
			exit("Error: " . $e->getMessage() . PHP_EOL);
		}
	}

	/**
	 * Check Migrations Status
	 *
	 * @return void
	 */
	public function status()
	{

		$list = implode(PHP_EOL, array_map(
			function ($migration) {
				return $migration->run_at . ' ' . $migration->migration;
			},
			$this->executedMigrations(false)
		));

		$this->output->set_header('Content-type: text/plain');

		$output = '';

		if ($list) {

			$output = $this->info("\n\tMigrations Used", 2);
			$output .= $this->warning("$list", 2);
		} else {
			$output .= $this->warning("\n\tNo Migrations Staged Yet\n");
		}

		echo $output;
	}

	/**
	 * Check Latest or Current Migration
	 *
	 * @return void
	 */
	public function latest()
	{
		$migration = $this->executedMigrations(true, true);

		$output = '';

		$this->output->set_header('Content-type: text/plain');

		if (!$migration) {
			echo $output = $this->warning("\n\tNo Current Migration Available\n");
			exit;
		}

		$list = $migration->run_at . ' ' . $migration->migration;

		if ($list) {
			$output = $this->info("\n\tLatest Migration", 2);
			$output .= $this->warning("\t{$list}", 2);
		} else {
			$output .= $this->warning("\n\tNo Migrations Staged Yet\n");
		}

		echo $output;
	}

	/**
	 * Check Migrations To Be Used Later
	 *
	 * @return void
	 */
	public function future()
	{
		if ($this->previousMigration) {

			$lastMigrationFile = array_search($this->previousMigration, $this->migrationFiles);

			if (($lastMigrationFile) !== false) {
				array_splice($this->migrationFiles, 0, $lastMigrationFile + 1);
			}
		}

		$this->output->set_header('Content-type: text/plain');

		$output = '';

		if ($this->migrationFiles) {
			$output = $this->info("\n\tAvailable Migrations To Run", 2);
			$output .= $this->warning(implode(PHP_EOL, $this->migrationFiles), 2);
		} else {
			$output = $this->warning("\n\tAll Migrations Executed Already\n");
		}

		echo $output;
	}
}
