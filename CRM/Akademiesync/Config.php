<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 1 March 2017
 * @license AGPL-3.0
 */
class CRM_Akademiesync_Config {

  private static $_singleton;

  protected $_resourcesPath = NULL;
  protected $_importSettings = array();
  protected $_dataDifferenceActivityTypeId = NULL;
  protected $_campaignOptionGroupId = null;
  protected $_translatedStrings = array();
  protected $_loadingTypes = array();
  protected $_defaultPhoneTypeId = NULL;
  protected $_defaultLocationTypeId = NULL;
  protected $_employeeRelationshipTypeId = NULL;
  protected $_errorActivityTypeId = NULL;
  protected $_scheduledActivityStatusId = NULL;

  /**
   * Constructor method
   *
   * @param string $context
   */
  function __construct($context) {

    $settings = civicrm_api3('Setting', 'Getsingle', array());
    $this->_resourcesPath = $settings['extensionsDir'].'/de.forumzfd.akademiesync/resources/';
    $this->_loadingTypes = array('contact', 'campaign');
    $this->_defaultLocationTypeId = civicrm_api3('LocationType', 'getvalue', array('is_default' => 1, 'return' => 'id'));
    // set default phone type to phone or first one active if not found
    try {
      $this->_defaultPhoneTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => "phone_type",
        'name' => "phone",
        'return' => 'value'
      ));
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_defaultPhoneTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => "phone_type",
        'is_active' => 1,
        'return' => 'value',
        'options' => array('limit' => 1),
      ));
    }
    $this->setRelationshipTypeIds();
    $this->setOptionGroupIds();
    $this->setActivityTypeIds();
    $this->setActivityStatusIds();
    $this->setImportSettings();
    $this->setTranslationFile();
  }

  /**
   * Method to set (or create if they do not exist) the option groups required
   *
   * @access private
   * @throws Exception when error from api OptionGroup create
   */
  private function setOptionGroupIds() {
    try {
      $this->_campaignOptionGroupId = civicrm_api3('OptionGroup', 'getvalue', array(
        'name' => 'forumzfd_internal_campaigns',
        'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      try {
        $optionGroup = civicrm_api3('OptionGroup', 'create', array(
          'name' => 'forumzfd_internal_campaigns',
          'title' => $this->translate('ForumZFD Campaigns from Internal CiviCRM'),
          'description' => $this->translate('This option group holds the campaigns from the internal CiviCRM'),
          'is_active' => 1
        ));
        $this->_campaignOptionGroupId = $optionGroup['id'];
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception($this->translate('Could not create an option group with name forumzfd_internal_campaigns')
          .' in '.__METHOD__.', '.$this->translate('contact your system administrator').'. '
          .$this->translate('Error from API OptionGroup create').': '.$ex->getMessage());
      }
    }
  }

  /**
   * Method to set the required activity type ids (or create them if they do not exist yet)
   *
   * @access private
   * @throws Exception when error from API optionvalue create
   */
  private function setActivityTypeIds() {
    $activityTypes = array(
      '_dataDifferenceActivityTypeId' => array(
        'name' => 'forumzfd_data_difference',
        'label'=> $this->translate('Data Difference in Import from Internal CiviCRM')),
      '_errorActivityTypeId' => array(
        'name' => 'forumzfd_import_error',
        'label'=> $this->translate('Error when importing from Internal CiviCRM')));
    foreach ($activityTypes as $activityTypeProperty => $activityType) {
      try {
        $this->$activityTypeProperty = civicrm_api3('OptionValue', 'getvalue', array(
          'option_group_id' => 'activity_type',
          'name' => $activityType['name'],
          'return' => 'value'));
      } catch (CiviCRM_API3_Exception $ex) {
        try {
          $optionValue = civicrm_api3('OptionValue', 'create', array(
            'option_group_id' => 'activity_type',
            'name' => $activityType['name'],
            'label' => $activityType['label'],
            'is_active' => 1));
        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception($this->translate('Could not create an activity type with name').' '.$activityTypeName
            .' in '.__METHOD__.', '.$this->translate('contact your system administrator').'. '
            .$this->translate('Error from API OptionValue create').': '.$ex->getMessage());
        }
      }
    }
  }

  /**
   * Method to set the required activity status
   *
   * @access private
   * @throws Exception when error from api option value getvalue
   */
  private function setActivityStatusIds() {
    try {
     $this->_scheduledActivityStatusId = civicrm_api3('OptionValue', 'getvalue', array(
       'option_group_id' => 'activity_type',
       'name' => 'Scheduled',
       'return' => 'value'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception($this->translate('Could not find an activity status Scheduled').' in '.__METHOD__.', '
        .$this->translate('contact your system administrator').'. '.$this->translate('Error from API OptionValue getvalue')
        .': '.$ex->getMessage());
    }
  }

  private function setRelationshipTypeIds() {

  }


  /**
   * Getter for default phone type id
   *
   * @return mixed
   * @access public
   */
  public function getDefaultPhoneTypeId() {
    return $this->_defaultPhoneTypeId;
  }

  /**
   * Getter for default location type id
   *
   * @return mixed
   * @access public
   */
  public function getDefaultLocationTypeId() {
    return $this->_defaultLocationTypeId;
  }

  /**
   * Getter for valid loading types
   *
   * @return array
   * @access public
   */
  public function getLoadingTypes() {
    return $this->_loadingTypes;
  }

  /**
   * Getter for option group id for campaigns from internal CiviCRM
   *
   * @return mixed
   * @access public
   */
  public function getCampaignOptionGroupId() {
    return $this->_campaignOptionGroupId;
  }

  /**
   * Getter for employee relationship type id
   *
   * @return mixed
   * @access public
   */
  public function getEmployeeRelationshipTypeId() {
    return $this->_employeeRelationshipTypeId;
  }

  /**
   * Getter for error activity type id
   *
   * @return mixed
   * @access public
   */
  public function getErrorActivityTypeId() {
    return $this->_errorActivityTypeId;
  }

  /**
   * Getter for scheduled activity status id
   *
   * @return mixed
   * @access public
   */
  public function getScheduledActivityStatusId() {
    return $this->_scheduledActivityStatusId;
  }

  /**
   * Getter for data difference activity type id
   *
   * @return mixed
   * @access public
   */
  public function getDataDifferenceActivityTypeId() {
    return $this->_dataDifferenceActivityTypeId;
  }

  /**
   * Getter for import settings
   *
   * @return array
   * @access public
   */
  public function getImportSettings() {
    return $this->_importSettings;
  }

  /**
   * This method offers translation of strings, such as
   *  - activity subjects
   *  - ...
   *
   * @param string $string
   * @return string
   * @access public
   */
  public function translate($string) {
    if (isset($this->_translatedStrings[$string])) {
      return $this->_translatedStrings[$string];
    } else {
      return ts($string);
    }
  }

  /**
   * Method to retrieve import file location
   *
   * @return string
   * @access public
   */
  public function getImportFileLocation() {
    return $this->_importSettings['import_location']['value'];
  }

  /**
   * Method to retrieve location for processed file
   *
   * @return string
   * @access public
   */
  public function getProcessedFileLocation() {
    return $this->_importSettings['processed_location']['value'];
  }

  /**
   * Method to retrieve location for files where processing has failed
   *
   * @return string
   * @access public
   */
  public function getFailFileLocation() {
    return $this->_importSettings['failed_location']['value'];
  }

  /**
   * Method to get the contact handling the data difference activities
   * (assignee of activities)
   *
   * @return integer
   * @access public
   */
  public function getDifferenceEmployeeContactID() {
    return $this->_importSettings['difference_assignee_id']['value'];
  }

  /**
   * Singleton method
   *
   * @param string $context to determine if triggered from install hook
   * @return CRM_Akademiesync_Config
   * @access public
   * @static
   */
  public static function singleton($context = null) {
    if (!self::$_singleton) {
      self::$_singleton = new CRM_Akademiesync_Config($context);
    }
    return self::$_singleton;
  }

  /**
   * Method to save the import settings
   *
   * @param array $params
   * @throws Exception when json file could not be opened
   * @access public
   */
  public function saveImportSettings($params) {
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        if (isset($this->_importSettings[$key])) {
          $this->_importSettings[$key]['value'] = $value;
        }
      }
      $fileName = $this->_resourcesPath . 'import_settings.json';
      try {
        $fh = fopen($fileName, 'w');
        fwrite($fh, json_encode($this->_importSettings, JSON_PRETTY_PRINT));
        fclose($fh);
      } catch (Exception $ex) {
        throw new Exception($this->translate('Could not open import_settings.json in').' '.__METHOD__
          .', '.$this->translate('contact your system administrator').'.'.$this->translate('Error reported:').' '
          . $ex->getMessage());
      }
    }
  }

  /**
   * Method to set the Import Settings property
   *
   * @throws Exception when file not found
   * @access protected
   */
  protected function setImportSettings() {
    $jsonFile = $this->_resourcesPath.'import_settings.json';
    if (!file_exists($jsonFile)) {
      throw new Exception($this->translate('Could not load import_settings configuration file for extension').', '
        .$this->translate('contact your system administrator'));
    }
    $importSettingsJson = file_get_contents($jsonFile);
    $this->_importSettings = json_decode($importSettingsJson, true);
  }

  /**
   * Protected function to load translation json based on local language
   *
   * @access protected
   */
  protected function setTranslationFile() {
    $config = CRM_Core_Config::singleton();
    $jsonFile = $this->_resourcesPath.$config->lcMessages.'_translate.json';
    if (file_exists($jsonFile)) {
      $translateJson = file_get_contents($jsonFile);
      $this->_translatedStrings = json_decode($translateJson, true);
    } else {
      $this->_translatedStrings = array();
    }
  }
}
