<?php
namespace KVSun\KVSAPI;
use \shgysk8zer0\Core\{PDO};
use \SplFileObject as CSV;
use \RecursiveDirectoryIterator as DirectoryScanner;
use \PDOStatement;

final class Classifieds extends Abstracts\Content
{
	const DEFAULTS = [
		'ads'         => [],
		'categories'  => [],
		'content'     => [],
		'title'       => 'Classifieds',
		'description' => '',
		'keywords'    => [
			'classifieds',
			'help wanted',
		],
	];

	const TYPE = 'classifieds';

	const ALLOWED_TAGS = '<b><p><div><br><hr>';

	const IMG_DIR = '/classifieds/01%20Current%20Graphics/';

	const AD_FEED = '/01 Current Graphics/display ad feed.5.1.csv';

	const DISP_AD_PATTERN = '/\s*\*+\s*DISPLAY\s+AD\s*\*+\s?/im';

	private $_dir = '';

	/**
	 * Create a new instance of Classifieds class
	 * @param PDO    $pdo PDO instance
	 * @param String $dir Directory containing classifieds HTML files and images
	 */
	public function __construct(PDO $pdo, String $dir)
	{
		if (! is_dir($dir)) {
			$this->setStatus(500);
			throw new \InvalidArgumentException("{$dir} is not a directory");
		} else {
			$this->_dir = $dir;
			$this::_init($pdo, '/classifieds');
		}
	}


	/**
	 * Returns SQL to create prepared statement from
	 * @return String SELECT statement
	 */
	protected function _getSQL(): String
	{
		return 'SELECT `id`, `name` FROM `classifieds`;';
	}

	/**
	 * Set instance data using prepared statement to execute
	 * @param PDOStatement $stm Statment prepared using `$this::getSql`
	 */
	protected function _setData(PDOStatement $stm)
	{
		$stm->execute();
		$cats = $stm->fetchAll(PDO::FETCH_CLASS);
		$cats = array_reduce($cats, [$this, '_reduceCats'], []);
		$this->_set('ads', $this->_parseCSV($this->_dir . self::AD_FEED));
		$this->_set('categories', $cats);
		$this->_set('content', $this->_scanDir($cats));
	}

	/**
	 * Convert categories object into a category code indexed array
	 * @param  Array    $cats [$id => $name]
	 * @param  stdClass $cat  {"id": $id, "name": $name}
	 * @return Array          $cats with $cat appended to it
	 */
	private function _reduceCats(Array $cats, \stdClass $cat): Array
	{
		$cats[$cat->id] = $cat->name;
		return $cats;
	}

	/**
	 * Scans directory for HTML files listed in $cats
	 * @param  Array $cats [$id => $name,...]
	 * @return Array       Array of HTML for each category
	 */
	private function _scanDir(Array $cats): Array
	{
		$scanner = new DirectoryScanner($this->_dir);
		$scanner->setFlags(DirectoryScanner::SKIP_DOTS);
		$content = [];

		while ($scanner->valid()) {
			if (
				$scanner->isFile()
				and $scanner->getExtension() === 'html'
				and $scanner->isReadable()
				and array_key_exists($scanner->getBasename('.html'), $cats)
			) {
				$content[$scanner->getBasename('.html')] = $this->_parseHTML($scanner->getPathname());
			}
			$scanner->next();
		}
		return $content;
	}

	private function _parseHTML(String $fname): String
	{
		$html = file_get_contents($fname);
		$html = strip_tags($html, self::ALLOWED_TAGS);
		$html = preg_replace(self::DISP_AD_PATTERN, null, $html);
		return $html;
	}

	/**
	 * Parses a CSV file containing ad data
	 * @param  String $fname /path/to/file.csv
	 * @return Array         Array of ad data
	 */
	private function _parseCSV(String $fname): Array
	{
		ini_set('auto_detect_line_endings', true);
		$csv = new CSV($fname);
		$csv->setFlags(CSV::READ_CSV | CSV::DROP_NEW_LINE | CSV::SKIP_EMPTY);
		$rows = [];

		if ($csv->valid()) {
			$headers = $csv->fgetcsv();
			$csv->next();
			$header_size = count($headers);
		}

		while ($csv->valid()) {
			$row = $csv->fgetcsv();
			$csv->next();

			if (empty($row)) {
				continue;
			}

			$row = array_pad($row, $header_size, null);
			$row = array_combine($headers, $row);
			if (isset($row['Category Code'], $row['Ad Text'], $row['Filename'])) {
				if (! array_key_exists($row['Category Code'], $rows)) {
					$rows[$row['Category Code']] = [];
				}
				$rows[$row['Category Code']][] = [
					'text'  => $row['Ad Text'],
					'image' => self::IMG_DIR . rawurlencode($row['Filename']),
				];
			}
		}
		return $rows;
	}
}
