<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage FilterFromTo
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Attribute\IAttribute;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\FromTo;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\FrontendIntegration\FrontendFilterOptions;

/**
 * Filter "value from x to y" for FE-filtering, based on filters by the meta models team.
 *
 * @package       MetaModels
 * @subpackage FilterFromTo
 * @author     Christian de la Haye <service@delahaye.de>
 */
abstract class AbstractFromTo extends Simple
{
    /**
     * Format the value for use in SQL.
     *
     * @param mixed $value The value to format.
     *
     * @return string
     */
    abstract protected function formatValue($value);

    /**
     * Create the rule to perform from to filtering on.
     *
     * @param IAttribute $attribute The attribute to filter on.
     *
     * @return FromTo
     */
    abstract protected function buildFromToRule($attribute);

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return ($strParamName = $this->getParamName()) ? array($strParamName) : array();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterNames()
    {
        if ($this->get('label')) {
            return array($this->getParamName() => $this->get('label'));
        }

        return array(
            $this->getParamName() => $this->getMetaModel()->getAttributeById($this->get('attr_id'))->getName()
        );
    }

    /**
     * Retrieve the parameter value from the filter url.
     *
     * @param array $filterUrl The filter url from which to extract the parameter.
     *
     * @return null|string|array
     */
    protected function getParameterValue($filterUrl)
    {
        $parameterName = $this->getParamName();
        if (isset($filterUrl[$parameterName]) && !empty($filterUrl[$parameterName])) {
            if (is_array($filterUrl[$parameterName])) {
                return array_values(array_filter($filterUrl[$parameterName]));
            }

            return array_values(array_filter(explode('__', $filterUrl[$parameterName])));
        }

        return null;
    }

    /**
     * Retrieve the attribute name that is referenced in this filter setting.
     *
     * @return array
     */
    public function getReferencedAttributes()
    {
        $objAttribute = null;
        if (!($this->get('attr_id')
            && ($objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'))))) {
            return array();
        }

        return $objAttribute ? array($objAttribute->getColName()) : array();
    }

    /**
     * {@inheritdoc}
     */
    protected function getParamName()
    {
        if ($this->get('urlparam')) {
            return $this->get('urlparam');
        }

        $objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));
        if ($objAttribute) {
            return $objAttribute->getColName();
        }

        return null;
    }

    /**
     * Add param filter to global list.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function registerFilterParameter()
    {
        $GLOBALS['MM_FILTER_PARAMS'][] = $this->getParamName();
    }

    /**
     * Prepare the widget label.
     *
     * @param IAttribute $objAttribute The metamodel attribute.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function prepareWidgetLabel($objAttribute)
    {
        $arrLabel = array(
            ($this->get('label') ? $this->get('label') : $objAttribute->getName()),
            'GET: ' . $this->getParamName()
        );

        if ($this->get('fromfield') && $this->get('tofield')) {
            $arrLabel[0] .= ' ' . $GLOBALS['TL_LANG']['metamodels_frontendfilter']['fromto'];
        } elseif ($this->get('fromfield') && !$this->get('tofield')) {
            $arrLabel[0] .= ' ' . $GLOBALS['TL_LANG']['metamodels_frontendfilter']['from'];
        } else {
            $arrLabel[0] .= ' ' . $GLOBALS['TL_LANG']['metamodels_frontendfilter']['to'];
        }

        return $arrLabel;
    }

    /**
     * Prepare options for the widget.
     *
     * @param array      $arrIds       List of ids.
     * @param IAttribute $objAttribute The metamodel attribute.
     *
     * @return array
     */
    protected function prepareWidgetOptions($arrIds, $objAttribute)
    {
        $arrOptions = $objAttribute->getFilterOptions(
            ($this->get('onlypossible') ? $arrIds : null),
            (bool) $this->get('onlyused')
        );

        // Remove empty values from list.
        foreach ($arrOptions as $mixKeyOption => $mixOption) {
            // Remove html/php tags.
            $mixOption = strip_tags($mixOption);
            $mixOption = trim($mixOption);

            if ($mixOption === '' || $mixOption === null) {
                unset($arrOptions[$mixKeyOption]);
            }
        }

        return $arrOptions;
    }

