<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @property State $object
 */
class AdminStatesControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'state';
        $this->className = 'State';
        $this->lang = false;
        $this->requiredDatabase = true;

        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = array(
            'delete' => array('text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')),
            'AffectZone' => array('text' => $this->l('Assign to a new zone'))
        );

        $this->_select = 'z.`name` AS zone, cl.`name` AS country';
        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'zone` z ON (z.`id_zone` = a.`id_zone`)
		LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.id_lang = '.(int)$this->context->language->id.')';
        $this->_use_found_rows = false;

        $countries_array = $zones_array = array();
        $this->zones = Zone::getZones();
        $this->countries = Country::getCountries($this->context->language->id, false, true, false);
        foreach ($this->zones as $zone) {
            $zones_array[$zone['id_zone']] = $zone['name'];
        }
        foreach ($this->countries as $country) {
            $countries_array[$country['id_country']] = $country['name'];
        }

        $this->fields_list = array(
            'id_state' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->trans('Name', array(), 'Admin.Global'),
                'filter_key' => 'a!name'
            ),
            'iso_code' => array(
                'title' => $this->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'zone' => array(
                'title' => $this->trans('Zone', array(), 'Admin.Global'),
                'type' => 'select',
                'list' => $zones_array,
                'filter_key' => 'z!id_zone',
                'filter_type' => 'int',
                'order_key' => 'zone'
            ),
            'country' => array(
                'title' => $this->trans('Country', array(), 'Admin.Global'),
                'type' => 'select',
                'list' => $countries_array,
                'filter_key' => 'cl!id_country',
                'filter_type' => 'int',
                'order_key' => 'country'
            ),
            'active' => array(
                'title' => $this->trans('Enabled', array(), 'Admin.Global'),
                'active' => 'status',
                'filter_key' => 'a!active',
                'align' => 'center',
                'type' => 'bool',
                'orderby' => false,
                'class' => 'fixed-width-sm'
            )
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_state'] = array(
                'href' => self::$currentIndex.'&addstate&token='.$this->token,
                'desc' => $this->l('Add new state', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->tpl_list_vars['zones'] = Zone::getZones();
        $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        $this->tpl_list_vars['POST'] = $_POST;

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('States'),
                'icon' => 'icon-globe'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Name', array(), 'Admin.Global'),
                    'name' => 'name',
                    'maxlength' => 32,
                    'required' => true,
                    'hint' => $this->l('Provide the State name to be display in addresses and on invoices.')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('ISO code'),
                    'name' => 'iso_code',
                    'maxlength' => 7,
                    'required' => true,
                    'class' => 'uppercase',
                    'hint' => $this->l('1 to 4 letter ISO code.').' '.$this->l('You can prefix it with the country ISO code if needed.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Country', array(), 'Admin.Global'),
                    'name' => 'id_country',
                    'required' => true,
                    'default_value' => (int)$this->context->country->id,
                    'options' => array(
                        'query' => Country::getCountries($this->context->language->id, false, true),
                        'id' => 'id_country',
                        'name' => 'name',
                    ),
                    'hint' => $this->l('Country where the state is located.').' '.$this->l('Only the countries with the option "contains states" enabled are displayed.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Zone', array(), 'Admin.Global'),
                    'name' => 'id_zone',
                    'required' => true,
                    'options' => array(
                        'query' => Zone::getZones(),
                        'id' => 'id_zone',
                        'name' => 'name'
                    ),
                    'hint' => array(
                        $this->l('Geographical region where this state is located.'),
                        $this->l('Used for shipping')
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'active',
                    'required' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->trans('Enabled', array(), 'Admin.Global').'" title="'.$this->trans('Enabled', array(), 'Admin.Global').'" />'
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->trans('Disabled', array(), 'Admin.Global').'" title="'.$this->trans('Disabled', array(), 'Admin.Global').'" />'
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit($this->table.'Orderby') || Tools::isSubmit($this->table.'Orderway')) {
            $this->filter = true;
        }

        // Idiot-proof controls
        if (!Tools::getValue('id_'.$this->table)) {
            if (Validate::isStateIsoCode(Tools::getValue('iso_code')) && State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }
        } elseif (Validate::isStateIsoCode(Tools::getValue('iso_code'))) {
            $id_state = State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'));
            if ($id_state && $id_state != Tools::getValue('id_'.$this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }
        }

        /* Delete state */
        if (Tools::isSubmit('delete'.$this->table)) {
            if ($this->access('delete')) {
                if (Validate::isLoadedObject($object = $this->loadObject())) {
                    /** @var State $object */
                    if (!$object->isUsed()) {
                        if ($object->delete()) {
                            Tools::redirectAdmin(self::$currentIndex.'&conf=1&token='.(Tools::getValue('token') ? Tools::getValue('token') : $this->token));
                        }
                        $this->errors[] = Tools::displayError('An error occurred during deletion.');
                    } else {
                        $this->errors[] = Tools::displayError('This state was used in at least one address. It cannot be removed.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        }

        if (!count($this->errors)) {
            parent::postProcess();
        }
    }

    protected function displayAjaxStates()
    {
        $states = Db::getInstance()->executeS('
		SELECT s.id_state, s.name
		FROM '._DB_PREFIX_.'state s
		LEFT JOIN '._DB_PREFIX_.'country c ON (s.`id_country` = c.`id_country`)
		WHERE s.id_country = '.(int)(Tools::getValue('id_country')).' AND s.active = 1 AND c.`contains_states` = 1
		ORDER BY s.`name` ASC');

        if (is_array($states) && !empty($states)) {
            $list = '';
            if ((bool)Tools::getValue('no_empty') != true) {
                $empty_value = (Tools::isSubmit('empty_value')) ? Tools::getValue('empty_value') : '-';
                $list = '<option value="0">'.Tools::htmlentitiesUTF8($empty_value).'</option>'."\n";
            }

            foreach ($states as $state) {
                $list .= '<option value="'.(int)($state['id_state']).'"'.((isset($_GET['id_state']) and $_GET['id_state'] == $state['id_state']) ? ' selected="selected"' : '').'>'.$state['name'].'</option>'."\n";
            }
        } else {
            $list = 'false';
        }

        die($list);
    }

    /**
     * Allow the assignation of zone only if the form is displayed.
     */
    protected function processBulkAffectZone()
    {
        $zone_to_affect = Tools::getValue('zone_to_affect');
        if ($zone_to_affect && $zone_to_affect !== 0) {
            parent::processBulkAffectZone();
        }

        if (Tools::getIsset('submitBulkAffectZonestate')) {
            $this->tpl_list_vars['assign_zone'] = true;
        }

        return;
    }
}
