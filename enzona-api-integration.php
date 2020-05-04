<?php
/*
Plugin Name: Enzona Api Integration
Version: 0.0.1
Description: Integrates enzona api to make arbitrary payments.
Author: Luisito el pulpito
Author URI: https://dofleini.com

*/


if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . 'enzonaApi.php';


/* Settings page */
// create custom plugin settings menu
add_action('admin_menu', 'enzona_api_integration_menu_option');
function enzona_api_integration_menu_option() {

  add_menu_page('Enzona API', 'Enzona API', 'administrator', 'ezai_admin', 'ezai_config_page', 'dashicons-admin-network');
  add_action('admin_init', 'register_eai_plugin_settings');
}


function register_eai_plugin_settings() {
  //register our settings
  register_setting('ezai-settings-group', 'ezai_mode');
  register_setting('ezai-settings-group', 'ezai_customer_key');
  register_setting('ezai-settings-group', 'ezai_customer_secret');
  register_setting('ezai-settings-group', 'ezai_success_url');
  register_setting('ezai-settings-group', 'ezai_cancel_url');
  register_setting('ezai-settings-group', 'ezai_error_url');
}

function ezai_config_page() {
  wp_enqueue_script('ezai-api', plugin_dir_url(__FILE__) . '/enzona_api_integration.js', ['jquery']);

  ?>
    <div class="wrap">
        <h1> Enzona Api Integration - Credentials</h1>
        <form method="post" action="options.php">
          <?php settings_fields('ezai-settings-group'); ?>
          <?php do_settings_sections('ezai-settings-group');
          $selected_mode = get_option('ezai_mode');
          ?>
            <table class="form-table">
                <tr valign="top">
                    <td style="width: auto" scope="row"> Mode</td>
                    <td>
                        <select name="ezai_mode">
                            <option value="1" <?php if ($selected_mode == 1) {
                              echo "selected";
                            } ?> > Sandbox
                            </option>
                            <option value="2" <?php if ($selected_mode == 2) {
                              echo "selected";
                            } ?> > Production
                            </option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"> Customer key</td>
                    <td><input type="text" name="ezai_customer_key"
                               value="<?php echo esc_attr(get_option('ezai_customer_key')); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"> Customer secret</td>
                    <td><input type="text" name="ezai_customer_secret"
                               value="<?php echo esc_attr(get_option('ezai_customer_secret')); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <button class="generate-access-token-saved"> Test
                            connection
                        </button>
                    </th>
                    <td>
                        <div class="generated_token">

                        </div>
                    </td>
                </tr>
                <input type="hidden"
                       value="<?php echo admin_url('admin-ajax.php'); ?>"
                       name="ajax_url" class="ajax_url_value"/>
            </table>

            <fieldset>
                <h3> Payment API stuff</h3>

                <table>
                    <tr>
                        <td> Success URL</td>
                        <td> <?php echo get_home_url(); ?> <input type="text"
                                                                  name="ezai_success_url"
                                                                  value="<?php echo esc_attr(get_option('ezai_success_url')); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td> Cancel URL</td>
                        <td><?php echo get_home_url(); ?> <input type="text"
                                                                 name="ezai_cancel_url"
                                                                 value="<?php echo esc_attr(get_option('ezai_cancel_url')); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td> Error URL</td>
                        <td>
                          <?php echo get_home_url(); ?> <input type="text"
                                                               name="ezai_error_url"
                                                               value="<?php echo esc_attr(get_option('ezai_error_url')); ?>"/>
                        </td>
                    </tr>
                </table>

            </fieldset>
          <?php submit_button(); ?>

        </form>
    </div>
<?php }


add_action('wp_ajax_ezai_get_token', 'ezai_get_token_js');

function ezai_get_token_js() {
  $api = new enzonaApi(get_option("ezai_customer_key"), get_option("ezai_customer_secret"));
  $token = $api->requestAccessToken();
  $ob = json_decode($token);
  if ($ob->access_token == "error") {
    echo "Error: " . $ob->message;
  }
  else {
    echo "Connection succeed. Token: " . $ob->access_token;
  }

  wp_die();
}

function ezai_generate_payment_info($amount, $descripcion, $items) {
  $api = new enzonaApi(get_option("ezai_customer_key"), get_option("ezai_customer_secret"));
  $return_url = get_home_url() . get_option("ezai_success_url");
  $cancel_url = get_home_url() . get_option("ezai_cancel_url");
  $error_url = get_home_url() . get_option("ezai_error_url");
  $token = $api->requestAccessToken();
  $ob = json_decode($token);
  $pago = $api->generatePayment($ob->access_token, $amount, $descripcion, $items, $return_url, $cancel_url);


  if ($pago["status"] == "ok") {
    $paymentObj = json_decode($pago["message"]);
    $redirect = '';
    foreach ($paymentObj->links as $l) {
      if ($l->method == "REDIRECT") {
        $redirect = $l->href;
      }
    }
    return [
      "status" => $pago["status"],
      "url" => $redirect,
      "uuid" => $paymentObj->transaction_uuid,
      "data" => $pago["message"],
    ];
  }
  else {
    return ["status" => $pago["status"],"url" => $error_url];
  }
}

function ezai_confirm_payment_info($transaction_uuid) {
  $api = new enzonaApi(get_option("ezai_customer_key"), get_option("ezai_customer_secret"));
  $token = $api->requestAccessToken();
  $ob = json_decode($token);
  $pago = $api->acceptPayment($ob->access_token, $transaction_uuid);
  if ($pago["status"] == "ok") {
    return TRUE;
  }
  return FALSE;
}