    /**
     * Prepare the widget Param and filter url.
     *
     * @param array $arrFilterUrl The filter url.
     *
     * @return array
     */
    protected function prepareWidgetParamAndFilterUrl($arrFilterUrl)
    {
        // Split up our param so the widgets can use it again.
        $parameterName    = $this->getParamName();
        $privateFilterUrl = $arrFilterUrl;
        $parameterValue   = null;

        // If we have a value, we have to explode it by double underscore to have a valid value which the active checks
        // may cope with.
        if (array_key_exists($parameterName, $arrFilterUrl) && !empty($arrFilterUrl[$parameterName])) {
            if (is_array($arrFilterUrl[$parameterName])) {
                $parameterValue = $arrFilterUrl[$parameterName];
            } else {
                $parameterValue = explode('__', $arrFilterUrl[$parameterName], 2);
            }

            if ($parameterValue && ($parameterValue[0] || $parameterValue[1])) {
                $privateFilterUrl[$parameterName] = $parameterValue;

                return array($privateFilterUrl, $parameterValue);
            } else {
                // No values given, clear the array.
                $parameterValue = null;

                return array($privateFilterUrl, $parameterValue);
            }
        }

        return array($privateFilterUrl, $parameterValue);
    }

    /**
     * Get the parameter array for configuring the widget.
     *
     * @param IAttribute $attribute    The attribute.
     *
     * @param array      $currentValue The current value.
     *
     * @param string[]   $ids          The list of ids.
     *
     * @return array
     */
    protected function getFilterWidgetParameters(IAttribute $attribute, $currentValue, $ids)
    {
        return array(
            'label'         => $this->prepareWidgetLabel($attribute),
            'inputType'     => 'multitext',
            'options'       => $this->prepareWidgetOptions($ids, $attribute),
            'timetype'      => $this->get('timetype'),
            'dateformat'    => $this->get('dateformat'),
            'eval'          => array(
                'multiple'  => true,
                'size'      => ($this->get('fromfield') && $this->get('tofield') ? 2 : 1),
                'urlparam'  => $this->getParamName(),
                'template'  => $this->get('template'),
                'colname'   => $attribute->getColname(),
            ),
            // We need to implode to have it transported correctly in the frontend filter.
            'urlvalue'      => !empty($currentValue) ? implode('__', $currentValue) : ''
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterWidgets(
        $arrIds,
        $arrFilterUrl,
        $arrJumpTo,
        FrontendFilterOptions $objFrontendFilterOptions
    ) {
        $objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));
        if (!$objAttribute) {
            return array();
        }

        list($privateFilterUrl, $currentValue) = $this->prepareWidgetParamAndFilterUrl($arrFilterUrl);

        $this->registerFilterParameter();

        return array(
            $this->getParamName() => $this->prepareFrontendFilterWidget(
                $this->getFilterWidgetParameters($objAttribute, $currentValue, $arrIds),
                $privateFilterUrl,
                $arrJumpTo,
                $objFrontendFilterOptions
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LengthException If not both fields are allowed.
     */
    public function prepareRules(IFilter $objFilter, $arrFilterUrl)
    {
        // Check if we can filter on anything.
        if (!$this->get('fromfield') && !$this->get('tofield')) {
            $objFilter->addFilterRule(new StaticIdList(null));

            return;
        }

        $attribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));

        if (!($attribute)) {
            $objFilter->addFilterRule(new StaticIdList(null));

            return;
        }

        $value = $this->getParameterValue($arrFilterUrl);
        // No filter values, get out.
        if (empty($value)) {
            $objFilter->addFilterRule(new StaticIdList(null));

            return;
        }

        $rule = $this->buildFromToRule($attribute);
        $objFilter->addFilterRule($rule);

        if (count($value) == 2) {
            // Two values, apply filtering for a value range if both fields are allowed.
            if (!($this->get('fromfield') && $this->get('tofield'))) {
                throw new \LengthException('Only one value is allowed.');
            }

            $rule
                ->setLowerBound($this->formatValue($value[0]), $this->get('moreequal'))
                ->setUpperBound($this->formatValue($value[1]), $this->get('lessequal'));

            return;
        }

        // Only one value, decide which one to use.
        if ($this->get('fromfield')) {
            $rule->setLowerBound($this->formatValue($value[0]), $this->get('moreequal'));

            return;
        }
        $rule->setUpperBound($this->formatValue($value[0]), $this->get('lessequal'));
    }
}
