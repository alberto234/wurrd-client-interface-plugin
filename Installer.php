<?php
/*
 * This file is a part of Wurrd ClientInterface plugin.
 *
 * Copyright 2005-2015 the original author or authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Wurrd\Mibew\Plugin\ClientInterface;

use Mibew\Database;
use Mibew\Settings;
use Mibew\Mail\Utils as MailUtils;
use Mibew\Maintenance\Installer as CoreInstaller;
use Symfony\Component\Yaml\Parser as YamlParser;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * Encapsulates installation process.
 */
class Installer extends CoreInstaller
{
    /**
     * Class constructor.
     *
     * @param array $system_configs Associative array of system configs.
     */
    public function __construct($system_configs)
    {
    	parent::__construct($system_configs);
    }

    /**
     * Create tables.
     *
     * One can get all logged messages of this step using
     * {@link Installer::getLog()} method. Also the list of all errors can be
     * got using {@link Installer::getErrors()}.
     *
     * @return boolean True if all tables are created and false otherwise.
     */
    public function createTables()
    {
        if ($this->tablesExist() && $this->tablesNeedUpdate()) {
            // Tables already exists but they should be updated
            $this->errors[] = getlocal('The tables are alredy in place but outdated. Run the updater to fix it.');
            return false;
        }

        if ($this->tablesExist()) {
            $this->log[] = getlocal('Tables structure is up to date.');
            return true;
        }

        // There are no tables in the database. We need to create them.
        if ($this->getDatabaseSchema() != null) {
	        if (!$this->doCreateTables()) {
	            return false;
	        }
	        $this->log[] = getlocal('Tables are created.');
		}
		
        if (!$this->prepopulateDatabase()) {
            return false;
        }
        $this->log[] = getlocal('Tables are pre popluated with necessary info.');

        return true;
    }


    /**
     * Drop tables.
     *
     * One can get all logged messages of this step using
     * {@link Installer::getLog()} method. Also the list of all errors can be
     * got using {@link Installer::getErrors()}.
     *
     * @return boolean True if all tables are dropped and false otherwise.
     */
    public function dropTables()
    {
        // Drop the tables.
        if (!$this->doDropTables()) {
            return false;
        }
        $this->log[] = getlocal('Tables are removed.');

		// Remove config info
        if (!$this->removeConfigInfo()) {
            return false;
        }
        $this->log[] = getlocal('Plugin config information removed from database.');

        return true;
    }

    /**
     * Loads database schema.
     *
     * @return array Associative array of database schema. Each key of the array
     *   is a table name and each value is its description. Table array itself
     *   is an associative array with the following keys:
     *     - fields: An associative array, which keys are MySQL columns names
     *       and values are columns definitions.
     *     - unique_keys: An associative array. Each its value is a name of a
     *       table's unique key. Each value is an array with names of the
     *       columns the key is based on.
     *     - indexes: An associative array. Each its value is a name of a
     *       table's index. Each value is an array with names of the
     *       columns the index is based on.
     */
    protected function getDatabaseSchema()
    {
        return $this->parser->parse(file_get_contents(__DIR__ . '/database_schema.yml'));
    }
	
    /**
     * Gets version of existing database structure for the Wurrd:AuthAPI plugin.
     *
     * If the plugin is not installed yet boolean false will be returned.
     *
     * @return string|boolean Database structure version or boolean false if the
     *   version cannot be determined.
     */
    protected function getDatabaseVersion()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            $result = $db->query(
                "SELECT vcvalue AS version FROM {config} WHERE vckey = :key LIMIT 1",
                array(':key' => Constants::WCI_VERSION_KEY),
                array('return_rows' => Database::RETURN_ONE_ROW)
            );
        } catch (\Exception $e) {
            return false;
        }

        if (!$result) {
            // It seems that database structure version isn't stored in the
            // database.
            return false;
        }

        return $result['version'];
    }


    /**
     * Checks if the database structure must be updated.
     *
     * @return boolean
     */
    protected function tablesNeedUpdate()
    {
        return version_compare($this->getDatabaseVersion(), Constants::WCI_VERSION, '<');
    }

    /**
     * Checks if database structure is already created.
     *
     * @return boolean
     */
    protected function tablesExist()
    {
        return ($this->getDatabaseVersion() !== false);
    }


    /**
     * Drop all tables.
     *
     * @return boolean Indicates if tables removed or not.
     */
    protected function doDropTables()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            // Drop tables as defined by the schema
            $schema = $this->getDatabaseSchema();
			
			// We need to delete backwards such that foreign key constraints are not violated
			// ??? or, we can do it from top to bottom with CASCADE ???
			if ($schema != null) {
				$tables = array_keys($schema);
				$tableCount = count($tables);
				for ($i = $tableCount - 1; $i >= 0; $i--) {
	                $db->query(sprintf(
	                    'DROP TABLE IF EXISTS {%s}',
	                    $tables[$i]
	                ));
				}
			}
        } catch (\Exception $e) {
            $this->errors[] = getlocal(
                'Cannot drop tables. Error: {0}',
                array($e->getMessage())
            );

            return false;
        }

        return true;
    }

    /**
     * Saves some necessary data in the database.
     *
     * This method is called just once after tables are created.
     *
     * @return boolean Indicates if the data are saved to the database or not.
     */
    protected function prepopulateDatabase()
    {
    	return $this->prepopulateSettings();
    }

    /**
     * Creates settings (configs) about this plugin.
     *
     * This method is called just once after tables are created.
     *
     * @return boolean Indicates if the data are saved to the database or not.
     */
    protected function prepopulateSettings()
    {
		if (Settings::get(Constants::WCI_VERSION_KEY) == null) {
			Settings::set(Constants::WCI_VERSION_KEY, Constants::WCI_VERSION);
			Settings::set(Constants::WCI_API_VERSION_KEY, Constants::WCI_API_VERSION);
			Settings::set(Constants::WCI_INSTALLATION_ID_KEY, 
							strtr(base64_encode(hash("sha256", time(), true)), '/', '-'));
		}
		
		return true;
    }

    /**
     * Removes this plugin's configuration info from the database
     *
     * This method is called just once after tables are dropped.
     *
     * @return boolean Indicates if the config info is removed or not.
     */
    protected function removeConfigInfo()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            $db->query(
                'DELETE FROM {config} WHERE vckey LIKE \'' . 
                Constants::WCI_CONFIG_PREFIX . '%\'');
        } catch (\Exception $e) {
            $this->errors[] = getlocal(
                'Cannot remove the plugin database structure version. Error {0}',
                array($e->getMessage())
            );

            return false;
        }
		
		return true;
    }


}
