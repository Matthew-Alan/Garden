<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class Gdn {
	/// CONSTANTS ///
	const AliasAuthenticator = 'Authenticator';
	const AliasConfig = 'Config';
	const AliasDatabase = 'Database';
	const AliasDatabaseStructure = 'DatabaseStructure';
	const AliasDispatcher = 'Dispatcher';
	const AliasLocale = 'Locale';
	const AliasSession = 'Session';
	const AliasSqlDriver = 'SqlDriver';
	const AliasUser = 'UserModel';
	
	const FactoryInstance = 'Instance';
	const FactoryPrototype = 'Prototype';
	const FactorySingleton = 'Singleton';
	
	/// PROPERTIES ///
	
	protected static $_Config = NULL;
	
	/** @var Gdn_Factory The factory used to create core objects in the application. */
	protected static $_Factory = NULL;
	
	protected static $_Locale = NULL;
	
	protected static $_Session = NULL;
	
	/// METHODS ///
	
	public static function Authenticator() {
		$Result = self::Factory(self::AliasAuthenticator);
		return $Result;
	}
	
	/**
	 * Get a configuration setting for the application.
	 * @param string $Name The name of the configuration setting. Settings in different sections are seperated by a dot ('.')
	 * @param mixed $Default The result to return if the configuration setting is not found.
	 * @return mixed The configuration setting.
	 */
	public static function Config($Name = FALSE, $Default = FALSE) {
		$Config = self::$_Config;
		if($Name === FALSE)
			$Result = $Config;
		else
			$Result = $Config->Get($Name, $Default);
			
		return $Result;
	}
	
	public static function Dispatcher() {
		$Result = self::Factory(self::AliasDispatcher);
		return $Result;
	}
	
	/**
	 * Get a reference to the default database object.
	 * @return Gdn_Database
	 */
	public static function Database() {
		$Result = self::Factory(self::AliasDatabase);
		return $Result;
	}
	
	/**
	 * Get an object from the factory.
	 * @param string $Alias The alias of the class.
	 * @param mixed $Args A variable number of arguments to pass to the constructor.
	 * @see Gdn_Factory::Factory()
	 */
	public static function Factory($Alias) {
		// Get the arguments to pass to the factory.
		$Args = func_get_args();
		array_shift($Args);
		
		$Result = self::$_Factory->Factory($Alias, $Args);
		return $Result;
	}
	
	/**
	 * Checks whether or not a factory alias exists.
	 * 
	 * @param string $Alias The alias of the factory to check for.
	 * @return boolean Whether or not a factory definintion exists.
	 * @see Gdn_Factory::Exists()
	 */
	public static function FactoryExists($Alias) {
		$Result = self::$_Factory->Exists($Alias);
		return $Result;
	}
	
	/**
	 * Install a class to the factory.
	 * 
	 * @param string $Alias An alias for the class that will be used to retreive instances of it.
	 * @param string $ClassName The actual name of the class.
	 * @param string $Path The path to the class' file. You can prefix the path with ~ to start at the application root.
	 * @param string $FactoryType The way objects will be instantiated for the class. One of (Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton).
	 * @param boolean $Override Whether or not to override an existing factory alias.
	 * @see Gdn_Factory::Install()
	 */
	public static function FactoryInstall($Alias, $ClassName, $Path, $FactoryType = self::FactoryInstance, $Data = NULL, $Override = TRUE) {
		self::$_Factory->Install($Alias, $ClassName, $Path, $FactoryType, $Data, $Override);
		
		// Cache some of the more commonly used factory objects as properties.
		switch($Alias) {
			case self::AliasConfig:
				self::$_Config = self::Factory($Alias);
				break;
			case self::AliasSession:
				self::$_Session = NULL;
				break;
		}
	}
	
	/**
	 * Installs a dependency to the factory.
	 *
	 *	@param string $Alias The alias of the class that will have the dependency.
	 * @param string $PropertyName The name of the property on the class that will have the dependency.
	 * @param string $SourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
	 * @param boolean $Override Whether or not to override an existing dependency.
	 * @see Gdn_Factory::InstalDependency()
	 */
	public static function FactoryInstallDependency($Alias, $PropertyName, $SourceAlias, $Override = TRUE) {
		self::$_Factory->InstallDependency($Alias, $PropertyName, $SourceAlias, $Override);
	}
	
	/**
	 * Installs a dependency to the factory with the settings from a configuration.
	 *
	 * @param mixed $Config The configuration of the factory definition. This argument can be of the following types:
	 * - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()}
	 * - <b>array</b>: The configuration will be set from the array.
	 * @param string $Alias The class alias to install into the factory. If omitted then it must be in the configuration.
	 *
	 * The dependency will be installed from the configuration array depending on the following keys:
	 * - <b>Alias</b>: Optional if $Alias is passed as an argument.
	 * - <b>PropertyName</b>: Required.
	 * - <b>SourceAlias</b>: Required.
	 * - <b>Override</b> Optional.
	 * All of these values are passed to the corresponding argument in inline{@link Gdn::FactoryInstallDependency()}.
	 */
	public static function FactoryInstallDependencyFromConfig($Config, $Alias = NULL) {
		if(is_string($Config))
			$Config = self::Config($Config);
		if(is_null($Alias))
			$Alias = $Config['Alias'];
			
		$PropertyName = $Config['PropertyName'];
		$SourceAlias = $Config['SourceAlias'];
		$Override = ArrayValue('Override', $Config, TRUE);
		
		self::FactoryInstallDependency($Alias, $PropertyName, $SourceAlias, $Override);
	}
	
	/**
	 * Installs a class to the factory with the settings from a configuration.
	 *
	 * @param mixed $Config The configuration of the factory definition. This argument can be of the following types:
	 * - <b>string</b>: The configuration will be looked up by calling inline{@link Gdn::Config()}
	 * - <b>array</b>: The configuration will be set from the array.
	 * @param string $Alias The class alias to install into the factory. If omitted then it must be in the configuration.
	 *
	 * The factory will be installed from the configuration array depending on the following keys:
	 * - <b>Alias</b>: Optional if $Alias is passed as an argument.
	 * - <b>FactoryType</b>: Required.
	 * - <b>Data</b>: Optional.
	 * - <b>Override</b> Optional.
	 * - <b>Dependencies</b> Optional. Dependencies for the class can be defined as a subarray. Each item in the subarray will be passed to inline{@link Gdn::FactoryInstallDependencyFromConfig}.
	 * All of these values (except Dependencies) are passed to the corresponding argument in inline{@link Gdn::FactoryInstall()}.
	 */
	public static function FactoryInstallFromConfig($Config, $Alias = NULL) {
		if(is_string($Config))
			$Config = self::Config($Config);
		if(is_null($Alias))
			$Alias = $Config['Alias'];
			
		$FactoryType = $Config['FactoryType'];
		$Data = ArrayValue('Data', $Config, NULL);
		$Override = ArrayValue('Override', $Config, TRUE);
			
		self::FactoryInstall($Alias, $Config['ClassName'], $Config['Path'], $FactoryType, $Data, $Override);
		
		if(array_key_exists('Dependencies', $Config)) {
			$Dependencies = $Config['Dependencies'];
			foreach($Dependencies as $Index => $DependencyConfig) {
				self::FactoryInstallFromConfig($DependencyConfig, $Alias);
			}
		}
	}
	
	/**
	 * Uninstall an class from the factory.
	 * 
	 * @param string $Alias The alias of the class to uninstall.
	 * @see Gdn_Factory::Uninstall()
	 */
	public static function FactoryUninstall($Alias) {
		self::$_Factory->Uninstall($Alias);
	}
	
	/**
	 * Uninstall a dependency from the factory.
	 *
	 * @see Gdn_Factory::UninstallDependency()
	 */
	public static function FactoryUninstallDependency($Alias, $PropertyName = NULL) {
		self::$_Factory->UninstallDependency($Alias, $PropertyName);
	}
	
	/**
	 * @return Gdn_Locale
	 */
	public static function Locale() {
		if(is_null(self::$_Locale))
			self::$_Locale = self::Factory(self::AliasLocale);
		
		return self::$_Locale;
	}
	
	/**
	 * Get the session object.
	 *
	 * @return Gdn_Session
	 */
	public static function Session() {
		if(is_null(self::$_Session))
			self::$_Session = self::Factory(self::AliasSession);
		return self::$_Session;
	}
	
	/**
	 * Get a reference to the default SQL driver object.
	 * 
	 * @return Gdn_SQLDriver
	 * @see Gdn_Database::SQL()
	 */
	public static function SQL() {
		$Database = self::Database();
		$Result = $Database->SQL();
		return $Result;
	}
	
	/**
	 * Get a reference to the default database structure object.
	 * @return Gdn_DatabaseStructure
	 */
	public static function Structure() {
		$Database = self::Database();
		$Result = $Database->Structure();
		return $Result;
	}
	
	/**
	 * Translates a code into the selected locale's definition.
	 *
	 * @param string $Code The code related to the language-specific definition.
	 * @param string $Default The default value to be displayed if the translation code is not found.
	 * @return string The translated string or $Code if there is no value in $Default.
	 */
	public static function Translate($Code, $Default = '') {
		if ($Default == '')
			$Default = $Code;
			
		return Gdn::Locale()->Translate($Code, $Default);
		/*
		if (is_null(self::$_Locale))
			return $Default;
		else 
			return self::$_Locale->Translate($Code, $Default);
		*/
	}
	
	/**
	 * Get a reference to the user model.
	 * 
	 * @return Gdn_User
	 */
	public static function UserModel() {
		$Result = self::Factory(self::AliasUser);
		return $Result;
	}
	
	/**
	 * Set the object used as the factory for the api.
	 * 
	 * @param Gdn_Factory $Factory The object used as the factory.
	 * @param boolean $Override whether to override the property if it is already set.
	 */
	public static function SetFactory($Factory, $Override = TRUE) {
		if($Override || self::$_Factory == NULL)
			self::$_Factory = $Factory;
	}
}