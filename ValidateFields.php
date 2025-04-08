<?php
/*
* A validator class for a roster upload process.
*
* Client: big learning website.
*/

namespace EnterpriseServiceLayer\RosterProcess;

// ---------------------------------------------------------------------------->
/**
 * Validates roster CSV fields
 *
 * @category EnterpriseServiceLayer
 *
 * @package RosterProcess
 *
 * @subpackage BusinessObject
 *
 * @version $Id$
 *
 * @author Victor Abadio <EDITED>
 *
 */
class ValidateFields
{
    // ------------------------------------------------------------------------>
    /**
     * This constructor is placed here for compliance with convention.
     */
    public function __construct()
    {
    }
    // ------------------------------------------------------------------------>
    /**
     * Returns validation information about each roster field
     *
     * @param array  $arg_fields
     *               A list of fields to be validated.
     *
     * @param int    $arg_roster_process_id
     *               The id of the roster process.
     *
     * @param string $arg_roster_type
     *               The type of the roster uploaded file.
     *
     * @return array Associative array with errors and warnings:
     *               array(
     *                   "errors" => array(
     *                       array(
     *                           "field" => "school_name",
     *                           "error_message" => "name_missing"
     *                       ),
     *                       array(
     *                           "field" => "school_id",
     *                           "error_message" => "schoold_id_not_unique"
     *                       )
     *                   ),
     *                   "warnings" => array(
     *                       array(
     *                           "field" => "principal_email",
     *                           "error_message" => "principal_email_invalid_email"
     *                       )
     *                   )
     *               );
     */
    public function init(
        $arg_input,
        $arg_filter_rules,
        $arg_required_inputs = null
    ) {
        $return = false;

        foreach ($arg_input as $field_name => $value) {
            if (!empty($arg_filter_rules[$field_name])) {
                foreach ($arg_filter_rules[$field_name] as $rule_node => $rule) {
                    $result = $this->validateValue($value, $rule);
                    if ($result === false) {
                        $type = $rule['type'];
                        if (empty($type)) {
                            $type = 'errors';
                        }

                        $return[$type][$field_name] = array(
                            "field" => $field_name,
                            "error_message" => $rule['error_message']
                        );
                    }
                }
            }
        }

        if (!empty($arg_required_inputs)) {
            foreach ($arg_required_inputs as $required_field) {
                if (empty($arg_input[$required_field])) {
                    $return['errors'][$required_field] = array(
                        "field" => $required_field,
                        "error_message" => $required_field . "_missing"
                    );
                }
            }
        }

        return $return;
    }
    // ------------------------------------------------------------------------>
    /**
     * Rule based input validatin mechanism for a single input value.
     *
     * @param string $arg_value
     *               A value to validate against the given rule.
     *
     * @param array $arg_rule
     *              A validation rule
     *
     * @return bool FALSE if the input value failes validation, otherwise TRUE.
     */
    public function validateValue($arg_value, $arg_rule)
    {
        $return = true;

        if (!empty($arg_value)) {
            $options = array();
            if (! empty($arg_rule['options'])) {
                $options['options'] = $arg_rule['options'];
            }

            $return = filter_var($arg_value, $arg_rule['rule'], $options);
        }

        return $return;
    }
}
