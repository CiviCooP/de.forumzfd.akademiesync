<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 * Used to show and save import settings for de.forumzfd.akademiesync
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 1 March 2017
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Akademiesync_Form_ImportSettings extends CRM_Core_Form {

  private $_importSettings = array();

  /**
   * Overridden parent method to build the form
   *
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Akademiesync_Config::singleton();
    $this->_importSettings = $config->getImportSettings();
    $employeeList = $this->getEmployeeList();
    $activityTypeList = $this->getActivityTypeList();

    foreach ($this->_importSettings as $settingName => $settingValues) {
      switch($settingName) {
        case 'difference_activity_type_id':
          $this->add('select', $settingName, $config->translate($settingValues['label']), $activityTypeList);
          break;
        case 'difference_assignee_id':
          $this->add('select', $settingName, $config->translate($settingValues['label']), $employeeList, TRUE);
          break;
        case 'error_assignee_id':
          $this->add('select', $settingName, $config->translate($settingValues['label']), $employeeList, TRUE);
          break;
        default:
          $this->add('text', $settingName, $config->translate($settingValues['label']), array('size' => 50), TRUE);
          break;
      }
    }
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE,),
      array('type' => 'cancel', 'name' => ts('Cancel')),));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Overridden parent method to set default values
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    foreach ($this->_importSettings as $settingName => $settingValues) {
      $defaults[$settingName] = $settingValues['value'];
    }
    return $defaults;
  }

  /**
   * Overridden parent method to deal with processing after succesfull submit
   *
   * @access public
   */
  public function postProcess() {
    $this->saveImportSettings($this->_submitValues);
    $config = CRM_Akademiesync_Config::singleton();
    $userContext = CRM_Core_Session::USER_CONTEXT;
    if (empty($userContext) || $userContext == 'userContext') {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm', '', true));
    }
    CRM_Core_Session::setStatus($config->translate('Forum ZFD Akademie Import Settings saved'), 'Saved', 'success');
  }

  /**
   * Overridden parent method to add validation rules
   *
   * @access public
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Akademiesync_Form_ImportSettings', 'validateImportSettings'));
  }

  /**
   * Function to validate the import settings
   *
   * @param array $fields
   * @return array|bool $errors or TRUE
   * @access public
   * @static
   */
  public static function validateImportSettings($fields) {
    $config = CRM_Akademiesync_Config::singleton();
    $folderElements = array('import_location', 'processed_location', 'failed_location');
    foreach ($folderElements as $folderElement) {
      if (!is_writable($fields[$folderElement])) {
        $errors[$folderElement] = $config->translate('This folder does not exists or you do not have sufficient permissions to write to the folder');
      }
    }
    if (empty($errors)) {
      return TRUE;
    } else {
      return $errors;
    }
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  private function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Method to get ForumZFD employees
   * @return array
   * @throws Exception
   */
  private function getEmployeeList() {
    $employeeList = array();
    $config = CRM_Akademiesync_Config::singleton();
    // todo get default organization and then all active employees
    $defaultOrganizationId = NULL;
    return $employeeList;
  }

  /**
   * Method to get activity types
   *
   * @return array
   */
  private function getActivityTypeList() {
   $activityTypeList = array();
   try {
     $activityTypes = civicrm_api3('OptionValue', 'get', array(
       'option_group_id' => 'activity_type',
       'is_active' => 1,
       'options' => array('limit' => 0)
     ));
     foreach ($activityTypes['values'] as $activityType) {
       $activityTypeList[$activityType['value']] = $activityType['label'];
     }
   } catch (CiviCRM_API3_Exception $ex) {}
   $activityTypeList[0] = '- select -';
   asort($activityTypeList);
   return $activityTypeList;
  }

  /**
   * Method to save the import settings
   *
   * @param array $formValues
   */
  private function saveImportSettings($formValues) {
    $saveValues = array();
    foreach ($formValues as $key => $value) {
      if ($key != 'qfKey' && $key != 'entryURL' && substr($key,0,3) != '_qf') {
        $saveValues[$key] = $value;
      }
    }
    $config = CRM_Akademiesync_Config::singleton();
    $config->saveImportSettings($saveValues);
  }
}
