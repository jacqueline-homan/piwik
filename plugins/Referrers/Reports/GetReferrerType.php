<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Referrers\Reports;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Plugins\Referrers\Columns\ReferrerType;

class GetReferrerType extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new ReferrerType();
        $this->name          = Piwik::translate('Referrers_Type');
        $this->documentation = Piwik::translate('Referrers_TypeReportDocumentation') . '<br />'
                             . '<b>' . Piwik::translate('Referrers_DirectEntry') . ':</b> ' . Piwik::translate('Referrers_DirectEntryDocumentation') . '<br />'
                             . '<b>' . Piwik::translate('Referrers_SearchEngines') . ':</b> ' . Piwik::translate('Referrers_SearchEnginesDocumentation',
                                 array('<br />', '&quot;' . Piwik::translate('Referrers_SubmenuSearchEngines') . '&quot;')) . '<br />'
                             . '<b>' . Piwik::translate('Referrers_Websites') . ':</b> ' . Piwik::translate('Referrers_WebsitesDocumentation',
                                 array('<br />', '&quot;' . Piwik::translate('Referrers_SubmenuWebsites') . '&quot;')) . '<br />'
                             . '<b>' . Piwik::translate('Referrers_Campaigns') . ':</b> ' . Piwik::translate('Referrers_CampaignsDocumentation',
                                 array('<br />', '&quot;' . Piwik::translate('Referrers_Campaigns') . '&quot;'));
        $this->constantRowsCount = true;
        $this->hasGoalMetrics = true;
        $this->order = 1;
        $this->widgetTitle = 'General_Overview';
        $this->subCategory = 'General_Overview';
    }

    public function getDefaultTypeViewDataTable()
    {
        return HtmlTable\AllColumns::ID;
    }

    public function getViews()
    {
        return array(
            $this->createView(), // makes a default view
            $this->createEvolutionView($defaultColumns = array('nb_visits')),
            $this->createSparklinesView($apiMethodToGetSparklines = 'Referrers.getSparklines')
        );
    }

    public function configureView(ViewDataTable $view)
    {
        $idSubtable       = Common::getRequestVar('idSubtable', false);
        $labelColumnTitle = $this->name;

        switch ($idSubtable) {
            case Common::REFERRER_TYPE_SEARCH_ENGINE:
                $labelColumnTitle = Piwik::translate('Referrers_ColumnSearchEngine');
                break;
            case Common::REFERRER_TYPE_WEBSITE:
                $labelColumnTitle = Piwik::translate('Referrers_ColumnWebsite');
                break;
            case Common::REFERRER_TYPE_CAMPAIGN:
                $labelColumnTitle = Piwik::translate('Referrers_ColumnCampaign');
                break;
            default:
                break;
        }

        $view->config->show_search = false;
        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;
        $view->config->show_limit_control      = false;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', $labelColumnTitle);

        $view->requestConfig->filter_limit = 10;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_subtable_when_show_goals = true;
        }

        if ($view->isViewDataTableId(Evolution::ID)) {

            $view->config->add_total_row = true;

            // configure displayed columns
            if (empty($columns)) {
                $columns = Common::getRequestVar('columns', false);
                if (false !== $columns) {
                    $columns = Piwik::getArrayFromApiParameter($columns);
                }
            }
            if (false !== $columns) {
                $columns = !is_array($columns) ? array($columns) : $columns;
            }

            if (!empty($columns)) {
                $view->config->columns_to_display = $columns;
            } elseif (empty($view->config->columns_to_display) && !empty($defaultColumns)) {
                $view->config->columns_to_display = $defaultColumns;
            }

            // configure selectable columns
            // todo: should use SettingsPiwik::isUniqueVisitorsEnabled
            if (Common::getRequestVar('period', false) == 'day') {
                $selectable = array('nb_visits', 'nb_uniq_visitors', 'nb_users', 'nb_actions');
            } else {
                $selectable = array('nb_visits', 'nb_actions');
            }
            $view->config->selectable_columns = $selectable;

            // configure displayed rows
            $visibleRows = Common::getRequestVar('rows', false);
            if ($visibleRows !== false) {
                // this happens when the row picker has been used
                $visibleRows = Piwik::getArrayFromApiParameter($visibleRows);

                // typeReferrer is redundant if rows are defined, so make sure it's not used
                $view->config->custom_parameters['typeReferrer'] = false;
            } else {
                // use $typeReferrer as default
                $typeReferrer = Common::getRequestVar('typeReferrer', Common::REFERRER_TYPE_DIRECT_ENTRY);
                $label = Piwik::translate(\Piwik\Plugins\Referrers\getReferrerTypeLabel($typeReferrer));
                $total = Piwik::translate('General_Total');

                if (!empty($view->config->rows_to_display)) {
                    $visibleRows = $view->config->rows_to_display;
                } else {
                    $visibleRows = array($label, $total);
                }

                $view->requestConfig->request_parameters_to_modify['rows'] = $label . ',' . $total;
            }
            $view->config->row_picker_match_rows_by = 'label';
            $view->config->rows_to_display = $visibleRows;

            $view->config->documentation = Piwik::translate('Referrers_EvolutionDocumentation') . '<br />'
                . Piwik::translate('General_BrokenDownReportDocumentation') . '<br />'
                . Piwik::translate('Referrers_EvolutionDocumentationMoreInfo', '&quot;'
                    . Piwik::translate('Referrers_ReferrerTypes') . '&quot;');

        }
    }

}
