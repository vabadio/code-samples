<?php
/*
* A simple class for creating a student's contact info.
*
* Client: big learning website.
*/

namespace EnterpriseServiceLayer\StudentContact;

// ---------------------------------------------------------------------------->
use \dt_lib5\ManageInput\ValidateInput as ValidateInput;
use \dt_lib5\ManageInput\ValidateInputException as ValidateInputException;
use \EnterpriseServiceLayer\Student\GetInfo as StudentGetInfo;

// ---------------------------------------------------------------------------->
include_once DT_LIB5 . 'ManageInput/ValidateInput.php';
// ---------------------------------------------------------------------------->
/**
 * The base exception for the Create student contact class.
 *
 * @category EnterpriseServiceLayer
 *
 * @package StudentContact
 *
 * @subpackage BusinessObject
 *
 * @version $Id$
 *
 * @author Victor Abadio <EDITED>
 */
class CreateValidateInputException extends ValidateInputException
{
}
// ---------------------------------------------------------------------------->
/**
 * Creates a student contact entry.
 *
 * @category EnterpriseServiceLayer
 *
 * @package StudentContact
 *
 * @subpackage BusinessObject
 *
 * @version $Id$
 *
 * @author Victor Abadio <EDITED>
 */
class Create
{
    /**
     * Instance of the CreateDal class
     *
     * @var object
     *
     * @see \CoreServiceLayer\StudentContact\CreateDal
     */
    private $objCreateDal;
    // ------------------------------------------------------------------------>
    /**
     * The constructor of this object. Instantiates the create DAL object.
     */
    public function __construct()
    {
        include_once DATA_ACCESS_LIBRARY . 'StudentContact/CreateDal.php';
        $this->objCreateDal = new CreateDal();
    }
    // ------------------------------------------------------------------------>
    /**
     * Create student account
     *
     * @param array $arg_fields
     *              A list with the student contact info.
     *
     * @throws CreateValidateInputException
     *         114138 if data is invalid.
     *
     * @return array The id of the newly created student.
     *         array(
     *              student_id = 1
     *         )
     */
    public function init($arg_fields)
    {
        $this->doValidate($arg_fields);

        $student_contact_info_id = $this->objCreateDal->insert($arg_fields);

        $student_info = array();
        if (!empty($student_contact_info_id)) {
            include_once 'StudentContact/Get.php';
            $objGet = new Get();

            $student_info = $objGet->init(array($student_contact_info_id));
        }

        $return = $student_info;

        return $return;
    }
    // ------------------------------------------------------------------------>
    /**
     * Validates the inputs from user.
     *
     * @param array $arg_inputs
     *              A list of inputs from user.
     *
     * @throws CreateValidateInputException
     *         114138 Invalid inputs.
     *
     * @return null
     */
    private function doValidate($arg_inputs)
    {
        $objValidateInput = ValidateInput::instance();

        $required_fields =  array(
            'student_id',
            'email',
        );

        $validate_int = array(
            'rule_name' => 'validate_int',
            'rule' => FILTER_VALIDATE_INT,
            'error_message' => 'not_an_integer'
        );

        $validate_length_100 = array(
            'rule_name' => 'validate_length',
            'rule' => FILTER_VALIDATE_REGEXP,
            'options' => array(
                'regexp' => "/^.{0,100}$/"
            ),
            'error_message' => 'invalid_length'
        );

        $validate_student_id = array(
            'rule_name' => 'validate_student_id',
            'rule' => FILTER_CALLBACK,
            'options' => array(
                $this,
                'doValidateStudentId'
            ),
            'error_message' => 'student_id_invalid'
        );

        $validate_email = array(
            'rule_name' => 'validate_email',
            'rule' => FILTER_VALIDATE_EMAIL,
            'error_message' => 'email_invalid'
        );

        // Associates validation rules to the fields that will be checked
        $validation_rules = array(
            'student_id' => array(
                $validate_int,
                $validate_student_id
            ),
            'email' => array(
                $validate_email
            ),
            'name' => array(
                $validate_length_100
            ),
            'phone' => array(
                $validate_length_100
            ),
            'type' => array(
                $validate_length_100
            )
        );

        $input_errors = $objValidateInput->validateInputArray(
            $arg_inputs,
            $validation_rules,
            $required_fields
        );

        if (! empty($input_errors)) {
            throw new CreateValidateInputException(
                serialize($input_errors),
                114138
            );
        }
    }
    // ------------------------------------------------------------------------>
    /**
     * Validates if the student exists.
     *
     * @param int $arg_student_id
     *            The student's id to be verified.
     *
     * @return boolean true if id is valid and false if not.
     */
    public function doValidateStudentId($arg_student_id)
    {
        $return = true;

        include_once 'Student/GetInfo.php';
        $objStudentGetInfo = new StudentGetInfo();

        $student_info = $objStudentGetInfo->init(
            array($arg_student_id),
            array('id')
        );

        if (empty($student_info['student_ids'])) {
            $return = false;
        }

        return $return;
    }
}
