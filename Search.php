<?php
/*
* A model layer for schools search in the database.
*
* Client: big learning website.
*/

namespace EnterpriseServiceLayer\Institution;

// ---------------------------------------------------------------------------->
/**
 * The base exception for the Search object.
 *
 * @author     Victor Abadio <EDITED>
 */
class SearchInstitutionException extends \Exception
{
}
// ---------------------------------------------------------------------------->
/**
 * Searches for institutions by a search criteria.
 *
 * @category EnterpriseServiceLayer
 *
 * @package Institution
 *
 * @subpackage BusinessObject
 *
 * @version $Id$
 *
 * @author Victor Abadio <EDITED>
 */
class Search
{
    /**
     * Instance of the SearchDal class
     *
     * @var object
     */
    private $objSearchDal;
    // ------------------------------------------------------------------------>
    /**
     * The constructor of this object. Instantiates the create DAL object.
     */
    public function __construct()
    {
        include_once DATA_ACCESS_LIBRARY . 'Institution/SearchDal.php';
        $this->objSearchDal = new SearchDal();
    }
    // ------------------------------------------------------------------------>
    /**
     * Search for institutions by the given search criteria.
     *
     * @param   string $arg_search_criteria
     *          A list with the institution info.
     *
     * @param   array $arg_fields
     *          An array with fields to restrict the search to.
     *
     * @param   int $arg_offset
     *          The offset used for pagination. Defaults to 0.
     *
     * @param   int $arg_limit
     *          The limit used for pagination. Defaults to 100.
     *
     * @param   bool $arg_include_inactive
     *          Whether to include inactive institutions in the search or not.
     *          Defaults to false.
     *
     * @throws  SearchInstitutionException
     *          114120 No search criteria provided.
     *
     * @throws  SearchInstitutionException
     *          114121 Invalid fields format.
     *
     * @throws  SearchInstitutionException
     *          114122 Invalid offset.
     *
     * @throws  SearchInstitutionException
     *          114123 Invalid limit.
     *
     * @return  array A list with the institutions found and their information
     *          array(
     *              "institution_ids" => array(
     *                  62,
     *                  61,
     *                  60
     *              ),
     *              "institution_info" => array(
     *                  "62" => array(
     *                      "id" => 62,
     *                      "country" => "United States",
     *                      "name" => "Test Institution"
     *                  ),
     *                  "61" => array(
     *                      "id" => 61,
     *                      "country" => "China",
     *                      "name" => "International School of Beijing Child",
     *                      "parent_id" => 60
     *                  ),
     *                  "60" => array(
     *                      "id" => 60,
     *                      "country" => "China",
     *                      "name" => "International School of Beijing"
     *                  ),
     *              ),
     *              "institution_total" => 5
     *          )
     */
    public function init(
        $arg_search_criteria,
        $arg_fields,
        $arg_offset = 0,
        $arg_limit = 100,
        $arg_include_inactive = false
    ) {
        $this->doValidate(
            array(
                'search_criteria' => $arg_search_criteria,
                'fields' => $arg_fields,
                'offset' => $arg_offset,
                'limit' => $arg_limit
            )
        );

        $user_type = $_SESSION['user_info']['type'];

        $institution_ids = array();

        if ($user_type != 'SUPER_USER') {
            $institution_ids = $this->getInstitutionIds($arg_fields,  $arg_offset, $arg_limit)['institution_ids'];
        }

        $institutions_found = $this->objSearchDal->select(
            $arg_search_criteria,
            $arg_fields,
            $arg_offset,
            $arg_limit,
            $arg_include_inactive,
            $user_type,
            $institution_ids
        );

        $institution_ids = $institutions_found["institution_ids"];
        $institution_total = $institutions_found["institution_total"];

        if (!empty($institution_ids)) {
            include_once 'Institution/GetInfo.php';
            $objGetInfo = new GetInfo();

            $institution_info = $objGetInfo->init($institution_ids, array("id", "country", "name", "parent_id"));

            $return['institution_ids'] = $institution_ids;
            $return['institution_info'] = $institution_info;
            $return["institution_total"] = $institution_total;
        }

        return $return;
    }
    // ------------------------------------------------------------------------>
    /*
     * Returns all institutions
     *
     * @param array $arg_fields  Fields requested
     *
     * @param int $arg_offset     The offset used for pagination.
     *
     * @param int $arg_limit     The limit used for pagination.
     *
     * @return array             A list of child institution ids with details
     *
     *         array(
     *             'institution_ids' => array(
     *                  5, 4, 2, 1
     *              ),
     *              'institution_total' => 10
     *         )
     *
     */
    private function getInstitutionIds($arg_fields, $arg_offset, $arg_limit)
    {
        // use retrieve object
        include_once 'Institution/Retrieve.php';
        $objInstitutionRetrieve = new Retrieve();

        $institution_ids = $objInstitutionRetrieve->init($arg_fields, $arg_offset, $arg_limit);
        $institution_ids['institution_total'] = count($institution_ids);

        // get children for each
        include_once 'Institution/GetChildren.php';
        $objInstitutionGetChildren = new GetChildren();

        foreach ($institution_ids['institution_ids'] as $institution_id) {
            $children = $objInstitutionGetChildren->init((int)$institution_id, false, array('id', 'name'));

            foreach ($children['institution_ids'] as $i => $child_id) {
                $institution_ids['institution_ids'][] = $child_id;
                $institution_ids['institution_info'][] = $children['institution_info'][$child_id];
            }

            $institution_ids['institution_total'] = $institution_ids['institution_total'] + $children['institution_total'];
        }

        return $institution_ids;
    }
    // ------------------------------------------------------------------------>
    /**
     * Validates the inputs from user.
     *
     * @param array $arg_inputs
     *              A list of inputs from user.
     *
     * @throws SearchInstitutionException
     *         114120 No search criteria provided.
     *
     * @throws SearchInstitutionException
     *         114121 Invalid fields format.
     *
     * @throws SearchInstitutionException
     *         114122 Invalid offset.
     *
     * @throws SearchInstitutionException
     *         114123 Invalid limit.
     *
     * @return null
     */
    private function doValidate($arg_inputs)
    {
        if (empty($arg_inputs['search_criteria'])) {
            throw new SearchInstitutionException(
                'No search criteria provided.',
                114120
            );
        }

        if (!empty($arg_inputs['fields']) && !is_array($arg_inputs['fields'])) {
            throw new SearchInstitutionException(
                'Invalid fields format.',
                114121
            );
        }

        if (!empty($arg_inputs['offset']) && !is_int($arg_inputs['offset'])) {
            throw new SearchInstitutionException(
                'Invalid offset.',
                114122
            );
        }

        if (!empty($arg_inputs['limit']) && !is_int($arg_inputs['limit'])) {
            throw new SearchInstitutionException(
                'Invalid limit.',
                114123
            );
        }
    }
}
