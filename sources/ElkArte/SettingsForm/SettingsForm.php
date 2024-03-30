<?php

/**
 * This class handles display, edit, save, of forum settings.
 *
 * @package   ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause (see accompanying LICENSE.txt file)
 *
 * This file contains code covered by:
 * copyright: 2011 Simple Machines (http://www.simplemachines.org)
 *
 * @version 2.0 dev
 *
 *
 * Adding options to one of the setting screens isn't hard.
 *
 * Call prepareDBSettingsContext;
 * The basic format for a checkbox is:
 *    array('check', 'nameInModSettingsAndSQL'),
 * And for a text box:
 *    array('text', 'nameInModSettingsAndSQL')
 * (NOTE: You have to add an entry for this at the bottom!)
 *
 * In the above examples, it will look for $txt['nameInModSettingsAndSQL'] as the description,
 * and $helptxt['nameInModSettingsAndSQL'] as the help popup description.
 *
 * Here's a quick explanation of how to add a new item:
 *
 * - A text input box.  For textual values.
 *     array('text', 'nameInModSettingsAndSQL', 'OptionalInputBoxWidth'),
 * - A text input box.  For numerical values.
 *     array('int', 'nameInModSettingsAndSQL', 'OptionalInputBoxWidth'),
 * - A text input box.  For floating point values.
 *     array('float', 'nameInModSettingsAndSQL', 'OptionalInputBoxWidth'),
 * - A large text input box. Used for textual values spanning multiple lines.
 *     array('large_text', 'nameInModSettingsAndSQL', 'OptionalNumberOfRows'),
 * - A checkbox.  Either one or zero. (boolean)
 *     array('check', 'nameInModSettingsAndSQL'),
 * - A selection box.  Used for the selection of something from a list.
 *     array('select', 'nameInModSettingsAndSQL', array('valueForSQL' => $txt['displayedValue'])),
 *     Note that just saying array('first', 'second') will put 0 in the SQL for 'first'.
 * - A password input box. Used for passwords, no less!
 *     array('password', 'nameInModSettingsAndSQL', 'OptionalInputBoxWidth'),
 * - A permission - for picking groups who have a permission.
 *     array('permissions', 'manage_groups'),
 * - A BBC selection box.
 *     array('bbc', 'sig_bbc'),
 *  - A simple message.
 *      array('message', 'a simple message'),
 *  - A html5 input
 *      array(one of 'url', 'search', 'date', 'email', 'color') will act like an input with type=xyz
 *
 * For each option:
 *  - type (see above), variable name, size/possible values.
 *    OR make type '' for an empty string for a horizontal rule.
 *  - SET preinput - to put some HTML prior to the input box.
 *  - SET postinput - to put some HTML following the input box.
 *  - SET subtext - to put text under the description text.
 *  - SET invalid - to mark the data as invalid.
 *  - SET disabled - to disable the field from entry
 *  - SET helptext - add a (?) icon with help text (done automatically if var is in $helptxt)
 *
 *  - PLUS you can override label and help parameters by forcing their keys in the array, for example:
 *    array('text', 'invalid label', 3, 'label' => 'Actual Label')
 *  - force_div_id => 'xyz' to set a field input to a specific id, great for JS targeting
 */

namespace ElkArte\SettingsForm;

use ElkArte\SettingsForm\SettingsFormAdapter\Adapter;
use ElkArte\SettingsForm\SettingsFormAdapter\Db;
use ElkArte\SettingsForm\SettingsFormAdapter\DbTable;
use ElkArte\SettingsForm\SettingsFormAdapter\File;

/**
 * Class SettingsForm
 *
 * The SettingsForm class is responsible for managing the configuration settings for the application.
 *
 *  - This class handles display, edit, save, of forum settings.
 *  - It is used by the various admin areas, and it is available for addons administration screens.
 */
class SettingsForm
{
	/** @var string The constant representing the database adapter class. */
	public const DB_ADAPTER = Db::class;

	/** @var DbTable  */
	public const DBTABLE_ADAPTER = DbTable::class;

	/** @var File  */
	public const FILE_ADAPTER = File::class;

	/** @var Adapter */
	private $adapter;

	/**
	 * @param string|null $adapter Will default to the file adapter if none is specified.
	 */
	public function __construct($adapter = null)
	{
		$fqcn = $adapter ?: self::FILE_ADAPTER;

		$this->adapter = new $fqcn();
	}

	/**
	 * Retrieve the configuration variables from the adapter.
	 *
	 * @return array The configuration variables retrieved from the adapter.
	 */
	public function getConfigVars()
	{
		return $this->adapter->getConfigVars();
	}

	/**
	 * Sets the configuration variables for the adapter.
	 *
	 * @param array $configVars An associative array of configuration variables.
	 */
	public function setConfigVars(array $configVars)
	{
		$this->adapter->setConfigVars($configVars);
	}

	/**
	 * Get the configuration values from the adapter.
	 *
	 * @return array The configuration values returned by the adapter.
	 */
	public function getConfigValues()
	{
		return $this->adapter->getConfigValues();
	}

	/**
	 * Sets the config values for the adapter.
	 *
	 * @param array $configValues An array of config values to be set.
	 *
	 * @return void
	 */
	public function setConfigValues(array $configValues)
	{
		$this->adapter->setConfigValues($configValues);
	}

	/**
	 * Retrieves the currently configured adapter.
	 *
	 * @return mixed Returns the current adapter being used.
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * This method reads the settings and prepares them for display within the template.
	 *
	 * It will read either Settings.php or the settings table
	 * according to the adapter specified in the constructor.
	 *
	 * Creates the token `admin-ssc`
	 */
	public function prepare()
	{
		createToken('admin-ssc');
		$this->adapter->prepare();
	}

	/**
	 * This method saves the settings.
	 *
	 * It will put them in Settings.php or in the settings table
	 * according to the adapter specified in the constructor.
	 *
	 * Validates the token `admin-ssc`
	 */
	public function save()
	{
		validateToken('admin-ssc');

		$this->adapter->save();
	}
}
