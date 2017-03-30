<?php
namespace KVSun\KVSAPI;
use \shgysk8zer0\Schema\{Person, PostalAddress, Organization, ImageObject, OpeningHoursSpecification};
use \shgysk8zer0\Core\{Gravatar};
class Contact extends Abstracts\Content
{
	const TYPE = 'contact';

	const DEFAULTS = [];

	const EMPLOYEES = [
		[
			'fname' => 'Marsha',
			'lname' => 'Smith',
			'email' => 'marshas@kvsun.com',
			'ext'   => 15,
			'title' => 'Publisher',
		],[
			'fname' => 'Natalie',
			'lname' => 'Brown',
			'email' => 'natalieb@kvsun.com',
			'ext'   => 12,
			'title' => 'General Manager',
		],  [
			'fname' => 'Chris',
			'lname' => 'Zuber',
			'email' => 'czuber@kvsun.com',
			'ext'   => 14,
			'title' => 'Editor of Valley News',
		], [
			'fname' => 'Ashley',
			'lname' => 'Loza',
			'email' => 'ashleyl@kvsun.com',
			'ext'   => 20,
			'title' => 'Editor of Valley Life',
		], [
			'fname' => 'Ray',
			'lname' => 'Conner',
			'email' => 'sports@kvsun.com',
			'ext'   => 13,
			'title' => 'Sports Editor',
		], [
			'fname' => 'Clayton',
			'lname' => 'Huckaby',
			'email' => 'claytonh@kvsun.com',
			'ext'   => 13,
			'title' => 'Coordinating Editor',
		], [
			'fname' => 'Christina',
			'lname' => 'Denys',
			'email' => 'classified@kvsun.com',
			'ext'   => 11,
			'title' => 'Classifieds, Legals and Obituaries Manager',
		], [
			'fname' => 'Steve',
			'lname' => 'Rinehart',
			'email' => 'circulation@kvsun.com',
			'ext'   => 22,
			'title' => 'Circulation Manager',
		], [
			'fname' => 'Sarah',
			'lname' => 'Rooffener',
			'email' => 'production@kvsun.com',
			'ext'   => 21,
			'title' => 'Production',
		], [
			'fname' => 'Shannon',
			'lname' => 'Rapose',
			'email' => 'production2@kvsun.com',
			'ext'   => 19,
			'title' => 'Production Assistant',
		], [
			'fname' => 'Michele',
			'lname' => 'Lynn',
			'email' => 'michelel@kvsun.com',
			'ext'   => 17,
			'title' => 'Advertising Sales',
		], [
			'fname' => 'Tam',
			'lname' => 'Hartman',
			'email' => 'tamh@kvsun.com',
			'ext'   => 23,
			'title' => 'Advertising Sales',
		],
	];

	const HOURS = [
		'Monday' => [
			'opens' => '7:30',
			'closes' => '16:30',
		], 'Tuesday' => [
			'opens' => '7:30',
			'closes' => '16:30',
		], 'Wednesday' => [
			'opens' => '7:30',
			'closes' => '16:30',
		], 'Thursday' => [
			'opens' => '7:30',
			'closes' => '16:30',
		], 'Friday' => [
			'opens' => '7:30',
			'closes' => '16:30',
		],
	];

	public function __construct(\PDO $pdo, String $url)
	{
		$this->_init($pdo, $url);
	}

	protected function _getSQL(): String
	{
		return 'SELECT `id`, `name` FROM `classifieds`;';
	}

	protected function _setData(\PDOStatement $stm)
	{
		$org     = new Organization();
		$address = new PostalAddress();
		$logo    = new ImageObject();

		$address->setStreetAddress('6416 Lake Isabella Blvd');
		$address->setPostOfficeBoxNumber('P.O. Box 3074');
		$address->setLocality('Lake Isabella');
		$address->setCountry('US');
		$address->setRegion('CA');
		$address->setPostalCode(93240);

		foreach (self::HOURS as $day => $hours) {
			$times = new OpeningHoursSpecification();
			$times->setDayOfWeek($day);
			$times->setOpens($hours['opens']);
			$times->setCloses($hours['closes']);
			$address->addHoursAvailable($times);
		}

		$logo->setURL('https://kernvalleysun.com/images/sun-icons/256.png');
		$logo->setWidth(256);
		$logo->setHeight(256);
		$logo->setEncodingFormat('image/png');
		$logo->setContentSize(42);

		$org->setName('Kern Valley Sun');
		$org->setAddress($address);
		$org->setLogo($logo);
		$org->setURL('https://kernvalleysun.com');
		$org->setTelephone('+1-760-379-3667');
		$org->setFaxNumber('+1-760-379-4343');

		foreach (self::EMPLOYEES as $employee) {
			$emp = new Person();
			$emp->setGivenName($employee['fname']);
			$emp->setFamilyName($employee['lname']);
			$emp->setJobTitle($employee['title']);
			$emp->setTelephone("{$org->telephone},{$employee['ext']}");
			$emp->setEmail($employee['email']);
			$avatar = new ImageObject();
			$avatar->setURL(new Gravatar($emp->email, 128));
			$avatar->setHeight(128);
			$avatar->setWidth(128);
			$emp->setImage($avatar);

			$org->addEmployee($emp);
		}
		$this->_set('title', 'Contact Us');
		$this->_set('description', 'Contact info for Kern Valley Sun & employees');
		$this->_set('keywords', 'KVSun, contact info');
		$this->_set('contactInfo', $org);
	}
}
