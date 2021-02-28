<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wiryio extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'wiryio';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.1';
        $this->author = 'Wiry.io';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Wiry.io - Acquire and delight customers');
        $this->description = $this->l('Privacy-friendly live chat, popups and web analytics without cookies. Compliant with GDPR, CCPA and other privacy regulations.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayBeforeBodyClosingTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WIRYIO_ACCOUNT_ID');
        Configuration::deleteByName('WIRYIO_VERSION');
        Configuration::deleteByName('WIRYIO_DOMAIN');
        Configuration::deleteByName('WIRYIO_EXTRAS');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitWiryioModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitWiryioModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter your Account ID (17 characters)'),
                        'name' => 'WIRYIO_ACCOUNT_ID',
                        'label' => $this->l('Account ID'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Default: 1.0'),
                        'name' => 'WIRYIO_VERSION',
                        'label' => $this->l('Script version'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Change only if you\'re using a custom domain name'),
                        'name' => 'WIRYIO_DOMAIN',
                        'label' => $this->l('Custom domain name'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'textarea',
                        'desc' => $this->l('Expert configuration (JSON)'),
                        'name' => 'WIRYIO_EXTRAS',
                        'label' => $this->l('Expert configuration'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'WIRYIO_ACCOUNT_ID' => Configuration::get('WIRYIO_ACCOUNT_ID', ''),
            'WIRYIO_VERSION' => Configuration::get('WIRYIO_VERSION', ''),
            'WIRYIO_DOMAIN' => Configuration::get('WIRYIO_DOMAIN', ''),
            'WIRYIO_EXTRAS' => Configuration::get('WIRYIO_EXTRAS', ''),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add JS snippet before </body>
     */
    public function hookDisplayBeforeBodyClosingTag()
    {
		if (Configuration::get('WIRYIO_ACCOUNT_ID'))
		{
            $account_id = Tools::safeOutput(Configuration::get('WIRYIO_ACCOUNT_ID'));
            $version = Tools::safeOutput(Configuration::get('WIRYIO_VERSION'));
            $domain = Tools::safeOutput(Configuration::get('WIRYIO_DOMAIN'));
            $user_extra = Tools::safeOutput(Configuration::get('WIRYIO_EXTRAS'));
			$extras = (object) array();
			if ($user_extra) {
				$extras = (object) array_merge((array) $extras, (array) json_decode($user_extra));
			}
			if (!$domain) {
				$domain = "gateway.wiryio.com";
			}
			if (!$version) {
				$version = "1.0";
			}
			$json_extras = urlencode(json_encode($extras));
            return "
                <!-- Wiry.io Plugin v{$this->version} -->
                <script
                    async
                    src=\"https://{$domain}/script/{$version}/{$account_id}.js\"
                    data-options=\"{$json_extras}\"
                ></script>
                <!-- / Wiry.io Plugin -->
            ";
		}
    }
}
