<?php

namespace Drupal\portland\Plugin\OpenIDConnectClient;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectStateTokenInterface;
use Drupal\openid_connect\OpenIDConnectAutoDiscover;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Drupal\openid_connect\StateToken;
use Drupal\portland\SecretsReader;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @OpenIDConnectClient(
 *   id = "azure",
 *   label = @Translation("Office 365")
 * )
 */
class Azure extends OpenIDConnectClientBase
{
  /**
   * Overrides OpenIDConnectClientBase::buildConfigurationForm().
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $url = "https://portal.azure.com";
    $form["description"] = [
      "#markup" =>
        '<div class="description">' .
        $this->t(
          'Set up your app in <a href="@url" target="_blank">app registration</a> on your tenant.',
          ["@url" => $url]
        ) .
        "</div>",
    ];

    return $form;
  }

  /**
   * Overrides OpenIDConnectClientBase::getEndpoints().
   */
  public function getEndpoints(): array
  {
    return [
      "authorization" =>
        "https://login.microsoftonline.com/common/oauth2/authorize",
      "token" => "https://login.microsoftonline.com/common/oauth2/token",
      "userinfo" => "https://graph.windows.net/me?api-version=1.6",
    ];
  }

  /**
   * Overrides OpenIDConnectClientBase::getUrlOptions.
   */
  protected function getUrlOptions(
    string $scope,
    GeneratedUrl $redirect_uri
  ): array {
    return [
      "query" => [
        "client_id" => $this->secretsReader->get("azure_client_id"),
        "response_type" => "code",
        "scope" => $scope,
        "redirect_uri" => $redirect_uri->getGeneratedUrl(),
        "state" => $this->stateToken->generateToken(),
      ],
    ];
  }

  /**
   * Overrides OpenIDConnectClientBase::getRequestOptions.
   */
  protected function getRequestOptions(
    string $authorization_code,
    string $redirect_uri
  ): array {
    return [
      "form_params" => [
        "code" => $authorization_code,
        "client_id" => $this->secretsReader->get("azure_client_id"),
        "client_secret" => $this->secretsReader->get("azure_client_secret"),
        "redirect_uri" => $redirect_uri,
        "grant_type" => "authorization_code",
        "resource" => "https://graph.windows.net", // to access user profile
      ],
      "headers" => [
        "Accept" => "application/json",
      ],
    ];
  }

  /**
   * Implements OpenIDConnectClientInterface::retrieveUserInfo().
   *
   * @param string $access_token
   *   An access token string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveUserInfo(string $access_token): ?array
  {
    $userinfo = parent::retrieveUserInfo($access_token);

    if (isset($userinfo)) {
      // convert properties
      $userinfo["email"] = $userinfo[$primary_email_property];

      if (!isset($userinfo["email"])) {
        // Write watchdog warning.
        $variables = ["@user" => $userinfo[$backup_mail_property]];

        $this->loggerFactory
          ->get("portland")
          ->warning(
            "Email address of user @user not found in UserInfo. Used username instead, please check.",
            $variables
          );

        $userinfo["email"] = $userinfo[$backup_mail_property];
      }

      // create username from the email address
      $userinfo["name"] = implode(" ", [
        $userinfo[$first_name_property],
        $userinfo[$last_name_property],
      ]);
    }

    return $userinfo;
  }
}
