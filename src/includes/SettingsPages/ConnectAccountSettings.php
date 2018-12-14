<?php

namespace MailjetPlugin\Includes\SettingsPages;

use MailjetPlugin\Admin\Partials\MailjetAdminDisplay;
use MailjetPlugin\Includes\MailjetApi;
use MailjetPlugin\Includes\Mailjeti18n;
use MailjetPlugin\Includes\MailjetLogger;
use MailjetPlugin\Includes\MailjetMail;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Mailjet
 * @subpackage Mailjet/includes
 * @author     Your Name <email@example.com>
 */
class ConnectAccountSettings
{

    public function mailjet_section_connect_account_cb($args)
    {
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>">
        <?php _e('In your Mailjet account, go to <a class="greenLink" target="_blank" href="https://app.mailjet.com/account/api_keys">My Account > API Keys</a> and paste your credentials bellow', 'mailjet'); ?>
        </p>
        <?php
    }

    public function mailjet_connect_account_cb($args)
    {
        // get the value of the setting we've registered with register_setting()
        $mailjetApikey = get_option('mailjet_apikey');
        $mailjetApiSecret = get_option('mailjet_apisecret');

        // output the field
        ?>
        <fieldset class="settingsConnectFldset">
            <input name="settings_step" type="hidden" id="settings_step" value="initial_step">

            <label class="mj-label" for="mailjet_apikey"><?php _e('<b>Api Key</b>', 'mailjet'); ?></label>
            <input name="mailjet_apikey" type="text" id="mailjet_apikey" value="<?php echo $mailjetApikey ?>" class="regular-text code" required="required" placeholder="<?php esc_html_e('Your Mailjet API Key', 'mailjet'); ?>">

            <label class="mj-label" for="mailjet_apisecret"><?php _e('<b>Secret Key</b>', 'mailjet'); ?></label>
            <input name="mailjet_apisecret" type="text" id="mailjet_apisecret" value="<?php echo $mailjetApiSecret ?>" class="regular-text code" required="required" placeholder="<?php esc_html_e('Your Mailjet API Secret', 'mailjet'); ?>">
        </fieldset>
        <?php
    }

    /**
     * top level menu:
     * callback functions
     */
    public function mailjet_connect_account_page_html()
    {
        global $phpmailer;

        // check user capabilities
        if (!current_user_can('manage_options')) {
            MailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Current user don\'t have \`manage_options\` permission ]');
            return;
        }


        // register a new section in the "mailjet" page
        add_settings_section(
                'mailjet_section_connect_account_settings', null, array($this, 'mailjet_section_connect_account_cb'), 'mailjet_connect_account_page'
        );

        // register a new field in the "mailjet_section_developers" section, inside the "mailjet" page
        add_settings_field(
                'mailjet_connect_account_settings', // as of WP 4.6 this value is used only internally
                // use $args' label_for to populate the id inside the callback
                __('Mailjet API credentials', 'mailjet'), array($this, 'mailjet_connect_account_cb'), 'mailjet_connect_account_page', 'mailjet_section_connect_account_settings', [
            'label_for' => 'mailjet_connect_account_settings',
            'class' => 'mailjet_row',
            'mailjet_custom_data' => 'custom',
                ]
        );


        // add error/update messages
        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {

            // Validate Mailjet API credentials
            $isValidAPICredentials = MailjetApi::isValidAPICredentials();
            if (false == $isValidAPICredentials) {
//                ailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Invalid Mailjet API credentials ]');
                add_settings_error('mailjet_messages', 'mailjet_message', __('Please make sure that you are using the correct API key and Secret key associated to your Mailjet account: <a href="https://app.mailjet.com/account/api_keys">https://app.mailjet.com/account/api_keys</a>', 'mailjet'), 'error');
            } else {
//            MailjetLogger::info('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Initial settings form submitted ]');
                // Initialize PhpMailer
                //
                if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
                    require_once ABSPATH . WPINC . '/class-phpmailer.php';
                    require_once ABSPATH . WPINC . '/class-smtp.php';
                    $phpmailer = new \PHPMailer();
//                MailjetLogger::warning('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ PHPMailer initialized by the Mailjet plugin ]');
                }

                // Update From Email and Name
                add_filter('wp_mail_from', array(new MailjetMail(), 'wp_sender_email'));
                add_filter('wp_mail_from_name', array(new MailjetMail(), 'wp_sender_name'));

                // add settings saved message with the class of "updated"
                add_settings_error('mailjet_messages', 'mailjet_message', __('Settings Saved', 'mailjet'), 'updated');
//            MailjetLogger::info('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Initial settings saved successfully ]');
            }
        }

        // show error/update messages
        settings_errors('mailjet_messages');
        ?>

        <div class="mj-pluginPage">
            <div id="initialSettingsHead"><img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/admin/images/LogoMJ_White_RVB.svg'; ?>" alt="Mailjet Logo" /></div>
            <div class="mainContainer">
            
                <div class="backToDashboard">
                    <a class="mj-btn btnCancel" href="admin.php?page=mailjet_dashboard_page">
                    <svg width="8" height="8" viewBox="0 0 16 16"><path d="M7.89 11.047L4.933 7.881H16V5.119H4.934l2.955-3.166L6.067 0 0 6.5 6.067 13z"/></svg>
                    <?php _e('Back to dashboard', 'mailjet') ?>
                    </a>
                </div>

                <h1 class="page_top_title"><?php _e('Settings', 'mailjet') ?></h1>
                <div class="mjSettings">
                    <div class="left">
        <?php
        MailjetAdminDisplay::getSettingsLeftMenu();
        ?>
                    </div>

                    <div class="right">
                        <div class="centered">
                            <!--                    <h1>--><?php //echo esc_html(get_admin_page_title());  ?><!--</h1>-->
                            <h2 class="section_inner_title"><?php echo __('Connect your Mailjet account', 'mailjet'); ?></h2>
                            <form action="options.php" method="post">
                        <?php
                        // output security fields for the registered setting "mailjet"
                        settings_fields('mailjet_connect_account_page');
                        // output setting sections and their fields
                        // (sections are registered for "mailjet", each field is registered to a specific section)
                        do_settings_sections('mailjet_connect_account_page');
                        // output save settings button
                        $saveButton = __('Save', 'mailjet');
                        submit_button($saveButton, 'mj-btn btnPrimary MailjetSubmit', 'submit', false, array('id' => 'connectAccountSubmit'));
                        ?>
                                <!-- <input name="cancelBtn" class="mj-btn btnCancel" type="button" id="cancelBtn" onClick="location.href = location.href" value="<?= __('Cancel', 'mailjet') ?>"> -->
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bottom_links">
                <div class="needHelpDiv">
                    <img src=" <?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/admin/images/need_help.png'; ?>" alt="<?php echo __('Need help?', 'mailjet'); ?>" />
        <?php echo __('Need help?', 'mailjet'); ?>
                </div>
        <?php echo '<a target="_blank" href="' . Mailjeti18n::getMailjetUserGuideLinkByLocale() . '">' . __('Read our user guide', 'mailjet') . '</a>'; ?>
        <?php echo '<a target="_blank" href="' . Mailjeti18n::getMailjetSupportLinkByLocale() . '">' . __('Contact our support team', 'mailjet') . '</a>'; ?>
            </div>
        </div>

        <?php
    }

}
